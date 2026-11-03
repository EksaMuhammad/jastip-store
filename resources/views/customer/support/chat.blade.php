@extends('layouts.support')

@section('title', 'Bantuan Customer Support - JastipKuy')

@section('content')
<div class="fixed inset-0 h-[100dvh] max-h-[100dvh] bg-[#F8FAFC] flex flex-col overflow-hidden z-40"
     x-data="supportChat({
         fetchUrl: '{{ route('customer.support.messages') }}',
         sendUrl: '{{ route('customer.support.send') }}',
         csrfToken: '{{ csrf_token() }}'
     })"
     x-init="initChat()">

    <!-- Top Sticky Header -->
    <header class="shrink-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 py-3 shadow-xs">
        <div class="max-w-xl mx-auto flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ url('/customer/dashboard?tab=chats') }}" onclick="if(window.history.length > 1) { window.history.back(); return false; }" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition shrink-0" title="Kembali">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <div class="relative shrink-0">
                    <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-black text-sm shadow-sm">
                        🛡️
                    </div>
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white"></span>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <h2 class="font-extrabold text-sm text-slate-900 truncate">Customer Support</h2>
                        <span class="px-1.5 py-0.2 rounded text-[8px] font-black uppercase bg-emerald-100 text-emerald-800">Resmi</span>
                    </div>
                    <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Online • Siap Membantu
                    </span>
                </div>
            </div>

            <!-- Hubungi WA Alternatif -->
            <a href="https://wa.me/6281234567890?text=Halo%20Admin%20JastipKuy,%20saya%20butuh%20bantuan%20support"
               target="_blank"
               title="Hubungi WhatsApp"
               class="px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-bold flex items-center gap-1 transition shrink-0">
                <span>💬 WhatsApp</span>
            </a>
        </div>
    </header>

    <!-- Chat Messages Scroll Area -->
    <main class="flex-1 min-h-0 max-w-xl w-full mx-auto p-4 space-y-4 overflow-y-auto" x-ref="messagesContainer">

        <!-- Welcome Banner Card -->
        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200/90 rounded-3xl p-4 shadow-2xs space-y-3">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black text-lg shrink-0 shadow-sm">
                    🤝
                </div>
                <div>
                    <h3 class="font-extrabold text-xs text-emerald-950">Pusat Layanan & Bantuan Pelanggan</h3>
                    <p class="text-[11px] text-emerald-800 mt-0.5 leading-relaxed">
                        Halo <strong>{{ $customer->name }}</strong>! Tim Customer Support JastipKuy siap membantu Anda menangani pertanyaan, kendala belanja, status pengiriman, maupun proses pengembalian dana.
                    </p>
                </div>
            </div>

            <!-- Quick Action Chips -->
            <div class="pt-1 border-t border-emerald-200/60 flex flex-wrap gap-1.5">
                <button type="button" @click="sendQuickMessage('Halo Admin, saya ingin menanyakan status pesanan saya.')"
                        class="text-[10px] font-bold bg-white/90 hover:bg-white text-emerald-900 px-3 py-1.5 rounded-xl border border-emerald-200/70 transition shadow-2xs">
                    📦 Cek Status Pesanan
                </button>
                <button type="button" @click="sendQuickMessage('Halo Admin, saya ada kendala barang tidak sesuai / ingin pengajuan refund.')"
                        class="text-[10px] font-bold bg-white/90 hover:bg-white text-emerald-900 px-3 py-1.5 rounded-xl border border-emerald-200/70 transition shadow-2xs">
                    💸 Bantuan Pengembalian Dana
                </button>
                <button type="button" @click="sendQuickMessage('Halo Admin, bagaimana cara titip belanja barang yang tidak ada di katalog?')"
                        class="text-[10px] font-bold bg-white/90 hover:bg-white text-emerald-900 px-3 py-1.5 rounded-xl border border-emerald-200/70 transition shadow-2xs">
                    🛍️ Titip Barang Non-Katalog
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <template x-if="messages.length === 0 && !loading">
            <div class="text-center py-10 space-y-2">
                <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-2xl text-slate-400">
                    💬
                </div>
                <h4 class="font-bold text-xs text-slate-700">Mulai Percakapan dengan Admin</h4>
                <p class="text-[11px] text-slate-400 max-w-xs mx-auto">Ketik pertanyaan atau kendala Anda di kolom bawah. Tim kami akan segera merespons Anda.</p>
            </div>
        </template>

        <!-- Dynamic Message Bubbles -->
        <template x-for="msg in messages" :key="msg.id">
            <div class="flex flex-col" :class="msg.is_mine ? 'items-end' : 'items-start'">
                <div class="max-w-[85%] rounded-2xl p-3.5 shadow-2xs space-y-1.5"
                     :class="msg.is_mine ? 'bg-emerald-600 text-white rounded-br-xs' : 'bg-white text-slate-800 border border-slate-200/80 rounded-bl-xs'">

                    <!-- Sender Title -->
                    <div class="flex items-center justify-between gap-3 text-[10px]"
                         :class="msg.is_mine ? 'text-emerald-100' : 'text-slate-400 font-bold'">
                        <span class="flex items-center gap-1 font-bold">
                            <span x-text="msg.is_mine ? 'Anda' : '🛡️ ' + msg.sender_name"></span>
                            <template x-if="!msg.is_mine">
                                <span class="px-1 py-0.2 rounded text-[7px] font-black uppercase bg-rose-50 text-rose-600 border border-rose-200">Admin</span>
                            </template>
                        </span>
                        <span x-text="msg.time" class="font-mono text-[9px]"></span>
                    </div>

                    <!-- Referenced Order Tag (if any) -->
                    <template x-if="msg.order_id">
                        <div class="p-1.5 rounded-lg text-[10px] font-bold"
                             :class="msg.is_mine ? 'bg-emerald-700/60 text-emerald-100' : 'bg-slate-50 text-slate-700 border border-slate-100'">
                            🛍️ Terkait Pesanan #<span x-text="msg.order_id"></span>: <span x-text="msg.order_desc || ''"></span>
                        </div>
                    </template>

                    <!-- Message Text -->
                    <p class="text-xs leading-relaxed whitespace-pre-wrap font-normal" x-text="msg.message"></p>

                    <!-- Attachment Image (if any) -->
                    <template x-if="msg.attachment_url">
                        <div class="pt-1">
                            <a :href="msg.attachment_url" target="_blank" class="block rounded-xl overflow-hidden border border-black/10">
                                <img :src="msg.attachment_url" alt="Lampiran" class="max-h-48 w-full object-cover">
                            </a>
                        </div>
                    </template>
                </div>

                <!-- Read status / sub-caption -->
                <div class="mt-0.5 px-1 text-[9px] text-slate-400 font-mono">
                    <span x-text="msg.date"></span>
                    <template x-if="msg.is_mine">
                        <span x-text="msg.is_read ? ' • Dibaca Admin' : ' • Terkirim'"></span>
                    </template>
                </div>
            </div>
        </template>

        <div x-show="sending" class="flex justify-end animate-pulse">
            <div class="bg-emerald-500/80 text-white rounded-2xl px-3 py-2 text-xs">
                Mengirim pesan...
            </div>
        </div>

    </main>

    <!-- Bottom Input Box Container (Statis di Bawah) -->
    <footer class="shrink-0 z-50 bg-white border-t border-slate-200 px-4 py-3 shadow-lg">
        <div class="max-w-xl mx-auto space-y-2">

            <!-- Attachment Preview (if selected) -->
            <div x-show="imagePreview" x-cloak class="relative inline-block">
                <img :src="imagePreview" alt="Preview" class="h-20 rounded-xl object-cover border border-slate-200 shadow-sm">
                <button type="button" @click="clearAttachment()" class="absolute -top-2 -right-2 bg-slate-900 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shadow-md">✕</button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitMessage()" class="flex items-end gap-2">
                <!-- Attachment Button -->
                <label class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center cursor-pointer transition shrink-0" title="Lampirkan Foto Bukti (JPG, PNG, HEIC iPhone)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    <input type="file" accept="image/*,.heic,.heif,image/heic,image/heif" @change="previewFile($event)" class="hidden" x-ref="fileInput">
                </label>

                <!-- Textarea Message Input -->
                <div class="flex-1 min-w-0 bg-slate-50 border border-slate-200 rounded-2xl px-3 py-1.5 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 transition">
                    <textarea
                        x-model="newMessage"
                        rows="1"
                        placeholder="Ketik pesan atau kendala Anda di sini..."
                        @keydown.enter.exact.prevent="submitMessage()"
                        class="w-full text-xs bg-transparent focus:outline-none resize-none max-h-24 leading-relaxed py-1.5 text-slate-800 placeholder-slate-400"></textarea>
                </div>

                <!-- Send Button -->
                <button type="submit"
                        :disabled="sending || (!newMessage.trim() && !attachmentFile)"
                        class="w-10 h-10 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white flex items-center justify-center transition shrink-0 shadow-md shadow-emerald-600/25">
                    <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                </button>
            </form>
        </div>
    </footer>
