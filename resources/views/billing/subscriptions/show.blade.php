@extends('layouts.adminlte')

@section('title', 'Langganan #'.$subscription->id)
@section('page_title', 'Detail Langganan')

@php
    $statusLabels = App\Models\Subscription::statuses();
    $statusLabel = $statusLabels[$subscription->status] ?? $subscription->status;
    $statusStyles = [
        App\Models\Subscription::STATUS_ACTIVE => ['class' => 'success', 'icon' => 'check-circle'],
        App\Models\Subscription::STATUS_PENDING => ['class' => 'info', 'icon' => 'hourglass-half'],
        App\Models\Subscription::STATUS_PAST_DUE => ['class' => 'danger', 'icon' => 'exclamation-circle'],
        App\Models\Subscription::STATUS_CANCELLED => ['class' => 'secondary', 'icon' => 'ban'],
        App\Models\Subscription::STATUS_EXPIRED => ['class' => 'dark', 'icon' => 'archive'],
    ];
    $statusStyle = $statusStyles[$subscription->status] ?? ['class' => 'secondary', 'icon' => 'info-circle'];

    $invoiceStatusLabels = App\Models\Invoice::statuses();
    $invoiceBadgeMap = [
        App\Models\Invoice::STATUS_PAID => 'success',
        App\Models\Invoice::STATUS_PARTIAL => 'warning',
        App\Models\Invoice::STATUS_OVERDUE => 'danger',
        App\Models\Invoice::STATUS_VOID => 'secondary',
        App\Models\Invoice::STATUS_UNPAID => 'info',
    ];

    $intervalLabel = $subscription->plan
        ? (App\Models\SubscriptionPlan::intervals()[$subscription->plan->billing_interval] ?? '')
        : '';

    $invoiceCount = $subscription->invoices->count();
    $totalInvoiced = $subscription->invoices->sum('amount');
    $totalPaid = $subscription->invoices->sum('amount_paid');
    $outstanding = max(0, $totalInvoiced - $totalPaid);

    $generateDays = (int) config('billing.invoice_generate_days_before_due', 7);
@endphp

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('billing.subscriptions.index') }}">Langganan</a></li>
    <li class="breadcrumb-item active">#{{ $subscription->id }}</li>
@endsection

