@extends('layouts.support')

@section('title', 'Atur Wilayah & Radius Jastiper')

@section('styles')
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endsection

@section('content')
<div class="bg-slate-50 min-h-screen py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb / Back Navigation -->
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('jastiper.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Dashboard Jastiper
            </a>
            
            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-sm uppercase tracking-wide">Pengaturan Area Kerja</span>
        </div>

        <!-- Livewire Component -->
        @livewire('jastiper.jastiper-area-settings')

    </div>
</div>
@endsection

@section('scripts')
    <!-- Leaflet JS has been moved to head to prevent race conditions -->
@endsection
