@extends('layouts.support')

@section('title', 'Bayar Top Up Wallet')

@section('content')
<div class="max-w-xl mx-auto mt-6 sm:mt-10 px-4 sm:px-0">
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 text-center">
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-2">Selesaikan Pembayaran</h2>
        <p class="text-sm sm:text-base text-slate-500 mb-6 sm:mb-8">Anda akan melakukan Top Up saldo E-Wallet sebesar <span class="font-bold text-slate-700">Rp{{ number_format($topup->amount, 0, ',', '.') }}</span></p>

        @if($topup->channel === 'qris' && $topup->qr_string)
            <div class="mb-6 sm:mb-8 flex justify-center">
                <div class="p-3 sm:p-4 bg-white border-2 border-slate-200 rounded-xl inline-block max-w-full overflow-hidden">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($topup->qr_string) !!}
                </div>
            </div>
            <p class="text-xs sm:text-sm font-semibold text-slate-700 mb-2">Scan QR Code di atas menggunakan aplikasi e-Wallet favorit Anda</p>
        @elseif($topup->channel === 'bank_transfer_va' && $topup->va_number)
            <div class="bg-slate-50 rounded-2xl p-4 sm:p-6 mb-6 sm:mb-8 border border-slate-200">
                <p class="text-xs sm:text-sm text-slate-500 mb-1">Nomor Virtual Account</p>
                <p class="text-xl sm:text-3xl font-black tracking-wider text-slate-800 font-mono break-all">{{ $topup->va_number }}</p>
            </div>
            <p class="text-xs sm:text-sm font-semibold text-slate-700 mb-2">Transfer ke nomor VA di atas</p>
        @else
            <div class="p-4 bg-amber-50 text-amber-700 rounded-xl mb-8">
                Data pembayaran tidak tersedia atau menggunakan metode lain.
            </div>
        @endif

        <div class="text-sm text-slate-400">
            Bayar sebelum: <span class="font-bold text-slate-600">{{ \Carbon\Carbon::parse($topup->payment_deadline)->format('d M Y H:i') }}</span>
        </div>
        
        <div class="mt-8 pt-8 border-t border-slate-100">
            <a href="{{ route('customer.wallet') }}" class="text-rose-600 font-bold hover:text-rose-700 transition">Kembali ke Wallet</a>
        </div>
    </div>
</div>
@endsection
