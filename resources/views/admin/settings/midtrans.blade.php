@extends('layouts.support')

@section('title', 'Panel Admin - Pengaturan Midtrans')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F3F4F6]">
    
    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT MAIN CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- Top App Bar -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-30 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold text-slate-400">Portal Admin</span>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-bold text-slate-700">Pengaturan</span>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-extrabold text-rose-600">Payment Gateway Midtrans</span>
            </div>
            <div>
                <a href="{{ route('admin.payments') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-rose-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Kembali ke Verifikasi</span>
                </a>
            </div>
        </header>

        <!-- Main Body -->
        <div class="p-6 sm:p-8 max-w-6xl w-full mx-auto space-y-6">

            <!-- Title & Alerts -->
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Pengaturan Midtrans Payment Gateway</h2>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Kelola kredensial Midtrans Server Key, Client Key, dan environment langsung dari portal tanpa perlu sentuh kode program.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($isActive)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                GATEWAY AKTIF
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-300">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                GATEWAY NONAKTIF
                            </span>
                        @endif

                        @if($isProduction)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                PRODUCTION (LIVE)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                SANDBOX (TESTING)
                            </span>
                        @endif
                    </div>
                </div>

                @if(session('success'))
                    <div class="mt-4 bg-emerald-50 border border-emerald-200 p-4 rounded-2xl flex items-center gap-3 text-emerald-800 text-xs font-bold shadow-xs">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
            </div>

            <!-- Form & Live Connection Test Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Form Kolom Kiri (2 Kolom) -->
                <div class="lg:col-span-2 space-y-6">
                    <form action="{{ route('admin.settings.midtrans.update') }}" method="POST" class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
                        @csrf

                        <!-- Section: Status & Mode -->
                        <div class="border-b border-slate-100 pb-6 space-y-4">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Status & Lingkungan Midtrans
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Toggle Aktifkan Gateway -->
                                <label class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition {{ $isActive ? 'bg-emerald-50/50 border-emerald-200' : '' }}">
                                    <input type="checkbox" name="is_active" value="1" {{ $isActive ? 'checked' : '' }} class="w-4 h-4 text-rose-600 rounded focus:ring-rose-500">
                                    <div>
                                        <div class="font-bold text-xs text-slate-800">Aktifkan Payment Gateway</div>
                                        <div class="text-[11px] text-slate-500">Izinkan Snap Midtrans untuk transaksi Top Up & Checkout.</div>
                                    </div>
                                </label>

                                <!-- Pilihan Sandbox vs Production -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-slate-700">Mode Lingkungan</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer text-xs font-bold hover:bg-slate-50 transition {{ !$isProduction ? 'bg-amber-50/80 border-amber-300 text-amber-800' : 'text-slate-600' }}">
                                            <input type="radio" name="is_production" value="0" {{ !$isProduction ? 'checked' : '' }} class="text-amber-600">
                                            <span>Sandbox (Test)</span>
                                        </label>
                                        <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer text-xs font-bold hover:bg-slate-50 transition {{ $isProduction ? 'bg-purple-50/80 border-purple-300 text-purple-800' : 'text-slate-600' }}">
                                            <input type="radio" name="is_production" value="1" {{ $isProduction ? 'checked' : '' }} class="text-purple-600">
                                            <span>Production (Live)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Kredensial Kunci API -->
                        <div class="border-b border-slate-100 pb-6 space-y-4">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Kredensial API Midtrans
                            </h3>

                            <!-- Merchant ID -->
                            <div class="space-y-1.5">
                                <label for="merchant_id" class="block text-xs font-bold text-slate-700">
                                    Merchant ID <span class="text-slate-400 font-normal">(Opsional)</span>
                                </label>
                                <input
                                    type="text"
                                    id="merchant_id"
                                    name="merchant_id"
                                    value="{{ old('merchant_id', $merchantId) }}"
                                    placeholder="Contoh: G123456789"
                                    class="w-full bg-[#F9FAFB] border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-mono text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition"
                                >
                                <p class="text-[10px] text-slate-400">Merchant ID dari dashboard Midtrans akun Anda.</p>
                            </div>

                            <!-- Client Key -->
                            <div class="space-y-1.5">
                                <label for="client_key" class="block text-xs font-bold text-slate-700">
                                    Client Key <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="client_key"
                                    name="client_key"
                                    value="{{ old('client_key', $clientKey) }}"
                                    placeholder="SB-Mid-client-xxxxxxxxxxxxxxxx"
                                    class="w-full bg-[#F9FAFB] border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-mono text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition"
                                >
                                <p class="text-[10px] text-slate-400">Digunakan oleh frontend Snap JS browser customer saat memunculkan popup pembayaran.</p>
                            </div>

                            <!-- Server Key -->
                            <div class="space-y-1.5">
                                <label for="server_key" class="block text-xs font-bold text-slate-700">
                                    Server Key <span class="text-rose-500">* (Rahasia)</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="password"
                                        id="server_key"
                                        name="server_key"
                                        value="{{ old('server_key', $serverKey) }}"
                                        placeholder="SB-Mid-server-xxxxxxxxxxxxxxxx"
                                        class="w-full bg-[#F9FAFB] border border-slate-200 rounded-xl px-4 py-2.5 pr-20 text-xs font-mono text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition"
                                    >
                                    <button
                                        type="button"
                                        onclick="toggleKeyVisibility()"
                                        id="toggle-vis-btn"
                                        class="absolute right-2 top-2 px-2.5 py-1 text-[10px] font-bold text-slate-500 hover:text-slate-800 bg-slate-200/60 rounded-lg transition"
                                    >
                                        Lihat
                                    </button>
                                </div>
                                <p class="text-[10px] text-slate-400">Kunci rahasia API backend untuk otentikasi transaksi dan kalkulasi verifikasi signature webhook.</p>
                            </div>
                        </div>

                        <!-- Section: Pilihan Kanal Pembayaran yang Diizinkan -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Saluran Pembayaran Snap yang Diizinkan
                            </h3>
                            <p class="text-[11px] text-slate-500">Pilih opsi pembayaran yang ingin ditampilkan dalam popup Midtrans Snap:</p>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1">
                                @php
                                    $paymentOptions = [
                                        'qris' => 'QRIS (Semua E-Wallet)',
                                        'gopay' => 'GoPay',
                                        'shopeepay' => 'ShopeePay',
                                        'bca_va' => 'BCA Virtual Account',
                                        'bni_va' => 'BNI Virtual Account',
                                        'bri_va' => 'BRI Virtual Account',
                                        'echannel' => 'Mandiri Bill',
                                        'permata_va' => 'Permata VA',
                                        'cimb_va' => 'CIMB Niaga VA',
                                    ];
                                @endphp
                                @foreach($paymentOptions as $key => $label)
                                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="enabled_payments[]"
                                            value="{{ $key }}"
                                            {{ in_array($key, $enabledPayments) ? 'checked' : '' }}
                                            class="w-3.5 h-3.5 text-rose-600 rounded"
                                        >
                                        <span class="text-slate-700 font-semibold text-[11px]">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 flex flex-col sm:flex-row items-center gap-3 justify-between">
                            <button
                                type="button"
                                onclick="runTestConnection()"
                                id="btn-test-conn"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs uppercase tracking-wider transition shadow-xs flex items-center justify-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span>Uji Koneksi Server Key</span>
                            </button>

                            <button
                                type="submit"
                                class="w-full sm:w-auto px-8 py-2.5 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs uppercase tracking-wider transition shadow-xs"
                            >
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>

                    <!-- Feedback Box Uji Koneksi Live -->
                    <div id="test-result-box" class="hidden rounded-2xl p-4 text-xs font-semibold space-y-1 animate-fade-in"></div>
                </div>

                <!-- Kolom Kanan: Panduan Konfigurasi Webhook & Dashboard Midtrans -->
                <div class="space-y-6">
                    
                    <!-- Box 1: Webhook & URL Redirect -->
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            </div>
                            <h4 class="font-extrabold text-xs text-slate-800 uppercase tracking-wide">URL Callback Midtrans</h4>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Salin URL di bawah ini ke menu <b>Settings > Configuration</b> di Dashboard Midtrans Anda agar sistem menerima webhook notifikasi otomatis (ACC 2 otomatis):
                        </p>

                        <!-- Notification Webhook URL -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-600 uppercase">Payment Notification URL</label>
                            <div class="flex items-center gap-1.5">
                                <input
                                    type="text"
                                    readonly
                                    id="webhook-url-input"
                                    value="{{ $webhookUrl }}"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-[11px] font-mono text-slate-700 select-all"
                                >
                                <button
                                    type="button"
                                    onclick="copyToClipboard('webhook-url-input', this)"
                                    class="shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-[10px] transition"
                                >
                                    Salin
                                </button>
                            </div>
                        </div>

                        <!-- Finish Redirect URL -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-600 uppercase">Finish / Unfinish Redirect URL</label>
                            <div class="flex items-center gap-1.5">
                                <input
                                    type="text"
                                    readonly
                                    id="finish-url-input"
                                    value="{{ $finishUrl }}"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-[11px] font-mono text-slate-700 select-all"
                                >
                                <button
                                    type="button"
                                    onclick="copyToClipboard('finish-url-input', this)"
                                    class="shrink-0 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-[10px] transition"
                                >
                                    Salin
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Box 2: Panduan Langkah Pengaturan untuk Pemilik Aplikasi -->
                    <div class="bg-slate-950 text-white rounded-3xl p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-slate-800 text-slate-200 flex items-center justify-center font-bold text-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            </div>
                            <h4 class="font-extrabold text-xs uppercase tracking-wide text-slate-200">Panduan Siap Pakai</h4>
                        </div>
                        <ol class="space-y-3 text-[11px] text-slate-350 list-decimal list-inside leading-relaxed">
                            <li>
                                Buat / masuk ke akun di <a href="https://dashboard.midtrans.com" target="_blank" class="text-sky-400 hover:underline font-bold">dashboard.midtrans.com</a>.
                            </li>
                            <li>
                                Buka menu <b>Settings > Access Keys</b>.
                            </li>
                            <li>
                                Salin <b>Client Key</b> dan <b>Server Key</b> ke formulir di sebelah kiri.
                            </li>
                            <li>
                                Buka menu <b>Settings > Configuration</b>, lalu tempel <b>Payment Notification URL</b> yang ada di kartu atas.
                            </li>
                            <li>
                                Klik tombol <b>Uji Koneksi Server Key</b> untuk memastikan kredensial terhubung dengan benar.
                            </li>
                            <li>
                                Klik <b>Simpan Pengaturan</b>. Aplikasi Anda langsung siap menerima pembayaran otomatis!
                            </li>
                        </ol>
                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

