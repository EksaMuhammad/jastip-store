<?php

use Livewire\Volt\Component;
use App\Models\Order;
use App\Models\OrderAddon;
use Illuminate\Support\Facades\Auth;
use App\Services\WhatsAppService;

new class extends Component
{
    public Order $order;
    public $role;
    
    public function mount(Order $order)
    {
        $this->order = $order;
        if (Auth::guard('customer')->check()) {
            $this->role = 'customer';
        } elseif (Auth::guard('jastiper')->check()) {
            $this->role = 'jastiper';
        }
    }

    public function updateStatus($newStatus)
    {
        if ($this->role !== 'jastiper') {
            return;
        }

        $validStatuses = ['diproses', 'barang_diambil', 'sedang_diantar', 'tiba_tujuan', 'selesai'];
        if (!in_array($newStatus, $validStatuses)) {
            return;
        }

        $this->order->update(['status' => $newStatus]);
        
        // Notify Customer
        $customer = $this->order->customer;
        if ($customer) {
            $statusMessages = [
                'barang_diambil' => "Jastiper telah mengambil barang pesanan \"{$this->order->description}\".",
                'sedang_diantar' => "Jastiper sedang mengantar pesanan \"{$this->order->description}\" ke tujuan.",
                'tiba_tujuan' => "Pesanan \"{$this->order->description}\" telah tiba di tujuan! Mohon konfirmasi penerimaan.",
                'selesai' => "Pesanan \"{$this->order->description}\" telah selesai. Terima kasih!"
            ];
            
            if (isset($statusMessages[$newStatus])) {
                WhatsAppService::sendMessage($customer->phone_number, $statusMessages[$newStatus]);
            }
        }
        
        $this->dispatch('status-updated');
    }
};
?>

