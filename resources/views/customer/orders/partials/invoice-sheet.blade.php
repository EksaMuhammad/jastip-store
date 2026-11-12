@php
    $items = $order->orderItems;
    $subtotalItems = $items->sum(function($item) {
        $price = $item->actual_price ?: $item->estimated_price ?: 0;
        return $price * ($item->quantity ?: 1);
    });
    $deliveryFee = (float) ($order->agreed_fare ?? $order->estimated_fare ?? 0);
    $serviceFee = 2000;
    $multiStopFee = (is_array($order->multi_stops) && count($order->multi_stops) > 1) ? (count($order->multi_stops) - 1) * 3000 : 0;
    $insuranceFee = $order->has_insurance ? (float) ($order->insurance_fee ?? 2000) : 0;
    $grandTotal = $subtotalItems + $deliveryFee + $serviceFee + $multiStopFee + $insuranceFee;
    if ($grandTotal <= 0 && $deliveryFee > 0) {
        $grandTotal = $deliveryFee + $serviceFee + $insuranceFee;
    }
    $isPaid = in_array($order->status, ['deal', 'diproses', 'barang_diambil', 'sedang_diantar', 'tiba_tujuan', 'selesai'])
        || ($latestPayment && in_array($latestPayment->status, ['settlement', 'verified', 'paid', 'lunas']));
@endphp

