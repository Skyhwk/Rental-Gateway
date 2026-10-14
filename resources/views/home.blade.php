@extends('layouts.adminlte')

@section('title', 'Dasbor')
@section('page_title', 'Dasbor')

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalClients }}</h3>
                <p>Total Client</p>
            </div>
            <div class="icon"><i class="fas fa-building"></i></div>
            @can('viewAny', App\Models\Client::class)
                <a href="{{ route('clients.index') }}" class="small-box-footer">Lihat detail <i class="fas fa-arrow-circle-right"></i></a>
            @else
                <span class="small-box-footer">&nbsp;</span>
            @endcan
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totalApplications }}</h3>
                <p>Total Aplikasi</p>
            </div>
            <div class="icon"><i class="fas fa-server"></i></div>
            @can('viewAny', App\Models\Application::class)
                <a href="{{ route('applications.index') }}" class="small-box-footer">Lihat detail <i class="fas fa-arrow-circle-right"></i></a>
            @else
                <span class="small-box-footer">&nbsp;</span>
            @endcan
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $activeApplications }}</h3>
                <p>Aplikasi Aktif</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <span class="small-box-footer">&nbsp;</span>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $suspendedApplications }}</h3>
                <p>Ditangguhkan</p>
            </div>
            <div class="icon"><i class="fas fa-ban"></i></div>
            <span class="small-box-footer">&nbsp;</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-tools"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pemeliharaan</span>
                <span class="info-box-number">{{ $maintenanceApplications }}</span>
            </div>
        </div>
    </div>
    @can('viewAny', App\Models\Invoice::class)
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-file-invoice"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Invoice belum lunas</span>
                <span class="info-box-number">{{ $unpaidInvoices }}</span>
                <a href="{{ route('billing.invoices.index') }}" class="small">Lihat invoice</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Invoice jatuh tempo</span>
                <span class="info-box-number">{{ $overdueInvoices }}</span>
            </div>
        </div>
    </div>
    @endcan
</div>

@can('monitoring.view')
<div class="row">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-heartbeat"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Backend OK</span>
                <span class="info-box-number">{{ $healthUp }}</span>
                <a href="{{ route('monitoring.index') }}">Monitoring</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-plug"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Backend down</span>
                <span class="info-box-number">{{ $healthDown }}</span>
            </div>
        </div>
    </div>
    @if ($services)
    <div class="col-md-4">
        <div class="info-box mb-0">
            <span class="info-box-icon bg-info"><i class="fas fa-cogs"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Layanan</span>
                <span class="info-box-number text-sm">
                    Apache {{ ! empty($services['apache2']['active']) ? '✓' : '✗' }} ·
                    Tunnel {{ ! empty($services['cloudflared']['active']) ? '✓' : '✗' }} ·
                    Agent {{ ! empty($services['gateway_agent']['active']) ? '✓' : '✗' }}
                </span>
            </div>
        </div>
    </div>
    @endif
</div>
@else
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-network-wired mr-1"></i> Status Gateway</h3>
            </div>
            <div class="card-body">
                <p class="mb-0">Gateway Agent — set <code>GATEWAY_AGENT_*</code>@can('gateway.sync'), menu <a href="{{ route('gateway.index') }}">Gateway</a>@endcan.</p>
            </div>
        </div>
    </div>
</div>
@endcan

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Aksi cepat</h3>
            </div>
            <div class="card-body">
                @can('create', App\Models\Client::class)
                    <a href="{{ route('clients.create') }}" class="btn btn-primary mr-2 mb-2"><i class="fas fa-plus mr-1"></i> Client Baru</a>
                @endcan
                @can('create', App\Models\Application::class)
                    <a href="{{ route('applications.create') }}" class="btn btn-outline-primary mr-2 mb-2"><i class="fas fa-plus mr-1"></i> Aplikasi Baru</a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
