@extends('layouts.adminlte')

@section('title', 'Pembayaran')
@section('page_title', 'Pembayaran')

@section('content')
<div class="mb-3">
    @can('create', App\Models\Payment::class)
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#paymentRecordModal" id="btnOpenPaymentModal">
            <i class="fas fa-plus"></i> Catat pembayaran
        </button>
    @endcan
</div>

@if ($pendingCount > 0)
    <div class="alert alert-warning">
        <strong>{{ $pendingCount }}</strong> pembayaran menunggu verifikasi.
        <a href="{{ route('billing.payments.index', ['filter' => 'pending']) }}">Tampilkan</a>
    </div>
@endif

<div class="card card-outline card-warning">
    <div class="card-header">
        <h3 class="card-title">Invoice belum lunas</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr><th>Invoice</th><th>Client</th><th>Jatuh tempo</th><th>Sisa</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($unpaidInvoices as $inv)
                    <tr>
                        <td><a href="{{ route('billing.invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
                        <td>{{ optional($inv->subscription->client)->company_name }}</td>
                        <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                        <td>Rp {{ number_format($inv->balanceDue(), 0, ',', '.') }}</td>
                        <td>
                            @can('create', App\Models\Payment::class)
                                <button type="button" class="btn btn-xs btn-success btn-record-payment"
                                    data-invoice-id="{{ $inv->id }}"
                                    data-balance="{{ $inv->balanceDue() }}">
                                    Catat bayar
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center p-3 text-muted">Semua invoice sudah lunas atau tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Riwayat pembayaran</h3>
        <div class="card-tools">
            <a href="{{ route('billing.payments.index') }}" class="btn btn-xs btn-default {{ request('filter') !== 'pending' ? 'active' : '' }}">Semua</a>
            <a href="{{ route('billing.payments.index', ['filter' => 'pending']) }}" class="btn btn-xs btn-warning {{ request('filter') === 'pending' ? 'active' : '' }}">Menunggu verifikasi</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>Invoice</th><th>Client</th><th>Tanggal</th><th>Nominal</th><th>Ref</th><th>Status</th><th>Verifikator</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($payments as $pay)
                    <tr>
                        <td>
                            @if ($pay->invoice)
                                <a href="{{ route('billing.invoices.show', $pay->invoice) }}">{{ $pay->invoice->invoice_number }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ optional(optional($pay->invoice)->subscription)->client->company_name ?? '—' }}</td>
                        <td>{{ $pay->paid_at->format('d/m/Y') }}</td>
                        <td>{{ number_format($pay->amount, 0, ',', '.') }}</td>
                        <td>{{ $pay->reference_number ?: '—' }}</td>
                        <td>{{ App\Models\Payment::verificationStatuses()[$pay->verification_status] ?? $pay->verification_status }}</td>
                        <td>{{ optional($pay->verifiedBy)->name ?? '—' }}</td>
                        <td class="text-nowrap">
                            @can('verify', $pay)
                                @if ($pay->verification_status === App\Models\Payment::STATUS_PENDING)
                                    <form method="post" action="{{ route('billing.payments.verify', $pay) }}" class="d-inline">@csrf<button class="btn btn-xs btn-success">Verifikasi</button></form>
                                    <form method="post" action="{{ route('billing.payments.reject', $pay) }}" class="d-inline">@csrf<button class="btn btn-xs btn-danger">Tolak</button></form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center p-3 text-muted">Belum ada pembayaran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($payments->hasPages())
        <div class="card-footer">{{ $payments->links() }}</div>
    @endif
</div>

@php
    $preselectInvoiceId = request('record') ?: old('invoice_id');
@endphp
@include('billing.payments._record-modal', ['openInvoices' => $openInvoices, 'preselectInvoiceId' => $preselectInvoiceId])

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

    $('.btn-record-payment').on('click', function () {
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
