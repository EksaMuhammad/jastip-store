@extends('layouts.support')

@section('title', 'Masuk / Daftar')

@section('content')
<div class="relative min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50 overflow-hidden">
    <!-- Decorative background elements -->
    <div class="absolute top-0 left-0 w-full h-full bg-cover bg-center pointer-events-none opacity-[0.03] z-0" style="background-image: url('{{ asset('images/hero-bg.png') }}');"></div>
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-rose-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-rose-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse" style="animation-delay: 2s;"></div>

    <div class="relative z-10 w-full">
        <!-- Livewire OTP Authentication Component -->
        <livewire:auth.otp-auth />
    </div>
</div>
@endsection
