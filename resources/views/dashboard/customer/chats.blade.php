@extends('layouts.support')

@section('title', 'Chat Saya')

@section('content')
<div class="min-h-screen bg-white pb-16">
    
    <!-- Top Header (Gojek Style) -->
    <div class="bg-white border-b border-slate-200 sticky top-16 sm:static z-40 sm:z-auto px-4 py-3.5 shadow-sm sm:shadow-none">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <h2 class="font-display font-black text-lg text-slate-900 leading-none">Chat</h2>
            <button type="button" 
                    onclick="markAllCustomerChatsRead()" 
                    class="text-[11px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-full transition flex items-center gap-1.5 shadow-2xs cursor-pointer"
                    title="Tandai semua obrolan sebagai dibaca">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7m-7 6l2 2 4-4"></path>
                </svg>
                <span>Tandai Semua Dibaca</span>
            </button>
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
                    </div>
                    <span class="text-[10px] font-bold text-slate-700">Inbox</span>
                </div>

                <!-- Bantuan (Live Chat Admin Support) -->
                @php
                    $unreadSupportCount = \App\Models\SupportMessage::where('customer_id', auth('customer')->id())
                        ->where('sender_type', 'admin')
                        ->where('is_read', false)
                        ->count();
                @endphp
                <a href="{{ route('customer.support.chat') }}" class="flex flex-col items-center gap-1 cursor-pointer group">
                    <div class="w-12 h-12 bg-emerald-600 group-hover:bg-emerald-700 rounded-full flex items-center justify-center text-white relative shadow-md shadow-emerald-500/20 transition transform group-hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        @if($unreadSupportCount > 0)
                            <span class="absolute -top-1 -right-1 bg-rose-600 text-[8px] font-black text-white px-1.5 py-0.5 rounded-full border border-white animate-pulse">
                                {{ $unreadSupportCount }}
                            </span>
                        @endif
                    </div>
                    <span class="text-[10px] font-bold text-slate-700 group-hover:text-emerald-700 transition">Bantuan CS</span>
                </a>
            </div>
        </div>

        <!-- Chat List (Chat kamu) -->
        <div>
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider mb-3">Chat kamu</h3>

            <!-- Pinned Customer Support Chat Item -->
            @php
                $latestSupportMsg = \App\Models\SupportMessage::where('customer_id', auth('customer')->id())->latest()->first();
            @endphp
            <a href="{{ route('customer.support.chat') }}" class="p-3.5 mb-3 flex items-center justify-between gap-3 bg-gradient-to-r from-emerald-50/80 to-teal-50/40 border border-emerald-200/90 rounded-2xl hover:bg-emerald-50 transition shadow-2xs block">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="relative shrink-0">
                        <div class="w-11 h-11 bg-emerald-600 text-white rounded-full flex items-center justify-center font-display font-black text-lg shadow-sm">
                            🛡️
                        </div>
                        <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-400 rounded-full border-2 border-white flex items-center justify-center">
                            <span class="w-1 h-1 bg-white rounded-full"></span>
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <h4 class="font-extrabold text-slate-900 text-xs truncate">Admin Customer Support</h4>
                                <span class="text-[8px] font-black uppercase px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800">Bantuan</span>
                            </div>
                            <span class="text-[9px] font-bold text-slate-400 shrink-0 font-mono">
                                {{ $latestSupportMsg ? $latestSupportMsg->created_at->format('H:i') : 'Online' }}
                            </span>
                        </div>
                        <p class="text-[10px] text-emerald-700 font-bold truncate mt-0.5">Pusat Bantuan, Kendala & Komplain</p>
                        <p class="text-[11px] text-slate-600 font-medium truncate mt-0.5">
                            {{ $latestSupportMsg ? ($latestSupportMsg->sender_type === 'admin' ? 'Admin: ' : 'Anda: ') . $latestSupportMsg->message : 'Ada kendala pesanan atau pertanyaan? Chat admin kami di sini...' }}
                        </p>
                    </div>
                </div>
                <div id="cs-support-unread-container" class="shrink-0">
                    @if($unreadSupportCount > 0)
                        <span id="cs-support-unread-badge" class="bg-rose-600 text-[9px] font-extrabold text-white w-5 h-5 rounded-full flex items-center justify-center shadow-sm">
                            {{ $unreadSupportCount }}
                        </span>
                    @else
                        <span class="text-slate-400 text-xs">›</span>
                    @endif
                </div>
            </a>

            @if($conversations->isEmpty())
                <div class="bg-slate-50 border border-slate-200 rounded-3xl p-8 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">💬</div>
                    <h4 class="font-bold text-slate-700 text-sm">Belum ada obrolan</h4>
                    <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto leading-normal">Setelah Anda memesan jastip dan dialokasikan ke jastiper, Anda bisa mengirim pesan di sini.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($conversations as $conv)
                        <div id="chat-item-{{ $conv['order_id'] }}"
                             class="py-3.5 flex items-center justify-between gap-3 cursor-pointer hover:bg-slate-50 rounded-2xl px-2 -mx-2 transition"
                             onclick="openCustomerOrderChat({{ $conv['order_id'] }}, '{{ addslashes($conv['order_description']) }}')">
                            
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
                            <div id="unread-container-{{ $conv['order_id'] }}" class="order-unread-container shrink-0">
                                @if($conv['unread_count'] > 0)
                                    <span class="bg-rose-600 text-[9px] font-extrabold text-white w-5 h-5 rounded-full flex items-center justify-center shadow-sm shadow-rose-600/20">
                                        {{ $conv['unread_count'] }}
                                    </span>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>

