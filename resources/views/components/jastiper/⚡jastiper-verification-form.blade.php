<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\JastiperVerification;
use App\Models\Jastiper;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    use WithFileUploads;

    // File Uploads
    public $ktp_image;
    public $selfie_image;

    // Simulation Panel Input
    public string $rejection_reason_input = '';

    // Messages
    public string $success_message = '';
    public string $error_message = '';

    protected function rules()
    {
        return [
            'ktp_image' => ['required', 'image', 'max:2048'], // Max 2MB
            'selfie_image' => ['required', 'image', 'max:2048'], // Max 2MB
        ];
    }

    protected function messages()
    {
        return [
            'ktp_image.required' => 'Foto KTP wajib diunggah.',
            'ktp_image.image' => 'File KTP harus berupa gambar.',
            'ktp_image.max' => 'Ukuran foto KTP maksimal 2MB.',
            'selfie_image.required' => 'Foto Selfie wajib diunggah.',
            'selfie_image.image' => 'File Selfie harus berupa gambar.',
            'selfie_image.max' => 'Ukuran foto Selfie maksimal 2MB.',
        ];
    }

    /**
     * Submit dokumen verifikasi oleh Jastiper
     */
    public function submitVerification()
    {
        $this->validate();

        $jastiper = Auth::guard('jastiper')->user();

        try {
            // Upload berkas ke storage public
            $ktpPath = $this->ktp_image->store('verifications/ktp', 'public');
            $selfiePath = $this->selfie_image->store('verifications/selfie', 'public');

            // Simpan data pengajuan ke database
            JastiperVerification::create([
                'jastiper_id' => $jastiper->id,
                'ktp_image' => $ktpPath,
                'selfie_image' => $selfiePath,
                'status' => 'menunggu',
            ]);

            // Update status verifikasi Jastiper ke 'menunggu'
            $jastiper->verification_status = 'menunggu';
            $jastiper->save();

            $this->success_message = 'Dokumen verifikasi berhasil dikirim! Mohon tunggu review dari Admin.';
            $this->reset(['ktp_image', 'selfie_image']);
            
            // Redirect / reload
            return redirect()->route('jastiper.verification');
        } catch (\Exception $e) {
            Log::error("Error Jastiper Verification Submit: " . $e->getMessage());
            $this->error_message = 'Gagal menyimpan dokumen. Silakan coba lagi.';
        }
    }

    /**
     * Simulasi langsung aksi persetujuan Admin (Approve/Reject)
     */
    public function adminSimulateStatus(string $status, ?string $reason = null)
    {
        $jastiper = Auth::guard('jastiper')->user();
        $latest = $jastiper->latestVerification;

        if (!$latest) {
            $this->error_message = 'Tidak ada berkas verifikasi aktif untuk direview.';
            return;
        }

        // Simulasikan HTTP request ke endpoint admin
        $latest->status = $status;
        $latest->rejection_reason = $status === 'rejected' ? $reason : null;
        $latest->reviewed_by = \App\Models\Admin::first()?->id ?? 1;
        $latest->reviewed_at = now();
        $latest->save();

        $jastiper->verification_status = $status;
        $jastiper->save();

        // Kirim WhatsApp Notification
        if ($status === 'approved') {
            $msg = "Halo *{$jastiper->name}*!\n\nPengajuan verifikasi akun Jastiper Anda di *JastipKuy* telah *DISETUJUI* oleh Admin. Akun Anda kini aktif dan Anda siap untuk menerima tawaran titipan belanjaan! 🚀";
        } else {
            $msg = "Halo *{$jastiper->name}*!\n\nPengajuan verifikasi akun Jastiper Anda di *JastipKuy* ditolak oleh Admin dengan alasan:\n\n_\"{$reason}\"_\n\nSilakan masuk kembali ke Dashboard Jastiper dan ajukan ulang dengan dokumen/foto yang lebih jelas. Terima kasih.";
        }

        WhatsAppService::sendMessage($jastiper->phone_number, $msg);

        $this->success_message = 'Simulasi Admin Berhasil! Status diupdate dan notifikasi WA dikirim.';
        $this->rejection_reason_input = '';

        return redirect()->route('jastiper.verification');
    }

    /**
     * Memicu Jastiper untuk mengajukan verifikasi ulang (setelah ditolak)
     */
    public function triggerResubmission()
    {
        $jastiper = Auth::guard('jastiper')->user();
        $jastiper->verification_status = 'belum';
        $jastiper->save();

        return redirect()->route('jastiper.verification');
    }
};
?>

