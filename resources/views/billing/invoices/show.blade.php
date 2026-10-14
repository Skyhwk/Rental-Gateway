@extends('layouts.adminlte')

@section('title', $invoice->invoice_number)
@section('page_title', 'Invoice '.$invoice->invoice_number)

@section('content')
<div class="mb-3">
    <a href="{{ route('billing.invoices.index') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke rekap</a>
    <a href="{{ route('billing.invoices.pdf', $invoice) }}" class="btn btn-primary btn-sm" target="_blank"><i class="fas fa-file-pdf"></i> Lihat / Cetak PDF</a>
    <a href="{{ route('billing.invoices.download', $invoice) }}" class="btn btn-default btn-sm"><i class="fas fa-download"></i> Unduh file invoice</a>
    <button type="button" class="btn btn-default btn-sm" disabled title="Segera hadir"><i class="fas fa-envelope"></i> Kirim email (coming soon)</button>
    @can('create', App\Models\Payment::class)
        @if ($invoice->balanceDue() > 0)
            <a href="{{ route('billing.payments.index', ['record' => $invoice->id]) }}" class="btn btn-success btn-sm"><i class="fas fa-money-bill-wave"></i> Catat pembayaran</a>
        @endif
    @endcan
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Ringkasan tagihan</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Client</dt>
                    <dd class="col-sm-8">{{ $invoice->subscription->client->company_name }}</dd>
                    <dt class="col-sm-4">Aplikasi</dt>
                    <dd class="col-sm-8">{{ $invoice->subscription->application->name }}</dd>
                    <dt class="col-sm-4">Langganan</dt>
                    <dd class="col-sm-8"><a href="{{ route('billing.subscriptions.show', $invoice->subscription) }}">#{{ $invoice->subscription_id }}</a></dd>
                    <dt class="col-sm-4">Periode</dt>
                    <dd class="col-sm-8">{{ $invoice->period_start->format('d/m/Y') }} — {{ $invoice->period_end->format('d/m/Y') }}</dd>
                    <dt class="col-sm-4">Jatuh tempo</dt>
                    <dd class="col-sm-8">{{ $invoice->due_date->format('d/m/Y') }}</dd>
                    <dt class="col-sm-4">Total</dt>
                    <dd class="col-sm-8">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</dd>
                    <dt class="col-sm-4">Terbayar</dt>
                    <dd class="col-sm-8">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</dd>
                    <dt class="col-sm-4">Sisa</dt>
                    <dd class="col-sm-8">Rp {{ number_format($invoice->balanceDue(), 0, ',', '.') }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">{{ App\Models\Invoice::statuses()[$invoice->status] ?? $invoice->status }}</dd>
                </dl>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Item</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Deskripsi</th><th class="text-right">Jumlah</th></tr></thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="text-right">{{ number_format($item->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="callout callout-info">
            <p class="mb-0">Halaman ini hanya untuk <strong>rekap &amp; unduh</strong>. Riwayat dan verifikasi pembayaran ada di menu <a href="{{ route('billing.payments.index') }}">Pembayaran</a>.</p>
        </div>
    </div>
</div>
@endsection
