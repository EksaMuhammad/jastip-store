@extends('layouts.cs')

@section('title', 'Investigasi Komplain #REP-' . $report->id . ' - CS Desk')

@section('content')
<div class="flex-1 flex flex-col min-w-0" x-data="csDisputeResolution({ totalPaid: {{ $totalPaid }} })">

    <!-- Header Bar -->
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('cs.reports.index') }}" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1 class="font-extrabold text-sm text-slate-900">Investigasi Pengaduan #REP-{{ $report->id }}</h1>
                <span class="text-[10px] text-slate-500 block -mt-0.5">Pesanan #{{ $order->id }} • {{ $report->created_at->format('d M Y, H:i') }} WIB</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($report->status === 'menunggu')
                @if(!$report->assigned_cs_id || $report->cs_claim_status === 'unassigned')
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        Antrean Bebas
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span>
                        Ditangani: {{ $report->assignedCs?->name }}
                    </span>
                @endif
            @else
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Tiket Selesai
                </span>
            @endif
        </div>
    </header>

    <main class="flex-grow p-4 lg:p-6 space-y-4">

        @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
                <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2 shadow-2xs">
                <svg class="w-4 h-4 text-rose-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-2xs">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Banner Klaim jika tiket belum diklaim -->
        @if($report->status === 'menunggu' && (!$report->assigned_cs_id || $report->cs_claim_status === 'unassigned'))
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 shadow-xs flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 border border-amber-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-amber-900">Tiket ini masih berada di Antrean Bebas (Belum Ditangani)</h4>
                        <p class="text-[11px] text-amber-700 mt-0.5">Klaim tiket ini sekarang agar tercatat di bawah penanganan Anda.</p>
                    </div>
                </div>
                <form action="{{ route('cs.reports.claim', $report->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
                        Klaim Tiket Ini
                    </button>
                </form>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- LEFT 2 COLS: Bukti & Kronologi Komplain -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Card Detail Komplain -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Kategori Masalah</span>
                                <h3 class="font-bold text-base text-slate-900">
                                    {{ str_replace('_', ' ', strtoupper($report->category)) }}
                                </h3>
                            </div>
                        </div>
                        <span class="text-xs font-mono text-slate-400">
                            {{ $report->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <!-- Status Proteksi Asuransi Barang -->
                    <div class="p-3.5 rounded-xl border {{ $order->has_insurance ? 'bg-amber-50/80 border-amber-200' : 'bg-slate-50 border-slate-200' }} flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg {{ $order->has_insurance ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600' }} flex items-center justify-center font-bold text-xs shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs {{ $order->has_insurance ? 'text-amber-900' : 'text-slate-700' }}">
                                        {{ $order->has_insurance ? 'Pesanan Dilindungi Proteksi Asuransi' : 'Pesanan Tanpa Proteksi Asuransi' }}
                                    </span>
                                    @if($order->has_insurance)
                                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-200">
                                            Premi Rp {{ number_format($order->insurance_fee, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-slate-500 mt-0.5">
                                    @if($order->has_insurance)
                                        Customer membayar premi saat checkout. Platform siap menalangi ganti rugi 100%.
                                    @else
                                        Pesanan ini tidak memilih proteksi asuransi.
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($report->is_insurance_claim || $order->insurance_claimed)
                            <span class="px-2 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold shrink-0">
                                Klaim Ditalangi: Rp {{ number_format($report->insurance_payout ?: $order->insurance_claim_amount, 0, ',', '.') }}
                            </span>
                        @endif
                    </div>

                    <div>
                        <span class="text-xs font-bold text-slate-700 block mb-1">Kronologi / Penjelasan Customer:</span>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 text-xs leading-relaxed">
                            {{ $report->description }}
                        </div>
                    </div>

                    <!-- Foto Bukti Customer -->
                    <div>
                        <span class="text-xs font-bold text-slate-700 block mb-2">Foto Bukti Kerusakan / Masalah:</span>
                        @if($report->evidence_image)
                            <div class="relative group max-w-sm rounded-xl overflow-hidden border border-slate-200 bg-slate-100">
                                <img src="{{ asset('storage/' . $report->evidence_image) }}" alt="Bukti Komplain" class="w-full h-56 object-cover">
                                <a href="{{ asset('storage/' . $report->evidence_image) }}" target="_blank"
                                   class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold gap-2">
                                    <span>Lihat Ukuran Penuh ↗</span>
                                </a>
                            </div>
                        @else
                            <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-slate-400 text-xs text-center">
                                Customer tidak melampirkan foto bukti fisik.
                            </div>
                        @endif
                    </div>

                    <!-- Bukti Lapangan Jastiper -->
                    <div class="pt-4 border-t border-slate-100">
                        <span class="text-xs font-bold text-slate-700 block mb-2">Foto Lapangan Mitra Jastiper (Audit Trail):</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1.5">
                                    1. Bukti Pembelian / Struk
                                </span>
                                @if($order->pickup_photo)
                                    <div class="relative group rounded-lg overflow-hidden border border-slate-200 bg-slate-900 h-36">
                                        <img src="{{ Storage::url($order->pickup_photo) }}" alt="Bukti Beli" class="w-full h-full object-cover">
                                        <a href="{{ Storage::url($order->pickup_photo) }}" target="_blank" class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-[11px] font-bold">
                                            Buka Foto ↗
                                        </a>
                                    </div>
                                @else
                                    <p class="text-[11px] text-slate-400 italic py-4 text-center">Tidak ada foto struk belanja.</p>
                                @endif
                            </div>

                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1.5">
                                    2. Bukti Pengantaran / Tiba
                                </span>
                                @if($order->delivery_photo)
                                    <div class="relative group rounded-lg overflow-hidden border border-slate-200 bg-slate-900 h-36">
                                        <img src="{{ Storage::url($order->delivery_photo) }}" alt="Bukti Tiba" class="w-full h-full object-cover">
                                        <a href="{{ Storage::url($order->delivery_photo) }}" target="_blank" class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-[11px] font-bold">
                                            Buka Foto ↗
                                        </a>
                                    </div>
                                @else
                                    <p class="text-[11px] text-slate-400 italic py-4 text-center">Tidak ada foto pengantaran.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Detail Pesanan -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700">Rincian Finansial & Barang</h4>
                        <span class="text-xs font-mono font-bold text-slate-700">Order #{{ $order->id }}</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                            <span class="text-[10px] text-slate-500 block">Status:</span>
                            <strong class="uppercase text-slate-800">{{ $order->status }}</strong>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                            <span class="text-[10px] text-slate-500 block">Kesepakatan Awal:</span>
                            <strong class="text-slate-900 font-mono">Rp {{ number_format($order->agreed_fare, 0, ',', '.') }}</strong>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200">
                            <span class="text-[10px] text-slate-500 block">Penyesuaian Harga:</span>
                            <strong class="text-slate-900 font-mono">Rp {{ number_format($order->approvedPriceAdjustmentsTotal(), 0, ',', '.') }}</strong>
                        </div>
                        <div class="p-2.5 bg-slate-100 rounded-xl border border-slate-200">
                            <span class="text-[10px] text-slate-600 block font-bold">Total Pembayaran:</span>
                            <strong class="text-slate-900 font-mono text-sm">Rp {{ number_format($totalPaid, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="text-xs pt-1">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Catatan / Detail Barang:</span>
                        <p class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-slate-700 leading-relaxed">
                            {{ $order->description }}
                        </p>
                    </div>
                </div>

            </div>

            <!-- RIGHT COL: Info Pihak & Form Resolusi -->
            <div class="space-y-4">

                <!-- Pihak Terlibat -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-3">
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500">Pihak Terkait & Saldo</h4>

                    <!-- Customer Info -->
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-1 text-xs">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 block">Pelanggan (Pelapor)</span>
                        <div class="font-bold text-slate-900">{{ $customer->name ?? 'Customer' }}</div>
                        <div class="text-slate-500 text-[11px] font-mono">{{ $customer->phone_number ?? '-' }}</div>
                        <div class="pt-1 text-[11px] font-semibold text-slate-700 font-mono">
                            Saldo: <strong class="text-slate-900">Rp {{ number_format($customer->wallet->balance ?? 0, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <!-- Jastiper Info -->
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-1 text-xs">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 block">Mitra Jastiper</span>
                        <div class="font-bold text-slate-900">{{ $jastiper->name ?? 'Belum ada' }}</div>
                        <div class="text-slate-500 text-[11px] font-mono">{{ $jastiper->phone_number ?? '-' }}</div>
                        <div class="pt-1 text-[11px] font-semibold text-slate-700 font-mono">
                            Saldo: <strong class="{{ ($jastiper->wallet->balance ?? 0) < 0 ? 'text-rose-600' : 'text-slate-900' }}">Rp {{ number_format($jastiper->wallet->balance ?? 0, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Putusan Resolusi -->
                @if($report->status === 'selesai')
                    <!-- Card Jika Selesai -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block">Status Tiket</span>
                                <h4 class="font-bold text-sm text-slate-900">Pengaduan Telah Selesai</h4>
                            </div>
                        </div>

                        <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 text-xs space-y-2">
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
                                    <span class="font-bold text-emerald-700 font-mono">
                                        Rp {{ number_format($report->refund_amount, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-500">Diputuskan Oleh:</span>
                                <span class="font-bold text-slate-800">{{ $report->assignedCs?->name ?? 'Petugas CS' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-500">Waktu Putusan:</span>
                                <span class="font-bold text-slate-800 font-mono">{{ $report->decided_at?->format('d M Y, H:i') }} WIB</span>
                            </div>
                            <div class="pt-1">
                                <span class="text-slate-500 block mb-0.5">Alasan / Catatan:</span>
                                <p class="font-medium text-slate-800 bg-white p-2.5 rounded-xl border border-slate-200 leading-relaxed">
                                    {{ $report->admin_decision }}
                                </p>
                            </div>

                            @if($report->is_insurance_claim)
                                <div class="mt-2 p-3 bg-amber-50 rounded-xl border border-amber-200 space-y-1">
                                    <div class="flex items-center justify-between text-[11px] font-bold text-amber-900">
                                        <span>Skema Talangan Asuransi</span>
                                        <span class="px-2 py-0.5 bg-amber-200 text-amber-900 rounded text-[9px] uppercase tracking-wider font-extrabold">Ditalangi Platform</span>
                                    </div>
                                    <p class="text-[10px] text-amber-800 leading-relaxed">
                                        Dana pengembalian sebesar <strong>Rp {{ number_format($report->insurance_payout, 0, ',', '.') }}</strong> telah ditalangi 100% oleh platform langsung ke customer. Kewajiban ganti rugi sebesar <strong>Rp {{ number_format($report->jastiper_charged_amount, 0, ',', '.') }}</strong> dibebankan sebagai piutang minus ke dompet Jastiper.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- FORM EKSEKUSI PUTUSAN CS -->
                    <form action="{{ route('cs.reports.resolve', $report->id) }}" method="POST" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-3.5">
                        @csrf

                        <div>
                            <h4 class="font-extrabold text-sm text-slate-900">Eksekusi Putusan Resolusi</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Pilih tindakan resolusi atas sengketa ini.</p>
                        </div>

                        <!-- 3 Pilihan Tindakan -->
                        <div class="space-y-2">
                            <!-- Option 1: Refund 100% -->
                            <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition select-none"
                                   :class="action === 'refund_full' ? 'bg-slate-100 border-slate-900 text-slate-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700'">
                                <input type="radio" name="action" value="refund_full" x-model="action" class="mt-0.5 text-slate-900 focus:ring-slate-900">
                                <div class="text-xs">
                                    <span class="block">1. Refund Penuh 100% ke Customer</span>
                                    <span class="text-[10px] font-normal text-slate-500 block mt-0.5">
                                        Batalkan pesanan, kembalikan Rp {{ number_format($totalPaid, 0, ',', '.') }} ke dompet customer.
                                    </span>
                                </div>
                            </label>

                            <!-- Option 2: Refund Sebagian -->
                            <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition select-none"
                                   :class="action === 'refund_partial' ? 'bg-slate-100 border-slate-900 text-slate-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700'">
                                <input type="radio" name="action" value="refund_partial" x-model="action" class="mt-0.5 text-slate-900 focus:ring-slate-900">
                                <div class="text-xs">
                                    <span class="block">2. Refund Sebagian (Parsial)</span>
                                    <span class="text-[10px] font-normal text-slate-500 block mt-0.5">
                                        Kompromi nominal, sisa dana diteruskan ke Jastiper dan pesanan selesai.
                                    </span>
                                </div>
                            </label>

                            <!-- Option 3: Tolak Komplain -->
                            <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition select-none"
                                   :class="action === 'reject_complaint' ? 'bg-slate-100 border-slate-900 text-slate-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700'">
                                <input type="radio" name="action" value="reject_complaint" x-model="action" class="mt-0.5 text-slate-900 focus:ring-slate-900">
                                <div class="text-xs">
                                    <span class="block">3. Tolak Komplain (Barang Sesuai)</span>
                                    <span class="text-[10px] font-normal text-slate-500 block mt-0.5">
                                        Barang valid, teruskan seluruh pembayaran ke Jastiper dan pesanan selesai.
                                    </span>
                                </div>
                            </label>
                        </div>

                        <!-- Nominal Refund Sebagian Input -->
                        <div x-show="action === 'refund_partial'" class="p-3 rounded-xl bg-amber-50 border border-amber-200 space-y-1" x-cloak>
                            <label class="block text-[11px] font-bold text-amber-900 uppercase tracking-wider">Nominal Refund Customer (Rp)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" inputmode="numeric" name="refund_amount" 
                                       :value="formatThousand(refundAmount)"
                                       @input="refundAmount = $event.target.value.replace(/\D/g, ''); $event.target.value = formatThousand(refundAmount)"
                                       placeholder="Contoh: 25.000"
                                       class="w-full text-xs font-mono font-bold bg-white border border-amber-300 rounded-lg pl-9 pr-3 py-2 text-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                            </div>
                            <span x-show="Number(refundAmount) > 0" class="text-[10px] font-bold text-amber-800 block mt-1" x-text="'Nominal Terpilih: Rp ' + formatThousand(refundAmount)"></span>
                            <span class="text-[10px] text-amber-700 block">Maksimal: Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                        </div>

                        <!-- Catatan Putusan Resmi -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                Catatan Putusan Resmi *
                            </label>
                            <textarea name="admin_decision" rows="3" required
                                      placeholder="Tuliskan alasan keputusan..."
                                      class="w-full text-xs rounded-xl p-2.5 bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"></textarea>
                        </div>

                        <!-- Catatan Investigasi Internal -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                Catatan Internal (Opsional)
                            </label>
                            <textarea name="investigation_notes" rows="2"
                                      placeholder="Catatan internal tim CS..."
                                      class="w-full text-xs rounded-xl p-2.5 bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"></textarea>
                        </div>

                        <!-- Opsi Talangan Dana Asuransi Platform -->
                        <div class="p-3 rounded-xl border {{ $order->has_insurance ? 'bg-amber-50 border-amber-200' : 'bg-slate-50 border-slate-200' }}" x-show="action !== 'reject_complaint'">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" name="use_insurance" value="1" {{ $order->has_insurance ? 'checked' : '' }}
                                       class="mt-0.5 w-4 h-4 text-slate-900 rounded border-slate-300 focus:ring-slate-900">
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">
                                        Klaim Talangan Asuransi Platform (Talangi Customer Dulu)
                                    </span>
                                    <span class="text-[10px] text-slate-600 block leading-snug mt-1">
                                        • <strong>Dicentang:</strong> Platform langsung menalangi dana ganti rugi dari Kas Asuransi ke dompet customer hari ini juga, dan kewajiban ganti rugi dibebankan ke saldo jastiper.<br>
                                        • <strong>Tidak Dicentang:</strong> Refund reguler (dana pesanan yang masih tertahan di sistem langsung dikembalikan ke customer tanpa menyentuh buku kas pool asuransi).
                                    </span>
                                </div>
                            </label>
                        </div>

                        <button type="submit" 
                                onclick="return confirm('Apakah Anda yakin ingin mengeksekusi putusan resolusi ini? Keputusan dan penyesuaian saldo bersifat permanen.')"
                                class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
                            Eksekusi & Simpan Putusan
                        </button>
                    </form>
                @endif

            </div>

        </div>

    </main>
</div>
@endsection

@section('scripts')
<script>
function csDisputeResolution(config) {
    return {
        totalPaid: config.totalPaid || 0,
        action: 'refund_full',
        refundAmount: '',
        formatThousand(val) {
            if (!val && val !== 0) return '';
            let num = val.toString().replace(/\D/g, '');
            return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
    };
}

@if($report->status === 'menunggu')
// Auto-refresh tiket komplain jika sudah selesai diputuskan
let csReportPoll = setInterval(() => {
    fetch("{{ route('cs.reports.status', $report->id) }}")
        .then(res => res.json())
        .then(data => {
            if (data.is_resolved) {
                clearInterval(csReportPoll);
                window.location.reload();
            }
        })
        .catch(err => console.error(err));
}, 5000);
@endif
</script>
@endsection
