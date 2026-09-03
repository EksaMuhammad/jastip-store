@extends('layouts.support')

@section('title', 'Bayar Top Up Wallet')

@section('content')
<div class="min-h-screen bg-[#F3F4F6]">
    @livewire('customer.pay-topup-page', ['topupId' => $topup->id], key('pay-topup-page-' . $topup->id))
</div>
@endsection

