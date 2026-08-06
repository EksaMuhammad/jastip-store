@extends('layouts.support')

@section('title', 'Beri Rating Pesanan')

@section('content')
<div class="min-h-screen bg-[#F3F4F6] py-8 pb-24">
    <div class="max-w-md mx-auto px-4">

        <!-- Breadcrumb -->
        <div class="mb-6 flex items-center gap-2">
            <a href="{{ route('customer.dashboard') }}" class="text-xs font-bold text-rose-600 hover:underline">Dashboard</a>
            <span class="text-slate-400">/</span>
            <span class="text-xs font-bold text-slate-500">Beri Rating</span>
        </div>

        <!-- Success/Error Alerts -->
        @if(session('success'))
            <div class="mb-5 bg-emerald-50 border border-emerald-100 p-4 text-emerald-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-4.5 h-4.5 mt-0.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-5 bg-rose-50 border border-rose-100 p-4 text-rose-700 text-xs font-semibold rounded-2xl flex items-start gap-2.5 shadow-sm">
                <svg class="w-4.5 h-4.5 mt-0.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @error('rating')
            <div class="mb-5 bg-rose-50 border border-rose-100 p-4 text-rose-700 text-xs font-semibold rounded-2xl">
                {{ $message }}
            </div>
        @enderror

        <!-- Order Summary Card -->
        <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm mb-5 space-y-1">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Pesanan Selesai</span>
            <h2 class="font-display font-black text-sm text-slate-800 leading-snug">{{ $order->description }}</h2>
            <p class="text-[10px] text-slate-500 font-medium">
                Dikerjakan oleh Jastiper <span class="font-bold text-slate-700">{{ $order->jastiper->name ?? '-' }}</span>
            </p>
        </div>

        @if($order->rating)
            <!-- Mode read-only: rating sudah pernah diberikan sebelumnya -->
            <div class="bg-white border border-slate-200/80 p-6 rounded-3xl shadow-sm space-y-4">
                <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider">Rating Anda</h3>

                <div class="flex items-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <span class="text-2xl {{ $i <= $order->rating->rating ? 'text-amber-400' : 'text-slate-200' }}">★</span>
                    @endfor
                    <span class="ml-2 text-xs font-bold text-slate-600">{{ $order->rating->rating }}/5</span>
                </div>

                @if($order->rating->review_text)
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                        <p class="text-xs text-slate-600 leading-relaxed">{{ $order->rating->review_text }}</p>
                    </div>
                @endif

                <a href="{{ route('customer.dashboard') }}" class="block text-center bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] px-4 py-3 rounded-full transition uppercase tracking-wider">
                    Kembali ke Dashboard
                </a>
            </div>
        @else
            <!-- Form rating bintang + review -->
            <div
                x-data="{
                    rating: {{ old('rating', 0) }},
                    hovered: 0,
                    reviewText: '{{ old('review_text', '') }}',
                    submitting: false,
                }"
                class="bg-white border border-slate-200/80 p-6 rounded-3xl shadow-sm space-y-5"
            >
                <div>
                    <h3 class="font-display font-black text-xs text-slate-800 uppercase tracking-wider mb-1">Seberapa Puas Anda?</h3>
                    <p class="text-[10px] text-slate-400">Ketuk bintang untuk memberi nilai layanan Jastiper.</p>
                </div>

                <form method="POST" action="{{ route('customer.orders.rating.store', ['id' => $order->id]) }}" @submit="submitting = true">
                    @csrf

                    <!-- Star Picker -->
                    <div class="flex items-center justify-center gap-2 py-3">
                        <template x-for="star in [1,2,3,4,5]" :key="star">
                            <button
                                type="button"
                                @click="rating = star"
                                @mouseenter="hovered = star"
                                @mouseleave="hovered = 0"
                                class="text-4xl leading-none transition-transform duration-100 focus:outline-none"
                                :class="(hovered || rating) >= star ? 'text-amber-400 scale-110' : 'text-slate-200'"
                            >★</button>
                        </template>
                    </div>
                    <input type="hidden" name="rating" :value="rating">

                    <p class="text-center text-[10px] font-bold text-slate-500 h-4" x-show="rating > 0" x-cloak x-text="({1:'Sangat Kurang',2:'Kurang',3:'Cukup',4:'Baik',5:'Sangat Baik'})[rating]"></p>

                    <!-- Review Text -->
                    <div class="mt-4">
                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Ulasan (Opsional)</label>
                        <textarea
                            name="review_text"
                            x-model="reviewText"
                            rows="4"
                            maxlength="1000"
                            placeholder="Ceritakan pengalaman belanja Anda dengan Jastiper ini..."
                            class="w-full text-xs text-slate-700 bg-slate-50 border border-slate-200 rounded-2xl p-4 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent resize-none"
                        ></textarea>
                        <p class="text-[9px] text-slate-400 text-right mt-1" x-text="reviewText.length + '/1000'"></p>
                    </div>

                    <button
                        type="submit"
                        :disabled="rating < 1 || submitting"
                        :class="rating < 1 || submitting ? 'opacity-40 cursor-not-allowed' : 'hover:bg-rose-700'"
                        class="mt-4 w-full bg-rose-600 text-white font-bold text-[11px] px-4 py-3.5 rounded-full transition uppercase tracking-wider shadow-sm"
                    >
                        <span x-show="!submitting">Kirim Rating</span>
                        <span x-show="submitting" x-cloak>Mengirim...</span>
                    </button>
                </form>
            </div>
        @endif

    </div>
</div>
@endsection