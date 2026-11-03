@extends('layouts.support')

@section('title', 'Rincian Pesananmu - JastipKuy')

@section('styles')
<style>
    /* Watermark Paid / Unpaid Stamp */
    .stamp-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 6px;
        font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
        font-weight: 800;
        font-size: 11px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        transform: rotate(-10deg);
        user-select: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .stamp-paid {
        border: 2px solid rgba(16, 185, 129, 0.55);
        color: #059669;
        background: rgba(16, 185, 129, 0.05);
    }
    .stamp-unpaid {
        border: 2px solid rgba(245, 158, 11, 0.55);
        color: #d97706;
        background: rgba(245, 158, 11, 0.05);
    }
    .stamp-cancelled {
        border: 2px solid rgba(239, 68, 68, 0.55);
        color: #dc2626;
        background: rgba(239, 68, 68, 0.05);
    }
</style>
@endsection

@section('content')
<div class="min-h-screen bg-[#F6F7F9] pb-24 text-slate-800" x-data="{
    copied: false,
    copyOrderNumber(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
        this.copied = true;
        setTimeout(() => this.copied = false, 2500);
    },
    shareOrder() {
        if (navigator.share) {
            navigator.share({
                title: 'Rincian Pesanan JastipKuy',
                text: 'Pesanan Jastip #{{ $order->id }}',
                url: window.location.href
            }).catch(() => {});
        } else {
            this.copyOrderNumber(window.location.href);
            alert('Tautan pesanan disalin ke papan klip!');
        }
    },
    showHelp: false
}" @order-price-adjusted.window="setTimeout(() => window.location.reload(), 1000)">

    <!-- Mobile-First Header (Inspired by ShopeeFood Reference) -->
    <div class="sticky top-0 z-40 bg-white border-b border-slate-100 shadow-xs">
        <div class="max-w-md mx-auto px-4 h-14 flex items-center justify-between">
            <a href="{{ route('customer.activity') }}" class="text-slate-700 hover:text-rose-600 transition p-1.5 -ml-1.5 rounded-full hover:bg-slate-50">
                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            
            <h1 class="font-extrabold text-base text-slate-900 tracking-tight">Rincian Pesananmu</h1>
            
            <div class="flex items-center gap-1 -mr-1.5">
                <button type="button" @click="shareOrder()" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-full transition" title="Bagikan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                </button>
                <button type="button" @click="showHelp = true" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-full transition" title="Bantuan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" stroke-width="2"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="max-w-md mx-auto px-3.5 sm:px-4 pt-3.5 space-y-3.5">

        <!-- Flash Notice / Toast Copy notification -->
        <div x-show="copied" x-transition.opacity class="fixed top-16 left-1/2 -translate-x-1/2 z-50 bg-slate-900/90 backdrop-blur-md text-white text-xs font-bold px-4 py-2 rounded-full shadow-lg flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span>No. Pesanan berhasil disalin!</span>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold p-3 rounded-2xl flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold p-3 rounded-2xl flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Sleek Quick Banner (ShopeeFood style) -->
        <div class="bg-gradient-to-r from-rose-50 to-orange-50/60 border border-rose-100/80 rounded-2xl p-3 flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-white text-rose-600 shadow-2xs border border-rose-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-slate-800 truncate">Pantau Pesanan di JastipKuy</p>
                    <p class="text-[10px] text-slate-500 truncate">Jastiper siap mengantar dengan aman</p>
                </div>
            </div>
            <a href="{{ route('customer.dashboard') }}" class="bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition shrink-0 shadow-2xs">
                Beranda
            </a>
        </div>

        @php
            $isPaid = in_array($order->status, ['deal', 'diproses', 'barang_diambil', 'sedang_diantar', 'tiba_tujuan', 'selesai'])
                || ($latestPayment && in_array($latestPayment->status, ['settlement', 'verified', 'paid', 'lunas']));
            $isCancelled = in_array($order->status, ['dibatalkan', 'ditolak']);
            $isPriceCancellation = $order->status === 'dibatalkan' && (($order->priceAdjustments && $order->priceAdjustments->where('status', 'cancelled')->isNotEmpty()) || str_contains(strtolower($order->cancellation_reason ?? ''), 'harga'));
            
            // Perhitungan item dan subtotal
            $items = $order->orderItems;
            $itemsCount = $items->sum('quantity') ?: 1;

            // Penyesuaian harga barang asli toko
            $approvedAdjustments = $order->priceAdjustments ? $order->priceAdjustments->where('status', 'approved') : collect();
            $pendingAdjustment = $order->priceAdjustments ? $order->priceAdjustments->where('status', 'pending')->first() : null;
            $totalPriceAdjustments = (float) $approvedAdjustments->sum('difference');

            // Subtotal awal (sebelum penyesuaian)
            $initialSubtotal = $items->sum(function($item) {
                $origPrice = ($item->estimated_price !== null && $item->estimated_price > 0) ? $item->estimated_price : ($item->actual_price ?? 0);
                return $origPrice * ($item->quantity ?: 1);
            });
            
            // Subtotal terkini (setelah harga disesuaikan)
            $subtotalItems = $items->sum(function($item) {
                $price = $item->actual_price ?: $item->estimated_price ?: 0;
                return $price * ($item->quantity ?: 1);
            });
            
            // Ongkir & Biaya Tambahan
            $deliveryFee = (float) ($order->agreed_fare ?? $order->estimated_fare ?? 0);
            $serviceFee = 2000; // Biaya layanan standar aplikasi
            $multiStopFee = (is_array($order->multi_stops) && count($order->multi_stops) > 1) ? (count($order->multi_stops) - 1) * 3000 : 0;
            
            // Total tagihan
            $grandTotal = $subtotalItems + $deliveryFee + $serviceFee + $multiStopFee;
            if ($grandTotal <= 0 && $deliveryFee > 0) {
                $grandTotal = $deliveryFee + $serviceFee;
            }
        @endphp

        <!-- Callout Alert: Pesanan Bermasalah / Sedang Diinvestigasi Komplain -->
        @if($order->status === 'bermasalah')
        <div class="bg-amber-50 border border-amber-300 rounded-2xl p-4 space-y-3 shadow-2xs animate-fade-in">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shrink-0 shadow-xs">
                    ⚖️
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5 mb-1">
                        <span class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-full bg-amber-600 text-white">
                            Dalam Investigasi
                        </span>
                        <span class="text-xs font-black text-amber-950">Pengaduan Sengketa Barang Diajukan</span>
                    </div>
                    <p class="text-xs text-amber-900 leading-relaxed font-medium">
                        Pengaduan resmi telah diajukan untuk pesanan ini. Pembayaran ditahan di Virtual Escrow dan Tim Admin JastipKuy sedang melakukan investigasi terhadap bukti yang diberikan.
                    </p>
                </div>
            </div>
            <div class="pt-1 flex items-center gap-2 border-t border-amber-200">
                <a href="{{ route('customer.orders.report', $order->id) }}" class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white text-[11px] font-extrabold rounded-xl transition shadow-xs flex items-center gap-1">
                    <span>📋</span> Lihat Status Pengaduan
                </a>
                <button type="button" 
                    @click="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: {{ $order->id }}, orderLabel: '{{ addslashes($order->description) }}' } }))"
                    class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-[11px] font-bold rounded-xl transition shadow-2xs flex items-center gap-1">
                    <span>💬</span> Chat Pesanan
                </button>
            </div>
        </div>
        @endif

        <!-- Callout Alert: Jika ada Penyesuaian Harga Menunggu Konfirmasi -->
        @if($pendingAdjustment)
        <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-4 space-y-3 shadow-md animate-fade-in">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-2.5 min-w-0">
                    <span class="text-2xl shrink-0">🏷️</span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-600 text-white">
                                Perlu Konfirmasi
                            </span>
                            <p class="text-xs font-black text-amber-950">Ada Perubahan Harga di Toko!</p>
                        </div>
                        <p class="text-xs text-amber-900 mt-1 font-medium leading-relaxed">
                            Jastiper melaporkan harga asli <strong>{{ $pendingAdjustment->item_name }}</strong> adalah <strong>Rp{{ number_format($pendingAdjustment->proposed_price, 0, ',', '.') }}</strong> (Selisih: <strong class="text-amber-700">+Rp{{ number_format($pendingAdjustment->difference, 0, ',', '.') }}</strong>).
                        </p>
                        @if($pendingAdjustment->reason)
                            <p class="text-[11px] text-amber-800 italic mt-0.5">
                                Catatan jastiper: "{{ $pendingAdjustment->reason }}"
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Action Buttons: Bisa Konfirmasi Langsung Disini -->
            <div class="pt-2.5 border-t border-amber-200/90 flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('customer.price-adjustments.respond', $pendingAdjustment->id) }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" onclick="return confirm('Setujui kenaikan harga Rp{{ number_format($pendingAdjustment->difference, 0, ',', '.') }}? Selisih akan diselesaikan saat serah terima barang.')" 
                        class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-[11px] font-black uppercase tracking-wider rounded-xl transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                        <span>✔️</span> Setujui (+Rp{{ number_format($pendingAdjustment->difference, 0, ',', '.') }})
                    </button>
                </form>

                <form method="POST" action="{{ route('customer.price-adjustments.respond', $pendingAdjustment->id) }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" onclick="return confirm('Tolak kenaikan harga ini? Jastiper tidak akan membeli item dengan harga baru.')" 
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 text-[11px] font-bold uppercase tracking-wider rounded-xl transition border border-slate-300 flex items-center gap-1 cursor-pointer">
                        <span>❌</span> Tolak
                    </button>
                </form>

                <button type="button" 
                    @click="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: {{ $order->id }}, orderLabel: '{{ addslashes($order->description) }}' } }))"
                    class="px-3.5 py-2 bg-white hover:bg-slate-50 active:scale-95 text-amber-900 border border-amber-300 text-[11px] font-bold rounded-xl transition shadow-2xs flex items-center gap-1.5 ml-auto cursor-pointer">
                    <span>💬</span> Buka Chat & Bukti Foto
                </button>
            </div>
        </div>
        @endif

        <!-- Callout Alert: Pesanan Dibatalkan karena Kenaikan Harga Toko -->
        @if($isPriceCancellation)
        <div class="bg-rose-50 border border-rose-200/90 rounded-2xl p-4 space-y-3 shadow-2xs">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-100 border border-rose-200 flex items-center justify-center text-xl shrink-0 shadow-xs">
                    🏷️
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5 mb-1">
                        <span class="text-[9px] font-black tracking-wider uppercase px-2 py-0.5 rounded-full bg-rose-600 text-white">
                            Pesanan Dibatalkan
                        </span>
                        <span class="text-xs font-black text-rose-950">Kenaikan Harga Toko Tidak Disetujui</span>
                    </div>
                    <p class="text-xs text-rose-900 leading-relaxed font-medium">
                        Pesanan ini telah dibatalkan karena terdapat kenaikan harga barang asli di toko yang tidak sesuai dengan estimasi. Sesuai ketentuan JastipKuy, <strong>seluruh dana pembayaran awal Anda (100%) telah dikembalikan ke Saldo Dompet</strong>.
                    </p>
                    @if($order->cancellation_reason)
                        <div class="mt-2 bg-white/90 rounded-xl p-2.5 border border-rose-100/80 text-[11px] text-slate-700">
                            <span class="text-slate-400 font-semibold block text-[10px]">Alasan Pembatalan:</span>
                            <span class="font-bold text-slate-800">{{ $order->cancellation_reason }}</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="pt-1 flex items-center gap-2 border-t border-rose-200/60">
                <a href="{{ route('customer.profile') }}" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white text-[11px] font-extrabold rounded-xl transition shadow-xs flex items-center gap-1">
                    <span>💳</span> Cek Saldo Dompet
                </a>
                <button type="button" 
                    @click="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: {{ $order->id }}, orderLabel: '{{ addslashes($order->description) }}' } }))"
                    class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-[11px] font-bold rounded-xl transition shadow-2xs flex items-center gap-1">
                    <span>💬</span> Riwayat Chat
                </button>
            </div>
        </div>
        @elseif($isCancelled)
        <div class="bg-slate-100 border border-slate-200 rounded-2xl p-3.5 flex items-start gap-3 shadow-2xs">
            <span class="text-xl shrink-0">🚫</span>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-extrabold text-slate-900">Pesanan Telah Dibatalkan</p>
                <p class="text-[11px] text-slate-600 mt-0.5">
                    {{ $order->cancellation_reason ?: 'Pesanan telah dibatalkan sesuai ketentuan layanan.' }}
                </p>
            </div>
        </div>
        @endif

        <!-- CARD 1: Rincian Pesanan (Exact Reference Form) -->
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs space-y-4">
            <h2 class="font-extrabold text-sm text-slate-900 tracking-tight">Rincian Pesanan</h2>

            <!-- Item List -->
            <div class="space-y-3 divide-y divide-slate-50">
                @if($items->isNotEmpty())
                    @foreach($items as $item)
                        @php
                            $itemPrice = $item->actual_price ?: $item->estimated_price;
                            $productImage = $item->product?->image;
                            $itemAdj = $item->priceAdjustment;
                            $hasApprovedAdj = $itemAdj && $itemAdj->status === 'approved';
                            $hasPendingAdj = $itemAdj && $itemAdj->status === 'pending';
                        @endphp
                        <div class="pt-2.5 first:pt-0 flex items-start gap-3">
                            <!-- Thumbnail -->
                            <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-xl bg-slate-100 border border-slate-200/80 overflow-hidden shrink-0 flex items-center justify-center">
                                @if($productImage)
                                    <img src="{{ $productImage }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-rose-50 flex items-center justify-center text-rose-500 font-bold text-xs">
                                        JK
                                    </div>
                                @endif
                            </div>

                            <!-- Name & Qty -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 leading-snug">
                                            <span class="text-slate-500 font-semibold mr-1">{{ $item->quantity }} x</span>
                                            {{ preg_replace('/\[.*?\]/', '', $item->name) }}
                                        </p>

                                        @if($hasApprovedAdj)
                                            <!-- Badge Penyesuaian Disetujui -->
                                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                <span class="text-[9px] font-bold text-slate-400 line-through">
                                                    Est. Rp{{ number_format($itemAdj->original_price, 0, ',', '.') }}
                                                </span>
                                                <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded flex items-center gap-0.5">
                                                    <span>✓</span> Harga Toko: Rp{{ number_format($item->actual_price, 0, ',', '.') }} (+Rp{{ number_format($itemAdj->difference, 0, ',', '.') }})
                                                </span>
                                            </div>
                                        @elseif($hasPendingAdj)
                                            <!-- Badge Penyesuaian Pending -->
                                            <div class="mt-1">
                                                <span class="text-[9px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded flex items-center gap-1 inline-flex">
                                                    <span>⏳</span> Konfirmasi penyesuaian +Rp{{ number_format($itemAdj->difference, 0, ',', '.') }} di Chat
                                                </span>
                                            </div>
                                        @endif

                                        @if($item->notes)
                                            <p class="text-[10px] text-slate-400 mt-0.5 line-clamp-1 italic">
                                                Catatan: {{ $item->notes }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-xs font-bold text-slate-900 block">
                                            Rp{{ number_format($itemPrice * $item->quantity, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Fallback Jastip Request Row -->
                    <div class="flex items-start gap-3">
                        <div class="w-13 h-13 rounded-xl bg-rose-50 border border-rose-100 overflow-hidden shrink-0 flex items-center justify-center text-rose-600 font-bold text-xs">
                            JK
                        </div>
                        <div class="flex-1 min-w-0 flex items-start justify-between gap-2">
                            <div>
                                <p class="text-xs font-bold text-slate-800 leading-snug">
                                    <span class="text-slate-500 font-semibold mr-1">1 x</span>
                                    {{ $order->description }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">Layanan Jastip: {{ ucfirst(str_replace('-', ' ', $order->category)) }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs font-bold text-slate-900">
                                    Rp{{ number_format($deliveryFee, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Price Breakdown Rows -->
            <div class="border-t border-slate-100 pt-3 space-y-2 text-xs">
                <!-- Subtotal Pesanan Awal -->
                <div class="flex items-center justify-between font-bold text-slate-800">
                    <span>Subtotal Pesanan ({{ $itemsCount }} menu)</span>
                    <span>Rp{{ number_format($initialSubtotal ?: $subtotalItems ?: $deliveryFee, 0, ',', '.') }}</span>
                </div>

                <!-- Penyesuaian Harga Asli Toko (Jika Ada) -->
                @if($totalPriceAdjustments > 0)
                <div class="flex items-center justify-between text-emerald-800 font-bold bg-emerald-50/70 p-2.5 rounded-xl border border-emerald-200/80">
                    <span class="flex items-center gap-1.5 text-xs">
                        <span>🏷️</span> Penyesuaian Harga Toko (+Disetujui)
                    </span>
                    <span class="text-xs font-black">+Rp{{ number_format($totalPriceAdjustments, 0, ',', '.') }}</span>
                </div>
                @endif

                <!-- Biaya Pengiriman -->
                <div class="flex items-center justify-between text-slate-500 font-medium">
                    <span class="flex items-center gap-1">
                        Biaya Pengiriman
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"></circle><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"></path></svg>
                    </span>
                    <span class="text-slate-800 font-semibold">Rp{{ number_format($deliveryFee, 0, ',', '.') }}</span>
                </div>

                <!-- Biaya Layanan -->
                <div class="flex items-center justify-between text-slate-500 font-medium">
                    <span class="flex items-center gap-1">
                        Biaya Layanan
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"></circle><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"></path></svg>
                    </span>
                    <span class="text-slate-800 font-semibold">Rp{{ number_format($serviceFee, 0, ',', '.') }}</span>
                </div>

                <!-- Biaya Multi-Stop / Pengemasan (if any) -->
                @if($multiStopFee > 0)
                <div class="flex items-center justify-between text-slate-500 font-medium">
                    <span class="flex items-center gap-1">
                        Biaya Multi-Stop ({{ count($order->multi_stops) }} Titik)
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"></circle><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"></path></svg>
                    </span>
                    <span class="text-slate-800 font-semibold">Rp{{ number_format($multiStopFee, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>

            <!-- Grand Total with Paid/Unpaid Watermark Stamp -->
            <div class="border-t border-slate-100 pt-3 relative flex items-end justify-between">
                <div>
                    @if($isPaid)
                        <div class="stamp-badge stamp-paid mb-1">
                            Paid
                        </div>
                    @elseif($isCancelled)
                        <div class="stamp-badge stamp-cancelled mb-1">
                            Batal
                        </div>
                    @else
                        <div class="stamp-badge stamp-unpaid mb-1">
                            Belum Lunas
                        </div>
                    @endif
                    <p class="text-[10px] text-slate-400">Sudah termasuk pajak & layanan</p>
                    @if($totalPriceAdjustments > 0)
                        <p class="text-[9px] text-emerald-600 font-extrabold mt-0.5">
                            * Termasuk selisih toko Rp{{ number_format($totalPriceAdjustments, 0, ',', '.') }}
                        </p>
                    @endif
                </div>
                <div class="text-right">
                    <span class="text-lg font-black text-slate-900 tracking-tight">
                        Rp{{ number_format($grandTotal, 0, ',', '.') }}
                    </span>
                </div>
        </div>

        <!-- CARD 2: Informasi Pesanan (Exact Reference Form) -->
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs space-y-3">
            <h2 class="font-extrabold text-sm text-slate-900 tracking-tight">Informasi Pesanan</h2>

            <div class="space-y-2.5 text-xs">
                <!-- Catatan Tambahan -->
                <div class="flex justify-between items-start gap-4">
                    <span class="text-slate-500 font-medium shrink-0">Catatan Tambahan</span>
                    <span class="text-slate-800 font-semibold text-right">{{ $order->notes ?: 'Tidak ada' }}</span>
                </div>

                <!-- No. Pesanan with SALIN button -->
                <div class="flex justify-between items-center gap-2">
                    <span class="text-slate-500 font-medium shrink-0">No. Pesanan</span>
                    <div class="flex items-center gap-2 font-mono font-bold text-slate-800">
                        <span>{{ '33' . str_pad($order->id, 14, '9', STR_PAD_LEFT) }}</span>
                        <button type="button" 
                                @click="copyOrderNumber('{{ '33' . str_pad($order->id, 14, '9', STR_PAD_LEFT) }}')"
                                class="text-rose-600 hover:text-rose-700 font-extrabold text-[11px] tracking-wider transition">
                            SALIN
                        </button>
                    </div>
                </div>

                <!-- Waktu Pemesanan -->
                <div class="flex justify-between items-center gap-2">
                    <span class="text-slate-500 font-medium">Waktu Pemesanan</span>
                    <span class="text-slate-800 font-semibold">{{ $order->created_at->format('d M Y H:i') }}</span>
                </div>

                <!-- Waktu Pembayaran -->
                <div class="flex justify-between items-center gap-2">
                    <span class="text-slate-500 font-medium">Waktu Pembayaran</span>
                    <span class="text-slate-800 font-semibold">
                        {{ $latestPayment?->verified_at ? $latestPayment->verified_at->format('d M Y H:i') : ($latestPayment?->created_at && $isPaid ? $latestPayment->created_at->format('d M Y H:i') : '-') }}
                    </span>
                </div>

                <!-- Pembayaran -->
                <div class="flex justify-between items-center gap-2">
                    <span class="text-slate-500 font-medium">Pembayaran</span>
                    <span class="text-slate-800 font-bold">
                        @if($latestPayment?->channel)
                            {{ strtoupper($latestPayment->channel) }}
                        @elseif($latestPayment?->method)
                            {{ strtoupper($latestPayment->method) }}
                        @elseif($order->cash_amount > 0)
                            Tunai (COD)
                        @else
                            JastipPay / QRIS
                        @endif
                    </span>
                </div>

                <!-- Alamat Pengantaran -->
                <div class="border-t border-slate-100 pt-2.5 flex justify-between items-start gap-4">
                    <span class="text-slate-500 font-medium shrink-0">Alamat Antar</span>
                    <div class="text-right min-w-0">
                        <p class="text-slate-800 font-bold truncate">{{ $order->recipient_name ?: $customer->name }}</p>
                        <p class="text-[11px] text-slate-500 leading-tight mt-0.5 line-clamp-2">{{ $order->destination_address }}</p>
                    </div>
                </div>

                @if($isCancelled)
                <!-- Detail Pembatalan -->
                <div class="border-t border-slate-100 pt-2.5 flex justify-between items-start gap-4">
                    <span class="text-rose-600 font-bold shrink-0">Status Pesanan</span>
                    <span class="text-rose-700 font-extrabold text-right">Dibatalkan</span>
                </div>
                @if($order->cancellation_reason)
                <div class="flex justify-between items-start gap-4">
                    <span class="text-slate-500 font-medium shrink-0">Alasan Batal</span>
                    <span class="text-slate-800 font-semibold text-right">{{ $order->cancellation_reason }}</span>
                </div>
                @endif
                @endif
            </div>
        </div>

        <!-- Jastiper Information (If assigned) -->
        @if($order->jastiper)
        <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-full bg-slate-900 text-rose-500 font-black text-xs flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($order->jastiper->name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <h3 class="text-xs font-bold text-slate-800 truncate">{{ $order->jastiper->name }}</h3>
                        <span class="text-[9px] bg-rose-50 text-rose-600 font-extrabold px-1.5 py-0.2 rounded-full">Jastiper</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $order->jastiper->phone_number }}</p>
                </div>
            </div>
            <button type="button" 
                    @click="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: {{ $order->id }}, orderLabel: '{{ addslashes($order->description) }}' } }))"
                    class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow-2xs">
                Chat
            </button>
        </div>
        @endif

        <!-- Foto Bukti dari Jastiper (Proof of Pickup & Delivery) -->
        @if($order->pickup_photo || $order->delivery_photo)
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xs space-y-3">
            <h2 class="font-extrabold text-sm text-slate-900 tracking-tight flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Foto Bukti dari Jastiper</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if($order->pickup_photo)
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                        <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider block">1. Foto Barang di Toko</span>
                        <div class="relative group rounded-lg overflow-hidden border border-slate-200 bg-slate-900 h-36">
                            <img src="{{ Storage::url($order->pickup_photo) }}" alt="Foto Ambil di Toko" class="w-full h-full object-cover">
                            <a href="{{ Storage::url($order->pickup_photo) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Perbesar</span>
                            </a>
                        </div>
                        <span class="text-[9.5px] text-slate-400 font-mono block">Diambil: {{ $order->pickup_time ? $order->pickup_time->format('d M, H:i') . ' WIB' : 'Tercatat' }}</span>
                    </div>
                @endif
                @if($order->delivery_photo)
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                        <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider block">2. Foto Bukti Serah Terima</span>
                        <div class="relative group rounded-lg overflow-hidden border border-slate-200 bg-slate-900 h-36">
                            <img src="{{ Storage::url($order->delivery_photo) }}" alt="Foto Serah Terima" class="w-full h-full object-cover">
                            <a href="{{ Storage::url($order->delivery_photo) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Perbesar</span>
                            </a>
                        </div>
                        <span class="text-[9.5px] text-slate-400 font-mono block">Tiba: {{ $order->delivery_time ? $order->delivery_time->format('d M, H:i') . ' WIB' : 'Tercatat' }}</span>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Bottom Contextual Action Buttons -->
        <div class="pt-2">
            @if(in_array($order->status, ['menunggu_tawaran', 'ada_tawaran']))
                <!-- Batalkan Pesanan (Clean Shopee outline button style) -->
                <form action="{{ route('customer.orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')">
                    @csrf
                    <button type="submit" class="w-full bg-white hover:bg-slate-50 text-slate-400 hover:text-slate-600 font-bold text-xs py-3 rounded-xl border border-slate-200 transition tracking-wide shadow-2xs">
                        Batalkan Pesanan
                    </button>
                </form>
            @elseif($order->status === 'menunggu_pembayaran')
                <div class="space-y-2">
                    <a href="{{ route('customer.orders.payment.page', $order->id) }}" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 rounded-xl block text-center transition tracking-wide shadow-md shadow-rose-600/20">
                        Bayar Sekarang
                    </a>
                    <form action="{{ route('customer.orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')">
                        @csrf
                        <button type="submit" class="w-full bg-white text-slate-400 hover:text-slate-600 font-bold text-xs py-2.5 rounded-xl border border-slate-200 transition tracking-wide">
                            Batalkan Pesanan
                        </button>
                    </form>
                </div>
            @elseif($order->status === 'tiba_tujuan')
                <div class="space-y-2">
                    <form action="{{ route('customer.orders.confirmation', $order->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs py-3 rounded-xl transition shadow-md shadow-emerald-600/20">
                            ✓ Konfirmasi Diterima (Selesai)
                        </button>
                    </form>
                    <a href="{{ route('customer.orders.report', $order->id) }}" class="w-full bg-white hover:bg-amber-50 text-amber-700 font-bold text-xs py-2.5 rounded-xl border border-amber-300 transition text-center block shadow-2xs">
                        ⚠️ Ada Masalah / Komplain Barang
                    </a>
                </div>
            @elseif($order->status === 'bermasalah')
                <a href="{{ route('customer.orders.report', $order->id) }}" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs py-3 rounded-xl block text-center transition tracking-wide shadow-md shadow-amber-500/20">
                    ⚖️ Pantau Status Pengaduan Admin
                </a>
            @elseif($order->status === 'selesai')
                <a href="{{ route('customer.food.index') }}" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 rounded-xl block text-center transition tracking-wide shadow-md shadow-rose-600/20">
                    Pesan Kuliner Lagi
                </a>
            @endif
        </div>

    </div>

    <!-- Help Modal Dialog -->
    <div x-show="showHelp" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-3xl p-5 w-full max-w-sm shadow-xl space-y-4" @click.away="showHelp = false">
            <div class="flex items-center justify-between">
                <h3 class="font-extrabold text-slate-900 text-sm">Bantuan Pesanan</h3>
                <button type="button" @click="showHelp = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed">
                Butuh bantuan terkait pesanan ini? Tim Customer Support JastipKuy siap membantu Anda jika ada kendala barang tidak sesuai, rusak, atau kendala pembayaran.
            </p>
            <div class="space-y-2 pt-1">
                <a href="{{ route('customer.orders.report', $order->id) }}" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs py-2.5 rounded-xl block text-center transition shadow-2xs">
                    ⚠️ Pengaduan & Komplain Resmi
                </a>
                <a href="https://wa.me/6281234567890?text=Halo%20Admin%20JastipKuy,%20saya%20butuh%20bantuan%20pesanan%20%23{{ $order->id }}" target="_blank" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 rounded-xl block text-center transition">
                    Hubungi via WhatsApp
                </a>
                <button type="button" @click="showHelp = false" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-2.5 rounded-xl transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

@include('components.chat.order-chat-modal', [
    'viewerRole' => 'customer',
    'chatSendUrlTemplate' => route('customer.orders.chat.send', ['id' => '__ID__']),
    'chatHistoryUrlTemplate' => route('customer.orders.chat.history', ['id' => '__ID__']),
])
@endsection
