@extends('layouts.merchant')

@section('title', 'Profil Toko')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="font-display font-black text-2xl text-slate-900">Profil & Informasi Toko</h1>
        <p class="text-xs text-slate-500 mt-0.5">Kelola identitas toko, banner gambar, dan informasi kontak usaha Anda.</p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <form action="{{ route('merchant.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Foto Toko / Banner -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Foto / Banner Toko
                </label>
                <div class="flex items-center gap-5">
                    <div class="w-24 h-24 rounded-2xl bg-slate-100 overflow-hidden border border-slate-200 shrink-0">
                        @if($merchant->image)
                            <img src="{{ $merchant->image }}" id="store-avatar" class="w-full h-full object-cover">
                        @else
                            <div id="store-avatar-placeholder" class="w-full h-full flex items-center justify-center font-black text-slate-400 text-xl">
                                {{ strtoupper(substr($merchant->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <input type="file" name="image" id="store-image" accept="image/*" class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
                        <p class="text-[10px] text-slate-400 mt-1">Upload foto tampak depan toko atau logo (JPG/PNG/WEBP, maks 3MB)</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Nama Toko / Usaha <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $merchant->name) }}" required class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Bidang Usaha (Didaftarkan Admin)
                    </label>
                    <input type="text" disabled value="{{ $merchant->type === 'food' ? 'Kuliner / Resto (GoFood)' : 'Minimarket / Mart (GoMart)' }}" class="w-full border border-slate-200 bg-slate-100 text-slate-500 rounded-2xl px-4 py-3 text-xs font-bold cursor-not-allowed">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Email Login (Akun)
                    </label>
                    <input type="text" disabled value="{{ $merchant->email }}" class="w-full border border-slate-200 bg-slate-100 text-slate-500 rounded-2xl px-4 py-3 text-xs font-bold cursor-not-allowed">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        No. Telepon / WhatsApp
                    </label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $merchant->phone_number) }}" placeholder="08xxxxxxxxxx" class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Alamat Lengkap Toko <span class="text-rose-500">*</span>
                </label>
                <textarea name="address" rows="2" required class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl p-4 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none">{{ old('address', $merchant->address) }}</textarea>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Deskripsi / Catatan Toko
                </label>
                <textarea name="description" rows="2" placeholder="Jam buka, fasilitas toko, dll..." class="w-full border border-slate-200 bg-slate-50 focus:bg-white rounded-2xl p-4 text-xs font-semibold focus:ring-2 focus:ring-slate-900 focus:outline-none">{{ old('description', $merchant->description) }}</textarea>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-slate-900/20">
                    Simpan Profil Toko
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
