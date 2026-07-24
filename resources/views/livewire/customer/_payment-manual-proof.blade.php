<p class="text-[11px] font-bold text-slate-500 mb-2">Sudah transfer tapi status belum berubah? Unggah bukti transfer untuk diverifikasi manual oleh admin.</p>

<form wire:submit.prevent="uploadProof" class="space-y-2">
    <input type="file" wire:model="proof" accept=".jpg,.jpeg,.png,.pdf"
        class="block w-full text-[11px] text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">

    <div wire:loading wire:target="proof" class="text-[10px] font-bold text-slate-400">Mengunggah...</div>

    @error('proof')
        <p class="text-[11px] font-bold text-rose-600">{{ $message }}</p>
    @enderror

    @if($proof)
        <button type="submit" wire:loading.attr="disabled" wire:target="uploadProof"
            class="text-[11px] font-bold bg-slate-900 text-white px-3 py-1.5 rounded-lg hover:bg-slate-800">
            Kirim Bukti Transfer
        </button>
    @endif
</form>