<script>
    function openCustomerOrderChat(orderId, orderLabel) {
        // Hapus seketika badge unread order ini dari DOM
        const container = document.getElementById('unread-container-' + orderId);
        if (container) {
            container.innerHTML = '';
        }
        window.dispatchEvent(new CustomEvent('open-chat', {
            detail: { orderId: orderId, orderLabel: orderLabel }
        }));
    }

    async function markAllCustomerChatsRead() {
        try {
            const res = await fetch("{{ route('customer.chats.mark-all-read') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                // Bersihkan semua badge di daftar order
                document.querySelectorAll('.order-unread-container').forEach(el => el.innerHTML = '');

                // Bersihkan badge top circle icon Bantuan CS jika ada
                const topCsBadge = document.querySelector('a[href="{{ route('customer.support.chat') }}"] .animate-pulse');
                if (topCsBadge) topCsBadge.remove();

                // Bersihkan badge pinned bar bantuan
                const pinnedContainer = document.getElementById('cs-support-unread-container');
                if (pinnedContainer) {
                    pinnedContainer.innerHTML = '<span class="text-slate-400 text-xs">›</span>';
                }

                // Bersihkan badge merah pada lonceng notifikasi header jika ada
                const headerBellBadge = document.querySelector('#btn-customer-notifications span.bg-rose-600');
                if (headerBellBadge) {
                    headerBellBadge.remove();
                }

                // Munculkan toast notifikasi
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-20 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-full shadow-lg z-50 flex items-center gap-2';
                toast.innerHTML = '<span>✓</span><span>Semua pesan telah ditandai sebagai dibaca</span>';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2500);
            }
        } catch (e) {
            console.error('Gagal menandai chat terbaca:', e);
        }
    }

    // Dengarkan jika modal chat membaca riwayat di latar
    window.addEventListener('chat-read', function(e) {
        if (e.detail && e.detail.orderId) {
            const container = document.getElementById('unread-container-' + e.detail.orderId);
            if (container) container.innerHTML = '';
        }
    });
</script>

@include('components.chat.order-chat-modal', [
    'viewerRole' => 'customer',
    'chatSendUrlTemplate' => route('customer.orders.chat.send', ['id' => '__ID__']),
    'chatHistoryUrlTemplate' => route('customer.orders.chat.history', ['id' => '__ID__']),
])
@endsection