</div>

<script>
    function supportChat(config) {
        return {
            fetchUrl: config.fetchUrl,
            sendUrl: config.sendUrl,
            csrfToken: config.csrfToken,
            messages: [],
            newMessage: '',
            attachmentFile: null,
            imagePreview: null,
            loading: false,
            sending: false,
            pollTimer: null,

            initChat() {
                this.loadMessages(true);
                // Poll pesan baru setiap 3.5 detik
                this.pollTimer = setInterval(() => {
                    this.loadMessages(false);
                }, 3500);
            },

            async loadMessages(shouldScroll = false) {
                try {
                    const res = await fetch(this.fetchUrl, {
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
                    console.error('Error fetching messages:', e);
                }
            },

            async submitMessage() {
                if (!this.newMessage.trim() && !this.attachmentFile) return;

                this.sending = true;
                const formData = new FormData();
                if (this.newMessage.trim()) formData.append('message', this.newMessage.trim());
                if (this.attachmentFile) formData.append('attachment', this.attachmentFile);

                try {
                    const res = await fetch(this.sendUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await res.json();
                    if (data.success) {
                        this.newMessage = '';
                        this.clearAttachment();
                        this.messages.push(data.message);
                        this.$nextTick(() => this.scrollToBottom());
                    } else {
                        alert(data.message || 'Gagal mengirim pesan.');
                    }
                } catch (e) {
                    alert('Terjadi kesalahan koneksi.');
                } finally {
                    this.sending = false;
                }
            },

            sendQuickMessage(text) {
                this.newMessage = text;
                this.submitMessage();
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
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.messagesContainer;
                    if (el) {
                        el.scrollTop = el.scrollHeight;
                    }
                });
            }
        };
    }
</script>
@endsection
