@extends('layouts.support')

@section('title', 'Profil Saya')

@section('content')
<div class="min-h-screen bg-[#F8FAFC]">

    {{-- ===== HEADER ===== --}}
    <div class="relative bg-rose-600 pt-5 pb-20 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/10 rounded-full"></div>
        <div class="absolute top-10 -right-4 w-28 h-28 bg-white/10 rounded-full"></div>
        <div class="absolute -bottom-6 left-1/3 w-24 h-24 bg-white/10 rounded-full"></div>

        <div class="relative z-10 max-w-lg mx-auto px-4 flex items-center gap-3">
            <a href="{{ route('jastiper.dashboard') }}" class="text-white/80 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="font-display font-black text-xl text-white tracking-tight">Akun Jastiper</h1>
        </div>
    </div>

    {{-- ===== MAIN CONTENT ===== --}}
    <div class="max-w-lg mx-auto px-4 -mt-14 pb-28 space-y-4 relative z-10">

        {{-- ===== PROFILE CARD ===== --}}
        <div class="bg-white rounded-2xl shadow-[0_8px_30px_-4px_rgba(0,0,0,0.08)] border border-slate-100 p-5">
            <div class="flex items-center gap-4">
                <div class="relative shrink-0">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-rose-500 to-rose-700 flex items-center justify-center text-white font-black text-2xl font-display shadow-lg shadow-rose-200 ring-4 ring-white">
                        {{ strtoupper(substr($jastiper->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $jastiper->name)[1] ?? 'X', 0, 1)) }}
                    </div>
                    <div class="absolute bottom-0.5 right-0.5 w-4 h-4 rounded-full border-2 border-white shadow-sm
                        @if($jastiper->work_status === 'tersedia') bg-emerald-500
                        @elseif($jastiper->work_status === 'standby') bg-amber-400
                        @else bg-slate-400 @endif">
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <h2 class="font-display font-black text-base text-slate-900 truncate leading-tight">{{ $jastiper->name }}</h2>
                    <div class="flex items-center gap-1.5 mt-0.5 text-slate-500">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <p class="text-xs font-medium truncate">{{ $jastiper->phone_number }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                        {{-- Badge verifikasi — gunakan 'approved' sebagai status terverifikasi --}}
                        @if($jastiper->verification_status === 'approved')
                            <span class="inline-flex items-center gap-1 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                Terverifikasi
                            </span>
                        @elseif($jastiper->verification_status === 'pending')
                            <span class="inline-flex items-center gap-1 bg-amber-50 border border-amber-200 text-amber-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Menunggu Verifikasi
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-slate-100 border border-slate-200 text-slate-500 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Belum Terverifikasi
                            </span>
                        @endif

                        @if($jastiper->wilayah)
                            <span class="inline-flex items-center gap-1 bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ $jastiper->wilayah->name }}
                            </span>
                        @endif
                    </div>
                </div>

                <a href="{{ route('jastiper.verification') }}" class="shrink-0 w-9 h-9 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-full flex items-center justify-center text-slate-500 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </a>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-3 gap-3">
                <div class="text-center">
                    <div class="font-display font-black text-xl text-slate-900">{{ $ratingAvg ? number_format($ratingAvg, 1) : '—' }}</div>
                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5">Rating</div>
                </div>
                <div class="text-center border-x border-slate-100">
                    <div class="font-display font-black text-xl text-slate-900">{{ $completedOrdersCount ?? 0 }}</div>
                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5">Selesai</div>
                </div>
                <div class="text-center">
                    <div class="font-display font-black text-xl {{ ($completionRate ?? 100) >= 90 ? 'text-emerald-600' : (($completionRate ?? 100) >= 75 ? 'text-amber-600' : 'text-rose-600') }}">
                        {{ $completionRate ?? 100 }}%
                    </div>
                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5">Penyelesaian</div>
                </div>
            </div>
        </div>

        {{-- ===== PENILAIAN ===== --}}
        <a href="{{ route('jastiper.earnings') }}" class="flex items-center justify-between bg-white rounded-2xl shadow-[0_4px_15px_-4px_rgba(0,0,0,0.06)] border border-slate-100 px-5 py-4 hover:bg-slate-50 transition group">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center text-slate-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                </div>
                <div>
                    <span class="font-semibold text-sm text-slate-900 group-hover:text-rose-600 transition">Penilaian & Ulasan</span>
                    <p class="text-[10px] text-slate-500 font-medium mt-0.5">
                        Rating rata-rata:
                        @if($ratingAvg)
                            <span class="font-bold text-slate-700">{{ $ratingAvg }} / 5.0</span>
                        @else
                            <span class="text-slate-400">Belum ada ulasan</span>
                        @endif
                    </p>
                </div>
            </div>
            <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
        </a>

        {{-- ===== TIPS BANNER ===== --}}
        <div class="relative bg-gradient-to-br from-rose-600 to-rose-700 rounded-2xl p-5 overflow-hidden shadow-md shadow-rose-200">
            <div class="absolute -right-6 -bottom-6 w-36 h-36 bg-white/10 rounded-full blur-md"></div>
            <div class="absolute right-8 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <div class="relative z-10 flex items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                        <h3 class="font-display font-black text-base text-white">Tips Jastiper Handal</h3>
                    </div>
                    <p class="text-xs text-rose-100 leading-relaxed">Pelajari cara jadi Jastiper berpenghasilan tinggi dan dipercaya pelanggan.</p>
                    <a href="{{ route('faq') }}" class="inline-block mt-3 bg-white text-rose-600 text-xs font-black py-1.5 px-4 rounded-full hover:bg-rose-50 transition shadow-sm">Lihat Panduan</a>
                </div>
                <div class="shrink-0 w-16 h-16 bg-white/15 rounded-2xl flex items-center justify-center border border-white/20">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- ===== MENU LAINNYA — semua icon warna netral slate ===== --}}
        <div>
            <p class="text-xs font-black text-slate-500 uppercase tracking-widest px-1 mb-3">Menu Lainnya</p>
            <div class="bg-white rounded-2xl shadow-[0_4px_15px_-4px_rgba(0,0,0,0.06)] border border-slate-100 overflow-hidden divide-y divide-slate-100">

                <a href="{{ route('jastiper.earnings') }}" class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="flex-1">
                        <span class="font-semibold text-sm text-slate-800 group-hover:text-slate-900 transition">Pendapatan & Penarikan</span>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Rekap komisi dan withdraw saldo</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('jastiper.area') }}" class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div class="flex-1">
                        <span class="font-semibold text-sm text-slate-800 group-hover:text-slate-900 transition">Area Kerja Saya</span>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Atur radius & wilayah operasional</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('jastiper.verification') }}" class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div class="flex-1">
                        <span class="font-semibold text-sm text-slate-800 group-hover:text-slate-900 transition">Status Verifikasi Akun</span>
                        <div class="flex items-center gap-1 mt-0.5">
                            @if($jastiper->verification_status === 'approved')
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></div>
                                <p class="text-[10px] text-emerald-600 font-medium">Akun sudah terverifikasi</p>
                            @elseif($jastiper->verification_status === 'pending')
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></div>
                                <p class="text-[10px] text-amber-600 font-medium">Menunggu persetujuan admin</p>
                            @else
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></div>
                                <p class="text-[10px] text-slate-500 font-medium">Akun belum diverifikasi</p>
                            @endif
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('faq') }}" class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="flex-1">
                        <span class="font-semibold text-sm text-slate-800 group-hover:text-slate-900 transition">Bantuan & FAQ</span>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Pertanyaan umum & panduan penggunaan</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

                <a href="{{ route('terms') }}" class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition group">
                    <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center text-slate-600 shrink-0 group-hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <div class="flex-1">
                        <span class="font-semibold text-sm text-slate-800 group-hover:text-slate-900 transition">Syarat & Ketentuan</span>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Aturan penggunaan platform JastipKuy</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>

            </div>
        </div>

        {{-- ===== LOGOUT ===== --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 hover:text-rose-600 font-semibold text-sm py-4 rounded-2xl transition flex items-center justify-center gap-2 shadow-sm group">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Keluar dari Akun
            </button>
        </form>

        <p class="text-center text-[10px] text-slate-400 font-medium pt-1">JastipKuy v1.0 &bull; Platform Jasa Titip Terpercaya</p>

    </div>
</div>
@endsection
