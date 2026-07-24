@extends('layouts.support')

@section('title', 'Pembayaran Order #' . $order->id)

@section('content')
<div class="min-h-screen bg-[#F3F4F6]">
    @livewire('customer.payment-page', ['orderId' => $order->id], key('payment-page-' . $order->id))
</div>
@endsection