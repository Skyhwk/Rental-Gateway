@extends('layouts.adminlte')

@section('title', 'Invoice')
@section('page_title', 'Rekap Invoice')

@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $recap['paid'] }}</h3>
                <p>Sudah lunas</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <a href="{{ route('billing.invoices.index', ['payment_state' => 'paid']) }}" class="small-box-footer">Lihat <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $recap['unpaid'] }}</h3>
                <p>Belum lunas</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
            <a href="{{ route('billing.invoices.index', ['payment_state' => 'unpaid']) }}" class="small-box-footer">Lihat <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $recap['void'] }}</h3>
                <p>Batal (void)</p>
            </div>
            <div class="icon"><i class="fas fa-ban"></i></div>
            <a href="{{ route('billing.invoices.index', ['status' => 'void']) }}" class="small-box-footer">Lihat <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar invoice</h3>
        <div class="card-tools">
            <a href="{{ route('billing.invoices.export') }}" class="btn btn-default btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
            <button type="button" class="btn btn-default btn-sm" disabled title="Segera hadir"><i class="fas fa-envelope"></i> Kirim email (coming soon)</button>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="form-inline mb-3 flex-wrap">
            <select name="payment_state" class="form-control mr-2 mb-2">
                <option value="">Semua (lunasi)</option>
                <option value="paid" {{ request('payment_state') === 'paid' ? 'selected' : '' }}>Sudah bayar</option>
                <option value="unpaid" {{ request('payment_state') === 'unpaid' ? 'selected' : '' }}>Belum bayar</option>
            </select>
            <select name="status" class="form-control mr-2 mb-2">
                <option value="">Semua status detail</option>
                @foreach ($statuses as $k => $label)
                    <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-default mb-2" type="submit">Filter</button>
            @if (request()->hasAny(['payment_state', 'status']))
                <a href="{{ route('billing.invoices.index') }}" class="btn btn-link mb-2">Reset</a>
            @endif
        </form>
        <p class="text-muted small">Untuk <strong>catat atau verifikasi pembayaran</strong>, gunakan menu <a href="{{ route('billing.payments.index') }}">Pembayaran</a>.</p>
        <table class="table table-sm table-bordered">
            <thead>
                <tr><th>Nomor</th><th>Client</th><th>Aplikasi</th><th>Jatuh tempo</th><th>Total</th><th>Terbayar</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($invoices as $inv)
                    <tr>
                        <td>{{ $inv->invoice_number }}</td>
                        <td>{{ optional($inv->subscription->client)->company_name }}</td>
                        <td>{{ optional($inv->subscription->application)->name }}</td>
                        <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                        <td>{{ number_format($inv->amount, 0, ',', '.') }}</td>
                        <td>{{ number_format($inv->amount_paid, 0, ',', '.') }}</td>
                        <td>{{ $statuses[$inv->status] ?? $inv->status }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('billing.invoices.show', $inv) }}" class="btn btn-xs btn-info">Detail</a>
                            <button type="button" class="btn btn-xs btn-default btn-invoice-preview"
                                data-preview-url="{{ route('billing.invoices.pdf', ['invoice' => $inv, 'embed' => 1], false) }}"
                                data-page-url="{{ route('billing.invoices.pdf', $inv, false) }}"
                                data-download-url="{{ route('billing.invoices.download', $inv, false) }}"
                                data-invoice-number="{{ $inv->invoice_number }}">
                                <i class="fas fa-eye mr-1"></i> Lihat
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $invoices->links() }}
    </div>
</div>

@include('billing.invoices._preview-modal')
@endsection
