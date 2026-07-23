{{--
    Modal Chat Personal per Order.

    Reusable untuk dashboard customer & jastiper — dibedakan lewat parameter yang
    dikirim saat @include (viewerRole + 2 URL template send/history). Dibuka dari
    halaman manapun cukup dengan dispatch custom event window "open-chat":

        window.dispatchEvent(new CustomEvent('open-chat', {
            detail: { orderId: 123, orderLabel: 'Beli oleh-oleh khas Malang' }
        }))

    Kenapa custom event (bukan taruh state chat langsung di x-data dashboard)?
    Supaya modal ini betul-betul independen dari Alpine scope besar milik
    customerDashboard()/jastiperDashboard() — tidak perlu utak-atik x-data
    dashboard yang sudah besar, dan partial ini bisa di-include apa adanya di
    halaman manapun yang punya tombol pemicu.

    Wajib: @include ini hanya SEKALI per halaman (skrip Alpine dibungkus @once,
    tapi elemen modalnya sendiri jangan diduplikasi juga).
--}}
@once
<script>
    // Alpine component untuk 1 jendela chat per order. Polling riwayat HANYA
    // berjalan selama modal terbuka (bukan di background dashboard), karena
    // endpoint history() di server punya efek samping menandai pesan lawan
    // bicara sebagai "sudah dibaca" — memanggilnya saat modal tertutup akan
    // salah menandai pesan padahal user belum benar-benar membuka chatnya.
    function orderChatWidget(config) {
        return {
            viewerRole: config.viewerRole,
            sendUrlTemplate: config.sendUrlTemplate,
            historyUrlTemplate: config.historyUrlTemplate,
            csrfToken: config.csrfToken,

            open: false,
            orderId: null,
            orderLabel: '',
            messages: [],
            loadingHistory: false,
            sending: false,
            newMessage: '',
            attachmentFile: null,
            attachmentPreviewUrl: null,
            pollHandle: null,
            errorMessage: null,

            openChat(detail) {
                this.orderId = detail.orderId;
                this.orderLabel = detail.orderLabel || '';
                this.messages = [];
                this.errorMessage = null;
                this.clearAttachment();
                this.newMessage = '';
                this.open = true;
                this.fetchHistory();
                this.startPolling();
            },

            closeChat() {
                this.open = false;
                this.stopPolling();
            },

            startPolling() {
                this.stopPolling();
                // ~4.5 detik, sesuai catatan Tahap 4 (endpoint dirancang untuk dipanggil tiap 4-5 detik).
                this.pollHandle = setInterval(() => this.fetchHistory(true), 4500);
            },

            stopPolling() {
                if (this.pollHandle) {
                    clearInterval(this.pollHandle);
                    this.pollHandle = null;
                }
            },

            historyUrl() {
                return new URL(this.historyUrlTemplate.replace('__ID__', this.orderId), window.location.origin).pathname;
            },

            sendUrl() {
                return new URL(this.sendUrlTemplate.replace('__ID__', this.orderId), window.location.origin).pathname;
            },

            async fetchHistory(silent = false) {
                if (!this.orderId) return;
                if (!silent) this.loadingHistory = true;
                try {
                    const res = await fetch(this.historyUrl(), { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.success) {
                        this.messages = data.messages || [];
                        this.errorMessage = null;
                        this.$nextTick(() => this.scrollToBottom());
                    } else {
                        // Order tidak/kembali tidak bisa diakses (mis. status berubah) — hentikan polling.
                        this.errorMessage = data.message || 'Gagal memuat chat.';
                        this.stopPolling();
                    }
                } catch (e) {
                    // Polling diam-diam gagal (koneksi putus sesaat) — biarkan, coba lagi siklus berikutnya.
                } finally {
                    this.loadingHistory = false;
                }
            },

            handleFileChange(e) {
                const file = e.target.files[0];
                if (!file) return;
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran foto maksimal 2MB.');
                    e.target.value = '';
                    return;
                }
                this.attachmentFile = file;
                const reader = new FileReader();
                reader.onload = (ev) => { this.attachmentPreviewUrl = ev.target.result; };
                reader.readAsDataURL(file);
            },

            clearAttachment() {
                this.attachmentFile = null;
                this.attachmentPreviewUrl = null;
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            },

            async sendMessage() {
                const text = this.newMessage.trim();
                if (!text && !this.attachmentFile) return;
                if (this.sending) return;

                this.sending = true;
                try {
                    const formData = new FormData();
                    if (text) formData.append('message', text);
                    if (this.attachmentFile) formData.append('attachment', this.attachmentFile);

                    const res = await fetch(this.sendUrl(), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: formData,
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.messages.push(data.message);
                        this.newMessage = '';
                        this.clearAttachment();
                        this.$nextTick(() => this.scrollToBottom());
                    } else {
                        alert(data.message || 'Gagal mengirim pesan.');
                    }
                } catch (e) {
                    alert('Gagal mengirim pesan. Periksa koneksi Anda.');
                } finally {
                    this.sending = false;
                }
            },

            scrollToBottom() {
                const box = this.$refs.scrollBox;
                if (box) box.scrollTop = box.scrollHeight;
            },

            bubbleAlign(msg) {
                if (msg.sender_role === 'system') return 'justify-center';
                return msg.is_mine ? 'justify-end' : 'justify-start';
            },

            formatTime(iso) {
                try {
                    return new Date(iso).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                } catch (e) {
                    return '';
                }
            },
        };
    }