<!-- Official Invoice Document Sheet -->
<div class="w-full max-w-3xl mx-auto bg-white rounded-3xl p-5 sm:p-10 shadow-xl border border-slate-200/80 print-shadow-none relative overflow-hidden text-slate-800">
    
    <!-- Watermark / Background Logo Accent -->
    <div class="absolute -right-16 -top-16 w-64 h-64 bg-rose-50 rounded-full blur-3xl opacity-60 pointer-events-none"></div>

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
                    Grand Slipi Tower Lt. 9, Jl. S. Parman Kav. 22-24, Palmerah, Jakarta Barat 11480<br>
                    NPWP: 31.892.415.2-045.000 | Email: finance@jastipkuy.com | Web: jastipkuy.com
                </p>
            </div>
            <div class="text-left sm:text-right">
                <span class="text-xs font-black uppercase tracking-wider text-rose-600 block">FAKTUR / NOTA TRANSAKSI</span>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight mt-0.5 break-all">
                    INV/{{ $order->created_at->format('Ymd') }}/ORD-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                </h1>
                <p class="text-[11px] text-slate-500 mt-1">
                    Tanggal: <strong class="text-slate-800">{{ $order->created_at->format('d M Y, H:i') }} WIB</strong>
                </p>
                @if($isPaid)
                    <div class="mt-2 inline-block px-3 py-1 rounded-lg text-xs font-black tracking-widest stamp-lunas uppercase border border-emerald-600 text-emerald-700 bg-emerald-50">
                        ✓ LUNAS / TRANSAKSI VALID
                    </div>
                @else
                    <div class="mt-2 inline-block px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-300">
                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Customer & Order Meta Information -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 pb-6 mb-6 border-b border-slate-100 text-xs">
        <!-- Ditagihkan Kepada -->
        <div class="space-y-1 bg-slate-50 p-4 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">DITAGIHKAN KEPADA (PELANGGAN):</p>
            <p class="text-sm font-black text-slate-900">{{ $order->customer->name ?? 'Pelanggan JastipKuy' }}</p>
            <p class="text-slate-600">No. HP: <strong>{{ $order->customer->phone_number ?? '-' }}</strong></p>
            <div class="pt-2 mt-2 border-t border-slate-200">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Penerima & Alamat Pengantaran:</p>
                <p class="font-bold text-slate-800">{{ $order->recipient_name ?: ($order->customer->name ?? '-') }} ({{ $order->recipient_phone ?: ($order->customer->phone_number ?? '-') }})</p>
                <p class="text-slate-600 mt-0.5 leading-snug">{{ $order->destination_address ?? '-' }}</p>
            </div>
        </div>

        <!-- Detail Kurir & Pengiriman -->
        <div class="space-y-1 bg-slate-50 p-4 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">INFO EKSEKUSI & KURIR JASTIPER:</p>
            <p class="text-sm font-black text-slate-900">{{ $order->jastiper->name ?? 'Mitra Kurir JastipKuy' }}</p>
            <p class="text-slate-600">Plat Kendaraan: <strong class="text-slate-800">{{ $order->jastiper->vehicle_plate ?? '-' }}</strong></p>
            <p class="text-slate-600">Kontak Kurir: <strong>{{ $order->jastiper->phone_number ?? '-' }}</strong></p>
            <div class="pt-2 mt-2 border-t border-slate-200">
                <p class="text-[10px] font-bold text-slate-500 uppercase">Metode Pembayaran:</p>
                <p class="font-black text-emerald-700">
                    {{ $order->payments->first() ? strtoupper($order->payments->first()->payment_method ?? 'SALDO E-WALLET') : 'SALDO E-WALLET JASTIPKUY' }}
                </p>
                <p class="text-[10px] text-slate-500 mt-0.5">Kategori Order: <span class="capitalize font-semibold text-slate-700">{{ str_replace('-', ' ', $order->category ?? 'Jastip Titip Belanja') }}</span></p>
                @if($order->description)
                    <div class="mt-2 p-2 bg-slate-100/80 rounded-xl border border-slate-200">
                        <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Deskripsi / Catatan Pesanan:</span>
                        <span class="text-xs font-bold text-slate-800 leading-snug">{{ $order->description }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Titik Singgah Multi-Stop (Jika Ada) -->
    @if(is_array($order->multi_stops) && count($order->multi_stops) > 0)
    <div class="mb-6 p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80">
        <h3 class="text-xs font-black uppercase tracking-wider text-amber-900 mb-2 flex items-center gap-1.5">
            <span>📍</span> Titik Singgah / Rute Belanja Multi-Toko ({{ count($order->multi_stops) }} Toko)
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
            @foreach($order->multi_stops as $st)
                <div class="bg-white p-2.5 rounded-xl border border-amber-100 flex items-start gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-500 text-white font-extrabold text-[10px] flex items-center justify-center shrink-0 mt-0.5">
                        {{ $st['stop_number'] ?? $loop->iteration }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-slate-800 truncate">{{ $st['name'] ?? 'Toko Singgah' }}</p>
                        <p class="text-slate-500 text-[10px] truncate">{{ $st['address'] ?? '-' }}</p>
                        @if(!empty($st['status']) && $st['status'] === 'completed')
                            <span class="inline-block mt-0.5 text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200">✓ Selesai Diambil</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Rincian Item Belanja (Itemized Table) -->
    <div class="mb-6 overflow-x-auto rounded-2xl border border-slate-200">
        <table class="w-full text-left text-xs text-slate-700 min-w-[500px]">
            <thead class="bg-slate-900 text-white uppercase text-[10px] font-black tracking-wider">
                <tr>
                    <th class="py-3 px-3 w-10 text-center">No</th>
                    <th class="py-3 px-3">Item / Produk & Toko</th>
                    <th class="py-3 px-3 text-center w-16">Qty</th>
                    <th class="py-3 px-3 text-right w-28">Harga Satuan</th>
                    <th class="py-3 px-3 text-right w-32">Total Harga</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($items as $idx => $it)
                    @php
                        $unitPrice = $it->actual_price ?: $it->estimated_price ?: 0;
                        $rowTotal = $unitPrice * ($it->quantity ?: 1);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-3 text-center text-slate-400 font-bold">{{ $idx + 1 }}</td>
                        <td class="py-3 px-3">
                            <p class="font-extrabold text-slate-900">{{ $it->clean_name }}</p>
                            @if($it->merchant_name)
                                <p class="text-[10px] text-slate-500 font-medium">Toko: {{ $it->merchant_name }}</p>
                            @elseif($order->merchant)
                                <p class="text-[10px] text-slate-500 font-medium">Toko: {{ $order->merchant->name }}</p>
                            @endif
                            @if($it->notes)
                                <p class="text-[10px] text-amber-700 italic">Catatan: "{{ $it->notes }}"</p>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center font-bold text-slate-800">{{ $it->quantity ?: 1 }}</td>
                        <td class="py-3 px-3 text-right font-medium text-slate-700">Rp{{ number_format($unitPrice, 0, ',', '.') }}</td>
                        <td class="py-3 px-3 text-right font-extrabold text-slate-900">Rp{{ number_format($rowTotal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="py-3 px-3 text-center text-slate-400 font-bold">1</td>
                        <td class="py-3 px-3">
                            <p class="font-extrabold text-slate-900">{{ $order->description }}</p>
                            <p class="text-[10px] text-slate-500">Jasa Titip Pembelian Barang Custom</p>
                        </td>
                        <td class="py-3 px-3 text-center font-bold text-slate-800">1</td>
                        <td class="py-3 px-3 text-right font-medium text-slate-700">Rp{{ number_format($order->downpayment_amount ?? 0, 0, ',', '.') }}</td>
                        <td class="py-3 px-3 text-right font-extrabold text-slate-900">Rp{{ number_format($order->downpayment_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforelse

                <!-- Addon Items jika ada -->
                @if($order->addons && $order->addons->isNotEmpty())
                    @foreach($order->addons as $addonIdx => $addon)
                        @if($addon->status === 'paid' || $addon->status === 'disetujui')
                        <tr class="bg-amber-50/40">
                            <td class="py-2.5 px-3 text-center text-amber-600 font-bold">+</td>
                            <td class="py-2.5 px-3">
                                <p class="font-bold text-amber-950">Add-on: {{ $addon->description }}</p>
                                <p class="text-[10px] text-amber-700">Permintaan titip belanja tambahan</p>
                            </td>
                            <td class="py-2.5 px-3 text-center font-bold text-slate-800">1</td>
                            <td class="py-2.5 px-3 text-right font-medium text-slate-700">Rp{{ number_format($addon->price, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-extrabold text-amber-900">Rp{{ number_format($addon->price, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <!-- Breakdown Biaya & Total Perhitungan -->
    <div class="flex flex-col sm:flex-row items-start justify-between gap-6 mb-8 text-xs">
        <div class="w-full sm:w-1/2 p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2">
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">CATATAN & LEGALITAS TRANSAKSI:</p>
            <p class="text-[11px] text-slate-600 leading-relaxed">
                Dokumen ini merupakan bukti pembayaran resmi yang sah yang diterbitkan oleh sistem <strong>JastipKuy</strong>. Berlaku sebagai bukti transaksi sah, pembukuan keuangan, dan kebutuhan administrasi resmi sesuai ketentuan perpajakan RI.
            </p>
            <div class="pt-2 flex items-center gap-2 text-[10px] text-emerald-700 font-bold">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span>Terverifikasi melalui Virtual Escrow JastipKuy</span>
            </div>
        </div>

        <div class="w-full sm:w-1/2 space-y-2">
            <div class="flex items-center justify-between text-slate-600">
                <span>Subtotal Belanja Barang</span>
                <span class="font-bold text-slate-800">Rp{{ number_format($subtotalItems, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
                <span>Ongkos Jasa Jastip / Kurir</span>
                <span class="font-bold text-slate-800">Rp{{ number_format($deliveryFee, 0, ',', '.') }}</span>
            </div>
            @if($multiStopFee > 0)
            <div class="flex items-center justify-between text-slate-600">
                <span>Biaya Multi-Stop Titik Singgah</span>
                <span class="font-bold text-slate-800">Rp{{ number_format($multiStopFee, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="flex items-center justify-between text-slate-600">
                <span>Biaya Layanan & Penanganan Aplikasi</span>
                <span class="font-bold text-slate-800">Rp{{ number_format($serviceFee, 0, ',', '.') }}</span>
            </div>
            @if($insuranceFee > 0)
            <div class="flex items-center justify-between text-slate-600">
                <span>Proteksi Jaminan Asuransi</span>
                <span class="font-bold text-slate-800">Rp{{ number_format($insuranceFee, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="pt-2 border-t-2 border-slate-900 flex items-center justify-between text-sm sm:text-base">
                <span class="font-black text-slate-900 uppercase">TOTAL PEMBAYARAN</span>
                <span class="font-black text-rose-600">Rp{{ number_format($grandTotal, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Otorisasi & Tanda Tangan -->
    <div class="border-t border-slate-200 pt-6 mt-6">
        <div class="grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Diterima / Pemesan,</p>
                <div class="h-16 flex items-end justify-center">
                    <div class="border-b border-dashed border-slate-300 w-44"></div>
                </div>
                <p class="font-extrabold text-slate-800 mt-1">{{ $order->customer->name ?? 'Pelanggan JastipKuy' }}</p>
                <p class="text-[10px] text-slate-400">Customer JastipKuy</p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Otorisasi Resmi,</p>
                <div class="h-16 flex items-center justify-center">
                    <div class="stamp-lunas px-3 py-1 rounded text-[10px] font-black uppercase tracking-wider border border-emerald-600 text-emerald-700 bg-emerald-50">
                        PT JASTIPKUY SOLUSI INDONESIA<br>
                        <span class="text-[8px] text-emerald-800">FINANCE & ESCROW DEPT</span>
                    </div>
                </div>
                <p class="font-extrabold text-slate-800 mt-1">Finance Operations Team</p>
                <p class="text-[10px] text-slate-400">Sistem Settlement Terverifikasi</p>
            </div>
        </div>
    </div>

    <div class="mt-8 text-center text-[10px] text-slate-400 border-t border-slate-100 pt-3">
        Dokumen elektronik ini digenerate secara otomatis oleh Platform JastipKuy dan sah tanpa tanda tangan basah fisik. Hubungi finance@jastipkuy.com untuk verifikasi validitas faktur.
    </div>

</div>

