@extends('layouts.adminlte')

@section('title', $invoice->invoice_number)
@section('page_title', 'Invoice '.$invoice->invoice_number)

@php
    $statusLabels = App\Models\Invoice::statuses();
    $statusLabel = $statusLabels[$invoice->status] ?? $invoice->status;
    $statusStyles = [
        App\Models\Invoice::STATUS_PAID => ['class' => 'success', 'icon' => 'check-circle'],
        App\Models\Invoice::STATUS_PARTIAL => ['class' => 'warning', 'icon' => 'adjust'],
        App\Models\Invoice::STATUS_OVERDUE => ['class' => 'danger', 'icon' => 'exclamation-circle'],
        App\Models\Invoice::STATUS_VOID => ['class' => 'secondary', 'icon' => 'ban'],
        App\Models\Invoice::STATUS_UNPAID => ['class' => 'info', 'icon' => 'clock'],
    ];
    $statusStyle = $statusStyles[$invoice->status] ?? ['class' => 'secondary', 'icon' => 'info-circle'];
    $balanceDue = $invoice->balanceDue();
@endphp

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('billing.invoices.index') }}">Invoice</a></li>
    <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
@endsection

@push('styles')
<style>
    .invoice-detail .invoice-heading {
        border-top: 3px solid #007bff;
    }
    .invoice-detail .invoice-number {
        font-size: 1.35rem;
        letter-spacing: .02em;
    }
    .invoice-detail .status-pill {
        border-radius: 50rem;
        display: inline-flex;
        align-items: center;
        font-size: .85rem;
        font-weight: 600;
        padding: .45rem .8rem;
    }
    .invoice-detail .summary-box {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: .35rem;
        height: 100%;
        padding: 1rem;
    }
    .invoice-detail .summary-label {
        color: #6c757d;
        display: block;
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .04em;
        margin-bottom: .3rem;
        text-transform: uppercase;
    }
    .invoice-detail .summary-value {
        color: #212529;
        font-size: 1rem;
        font-weight: 600;
        word-break: break-word;
    }
    .invoice-detail .amount-due {
        font-size: 1.65rem;
        line-height: 1.2;
    }
    .invoice-detail .detail-list dt {
        color: #6c757d;
        font-weight: 500;
    }
    .invoice-detail .detail-list dd {
        font-weight: 600;
    }
    .invoice-detail .action-card .btn {
        text-align: left;
    }
    .invoice-detail .action-card .btn i {
        text-align: center;
        width: 1.35rem;
    }
    @media (max-width: 767.98px) {
        .invoice-detail .invoice-number {
            font-size: 1.1rem;
        }
        .invoice-detail .amount-due {
            font-size: 1.35rem;
        }
    }
</style>
@endpush

@section('content')
<div class="invoice-detail">
    <div class="mb-3">
        <a href="{{ route('billing.invoices.index') }}" class="btn btn-default btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke daftar invoice
        </a>
    </div>

    <div class="card invoice-heading shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="mb-3 mb-md-0">
                    <span class="text-muted small text-uppercase">Nomor invoice</span>
                    <div class="invoice-number font-weight-bold">{{ $invoice->invoice_number }}</div>
                    <div class="text-muted mt-1">
                        Dibuat {{ $invoice->created_at->format('d/m/Y') }}
                        <span class="mx-1">&bull;</span>
                        {{ $invoice->subscription->client->company_name }}
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
                        <span class="summary-label">Total tagihan</span>
                        <span class="summary-value">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="col-sm-4 mb-2 mb-sm-0">
                    <div class="summary-box">
                        <span class="summary-label">Sudah dibayar</span>
                        <span class="summary-value text-success">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="summary-box">
                        <span class="summary-label">Sisa pembayaran</span>
                        <span class="summary-value amount-due {{ $balanceDue > 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($balanceDue, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-info-circle text-primary mr-2"></i>Informasi tagihan
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <dl class="row detail-list mb-0">
                        <dt class="col-sm-4">Client</dt>
                        <dd class="col-sm-8">{{ $invoice->subscription->client->company_name }}</dd>

                        <dt class="col-sm-4">Aplikasi</dt>
                        <dd class="col-sm-8">{{ $invoice->subscription->application->name }}</dd>

                        <dt class="col-sm-4">Langganan</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('billing.subscriptions.show', $invoice->subscription) }}">
                                Lihat langganan #{{ $invoice->subscription_id }}
                            </a>
                        </dd>

                        <dt class="col-sm-4">Periode layanan</dt>
                        <dd class="col-sm-8">{{ $invoice->period_start->format('d/m/Y') }} — {{ $invoice->period_end->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4">Jatuh tempo</dt>
                        <dd class="col-sm-8">
                            <i class="far fa-calendar-alt text-muted mr-1"></i>
                            {{ $invoice->due_date->format('d/m/Y') }}
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-list text-primary mr-2"></i>Rincian biaya
                    </h3>
                </div>
            <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 pl-4">Deskripsi</th>
                                    <th class="border-0 text-right pr-4">Jumlah</th>
                                </tr>
                            </thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr>
                                        <td class="pl-4">{{ $item->description }}</td>
                                        <td class="text-right font-weight-bold pr-4">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card action-card shadow-sm">
                <div class="card-header border-0">
                    <h3 class="card-title font-weight-bold">Tindakan</h3>
                </div>
                <div class="card-body pt-0">
                    @can('create', App\Models\Payment::class)
                        @if ($balanceDue > 0)
                            <a href="{{ route('billing.payments.index', ['record' => $invoice->id]) }}" class="btn btn-success btn-block mb-3">
                                <i class="fas fa-money-bill-wave mr-1"></i> Catat pembayaran
                            </a>
                        @endif
                    @endcan

                    <button type="button" class="btn btn-primary btn-block btn-invoice-preview"
                        data-preview-url="{{ route('billing.invoices.pdf', ['invoice' => $invoice, 'embed' => 1], false) }}"
                        data-page-url="{{ route('billing.invoices.pdf', $invoice, false) }}"
                        data-download-url="{{ route('billing.invoices.download', $invoice, false) }}"
                        data-invoice-number="{{ $invoice->invoice_number }}">
                        <i class="fas fa-eye mr-1"></i> Lihat / cetak invoice
                    </button>
                    <a href="{{ route('billing.invoices.download', $invoice) }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-download mr-1"></i> Unduh invoice
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-block" disabled title="Fitur segera tersedia">
                        <i class="fas fa-envelope mr-1"></i> Kirim melalui email
                        <small class="d-block ml-4">Segera tersedia</small>
                    </button>
                </div>
            </div>

            <div class="callout callout-info shadow-sm">
                <h5 class="mb-2"><i class="fas fa-lightbulb mr-1"></i> Informasi</h5>
                <p class="mb-0">
                    Riwayat dan verifikasi pembayaran dikelola di menu
                    <a href="{{ route('billing.payments.index') }}" class="font-weight-bold">Pembayaran</a>.
                </p>
            </div>
        </div>
    </div>

    @include('billing.invoices._preview-modal')
</div>
@endsection
