<?php

use Livewire\Volt\Component;
use App\Models\Order;
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

<div class="p-4 bg-white rounded-xl border border-gray-100 shadow-sm mb-4">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h3 class="font-semibold text-gray-900">{{ $order->description }}</h3>
            <p class="text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</p>
        </div>
        <div class="text-right">
            <div class="font-bold text-gray-900">Rp {{ number_format($order->agreed_fare, 0, ',', '.') }}</div>
        </div>
    </div>
    
    <!-- Status Progress -->
    <div class="mb-6">
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
        
        <div class="flex items-center justify-between relative">
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 rounded-full z-0"></div>
            @foreach($statuses as $key => $label)
                @php
                    $stepIndex = array_search($key, array_keys($statuses));
                    $isActive = $stepIndex <= $currentIndex;
                    $isCurrent = $stepIndex === $currentIndex;
                @endphp
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-4 h-4 rounded-full {{ $isActive ? 'bg-primary-600' : 'bg-gray-300' }} {{ $isCurrent ? 'ring-4 ring-primary-100' : '' }}"></div>
                    <span class="text-xs mt-1 {{ $isActive ? 'text-primary-600 font-medium' : 'text-gray-400' }} hidden sm:block">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Actions -->
    <div class="flex gap-2 justify-end">
        @if($role === 'jastiper')
            @if($order->status === 'diproses')
                <button wire:click="updateStatus('barang_diambil')" class="btn btn-primary px-4 py-2 rounded-lg text-sm font-medium">Konfirmasi Barang Diambil</button>
            @elseif($order->status === 'barang_diambil')
                <button wire:click="updateStatus('sedang_diantar')" class="btn btn-primary px-4 py-2 rounded-lg text-sm font-medium">Mulai Mengantar</button>
            @elseif($order->status === 'sedang_diantar')
                <button wire:click="updateStatus('tiba_tujuan')" class="btn btn-primary px-4 py-2 rounded-lg text-sm font-medium">Konfirmasi Tiba di Tujuan</button>
            @endif
        @elseif($role === 'customer')
            @if(in_array($order->status, ['diproses', 'barang_diambil', 'sedang_diantar']))
                <button type="button" onclick="cancelOrder('{{ $order->id }}')" class="btn btn-danger px-4 py-2 rounded-lg text-sm font-medium border border-red-500 text-red-600 hover:bg-red-50">Batalkan Order</button>
            @endif
            @if($order->status === 'tiba_tujuan')
                <form action="{{ route('customer.orders.confirmation', $order->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-success px-4 py-2 rounded-lg text-sm font-medium bg-green-500 text-white hover:bg-green-600">Konfirmasi Diterima</button>
                </form>
                <a href="/customer/orders/{{ $order->id }}/report" class="btn btn-warning px-4 py-2 rounded-lg text-sm font-medium border border-yellow-500 text-yellow-600 hover:bg-yellow-50">Barang Tidak Sesuai</a>
            @endif
        @endif
    </div>
</div>