</script>
@endonce

<div
    x-data="orderChatWidget({
        viewerRole: @js($viewerRole),
        sendUrlTemplate: @js($chatSendUrlTemplate),
        historyUrlTemplate: @js($chatHistoryUrlTemplate),
        csrfToken: @js(csrf_token()),
    })"
    x-on:open-chat.window="openChat($event.detail)"
    x-show="open"
    x-cloak
    style="display: none; position: fixed; inset: 0; z-index: 9999; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); align-items: center; justify-content: center;"
    class="flex p-4 animate-fade-in"
>
    <!-- Modal Card Container -->
    <div @click.outside="closeChat()" 
         class="shadow-2xl flex flex-col overflow-hidden transition-all duration-300 transform"
         style="background-color: #ffffff; width: 100%; max-width: 440px; height: 580px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Header -->
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5 shrink-0 bg-white" style="border-bottom: 1px solid #f1f5f9;">
            <div class="flex items-center gap-3 min-w-0">
                <!-- Chat Icon Avatar -->
                <div class="w-10 h-10 rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center font-display font-black text-sm text-rose-500 shrink-0 shadow-sm">
                    💬
                </div>
                <div class="min-w-0">
                    <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest block">Chat Pesanan</span>
                    <h4 class="font-display font-extrabold text-sm text-slate-800 truncate" x-text="orderLabel || 'Detail Pesanan'"></h4>
                </div>
            </div>
            <!-- Circular Close Button -->
            <button type="button" @click="closeChat()" 
                class="w-8 h-8 rounded-full bg-slate-50 border border-slate-200/60 hover:bg-slate-100 hover:text-slate-800 text-slate-400 flex items-center justify-center transition-colors shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div x-ref="scrollBox" class="flex-grow overflow-y-auto px-4 py-4 space-y-4" style="background-color: #f8fafc;">
            <div x-show="loadingHistory && messages.length === 0" class="text-center text-xs text-slate-400 font-semibold py-8">
                Memuat percakapan...
            </div>

            <div x-show="!loadingHistory && messages.length === 0 && !errorMessage" x-cloak class="text-center text-xs text-slate-400 font-semibold py-8 px-4 leading-normal">
                Belum ada pesan. Mulai percakapan dengan mengetik di bawah.
            </div>

            <div x-show="errorMessage" x-cloak class="text-center text-xs text-rose-500 font-semibold py-8" x-text="errorMessage"></div>

            <template x-for="msg in messages" :key="msg.id">
                <div class="flex" :class="bubbleAlign(msg)">

                    <!-- Pesan sistem (center, italic) -->
                    <template x-if="msg.sender_role === 'system'">
                        <div class="max-w-[85%] text-center my-1.5 mx-auto">
                            <span class="inline-block bg-slate-100 border border-slate-200 text-slate-500 text-[10px] font-bold px-4 py-1.5 rounded-full shadow-sm" x-text="msg.message"></span>
                        </div>
                    </template>

                    <!-- Pesan customer / jastiper -->
                    <template x-if="msg.sender_role !== 'system'">
                        <div class="max-w-[80%] space-y-1 flex flex-col" :class="msg.is_mine ? 'items-end' : 'items-start'">
                            
                            <!-- Sender name (only for opponent) -->
                            <p class="text-[9px] font-bold px-1.5 text-slate-400" x-show="!msg.is_mine" x-text="msg.sender_name"></p>

                            <!-- Message Bubble -->
                            <div class="shadow-sm"
                                :class="msg.is_mine 
                                    ? 'bg-rose-600 text-white rounded-2xl rounded-tr-none px-4 py-2.5' 
                                    : 'bg-white border border-slate-200/80 text-slate-800 rounded-2xl rounded-tl-none px-4 py-2.5'">

                                <img x-show="msg.attachment_url" :src="msg.attachment_url" @click="window.open(msg.attachment_url, '_blank')"
                                    class="rounded-xl max-h-48 w-full object-cover mb-2 border border-slate-100 shadow-sm cursor-pointer hover:opacity-95 transition-opacity" alt="Lampiran foto">

                                <p x-show="msg.message" class="text-[13px] leading-relaxed whitespace-pre-wrap font-medium" x-text="msg.message"></p>

                                <!-- Placeholder tombol aksi — dipakai Sprint 7 -->
                                <button type="button" x-show="msg.message_type === 'action_button'"
                                    @click="alert('Fitur ini aktif di Sprint 7')"
                                    class="mt-2 w-full bg-slate-950 hover:bg-slate-900 text-white text-[10px] font-extrabold uppercase tracking-wider py-2.5 rounded-xl transition shadow-sm">
                                    <span x-text="msg.message || 'Aksi'"></span>
                                </button>
                            </div>

                            <!-- Timestamp -->
                            <p class="text-[8px] text-slate-400 px-1.5 mt-0.5" x-text="formatTime(msg.created_at)"></p>
                        </div>
                    </template>

                </div>
            </template>
        </div>

        <!-- Attachment Preview before Sending -->
        <div x-show="attachmentPreviewUrl" x-cloak class="px-4 py-2 bg-white border-t border-slate-100 shrink-0" style="border-top: 1px solid #f1f5f9;">
            <div class="relative inline-block">
                <img :src="attachmentPreviewUrl" class="h-16 w-16 object-cover rounded-xl border border-slate-200 shadow-inner">
                <button type="button" @click="clearAttachment()"
                    class="absolute -top-2 -right-2 bg-slate-900 hover:bg-slate-800 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-black shadow-md">✕</button>
            </div>
        </div>

        <!-- Input Box Area -->
        <form @submit.prevent="sendMessage()" class="border-t border-slate-150 p-3 bg-white flex items-center gap-2.5 shrink-0" style="border-top: 1px solid #f1f5f9;">
            <input type="file" accept="image/*" x-ref="fileInput" class="hidden" @change="handleFileChange($event)">

            <!-- Plus Icon (Attachment upload trigger) -->
            <button type="button" @click="$refs.fileInput.click()"
                class="shrink-0 w-10 h-10 rounded-full bg-slate-50 border border-slate-200/80 hover:bg-slate-100 text-slate-550 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>

            <!-- Textarea (min-height 40px, font-size 14px to prevent iOS auto-zoom) -->
            <textarea
                x-model="newMessage"
                @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                rows="1"
                placeholder="Ketik pesan..."
                class="flex-grow resize-none bg-slate-50 border border-slate-200 focus:border-rose-500 focus:bg-white rounded-2xl px-4 py-2 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none transition max-h-24"
                style="line-height: 1.25rem; min-height: 40px; padding-top: 9px; padding-bottom: 9px;"
            ></textarea>

            <!-- Send Button -->
            <button type="submit" :disabled="sending || (!newMessage.trim() && !attachmentFile)"
                class="shrink-0 w-10 h-10 rounded-full bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-white transition shadow-md shadow-rose-600/10">
                <svg class="w-4.5 h-4.5 transform rotate-45 -translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
            </button>
        </form>

    </div>
</div>