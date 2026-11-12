@extends('layouts.support')

@section('title', 'JK Pay - E-Wallet')

@section('content')
<div class="p-4 sm:p-6 md:p-8 space-y-6 max-w-4xl mx-auto pb-28">
    <!-- Top Header Navigation with Back Button to Profile -->
    <div class="flex items-center pb-3 border-b border-slate-200/80">
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.profile') }}" class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-700 hover:text-rose-600 hover:border-rose-300 transition shadow-xs group cursor-pointer" title="Kembali ke Profil">
                <svg class="w-5 h-5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h1 class="font-display font-black text-xl text-slate-900 leading-tight">JK Pay & Dompet</h1>
                <p class="text-xs text-slate-500">Kelola saldo dan riwayat transaksi akun</p>
            </div>
        </div>
    </div>

    @livewire('customer.wallet')
</div>
@endsection
