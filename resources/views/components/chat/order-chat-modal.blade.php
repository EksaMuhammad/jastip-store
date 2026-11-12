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

            // State Penyesuaian Harga Asli Toko
            showAdjustmentModal: false,
            loadingAdjustmentItems: false,
            submittingAdjustment: false,
            adjustmentItems: [],
            selectedAdjustmentItemId: '',
            selectedAdjustmentItem: null,
            proposedPrice: '',
            adjustmentReason: '',
            adjustmentProofFile: null,
            adjustmentProofPreview: null,
            respondingAdjustmentId: null,

            // State Alert & Konfirmasi Pembatalan karena Kenaikan Harga Toko
            showCancelConfirmModal: false,
            cancelAdjustmentTargetId: null,
            cancellationAlertMessage: '',
            cancellationAlertDetail: '',
            showCancellationSuccessAlert: false,

            openChat(detail) {
                this.orderId = detail.orderId;
                this.orderLabel = detail.orderLabel || '';
                this.messages = [];
                this.errorMessage = null;
                this.clearAttachment();
                this.newMessage = '';
                this.showAdjustmentModal = false;
                this.adjustmentItems = [];
                this.selectedAdjustmentItemId = '';
                this.selectedAdjustmentItem = null;
                this.proposedPrice = '';
                this.adjustmentReason = '';
                this.clearAdjustmentProof();
                this.showCancelConfirmModal = false;
                this.cancelAdjustmentTargetId = null;
                this.cancellationAlertMessage = '';
                this.cancellationAlertDetail = '';
                this.showCancellationSuccessAlert = false;
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
                        window.dispatchEvent(new CustomEvent('chat-read', { detail: { orderId: this.orderId } }));
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
                if (msg.message_type === 'action_button') return 'justify-center w-full';
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

            // --- Metrik & Metode Penyesuaian Harga Asli Toko ---
            async openAdjustmentModal() {
                this.selectedAdjustmentItemId = '';
                this.selectedAdjustmentItem = null;
                this.proposedPrice = '';
                this.adjustmentReason = '';
                this.clearAdjustmentProof();
                this.showAdjustmentModal = true;
                this.loadingAdjustmentItems = true;
                try {
                    const res = await fetch(`/jastiper/orders/${this.orderId}/adjustment-items`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.adjustmentItems = data.items || [];
                    } else {
                        alert(data.message || 'Gagal memuat item pesanan.');
                    }
                } catch (e) {
                    alert('Gagal mengambil daftar barang pesanan.');
                } finally {
                    this.loadingAdjustmentItems = false;
                }
            },

            closeAdjustmentModal() {
                this.showAdjustmentModal = false;
            },

            onSelectAdjustmentItem() {
                this.selectedAdjustmentItem = this.adjustmentItems.find(i => i.id == this.selectedAdjustmentItemId) || null;
                if (this.selectedAdjustmentItem) {
                    this.proposedPrice = Math.round(this.selectedAdjustmentItem.current_price);
                }
            },

            handleAdjustmentProofChange(e) {
                const file = e.target.files[0];
                if (!file) return;
                if (file.size > 3 * 1024 * 1024) {
                    alert('Ukuran foto bukti toko maksimal 3MB.');
                    e.target.value = '';
                    return;
                }
                this.adjustmentProofFile = file;
                const reader = new FileReader();
                reader.onload = (ev) => { this.adjustmentProofPreview = ev.target.result; };
                reader.readAsDataURL(file);
            },

            clearAdjustmentProof() {
                this.adjustmentProofFile = null;
                this.adjustmentProofPreview = null;
                if (this.$refs.adjustmentFileInput) this.$refs.adjustmentFileInput.value = '';
            },

            get proposedDifference() {
                if (!this.selectedAdjustmentItem || !this.proposedPrice) return 0;
                const orig = parseFloat(this.selectedAdjustmentItem.current_price) || 0;
                const prop = parseFloat(this.proposedPrice) || 0;
                return prop - orig;
            },

            formatRupiah(num) {
                return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
            },

            async submitAdjustment() {
                if (!this.selectedAdjustmentItemId) {
                    alert('Pilih barang yang ingin disesuaikan harganya.');
                    return;
                }
                if (!this.proposedPrice || parseFloat(this.proposedPrice) <= 0) {
                    alert('Masukkan harga asli toko yang valid.');
                    return;
                }
                if (this.proposedDifference === 0) {
                    alert('Harga asli toko sama dengan estimasi awal. Tidak ada perubahan harga.');
                    return;
                }
                if (this.submittingAdjustment) return;

                this.submittingAdjustment = true;
                try {
                    const formData = new FormData();
                    formData.append('order_item_id', this.selectedAdjustmentItemId);
                    formData.append('proposed_price', this.proposedPrice);
                    if (this.adjustmentReason) formData.append('reason', this.adjustmentReason);
                    if (this.adjustmentProofFile) formData.append('proof_image', this.adjustmentProofFile);

                    const res = await fetch(`/jastiper/orders/${this.orderId}/price-adjustments`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: formData,
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.closeAdjustmentModal();
                        await this.fetchHistory(true);
                        this.$nextTick(() => this.scrollToBottom());
                    } else {
                        alert(data.message || 'Gagal mengajukan perubahan harga.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan saat mengajukan perubahan harga.');
                } finally {
                    this.submittingAdjustment = false;
                }
            },

            promptCancelAdjustment(adjustmentId) {
                this.cancelAdjustmentTargetId = adjustmentId;
                this.showCancelConfirmModal = true;
            },

            closeCancelConfirmModal() {
                this.showCancelConfirmModal = false;
                this.cancelAdjustmentTargetId = null;
            },

            async executeCancelAdjustment() {
                if (!this.cancelAdjustmentTargetId) return;
                const adjustmentId = this.cancelAdjustmentTargetId;
                this.showCancelConfirmModal = false;
                await this.respondAdjustment(adjustmentId, 'cancel_order');
            },

            async respondAdjustment(adjustmentId, action) {
                if (action === 'reject') {
                    if (!confirm('Tolak penyesuaian harga ini? Jastiper akan berkoordinasi lebih lanjut via chat.')) {
                        return;
                    }
                }

                this.respondingAdjustmentId = adjustmentId;
                try {
                    const res = await fetch(`/customer/price-adjustments/${adjustmentId}/respond`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({ action: action }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        await this.fetchHistory(true);
                        if (action === 'approve') {
                            window.dispatchEvent(new CustomEvent('order-price-adjusted', { detail: { adjustmentId } }));
                        } else if (action === 'cancel_order') {
                            this.cancellationAlertMessage = data.title || 'Pesanan Berhasil Dibatalkan';
                            this.cancellationAlertDetail = data.message || 'Kenaikan harga barang di toko tidak disetujui. Dana pembayaran awal Anda telah dikembalikan 100% ke Saldo Dompet.';
                            this.showCancellationSuccessAlert = true;
                            window.dispatchEvent(new CustomEvent('order-price-adjusted', { detail: { adjustmentId } }));
                            setTimeout(() => {
                                window.location.reload();
                            }, 2500);
                        }
                    } else {
                        alert(data.message || 'Gagal menanggapi penyesuaian harga.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan saat memproses tanggapan.');
                } finally {
                    this.respondingAdjustmentId = null;
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
    :class="{ 'pointer-events-none': !open, 'pointer-events-auto': open }"
    style="display: none; position: fixed; inset: 0; z-index: 9999; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); align-items: center; justify-content: center;"
    class="flex p-4 animate-fade-in"
>
    <!-- Modal Card Container -->
    <div @click.outside="closeChat()" 
         class="shadow-2xl flex flex-col overflow-hidden transition-all duration-300 transform relative"
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
            <div class="flex items-center gap-1.5 shrink-0">
                <template x-if="viewerRole === 'customer'">
                    <div class="flex items-center gap-1">
                        <a href="{{ route('customer.support.chat') }}" 
                            title="Chat Bantuan Admin CS" 
                            class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200/80 text-rose-600 font-extrabold text-[10px] flex items-center gap-1 transition">
                            <span>🎧</span>
                            <span class="hidden sm:inline">CS Admin</span>
                        </a>
                        <a :href="'/customer/orders/' + orderId + '/report'" 
                            title="Ajukan Pengaduan Masalah Pesanan" 
                            class="px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-600 font-extrabold text-[10px] flex items-center gap-1 transition">
                            <span>⚖️</span>
                            <span class="hidden sm:inline">Komplain</span>
                        </a>
                    </div>
                </template>
                <!-- Circular Close Button -->
                <button type="button" @click="closeChat()" 
                    class="w-8 h-8 rounded-full bg-slate-50 border border-slate-200/60 hover:bg-slate-100 hover:text-slate-800 text-slate-400 flex items-center justify-center transition-colors shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
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

                    <!-- 1. Pesan Aksi Interaktif (action_button: Penyesuaian Harga / Addon) -->
                    <template x-if="msg.message_type === 'action_button'">
                        <div class="w-full max-w-[92%] my-2 mx-auto">
                            <div class="bg-white border-2 border-amber-300 rounded-2xl p-3.5 shadow-md space-y-2.5 text-left">
                                
                                <!-- Header Kartu Aksi -->
                                <div class="flex items-center justify-between border-b border-amber-100 pb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-sm font-bold">
                                            🏷️
                                        </div>
                                        <div>
                                            <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 block leading-tight">Konfirmasi Toko</span>
                                            <h4 class="font-extrabold text-xs text-slate-900 leading-tight">Perubahan Harga Barang Asli</h4>
                                        </div>
                                    </div>
                                    <div>
                                        <template x-if="msg.adjustment_status === 'approved'">
                                            <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full inline-flex items-center gap-0.5">
                                                <span>✓</span> Disetujui
                                            </span>
                                        </template>
                                        <template x-if="msg.adjustment_status === 'rejected'">
                                            <span class="text-[9px] font-extrabold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full inline-flex items-center gap-0.5">
                                                <span>✕</span> Ditolak
                                            </span>
                                        </template>
                                        <template x-if="msg.adjustment_status === 'cancelled'">
                                            <span class="text-[9px] font-extrabold text-slate-600 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-full inline-flex items-center gap-0.5">
                                                <span>⚠️</span> Dibatalkan
                                            </span>
                                        </template>
                                        <template x-if="!msg.adjustment_status || msg.adjustment_status === 'pending'">
                                            <span class="text-[9px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full animate-pulse inline-flex items-center gap-0.5">
                                                <span>⏳</span> Menunggu Respon
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Lampiran Foto Bukti Struk/Harga Toko (jika ada) -->
                                <template x-if="msg.attachment_url">
                                    <div class="pt-1">
                                        <p class="text-[10px] font-bold text-slate-600 mb-1">📸 Bukti Foto Toko:</p>
                                        <img :src="msg.attachment_url" @click="window.open(msg.attachment_url, '_blank')"
                                            class="rounded-xl max-h-48 w-full object-cover border border-slate-200 shadow-xs cursor-pointer hover:opacity-95 transition"
                                            alt="Bukti Penyesuaian Harga">
                                    </div>
                                </template>

                                <!-- Rincian Teks -->
                                <div class="bg-amber-50/70 rounded-xl p-2.5 border border-amber-100/80 text-[11px] text-slate-800 whitespace-pre-wrap font-medium leading-relaxed" x-text="msg.message">
                                </div>

                                <!-- Action Buttons: Penyesuaian Harga -->
                                <template x-if="msg.action_type && msg.action_type.startsWith('price_adjustment:')">
                                    <div class="pt-1 w-full space-y-2">
                                        <!-- Customer Actions -->
                                        <template x-if="viewerRole === 'customer'">
                                            <div>
                                                <!-- Jika Masih Pending: Tombol Interaktif -->
                                                <template x-if="!msg.adjustment_status || msg.adjustment_status === 'pending'">
                                                    <div class="space-y-1.5">
                                                        <button type="button"
                                                            :disabled="respondingAdjustmentId"
                                                            @click="respondAdjustment(msg.action_type.split(':')[1], 'approve')"
                                                            class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-95 disabled:opacity-50 text-white text-[11px] font-black uppercase tracking-wider py-2.5 px-3 rounded-xl transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                                                            <span x-show="respondingAdjustmentId !== msg.action_type.split(':')[1]">✔️</span>
                                                            <span x-text="respondingAdjustmentId === msg.action_type.split(':')[1] ? 'Memproses...' : 'Setujui Perubahan Harga'"></span>
                                                        </button>
                                                        <div class="grid grid-cols-2 gap-1.5">
                                                            <button type="button"
                                                                :disabled="respondingAdjustmentId"
                                                                @click="respondAdjustment(msg.action_type.split(':')[1], 'reject')"
                                                                class="bg-slate-100 hover:bg-slate-200 disabled:opacity-50 text-slate-700 text-[10px] font-extrabold uppercase tracking-wider py-2 px-2 rounded-xl transition text-center border border-slate-200 cursor-pointer active:scale-95">
                                                                ❌ Tolak
                                                            </button>
                                                            <button type="button"
                                                                :disabled="respondingAdjustmentId"
                                                                @click="promptCancelAdjustment(msg.action_type.split(':')[1])"
                                                                class="bg-rose-50 hover:bg-rose-100 disabled:opacity-50 text-rose-600 text-[10px] font-extrabold uppercase tracking-wider py-2 px-2 rounded-xl transition text-center border border-rose-200 active:scale-95 cursor-pointer">
                                                                ⚠️ Batal Pesanan
                                                            </button>
                                                        </div>
                                                        <p class="text-[9px] text-slate-400 text-center font-medium leading-tight">
                                                            Jika disetujui, perubahan harga tercatat resmi pada rincian struk pesanan.
                                                        </p>
                                                    </div>
                                                </template>

                                                <!-- Jika Sudah Disetujui -->
                                                <template x-if="msg.adjustment_status === 'approved'">
                                                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-2.5 text-center text-[10px] font-bold">
                                                        ✓ Anda telah menyetujui perubahan harga ini. Tercatat resmi di struk pesanan.
                                                    </div>
                                                </template>

                                                <!-- Jika Ditolak -->
                                                <template x-if="msg.adjustment_status === 'rejected'">
                                                    <div class="bg-slate-100 border border-slate-200 text-slate-600 rounded-xl p-2.5 text-center text-[10px] font-bold">
                                                        ❌ Anda telah menolak penyesuaian harga ini.
                                                    </div>
                                                </template>

                                                <!-- Jika Dibatalkan -->
                                                <template x-if="msg.adjustment_status === 'cancelled'">
                                                    <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-2.5 text-center text-[10px] font-bold">
                                                        ⚠️ Pesanan telah dibatalkan karena perubahan harga tidak disetujui.
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Jastiper Status Notice -->
                                        <template x-if="viewerRole === 'jastiper'">
                                            <div>
                                                <template x-if="!msg.adjustment_status || msg.adjustment_status === 'pending'">
                                                    <div class="bg-amber-50 border border-amber-200/80 text-amber-900 rounded-xl p-2.5 text-center shadow-xs">
                                                        <p class="text-[10px] font-black uppercase tracking-wider flex items-center justify-center gap-1">
                                                            <span>⏳</span> Menunggu Respon Customer
                                                        </p>
                                                        <p class="text-[9px] text-amber-700 mt-0.5 leading-snug">
                                                            Customer dapat menyetujui, menolak, atau membatalkan pesanan.
                                                        </p>
                                                    </div>
                                                </template>
                                                <template x-if="msg.adjustment_status === 'approved'">
                                                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-2 text-center text-[10px] font-bold">
                                                        ✓ Disetujui Customer. Lanjutkan pembelian dengan harga baru.
                                                    </div>
                                                </template>
                                                <template x-if="msg.adjustment_status === 'rejected'">
                                                    <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-2 text-center text-[10px] font-bold">
                                                        ✕ Ditolak Customer. Jangan beli item dengan harga baru.
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Action Buttons: Addon Request -->
                                <template x-if="msg.action_type && msg.action_type.startsWith('addon_request:')">
                                    <div class="pt-1">
                                        <template x-if="viewerRole === 'jastiper'">
                                            <button type="button"
                                                @click="
                                                    const addonId = msg.action_type.split(':')[1];
                                                    const respondUrl = `/jastiper/addons/${addonId}/respond`;
                                                    const descMatch = msg.message.split('\n')[1] || 'Tambahan';
                                                    window.dispatchEvent(new CustomEvent('open-respond-modal', { detail: { url: respondUrl, description: descMatch } }));
                                                "
                                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-extrabold uppercase tracking-wider py-2.5 rounded-xl transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                                                <span>✔️</span>
                                                <span>Tinjau & Setujui</span>
                                            </button>
                                        </template>
                                        <template x-if="viewerRole === 'customer'">
                                            <div class="w-full bg-slate-100 text-slate-500 text-[10px] font-bold uppercase tracking-wider py-2 rounded-xl text-center">
                                                Menunggu Konfirmasi Jastiper...
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Timestamp -->
                                <p class="text-[8px] text-slate-400 text-right" x-text="formatTime(msg.created_at)"></p>
                            </div>
                        </div>
                    </template>

                    <!-- 2. Pesan sistem biasa (center pill) -->
                    <template x-if="msg.sender_role === 'system' && msg.message_type !== 'action_button'">
                        <div class="max-w-[85%] text-center my-1.5 mx-auto">
                            <span class="inline-block bg-slate-100 border border-slate-200 text-slate-500 text-[10px] font-bold px-4 py-1.5 rounded-full shadow-sm" x-text="msg.message"></span>
                        </div>
                    </template>

                    <!-- 3. Pesan customer / jastiper biasa -->
                    <template x-if="msg.sender_role !== 'system' && msg.message_type !== 'action_button'">
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

        <!-- Jastiper Quick Action: Sesuaikan Harga Toko -->
        <div x-show="viewerRole === 'jastiper'" class="px-4 py-2 bg-gradient-to-r from-amber-50 to-orange-50 border-t border-amber-200/60 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-sm shrink-0">🏷️</span>
                <p class="text-[11px] font-bold text-amber-900 truncate">
                    Harga barang di toko berubah?
                </p>
            </div>
            <button type="button" @click="openAdjustmentModal()"
                class="shrink-0 bg-amber-500 hover:bg-amber-600 active:scale-95 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1">
                <span>+</span>
                <span>Ubah Harga</span>
            </button>
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

        <!-- Popup Modal Penyesuaian Harga Toko (Khusus Jastiper) -->
        <div x-show="showAdjustmentModal" x-cloak
             style="position: absolute; inset: 0; z-index: 50; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(2px);"
             class="flex items-center justify-center p-3">
            <div class="bg-white rounded-2xl w-full max-w-sm max-h-[95%] flex flex-col shadow-2xl overflow-hidden border border-slate-100" @click.outside="closeAdjustmentModal()">
                <!-- Modal Header -->
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🏷️</span>
                        <h4 class="font-extrabold text-sm text-slate-800">Ubah Harga Asli Toko</h4>
                    </div>
                    <button type="button" @click="closeAdjustmentModal()" class="text-slate-400 hover:text-slate-600 text-sm font-bold p-1">✕</button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitAdjustment()" class="p-4 space-y-3 overflow-y-auto text-xs">
                    <!-- Loading state -->
                    <div x-show="loadingAdjustmentItems" class="text-center py-6 text-slate-400 font-semibold">
                        Memuat daftar barang pesanan...
                    </div>

                    <div x-show="!loadingAdjustmentItems" class="space-y-3">
                        <!-- Select Item -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pilih Barang yang Berubah Harga</label>
                            <select x-model="selectedAdjustmentItemId" @change="onSelectAdjustmentItem()"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:border-rose-500">
                                <option value="">-- Pilih Barang --</option>
                                <template x-for="item in adjustmentItems" :key="item.id">
                                    <option :value="item.id" :disabled="item.has_pending"
                                            x-text="item.name + ' (Est: Rp ' + Number(item.current_price).toLocaleString('id-ID') + ')' + (item.has_pending ? ' [Pending]' : '')">
                                    </option>
                                </template>
                            </select>
                        </div>

                        <!-- Item Price Detail Comparison -->
                        <template x-if="selectedAdjustmentItem">
                            <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-2.5 space-y-2">
                                <div class="flex justify-between items-center text-[11px]">
                                    <span class="text-slate-500 font-medium">Estimasi Awal:</span>
                                    <span class="font-bold text-slate-800" x-text="formatRupiah(selectedAdjustmentItem.current_price)"></span>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Harga Asli di Toko (Rp)</label>
                                    <input type="number" min="0" step="500" x-model.number="proposedPrice"
                                           placeholder="Contoh: 28.000"
                                           class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-rose-500">
                                    <span x-show="proposedPrice > 0" class="text-[10px] font-bold text-rose-600 block mt-1" x-text="'Harga Toko: ' + formatRupiah(proposedPrice)"></span>
                                </div>
                                <div class="flex justify-between items-center text-[11px] pt-1.5 border-t border-slate-200">
                                    <span class="font-bold text-slate-600">Selisih Penyesuaian:</span>
                                    <span class="font-black"
                                          :class="proposedDifference > 0 ? 'text-amber-600' : (proposedDifference < 0 ? 'text-emerald-600' : 'text-slate-400')"
                                          x-text="(proposedDifference > 0 ? '+' : '') + formatRupiah(proposedDifference)">
                                    </span>
                                </div>
                            </div>
                        </template>

                        <!-- Reason / Note -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Catatan / Alasan Perubahan <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <textarea x-model="adjustmentReason" rows="2"
                                      placeholder="Contoh: Harga di kasir toko naik per bulan ini"
                                      class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:border-rose-500 resize-none"></textarea>
                        </div>

                        <!-- Proof Photo Upload -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Foto Bukti / Struk / Label Harga <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input type="file" accept="image/*" x-ref="adjustmentFileInput" @change="handleAdjustmentProofChange($event)" class="hidden">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="$refs.adjustmentFileInput.click()"
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-xl text-slate-700 font-bold text-[11px] transition flex items-center gap-1.5">
                                    <span>📷</span>
                                    <span x-text="adjustmentProofFile ? 'Ganti Foto' : 'Ambil/Unggah Foto'"></span>
                                </button>
                                <button type="button" x-show="adjustmentProofFile" @click="clearAdjustmentProof()"
                                        class="text-rose-600 hover:text-rose-700 font-bold text-[11px]">Hapus</button>
                            </div>
                            <template x-if="adjustmentProofPreview">
                                <div class="mt-2">
                                    <img :src="adjustmentProofPreview" class="h-16 w-16 object-cover rounded-xl border border-slate-200 shadow-xs">
                                </div>
                            </template>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button type="button" @click="closeAdjustmentModal()"
                                    class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                                Batal
                            </button>
                            <button type="submit"
                                    :disabled="submittingAdjustment || !selectedAdjustmentItemId || !proposedPrice || proposedDifference === 0"
                                    class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-extrabold text-xs transition shadow-sm flex items-center gap-1.5">
                                <span x-show="submittingAdjustment">Mengirim...</span>
                                <span x-show="!submittingAdjustment">Kirim ke Chat</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Dialog Konfirmasi Pembatalan karena Kenaikan Harga Toko -->
        <div x-show="showCancelConfirmModal" x-cloak
             style="position: absolute; inset: 0; z-index: 55; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(2px);"
             class="flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-sm p-4 text-center space-y-3.5 shadow-2xl border border-rose-100" @click.outside="closeCancelConfirmModal()">
                <div class="w-11 h-11 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-xl mx-auto shadow-inner">
                    ⚠️
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-900">Batalkan Pesanan?</h4>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Konfirmasi pembatalan karena ketidaksesuaian harga toko
                    </p>
                </div>
                
                <!-- Alert Box with Clear Explanations -->
                <div class="bg-rose-50 border border-rose-200/80 rounded-xl p-3 text-left space-y-2 text-xs">
                    <div class="flex items-center gap-1.5 font-bold text-rose-800 text-[11px]">
                        <span>🏬</span> Kenaikan Harga Toko Tidak Sesuai
                    </div>
                    <p class="text-[11px] text-slate-700 leading-relaxed font-medium">
                        Karena terdapat kenaikan harga barang asli di toko yang berbeda dari estimasi awal, Anda berhak membatalkan pesanan ini.
                    </p>
                    <div class="bg-white/90 rounded-lg p-2 border border-rose-100 text-[10px] text-emerald-700 font-bold flex items-start gap-1.5">
                        <span class="text-xs">🛡️</span>
                        <span>Dana pembayaran awal (100%) otomatis dikembalikan penuh ke Saldo Dompet Anda tanpa potongan.</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button type="button" @click="closeCancelConfirmModal()"
                            class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-2.5 rounded-xl transition">
                        Kembali ke Chat
                    </button>
                    <button type="button" @click="executeCancelAdjustment()"
                            :disabled="respondingAdjustmentId"
                            class="w-full bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-extrabold text-xs py-2.5 rounded-xl transition shadow-xs flex items-center justify-center gap-1">
                        <span x-show="respondingAdjustmentId">Memproses...</span>
                        <span x-show="!respondingAdjustmentId">Ya, Batalkan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Alert Sukses Pembatalan Pesanan -->
        <div x-show="showCancellationSuccessAlert" x-cloak
             style="position: absolute; inset: 0; z-index: 60; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(3px);"
             class="flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-sm p-5 text-center space-y-3.5 shadow-2xl border border-rose-100">
                <div class="w-13 h-13 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-2xl mx-auto shadow-inner">
                    🏷️
                </div>
                <div>
                    <span class="text-[9px] font-black tracking-widest text-rose-600 uppercase bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-full inline-block mb-1.5">
                        Status: Dibatalkan
                    </span>
                    <h4 class="font-extrabold text-sm text-slate-900" x-text="cancellationAlertMessage || 'Pesanan Dibatalkan'"></h4>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed font-medium" x-text="cancellationAlertDetail"></p>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-2.5 text-center text-emerald-800 text-[11px] font-bold flex items-center justify-center gap-1.5">
                    <span>✓</span>
                    <span>Dana telah dikembalikan ke Saldo Dompet Anda</span>
                </div>
                <button type="button" @click="window.location.reload()"
                        class="w-full bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs py-2.5 rounded-xl transition shadow-sm">
                    Lihat Rincian Pesanan
                </button>
            </div>
        </div>

    </div>
</div>