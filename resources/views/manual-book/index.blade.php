@extends('layouts.adminlte')

@section('title', 'Manual Book')
@section('page_title', 'Manual Book — Panduan Operasional')

@section('content')
<div class="callout callout-info">
    <h5><i class="fas fa-info-circle"></i> Untuk siapa buku panduan ini?</h5>
    <p class="mb-0">
        Ditulis untuk <strong>staff admin, finance, dan operator</strong> yang <em>tidak</em> perlu tahu pemrograman.
        Ikuti langkah dari atas ke bawah saat pertama kali memasang client baru, atau loncat ke bab yang Anda butuhkan lewat daftar isi.
    </p>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list-ul mr-1"></i> Daftar isi</h3>
    </div>
    <div class="card-body">
        <ol class="mb-0 pl-3">
            <li><a href="#bab1">Apa itu Rental Manager?</a></li>
            <li><a href="#bab2">Login &amp; peran pengguna</a></li>
            <li><a href="#bab3">Dasbor — membaca ringkasan</a></li>
            <li><a href="#bab4">Alur lengkap: client baru sampai online</a></li>
            <li><a href="#bab5">Menu Client</a></li>
            <li><a href="#bab6">Menu Aplikasi (domain, backend, gateway)</a></li>
            <li><a href="#bab7">Menangguhkan &amp; mengaktifkan akses</a></li>
            <li><a href="#bab8">Langganan &amp; nominal tagihan</a></li>
            <li><a href="#bab9">Invoice otomatis &amp; manual</a></li>
            <li><a href="#bab10">Pembayaran &amp; verifikasi</a></li>
            <li><a href="#bab11">Monitoring kesehatan sistem</a></li>
            <li><a href="#bab12">Menu Gateway (sinkronisasi)</a></li>
            <li><a href="#bab13">Glosarium istilah</a></li>
            <li><a href="#bab14">FAQ &amp; masalah umum</a></li>
        </ol>
    </div>
</div>

@include('manual-book.sections')

@endsection

@push('styles')
<style>
    .manual-book h4 { margin-top: 1rem; font-size: 1.05rem; }
    .manual-book .step-num {
        display: inline-block;
        width: 1.75rem;
        height: 1.75rem;
        line-height: 1.75rem;
        text-align: center;
        border-radius: 50%;
        background: #007bff;
        color: #fff;
        font-weight: 600;
        font-size: 0.85rem;
        margin-right: 0.35rem;
    }
    .manual-book .flow-box {
        border-left: 4px solid #17a2b8;
        padding: 0.75rem 1rem;
        background: #f8f9fa;
        margin-bottom: 1rem;
    }
</style>
@endpush
