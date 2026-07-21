<?php

use Livewire\Component;
use App\Models\JastiperVerification;
use App\Models\Jastiper;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    public string $search = '';
    public string $filter_status = 'menunggu'; // menunggu, approved, rejected, all
    
    public $selected_id = null;
    public string $rejection_reason = '';
    
    public string $success_message = '';
    public string $error_message = '';

    protected function rules()
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:5'],
        ];
    }

    protected function messages()
    {
        return [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 5 karakter.',
        ];
    }

    public function selectVerification($id)
    {
        $this->selected_id = $id;
        $this->rejection_reason = '';
        $this->success_message = '';
        $this->error_message = '';
    }

    public function closeDetail()
    {
        $this->selected_id = null;
        $this->rejection_reason = '';
    }

    public function approveVerification($id)
    {
        $verification = JastiperVerification::findOrFail($id);
        $jastiper = $verification->jastiper;
        $adminId = Auth::guard('admin')->id() ?? 1;

        try {
            // Update JastiperVerification
            $verification->update([
                'status' => 'approved',
                'rejection_reason' => null,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            // Update Jastiper verification status
            $jastiper->update([
                'verification_status' => 'approved',
            ]);

            // Kirim WhatsApp
            $msg = "Halo *{$jastiper->name}*!\n\nPengajuan verifikasi akun Jastiper Anda di *JastipKuy* telah *DISETUJUI* oleh Admin. Akun Anda kini aktif dan Anda siap untuk menerima tawaran titipan belanjaan! 🚀";
            WhatsAppService::sendMessage($jastiper->phone_number, $msg);

            $this->success_message = "Akun Jastiper {$jastiper->name} berhasil disetujui (Approved)!";
            $this->selected_id = null;
        } catch (\Exception $e) {
            Log::error("Admin Verification Approval Error: " . $e->getMessage());
            $this->error_message = "Gagal menyetujui verifikasi.";
        }
    }

    public function rejectVerification($id)
    {
        $this->validate();

        $verification = JastiperVerification::findOrFail($id);
        $jastiper = $verification->jastiper;
        $adminId = Auth::guard('admin')->id() ?? 1;

        try {
            // Update JastiperVerification
            $verification->update([
                'status' => 'rejected',
                'rejection_reason' => $this->rejection_reason,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            // Update Jastiper verification status
            $jastiper->update([
                'verification_status' => 'rejected',
            ]);

            // Kirim WhatsApp
            $msg = "Halo *{$jastiper->name}*!\n\nPengajuan verifikasi akun Jastiper Anda di *JastipKuy* ditolak oleh Admin dengan alasan:\n\n_\"{$this->rejection_reason}\"_\n\nSilakan masuk kembali ke Dashboard Jastiper dan ajukan ulang dengan dokumen/foto yang lebih jelas. Terima kasih.";
            WhatsAppService::sendMessage($jastiper->phone_number, $msg);

            $this->success_message = "Akun Jastiper {$jastiper->name} berhasil ditolak (Rejected) dengan alasan.";
            $this->selected_id = null;
            $this->rejection_reason = '';
        } catch (\Exception $e) {
            Log::error("Admin Verification Rejection Error: " . $e->getMessage());
            $this->error_message = "Gagal menolak verifikasi.";
        }
    }
};
?>

<div class="space-y-6">
    @php
        $verifications = \App\Models\JastiperVerification::with(['jastiper.wilayah', 'reviewer'])
            ->when($filter_status !== 'all', function ($query) use ($filter_status) {
                $query->where('status', $filter_status);
            })
            ->when($search, function ($query) use ($search) {
                $query->whereHas('jastiper', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('phone_number', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->get();

        $selected_verification = $selected_id ? \App\Models\JastiperVerification::with(['jastiper.wilayah'])->find($selected_id) : null;
    @endphp

    <!-- Konten Dashboard Admin -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-sm">
        
        <!-- Header -->
        <div class="border-b border-slate-100 pb-4 mb-6">
            <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Antrian Verifikasi Jastiper</h3>
            <p class="text-xs text-slate-400 mt-1">Review dokumen KTP & Foto Selfie untuk mengaktifkan status akun mitra Jastiper.</p>
        </div>

        <!-- Alerts -->
        @if ($success_message)
            <div class="mb-6 bg-emerald-50 border border-emerald-100 p-4 text-emerald-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-4.5 h-4.5 mt-0.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $success_message }}</span>
            </div>
        @endif

        @if ($error_message)
            <div class="mb-6 bg-rose-50 border border-rose-100 p-4 text-rose-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-4.5 h-4.5 mt-0.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ $error_message }}</span>
            </div>
        @endif

        <!-- Filter & Search Panel -->
        <div class="flex flex-col xl:flex-row gap-4 items-stretch xl:items-center justify-between mb-6">
            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-2">
                <button 
                    wire:click="$set('filter_status', 'menunggu')" 
                    class="px-4 py-2 font-bold text-xs uppercase rounded-full border transition duration-150"
                    style="{{ $filter_status === 'menunggu' ? 'background-color: #f59e0b; border-color: #f59e0b; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;' }}"
                >
                    ⏳ Menunggu Review
                </button>
                <button 
                    wire:click="$set('filter_status', 'approved')" 
                    class="px-4 py-2 font-bold text-xs uppercase rounded-full border transition duration-150"
                    style="{{ $filter_status === 'approved' ? 'background-color: #10b981; border-color: #10b981; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;' }}"
                >
                    ✅ Disetujui
                </button>
                <button 
                    wire:click="$set('filter_status', 'rejected')" 
                    class="px-4 py-2 font-bold text-xs uppercase rounded-full border transition duration-150"
                    style="{{ $filter_status === 'rejected' ? 'background-color: #e11d48; border-color: #e11d48; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;' }}"
                >
                    ❌ Ditolak
                </button>
                <button 
                    wire:click="$set('filter_status', 'all')" 
                    class="px-4 py-2 font-bold text-xs uppercase rounded-full border transition duration-150"
                    style="{{ $filter_status === 'all' ? 'background-color: #0f172a; border-color: #0f172a; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;' }}"
                >
                    🌐 Semua
                </button>
            </div>

            <!-- Search Input -->
            <div class="relative flex-grow max-w-md">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari berdasarkan nama atau no HP..." 
                    class="w-full bg-[#F3F4F6] border border-slate-200 text-slate-750 pl-10 pr-4 py-2 rounded-full text-xs font-semibold focus:outline-none focus:bg-white focus:border-rose-500 transition duration-150"
                >
                <div class="absolute left-3.5 top-2.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Grid Antrian Pengajuan -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse($verifications as $v)
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 flex flex-col justify-between space-y-4 shadow-sm hover:shadow-md transition duration-150">
                    
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-mono text-slate-400">{{ $v->created_at->format('d M Y - H:i') }}</span>
                            <span>
                                @if($v->status === 'menunggu')
                                    <span class="bg-amber-50 border border-amber-100 text-amber-600 text-[9px] font-bold px-2.5 py-0.5 rounded-full uppercase">Menunggu</span>
                                @elseif($v->status === 'approved')
                                    <span class="bg-emerald-50 border border-emerald-100 text-emerald-600 text-[9px] font-bold px-2.5 py-0.5 rounded-full uppercase">Approved</span>
                                @elseif($v->status === 'rejected')
                                    <span class="bg-rose-50 border border-rose-100 text-rose-600 text-[9px] font-bold px-2.5 py-0.5 rounded-full uppercase">Rejected</span>
                                @endif
                            </span>
                        </div>

                        <div>
                            <h4 class="font-display font-black text-sm text-slate-805 leading-snug">{{ $v->jastiper->name }}</h4>
                            <p class="text-xs text-slate-400 font-semibold mt-0.5">📞 {{ $v->jastiper->phone_number }}</p>
                        </div>

                        <div class="bg-[#F3F4F6] border border-slate-100 p-3 rounded-2xl text-xs text-slate-500 space-y-1.5">
                            <div>📍 Wilayah: <span class="font-bold text-slate-700">{{ $v->jastiper->wilayah?->name ?: 'N/A' }}</span></div>
                            <div>🎯 Radius: <span class="font-bold text-slate-700">{{ number_format($v->jastiper->radius_km, 1) }} KM</span></div>
                        </div>

                        @if($v->status === 'rejected' && $v->rejection_reason)
                            <div class="bg-rose-50 border border-rose-100 p-2.5 rounded-2xl text-xs text-rose-700 font-semibold italic">
                                ❌ Alasan: "{{ $v->rejection_reason }}"
                            </div>
                        @endif

                        @if($v->status !== 'menunggu' && $v->reviewer)
                            <div class="text-[10px] text-slate-400">
                                Direview oleh: <b>{{ $v->reviewer->name }}</b> pada {{ $v->reviewed_at->format('d M Y') }}
                            </div>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            wire:click="selectVerification({{ $v->id }})" 
                            class="w-full bg-slate-950 hover:bg-slate-800 text-white font-bold text-center py-2.5 rounded-full text-xs uppercase border border-slate-900 transition tracking-wider shadow-sm"
                        >
                            🔎 Review Dokumen
                        </button>
                    </div>

                </div>
            @empty
                <div class="col-span-full py-16 text-center">
                    <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h4 class="font-bold text-sm text-slate-700">Tidak Ada Antrian Verifikasi</h4>
                    <p class="text-xs text-slate-400 mt-1">Belum ada pengajuan verifikasi baru dengan filter status "{{ $filter_status }}".</p>
                </div>
            @endforelse
        </div>

    </div>

    <!-- MODAL DETAIL / AKSI VERIFIKASI (TAMPIL DI LAYER ATAS) -->
    @if ($selected_verification)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-4xl w-full p-6 sm:p-8 space-y-6 relative shadow-lg animate-fade-in">
                
                <!-- Close Button -->
                <button 
                    type="button" 
                    wire:click="closeDetail" 
                    class="absolute top-5 right-5 text-slate-400 hover:text-slate-750 transition font-extrabold text-sm"
                >
                    ✕
                </button>

                <!-- Modal Header -->
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="font-display font-black text-lg text-slate-800 uppercase tracking-wider">Review Dokumen Verifikasi</h3>
                    <p class="text-xs text-slate-400 mt-1">Review berkas Jastiper: <b>{{ $selected_verification->jastiper->name }}</b> ({{ $selected_verification->jastiper->phone_number }})</p>
                </div>

                <!-- Gambar Dokumen (Side-by-Side) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- KTP Column -->
                    <div class="space-y-2 text-center">
                        <label class="block text-xs font-bold text-slate-755 uppercase tracking-wider text-left">Foto KTP Asli</label>
                        <div class="border border-slate-200 rounded-3xl overflow-hidden p-2.5 bg-slate-50 shadow-inner">
                            <a href="{{ asset('storage/' . $selected_verification->ktp_image) }}" target="_blank">
                                <img 
                                    src="{{ asset('storage/' . $selected_verification->ktp_image) }}" 
                                    class="max-h-80 mx-auto object-contain hover:scale-105 transition duration-150 rounded-2xl"
                                    alt="Foto KTP"
                                >
                            </a>
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-1">Klik gambar untuk melihat resolusi penuh ↗</span>
                    </div>

                    <!-- Selfie Column -->
                    <div class="space-y-2 text-center">
                        <label class="block text-xs font-bold text-slate-755 uppercase tracking-wider text-left">Foto Selfie + KTP</label>
                        <div class="border border-slate-200 rounded-3xl overflow-hidden p-2.5 bg-slate-50 shadow-inner">
                            <a href="{{ asset('storage/' . $selected_verification->selfie_image) }}" target="_blank">
                                <img 
                                    src="{{ asset('storage/' . $selected_verification->selfie_image) }}" 
                                    class="max-h-80 mx-auto object-contain hover:scale-105 transition duration-150 rounded-2xl"
                                    alt="Foto Selfie memegang KTP"
                                >
                            </a>
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-1">Klik gambar untuk melihat resolusi penuh ↗</span>
                    </div>
                </div>

                <!-- Panel Aksi Evaluasi -->
                <div class="bg-slate-50 border border-slate-200 p-6 rounded-3xl space-y-4">
                    <h4 class="font-bold text-xs text-slate-705 uppercase tracking-wide">Evaluasi Admin JastipKuy</h4>
                    
                    @if($selected_verification->status === 'menunggu')
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                            
                            <!-- Approve Box (Left) -->
                            <div class="md:col-span-5 border-b md:border-b-0 md:border-r border-slate-200 pb-4 md:pb-0 md:pr-6 flex flex-col justify-end h-full">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mb-2">Aksi 1: Terima Akun</span>
                                <button 
                                    type="button" 
                                    wire:click="approveVerification({{ $selected_verification->id }})" 
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center py-2.5 rounded-full transition shadow-md shadow-emerald-600/10 uppercase tracking-wider text-xs border border-emerald-500"
                                >
                                    ✅ SETUJUI (APPROVE) MITRA
                                </button>
                            </div>

                            <!-- Reject Box (Right) -->
                            <div class="md:col-span-7 space-y-3">
                                <span class="text-[10px] text-rose-500 font-bold uppercase tracking-wider block">Aksi 2: Tolak Pengajuan</span>
                                
                                <div class="space-y-1.5">
                                    <label for="modal_rejection_reason" class="block text-[10px] font-bold text-slate-600 uppercase">Alasan Penolakan</label>
                                    <textarea 
                                        id="modal_rejection_reason" 
                                        wire:model="rejection_reason" 
                                        rows="2"
                                        placeholder="Misal: Foto KTP buram atau wajah selfie terpotong..." 
                                        class="w-full bg-white border border-slate-200 text-slate-900 text-xs px-3.5 py-2.5 rounded-2xl outline-none focus:border-rose-600 transition"
                                    ></textarea>
                                    @error('rejection_reason') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <button 
                                    type="button" 
                                    wire:click="rejectVerification({{ $selected_verification->id }})" 
                                    class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-2.5 rounded-full transition shadow-md shadow-rose-600/10 uppercase tracking-wider text-xs border border-rose-500"
                                >
                                    ❌ TOLAK (REJECT) PENGAJUAN
                                </button>
                            </div>

                        </div>
                    @else
                        <!-- Tampilan Jika Sudah Direview -->
                        <div class="flex justify-between items-center text-xs">
                            <div>
                                Status Saat Ini: 
                                <span class="font-bold uppercase {{ $selected_verification->status === 'approved' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $selected_verification->status }}
                                </span>
                            </div>
                            <button 
                                type="button" 
                                wire:click="closeDetail" 
                                class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs px-6 py-2.5 rounded-full transition"
                            >
                                Tutup Halaman Detail
                            </button>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    @endif
</div>
