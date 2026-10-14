@extends('layouts.adminlte')

@section('title', 'Tambah Aplikasi')
@section('page_title', 'Tambah Aplikasi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('applications.index') }}">Aplikasi</a></li>
    <li class="breadcrumb-item active">Tambah</li>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Form Aplikasi</h3></div>
    <form method="post" action="{{ route('applications.store') }}">
        @csrf
        <div class="card-body">
            @include('applications._form')
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('applications.index') }}" class="btn btn-default">Batal</a>
        </div>
    </form>
</div>
@endsection