@push('styles')
<style>
    .subscription-detail .subscription-heading {
        border-top: 3px solid #17a2b8;
    }
    .subscription-detail .subscription-title {
        font-size: 1.35rem;
        letter-spacing: .02em;
    }
    .subscription-detail .status-pill {
        align-items: center;
        border-radius: 50rem;
        display: inline-flex;
        font-size: .85rem;
        font-weight: 600;
        padding: .45rem .8rem;
    }
    .subscription-detail .summary-box {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: .35rem;
        height: 100%;
        padding: 1rem;
    }
    .subscription-detail .summary-label {
        color: #6c757d;
        display: block;
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .04em;
        margin-bottom: .3rem;
        text-transform: uppercase;
    }
    .subscription-detail .summary-value {
        color: #212529;
        font-size: 1rem;
        font-weight: 600;
        word-break: break-word;
    }
    .subscription-detail .summary-value-lg {
        font-size: 1.35rem;
        line-height: 1.2;
    }
    .subscription-detail .detail-list dt {
        color: #6c757d;
        font-weight: 500;
    }
    .subscription-detail .detail-list dd {
        font-weight: 600;
    }
    .subscription-detail .schedule-hint {
        background: #f8f9fa;
        border-left: 3px solid #17a2b8;
        border-radius: 0 .25rem .25rem 0;
        font-size: .875rem;
        padding: .65rem .85rem;
    }
    .subscription-detail .invoice-table thead th {
        background: #f4f6f9;
        border-bottom-width: 1px;
        font-size: .75rem;
        letter-spacing: .03em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .subscription-detail .empty-invoices {
        color: #6c757d;
        padding: 2.5rem 1rem;
    }
    .subscription-detail .empty-invoices i {
        font-size: 2rem;
        margin-bottom: .75rem;
        opacity: .45;
    }
    @media (max-width: 767.98px) {
        .subscription-detail .subscription-title {
            font-size: 1.1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="subscription-detail">
    <div class="mb-3 d-flex flex-wrap align-items-center">
        <a href="{{ route('billing.subscriptions.index') }}" class="btn btn-default btn-sm mr-2 mb-2">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
        @can('update', $subscription)
            <a href="{{ route('billing.subscriptions.edit', $subscription) }}" class="btn btn-primary btn-sm mr-2 mb-2">
                <i class="fas fa-edit mr-1"></i> Ubah langganan
            </a>
        @endcan
        @can('create', App\Models\Invoice::class)
            <form method="post" action="{{ route('billing.invoices.store', $subscription) }}" class="d-inline mb-2">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-file-invoice mr-1"></i> Buat invoice manual
                </button>
            </form>
        @endcan
    </div>

    <div class="card subscription-heading shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start">
                <div class="mb-3 mb-md-0 pr-md-3">
                    <span class="text-muted small text-uppercase">Langganan</span>
                    <div class="subscription-title font-weight-bold">
                        {{ $subscription->plan->name ?? 'Paket' }}
                        <span class="text-muted font-weight-normal">#{{ $subscription->id }}</span>
                    </div>
                    <div class="text-muted mt-1">
                        <a href="{{ route('clients.show', $subscription->client) }}" class="text-muted">
                            <i class="fas fa-building mr-1"></i>{{ $subscription->client->company_name }}
                        </a>
                        <span class="mx-1">&bull;</span>
                        <a href="{{ route('applications.show', $subscription->application) }}">
                            <i class="fas fa-server mr-1"></i>{{ $subscription->application->name }}
                        </a>
                    </div>
                </div>
                <span class="status-pill badge-{{ $statusStyle['class'] }}">
                    <i class="fas fa-{{ $statusStyle['icon'] }} mr-2"></i>{{ $statusLabel }}
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="row mb-3">
                <div class="col-sm-4 mb-2 mb-sm-0">
                    <div class="summary-box">
                        <span class="summary-label">Harga langganan</span>
                        <span class="summary-value summary-value-lg text-primary">
                            Rp {{ number_format($subscription->price_amount, 0, ',', '.') }}
                        </span>
                        @if ($intervalLabel)
                            <span class="text-muted small d-block mt-1">{{ $intervalLabel }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-sm-4 mb-2 mb-sm-0">
                    <div class="summary-box">
                        <span class="summary-label">Perpanjangan berikutnya</span>
                        <span class="summary-value">
                            @if ($subscription->next_billing_date)
                                <i class="far fa-calendar-alt text-muted mr-1"></i>
                                {{ $subscription->next_billing_date->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="summary-box">
                        <span class="summary-label">Toleransi keterlambatan</span>
                        <span class="summary-value">
                            {{ $subscription->grace_days }} hari
                            @if ($subscription->grace_days === 0)
                                <span class="text-muted small d-block font-weight-normal">Suspend di hari jatuh tempo</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold mb-0">
                        <i class="fas fa-info-circle text-info mr-2"></i>Informasi langganan
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <dl class="row detail-list mb-0">
                        <dt class="col-sm-4">Client</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('clients.show', $subscription->client) }}">{{ $subscription->client->company_name }}</a>
                        </dd>

                        <dt class="col-sm-4">Aplikasi</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('applications.show', $subscription->application) }}">{{ $subscription->application->name }}</a>
                        </dd>

                        <dt class="col-sm-4">Paket</dt>
                        <dd class="col-sm-8">{{ $subscription->plan->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Mulai layanan</dt>
                        <dd class="col-sm-8">
                            <i class="far fa-calendar-check text-muted mr-1"></i>
                            {{ optional($subscription->starts_at)->format('d/m/Y') ?? '—' }}
                        </dd>

                        <dt class="col-sm-4">Jatuh tempo (sistem)</dt>
                        <dd class="col-sm-8">
                            {{ optional($subscription->next_billing_date)->format('d/m/Y') ?? '—' }}
                            @if ($intervalLabel)
                                <span class="badge badge-light border ml-1">{{ $intervalLabel }}</span>
                            @endif
                        </dd>

                        @if ($subscription->ends_at)
                            <dt class="col-sm-4">Berakhir</dt>
                            <dd class="col-sm-8">{{ $subscription->ends_at->format('d/m/Y') }}</dd>
                        @endif

                        @if ($subscription->notes)
                            <dt class="col-sm-4">Catatan</dt>
                            <dd class="col-sm-8 font-weight-normal">{{ $subscription->notes }}</dd>
                        @endif
                    </dl>

                    @if ($billingScheduleHint && $subscription->starts_at)
                        <div class="schedule-hint mt-3 mb-0 text-muted">
                            <i class="fas fa-sync-alt mr-1 text-info"></i>
                            Siklus billing: mulai <strong>{{ $subscription->starts_at->format('d/m/Y') }}</strong>,
                            perpanjangan pertama <strong>{{ $billingScheduleHint->format('d/m/Y') }}</strong>.
                            Invoice recurring dibuat <strong>H-{{ $generateDays }}</strong> sebelum jatuh tempo.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-info shadow-sm mb-3">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold mb-0">Ringkasan invoice</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Jumlah invoice</span>
                        <strong>{{ $invoiceCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total ditagihkan</span>
                        <strong>Rp {{ number_format($totalInvoiced, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total dibayar</span>
                        <strong class="text-success">Rp {{ number_format($totalPaid, 0, ',', '.') }}</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Outstanding</span>
                        <strong class="{{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($outstanding, 0, ',', '.') }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body py-3">
                    <p class="text-muted small mb-2"><i class="fas fa-lightbulb text-warning mr-1"></i> Tips</p>
                    <p class="small mb-0 text-muted">
                        Setelah client bayar, verifikasi pembayaran di menu Finance agar invoice lunas dan aplikasi bisa aktif kembali otomatis.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap">
            <h3 class="card-title font-weight-bold mb-0">
                <i class="fas fa-file-invoice-dollar text-secondary mr-2"></i>Invoice langganan ini
            </h3>
            @if ($invoiceCount > 0)
                <span class="badge badge-secondary">{{ $invoiceCount }} invoice</span>
            @endif
        </div>
        <div class="card-body p-0">
            @if ($invoiceCount > 0)
                <div class="table-responsive">
                    <table class="table table-hover invoice-table mb-0">
                        <thead>
                            <tr>
                                <th>Nomor</th>
                                <th>Jatuh tempo</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Dibayar</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscription->invoices->sortByDesc('due_date') as $inv)
                                @php
                                    $invLabel = $invoiceStatusLabels[$inv->status] ?? $inv->status;
                                    $invBadge = $invoiceBadgeMap[$inv->status] ?? 'secondary';
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('billing.invoices.show', $inv) }}" class="font-weight-bold">
                                            {{ $inv->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                                    <td class="text-right text-nowrap">Rp {{ number_format($inv->amount, 0, ',', '.') }}</td>
                                    <td class="text-right text-nowrap">Rp {{ number_format($inv->amount_paid, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $invBadge }}">{{ $invLabel }}</span>
                                    </td>
                                    <td class="text-right text-nowrap">
                                        <a href="{{ route('billing.invoices.pdf', $inv) }}" class="btn btn-xs btn-default" target="_blank" title="PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        @can('delete', $inv)
                                            <form method="post" action="{{ route('billing.subscriptions.invoices.destroy', [$subscription, $inv]) }}" class="d-inline" onsubmit="return confirm('Hapus invoice {{ $inv->invoice_number }}? Hanya untuk duplikat / salah buat.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Hapus">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-invoices text-center">
                    <div><i class="fas fa-inbox d-block"></i></div>
                    <p class="mb-1 font-weight-bold">Belum ada invoice</p>
                    <p class="small mb-0">Invoice recurring akan muncul otomatis sesuai jadwal billing, atau buat manual dari tombol di atas.</p>
                </div>
            @endif
        </div>
        <div class="card-footer text-muted small">
            <i class="fas fa-info-circle mr-1"></i>
            Hapus invoice hanya jika belum ada pembayaran terverifikasi (misalnya invoice dobel).
        </div>
    </div>
</div>
@endsection
