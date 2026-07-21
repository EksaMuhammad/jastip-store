@extends('layouts.support')

@section('title', 'Panel Admin - Verifikasi Akun Jastiper')

@section('content')
<div class="bg-slate-50 min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb / Back Navigation -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black text-slate-800 bg-slate-200 px-2.5 py-1 rounded-sm border border-slate-300 font-mono tracking-widest uppercase">Admin Panel</span>
                <span class="text-slate-400">/</span>
                <span class="text-xs font-bold text-slate-500">Antrian Verifikasi Jastiper</span>
            </div>
            
            <span class="text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-sm uppercase tracking-wider font-mono">Secure Admin Zone</span>
        </div>

        <!-- Livewire Component -->
        @livewire('admin.admin-verification')

    </div>
</div>
@endsection