<div class="space-y-8">
    @php
        $jastiper = Auth::guard('jastiper')->user();
        $latest = $jastiper->latestVerification;
    @endphp

    <!-- Card Status & Uploader -->
    <div class="bg-white border-2 border-slate-900 shadow-[8px_8px_0px_0px_rgba(15,23,42,1)] rounded-sm p-6 sm:p-8">
        
        <!-- Alerts -->
        @if ($success_message || session()->has('success'))
            <div class="mb-6 bg-emerald-50 border-2 border-emerald-200 p-4 text-emerald-700 text-sm font-semibold rounded-sm flex items-start gap-2.5">
                <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $success_message ?: session('success') }}</span>
            </div>
        @endif

        @if ($error_message)
            <div class="mb-6 bg-rose-50 border-2 border-rose-200 p-4 text-rose-700 text-sm font-semibold rounded-sm flex items-start gap-2.5 animate-pulse">
                <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ $error_message }}</span>
            </div>
        @endif

        <!-- KONDISI A: BELUM VERIFIKASI -->
        @if ($jastiper->verification_status === 'belum')
            <div class="border-b-2 border-dashed border-slate-100 pb-4 mb-6">
                <h3 class="font-display font-black text-xl text-slate-900 uppercase tracking-tight">Kirim Dokumen Verifikasi</h3>
                <p class="text-xs text-slate-500 mt-1">Silakan unggah foto KTP asli dan foto selfie memegang KTP Anda. Pastikan tulisan dan wajah terlihat jelas tanpa blur.</p>
            </div>

            <form wire:submit.prevent="submitVerification" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- KTP Upload -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Foto KTP Asli</label>
                        
                        <div class="border-2 border-dashed border-slate-300 hover:border-slate-900 rounded-sm p-6 text-center cursor-pointer relative bg-slate-50/50 hover:bg-white transition duration-150">
                            <input type="file" wire:model="ktp_image" id="ktp_image_input" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            
                            @if ($ktp_image)
                                <div class="flex flex-col items-center">
                                    <img src="{{ $ktp_image->temporaryUrl() }}" class="max-h-40 object-contain rounded-sm border border-slate-200">
                                    <span class="text-[10px] text-emerald-600 font-bold mt-2">✓ Foto KTP terpilih</span>
                                </div>
                            @else
                                <div class="flex flex-col items-center py-4">
                                    <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    <span class="text-xs font-bold text-slate-700">Pilih Foto KTP</span>
                                    <span class="text-[9px] text-slate-400 mt-1">PNG, JPG atau JPEG (Maks 2MB)</span>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Uploading progress bar -->
                        <div wire:loading wire:target="ktp_image" class="w-full">
                            <div class="h-1 bg-rose-200 rounded-sm overflow-hidden">
                                <div class="h-full bg-rose-600 animate-pulse" style="width: 70%"></div>
                            </div>
                            <span class="text-[9px] text-rose-500 font-bold mt-1 block">Mengunggah gambar KTP...</span>
                        </div>
                        @error('ktp_image') <span class="text-rose-600 text-xs block font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Selfie Upload -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Foto Selfie dengan KTP</label>
                        
                        <div class="border-2 border-dashed border-slate-300 hover:border-slate-900 rounded-sm p-6 text-center cursor-pointer relative bg-slate-50/50 hover:bg-white transition duration-150">
                            <input type="file" wire:model="selfie_image" id="selfie_image_input" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            
                            @if ($selfie_image)
                                <div class="flex flex-col items-center">
                                    <img src="{{ $selfie_image->temporaryUrl() }}" class="max-h-40 object-contain rounded-sm border border-slate-200">
                                    <span class="text-[10px] text-emerald-600 font-bold mt-2">✓ Foto Selfie terpilih</span>
                                </div>
                            @else
                                <div class="flex flex-col items-center py-4">
                                    <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-xs font-bold text-slate-700">Pilih Foto Selfie + KTP</span>
                                    <span class="text-[9px] text-slate-400 mt-1">PNG, JPG atau JPEG (Maks 2MB)</span>
                                </div>
                            @endif
                        </div>

                        <!-- Uploading progress bar -->
                        <div wire:loading wire:target="selfie_image" class="w-full">
                            <div class="h-1 bg-rose-200 rounded-sm overflow-hidden">
                                <div class="h-full bg-rose-600 animate-pulse" style="width: 70%"></div>
                            </div>
                            <span class="text-[9px] text-rose-500 font-bold mt-1 block">Mengunggah gambar Selfie...</span>
                        </div>
                        @error('selfie_image') <span class="text-rose-600 text-xs block font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm px-6 py-3 rounded-sm border-2 border-slate-900 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition duration-150 uppercase tracking-wide">
                        Kirim Dokumen Verifikasi
                    </button>
                </div>
            </form>

        <!-- KONDISI B: MENUNGGU PERSETUJUAN -->
        @elseif ($jastiper->verification_status === 'menunggu')
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-sm border border-amber-200 flex items-center justify-center mx-auto mb-4 animate-bounce">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-display font-black text-2xl text-slate-900 uppercase tracking-tight">Dokumen Sedang Ditinjau</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mt-2 leading-relaxed">
                    Terima kasih! Dokumen verifikasi Anda telah kami terima dan saat ini sedang ditinjau oleh Admin JastipKuy. Proses review biasanya memakan waktu maksimal 1x24 jam.
                </p>

                <div class="mt-8 border-2 border-slate-200 rounded-sm p-4 bg-slate-50 text-left max-w-lg mx-auto">
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3 text-center">Dokumen yang Dikirimkan</h4>
                    <div class="grid grid-cols-2 gap-4">
                        @if ($latest)
                            <div class="text-center">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Foto KTP</span>
                                <img src="{{ asset('storage/' . $latest->ktp_image) }}" class="w-full h-28 object-cover rounded-sm border border-slate-300">
                            </div>
                            <div class="text-center">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Foto Selfie + KTP</span>
                                <img src="{{ asset('storage/' . $latest->selfie_image) }}" class="w-full h-28 object-cover rounded-sm border border-slate-300">
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-8">
                    <a href="{{ route('jastiper.dashboard') }}" class="inline-flex items-center gap-2 border-2 border-slate-900 bg-white hover:bg-slate-50 text-slate-800 font-bold text-xs px-6 py-3 rounded-sm shadow-[2px_2px_0px_0px_rgba(15,23,42,1)] transition">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>

        <!-- KONDISI C: APPROVED -->
        @elseif ($jastiper->verification_status === 'approved')
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-sm border border-emerald-200 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-display font-black text-2xl text-slate-900 uppercase tracking-tight">Akun Anda Terverifikasi</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mt-2 leading-relaxed">
                    Selamat! Akun Jastiper Anda telah aktif secara penuh. Anda sudah dapat mengakses order titipan terdekat, mengirimkan penawaran belanja, dan mendapatkan komisi belanja.
                </p>

                <div class="mt-8">
                    <a href="{{ route('jastiper.dashboard') }}" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-6 py-3 rounded-sm border-2 border-slate-900 shadow-[3px_3px_0px_0px_rgba(16,185,129,1)] transition duration-150 uppercase tracking-wider">
                        Masuk Ke Dashboard Jastiper
                    </a>
                </div>
            </div>

        <!-- KONDISI D: REJECTED -->
        @elseif ($jastiper->verification_status === 'rejected')
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-sm border border-rose-200 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                
                <h3 class="font-display font-black text-2xl text-slate-900 uppercase tracking-tight">Verifikasi Akun Ditolak</h3>
                
                <!-- Rejection Reason Card -->
                <div class="mt-4 bg-rose-50 border-2 border-rose-200 text-rose-800 p-4 rounded-sm max-w-md mx-auto text-left">
                    <span class="text-[10px] font-bold uppercase tracking-wider block text-rose-500 mb-1">Alasan Penolakan dari Admin:</span>
                    <p class="text-sm font-semibold italic">
                        "{{ $latest ? $latest->rejection_reason : 'Foto dokumen KTP / selfie Anda tidak terbaca dengan jelas.' }}"
                    </p>
                </div>

                <p class="text-xs text-slate-400 mt-4 max-w-sm mx-auto">
                    Silakan klik tombol di bawah untuk mengunggah ulang dokumen verifikasi yang baru dengan kualitas foto yang lebih baik.
                </p>

                <div class="mt-8 flex justify-center gap-3">
                    <button type="button" wire:click="triggerResubmission" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-6 py-3 rounded-sm border-2 border-slate-900 shadow-[3px_3px_0px_0px_rgba(15,23,42,1)] transition uppercase tracking-wider">
                        Unggah Ulang Dokumen (Submit Ulang)
                    </button>
                    <a href="{{ route('jastiper.dashboard') }}" class="border-2 border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs px-6 py-3 rounded-sm transition">
                        Dashboard
                    </a>
                </div>
            </div>
        @endif

    </div>

    <!-- PANEL SIMULASI ADMIN REVIEW (HANYA MUNCUL DI LOCAL/TESTING ENVIRONMENT) -->
    @if (app()->environment('local', 'testing') && $latest && $jastiper->verification_status === 'menunggu')
        <div class="bg-slate-900 border-2 border-slate-800 text-slate-300 p-6 rounded-sm shadow-md font-mono text-xs animate-fade-in">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                <span class="font-bold text-rose-500 flex items-center gap-1.5 text-sm">
                    <span class="w-2.5 h-2.5 bg-rose-500 rounded-full animate-ping"></span>
                    ⚙️ PANEL SIMULASI ADMIN REVIEW
                </span>
                <span class="text-[9px] text-slate-500">MOCK ADMIN DASHBOARD</span>
            </div>

            <div class="space-y-4">
                <p class="text-slate-400 leading-normal text-[11px]">
                    💡 <b>Petunjuk Simulasi:</b> Karena dokumen Jastiper saat ini berstatus <code>menunggu</code>, sebagai admin Anda dapat menyetujui atau menolak pengajuan ini langsung dari tombol di bawah. Sistem akan memperbarui database dan mengirimkan notifikasi status ke WhatsApp nomor Anda.
                </p>

                <!-- Documents Link preview -->
                <div class="grid grid-cols-2 gap-4 bg-slate-950 p-3 border border-slate-800 rounded-sm">
                    <div>
                        <span class="text-[9px] text-slate-500 block mb-1">FILE KTP:</span>
                        <a href="{{ asset('storage/' . $latest->ktp_image) }}" target="_blank" class="text-emerald-400 underline hover:text-emerald-300 break-all">{{ basename($latest->ktp_image) }} ↗</a>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-500 block mb-1">FILE SELFIE:</span>
                        <a href="{{ asset('storage/' . $latest->selfie_image) }}" target="_blank" class="text-emerald-400 underline hover:text-emerald-300 break-all">{{ basename($latest->selfie_image) }} ↗</a>
                    </div>
                </div>

                <!-- Simulation Action Forms -->
                <div class="flex flex-col md:flex-row gap-4 pt-2 border-t border-slate-800">
                    
                    <!-- Approve Action -->
                    <div class="flex-1 flex flex-col justify-end">
                        <button type="button" wire:click="adminSimulateStatus('approved')" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center py-2.5 rounded-sm border border-emerald-700 transition uppercase tracking-wider text-[10px]">
                            ✅ SETUJUI (APPROVE) AKUN
                        </button>
                    </div>

                    <!-- Reject Action -->
                    <div class="flex-1 space-y-2 border-t md:border-t-0 md:border-l border-slate-800 pt-3 md:pt-0 md:pl-4">
                        <div>
                            <label for="reject_reason" class="block text-[9px] font-bold text-slate-400 uppercase tracking-wide mb-1">Alasan Penolakan</label>
                            <input type="text" id="reject_reason" wire:model="rejection_reason_input" placeholder="Misal: Foto selfie buram..." class="w-full bg-slate-950 border border-slate-800 text-white text-[11px] px-2.5 py-2 rounded-sm outline-none focus:border-slate-500 transition">
                        </div>
                        <button type="button" wire:click="adminSimulateStatus('rejected', rejection_reason_input)" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-center py-2.5 rounded-sm border border-rose-700 transition uppercase tracking-wider text-[10px]">
                            ❌ TOLAK (REJECT) PENGAJUAN
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
