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
    <div class="bg-white border-2 border-slate-900 shadow-[8px_8px_0px_0px_rgba(15,23,42,1)] rounded-sm p-6 sm:p-8">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b-2 border-dashed border-slate-100 pb-6 mb-6">
            <div>
                <h3 class="font-display font-black text-2xl text-slate-900 uppercase tracking-tight">Antrian Verifikasi Jastiper</h3>
                <p class="text-xs text-slate-500 mt-1">Review dokumen KTP & Foto Selfie untuk mengaktifkan status akun mitra Jastiper.</p>
            </div>
            
            <form action="{{ route('admin.logout') }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2.5 rounded-sm border-2 border-slate-900 shadow-[2px_2px_0px_0px_rgba(244,63,94,1)] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px] transition duration-150 uppercase tracking-wider">
                    Keluar Admin
                </button>
            </form>
        </div>

        <!-- Alerts -->
        @if ($success_message)
            <div class="mb-6 bg-emerald-50 border-2 border-emerald-200 p-4 text-emerald-700 text-sm font-semibold rounded-sm flex items-start gap-2.5">
                <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $success_message }}</span>
            </div>
        @endif

        @if ($error_message)
            <div class="mb-6 bg-rose-50 border-2 border-rose-200 p-4 text-rose-700 text-sm font-semibold rounded-sm flex items-start gap-2.5">
                <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ $error_message }}</span>
            </div>
        @endif

        <!-- Filter & Search Panel -->
        <div class="flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between mb-6">
            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-2">
                <button 
                    wire:click="$set('filter_status', 'menunggu')" 
                    class="px-4 py-2 font-bold text-xs uppercase rounded-sm border-2 transition duration-150 {{ $filter_status === 'menunggu' ? 'bg-amber-500 border-slate-900 text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]' : 'bg-white border-slate-200 hover:border-slate-900 text-slate-600' }}"
                >
                    ⏳ Menunggu Review
                </button>
                <button 
                    wire:click="$set('filter_status', 'approved')" 
                    class="px-4 py-2 font-bold text-xs uppercase rounded-sm border-2 transition duration-150 {{ $filter_status === 'approved' ? 'bg-emerald-600 border-slate-900 text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]' : 'bg-white border-slate-200 hover:border-slate-900 text-slate-600' }}"
                >
                    ✅ Disetujui
                </button>
                <button 
                    wire:click="$set('filter_status', 'rejected')" 
                    class="px-4 py-2 font-bold text-xs uppercase rounded-sm border-2 transition duration-150 {{ $filter_status === 'rejected' ? 'bg-rose-600 border-slate-900 text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]' : 'bg-white border-slate-200 hover:border-slate-900 text-slate-600' }}"
                >
                    ❌ Ditolak
                </button>
                <button 
                    wire:click="$set('filter_status', 'all')" 
                    class="px-4 py-2 font-bold text-xs uppercase rounded-sm border-2 transition duration-150 {{ $filter_status === 'all' ? 'bg-slate-900 border-slate-900 text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]' : 'bg-white border-slate-200 hover:border-slate-900 text-slate-600' }}"
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
                    class="w-full bg-white border-2 border-slate-900 px-3.5 py-2.5 pl-10 rounded-sm shadow-[2px_2px_0px_0px_rgba(15,23,42,1)] text-xs font-semibold outline-none"
                >
                <div class="absolute left-3 top-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Grid Antrian Pengajuan -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($verifications as $v)
                <div class="bg-white border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition duration-150 rounded-sm p-5 flex flex-col justify-between space-y-4">
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-[9px] font-mono text-slate-400">{{ $v->created_at->format('d M Y - H:i') }}</span>
                            <span>
                                @if($v->status === 'menunggu')
                                    <span class="bg-amber-100 border border-amber-200 text-amber-700 text-[9px] font-bold px-2 py-0.5 rounded-sm">Menunggu</span>
                                @elseif($v->status === 'approved')
                                    <span class="bg-emerald-100 border border-emerald-200 text-emerald-700 text-[9px] font-bold px-2 py-0.5 rounded-sm">Approved</span>
                                @elseif($v->status === 'rejected')
                                    <span class="bg-rose-100 border border-rose-200 text-rose-700 text-[9px] font-bold px-2 py-0.5 rounded-sm">Rejected</span>
                                @endif
                            </span>
                        </div>

                        <div>
                            <h4 class="font-display font-black text-base text-slate-900">{{ $v->jastiper->name }}</h4>
                            <p class="text-[10px] text-slate-500 font-medium">📞 {{ $v->jastiper->phone_number }}</p>
                        </div>

                        <div class="bg-slate-50 border border-slate-200 p-2.5 rounded-sm text-[10px] text-slate-600 space-y-1">
                            <div>📍 Wilayah: <span class="font-bold text-slate-800">{{ $v->jastiper->wilayah?->name ?: 'N/A' }}</span></div>
                            <div>🎯 Radius: <span class="font-bold text-slate-800">{{ number_format($v->jastiper->radius_km, 1) }} KM</span></div>
                        </div>

                        @if($v->status === 'rejected' && $v->rejection_reason)
                            <div class="bg-rose-50 border border-rose-100 p-2.5 rounded-sm text-[10px] text-rose-700 font-semibold italic">
                                ❌ Alasan: "{{ $v->rejection_reason }}"
                            </div>
                        @endif

                        @if($v->status !== 'menunggu' && $v->reviewer)
                            <div class="text-[9px] text-slate-400">
                                Direview oleh: <b>{{ $v->reviewer->name }}</b> pada {{ $v->reviewed_at->format('d M Y') }}
                            </div>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            wire:click="selectVerification({{ $v->id }})" 
                            class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-center py-2.5 rounded-sm text-[10px] uppercase border border-slate-900 transition tracking-wider"
                        >
                            🔎 Review Dokumen & Aksi
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
            <div class="bg-white border-2 border-slate-900 shadow-[8px_8px_0px_0px_rgba(15,23,42,1)] rounded-sm max-w-4xl w-full p-6 sm:p-8 space-y-6 relative">
                
                <!-- Close Button -->
                <button 
                    type="button" 
                    wire:click="closeDetail" 
                    class="absolute top-4 right-4 text-slate-500 hover:text-slate-900 transition font-bold"
                >
                    ✕
                </button>

                <!-- Modal Header -->
                <div class="border-b-2 border-dashed border-slate-100 pb-4">
                    <h3 class="font-display font-black text-xl text-slate-900 uppercase tracking-tight">Review Dokumen Verifikasi</h3>
                    <p class="text-xs text-slate-500 mt-1">Review berkas Jastiper: <b>{{ $selected_verification->jastiper->name }}</b> ({{ $selected_verification->jastiper->phone_number }})</p>
                </div>

                <!-- Gambar Dokumen (Side-by-Side) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- KTP Column -->
                    <div class="space-y-2 text-center">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider text-left">Foto KTP Asli</label>
                        <div class="border-2 border-slate-200 rounded-sm overflow-hidden p-2 bg-slate-50">
                            <a href="{{ asset('storage/' . $selected_verification->ktp_image) }}" target="_blank">
                                <img 
                                    src="{{ asset('storage/' . $selected_verification->ktp_image) }}" 
                                    class="max-h-80 mx-auto object-contain hover:scale-105 transition duration-150"
                                    alt="Foto KTP"
                                >
                            </a>
                        </div>
                        <span class="text-[9px] text-slate-400 block mt-1">Klik gambar untuk melihat resolusi penuh ↗</span>
                    </div>

                    <!-- Selfie Column -->
                    <div class="space-y-2 text-center">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider text-left">Foto Selfie + KTP</label>
                        <div class="border-2 border-slate-200 rounded-sm overflow-hidden p-2 bg-slate-50">
                            <a href="{{ asset('storage/' . $selected_verification->selfie_image) }}" target="_blank">
                                <img 
                                    src="{{ asset('storage/' . $selected_verification->selfie_image) }}" 
                                    class="max-h-80 mx-auto object-contain hover:scale-105 transition duration-150"
                                    alt="Foto Selfie memegang KTP"
                                >
                            </a>
                        </div>
                        <span class="text-[9px] text-slate-400 block mt-1">Klik gambar untuk melihat resolusi penuh ↗</span>
                    </div>
                </div>

                <!-- Panel Aksi Evaluasi -->
                <div class="bg-slate-50 border border-slate-200 p-6 rounded-sm space-y-4">
                    <h4 class="font-bold text-xs text-slate-700 uppercase tracking-wide">Evaluasi Admin JastipKuy</h4>
                    
                    @if($selected_verification->status === 'menunggu')
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                            
                            <!-- Approve Box (Left) -->
                            <div class="md:col-span-5 border-b md:border-b-0 md:border-r border-slate-200 pb-4 md:pb-0 md:pr-6 flex flex-col justify-end h-full">
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block mb-2">Aksi 1: Terima Akun</span>
                                <button 
                                    type="button" 
                                    wire:click="approveVerification({{ $selected_verification->id }})" 
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center py-3 rounded-sm border-2 border-slate-900 shadow-[3px_3px_0px_0px_rgba(15,23,42,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition duration-150 uppercase tracking-wider text-xs"
                                >
                                    ✅ SETUJUI (APPROVE) MITRA
                                </button>
                            </div>

                            <!-- Reject Box (Right) -->
                            <div class="md:col-span-7 space-y-3">
                                <span class="text-[9px] text-rose-500 font-bold uppercase tracking-wider block">Aksi 2: Tolak Pengajuan</span>
                                
                                <div class="space-y-1.5">
                                    <label for="modal_rejection_reason" class="block text-[9px] font-bold text-slate-600 uppercase">Alasan Penolakan</label>
                                    <textarea 
                                        id="modal_rejection_reason" 
                                        wire:model="rejection_reason" 
                                        rows="2"
                                        placeholder="Misal: Foto KTP buram atau wajah selfie terpotong..." 
                                        class="w-full bg-white border-2 border-slate-900 text-slate-900 text-xs px-3 py-2 rounded-sm outline-none focus:border-rose-600 transition"
                                    ></textarea>
                                    @error('rejection_reason') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
                                </div>

                                <button 
                                    type="button" 
                                    wire:click="rejectVerification({{ $selected_verification->id }})" 
                                    class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-3 rounded-sm border-2 border-slate-900 shadow-[3px_3px_0px_0px_rgba(15,23,42,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition duration-150 uppercase tracking-wider text-xs"
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
                                class="bg-white border-2 border-slate-900 px-4 py-2 font-bold uppercase text-[10px] shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]"
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
