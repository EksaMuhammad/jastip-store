<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan & Mutasi Kas - PT JastipKuy Solusi Indonesia</title>
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
                size: A4 portrait;
                margin: 10mm;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="py-6 px-4 sm:px-6">

    <!-- Action Toolbar (Hidden in Print) -->
    <div class="max-w-5xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 no-print">
        <a href="{{ route('admin.mutations.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition shadow-xs">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Kembali ke Master Mutasi</span>
        </a>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-extrabold text-xs rounded-xl transition shadow-md cursor-pointer">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak Dokumen / Simpan PDF Laporan</span>
            </button>
        </div>
    </div>

    <!-- Official Report Sheet -->
    <div class="max-w-5xl mx-auto bg-white rounded-3xl p-6 sm:p-10 shadow-xl border border-slate-200 print-shadow-none">
        
        <!-- Header: Kop Surat Perusahaan Resmi -->
        <div class="border-b-2 border-slate-900 pb-5 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-black text-rose-600 tracking-tight">JastipKuy</span>
                        <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-slate-900 text-white tracking-widest">FINANCE PORTAL</span>
                    </div>
                    <h1 class="text-base font-black text-slate-900 uppercase tracking-wider mt-1">PT JASTIPKUY SOLUSI INDONESIA</h1>
                    <p class="text-[11px] text-slate-500 max-w-md leading-relaxed">
                        Grand Slipi Tower Lt. 9, Jl. S. Parman Kav. 22-24, Palmerah, Jakarta Barat 11480<br>
                        NPWP: 31.892.415.2-045.000 | Divisi Akuntansi & Keuangan Platform
                    </p>
                </div>
                <div class="text-left sm:text-right">
                    <span class="text-[10px] font-black uppercase tracking-wider text-rose-600 block">DOKUMEN LAPORAN PERUSAHAAN</span>
                    <h2 class="text-lg font-black text-slate-900 tracking-tight">LAPORAN KEUANGAN & MUTASI KAS</h2>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Periode: <strong class="text-slate-900">{{ $daysLabel }}</strong>
                    </p>
                    <p class="text-[10px] text-slate-400">
                        Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB | Oleh: {{ auth()->guard('admin')->user()->name ?? 'Administrator' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Ringkasan Eksekutif Arus Kas & Laba Bersih Perusahaan -->
        <div class="mb-6">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-2.5">RINGKASAN EKSEKUTIF ARUS KAS & LABA RUGI PLATFORM</h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                <div class="bg-emerald-50/70 p-3.5 rounded-2xl border border-emerald-200">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Total Uang Masuk (Kredit)</p>
                    <p class="text-base sm:text-lg font-black text-emerald-700 mt-0.5">Rp{{ number_format($totalKredit, 0, ',', '.') }}</p>
                    <p class="text-[9px] text-emerald-600 mt-0.5">Top-up, pembayaran & margin</p>
                </div>

                <div class="bg-rose-50/70 p-3.5 rounded-2xl border border-rose-200">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-rose-800">Total Uang Keluar (Debit)</p>
                    <p class="text-base sm:text-lg font-black text-rose-700 mt-0.5">Rp{{ number_format($totalDebit, 0, ',', '.') }}</p>
                    <p class="text-[9px] text-rose-600 mt-0.5">Withdraw, pembayaran & refund</p>
                </div>

                <div class="bg-indigo-50/70 p-3.5 rounded-2xl border border-indigo-200">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-800">Selisih Arus Kas Bersih</p>
                    <p class="text-base sm:text-lg font-black {{ $netMutasi >= 0 ? 'text-indigo-700' : 'text-rose-700' }} mt-0.5">
                        {{ $netMutasi >= 0 ? '+' : '' }}Rp{{ number_format($netMutasi, 0, ',', '.') }}
                    </p>
                    <p class="text-[9px] text-indigo-600 mt-0.5">Posisi net cash flow periode ini</p>
                </div>

                <div class="bg-slate-900 p-3.5 rounded-2xl text-white">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Total Laba Bersih Platform</p>
                    <p class="text-base sm:text-lg font-black text-emerald-400 mt-0.5">Rp{{ number_format($totalLabaPlatform, 0, ',', '.') }}</p>
                    <p class="text-[9px] text-slate-300 mt-0.5">Komisi driver + margin toko</p>
                </div>
            </div>

            <!-- Detail Breakdown Laba & Pool Asuransi -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
                <div>
                    <span class="text-slate-500 text-[11px] block">Komisi Ongkir Mitra Jastiper:</span>
                    <strong class="text-slate-900 font-extrabold text-sm">Rp{{ number_format($totalCommissionOngkir, 0, ',', '.') }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 text-[11px] block">Margin Laba Markup Barang Toko:</span>
                    <strong class="text-slate-900 font-extrabold text-sm">Rp{{ number_format($totalMarkupProfit, 0, ',', '.') }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 text-[11px] block">Dana Cadangan Pool Asuransi:</span>
                    <strong class="text-slate-900 font-extrabold text-sm">Rp{{ number_format($insurancePool['balance'] ?? 0, 0, ',', '.') }}</strong>
                    <span class="text-[10px] text-slate-400 block">(Klaim Selesai: Rp{{ number_format($insurancePool['total_claimed'] ?? 0, 0, ',', '.') }})</span>
                </div>
            </div>
        </div>

        <!-- Filter Parameters Terapan -->
        <div class="mb-4 flex flex-wrap items-center justify-between text-xs text-slate-600 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800">Filter Aktif:</span>
                <span class="px-2 py-0.5 rounded bg-slate-100 text-[11px]">Arus: <b>{{ ucfirst($type) }}</b></span>
                <span class="px-2 py-0.5 rounded bg-slate-100 text-[11px]">Role: <b>{{ ucfirst($role) }}</b></span>
                <span class="px-2 py-0.5 rounded bg-slate-100 text-[11px]">Sumber: <b>{{ $source !== 'all' ? ($availableSources[$source] ?? $source) : 'Semua Sumber' }}</b></span>
                @if($search)
                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 text-[11px]">Cari: "<b>{{ $search }}</b>"</span>
                @endif
            </div>
            <div class="text-[11px] text-slate-500 font-semibold">
                Total Baris Data: <strong>{{ count($mutations) }} Baris</strong>
            </div>
        </div>

        <!-- Tabel Ledger Mutasi -->
        <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200">
            <table class="w-full text-left text-[11px] text-slate-700">
                <thead class="bg-slate-900 text-white uppercase text-[9px] font-black tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 text-center w-8">No</th>
                        <th class="py-2.5 px-3 w-28">Waktu Transaksi</th>
                        <th class="py-2.5 px-3">Pemilik Akun & Role</th>
                        <th class="py-2.5 px-3">Kategori Sumber</th>
                        <th class="py-2.5 px-3">Keterangan / Deskripsi</th>
                        <th class="py-2.5 px-3 text-center w-16">Arus</th>
                        <th class="py-2.5 px-3 text-right w-28">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mutations as $idx => $trx)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 text-center text-slate-400 font-bold">{{ $idx + 1 }}</td>
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $trx->created_at->format('d/m/Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $trx->created_at->format('H:i:s') }} WIB</div>
                            </td>
                            <td class="py-2 px-3">
                                @if($trx->wallet && $trx->wallet->owner)
                                    <div class="font-extrabold text-slate-900">{{ $trx->wallet->owner->name }}</div>
                                    <div class="text-[10px] text-slate-500 capitalize">
                                        {{ $trx->wallet->owner_role ?? 'User' }} &bull; {{ $trx->wallet->owner->phone_number ?? '-' }}
                                    </div>
                                @else
                                    <div class="font-bold text-slate-500">Platform / Sistem</div>
                                @endif
                            </td>
                            <td class="py-2 px-3">
                                <span class="font-semibold text-slate-700">{{ $availableSources[$trx->source] ?? ucfirst(str_replace('_', ' ', $trx->source ?? 'Umum')) }}</span>
                                @if($trx->reference_order_id)
                                    <div class="text-[10px] text-rose-600 font-bold">Ref: #{{ $trx->reference_order_id }}</div>
                                @endif
                            </td>
                            <td class="py-2 px-3">
                                <p class="text-slate-800 leading-snug">{{ $trx->description ?? '-' }}</p>
                            </td>
                            <td class="py-2 px-3 text-center">
                                @if($trx->type === 'kredit')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">Kredit</span>
                                @else
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">Debit</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right font-extrabold {{ $trx->type === 'kredit' ? 'text-emerald-700' : 'text-slate-800' }}">
                                {{ $trx->type === 'kredit' ? '+' : '-' }}Rp{{ number_format($trx->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 italic">
                                Tidak ada data mutasi keuangan pada periode yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Otorisasi & Tanda Tangan Audit Dokumen Perusahaan -->
        <div class="border-t border-slate-200 pt-6 mt-6">
            <div class="grid grid-cols-3 gap-6 text-center text-xs">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Disiapkan Oleh,</p>
                    <div class="h-16 flex items-end justify-center">
                        <div class="border-b border-dashed border-slate-300 w-36"></div>
                    </div>
                    <p class="font-extrabold text-slate-800 mt-1">{{ auth()->guard('admin')->user()->name ?? 'Administrator' }}</p>
                    <p class="text-[10px] text-slate-400">Finance & Operational Admin</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Diperiksa Oleh,</p>
                    <div class="h-16 flex items-end justify-center">
                        <div class="border-b border-dashed border-slate-300 w-36"></div>
                    </div>
                    <p class="font-extrabold text-slate-800 mt-1">Finance & Accounting Lead</p>
                    <p class="text-[10px] text-slate-400">Financial Controller</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Disetujui Oleh,</p>
                    <div class="h-16 flex items-end justify-center">
                        <div class="border-b border-dashed border-slate-300 w-36"></div>
                    </div>
                    <p class="font-extrabold text-slate-800 mt-1">Direktur Utama</p>
                    <p class="text-[10px] text-slate-400">PT JastipKuy Solusi Indonesia</p>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center text-[9px] text-slate-400 border-t border-slate-100 pt-3">
            Laporan ini dicetak secara otomatis oleh Sistem Enterprise JastipKuy. Kerahasiaan data ini dilindungi oleh kebijakan perusahaan PT JastipKuy Solusi Indonesia.
        </div>

    </div>

</body>
</html>

