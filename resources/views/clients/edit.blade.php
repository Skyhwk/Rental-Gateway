@extends('layouts.adminlte')

@section('title', 'Ubah Client')
@section('page_title', 'Ubah Client')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Client</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clients.show', $client) }}">{{ $client->company_name }}</a></li>
    <li class="breadcrumb-item active">Ubah</li>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Form Client</h3></div>
    <form method="post" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('clients._form')
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('clients.show', $client) }}" class="btn btn-default">Batal</a>
        </div>
    </form>
</div>
@endsection
