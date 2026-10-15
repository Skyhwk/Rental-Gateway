@extends('layouts.adminlte')

@section('title', 'Pembayaran')
@section('page_title', 'Pembayaran')

@php
    $unpaidCount = $unpaidInvoices->count();
    $unpaidTotal = $unpaidInvoices->sum(function ($inv) {
        return $inv->balanceDue();
    });
    $overdueCount = $unpaidInvoices->where('status', App\Models\Invoice::STATUS_OVERDUE)->count();
    $paymentStatusLabels = App\Models\Payment::verificationStatuses();
    $invoiceStatusLabels = App\Models\Invoice::statuses();
    $filterPending = request('filter') === 'pending';
@endphp

@section('breadcrumb')
    <li class="breadcrumb-item active">Pembayaran</li>
@endsection

@push('styles')
<style>
    .payments-page .stat-card {
        border-radius: .35rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
        height: 100%;
        border-left: 4px solid #dee2e6;
    }
    .payments-page .stat-card.border-left-warning { border-left-color: #ffc107; }
    .payments-page .stat-card.border-left-info { border-left-color: #17a2b8; }
    .payments-page .stat-card.border-left-secondary { border-left-color: #6c757d; }
    .payments-page .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .payments-page .stat-label {
        color: #6c757d;
        font-size: .85rem;
    }
    .payments-page .table thead th {
        background: #f8f9fa;
        border-bottom-width: 1px;
        font-size: .8rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        white-space: nowrap;
    }
    .payments-page .amount-cell {
        font-weight: 600;
        white-space: nowrap;
    }
    .payments-page .filter-pills .btn {
        border-radius: 50rem;
        margin-left: .25rem;
    }
    .payments-page .empty-state {
        color: #6c757d;
        padding: 2rem 1rem;
        text-align: center;
    }
    .payments-page .empty-state i {
        font-size: 2rem;
        margin-bottom: .5rem;
        opacity: .5;
    }
</style>
@endpush

@section('content')
<div class="payments-page">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div class="mb-2 mb-md-0">
            <p class="text-muted mb-0 small">Catat transfer, verifikasi pembayaran, dan pantau invoice yang belum lunas.</p>
        </div>
        @can('create', App\Models\Payment::class)
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#paymentRecordModal" id="btnOpenPaymentModal">
                <i class="fas fa-plus mr-1"></i> Catat pembayaran
            </button>
        @endcan
    </div>

    <div class="row mb-3">
        <div class="col-md-4 mb-2 mb-md-0">
            <div class="card stat-card border-left-warning">
                <div class="card-body py-3">
                    <div class="stat-value text-warning">{{ $pendingCount }}</div>
                    <div class="stat-label">Menunggu verifikasi</div>
                    @if ($pendingCount > 0)
                        <a href="{{ route('billing.payments.index', ['filter' => 'pending']) }}" class="small">Tampilkan daftar →</a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2 mb-md-0">
            <div class="card stat-card border-left-info">
                <div class="card-body py-3">
                    <div class="stat-value text-info">{{ $unpaidCount }}</div>
                    <div class="stat-label">Invoice belum lunas</div>
                    @if ($overdueCount > 0)
                        <span class="badge badge-danger">{{ $overdueCount }} jatuh tempo</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card border-left-secondary">
                <div class="card-body py-3">
                    <div class="stat-value">Rp {{ number_format($unpaidTotal, 0, ',', '.') }}</div>
                    <div class="stat-label">Total sisa tagihan (tampilan)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header border-0 d-flex flex-wrap align-items-center justify-content-between">
            <h3 class="card-title font-weight-bold mb-0">
                <i class="fas fa-file-invoice-dollar text-warning mr-2"></i>Invoice belum lunas
            </h3>
            <span class="text-muted small">Maks. 50 invoice · urut jatuh tempo</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="pl-3">Invoice</th>
                            <th>Client / Aplikasi</th>
                            <th>Jatuh tempo</th>
                            <th>Status invoice</th>
                            <th class="text-right">Sisa</th>
                            <th class="pr-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($unpaidInvoices as $inv)
                            @php
                                $invStatus = $invoiceStatusLabels[$inv->status] ?? $inv->status;
                                $invBadge = 'secondary';
                                if ($inv->status === App\Models\Invoice::STATUS_OVERDUE) {
                                    $invBadge = 'danger';
                                } elseif ($inv->status === App\Models\Invoice::STATUS_PARTIAL) {
                                    $invBadge = 'warning';
                                } elseif ($inv->status === App\Models\Invoice::STATUS_UNPAID) {
                                    $invBadge = 'info';
                                }
                            @endphp
                            <tr>
                                <td class="pl-3">
                                    <a href="{{ route('billing.invoices.show', $inv) }}" class="font-weight-bold">{{ $inv->invoice_number }}</a>
                                </td>
                                <td>
                                    <div>{{ optional($inv->subscription->client)->company_name ?? '—' }}</div>
                                    <small class="text-muted">{{ optional($inv->subscription->application)->name }}</small>
                                </td>
                                <td>
                                    <i class="far fa-calendar-alt text-muted mr-1"></i>{{ $inv->due_date->format('d/m/Y') }}
                                </td>
                                <td><span class="badge badge-{{ $invBadge }}">{{ $invStatus }}</span></td>
                                <td class="text-right amount-cell text-danger">Rp {{ number_format($inv->balanceDue(), 0, ',', '.') }}</td>
                                <td class="pr-3 text-right text-nowrap">
                                    @can('create', App\Models\Payment::class)
                                        <button type="button" class="btn btn-sm btn-success btn-record-payment"
                                            data-invoice-id="{{ $inv->id }}"
                                            data-balance="{{ $inv->balanceDue() }}">
                                            <i class="fas fa-money-bill-wave mr-1"></i> Catat bayar
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div><i class="fas fa-check-circle text-success d-block"></i></div>
                                        <strong>Semua invoice sudah lunas</strong>
                                        <p class="mb-0 small">Tidak ada tagihan terbuka yang perlu dicatat.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header border-0 d-flex flex-wrap align-items-center justify-content-between">
            <h3 class="card-title font-weight-bold mb-2 mb-md-0">
                <i class="fas fa-history text-primary mr-2"></i>Riwayat pembayaran
            </h3>
            <div class="filter-pills">
                <a href="{{ route('billing.payments.index') }}" class="btn btn-sm {{ ! $filterPending ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
                <a href="{{ route('billing.payments.index', ['filter' => 'pending']) }}" class="btn btn-sm {{ $filterPending ? 'btn-warning' : 'btn-outline-secondary' }}">
                    Menunggu verifikasi
                    @if ($pendingCount > 0)
                        <span class="badge badge-light ml-1">{{ $pendingCount }}</span>
                    @endif
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="pl-3">Invoice</th>
                            <th>Client</th>
                            <th>Tanggal bayar</th>
                            <th>Metode</th>
                            <th class="text-right">Nominal</th>
                            <th>Referensi</th>
                            <th>Status</th>
                            <th>Verifikator</th>
                            <th class="pr-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $pay)
                            @php
                                $payLabel = $paymentStatusLabels[$pay->verification_status] ?? $pay->verification_status;
                                $payBadge = 'secondary';
                                if ($pay->verification_status === App\Models\Payment::STATUS_PENDING) {
                                    $payBadge = 'warning';
                                } elseif ($pay->verification_status === App\Models\Payment::STATUS_VERIFIED) {
                                    $payBadge = 'success';
                                } elseif ($pay->verification_status === App\Models\Payment::STATUS_REJECTED) {
                                    $payBadge = 'danger';
                                }
                                $methodLabel = App\Models\Payment::methods()[$pay->method] ?? $pay->method;
                            @endphp
                            <tr>
                                <td class="pl-3">
                                    @if ($pay->invoice)
                                        <a href="{{ route('billing.invoices.show', $pay->invoice) }}">{{ $pay->invoice->invoice_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ optional(optional($pay->invoice)->subscription)->client->company_name ?? '—' }}</td>
                                <td>{{ $pay->paid_at->format('d/m/Y') }}</td>
                                <td><span class="badge badge-light border">{{ $methodLabel }}</span></td>
                                <td class="text-right amount-cell">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                                <td>{{ $pay->reference_number ?: '—' }}</td>
                                <td><span class="badge badge-{{ $payBadge }}">{{ $payLabel }}</span></td>
                                <td>{{ optional($pay->verifiedBy)->name ?? '—' }}</td>
                                <td class="pr-3 text-right text-nowrap">
                                    @can('verify', $pay)
                                        @if ($pay->verification_status === App\Models\Payment::STATUS_PENDING)
                                            <form method="post" action="{{ route('billing.payments.verify', $pay) }}" class="d-inline">@csrf<button type="submit" class="btn btn-xs btn-success" title="Terima pembayaran"><i class="fas fa-check"></i> Verifikasi</button></form>
                                            <form method="post" action="{{ route('billing.payments.reject', $pay) }}" class="d-inline" onsubmit="return confirm('Tolak pembayaran ini?');">@csrf<button type="submit" class="btn btn-xs btn-outline-danger" title="Tolak"><i class="fas fa-times"></i></button></form>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div><i class="fas fa-inbox d-block"></i></div>
                                        <strong>Belum ada pembayaran</strong>
                                        <p class="mb-0 small">
                                            @if ($filterPending)
                                                Tidak ada entri yang menunggu verifikasi.
                                            @else
                                                Catat pembayaran pertama lewat tombol hijau di atas.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($payments->hasPages())
            <div class="card-footer">{{ $payments->links() }}</div>
        @endif
    </div>

    <div class="callout callout-info shadow-sm mt-3 mb-0">
        <p class="mb-0">
            <i class="fas fa-info-circle mr-1"></i>
            Setelah <strong>verifikasi</strong>, nominal masuk ke invoice dan status langganan/aplikasi dapat diperbarui otomatis (sesuai pengaturan billing).
            Rekap invoice ada di menu <a href="{{ route('billing.invoices.index') }}">Invoice</a>.
        </p>
    </div>

    @php
        $preselectInvoiceId = request('record') ?: old('invoice_id');
    @endphp
    @include('billing.payments._record-modal', ['openInvoices' => $openInvoices, 'preselectInvoiceId' => $preselectInvoiceId])
</div>
@endsection

@push('scripts')
<script>
(function () {
    var $modal = $('#paymentRecordModal');
    if (!$modal.length) return;

    var $select = $('#payment_invoice_id');
    var $amount = $('#payment_amount');

    function fillAmountFromSelect() {
        var opt = $select.find('option:selected');
        var bal = opt.data('balance');
        if (bal !== undefined && bal !== '') {
            $amount.attr('max', bal);
            if (!$amount.data('user-edited')) {
                $amount.val(Math.floor(parseFloat(bal)));
            }
        }
    }

    $amount.on('input', function () {
        $amount.data('user-edited', true);
    });

    $select.on('change', function () {
        $amount.data('user-edited', false);
        fillAmountFromSelect();
    });

    $(document).on('click', '.btn-record-payment', function () {
        var id = $(this).data('invoice-id');
        var bal = $(this).data('balance');
        $amount.data('user-edited', false);
        $select.val(String(id));
        if (bal !== undefined) {
            $amount.attr('max', bal);
            $amount.val(Math.floor(parseFloat(bal)));
        }
        $modal.modal('show');
    });

    $('#btnOpenPaymentModal').on('click', function () {
        if (!$select.val()) {
            $amount.val('');
            $amount.data('user-edited', false);
        }
    });

    @if ($preselectInvoiceId || $errors->any())
        $modal.modal('show');
        fillAmountFromSelect();
    @endif
})();
</script>
@endpush
