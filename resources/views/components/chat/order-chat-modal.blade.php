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
    style="display: none;"
    class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center bg-slate-900/60 backdrop-blur-sm"
>
    <div @click.outside="closeChat()" class="bg-white w-full sm:max-w-md sm:rounded-3xl rounded-t-3xl shadow-lg flex flex-col h-[88vh] sm:h-[620px] overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5 shrink-0">
            <div class="min-w-0">
                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Chat Pesanan</p>
                <h4 class="font-display font-black text-xs text-slate-800 truncate" x-text="orderLabel || 'Chat'"></h4>
            </div>
            <button type="button" @click="closeChat()" class="text-slate-400 hover:text-slate-700 font-extrabold text-sm shrink-0 px-2">✕</button>
        </div>

        <!-- Messages -->
        <div x-ref="scrollBox" class="flex-grow overflow-y-auto px-4 py-4 space-y-3 bg-[#F8F9FB]">
            <div x-show="loadingHistory && messages.length === 0" class="text-center text-[9px] text-slate-400 font-semibold py-6">
                Memuat percakapan...
            </div>

            <div x-show="!loadingHistory && messages.length === 0 && !errorMessage" x-cloak class="text-center text-[9px] text-slate-400 font-semibold py-6">
                Belum ada pesan. Mulai percakapan dengan mengetik di bawah.
            </div>

            <div x-show="errorMessage" x-cloak class="text-center text-[9px] text-rose-500 font-semibold py-6" x-text="errorMessage"></div>

            <template x-for="msg in messages" :key="msg.id">
                <div class="flex" :class="bubbleAlign(msg)">

                    <!-- Pesan sistem: center, italic, tanpa bubble arah -->
                    <template x-if="msg.sender_role === 'system'">
                        <div class="max-w-[85%] text-center">
                            <span class="inline-block bg-slate-200/70 text-slate-500 text-[9px] italic font-semibold px-3 py-1.5 rounded-full" x-text="msg.message"></span>
                        </div>
                    </template>

                    <!-- Pesan customer/jastiper -->
                    <template x-if="msg.sender_role !== 'system'">
                        <div class="max-w-[78%] space-y-1 flex flex-col" :class="msg.is_mine ? 'items-end' : 'items-start'">
                            <p class="text-[8px] font-bold px-1 text-slate-400" x-show="!msg.is_mine" x-text="msg.sender_name"></p>

                            <div class="rounded-2xl px-3.5 py-2.5 shadow-sm"
                                :class="msg.is_mine ? 'bg-rose-600 text-white rounded-br-sm' : 'bg-white border border-slate-200 text-slate-700 rounded-bl-sm'">

                                <img x-show="msg.attachment_url" :src="msg.attachment_url" @click="window.open(msg.attachment_url, '_blank')"
                                    class="rounded-xl max-h-48 object-cover mb-1.5 cursor-pointer" alt="Lampiran foto">

                                <p x-show="msg.message" class="text-[10px] leading-relaxed whitespace-pre-wrap" x-text="msg.message"></p>

                                <!-- Placeholder tombol aksi — dipakai Sprint 7 (belum ada trigger aktif sampai saat ini) -->
                                <button type="button" x-show="msg.message_type === 'action_button'"
                                    @click="alert('Fitur ini aktif di Sprint 7')"
                                    class="mt-1.5 w-full bg-slate-900 hover:bg-slate-800 text-white text-[9px] font-black uppercase tracking-wide py-2 rounded-xl transition">
                                    <span x-text="msg.message || 'Aksi'"></span>
                                </button>
                            </div>

                            <p class="text-[7px] text-slate-400 px-1" x-text="formatTime(msg.created_at)"></p>
                        </div>
                    </template>

                </div>
            </template>
        </div>

        <!-- Preview lampiran sebelum dikirim -->
        <div x-show="attachmentPreviewUrl" x-cloak class="px-4 pt-2 shrink-0">
            <div class="relative inline-block">
                <img :src="attachmentPreviewUrl" class="h-16 w-16 object-cover rounded-xl border border-slate-200">
                <button type="button" @click="clearAttachment()"
                    class="absolute -top-2 -right-2 bg-slate-900 text-white rounded-full w-5 h-5 flex items-center justify-center text-[9px] font-black">✕</button>
            </div>
        </div>

        <!-- Input -->
        <form @submit.prevent="sendMessage()" class="border-t border-slate-100 p-3 flex items-end gap-2 shrink-0">
            <input type="file" accept="image/*" x-ref="fileInput" class="hidden" @change="handleFileChange($event)">

            <button type="button" @click="$refs.fileInput.click()"
                class="shrink-0 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </button>

            <textarea
                x-model="newMessage"
                @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                rows="1"
                placeholder="Ketik pesan..."
                class="flex-grow resize-none bg-slate-100 border border-transparent focus:border-rose-500 focus:bg-white rounded-2xl px-3.5 py-2.5 text-[10px] font-semibold text-slate-700 focus:outline-none transition max-h-24"
            ></textarea>

            <button type="submit" :disabled="sending || (!newMessage.trim() && !attachmentFile)"
                class="shrink-0 w-9 h-9 rounded-full bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            </button>
        </form>

    </div>
</div>