<div class="p-4 sm:p-5 bg-white rounded-xl border border-gray-100 shadow-sm mb-4">
    <div class="flex justify-between items-start mb-4">
        <div class="pr-3">
            <h3 class="font-semibold text-gray-900 leading-tight">{{ $order->description }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</p>
        </div>
        <div class="text-right shrink-0">
            <div class="font-bold text-gray-900 text-lg">Rp {{ number_format($order->agreed_fare, 0, ',', '.') }}</div>
        </div>
    </div>
    
    <!-- Status Progress -->
    <div class="mb-6 overflow-x-auto py-2">
        @php
            $statuses = [
                'diproses' => 'Diproses',
                'barang_diambil' => 'Diambil',
                'sedang_diantar' => 'Diantar',
                'tiba_tujuan' => 'Tiba',
                'selesai' => 'Selesai'
            ];
            $currentIndex = array_search($order->status, array_keys($statuses));
            if ($currentIndex === false && in_array($order->status, ['menunggu_pembayaran'])) {
                $currentIndex = -1;
            }
        @endphp
        
        <div class="flex items-center justify-between relative min-w-[280px]">
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 rounded-full z-0"></div>
            @foreach($statuses as $key => $label)
                @php
                    $stepIndex = array_search($key, array_keys($statuses));
                    $isActive = $stepIndex <= $currentIndex;
                    $isCurrent = $stepIndex === $currentIndex;
                @endphp
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full {{ $isActive ? 'bg-primary-600' : 'bg-gray-300' }} {{ $isCurrent ? 'ring-4 ring-primary-100' : '' }}"></div>
                    <span class="text-[10px] sm:text-xs mt-2 {{ $isActive ? 'text-primary-600 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Riwayat Tambahan Item -->
    @php
        $allAddons = $order->addons;
    @endphp
    @if($allAddons->count() > 0)
        <div class="mb-5 border-t border-gray-100 pt-4">
            <h4 class="font-semibold text-sm text-gray-900 mb-3">Tambahan Item:</h4>
            <div class="space-y-3">
                @foreach($allAddons as $addon)
                    @if($role === 'jastiper' && $addon->payment_status === 'pending_jastiper')
                        <div x-data="{
                            loading: false,
                            additionalFare: '',
                            async respond(action) {
                                if(action === 'accept' && !this.additionalFare) {
                                    alert('Silakan isi biaya tambahan.');
                                    return;
                                }
                                this.loading = true;
                                try {
                                    let res = await fetch('/jastiper/addons/{{ $addon->id }}/respond', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                        body: JSON.stringify({ action: action, additional_fare: action === 'accept' ? this.additionalFare : 0 })
                                    });
                                    let data = await res.json();
                                    if(data.success) {
                                        $wire.$refresh();
                                    } else {
                                        alert(data.message || 'Gagal menyimpan respons.');
                                    }
                                } catch(e) { alert('Terjadi kesalahan jaringan.'); }
                                this.loading = false;
                            }
                        }" class="bg-orange-50 p-4 rounded-xl border border-orange-200">
                            <div class="flex items-start gap-2 mb-3">
                                <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <p class="text-sm text-gray-800 font-medium">"{{ $addon->description }}"</p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                                <div class="relative w-full sm:w-1/2">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm">Rp</span>
                                    </div>
                                    <input type="number" x-model="additionalFare" class="w-full pl-10 border-gray-300 rounded-lg py-2 text-sm focus:ring-primary-500 focus:border-primary-500 bg-white" placeholder="Biaya (Misal: 15000)">
                                </div>
                                <div class="flex gap-2 w-full sm:w-auto">
                                    <button @click="respond('accept')" :disabled="loading" class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors">Terima</button>
                                    <button @click="respond('reject')" :disabled="loading" class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 disabled:opacity-50 transition-colors">Tolak</button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm">
                            <div class="w-full">
                                <p class="font-medium text-gray-800 break-words">{{ $addon->description }}</p>
                                <div class="flex items-center gap-1 mt-1.5">
                                    @if($addon->payment_status === 'pending_jastiper')
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-orange-600 bg-orange-100 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Menunggu Respon Jastiper
                                        </span>
                                    @elseif($addon->payment_status === 'rejected')
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 bg-red-100 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            Ditolak Jastiper
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Disetujui (+ Rp {{ number_format($addon->additional_fare, 0, ',', '.') }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Actions -->
    <div class="flex flex-wrap gap-2 justify-end mt-4 pt-4 border-t border-gray-100">
        @if($role === 'jastiper')
            @if($order->status === 'diproses')
                <button wire:click="updateStatus('barang_diambil')" class="w-full sm:w-auto btn btn-primary px-5 py-2.5 rounded-xl text-sm font-medium transition-all shadow-sm hover:shadow">Konfirmasi Barang Diambil</button>
            @elseif($order->status === 'barang_diambil')
                <button wire:click="updateStatus('sedang_diantar')" class="w-full sm:w-auto btn btn-primary px-5 py-2.5 rounded-xl text-sm font-medium transition-all shadow-sm hover:shadow">Mulai Mengantar</button>
            @elseif($order->status === 'sedang_diantar')
                <button wire:click="updateStatus('tiba_tujuan')" class="w-full sm:w-auto btn btn-primary px-5 py-2.5 rounded-xl text-sm font-medium transition-all shadow-sm hover:shadow">Konfirmasi Tiba di Tujuan</button>
            @endif
        @elseif($role === 'customer')
            @if(in_array($order->status, ['diproses', 'barang_diambil']))
                <div x-data="{ 
                    showModal: false, 
                    description: '', 
                    loading: false,
                    async submit() {
                        this.loading = true;
                        try {
                            let res = await fetch('/customer/orders/{{ $order->id }}/addon', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ description: this.description })
                            });
                            let data = await res.json();
                            if(data.success) {
                                this.showModal = false;
                                this.description = '';
                                $wire.$refresh();
                            } else {
                                alert(data.message || 'Gagal mengirim permintaan.');
                            }
                        } catch(e) { alert('Terjadi kesalahan jaringan.'); }
                        this.loading = false;
                    }
                }" class="w-full sm:w-auto order-last sm:order-first">
                    <button @click="showModal = true" type="button" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-sm font-medium border-2 border-primary-100 text-primary-700 bg-primary-50 hover:bg-primary-100 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Minta Tambahan Item
                    </button>

                    <template x-teleport="body">
                        <div x-show="showModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" x-transition.opacity>
                            <div @click.away="showModal = false" x-show="showModal" x-transition.scale.origin.bottom class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 overflow-hidden">
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Minta Tambahan Item</h3>
                                <p class="text-sm text-gray-500 mb-4">Ada yang terlupa? Tuliskan detail barang tambahan yang ingin Anda titip.</p>
                                
                                <textarea x-model="description" class="w-full border-gray-200 rounded-xl p-3 text-sm focus:ring-primary-500 focus:border-primary-500 mb-4 bg-gray-50 resize-none" rows="4" placeholder="Contoh: Tolong sekalian belikan 1 botol air mineral ukuran besar ya..."></textarea>
                                
                                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-2">
                                    <button @click="showModal = false" type="button" class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">Batal</button>
                                    <button @click="submit" :disabled="loading || !description.trim()" class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-xl hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center">
                                        <span x-show="!loading">Kirim Permintaan</span>
                                        <span x-show="loading" class="flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Mengirim...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endif
            @if(in_array($order->status, ['diproses', 'barang_diambil', 'sedang_diantar']))
                <button type="button" onclick="cancelOrder('{{ $order->id }}')" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-sm font-medium border border-red-200 text-red-600 hover:bg-red-50 bg-white transition-colors text-center">Batalkan Order</button>
            @endif
            @if($order->status === 'tiba_tujuan')
                <a href="/customer/orders/{{ $order->id }}/report" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-sm font-medium border border-yellow-500 text-yellow-600 hover:bg-yellow-50 bg-white transition-colors text-center order-2 sm:order-none">Barang Tidak Sesuai</a>
                <form action="{{ route('customer.orders.confirmation', $order->id) }}" method="POST" class="w-full sm:w-auto inline-flex order-1 sm:order-none">
                    @csrf
                    <button type="submit" class="w-full px-5 py-2.5 rounded-xl text-sm font-bold bg-green-500 text-white hover:bg-green-600 transition-colors shadow-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Konfirmasi Diterima
                    </button>
                </form>
            @endif
        @endif
    </div>
</div>