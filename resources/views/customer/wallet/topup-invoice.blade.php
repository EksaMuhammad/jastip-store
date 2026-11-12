<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Top-Up #TOPUP-{{ str_pad($topup->id, 5, '0', STR_PAD_LEFT) }} - JastipKuy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
            }
            .no-print {
                display: none !important;
            }
            .print-shadow-none {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
            }
            @page {
                size: A4;
                margin: 15mm;
            }
        }
        .stamp-lunas {
            border: 3px double #059669;
            color: #059669;
            background-color: rgba(16, 185, 129, 0.06);
            transform: rotate(-7deg);
        }
    </style>
</head>
<body class="py-6 px-4 sm:px-6">

    <!-- Action Toolbar (Hidden in Print) -->
    <div class="max-w-2xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 no-print">
        <a href="{{ route('customer.wallet') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition shadow-xs">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Kembali ke E-Wallet</span>
        </a>

        <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-extrabold text-xs rounded-xl transition shadow-md cursor-pointer">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            <span>Cetak / Unduh Bukti Top-Up</span>
        </button>
    </div>

    <!-- Official Topup Receipt Document Sheet -->
    <div class="max-w-2xl mx-auto bg-white rounded-3xl p-6 sm:p-10 shadow-xl border border-slate-200/80 print-shadow-none relative overflow-hidden">
        
        <!-- Header: Kop Surat Perusahaan -->
        <div class="border-b-2 border-slate-900/90 pb-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-black text-rose-600 tracking-tight">JastipKuy</span>
                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-slate-900 text-white tracking-widest">OFFICIAL</span>
                    </div>
                    <p class="text-xs font-black text-slate-800 mt-1 uppercase tracking-wider">PT JASTIPKUY SOLUSI INDONESIA</p>
                    <p class="text-[11px] text-slate-500 max-w-sm mt-0.5 leading-relaxed">
                        Grand Slipi Tower Lt. 9, Jl. S. Parman Kav. 22-24, Jakarta Barat 11480<br>
                        NPWP: 31.892.415.2-045.000 | Divisi Pembayaran & Escrow
                    </p>
                </div>
                <div class="text-left sm:text-right">
                    <span class="text-xs font-black uppercase tracking-wider text-emerald-600 block">BUKTI PENERIMAAN DANA (TOP-UP)</span>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">
                        #TOPUP-{{ str_pad($topup->id, 5, '0', STR_PAD_LEFT) }}
                    </h1>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Waktu: <strong class="text-slate-800">{{ $topup->created_at->format('d M Y, H:i') }} WIB</strong>
                    </p>
                    <div class="mt-2 inline-block px-3 py-1 rounded-lg text-xs font-black tracking-widest stamp-lunas uppercase">
                        ✓ {{ strtoupper($topup->status) }} / BERHASIL MASUK
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Pemilik Akun & Transaksi -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-6 mb-6 border-b border-slate-100 text-xs">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-1">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">PEMILIK AKUN SALDO (CUSTOMER):</p>
                <p class="text-sm font-black text-slate-900">{{ $customer->name ?? 'Pelanggan JastipKuy' }}</p>
                <p class="text-slate-600">No. HP: <strong>{{ $customer->phone_number ?? '-' }}</strong></p>
                <p class="text-slate-600">ID Customer: <strong class="text-slate-800">#CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</strong></p>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-1">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">DETAIL METODE & GATEWAY:</p>
                <p class="text-sm font-black text-slate-900 uppercase">{{ $topup->channel ?: ($topup->method ?: 'Transfer / VA') }}</p>
                @if($topup->va_number)
                    <p class="text-slate-600">No. Rekening / VA: <strong class="text-slate-800 font-mono">{{ $topup->va_number }}</strong></p>
                @endif
                @if($topup->gateway_reference)
                    <p class="text-slate-600">Ref. Transaksi: <strong class="text-slate-800 font-mono">{{ $topup->gateway_reference }}</strong></p>
                @endif
                <p class="text-slate-600">Verifikasi: <strong class="text-emerald-700">{{ $topup->verified_at ? $topup->verified_at->format('d M Y, H:i') . ' WIB' : 'Otomatis Sistem' }}</strong></p>
            </div>
        </div>

        <!-- Rincian Nilai Finansial Top-Up -->
        <div class="mb-6 rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-900 text-white uppercase text-[10px] font-black tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Deskripsi Transaksi</th>
                        <th class="py-3 px-4 text-center w-24">Status</th>
                        <th class="py-3 px-4 text-right w-40">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="py-4 px-4">
                            <p class="font-extrabold text-slate-900 text-sm">Pengisian Saldo E-Wallet (Top-Up Dana Operasional)</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Saldo langsung dikreditkan dan aktif untuk pembayaran pesanan jasa titip.</p>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="inline-block px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[10px] rounded-full uppercase">Sukses</span>
                        </td>
                        <td class="py-4 px-4 text-right font-extrabold text-slate-900 text-sm">
                            Rp{{ number_format($topup->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <td class="py-2.5 px-4 text-slate-600">Biaya Administrasi & Layanan Top-Up</td>
                        <td class="py-2.5 px-4 text-center text-slate-400 font-bold">-</td>
                        <td class="py-2.5 px-4 text-right font-bold text-emerald-600">Rp 0 (Gratis)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Total Nominal Final -->
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between mb-8">
            <div>
                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-800">TOTAL DANA DITERIMA DI SALDO E-WALLET</p>
                <p class="text-xs text-emerald-700 mt-0.5">Sah & terverifikasi resmi untuk administrasi pembukuan & keuangan</p>
            </div>
            <div class="text-right">
                <span class="text-xl sm:text-2xl font-black text-emerald-800">Rp{{ number_format($topup->amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Tanda Tangan & Otorisasi -->
        <div class="border-t border-slate-200 pt-6 mt-6">
            <div class="grid grid-cols-2 gap-8 text-center text-xs">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Penerima Manfaat,</p>
                    <div class="h-14 flex items-end justify-center">
                        <div class="border-b border-dashed border-slate-300 w-40"></div>
                    </div>
                    <p class="font-extrabold text-slate-800 mt-1">{{ $customer->name ?? 'Pemegang Akun' }}</p>
                    <p class="text-[10px] text-slate-400">Customer</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Finance Treasury,</p>
                    <div class="h-14 flex items-center justify-center">
                        <div class="stamp-lunas px-3 py-1 rounded text-[10px] font-black uppercase tracking-wider">
                            PT JASTIPKUY SOLUSI INDONESIA<br>
                            <span class="text-[8px] text-emerald-800">TREASURY & SETTLEMENT</span>
                        </div>
                    </div>
                    <p class="font-extrabold text-slate-800 mt-1">E-Wallet Treasury Division</p>
                    <p class="text-[10px] text-slate-400">Settlement Elektronik Sah</p>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center text-[10px] text-slate-400 border-t border-slate-100 pt-3">
            Dokumen elektronik ini digenerate secara resmi oleh Platform JastipKuy untuk keperluan dokumentasi, pencatatan transaksi & pembukuan keuangan.
        </div>

    </div>

</body>
</html>

