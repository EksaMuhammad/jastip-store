@extends('layouts.support')

@section('title', 'Buat Request Jastip Baru')

@section('styles')
    <!-- Leaflet CSS & JS loaded in Head to avoid race conditions -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endsection

@section('content')
<div class="min-h-screen bg-[#F3F4F6] py-8">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- Breadcrumb / Navigation -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('customer.dashboard') }}" class="text-xs font-bold text-rose-600 hover:underline">Dashboard</a>
                <span class="text-slate-400">/</span>
                <span class="text-xs font-bold text-slate-500">Buat Request Baru</span>
            </div>
            
            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-full uppercase tracking-wider font-mono">Layanan On-Demand</span>
        </div>

        <!-- Livewire Component -->
        @livewire('customer.create-order-form')

    </div>
</div>
@endsection