<script>
function toggleKeyVisibility() {
    const input = document.getElementById('server_key');
    const btn = document.getElementById('toggle-vis-btn');
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerText = 'Sembunyikan';
    } else {
        input.type = 'password';
        btn.innerText = 'Lihat';
    }
}

function copyToClipboard(elementId, btn) {
    const input = document.getElementById(elementId);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    
    const originalText = btn.innerText;
    btn.innerText = 'Tersalin!';
    btn.classList.add('bg-emerald-600', 'text-white');
    setTimeout(() => {
        btn.innerText = originalText;
        btn.classList.remove('bg-emerald-600', 'text-white');
    }, 2000);
}

function runTestConnection() {
    const serverKey = document.getElementById('server_key').value;
    const isProd = document.querySelector('input[name="is_production"]:checked')?.value || 0;
    const resultBox = document.getElementById('test-result-box');
    const testBtn = document.getElementById('btn-test-conn');

    if (!serverKey) {
        resultBox.className = 'rounded-2xl p-4 text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-700 block';
        resultBox.innerHTML = 'Masukkan Server Key terlebih dahulu di formulir sebelum menguji koneksi.';
        return;
    }

    testBtn.disabled = true;
    testBtn.innerHTML = '<span>Menguji Koneksi Midtrans...</span>';

    fetch('{{ route("admin.settings.midtrans.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            server_key: serverKey,
            is_production: isProd
        })
    })
    .then(res => res.json())
    .then(data => {
        testBtn.disabled = false;
        testBtn.innerHTML = '<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg><span>Uji Koneksi Server Key</span>';

        if (data.success) {
            resultBox.className = 'rounded-2xl p-4 text-xs font-semibold bg-emerald-50 border border-emerald-200 text-emerald-800 block';
            resultBox.innerHTML = '<b>' + data.message + '</b>';
        } else {
            resultBox.className = 'rounded-2xl p-4 text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-800 block';
            resultBox.innerHTML = '<b>' + data.message + '</b>';
        }
    })
    .catch(err => {
        testBtn.disabled = false;
        testBtn.innerHTML = '<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg><span>Uji Koneksi Server Key</span>';
        resultBox.className = 'rounded-2xl p-4 text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-800 block';
        resultBox.innerHTML = 'Gagal melakukan pengecekan: ' + err.message;
    });
}
</script>
@endsection
