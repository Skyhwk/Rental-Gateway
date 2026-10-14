@extends('layouts.adminlte')

@section('title', 'Ubah Langganan')
@section('page_title', 'Ubah Langganan #'.$subscription->id)

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title mb-0">Ubah langganan #{{ $subscription->id }}</h3></div>
    <form method="post" action="{{ route('billing.subscriptions.update', $subscription) }}">
        @csrf
        @method('PUT')
        @include('billing.subscriptions._form', ['readonlyClientApp' => true])
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('billing.subscriptions.show', $subscription) }}" class="btn btn-default">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan perubahan</button>
        </div>
    </form>
</div>
@endsection
