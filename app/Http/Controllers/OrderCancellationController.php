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
                $stageCategory = ($order->status === 'diproses') ? 'sebelum_diambil' : 'setelah_diambil';
                
                $policy = CancellationPolicy::where('stage', $stageCategory)->first();
                if (!$policy) {
                    // Default policy if not set: 100% refund before taken, 70% after
                    $refundPercent = ($stageCategory === 'sebelum_diambil') ? 100 : 70;
                    $jastiperPercent = 100 - $refundPercent;
                } else {
                    $refundPercent = $policy->refund_percentage;
                    $jastiperPercent = $policy->jastiper_compensation_percentage;
                }

                $totalPaid = $order->agreed_fare; // Paid amount
                $totalRefund = ($totalPaid * $refundPercent) / 100;
                $jastiperCompensation = ($totalPaid * $jastiperPercent) / 100;
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

            // Credit Refund to Customer Wallet
            if ($totalRefund > 0) {
                $customerWallet = $customer->wallet;
                if ($customerWallet) {
                    $customerWallet->increment('balance', $totalRefund);
                    
                    WalletTransaction::create([
                        'wallet_id' => $customerWallet->id,
                        'type' => 'credit',
                        'amount' => $totalRefund,
                        'description' => 'Refund pembatalan pesanan #' . $order->id,
                        'reference_id' => $order->id,
                        'status' => 'success'
                    ]);
                }
            }

            // Credit Compensation to Jastiper Wallet
            if ($jastiperCompensation > 0) {
                $jastiper = $order->jastiper;
                if ($jastiper && $jastiper->wallet) {
                    $jastiper->wallet->increment('balance', $jastiperCompensation);
                    
                    WalletTransaction::create([
                        'wallet_id' => $jastiper->wallet->id,
                        'type' => 'credit',
                        'amount' => $jastiperCompensation,
                        'description' => 'Kompensasi pembatalan pesanan #' . $order->id,
                        'reference_id' => $order->id,
                        'status' => 'success'
                    ]);
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
