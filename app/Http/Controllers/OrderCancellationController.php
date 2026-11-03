<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderCancellationLog;
use App\Models\CancellationPolicy;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\WhatsAppService;

class OrderCancellationController extends Controller
{
    public function cancelOrder(Request $request, $id)
    {
        $customer = Auth::guard('customer')->user();
        
        $request->validate([
            'reason' => 'nullable|string|max:1000'
        ]);

        $result = DB::transaction(function () use ($id, $customer, $request) {
            $order = Order::where('id', $id)->where('customer_id', $customer->id)->lockForUpdate()->first();

            if (!$order) {
                return ['success' => false, 'message' => 'Pesanan tidak ditemukan atau bukan milik Anda.'];
            }

            // Allow cancellation during active processing phases and pre-deal phases
            $cancellableStatuses = ['menunggu_tawaran', 'ada_tawaran', 'diproses', 'barang_diambil', 'sedang_diantar', 'tiba_tujuan'];
            if (!in_array($order->status, $cancellableStatuses)) {
                return ['success' => false, 'message' => 'Pesanan ini tidak dapat dibatalkan pada tahap ini (sudah selesai/dibatalkan).'];
            }

            if (in_array($order->status, ['menunggu_tawaran', 'ada_tawaran'])) {
                // Cancel before deal (no payment made yet, no refund)
                $stageCategory = 'sebelum_deal';
                $refundPercent = 0;
                $jastiperPercent = 0;
                $totalPaid = 0;
                $totalRefund = 0;
                $jastiperCompensation = 0;

                // Bersihkan antrean tawaran pending milik jastiper untuk order ini
                \App\Models\Offer::where('order_id', $order->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'rejected']);
            } else {
                // Determine stage for policy (active order, already paid)
                // Jika pesanan dibatalkan saat barang_diambil / sedang_diantar atau karena penyesuaian harga toko, jastiper sudah berada di lokasi belanja
                $isAtLocation = in_array($order->status, ['barang_diambil', 'sedang_diantar', 'tiba_tujuan']) 
                    || $order->priceAdjustments()->whereIn('status', ['pending', 'rejected', 'cancelled'])->exists()
                    || str_contains(strtolower($request->reason ?? ''), 'harga');

                $stageCategory = $isAtLocation ? 'setelah_diambil' : 'sebelum_diambil';
                
                // Ambil total dana riil yang sudah dibayarkan customer (Payment lunas/verified atau downpayment_amount)
                $paidPayments = (float) $order->payments()->whereIn('status', ['lunas', 'verified'])->sum('amount');
                $totalPaid = $paidPayments > 0 ? $paidPayments : (float) ($order->downpayment_amount > 0 ? $order->downpayment_amount : $order->agreed_fare);
                $deliveryFare = (float) ($order->agreed_fare ?: $order->estimated_fare);

                if ($isAtLocation) {
                    // Jastiper sudah berada di lokasi belanja / toko:
                    // Ongkir kurir masuk ke dompet jastiper sebagai kompensasi perjalanan
                    // Sisa pembayaran (yaitu uang belanja barang) dikembalikan penuh ke dompet customer
                    $jastiperCompensation = min($totalPaid, $deliveryFare);
                    $totalRefund = max(0, $totalPaid - $jastiperCompensation);
                } else {
                    // Sebelum jastiper ke lokasi / belum berangkat:
                    // Refund 100% ke customer, 0 kompensasi jastiper
                    $totalRefund = $totalPaid;
                    $jastiperCompensation = 0;
                }
            }

            // Update Order
            $order->update([
                'status' => 'dibatalkan',
                'cancelled_by_role' => 'customer',
                'cancelled_by_id' => $customer->id,
                'cancellation_reason' => $request->reason ?? 'Dibatalkan oleh customer',
            ]);

            // Log Cancellation
            OrderCancellationLog::create([
                'order_id' => $order->id,
                'cancelled_by_role' => 'customer',
                'cancelled_by_id' => $customer->id,
                'stage' => $stageCategory,
                'reason' => $request->reason ?? 'Dibatalkan oleh customer',
                'total_refund' => $totalRefund,
                'jastiper_compensation' => $jastiperCompensation,
            ]);

            // Catat ke Audit Log Aktivitas Terpadu
            \App\Services\ActivityLogger::logOrder(
                $order,
                'order_cancelled',
                "Pesanan #{$order->id} Dibatalkan",
                "Customer {$customer->name} membatalkan pesanan #{$order->id} (Tahap: {$stageCategory}). Alasan: \"" . ($request->reason ?? 'Dibatalkan oleh customer') . "\". Refund: Rp " . number_format($totalRefund, 0, ',', '.') . ", Kompensasi Jastiper: Rp " . number_format($jastiperCompensation, 0, ',', '.'),
                $customer,
                'pesanan',
                ['stage' => $stageCategory, 'total_refund' => $totalRefund, 'jastiper_compensation' => $jastiperCompensation]
            );

            // Credit Refund to Customer Wallet
            if ($totalRefund > 0) {
                $customerWallet = $customer->wallet ?: \App\Models\Wallet::firstOrCreate(
                    ['owner_role' => 'customer', 'owner_id' => $customer->id],
                    ['balance' => 0]
                );
                app(\App\Services\WalletService::class)->credit(
                    $customerWallet,
                    $totalRefund,
                    'refund_pembatalan',
                    $order->id,
                    'Refund pembatalan pesanan #' . $order->id
                );
            }

            // Credit Compensation to Jastiper Wallet
            if ($jastiperCompensation > 0) {
                $jastiper = $order->jastiper;
                if ($jastiper) {
                    $jastiperWallet = $jastiper->wallet ?: \App\Models\Wallet::firstOrCreate(
                        ['owner_role' => 'jastiper', 'owner_id' => $jastiper->id],
                        ['balance' => 0]
                    );
                    app(\App\Services\WalletService::class)->credit(
                        $jastiperWallet,
                        $jastiperCompensation,
                        'kompensasi_pembatalan',
                        $order->id,
                        'Kompensasi pembatalan pesanan #' . $order->id
                    );
                }
            }

            return ['success' => true, 'order' => $order, 'refund' => $totalRefund, 'jastiper_compensation' => $jastiperCompensation];
        });

        if (!$result['success']) {
            return $request->wantsJson() 
                ? response()->json($result, 400) 
                : redirect()->back()->with('error', $result['message']);
        }

        // Notify Jastiper
        $order = $result['order'];
        $jastiper = $order->jastiper;
        if ($jastiper) {
            $msg = "Pesanan \"{$order->description}\" telah dibatalkan oleh customer. ";
            if ($result['jastiper_compensation'] > 0) {
                $compFormatted = 'Rp ' . number_format($result['jastiper_compensation'], 0, ',', '.');
                $msg .= "Kompensasi sebesar {$compFormatted} telah ditambahkan ke dompet Anda.";
            }
            WhatsAppService::sendMessage($jastiper->phone_number, $msg);
        }

        $successMsg = 'Pesanan berhasil dibatalkan.';
        if ($result['refund'] > 0) {
            $successMsg .= ' Refund sebesar Rp ' . number_format($result['refund'], 0, ',', '.') . ' telah dikembalikan ke dompet Anda.';
        }

        return $request->wantsJson() 
            ? response()->json(['success' => true, 'message' => $successMsg])
            : redirect()->back()->with('success', $successMsg);
    }
}
