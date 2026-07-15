@extends('layouts.support')

@section('title', 'Verifikasi Akun Jastiper')

@section('content')
<div class="bg-slate-50 min-h-screen py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb / Back Navigation -->
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('jastiper.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Dashboard Jastiper
            </a>
            
            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-sm uppercase tracking-wide">Verifikasi KTP & Selfie</span>
        </div>

        <!-- Livewire Volt Component -->
        @livewire('jastiper.jastiper-verification-form')

    </div>
</div>
@endsection
