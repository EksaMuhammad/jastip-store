<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerService;
use App\Models\Report;
use App\Models\SupportConversation;
use Illuminate\Support\Facades\DB;

class CustomerSupportRoutingService
{
    /**
     * Cari CS terbaik yang sedang online dan berstatus 'tersedia'.
     * Dipilih berdasarkan beban kerja aktif paling sedikit (least-loaded).
     */
    public function findBestAvailableCs(): ?CustomerService
    {
        return CustomerService::where('is_active', true)
            ->where('is_online', true)
            ->where('work_status', 'tersedia')
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '>=', now()->subMinutes(15)) // guard jika sesi terputus
            ->withCount(['activeConversations', 'activeReports'])
            ->get()
            ->sortBy(function ($cs) {
                return $cs->active_conversations_count + $cs->active_reports_count;
            })
            ->first();
    }

    /**
     * Rute otomatis pesan chat pelanggan agar langsung ditangani CS yang free,
     * atau dimasukkan ke antrean prioritas jika semua CS sedang sibuk/offline.
     */
    public function routeCustomerChat(Customer $customer): SupportConversation
    {
        return DB::transaction(function () use ($customer) {
            $conversation = SupportConversation::where('customer_id', $customer->id)
                ->lockForUpdate()
                ->first();

            if (!$conversation) {
                $conversation = new SupportConversation([
                    'customer_id' => $customer->id,
                    'status' => 'menunggu',
                    'priority' => 'normal',
                ]);
            }

            // Jika percakapan sudah aktif dengan CS yang masih valid, cukup update timestamp
            if ($conversation->status === 'aktif' && $conversation->assigned_cs_id) {
                $assignedCs = CustomerService::find($conversation->assigned_cs_id);
                if ($assignedCs && $assignedCs->is_active && $assignedCs->is_online) {
                    $conversation->last_message_at = now();
                    $conversation->save();
                    return $conversation;
                }
            }

            // Jika status menunggu / unassigned / CS sebelumnya offline:
            $availableCs = $this->findBestAvailableCs();

            if ($availableCs) {
                $conversation->assigned_cs_id = $availableCs->id;
                $conversation->status = 'aktif';
                $conversation->assigned_at = now();
                $conversation->last_message_at = now();
                $conversation->save();

                // Jika CS ini sudah memegang >= 3 chat aktif, ubah otomatis statusnya jadi 'sibuk'
                $activeCount = $availableCs->activeConversations()->count();
                if ($activeCount >= 3) {
                    $availableCs->update(['work_status' => 'sibuk']);
                }
            } else {
                // Semua CS offline / sibuk: Tempatkan di antrean menunggu agar customer tidak terlantar
                $conversation->assigned_cs_id = null;
                $conversation->status = 'menunggu';
                $conversation->last_message_at = now();
                $conversation->save();
            }

            return $conversation;
        });
    }

    /**
     * CS mengambil (claim) percakapan dari antrean terbuka secara manual.
     */
    public function claimChat(int $customerId, CustomerService $cs): SupportConversation
    {
        return DB::transaction(function () use ($customerId, $cs) {
            $conversation = SupportConversation::firstOrNew(['customer_id' => $customerId]);
            $conversation->assigned_cs_id = $cs->id;
            $conversation->status = 'aktif';
            $conversation->assigned_at = now();
            $conversation->last_message_at = now();
            $conversation->save();

            $cs->update([
                'last_active_at' => now(),
                'is_online' => true,
            ]);

            return $conversation;
        });
    }

    /**
     * CS menyelesaikan sesi percakapan dengan customer.
     */
    public function closeChat(int $customerId, CustomerService $cs): SupportConversation
    {
        return DB::transaction(function () use ($customerId, $cs) {
            $conversation = SupportConversation::where('customer_id', $customerId)->firstOrFail();
            $conversation->status = 'selesai';
            $conversation->closed_at = now();
            $conversation->save();

            // Jika CS sebelumnya bertatus 'sibuk' dan kini chat aktifnya < 3, kembalikan ke 'tersedia'
            if ($cs->work_status === 'sibuk' && $cs->activeConversations()->count() < 3) {
                $cs->update(['work_status' => 'tersedia']);
            }

            return $conversation;
        });
    }

    /**
     * Rute otomatis tiket komplain yang baru dibuat.
     */
    public function routeReport(Report $report): Report
    {
        if ($report->status === 'selesai' || $report->assigned_cs_id) {
            return $report;
        }

        $availableCs = $this->findBestAvailableCs();

        if ($availableCs) {
            $report->update([
                'assigned_cs_id' => $availableCs->id,
                'cs_claim_status' => 'in_progress',
            ]);
        } else {
            $report->update([
                'assigned_cs_id' => null,
                'cs_claim_status' => 'unassigned',
            ]);
        }

        return $report->fresh();
    }

    /**
     * CS mengklaim tiket komplain pesanan.
     */
    public function claimReport(Report $report, CustomerService $cs): Report
    {
        $report->update([
            'assigned_cs_id' => $cs->id,
            'cs_claim_status' => 'in_progress',
        ]);

        $cs->update(['last_active_at' => now()]);

        return $report->fresh();
    }

    /**
     * Update heartbeat online status untuk CS.
     */
    public function updateHeartbeat(CustomerService $cs, ?string $workStatus = null): void
    {
        $data = [
            'last_active_at' => now(),
            'is_online' => true,
        ];

        if ($workStatus && in_array($workStatus, ['tersedia', 'sibuk', 'istirahat'])) {
            $data['work_status'] = $workStatus;
        }

        $cs->update($data);
    }
}

