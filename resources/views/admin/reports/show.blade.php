@extends('layouts.support')

@section('title', 'Investigasi Pengaduan #REP-' . $report->id . ' - Admin JastipKuy')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F3F4F6]" x-data="disputeResolution({ totalPaid: {{ $totalPaid }} })">

    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-40">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.reports.index') }}" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div>
                    <h1 class="font-extrabold text-sm text-slate-900">Investigasi Pengaduan #REP-{{ $report->id }}</h1>
                    <span class="text-[10px] text-slate-400 block -mt-0.5">Pesanan #{{ $order->id }} • {{ $report->created_at->format('d M Y, H:i') }} WIB</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($report->status === 'menunggu')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Menunggu Putusan Admin
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Telah Diputuskan
                    </span>
                @endif
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-grow p-6 lg:p-8 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- LEFT 2 COLUMNS: Case Details, Order Details, Chat Log -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Complaint Report Card -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
                        <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold">
                                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Kategori Keluhan</span>
                                    <h3 class="font-bold text-base text-slate-900">
                                        {{ str_replace('_', ' ', strtoupper($report->category)) }}
                                    </h3>
                                </div>
                            </div>
                            <span class="text-xs font-mono text-slate-400">
                                {{ $report->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <div>
                            <span class="text-xs font-bold text-slate-500 block mb-1">Kronologi / Penjelasan Customer:</span>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-slate-800 text-xs leading-relaxed font-normal">
                                {{ $report->description }}
                            </div>
                        </div>

                        <!-- Evidence Photo Customer -->
                        <div>
                            <span class="text-xs font-bold text-slate-500 block mb-2">Foto Bukti Fisik dari Customer:</span>
                            @if($report->evidence_image)
                                <div class="relative group max-w-sm rounded-xl overflow-hidden border border-slate-200 bg-slate-100">
                                    <img src="{{ asset('storage/' . $report->evidence_image) }}" alt="Bukti Komplain" class="w-full h-56 object-cover group-hover:scale-102 transition duration-300">
                                    <a href="{{ asset('storage/' . $report->evidence_image) }}" target="_blank"
                                       class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        <span>Lihat Ukuran Penuh</span>
                                    </a>
                                </div>
                            @else
                                <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-slate-400 text-xs text-center">
                                    Customer tidak melampirkan foto bukti pada pengaduan ini.
                                </div>
                            @endif
                        </div>

                        <!-- Evidence Photo Jastiper (Pickup & Delivery Proof) -->
                        <div class="pt-3 border-t border-slate-100">
                            <span class="text-xs font-bold text-slate-700 block mb-2 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Foto Bukti Lapangan Mitra Jastiper (Audit Integritas):</span>
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Bukti Ambil Toko -->
                                <div class="p-3 rounded-xl border {{ $order->pickup_photo ? 'bg-indigo-50/40 border-indigo-200' : 'bg-slate-50 border-slate-200/70 border-dashed' }}">
                                    <span class="text-[10px] font-bold uppercase tracking-wider {{ $order->pickup_photo ? 'text-indigo-800' : 'text-slate-400' }} block mb-1">
                                        1. Bukti Ambil di Toko / Struk
                                    </span>
                                    @if($order->pickup_photo)
                                        <div class="relative group rounded-lg overflow-hidden border border-indigo-200 bg-slate-900 h-36">
                                            <img src="{{ Storage::url($order->pickup_photo) }}" alt="Bukti Ambil Toko" class="w-full h-full object-cover">
                                            <a href="{{ Storage::url($order->pickup_photo) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-[11px] font-bold gap-1.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                <span>Buka Foto</span>
                                            </a>
                                        </div>
                                        <span class="text-[10px] text-slate-500 font-mono block mt-1">Waktu: {{ $order->pickup_time ? $order->pickup_time->format('d M Y, H:i') . ' WIB' : 'Tercatat' }}</span>
                                    @else
                                        <p class="text-[11px] text-slate-400 italic py-4 text-center">Tidak ada foto ambil barang diunggah.</p>
                                    @endif
                                </div>

                                <!-- Bukti Serah Terima / Tiba -->
                                <div class="p-3 rounded-xl border {{ $order->delivery_photo ? 'bg-emerald-50/40 border-emerald-200' : 'bg-slate-50 border-slate-200/70 border-dashed' }}">
                                    <span class="text-[10px] font-bold uppercase tracking-wider {{ $order->delivery_photo ? 'text-emerald-800' : 'text-slate-400' }} block mb-1">
                                        2. Bukti Serah Terima / Tiba
                                    </span>
                                    @if($order->delivery_photo)
                                        <div class="relative group rounded-lg overflow-hidden border border-emerald-200 bg-slate-900 h-36">
                                            <img src="{{ Storage::url($order->delivery_photo) }}" alt="Bukti Tiba" class="w-full h-full object-cover">
                                            <a href="{{ Storage::url($order->delivery_photo) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-[11px] font-bold gap-1.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                <span>Buka Foto</span>
                                            </a>
                                        </div>
                                        <span class="text-[10px] text-slate-500 font-mono block mt-1">Waktu: {{ $order->delivery_time ? $order->delivery_time->format('d M Y, H:i') . ' WIB' : 'Tercatat' }}</span>
                                    @else
                                        <p class="text-[11px] text-slate-400 italic py-4 text-center">Tidak ada foto serah terima diunggah.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details Card -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                <span>Rincian Pesanan Terkait (#{{ $order->id }})</span>
                            </h3>
                            <div class="flex items-center gap-2">
                                @if($order->has_insurance)
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1">
                                        🛡️ Proteksi Asuransi (Rp {{ number_format($order->insurance_fee, 0, ',', '.') }})
                                    </span>
                                @endif
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase {{ $order->status === 'bermasalah' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-800' }}">
                                    Status: {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Banner Status Proteksi Asuransi -->
                        <div class="p-3 rounded-xl border {{ $order->has_insurance ? 'bg-amber-50/70 border-amber-200' : 'bg-slate-50 border-slate-200' }} flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ $order->has_insurance ? '🛡️' : '⚪' }}</span>
                                <div class="text-[11px]">
                                    <span class="font-bold {{ $order->has_insurance ? 'text-amber-900' : 'text-slate-700' }}">
                                        {{ $order->has_insurance ? 'Pesanan Memiliki Proteksi Asuransi Barang' : 'Pesanan Tanpa Premi Asuransi' }}
                                    </span>
                                    <span class="text-slate-500 block text-[10px]">
                                        {{ $order->has_insurance ? 'Customer berhak atas talangan ganti rugi kas asuransi jika insiden akibat jastiper.' : 'Tidak ada premi masuk untuk pesanan ini.' }}
                                    </span>
                                </div>
                            </div>
                            @if($report->is_insurance_claim || $order->insurance_claimed)
                                <span class="px-2 py-1 rounded-md bg-rose-100 text-rose-800 text-[10px] font-extrabold border border-rose-200">
                                    Talangan Dicairkan: Rp {{ number_format($report->insurance_payout ?: $order->insurance_claim_amount, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px]">Deskripsi Utama:</span>
                                <span class="font-bold text-slate-800">{{ $order->description }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px]">Total Dana Ditahan:</span>
                                <span class="font-black text-rose-600 text-sm">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px]">Alamat Asal / Toko:</span>
                                <span class="text-slate-700">{{ $order->origin_address ?: '-' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-semibold block text-[10px]">Alamat Pengantaran:</span>
                                <span class="text-slate-700">{{ $order->destination_address ?: '-' }}</span>
                            </div>
                        </div>

                        <!-- Item Breakdown Table -->
                        @if($order->items && $order->items->count() > 0)
                            <div class="border border-slate-100 rounded-2xl overflow-hidden mt-3">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500">
                                        <tr>
                                            <th class="py-2.5 px-3">Item Belanja</th>
                                            <th class="py-2.5 px-3 text-center">Qty</th>
                                            <th class="py-2.5 px-3 text-right">Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($order->items as $item)
                                            <tr>
                                                <td class="py-2.5 px-3 font-medium text-slate-800">
                                                    {{ $item->product ? $item->product->name : ($item->name ?? $order->description) }}
                                                </td>
                                                <td class="py-2.5 px-3 text-center text-slate-600">
                                                    {{ $item->quantity ?: 1 }}
                                                </td>
                                                <td class="py-2.5 px-3 text-right font-bold text-slate-800">
                                                    Rp {{ number_format($item->actual_price ?: $item->estimated_price ?: 0, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Chat Transcript Between Customer & Jastiper -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                <span>Transkrip Percakapan Chat Order</span>
                            </h3>
                            <span class="text-[10px] text-slate-400 font-semibold font-mono">
                                {{ $order->chats->count() }} pesan
                            </span>
                        </div>

                        <div class="max-h-72 overflow-y-auto space-y-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-xs">
                            @forelse($order->chats as $chat)
                                <div class="p-2.5 rounded-lg {{ $chat->sender_role === 'system' ? 'bg-amber-50 text-amber-900 border border-amber-200' : ($chat->sender_role === 'customer' ? 'bg-slate-100 text-slate-900 border border-slate-200' : 'bg-white text-slate-800 border border-slate-200') }}">
                                    <div class="flex items-center justify-between text-[10px] font-bold mb-1 opacity-75">
                                        <span>
                                            @if($chat->sender_role === 'system')
                                                Sistem
                                            @elseif($chat->sender_role === 'customer')
                                                Customer ({{ $customer->name ?? '' }})
                                            @else
                                                Jastiper ({{ $jastiper->name ?? '' }})
                                            @endif
                                        </span>
                                        <span class="font-mono">{{ $chat->created_at->format('H:i, d M') }}</span>
                                    </div>
                                    <p class="leading-relaxed">{{ $chat->message }}</p>
                                </div>
                            @empty
                                <p class="text-center text-slate-400 py-4 font-medium text-xs">
                                    Tidak ada catatan riwayat percakapan chat.
                                </p>
                            @endforelse
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: Parties Summary & Resolution Decision Box -->
                <div class="space-y-6">

                    <!-- Customer & Jastiper Wallets Box -->
                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-4">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500">Pihak Terlibat & Dompet</h4>

                        <!-- Customer Info -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1 text-xs">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 block">Customer (Pelapor)</span>
                            <div class="font-bold text-slate-900">{{ $customer->name ?? 'Customer' }}</div>
                            <div class="text-slate-500 text-[11px]">{{ $customer->phone_number ?? '-' }}</div>
                            <div class="pt-1 text-[11px] font-semibold text-slate-700 font-mono">
                                Saldo Dompet: <strong class="text-slate-900">Rp {{ number_format($customer->wallet->balance ?? 0, 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <!-- Jastiper Info -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1 text-xs">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 block">Mitra Jastiper</span>
                            <div class="font-bold text-slate-900">{{ $jastiper->name ?? 'Belum ada jastiper' }}</div>
                            <div class="text-slate-500 text-[11px]">{{ $jastiper->phone_number ?? '-' }}</div>
                            <div class="pt-1 text-[11px] font-semibold text-slate-700 font-mono">
                                Saldo Dompet: <strong class="text-slate-900">Rp {{ number_format($jastiper->wallet->balance ?? 0, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Resolution Decision Box -->
                    @if($report->status === 'selesai')
                        <!-- Resolved Record Box -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-lg">
                                    <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">Status Resolusi</span>
                                    <h4 class="font-bold text-sm text-slate-900">Pengaduan Selesai</h4>
                                </div>
                            </div>

                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 text-xs space-y-2">
                                <div class="flex justify-between py-1 border-b border-slate-200">
                                    <span class="text-slate-500">Putusan:</span>
                                    <span class="font-bold text-slate-900 uppercase">
                                        @if($report->resolution_type === 'refund_full')
                                            Refund Penuh (100%)
                                        @elseif($report->resolution_type === 'refund_partial')
                                            Refund Sebagian
                                        @else
                                            Komplain Ditolak
                                        @endif
                                    </span>
                                </div>
                                @if($report->refund_amount > 0)
                                    <div class="flex justify-between py-1 border-b border-slate-200">
                                        <span class="text-slate-500">Nominal Refund:</span>
                                        <span class="font-bold text-slate-900 font-mono">
                                            Rp {{ number_format($report->refund_amount, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endif
                                <div class="flex justify-between py-1 border-b border-slate-100">
                                    <span class="text-slate-500">Diputuskan Oleh:</span>
                                    <span class="font-bold text-slate-800">{{ $report->decider->name ?? 'Admin JastipKuy' }}</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-100">
                                    <span class="text-slate-500">Tanggal Putusan:</span>
                                    <span class="font-bold text-slate-800">{{ $report->decided_at?->format('d M Y, H:i') }} WIB</span>
                                </div>
                                <div class="pt-1">
                                    <span class="text-slate-500 block mb-0.5">Alasan Resmi:</span>
                                    <p class="font-semibold text-slate-800 bg-slate-50 p-2.5 rounded-xl border border-slate-100 leading-relaxed">
                                        {{ $report->admin_decision }}
                                    </p>
                                </div>
                                @if($report->investigation_notes)
                                    <div class="pt-1">
                                        <span class="text-slate-500 block mb-0.5">Catatan Investigasi Internal:</span>
                                        <p class="text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-100 leading-relaxed">
                                            {{ $report->investigation_notes }}
                                        </p>
                                    </div>
                                @endif

                                @if($report->is_insurance_claim)
                                    <div class="mt-2 p-3 bg-amber-50/90 rounded-xl border border-amber-200 space-y-1">
                                        <div class="flex items-center justify-between text-[11px] font-bold text-amber-900">
                                            <span class="flex items-center gap-1">🛡️ Skema Talangan Dana Asuransi</span>
                                            <span class="px-2 py-0.5 bg-amber-200 text-amber-900 rounded-md text-[9px] uppercase tracking-wider font-extrabold">Ditalangi Platform</span>
                                        </div>
                                        <p class="text-[10px] text-amber-800 leading-relaxed">
                                            Dana pengembalian sebesar <strong>Rp {{ number_format($report->insurance_payout, 0, ',', '.') }}</strong> ditalangi 100% oleh platform ke customer. Beban ganti rugi sebesar <strong>Rp {{ number_format($report->jastiper_charged_amount, 0, ',', '.') }}</strong> dibebankan sebagai piutang minus ke saldo jastiper.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <!-- Form Eksekusi Keputusan Admin -->
                        <form action="{{ route('admin.reports.resolve', $report->id) }}" method="POST" class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-4">
                            @csrf

                            <div>
                                <h4 class="font-black text-sm text-slate-900">Eksekusi Putusan Admin</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Pilih opsi penyelesaian sengketa untuk pengaduan ini.</p>
                            </div>

                            <!-- Opsi Resolusi (Radio) -->
                            <div class="space-y-2">
                                <label class="flex items-start gap-2.5 p-3 rounded-2xl border transition cursor-pointer"
                                    :class="action === 'refund_full' ? 'border-emerald-500 bg-emerald-50/50 ring-1 ring-emerald-500' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" name="action" value="refund_full" x-model="action" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                    <div>
                                        <span class="text-xs font-black text-emerald-950 block">Refund Penuh 100%</span>
                                        <span class="text-[10px] text-slate-500 block mt-0.5 leading-tight">
                                            Kembalikan Rp {{ number_format($totalPaid, 0, ',', '.') }} ke dompet Customer. Pesanan ditutup.
                                        </span>
                                    </div>
                                </label>

                                <label class="flex items-start gap-2.5 p-3 rounded-2xl border transition cursor-pointer"
                                    :class="action === 'refund_partial' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" name="action" value="refund_partial" x-model="action" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                                    <div class="flex-1">
                                        <span class="text-xs font-black text-amber-950 block">Refund Sebagian</span>
                                        <span class="text-[10px] text-slate-500 block mt-0.5 leading-tight">
                                            Kembalikan sebagian dana ke Customer, sisanya diteruskan ke Jastiper.
                                        </span>
                                    </div>
                                </label>

                                <!-- Input Partial Refund Amount -->
                                <div x-show="action === 'refund_partial'" x-cloak class="pl-7 pr-1 pt-1 space-y-1">
                                    <label class="block text-[10px] font-bold text-slate-700">Nominal Refund ke Customer (Rp):</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                        <input type="text" inputmode="numeric" name="refund_amount" 
                                               :value="formatThousand(partialAmount)"
                                               @input="partialAmount = $event.target.value.replace(/\D/g, ''); $event.target.value = formatThousand(partialAmount)"
                                               class="w-full text-xs rounded-xl pl-9 pr-3 py-2 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 font-mono font-bold"
                                               placeholder="Contoh: 25.000">
                                    </div>
                                    <span x-show="Number(partialAmount) > 0" class="text-[10px] font-bold text-amber-700 block mt-1" x-text="'Nominal Terpilih: Rp ' + formatThousand(partialAmount)"></span>
                                    <span class="text-[10px] text-slate-400 block">Maksimal: Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                                </div>

                                <label class="flex items-start gap-2.5 p-3 rounded-2xl border transition cursor-pointer"
                                    :class="action === 'reject_complaint' ? 'border-rose-500 bg-rose-50/50 ring-1 ring-rose-500' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" name="action" value="reject_complaint" x-model="action" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                                    <div>
                                        <span class="text-xs font-black text-rose-950 block">Tolak Komplain Customer</span>
                                        <span class="text-[10px] text-slate-500 block mt-0.5 leading-tight">
                                            Barang dinyatakan sesuai. Selesaikan pesanan & transfer hak bayar ke dompet Jastiper.
                                        </span>
                                    </div>
                                </label>
                            </div>

                            <!-- Alasan Resmi -->
                            <div>
                                <label for="admin_decision" class="block text-xs font-black text-slate-800 mb-1">
                                    Alasan Resmi Putusan <span class="text-rose-500">*</span>
                                </label>
                                <textarea id="admin_decision" name="admin_decision" rows="3" required
                                          placeholder="Tuliskan pertimbangan atau alasan keputusan yang akan disampaikan ke kedua pihak..."
                                          class="w-full text-xs rounded-2xl border border-slate-200 p-3 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 leading-relaxed"></textarea>
                            </div>

                            <!-- Catatan Internal -->
                            <div>
                                <label for="investigation_notes" class="block text-xs font-black text-slate-800 mb-1">
                                    Catatan Investigasi Internal (Opsional)
                                </label>
                                <textarea id="investigation_notes" name="investigation_notes" rows="2"
                                          placeholder="Catatan rahasia khusus internal tim admin..."
                                          class="w-full text-xs rounded-2xl border border-slate-200 p-3 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 leading-relaxed"></textarea>
                            </div>

                            <!-- Opsi Talangan Dana Asuransi Platform -->
                            <div class="p-3.5 rounded-2xl border border-dashed {{ $order->has_insurance ? 'bg-amber-50/70 border-amber-300' : 'bg-slate-50 border-slate-300' }} space-y-1.5" x-show="action !== 'reject_complaint'">
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="use_insurance" value="1" {{ $order->has_insurance ? 'checked' : '' }}
                                           class="mt-0.5 w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                                    <div>
                                        <span class="text-xs font-black text-slate-900 block">
                                            🛡️ Eksekusi sebagai Klaim Dana Asuransi Platform (Talangi Customer Dulu)
                                        </span>
                                        <span class="text-[10px] text-slate-600 block leading-snug mt-1">
                                            • <strong>Dicentang:</strong> Platform langsung menalangi ganti rugi 100% dari Kas Asuransi ke customer hari ini juga, dan beban ganti rugi dicatat ke saldo jastiper (saldo diperbolehkan minus jika tidak mencukupi).<br>
                                            • <strong>Tidak Dicentang:</strong> Refund reguler sengketa (mengembalikan dana pesanan yang tertahan di sistem ke customer tanpa klaim kas pool asuransi).
                                        </span>
                                    </div>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit"
                                    onclick="return confirm('Apakah Anda yakin ingin mengeksekusi keputusan ini? Saldo dan status pesanan akan diproses secara permanen.')"
                                    class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs py-3 rounded-xl transition shadow-xs flex items-center justify-center gap-2 cursor-pointer">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                                <span>Eksekusi Putusan Resolusi</span>
                            </button>
                        </form>
                    @endif

                </div>

            </div>

        </main>
    </div>
</div>

<script>
    function disputeResolution(config) {
        return {
            totalPaid: config.totalPaid || 0,
            action: 'refund_full',
            partialAmount: String(Math.round(config.totalPaid / 2)),
            formatThousand(val) {
                if (!val && val !== 0) return '';
                let num = val.toString().replace(/\D/g, '');
                return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }
        };
    }

    @if($report->status === 'menunggu')
    // Auto-refresh tiket komplain jika sudah selesai diputuskan
    let adminReportPoll = setInterval(() => {
        fetch("{{ route('admin.reports.status', $report->id) }}")
            .then(res => res.json())
            .then(data => {
                if (data.is_resolved) {
                    clearInterval(adminReportPoll);
                    window.location.reload();
                }
            })
            .catch(err => console.error(err));
    }, 5000);
    @endif
</script>
@endsection
