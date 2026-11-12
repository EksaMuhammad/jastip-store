<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderPriceAdjustment;
use App\Models\OrderCancellationLog;
use App\Models\Report;
use App\Models\SupportMessage;
use App\Models\Customer;
use App\Models\Jastiper;
use App\Models\Admin;
use App\Models\Topup;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Catat aktivitas umum ke database.
     */
    public static function log(array $data): ActivityLog
    {
        $ip = Request::ip() ?? null;

        return ActivityLog::create([
            'user_type' => $data['user_type'] ?? 'system',
            'user_id' => $data['user_id'] ?? null,
            'user_name' => $data['user_name'] ?? 'System',
            'order_id' => $data['order_id'] ?? null,
            'category' => $data['category'] ?? 'pesanan',
            'action' => $data['action'] ?? 'activity',
            'title' => $data['title'] ?? 'Aktivitas Tercatat',
            'description' => $data['description'] ?? null,
            'properties' => $data['properties'] ?? [],
            'ip_address' => $data['ip_address'] ?? $ip,
            'created_at' => $data['created_at'] ?? now(),
            'updated_at' => $data['created_at'] ?? now(),
        ]);
    }

    /**
     * Catat aktivitas pesanan & statusnya.
     */
    public static function logOrder(
        Order $order,
        string $action,
        string $title,
        string $description,
        $actor = null,
        string $category = 'pesanan',
        array $properties = []
    ): ActivityLog {
        $userType = 'system';
        $userId = null;
        $userName = 'System';

        if ($actor instanceof Customer) {
            $userType = 'customer';
            $userId = $actor->id;
            $userName = $actor->name;
        } elseif ($actor instanceof Jastiper) {
            $userType = 'jastiper';
            $userId = $actor->id;
            $userName = $actor->name;
        } elseif ($actor instanceof Admin) {
            $userType = 'admin';
            $userId = $actor->id;
            $userName = $actor->name ?? 'Admin';
        }

        return self::log([
            'user_type' => $userType,
            'user_id' => $userId,
            'user_name' => $userName,
            'order_id' => $order->id,
            'category' => $category,
            'action' => $action,
            'title' => $title,
            'description' => $description,
            'properties' => array_merge([
                'order_description' => $order->description,
                'status' => $order->status,
                'customer_id' => $order->customer_id,
                'jastiper_id' => $order->jastiper_id,
            ], $properties),
        ]);
    }

    /**
     * Catat aktivitas transaksi keuangan & top-up saldo e-wallet.
     */
    public static function logTopup(
        Topup $topup,
        string $action,
        string $title,
        string $description,
        $actor = null,
        array $properties = []
    ): ActivityLog {
        $userType = 'customer';
        $userId = null;
        $userName = 'Customer';

        if ($actor instanceof Admin) {
            $userType = 'admin';
            $userId = $actor->id;
            $userName = $actor->name ?? 'Admin Support';
        } elseif ($actor instanceof Customer) {
            $userType = 'customer';
            $userId = $actor->id;
            $userName = $actor->name;
        } elseif ($actor instanceof Jastiper) {
            $userType = 'jastiper';
            $userId = $actor->id;
            $userName = $actor->name;
        } else {
            $owner = $topup->wallet?->owner;
            if ($owner) {
                $userType = $topup->wallet->owner_role ?? 'customer';
                $userId = $owner->id;
                $userName = $owner->name;
            }
        }

        return self::log([
            'user_type' => $userType,
            'user_id' => $userId,
            'user_name' => $userName,
            'order_id' => null,
            'category' => 'keuangan',
            'action' => $action,
            'title' => $title,
            'description' => $description,
            'properties' => array_merge([
                'topup_id' => $topup->id,
                'gateway_reference' => $topup->gateway_reference,
                'amount' => (float) $topup->amount,
                'topup_amount' => (float) $topup->amount,
                'channel' => $topup->channel,
                'status' => $topup->status,
            ], $properties),
            'created_at' => $topup->verified_at ?? $topup->updated_at ?? now(),
        ]);
    }

    /**
     * Backfill data historis agar log aktivitas langsung terisi lengkap.
     */
    public static function backfillExistingHistory(): int
    {
        $count = 0;

        // 1. Backfill Orders (Pembuatan Pesanan & Deal)
        $orders = Order::with(['customer', 'jastiper'])->get();
        foreach ($orders as $order) {
            // A. Pembuatan Pesanan
            $existsCreated = ActivityLog::where('order_id', $order->id)->where('action', 'order_created')->exists();
            if (!$existsCreated) {
                $custName = $order->customer?->name ?? "Customer #{$order->customer_id}";
                self::log([
                    'user_type' => 'customer',
                    'user_id' => $order->customer_id,
                    'user_name' => $custName,
                    'order_id' => $order->id,
                    'category' => 'pesanan',
                    'action' => 'order_created',
                    'title' => "Pesanan #{$order->id} Dibuat",
                    'description' => "Customer {$custName} membuat pesanan: \"{$order->description}\" (Estimasi: Rp " . number_format($order->estimated_fare, 0, ',', '.') . ")",
                    'properties' => ['origin' => $order->origin_address, 'destination' => $order->destination_address],
                    'created_at' => $order->created_at,
                ]);
                $count++;
            }

            // B. Deal / Penugasan Jastiper
            if ($order->jastiper_id && !in_array($order->status, ['menunggu_tawaran', 'ada_tawaran'])) {
                $existsDeal = ActivityLog::where('order_id', $order->id)->where('action', 'order_deal')->exists();
                if (!$existsDeal) {
                    $jastiperName = $order->jastiper?->name ?? "Jastiper #{$order->jastiper_id}";
                    self::log([
                        'user_type' => 'jastiper',
                        'user_id' => $order->jastiper_id,
                        'user_name' => $jastiperName,
                        'order_id' => $order->id,
                        'category' => 'pesanan',
                        'action' => 'order_deal',
                        'title' => "Tawaran Jastiper Diterima (Deal)",
                        'description' => "Pesanan #{$order->id} telah mencapai kesepakatan deal dengan mitra jastiper {$jastiperName} (Tarif Deal: Rp " . number_format($order->agreed_fare, 0, ',', '.') . ")",
                        'properties' => ['agreed_fare' => $order->agreed_fare, 'downpayment' => $order->downpayment_amount],
                        'created_at' => $order->created_at->addMinutes(2),
                    ]);
                    $count++;
                }
            }

            // C. Pergerakan Jastiper: Barang Diambil
            if (in_array($order->status, ['barang_diambil', 'sedang_diantar', 'tiba_tujuan', 'selesai'])) {
                $existsPickup = ActivityLog::where('order_id', $order->id)->where('action', 'goods_picked_up')->exists();
                if (!$existsPickup) {
                    $jastiperName = $order->jastiper?->name ?? "Jastiper";
                    self::log([
                        'user_type' => 'jastiper',
                        'user_id' => $order->jastiper_id,
                        'user_name' => $jastiperName,
                        'order_id' => $order->id,
                        'category' => 'pengantaran',
                        'action' => 'goods_picked_up',
                        'title' => "Jastiper Mengambil Barang di Toko",
                        'description' => "Jastiper {$jastiperName} telah tiba di lokasi toko dan mengonfirmasi barang pesanan #{$order->id} telah diambil.",
                        'properties' => ['status' => 'barang_diambil'],
                        'created_at' => $order->updated_at->subMinutes(15),
                    ]);
                    $count++;
                }
            }

            // D. Pesanan Selesai
            if ($order->status === 'selesai') {
                $existsDone = ActivityLog::where('order_id', $order->id)->where('action', 'order_completed')->exists();
                if (!$existsDone) {
                    self::log([
                        'user_type' => 'system',
                        'user_id' => null,
                        'user_name' => 'Sistem JastipKuy',
                        'order_id' => $order->id,
                        'category' => 'pesanan',
                        'action' => 'order_completed',
                        'title' => "Pesanan #{$order->id} Berhasil Diselesaikan",
                        'description' => "Barang telah diterima oleh penerima, kode serah terima terverifikasi, dan pelepasan saldo jastiper telah tuntas.",
                        'properties' => ['status' => 'selesai'],
                        'created_at' => $order->updated_at,
                    ]);
                    $count++;
                }
            }
        }

        // 2. Backfill Order Cancellations
        $cancellations = OrderCancellationLog::with(['order.customer', 'order.jastiper'])->get();
        foreach ($cancellations as $can) {
            $exists = ActivityLog::where('order_id', $can->order_id)->where('action', 'order_cancelled')->exists();
            if (!$exists) {
                $actorName = $can->cancelled_by_role === 'customer' 
                    ? ($can->order?->customer?->name ?? 'Customer')
                    : ($can->order?->jastiper?->name ?? 'Jastiper');

                $refundFormatted = 'Rp ' . number_format($can->total_refund, 0, ',', '.');
                $compFormatted = 'Rp ' . number_format($can->jastiper_compensation, 0, ',', '.');

                self::log([
                    'user_type' => $can->cancelled_by_role ?? 'customer',
                    'user_id' => $can->cancelled_by_id,
                    'user_name' => $actorName,
                    'order_id' => $can->order_id,
                    'category' => 'pesanan',
                    'action' => 'order_cancelled',
                    'title' => "Pesanan #{$can->order_id} Dibatalkan",
                    'description' => "Pesanan #{$can->order_id} dibatalkan oleh {$actorName}. Tahap: {$can->stage}. Alasan: \"{$can->reason}\". Refund Customer: {$refundFormatted}, Kompensasi Jastiper: {$compFormatted}.",
                    'properties' => [
                        'stage' => $can->stage,
                        'reason' => $can->reason,
                        'total_refund' => $can->total_refund,
                        'jastiper_compensation' => $can->jastiper_compensation,
                    ],
                    'created_at' => $can->created_at,
                ]);
                $count++;
            }
        }

        // 3. Backfill Price Adjustments
        $adjustments = OrderPriceAdjustment::with(['order.customer', 'jastiper'])->get();
        foreach ($adjustments as $adj) {
            $exists = ActivityLog::where('order_id', $adj->order_id)->where('action', 'price_adjustment_' . $adj->status)->exists();
            if (!$exists) {
                $jName = $adj->jastiper?->name ?? 'Jastiper';
                $diffFormatted = ($adj->difference >= 0 ? '+' : '-') . 'Rp ' . number_format(abs($adj->difference), 0, ',', '.');

                self::log([
                    'user_type' => 'jastiper',
                    'user_id' => $adj->jastiper_id,
                    'user_name' => $jName,
                    'order_id' => $adj->order_id,
                    'category' => 'harga_toko',
                    'action' => 'price_adjustment_proposed',
                    'title' => "Pengajuan Penyesuaian Harga Toko",
                    'description' => "Jastiper {$jName} mengajukan perubahan harga barang \"{$adj->item_name}\" ({$diffFormatted}) pada pesanan #{$adj->order_id}. Alasan: \"{$adj->reason}\". Status akhir: {$adj->status}.",
                    'properties' => [
                        'adjustment_id' => $adj->id,
                        'item_name' => $adj->item_name,
                        'original_price' => $adj->original_price,
                        'proposed_price' => $adj->proposed_price,
                        'difference' => $adj->difference,
                        'status' => $adj->status,
                    ],
                    'created_at' => $adj->created_at,
                ]);
                $count++;
            }
        }

        // 4. Backfill Complaint Reports (Tiket Sengketa)
        $reports = Report::with(['order.customer', 'decider'])->get();
        foreach ($reports as $rep) {
            $existsRep = ActivityLog::where('order_id', $rep->order_id)->where('action', 'report_filed')->exists();
            if (!$existsRep) {
                $cName = $rep->order?->customer?->name ?? "Customer #{$rep->reporter_id}";
                self::log([
                    'user_type' => 'customer',
                    'user_id' => $rep->reporter_id,
                    'user_name' => $cName,
                    'order_id' => $rep->order_id,
                    'category' => 'komplain_tiket',
                    'action' => 'report_filed',
                    'title' => "Tiket Komplain Pengaduan #REP-{$rep->id}",
                    'description' => "Customer {$cName} mengajukan komplain resmi untuk pesanan #{$rep->order_id} (Kategori: {$rep->category}). Keterangan: \"{$rep->description}\"",
                    'properties' => ['report_id' => $rep->id, 'category' => $rep->category, 'status' => $rep->status],
                    'created_at' => $rep->created_at,
                ]);
                $count++;
            }

            if ($rep->status !== 'menunggu' && $rep->decided_at) {
                $existsDecided = ActivityLog::where('order_id', $rep->order_id)->where('action', 'report_resolved')->exists();
                if (!$existsDecided) {
                    $adminName = $rep->decider?->name ?? 'Admin Support';
                    self::log([
                        'user_type' => 'admin',
                        'user_id' => $rep->decided_by,
                        'user_name' => $adminName,
                        'order_id' => $rep->order_id,
                        'category' => 'komplain_tiket',
                        'action' => 'report_resolved',
                        'title' => "Tiket Komplain #REP-{$rep->id} Diselesaikan",
                        'description' => "Admin {$adminName} menyelesaikan tiket komplain pesanan #{$rep->order_id}. Keputusan: {$rep->status}. Solusi: \"{$rep->admin_notes}\"",
                        'properties' => ['report_id' => $rep->id, 'status' => $rep->status, 'refund_amount' => $rep->refund_amount],
                        'created_at' => $rep->decided_at,
                    ]);
                    $count++;
                }
            }
        }

        // 5. Backfill Support Messages
        $supportMessages = SupportMessage::with('customer')->latest()->take(30)->get();
        foreach ($supportMessages as $sm) {
            $existsSm = ActivityLog::where('category', 'chat_cs')->where('created_at', $sm->created_at)->exists();
            if (!$existsSm) {
                $isCust = $sm->sender_role === 'customer';
                $actorName = $isCust ? ($sm->customer?->name ?? 'Customer') : 'Admin Customer Support';
                self::log([
                    'user_type' => $sm->sender_role,
                    'user_id' => $sm->sender_id,
                    'user_name' => $actorName,
                    'order_id' => $sm->order_id,
                    'category' => 'chat_cs',
                    'action' => 'support_chat_message',
                    'title' => $isCust ? "Pesan Bantuan Masuk dari Pelanggan" : "Respon Admin Bantuan CS",
                    'description' => "{$actorName}: \"" . \Illuminate\Support\Str::limit($sm->message, 120) . "\"",
                    'properties' => ['support_message_id' => $sm->id, 'has_attachment' => !empty($sm->attachment_path)],
                    'created_at' => $sm->created_at,
                ]);
                $count++;
            }
        }

        // 6. Backfill Transaksi Top-Up Saldo Keuangan
        $topups = Topup::with('wallet.owner')->get();
        foreach ($topups as $topup) {
            $existsTopup = ActivityLog::where('category', 'keuangan')
                ->where('properties->topup_id', $topup->id)
                ->exists();

            if (!$existsTopup) {
                $owner = $topup->wallet?->owner;
                $ownerRole = $topup->wallet?->owner_role ?? 'customer';
                $ownerName = $owner?->name ?? "User #{$topup->wallet?->owner_id}";
                $amountFormatted = 'Rp ' . number_format((float) $topup->amount, 0, ',', '.');
                $channelName = strtoupper(str_replace('_', ' ', $topup->channel ?? 'Midtrans'));

                $action = match ($topup->status) {
                    'berhasil' => 'topup_berhasil',
                    'ditolak_pengajuan' => 'topup_ditolak',
                    'gagal' => 'topup_gagal',
                    default => 'topup_menunggu',
                };

                $title = match ($topup->status) {
                    'berhasil' => "Top-Up Saldo Berhasil (+{$amountFormatted})",
                    'ditolak_pengajuan' => "Pengajuan Top-Up Ditolak ({$amountFormatted})",
                    'gagal' => "Top-Up Saldo Gagal ({$amountFormatted})",
                    default => "Pengajuan Top-Up Saldo ({$amountFormatted})",
                };

                $desc = match ($topup->status) {
                    'berhasil' => "Pengguna {$ownerName} berhasil melakukan top-up saldo sebesar {$amountFormatted} via {$channelName} (Ref: {$topup->gateway_reference}). Saldo telah dikreditkan ke e-wallet.",
                    'ditolak_pengajuan' => "Pengajuan top-up oleh {$ownerName} sebesar {$amountFormatted} (Ref: {$topup->gateway_reference}) ditolak. Catatan: \"{$topup->rejection_note}\".",
                    'gagal' => "Transaksi top-up {$ownerName} sebesar {$amountFormatted} via {$channelName} telah dibatalkan atau kedaluwarsa.",
                    default => "Pengguna {$ownerName} mengajukan tiket top-up saldo sebesar {$amountFormatted} via {$channelName} (Ref: {$topup->gateway_reference}). Menunggu pembayaran.",
                };

                self::log([
                    'user_type' => $ownerRole,
                    'user_id' => $topup->wallet?->owner_id,
                    'user_name' => $ownerName,
                    'order_id' => null,
                    'category' => 'keuangan',
                    'action' => $action,
                    'title' => $title,
                    'description' => $desc,
                    'properties' => [
                        'topup_id' => $topup->id,
                        'gateway_reference' => $topup->gateway_reference,
                        'amount' => (float) $topup->amount,
                        'topup_amount' => (float) $topup->amount,
                        'channel' => $topup->channel,
                        'status' => $topup->status,
                    ],
                    'created_at' => $topup->verified_at ?? $topup->updated_at ?? $topup->created_at,
                ]);
                $count++;
            }
        }

        return $count;
    }
}
