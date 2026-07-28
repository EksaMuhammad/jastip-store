<div class="max-w-4xl mx-auto space-y-6">
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-sm font-semibold mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl text-sm font-semibold mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Balance Card -->
    <div class="bg-slate-900 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-10">
            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18V6c0-1.1-.9-2-2-2H5c-1.11 0-2 .89-2 2v12c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2zM5 18V6h14v12H5zm7-2c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm0-4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z"/></svg>
        </div>
        <div class="relative z-10">
            <h2 class="text-slate-400 text-sm font-bold uppercase tracking-wider mb-2">Total Saldo Wallet</h2>
            <div class="text-5xl font-black mb-6">Rp{{ number_format($balance, 0, ',', '.') }}</div>
            <button 
                wire:click="openTopupModal"
                class="bg-white text-slate-900 hover:bg-slate-100 font-bold py-3 px-8 rounded-full transition duration-200 shadow-md inline-flex items-center space-x-2 text-sm uppercase tracking-wider"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Top Up Saldo</span>
            </button>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Riwayat Transaksi</h3>
        
        @if(count($transactions) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider text-xs border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 rounded-tl-xl">Waktu</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-4 py-3 text-right">Nominal</th>
                            <th class="px-4 py-3 rounded-tr-xl text-center">Tipe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($transactions as $trx)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-slate-700">{{ $trx->created_at->format('d M Y') }}</div>
                                    <div class="text-xs text-slate-400">{{ $trx->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $trx->description ?? 'Transaksi Wallet' }}</div>
                                    @if($trx->reference_order_id)
                                        <div class="text-xs text-slate-500 mt-1">Ref: Order #{{ $trx->reference_order_id }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-bold {{ $trx->type === 'kredit' ? 'text-emerald-600' : 'text-slate-700' }}">
                                    {{ $trx->type === 'kredit' ? '+' : '-' }}Rp{{ number_format($trx->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($trx->type === 'kredit')
                                        <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-[10px] font-bold px-2 py-1 rounded-full uppercase">Masuk</span>
                                    @else
                                        <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold px-2 py-1 rounded-full uppercase">Keluar</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        @else
            <div class="py-12 text-center text-slate-500">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="font-medium">Belum ada riwayat transaksi.</p>
                <p class="text-sm mt-1">Lakukan top up untuk mulai menggunakan E-Wallet.</p>
            </div>
        @endif
    </div>

    <!-- Top Up Modal -->
    @if($showTopupModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-3xl p-6 w-full max-w-md shadow-2xl relative">
                <button wire:click="$set('showTopupModal', false)" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                
                <h3 class="text-xl font-bold text-slate-800 mb-6">Top Up E-Wallet</h3>
                
                <form wire:submit.prevent="processTopup">
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nominal Top Up</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-500">Rp</span>
                            <input 
                                type="number" 
                                wire:model.defer="amountToTopup" 
                                class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-2xl py-3 pl-12 pr-4 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent font-medium"
                                placeholder="100000"
                            >
                        </div>
                        @error('amountToTopup')
                            <p class="text-rose-500 text-xs font-semibold mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6">
                        <p class="text-sm text-blue-800 font-medium leading-relaxed">
                            Metode pembayaran default saat ini adalah <strong>QRIS</strong>. Anda akan diarahkan ke halaman pembayaran Midtrans.
                        </p>
                    </div>
                    
                    <button type="submit" class="w-full bg-slate-900 text-white font-bold rounded-2xl py-3.5 hover:bg-slate-800 transition shadow-lg">
                        Lanjut ke Pembayaran
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
