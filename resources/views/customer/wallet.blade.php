@extends('layouts.support')

@section('title', 'JK Pay - E-Wallet')

@section('content')
<div class="p-5 md:p-8 space-y-6 max-w-4xl mx-auto pb-24">
    @livewire('customer.wallet')
</div>
@endsection
