@extends('layouts.adminlte')

@section('title', 'Ubah Aplikasi')
@section('page_title', 'Ubah: '.$application->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('applications.index') }}">Aplikasi</a></li>
    <li class="breadcrumb-item"><a href="{{ route('applications.show', $application) }}">{{ $application->name }}</a></li>
    <li class="breadcrumb-item active">Ubah</li>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Form Aplikasi</h3></div>
    <form method="post" action="{{ route('applications.update', $application) }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('applications._form')
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('applications.show', $application) }}" class="btn btn-default">Batal</a>
        </div>
    </form>
</div>
@endsection
