@extends('layouts.adminlte')

@section('title', 'Buat Langganan')
@section('page_title', 'Buat Langganan')

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title mb-0">Data langganan baru</h3></div>
    <form method="post" action="{{ route('billing.subscriptions.store') }}">
        @csrf
        @include('billing.subscriptions._form')
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('billing.subscriptions.index') }}" class="btn btn-default">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan langganan</button>
        </div>
    </form>
</div>
@endsection
