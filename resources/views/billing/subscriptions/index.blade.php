@extends('layouts.adminlte')

@section('title', 'Langganan')
@section('page_title', 'Langganan')

@php
    $statusBadgeMap = [
        App\Models\Subscription::STATUS_ACTIVE => 'success',
        App\Models\Subscription::STATUS_PENDING => 'info',
        App\Models\Subscription::STATUS_PAST_DUE => 'danger',
        App\Models\Subscription::STATUS_CANCELLED => 'secondary',
        App\Models\Subscription::STATUS_EXPIRED => 'dark',
    ];
    $hasFilters = request()->filled('status') || request()->filled('q');
@endphp

@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $recap['active'] }}</h3>
                <p>Langganan aktif</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <a href="{{ route('billing.subscriptions.index', ['status' => App\Models\Subscription::STATUS_ACTIVE]) }}" class="small-box-footer">
                Filter aktif <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $recap['past_due'] }}</h3>
                <p>Lewat jatuh tempo</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <a href="{{ route('billing.subscriptions.index', ['status' => App\Models\Subscription::STATUS_PAST_DUE]) }}" class="small-box-footer">
                Perlu tindakan <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $recap['total'] }}</h3>
                <p>Semua langganan</p>
            </div>
            <div class="icon"><i class="fas fa-layer-group"></i></div>
            <a href="{{ route('billing.subscriptions.index') }}" class="small-box-footer">
                Lihat semua <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

@if ($recap['past_due'] > 0 && ! request('status'))
    <div class="alert alert-warning alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="icon fas fa-exclamation-triangle"></i>
        Ada <strong>{{ $recap['past_due'] }}</strong> langganan lewat jatuh tempo — cek invoice & pembayaran, atau status aplikasi client bisa otomatis suspend.
        <a href="{{ route('billing.subscriptions.index', ['status' => App\Models\Subscription::STATUS_PAST_DUE]) }}" class="alert-link ml-1">Tampilkan saja yang bermasalah</a>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Daftar langganan</h3>
        @can('create', App\Models\Subscription::class)
            <div class="card-tools">
                <a href="{{ route('billing.subscriptions.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Buat langganan
                </a>
            </div>
        @endcan
    </div>
    <div class="card-body">
        <form method="get" class="mb-3">
            <div class="form-row align-items-end">
                <div class="col-md-5 mb-2 mb-md-0">
                    <label class="small text-muted mb-1">Cari client / aplikasi / paket</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Contoh: PRIMA RASA, Ikhtisar…">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="small text-muted mb-1">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-default btn-sm mr-1">
                        <i class="fas fa-filter mr-1"></i> Terapkan
                    </button>
                    @if ($hasFilters)
                        <a href="{{ route('billing.subscriptions.index') }}" class="btn btn-link btn-sm">Reset filter</a>
                    @endif
                </div>
            </div>
        </form>

        <p class="text-muted small mb-3">
            Urutan: <strong>lewat jatuh tempo</strong> ditampilkan lebih dulu. Kelola tagihan lewat
            <a href="{{ route('billing.invoices.index') }}">Invoice</a> dan
            <a href="{{ route('billing.payments.index') }}">Pembayaran</a>.
        </p>

        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Client</th>
                        <th>Aplikasi</th>
                        <th>Paket</th>
                        <th class="text-right">Harga</th>
                        <th>Jatuh tempo berikutnya</th>
                        <th>Toleransi</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 7rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $sub)
                        @php
                            $statusKey = $sub->status;
                            $statusLabel = $statuses[$statusKey] ?? $statusKey;
                            $badgeClass = $statusBadgeMap[$statusKey] ?? 'secondary';
                            $rowClass = $statusKey === App\Models\Subscription::STATUS_PAST_DUE ? 'table-danger' : '';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>
                                <a href="{{ route('clients.show', $sub->client) }}" class="font-weight-bold text-dark">
                                    {{ $sub->client->company_name }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('applications.show', $sub->application) }}">{{ $sub->application->name }}</a>
                            </td>
                            <td>{{ $sub->plan->name }}</td>
                            <td class="text-right text-nowrap">
                                <span class="text-muted small">Rp</span>
                                {{ number_format($sub->price_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-nowrap">
                                @if ($sub->next_billing_date)
                                    <i class="far fa-calendar-alt text-muted mr-1"></i>
                                    {{ $sub->next_billing_date->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                {{ $sub->grace_days }} hari
                                @if ($sub->grace_days === 0)
                                    <span class="badge badge-light border" title="Suspend otomatis di hari jatuh tempo">H+0</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $badgeClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('billing.subscriptions.show', $sub) }}" class="btn btn-xs btn-primary" title="Detail langganan">
                                    <i class="fas fa-eye mr-1"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                @if ($hasFilters)
                                    Tidak ada langganan yang cocok dengan filter.
                                    <a href="{{ route('billing.subscriptions.index') }}">Tampilkan semua</a>
                                @else
                                    Belum ada langganan.
                                    @can('create', App\Models\Subscription::class)
                                        <a href="{{ route('billing.subscriptions.create') }}">Buat langganan pertama</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subscriptions->hasPages())
            <div class="mt-3">{{ $subscriptions->links() }}</div>
        @endif
    </div>
</div>
@endsection
