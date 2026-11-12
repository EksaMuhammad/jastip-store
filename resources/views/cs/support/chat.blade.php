@extends('layouts.cs')

@section('title', 'Live Chat Bantuan CS - JastipKuy')
@section('workspace-overflow', 'overflow-hidden')

@section('content')
<div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden"
     x-data="csSupportChat({
         initialCustomerId: {{ $selectedCustomerId ? $selectedCustomerId : 'null' }},
         initialTab: '{{ $filter }}',
         fetchConversationsUrl: '{{ route('cs.chats.conversations') }}',
         messagesBaseUrl: '{{ url('/cs/chats') }}',
         csrfToken: '{{ csrf_token() }}',
         csId: {{ $cs->id }}
     })"
     x-init="initDesk()">

    <!-- Top CS Desk Nav Bar (Matches Admin Header) -->
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 shrink-0 z-30 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-xs">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            </div>
            <div>
                <h1 class="font-extrabold text-sm text-slate-900">Live Support Desk CS</h1>
                <span class="text-[10px] text-slate-500 block -mt-0.5">Penanganan Chat Bantuan Pelanggan Real-Time</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Queue status badge -->
            <template x-if="unassignedCount > 0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span x-text="unassignedCount + ' Antrean Belum Diklaim'"></span>
                </span>
            </template>
            <template x-if="unassignedCount === 0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Semua Terlayani
                </span>
            </template>
        </div>
    </header>

    <!-- Main Workspace (3 Columns) -->
    <div class="flex-1 flex min-h-0 h-full bg-slate-100 overflow-hidden">

        <!-- COLUMN 1: Conversation Tabs & List -->
        <div class="w-80 bg-white border-r border-slate-200 flex flex-col shrink-0 min-h-0 h-full">
            <!-- Tabs: Chat Saya vs Antrean Bebas -->
            <div class="p-2 border-b border-slate-100 bg-slate-50 flex gap-1">
                <button type="button" @click="setTab('my')"
                        class="flex-1 py-1.5 px-2 rounded-lg text-xs font-bold transition text-center cursor-pointer"
                        :class="activeTab === 'my' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200' : 'text-slate-500 hover:text-slate-800'">
                    Chat Saya
                </button>
                <button type="button" @click="setTab('queue')"
                        class="flex-1 py-1.5 px-2 rounded-lg text-xs font-bold transition text-center relative cursor-pointer"
                        :class="activeTab === 'queue' ? 'bg-white text-amber-800 shadow-2xs border border-slate-200' : 'text-slate-500 hover:text-slate-800'">
                    Antrean Bebas
                    <template x-if="unassignedCount > 0">
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[9px] bg-amber-500 text-white font-extrabold" x-text="unassignedCount"></span>
                    </template>
                </button>
                <button type="button" @click="setTab('closed')"
                        class="py-1.5 px-2.5 rounded-lg text-xs font-bold transition text-center cursor-pointer"
                        :class="activeTab === 'closed' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200' : 'text-slate-400 hover:text-slate-600'">
                    Selesai
                </button>
            </div>

            <!-- Search Filter -->
            <div class="p-3 border-b border-slate-100">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text"
                           x-model="searchQuery"
                           @input.debounce.300ms="filterConversations()"
                           placeholder="Cari nama atau no HP..."
                           class="w-full text-xs rounded-xl pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                </div>
            </div>

            <!-- Conversation Items -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                <template x-for="conv in conversations" :key="conv.customer_id">
                    <div @click="selectCustomer(conv.customer_id)"
                         class="p-3.5 flex items-start gap-3 cursor-pointer transition select-none"
                         :class="activeCustomerId == conv.customer_id ? 'bg-slate-100/90 border-r-4 border-slate-900' : 'hover:bg-slate-50'">

                        <div class="relative shrink-0">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-xs shadow-2xs"
                                 :class="conv.is_unassigned ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-900 text-white'">
                                <span x-text="conv.customer_avatar"></span>
                            </div>
                            <template x-if="conv.is_unassigned">
                                <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full border border-white"></span>
                            </template>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-1 mb-0.5">
                                <h4 class="font-extrabold text-xs text-slate-900 truncate" x-text="conv.customer_name"></h4>
                                <span class="text-[9px] font-mono text-slate-400 shrink-0" x-text="conv.latest_raw_time"></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] text-slate-500 font-mono" x-text="conv.customer_phone"></span>
                                <template x-if="conv.is_unassigned">
                                    <span class="px-1.5 py-0.2 rounded text-[8px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wider">Antrean</span>
                                </template>
                            </div>
                            <p class="text-[11px] truncate mt-1"
                               :class="conv.unread_count > 0 ? 'font-bold text-slate-900' : 'text-slate-500 font-normal'">
                                <span x-show="conv.last_sender === 'admin'" class="text-slate-700 font-bold">CS: </span>
                                <span x-text="conv.latest_message || '(Belum ada pesan)'"></span>
                            </p>
                        </div>

                        <template x-if="conv.unread_count > 0">
                            <span class="shrink-0 bg-slate-900 text-white font-bold text-[9px] px-1.5 py-0.5 rounded-full"
                                  x-text="conv.unread_count"></span>
                        </template>
                    </div>
                </template>

                <div x-show="conversations.length === 0" x-cloak class="text-center py-12 text-slate-400 px-4">
                    <p class="text-xs font-bold text-slate-700">Tidak ada percakapan</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Tidak ditemukan obrolan di tab ini.</p>
                </div>
            </div>
        </div>

        <!-- COLUMN 2: Chat Stream & Reply Box -->
        <div class="flex-1 flex flex-col min-w-0 min-h-0 h-full bg-[#F3F4F6] overflow-hidden">

            <!-- Active Header -->
            <template x-if="activeCustomer">
                <div class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                            <span x-text="activeCustomer.avatar"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-sm text-slate-900 truncate" x-text="activeCustomer.name"></h3>
                                <template x-if="activeConversation && activeConversation.status === 'menunggu'">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Antrean Bebas</span>
                                </template>
                                <template x-if="activeConversation && activeConversation.status === 'aktif'">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Sesi Aktif</span>
                                </template>
                            </div>
                            <span x-text="activeCustomer.phone" class="font-mono text-[10px] text-slate-500"></span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <!-- Claim Button if unassigned -->
                        <template x-if="activeConversation && (activeConversation.status === 'menunggu' || !activeConversation.is_my_chat)">
                            <form :action="'/cs/chats/' + activeCustomerId + '/claim'" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                                    Klaim Chat Ini
                                </button>
                            </form>
                        </template>

                        <!-- Close Conversation Button if handling -->
                        <template x-if="activeConversation && activeConversation.status === 'aktif' && activeConversation.is_my_chat">
                            <form :action="'/cs/chats/' + activeCustomerId + '/close'" method="POST" class="inline"
                                  onsubmit="return confirm('Tandai sesi obrolan dengan customer ini telah selesai?')">
                                @csrf
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold border border-slate-200 transition shadow-2xs cursor-pointer">
                                    Selesaikan Sesi
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Chat Stream Scrollable Area -->
            <div class="flex-1 p-4 lg:p-6 overflow-y-auto min-h-0 space-y-4" x-ref="chatStream">
                <template x-if="!activeCustomerId">
                    <div class="h-full flex flex-col items-center justify-center text-center text-slate-400 py-16">
                        <div class="w-12 h-12 bg-white rounded-xl border border-slate-200 flex items-center justify-center text-slate-400 mb-3 shadow-2xs">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        </div>
                        <h4 class="font-extrabold text-slate-700 text-sm">Pilih Chat Pelanggan</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm">Pilih percakapan di kolom kiri untuk membaca riwayat dan membalas pertanyaan pelanggan.</p>
                    </div>
                </template>

                <!-- Messages -->
                <template x-for="msg in messages" :key="msg.id">
                    <div class="flex flex-col" :class="msg.is_staff ? 'items-end' : 'items-start'">
                        <div class="max-w-[75%] rounded-2xl p-3.5 shadow-2xs space-y-1.5"
                             :class="msg.is_staff ? 'bg-slate-900 text-white rounded-tr-xs' : 'bg-white text-slate-800 border border-slate-200 rounded-tl-xs'">

                            <div class="flex items-center justify-between gap-4 text-[10px]"
                                 :class="msg.is_staff ? 'text-slate-300' : 'text-slate-400 font-bold'">
                                <span class="font-bold" x-text="msg.is_staff ? msg.sender_name + ' (CS)' : msg.sender_name"></span>
                                <span x-text="msg.time" class="font-mono text-[9px]"></span>
                            </div>

                            <!-- Order Reference -->
                            <template x-if="msg.order_id">
                                <div class="p-2 rounded-xl text-[10px] font-bold"
                                     :class="msg.is_staff ? 'bg-slate-800 text-slate-200 border border-slate-700' : 'bg-slate-50 text-slate-700 border border-slate-200'">
                                    <span>Pesanan #<span x-text="msg.order_id"></span>: <span x-text="msg.order_desc || ''"></span></span>
                                </div>
                            </template>

                            <p class="text-xs leading-relaxed whitespace-pre-wrap" x-text="msg.message"></p>

                            <template x-if="msg.attachment_url">
                                <div class="pt-1.5">
                                    <a :href="msg.attachment_url" target="_blank" class="block rounded-xl overflow-hidden border border-black/10">
                                        <img :src="msg.attachment_url" alt="Lampiran" class="max-h-56 w-full object-cover" @load="scrollToBottom()">
                                    </a>
                                </div>
                            </template>
                        </div>
                        <div class="mt-0.5 px-1 text-[9px] text-slate-400 font-mono" x-text="msg.date"></div>
                    </div>
                </template>
            </div>

            <!-- Reply Input Box -->
            <template x-if="activeCustomerId">
                <div class="p-4 bg-white border-t border-slate-200 shrink-0">
                    <!-- Quick canned templates -->
                    <div class="flex items-center gap-1.5 mb-2 overflow-x-auto pb-1 text-[11px]">
                        <span class="text-slate-400 font-semibold shrink-0">Template:</span>
                        <button type="button" @click="insertTemplate('Halo, terima kasih telah menghubungi CS JastipKuy. Ada yang bisa saya bantu hari ini?')"
                                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium shrink-0 cursor-pointer">
                            Sapaan Pembuka
                        </button>
                        <button type="button" @click="insertTemplate('Mohon tunggu sebentar ya kak, saya sedang mengecek status pesanan kakak ke sistem terkait.')"
                                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium shrink-0 cursor-pointer">
                            Cek Pesanan
                        </button>
                        <button type="button" @click="insertTemplate('Baik, kendala sudah kami tangani. Apakah ada hal lain yang bisa saya bantu? Terima kasih telah menggunakan JastipKuy!')"
                                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium shrink-0 cursor-pointer">
                            Penutup
                        </button>
                    </div>

                    <form @submit.prevent="sendMessage()" class="flex items-end gap-2">
                        <!-- Attachment Upload -->
                        <label class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-slate-500 transition shrink-0">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            <input type="file" @change="handleFileChange($event)" class="hidden" accept="image/*,.heic,.heif">
                        </label>

                        <!-- Input field -->
                        <div class="flex-1 min-w-0">
                            <template x-if="attachmentFile">
                                <div class="mb-2 p-1.5 px-3 rounded-lg bg-slate-100 border border-slate-200 text-[11px] text-slate-700 flex items-center justify-between">
                                    <span class="truncate">Lampiran: <strong x-text="attachmentFile.name"></strong></span>
                                    <button type="button" @click="attachmentFile = null" class="text-rose-600 font-bold ml-2 cursor-pointer">Batal</button>
                                </div>
                            </template>
                            <textarea 
                                x-model="replyText" 
                                @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                                rows="2" 
                                placeholder="Ketik balasan CS ke pelanggan... (Enter untuk kirim, Shift+Enter untuk baris baru)"
                                class="w-full text-xs rounded-xl p-3 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 resize-none bg-slate-50"
                            ></textarea>
                        </div>

                        <!-- Send Button -->
                        <button type="submit" 
                                :disabled="isSending || (!replyText.trim() && !attachmentFile)"
                                class="px-5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs shadow-xs transition shrink-0 flex items-center gap-1.5 cursor-pointer">
                            <span x-show="!isSending">Kirim</span>
                            <span x-show="isSending">Mengirim...</span>
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </form>
                </div>
            </template>
        </div>

        <!-- COLUMN 3: Customer Info & Orders Sidebar -->
        <template x-if="activeCustomer">
            <div class="w-72 bg-white border-l border-slate-200 p-4 hidden xl:flex flex-col min-h-0 h-full overflow-y-auto space-y-4 shrink-0">
                <div>
                    <h4 class="font-extrabold text-[11px] text-slate-700 uppercase tracking-wider mb-2.5">Informasi Pelanggan</h4>
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Nama:</span>
                            <strong class="text-slate-800 truncate ml-2" x-text="activeCustomer.name"></strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Telepon:</span>
                            <span class="font-mono text-slate-700" x-text="activeCustomer.phone"></span>
                        </div>
                        <div class="flex justify-between items-center pt-1 border-t border-slate-200/80">
                            <span class="text-slate-500">Saldo Dompet:</span>
                            <strong class="text-emerald-700 font-mono" x-text="activeCustomer.wallet_formatted || 'Rp 0'"></strong>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div>
                    <h4 class="font-extrabold text-[11px] text-slate-700 uppercase tracking-wider mb-2.5">Pesanan Terakhir</h4>
                    <div class="space-y-2">
                        <template x-for="ord in customerOrders" :key="ord.id">
                            <div class="p-3 rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition text-xs space-y-1 shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-900" x-text="'#Order ' + ord.id"></span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase"
                                          :class="ord.status === 'selesai' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                                          x-text="ord.status"></span>
                                </div>
                                <p class="text-[11px] text-slate-600 truncate" x-text="ord.description"></p>
                                <div class="flex justify-between text-[10px] text-slate-400 font-mono pt-1">
                                    <span x-text="ord.fare_formatted"></span>
                                    <span x-text="ord.date"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="!customerOrders || customerOrders.length === 0" class="text-xs text-slate-400">
                            Tidak ada riwayat pesanan.
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@php
    $activeCustomerData = $selectedCustomer ? [
        'id' => $selectedCustomer->id,
        'name' => $selectedCustomer->name,
        'phone' => $selectedCustomer->phone_number,
        'avatar' => strtoupper(substr($selectedCustomer->name, 0, 2)),
        'wallet_formatted' => 'Rp ' . number_format($selectedCustomer->wallet?->balance ?? 0, 0, ',', '.')
    ] : null;

    $activeConversationData = $selectedConversation ? [
        'status' => $selectedConversation->status,
        'assigned_cs_id' => $selectedConversation->assigned_cs_id,
        'is_my_chat' => $selectedConversation->assigned_cs_id === $cs->id,
    ] : null;

    $formattedCustomerOrders = isset($customerOrders) ? $customerOrders->map(function ($ord) {
        return [
            'id' => $ord->id,
            'status' => $ord->status,
            'description' => $ord->description,
            'fare_formatted' => 'Rp ' . number_format($ord->agreed_fare, 0, ',', '.'),
            'date' => $ord->created_at->format('d/m/y'),
        ];
    })->values() : [];
@endphp

@section('scripts')
<script>
function csSupportChat(config) {
    return {
        activeCustomerId: config.initialCustomerId,
        activeTab: config.initialTab || 'my',
        conversations: @json($conversations ?? []),
        messages: @json($messages ?? []),
        customerOrders: @json($formattedCustomerOrders ?? []),
        activeCustomer: @json($activeCustomerData),
        activeConversation: @json($activeConversationData),
        unassignedCount: {{ (int) ($unassignedQueueCount ?? 0) }},
        searchQuery: '',
        replyText: '',
        attachmentFile: null,
        isSending: false,
        pollInterval: null,

        initDesk() {
            this.scrollToBottom();
            if (this.activeCustomerId) {
                this.fetchMessages(true);
            }
            this.startPolling();
        },

        setTab(tab) {
            this.activeTab = tab;
            this.fetchConversations();
        },

        filterConversations() {
            this.fetchConversations();
        },

        selectCustomer(customerId) {
            this.activeCustomerId = customerId;
            const conv = this.conversations.find(c => c.customer_id === customerId);
            if (conv) {
                this.activeCustomer = {
                    id: conv.customer_id,
                    name: conv.customer_name,
                    phone: conv.customer_phone,
                    avatar: conv.customer_avatar,
                    wallet_formatted: (this.activeCustomer && this.activeCustomer.id === customerId) ? this.activeCustomer.wallet_formatted : 'Memuat...'
                };
            }
            this.fetchMessages(true);
            this.scrollToBottom();
        },

        insertTemplate(text) {
            this.replyText = (this.replyText ? this.replyText + ' ' : '') + text;
        },

        handleFileChange(event) {
            if (event.target.files.length > 0) {
                this.attachmentFile = event.target.files[0];
            }
        },

        fetchConversations() {
            const url = `${config.fetchConversationsUrl}?tab=${this.activeTab}&search=${encodeURIComponent(this.searchQuery)}`;
            fetch(url, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.conversations = data.conversations;
                        this.unassignedCount = data.unassigned_count;
                    }
                })
                .catch(err => console.error('Error fetching conversations:', err));
        },

        fetchMessages(forceScroll = false) {
            if (!this.activeCustomerId) return;
            const url = `${config.messagesBaseUrl}/${this.activeCustomerId}/messages`;
            fetch(url, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const previousCount = this.messages.length;
                        this.messages = data.messages;
                        if (data.conversation) {
                            this.activeConversation = data.conversation;
                        }
                        if (data.customer) {
                            this.activeCustomer = data.customer;
                        }
                        if (data.orders) {
                            this.customerOrders = data.orders;
                        }
                        if (forceScroll || previousCount === 0 || this.messages.length > previousCount) {
                            this.scrollToBottom(previousCount > 0 && !forceScroll);
                        }
                    }
                })
                .catch(err => console.error('Error fetching messages:', err));
        },

        sendMessage() {
            if (!this.activeCustomerId || this.isSending) return;
            if (!this.replyText.trim() && !this.attachmentFile) return;

            this.isSending = true;
            const formData = new FormData();
            formData.append('_token', config.csrfToken);
            if (this.replyText.trim()) {
                formData.append('message', this.replyText.trim());
            }
            if (this.attachmentFile) {
                formData.append('attachment', this.attachmentFile);
            }

            const url = `${config.messagesBaseUrl}/${this.activeCustomerId}/send`;
            fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                this.isSending = false;
                if (data.success) {
                    this.messages.push(data.message);
                    this.replyText = '';
                    this.attachmentFile = null;
                    this.scrollToBottom(true);
                    this.fetchConversations();
                } else {
                    alert(data.message || 'Gagal mengirim pesan.');
                }
            })
            .catch(err => {
                this.isSending = false;
                console.error('Error sending message:', err);
                alert('Terjadi kesalahan koneksi.');
            });
        },

        scrollToBottom(smooth = false) {
            const doScroll = () => {
                const stream = this.$refs.chatStream;
                if (stream) {
                    if (smooth) {
                        stream.scrollTo({ top: stream.scrollHeight, behavior: 'smooth' });
                    } else {
                        stream.scrollTop = stream.scrollHeight;
                    }
                }
            };

            this.$nextTick(doScroll);
            if (typeof requestAnimationFrame !== 'undefined') {
                requestAnimationFrame(doScroll);
            }
            setTimeout(doScroll, 80);
            setTimeout(doScroll, 250);
        },

        startPolling() {
            this.pollInterval = setInterval(() => {
                this.fetchConversations();
                if (this.activeCustomerId) {
                    this.fetchMessages();
                }
            }, 3500);
        }
    };
}
</script>
@endsection
