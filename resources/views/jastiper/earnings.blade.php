@extends('layouts.support')

@section('title', 'Rekap Pendapatan')

@section('content')

<script>
    function jastiperNotifyEarnings(message, success = true) {
        const container = document.getElementById('toast-container');
        if (!container) { alert(message); return; }

        const toast = document.createElement('div');
        toast.className = "pointer-events-auto bg-slate-900 text-white border-2 border-slate-900 p-4 rounded-sm flex items-center gap-3 animate-toast-slide-in text-xs font-bold tracking-wide transform transition-all duration-300"
            + (success ? " shadow-[4px_4px_0px_0px_rgba(16,185,129,1)]" : " shadow-[4px_4px_0px_0px_rgba(244,63,94,1)]");
        toast.innerHTML = `
            <span class="text-base">${success ? '✅' : '⚠️'}</span>
            <div><p class="font-sans font-semibold text-slate-100 normal-case">${message}</p></div>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // Alpine component untuk halaman rekap pendapatan jastiper. Mengikuti pola
    // yang sama dengan jastiperDashboard() di dashboard/jastiper.blade.php:
    // fetch() JSON untuk render awal + toggle harian/mingguan, TANPA Livewire
    // (brief Sprint 8 Bagian 3 — tech split project ini).
    function jastiperEarnings(config) {
        return {
            period: 'harian',
            loading: false,
            saldo: 0,
            breakdown: [],
            totalNet: 0,
            pendingWithdraw: null,
            recentWithdraws: [],
            withdrawModalOpen: false,
            submitting: false,
            form: { amount: '', bank_name: '', bank_account_number: '', bank_account_holder: '' },
            dataUrl: config.dataUrl,
            withdrawUrl: config.withdrawUrl,
            csrfToken: config.csrfToken,

            init() {
                this.fetchData();
            },

            async fetchData() {
                this.loading = true;
                try {
                    const url = new URL(this.dataUrl, window.location.origin);
                    url.searchParams.set('period', this.period);

                    const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();

                    this.saldo = data.saldo || 0;
                    this.breakdown = data.breakdown || [];
                    this.totalNet = data.total_net || 0;
                    this.pendingWithdraw = data.pending_withdraw || null;
                    this.recentWithdraws = data.recent_withdraws || [];
                } catch (e) {
                    jastiperNotifyEarnings('Gagal memuat data rekap. Coba reload halaman.', false);
                } finally {
                    this.loading = false;
                }
            },

            switchPeriod(p) {
                this.period = p;
                this.fetchData();
            },

            openWithdrawModal() {
                this.form.amount = '';
                this.withdrawModalOpen = true;
            },
            closeWithdrawModal() {
                this.withdrawModalOpen = false;
            },

            async submitWithdraw() {
                this.submitting = true;
                try {
                    const res = await fetch(this.withdrawUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify(this.form),
                    });
                    const data = await res.json();

                    if (data.success) {
                        jastiperNotifyEarnings(data.message || 'Pengajuan withdraw terkirim.', true);
                        this.withdrawModalOpen = false;
                        this.fetchData();
                    } else {
                        jastiperNotifyEarnings(data.message || 'Pengajuan withdraw gagal.', false);
                    }
                } catch (e) {
                    jastiperNotifyEarnings('Terjadi kesalahan jaringan.', false);
                } finally {
                    this.submitting = false;
                }
            },

            formatRupiah(value) {
                return 'Rp' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
            },

            statusLabel(status) {
                return { menunggu: 'Menunggu', disetujui: 'Disetujui', ditolak: 'Ditolak' }[status] || status;
            },
            statusClass(status) {
                return {
                    menunggu: 'bg-amber-50 text-amber-700 border-amber-200',
                    disetujui: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    ditolak: 'bg-rose-50 text-rose-700 border-rose-200',
                }[status] || 'bg-slate-50 text-slate-700 border-slate-200';
            },
        };
    }
</script>

<div class="min-h-screen bg-[#F3F4F6] pb-16"
    x-data='jastiperEarnings({
        dataUrl: @json(route("jastiper.earnings.data")),
        withdrawUrl: @json(route("jastiper.earnings.withdraw")),
        csrfToken: @json(csrf_token()),
    })'
>
    <div class="max-w-md mx-auto px-4 pt-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center gap-3">
            <a href="{{ route('jastiper.dashboard') }}" class="w-9 h-9 flex items-center justify-center rounded-full bg-white border border-slate-200 shadow-sm shrink-0">
                <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1 class="font-extrabold text-sm text-slate-900">Rekap Pendapatan</h1>
                <p class="text-[10px] text-slate-500">Riwayat komisi & saldo wallet Anda</p>
            </div>
        </div>

        <!-- Saldo Card -->
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 text-white rounded-3xl p-5 shadow-lg border border-emerald-500/20 relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
            <span class="text-[9px] uppercase font-bold text-emerald-100 tracking-wider block">Saldo Wallet</span>
            <div class="text-3xl font-black font-display tracking-tight mt-1" x-text="formatRupiah(saldo)"></div>

            <button @click="openWithdrawModal()" :disabled="!!pendingWithdraw"
                class="mt-4 bg-slate-950/40 hover:bg-slate-950/60 disabled:opacity-50 disabled:cursor-not-allowed border border-white/20 text-white text-[10px] font-bold px-4 py-2.5 rounded-full transition uppercase tracking-wide w-full">
                <span x-show="!pendingWithdraw">Tarik Saldo</span>
                <span x-show="pendingWithdraw" x-cloak>Pengajuan Sedang Diproses</span>
            </button>
        </div>

        <!-- Pending Withdraw Banner -->
        <template x-if="pendingWithdraw">
            <div class="p-4 rounded-3xl border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] bg-amber-50" x-cloak>
                <p class="text-[10px] font-bold text-amber-800 uppercase tracking-wide">Pengajuan Withdraw Menunggu</p>
                <p class="text-xs text-slate-600 mt-1" x-text="'Rp' + formatRupiah(pendingWithdraw?.amount).replace('Rp', '') + ' sedang menunggu persetujuan admin.'"></p>
            </div>
        </template>

        <!-- Toggle Harian/Mingguan -->
        <div class="flex bg-white border border-slate-200 rounded-full p-1 shadow-sm">
            <button @click="switchPeriod('harian')" :class="period === 'harian' ? 'bg-slate-900 text-white' : 'text-slate-500'" class="flex-1 text-[10px] font-bold uppercase tracking-wide py-2 rounded-full transition">Harian</button>
            <button @click="switchPeriod('mingguan')" :class="period === 'mingguan' ? 'bg-slate-900 text-white' : 'text-slate-500'" class="flex-1 text-[10px] font-bold uppercase tracking-wide py-2 rounded-full transition">Mingguan</button>
        </div>

        <!-- Breakdown List -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm divide-y divide-slate-100">
            <div class="p-4 flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Total Bersih</span>
                <span class="text-sm font-black text-emerald-600" x-text="formatRupiah(totalNet)"></span>
            </div>

            <template x-if="loading">
                <div class="p-6 text-center text-[11px] text-slate-400">Memuat data...</div>
            </template>

            <template x-if="!loading && breakdown.length === 0">
                <div class="p-6 text-center text-[11px] text-slate-400">Belum ada pendapatan pada periode ini.</div>
            </template>

            <template x-for="row in breakdown" :key="row.label">
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-800" x-text="row.label"></p>
                        <p class="text-[10px] text-slate-400" x-text="row.orders_count + ' pesanan'"></p>
                    </div>
                    <span class="text-xs font-black text-slate-900" x-text="formatRupiah(row.net_amount)"></span>
                </div>
            </template>
        </div>

        <!-- Riwayat Withdraw -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm">
            <div class="p-4 border-b border-slate-100">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Riwayat Withdraw</span>
            </div>
            <template x-if="recentWithdraws.length === 0">
                <div class="p-6 text-center text-[11px] text-slate-400">Belum ada pengajuan withdraw.</div>
            </template>
            <div class="divide-y divide-slate-100">
                <template x-for="w in recentWithdraws" :key="w.id">
                    <div class="p-4 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800" x-text="formatRupiah(w.amount)"></p>
                            <p class="text-[9px] text-slate-400 truncate" x-text="w.admin_note || '—'"></p>
                        </div>
                        <span class="text-[9px] font-bold px-2.5 py-1 rounded-full border shrink-0" :class="statusClass(w.status)" x-text="statusLabel(w.status)"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Withdraw -->
    <div x-show="withdrawModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.outside="closeWithdrawModal()" class="bg-white rounded-3xl w-full max-w-sm p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-extrabold text-sm text-slate-900">Ajukan Tarik Saldo</h3>
                <button @click="closeWithdrawModal()" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form @submit.prevent="submitWithdraw()" class="space-y-3">
                <div>
                    <label class="text-[9px] font-bold text-slate-500 uppercase tracking-wide block mb-1">Jumlah (Rp)</label>
                    <input type="number" min="1" step="1" x-model="form.amount" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="text-[9px] font-bold text-slate-500 uppercase tracking-wide block mb-1">Nama Bank</label>
                    <input type="text" x-model="form.bank_name" required placeholder="cth. BCA" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="text-[9px] font-bold text-slate-500 uppercase tracking-wide block mb-1">Nomor Rekening</label>
                    <input type="text" x-model="form.bank_account_number" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="text-[9px] font-bold text-slate-500 uppercase tracking-wide block mb-1">Nama Pemilik Rekening</label>
                    <input type="text" x-model="form.bank_account_holder" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <button type="submit" :disabled="submitting" class="w-full bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs py-3 rounded-full uppercase tracking-wide transition">
                    <span x-show="!submitting">Kirim Pengajuan</span>
                    <span x-show="submitting" x-cloak>Mengirim...</span>
                </button>
            </form>
        </div>
    </div>
</div>

@endsection