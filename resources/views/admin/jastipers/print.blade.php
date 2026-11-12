<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Mitra Jastiper - PT JastipKuy Solusi Indonesia</title>
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
        <a href="{{ route('admin.jastipers.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition shadow-xs">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Kembali ke Data Jastiper</span>
        </a>

        <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-extrabold text-xs rounded-xl transition shadow-md cursor-pointer">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            <span>Cetak Dokumen / Simpan PDF</span>
        </button>
    </div>

    <!-- Official Jastiper Fleet Report Sheet -->
    <div class="max-w-5xl mx-auto bg-white rounded-3xl p-6 sm:p-10 shadow-xl border border-slate-200 print-shadow-none">
        
        <!-- Header: Kop Surat Perusahaan -->
        <div class="border-b-2 border-slate-900 pb-5 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-black text-rose-600 tracking-tight">JastipKuy</span>
                        <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-slate-900 text-white tracking-widest">LOGISTICS</span>
                    </div>
                    <h1 class="text-base font-black text-slate-900 uppercase tracking-wider mt-1">PT JASTIPKUY SOLUSI INDONESIA</h1>
                    <p class="text-[11px] text-slate-500 max-w-md leading-relaxed">
                        Grand Slipi Tower Lt. 9, Jl. S. Parman Kav. 22-24, Palmerah, Jakarta Barat 11480<br>
                        NPWP: 31.892.415.2-045.000 | Laporan Audit Mitra Armada Jastiper
                    </p>
                </div>
                <div class="text-left sm:text-right">
                    <span class="text-[10px] font-black uppercase tracking-wider text-rose-600 block">MITRA & ARMADA REPORT</span>
                    <h2 class="text-lg font-black text-slate-900 tracking-tight">LAPORAN DATA MITRA JASTIPER</h2>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Total Mitra: <strong class="text-slate-900">{{ number_format($totalJastiperCount, 0, ',', '.') }} Orang</strong> &bull; Terverifikasi: <strong class="text-emerald-700">{{ number_format($totalApprovedCount, 0, ',', '.') }}</strong> &bull; Online: <strong class="text-blue-700">{{ number_format($totalOnlineCount, 0, ',', '.') }}</strong>
                    </p>
                    <p class="text-[10px] text-slate-400">
                        Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB | Oleh: {{ auth()->guard('admin')->user()->name ?? 'Administrator' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Tabel Jastiper -->
        <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200">
            <table class="w-full text-left text-[11px] text-slate-700">
                <thead class="bg-slate-900 text-white uppercase text-[9px] font-black tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 text-center w-8">No</th>
                        <th class="py-2.5 px-3 w-28">Jastiper ID</th>
                        <th class="py-2.5 px-3">Nama Mitra & No. HP</th>
                        <th class="py-2.5 px-3">Wilayah & Kendaraan</th>
                        <th class="py-2.5 px-3 text-center">Verifikasi</th>
                        <th class="py-2.5 px-3 text-center">Status Kerja</th>
                        <th class="py-2.5 px-3 text-right">Saldo Dompet</th>
                        <th class="py-2.5 px-3 text-center">Order Selesai</th>
                        <th class="py-2.5 px-3 text-center">Rating</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($jastipers as $idx => $jastip)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 px-3 text-center text-slate-400 font-bold">{{ $idx + 1 }}</td>
                            <td class="py-2 px-3 whitespace-nowrap font-mono font-bold text-slate-700">
                                #JASTIP-{{ str_pad($jastip->id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="py-2 px-3">
                                <div class="font-extrabold text-slate-900">{{ $jastip->name }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $jastip->phone_number }}</div>
                            </td>
                            <td class="py-2 px-3">
                                <div class="font-semibold text-slate-800">{{ $jastip->wilayah->name ?? 'Semua Wilayah' }}</div>
                                <div class="text-[10px] text-slate-500">{{ $jastip->vehicle_type ?? 'Motor' }} &bull; {{ $jastip->vehicle_plate ?? '-' }}</div>
                            </td>
                            <td class="py-2 px-3 text-center">
                                @if($jastip->verification_status === 'approved')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">Disetujui</span>
                                @elseif($jastip->verification_status === 'pending')
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200">Pending</span>
                                @else
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">Ditolak</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-center">
                                @if(in_array($jastip->work_status, ['tersedia', 'standby']))
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">Online</span>
                                @else
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-slate-100 text-slate-600">Offline</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right font-extrabold text-slate-900">
                                Rp{{ number_format($jastip->wallet->balance ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold text-slate-800">
                                {{ $jastip->completed_orders_count ?? 0 }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold text-amber-600">
                                ⭐ {{ number_format($jastip->ratings_avg_rating ?? 5.0, 1) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 italic">
                                Belum ada data mitra jastiper yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Otorisasi & Tanda Tangan Audit Dokumen Perusahaan -->
        <div class="border-t border-slate-200 pt-6 mt-6">
            <div class="grid grid-cols-2 gap-8 text-center text-xs">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Disusun Oleh,</p>
                    <div class="h-16 flex items-end justify-center">
                        <div class="border-b border-dashed border-slate-300 w-44"></div>
                    </div>
                    <p class="font-extrabold text-slate-800 mt-1">{{ auth()->guard('admin')->user()->name ?? 'Administrator' }}</p>
                    <p class="text-[10px] text-slate-400">Fleet Operations Administrator</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Mengetahui & Menyetujui,</p>
                    <div class="h-16 flex items-end justify-center">
                        <div class="border-b border-dashed border-slate-300 w-44"></div>
                    </div>
                    <p class="font-extrabold text-slate-800 mt-1">Head of Fleet & Logistics</p>
                    <p class="text-[10px] text-slate-400">PT JastipKuy Solusi Indonesia</p>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center text-[9px] text-slate-400 border-t border-slate-100 pt-3">
            Laporan ini merupakan dokumen internal resmi PT JastipKuy Solusi Indonesia.
        </div>

    </div>

</body>
</html>

