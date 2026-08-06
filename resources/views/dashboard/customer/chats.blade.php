@extends('layouts.support')

@section('title', 'Chat Saya')

@section('content')
<div class="min-h-screen bg-white pb-16">
    
    <!-- Top Header (Gojek Style) -->
    <div class="bg-white border-b border-slate-200 sticky top-20 z-40 px-4 py-3.5 shadow-sm">
        <div class="max-w-md mx-auto">
            <h2 class="font-display font-black text-lg text-slate-900 leading-none">Chat</h2>
        </div>
    </div>

    <!-- Main Container -->
    <div class="max-w-md mx-auto px-4 mt-4">
        
        <!-- Pilihan Fitur (Top Circle Icons) -->
        <div class="mb-6">
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider mb-3">Pilihan fitur</h3>
            <div class="flex gap-4">
                <!-- Inbox -->
                <div class="flex flex-col items-center gap-1 cursor-pointer" onclick="showMaintenanceToast(event)">
                    <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white relative shadow-md shadow-orange-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span class="absolute -top-1 -right-1 bg-rose-600 text-[8px] font-black text-white px-1.5 py-0.5 rounded-full border border-white">1</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-700">Inbox</span>
                </div>

                <!-- Bantuan -->
                <div class="flex flex-col items-center gap-1 cursor-pointer" onclick="showMaintenanceToast(event)">
                    <div class="w-12 h-12 bg-emerald-600 rounded-full flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-700">Bantuan</span>
                </div>
            </div>
        </div>

        <!-- Chat List (Chat kamu) -->
        <div>
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider mb-3">Chat kamu</h3>

            @if($conversations->isEmpty())
                <div class="bg-slate-50 border border-slate-200 rounded-3xl p-8 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">💬</div>
                    <h4 class="font-bold text-slate-700 text-sm">Belum ada obrolan</h4>
                    <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto leading-normal">Setelah Anda memesan jastip dan dialokasikan ke jastiper, Anda bisa mengirim pesan di sini.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($conversations as $conv)
                        <div class="py-3.5 flex items-center justify-between gap-3 cursor-pointer hover:bg-slate-50 rounded-2xl px-2 -mx-2 transition"
                             onclick="window.dispatchEvent(new CustomEvent('open-chat', { detail: { orderId: {{ $conv['order_id'] }}, orderLabel: '{{ addslashes($conv['order_description']) }}' } }))">
                            
                            <!-- Left section: Avatar & Message info -->
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="relative shrink-0">
                                    <!-- Avatar -->
                                    <div class="w-11 h-11 bg-slate-900 text-rose-500 rounded-full flex items-center justify-center font-display font-black text-sm border border-slate-800 shadow-sm">
                                        {{ strtoupper(substr($conv['jastiper_name'], 0, 2)) }}
                                    </div>
                                    <!-- Online badge/checkmark indicator -->
                                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white flex items-center justify-center">
                                        <span class="w-1 h-1 bg-white rounded-full"></span>
                                    </span>
                                </div>
                                
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <h4 class="font-extrabold text-slate-800 text-xs truncate">{{ $conv['jastiper_name'] }}</h4>
                                        <span class="text-[9px] font-bold text-slate-400 shrink-0 font-mono">{{ $conv['latest_message_time'] }}</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-bold truncate mt-0.5">{{ $conv['order_description'] }}</p>
                                    <p class="text-[11px] text-slate-600 font-medium truncate mt-0.5">{{ $conv['latest_message'] }}</p>
                                </div>
                            </div>
                            
                            <!-- Right section: Unread Badge -->
                            @if($conv['unread_count'] > 0)
                                <span class="shrink-0 bg-rose-600 text-[9px] font-extrabold text-white w-5 h-5 rounded-full flex items-center justify-center shadow-sm shadow-rose-600/20">
                                    {{ $conv['unread_count'] }}
                                </span>
                            @endif

                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>

@include('components.chat.order-chat-modal', [
    'viewerRole' => 'customer',
    'chatSendUrlTemplate' => route('customer.orders.chat.send', ['id' => '__ID__']),
    'chatHistoryUrlTemplate' => route('customer.orders.chat.history', ['id' => '__ID__']),
])
@endsection
