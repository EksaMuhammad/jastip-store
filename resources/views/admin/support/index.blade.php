@extends('layouts.support')

@section('title', 'Panel Admin - Live Chat Bantuan & Customer Support')

@section('content')
<!-- Full-viewport Admin Wrapper -->
<div class="flex min-h-screen bg-[#F3F4F6]"
     x-data="adminSupportChat({
         initialCustomerId: {{ $selectedCustomerId ? $selectedCustomerId : 'null' }},
         fetchConversationsUrl: '{{ route('admin.support.conversations') }}',
         messagesBaseUrl: '{{ url('/admin/support-chats') }}',
         csrfToken: '{{ csrf_token() }}'
     })"
     x-init="initDashboard()">

    <!-- UNIFIED LEFT SIDEBAR -->
    @include('layouts.admin-sidebar')

    <!-- RIGHT CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">

        <!-- Top Nav Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 lg:px-8 shrink-0 z-40">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-slate-600 hover:text-slate-900 transition" onclick="toggleMobileSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Live Support Desk
                    </span>
                    <span class="text-xs font-bold text-slate-500 hidden sm:inline">• Bantuan & Komplain Pelanggan</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-medium">Pesan Belum Dibaca:</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-900 text-white" x-text="totalUnread">
                    {{ $totalUnreadAll }}
                </span>
            </div>
        </header>

        <!-- Main Workspace (3-Column Layout) -->
        <div class="flex-1 flex min-h-0 bg-slate-100">

            <!-- COLUMN 1: Conversation Thread List (Width: w-80) -->
            <div class="w-80 bg-white border-r border-slate-200 flex flex-col shrink-0 min-h-0">
                <!-- Search Box -->
                <div class="p-3.5 border-b border-slate-100">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.300ms="filterConversations()"
                               placeholder="Cari nama atau nomor telepon..."
                               class="w-full text-xs rounded-xl pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>
                </div>

                <!-- Conversation List Scrollable -->
                <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    <template x-for="conv in conversations" :key="conv.customer_id">
                        <div @click="selectCustomer(conv.customer_id)"
                             class="p-3.5 flex items-start gap-3 cursor-pointer transition select-none"
                             :class="activeCustomerId == conv.customer_id ? 'bg-slate-100/90 border-r-4 border-slate-900' : 'hover:bg-slate-50'">

                            <!-- Customer Initials Avatar -->
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-xs shadow-xs"
                                     :class="activeCustomerId == conv.customer_id ? 'bg-slate-800 text-white' : 'bg-slate-900 text-white'">
                                    <span x-text="conv.customer_avatar"></span>
                                </div>
                                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white"></span>
                            </div>

                            <!-- Content snippet -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-1 mb-0.5">
                                    <h4 class="font-extrabold text-xs text-slate-900 truncate" x-text="conv.customer_name"></h4>
                                    <span class="text-[9px] font-mono text-slate-400 shrink-0" x-text="conv.latest_raw_time"></span>
                                </div>
                                <p class="text-[10px] text-slate-500 font-mono truncate" x-text="conv.customer_phone"></p>
                                <p class="text-[11px] truncate mt-1"
                                   :class="conv.unread_count > 0 ? 'font-bold text-slate-900' : 'text-slate-500 font-normal'">
                                    <span x-show="conv.last_sender === 'admin'" class="text-slate-900 font-bold">Anda: </span>
                                    <span x-text="conv.latest_message || '(Belum ada pesan)'"></span>
                                </p>
                            </div>

                            <!-- Unread badge -->
                            <template x-if="conv.unread_count > 0">
                                <span class="shrink-0 bg-slate-900 text-white font-bold text-[9px] px-1.5 py-0.5 rounded-full"
                                      x-text="conv.unread_count"></span>
                            </template>
                        </div>
                    </template>

                    <div x-show="conversations.length === 0" class="text-center py-12 text-slate-400 px-4">
                        <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-600">Belum ada obrolan support</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Percakapan masuk dari customer akan tampil di sini secara realtime.</p>
                    </div>
                </div>
            </div>

            <!-- COLUMN 2: Active Chat Thread (Flex-1) -->
            <div class="flex-1 flex flex-col min-w-0 bg-[#F8FAFC]">

                <!-- Active Chat Header -->
                <template x-if="activeCustomer">
                    <div class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                <span x-text="activeCustomer.avatar"></span>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-extrabold text-sm text-slate-900 truncate" x-text="activeCustomer.name"></h3>
                                <div class="flex items-center gap-2 text-[10px] text-slate-500">
                                    <span x-text="activeCustomer.phone" class="font-mono"></span>
                                    <span>•</span>
                                    <span class="text-emerald-600 font-bold flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Pelanggan Aktif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a :href="'/admin/customers?search=' + encodeURIComponent(activeCustomer.name)"
                               target="_blank"
                               class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-xl transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span>Profil Pelanggan</span>
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Messages Stream Scroll Area -->
                <div class="flex-1 p-6 overflow-y-auto space-y-4" x-ref="chatStream">
                    <template x-if="!activeCustomerId">
                        <div class="h-full flex flex-col items-center justify-center text-center text-slate-400 py-16">
                            <div class="w-14 h-14 bg-white rounded-2xl border border-slate-200 flex items-center justify-center text-slate-400 mb-3 shadow-xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            </div>
                            <h4 class="font-extrabold text-slate-700 text-sm">Pilih Percakapan Pelanggan</h4>
                            <p class="text-xs text-slate-400 mt-1 max-w-sm">Pilih salah satu percakapan di kolom kiri untuk membaca riwayat dan membalas chat customer.</p>
                        </div>
                    </template>

                    <!-- Message Bubbles -->
                    <template x-for="msg in messages" :key="msg.id">
                        <div class="flex flex-col" :class="msg.is_admin ? 'items-end' : 'items-start'">
                            <div class="max-w-[75%] rounded-2xl p-4 shadow-2xs space-y-1.5"
                                 :class="msg.is_admin ? 'bg-slate-900 text-white rounded-br-xs' : 'bg-white text-slate-800 border border-slate-200/80 rounded-bl-xs'">

                                <!-- Header / Sender -->
                                <div class="flex items-center justify-between gap-4 text-[10px]"
                                     :class="msg.is_admin ? 'text-slate-300 font-medium' : 'text-slate-400 font-bold'">
                                    <span class="flex items-center gap-1 font-bold">
                                        <span x-text="msg.is_admin ? msg.sender_name + ' (Admin)' : msg.sender_name"></span>
                                    </span>
                                    <span x-text="msg.time" class="font-mono text-[9px]"></span>
                                </div>

                                <!-- Order Reference (if any) -->
                                <template x-if="msg.order_id">
                                    <a :href="'/admin/reports?search=' + msg.order_id" target="_blank"
                                       class="block p-2 rounded-xl text-[10px] font-bold transition"
                                       :class="msg.is_admin ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100'">
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 inline text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                            <span>Pesanan #<span x-text="msg.order_id"></span>: <span x-text="msg.order_desc || ''"></span> ↗</span>
                                        </span>
                                    </a>
                                </template>

                                <!-- Message Text -->
                                <p class="text-xs leading-relaxed whitespace-pre-wrap" x-text="msg.message"></p>

                                <!-- Attachment Photo -->
                                <template x-if="msg.attachment_url">
                                    <div class="pt-1.5">
                                        <a :href="msg.attachment_url" target="_blank" class="block rounded-xl overflow-hidden border border-black/10">
                                            <img :src="msg.attachment_url" alt="Lampiran" class="max-h-56 w-full object-cover">
                                        </a>
                                    </div>
                                </template>
                            </div>

                            <!-- Subdate -->
                            <div class="mt-0.5 px-1 text-[9px] text-slate-400 font-mono">
                                <span x-text="msg.date"></span>
                                <template x-if="msg.is_admin">
                                    <span x-text="msg.is_read ? ' • Dibaca Customer' : ' • Terkirim'"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Canned Responses / Quick Reply Chips -->
                <template x-if="activeCustomerId">
                    <div class="bg-white border-t border-slate-100 px-6 py-2.5 flex items-center gap-1.5 overflow-x-auto shrink-0 custom-scrollbar">
                        <span class="text-[10px] font-bold text-slate-400 shrink-0 uppercase tracking-wider mr-1">Balas Cepat:</span>
                        <button type="button" @click="applyCanned('Halo kak, ada yang bisa kami bantu terkait pesanannya?')"
                                class="text-[10px] font-semibold bg-slate-50 hover:bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200 transition shrink-0 whitespace-nowrap">
                            Halo kak, ada yang bisa dibantu?
                        </button>
                        <button type="button" @click="applyCanned('Bisa diinfokan nomor ID pesanan yang terkendala ya kak?')"
                                class="text-[10px] font-semibold bg-slate-50 hover:bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200 transition shrink-0 whitespace-nowrap">
                            Minta Nomor ID Pesanan
                        </button>
                        <button type="button" @click="applyCanned('Dana refund sudah kami kreditkan ke saldo dompet kakak, silakan dicek ya.')"
                                class="text-[10px] font-semibold bg-slate-50 hover:bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200 transition shrink-0 whitespace-nowrap">
                            Dana Refund Sudah Dikreditkan
                        </button>
                        <button type="button" @click="applyCanned('Mohon lampirkan foto bukti kondisi barang yang diterima ya kak.')"
                                class="text-[10px] font-semibold bg-slate-50 hover:bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200 transition shrink-0 whitespace-nowrap">
                            Minta Foto Bukti Barang
                        </button>
                    </div>
                </template>

                <!-- Chat Input Box -->
                <template x-if="activeCustomerId">
                    <div class="bg-white border-t border-slate-200 p-4 shrink-0 shadow-lg">
                        <!-- Attachment Preview -->
                        <div x-show="imagePreview" x-cloak class="relative inline-block mb-2">
                            <img :src="imagePreview" alt="Preview" class="h-20 rounded-xl object-cover border border-slate-200 shadow-sm">
                            <button type="button" @click="clearAttachment()" class="absolute -top-2 -right-2 bg-slate-900 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold">✕</button>
                        </div>

                        <form @submit.prevent="submitReply()" class="flex items-end gap-3">
                            <label class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center cursor-pointer transition shrink-0" title="Lampirkan Gambar (JPG, PNG, HEIC iPhone)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                <input type="file" accept="image/*,.heic,.heif,image/heic,image/heif" @change="previewFile($event)" class="hidden" x-ref="adminFileInput">
                            </label>

                            <div class="flex-1 min-w-0 bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2 focus-within:border-slate-900 focus-within:ring-2 focus-within:ring-slate-900/10 transition">
                                <textarea
                                    x-model="replyText"
                                    rows="1"
                                    placeholder="Ketik balasan resmi sebagai Admin JastipKuy... (Tekan Enter untuk kirim)"
                                    @keydown.enter.exact.prevent="submitReply()"
                                    class="w-full text-xs bg-transparent focus:outline-none resize-none max-h-24 leading-relaxed text-slate-800 placeholder-slate-400"></textarea>
                            </div>

                            <button type="submit"
                                    :disabled="submitting || (!replyText.trim() && !attachmentFile)"
                                    class="w-10 h-10 rounded-2xl bg-slate-900 hover:bg-slate-800 disabled:opacity-40 text-white flex items-center justify-center transition shrink-0 shadow-md shadow-slate-900/20">
                                <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                            </button>
                        </form>
                    </div>
                </template>

            </div>

            <!-- COLUMN 3: Customer Context Drawer (Width: w-72, visible on lg) -->
            <template x-if="activeCustomer">
                <div class="w-72 bg-white border-l border-slate-200 p-5 flex flex-col shrink-0 min-h-0 overflow-y-auto hidden xl:block space-y-5">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-2">Profil Pelanggan</span>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2 text-xs">
                            <div class="font-black text-slate-900 text-sm" x-text="activeCustomer.name"></div>
                            <div class="text-slate-500 font-mono text-[11px]" x-text="activeCustomer.phone"></div>
                            <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between">
                                <span class="text-slate-500 font-medium text-[11px]">Saldo Dompet:</span>
                                <span class="font-extrabold text-emerald-600 text-xs" x-text="'Rp ' + (activeCustomer.wallet_balance || 0).toLocaleString('id-ID')"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="space-y-2">
                        <a :href="'/admin/reports?search=' + encodeURIComponent(activeCustomer.name)"
                           target="_blank"
                           class="w-full bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 font-bold text-xs py-2 rounded-xl block text-center transition">
                            Riwayat Komplain / Sengketa
                        </a>
                        <a :href="'/admin/customers?search=' + encodeURIComponent(activeCustomer.name)"
                           target="_blank"
                           class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs py-2 rounded-xl block text-center transition">
                            Lihat Data Lengkap Pelanggan
                        </a>
                    </div>
                </div>
            </template>

        </div>
    </div>
</div>

<script>
    function adminSupportChat(config) {
        return {
            conversations: @js($conversations),
            activeCustomerId: config.initialCustomerId,
            activeCustomer: null,
            messages: [],
            totalUnread: {{ $totalUnreadAll }},
            searchQuery: '',
            replyText: '',
            attachmentFile: null,
            imagePreview: null,
            submitting: false,
            fetchConversationsUrl: config.fetchConversationsUrl,
            messagesBaseUrl: config.messagesBaseUrl,
            csrfToken: config.csrfToken,
            pollTimer: null,

            initDashboard() {
                if (this.activeCustomerId) {
                    this.syncActiveCustomerObj();
                    this.loadMessages(true);
                }

                // Poll pesan & daftar percakapan setiap 4 detik
                this.pollTimer = setInterval(() => {
                    this.pollUpdates();
                }, 4000);
            },

            syncActiveCustomerObj() {
                const found = this.conversations.find(c => c.customer_id == this.activeCustomerId);
                if (found) {
                    this.activeCustomer = {
                        id: found.customer_id,
                        name: found.customer_name,
                        phone: found.customer_phone,
                        avatar: found.customer_avatar,
                        wallet_balance: {{ $selectedCustomer?->wallet?->balance ?? 0 }}
                    };
                }
            },

            selectCustomer(customerId) {
                this.activeCustomerId = customerId;
                this.syncActiveCustomerObj();
                this.loadMessages(true);

                // Tandai unread lokal
                const conv = this.conversations.find(c => c.customer_id == customerId);
                if (conv && conv.unread_count > 0) {
                    this.totalUnread = Math.max(0, this.totalUnread - conv.unread_count);
                    conv.unread_count = 0;
                }
            },

            async loadMessages(shouldScroll = false) {
                if (!this.activeCustomerId) return;

                try {
                    const res = await fetch(`${this.messagesBaseUrl}/${this.activeCustomerId}/messages`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        const previousCount = this.messages.length;
                        this.messages = data.messages;
                        if (shouldScroll || this.messages.length > previousCount) {
                            this.$nextTick(() => this.scrollToBottom());
                        }
                    }
                } catch (e) {
                    console.error('Error fetching admin support messages:', e);
                }
            },

            async pollUpdates() {
                // Poll conversations list for unread counts
                try {
                    let convUrl = this.fetchConversationsUrl;
                    if (this.searchQuery) convUrl += '?search=' + encodeURIComponent(this.searchQuery);
                    const resConv = await fetch(convUrl, { headers: { 'Accept': 'application/json' } });
                    const dataConv = await resConv.json();
                    if (dataConv.success) {
                        this.conversations = dataConv.conversations;
                        this.totalUnread = dataConv.total_unread;
                    }
                } catch (e) {}

                // Poll messages in current active chat
                if (this.activeCustomerId) {
                    this.loadMessages(false);
                }
            },

            async submitReply() {
                if (!this.replyText.trim() && !this.attachmentFile) return;

                this.submitting = true;
                const formData = new FormData();
                if (this.replyText.trim()) formData.append('message', this.replyText.trim());
                if (this.attachmentFile) formData.append('attachment', this.attachmentFile);

                try {
                    const res = await fetch(`${this.messagesBaseUrl}/${this.activeCustomerId}/send`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.replyText = '';
                        this.clearAttachment();
                        this.messages.push(data.message);
                        this.$nextTick(() => this.scrollToBottom());
                    } else {
                        alert(data.message || 'Gagal mengirim balasan.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan koneksi.');
                } finally {
                    this.submitting = false;
                }
            },

            applyCanned(text) {
                this.replyText = text;
            },

            previewFile(e) {
                const file = e.target.files[0];
                if (file) {
                    this.attachmentFile = file;
                    this.imagePreview = URL.createObjectURL(file);
                }
            },

            clearAttachment() {
                this.attachmentFile = null;
                this.imagePreview = null;
                if (this.$refs.adminFileInput) this.$refs.adminFileInput.value = '';
            },

            scrollToBottom() {
                const el = this.$refs.chatStream;
                if (el) el.scrollTop = el.scrollHeight;
            },

            filterConversations() {
                this.pollUpdates();
            }
        };
    }
</script>
@endsection
