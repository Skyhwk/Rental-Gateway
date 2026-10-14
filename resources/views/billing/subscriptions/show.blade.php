@extends('layouts.adminlte')

@section('title', 'Langganan #'.$subscription->id)
@section('page_title', 'Detail Langganan')

@section('content')
<div class="mb-3">
    @can('update', $subscription)
        <a href="{{ route('billing.subscriptions.edit', $subscription) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Ubah langganan</a>
    @endcan
    @can('create', App\Models\Invoice::class)
        <form method="post" action="{{ route('billing.invoices.store', $subscription) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-default btn-sm">Buat invoice manual</button>
        </form>
    @endcan
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <p><strong>Client:</strong> {{ $subscription->client->company_name }}</p>
                <p><strong>Aplikasi:</strong> <a href="{{ route('applications.show', $subscription->application) }}">{{ $subscription->application->name }}</a></p>
                <p><strong>Paket:</strong> {{ $subscription->plan->name }} — Rp {{ number_format($subscription->price_amount, 0, ',', '.') }}</p>
                <p><strong>Mulai:</strong> {{ optional($subscription->starts_at)->format('d/m/Y') }}</p>
                <p><strong>Perpanjangan / jatuh tempo (sistem):</strong>
                    {{ optional($subscription->next_billing_date)->format('d/m/Y') ?? '—' }}
                    @if ($subscription->plan)
                        <span class="text-muted small">({{ App\Models\SubscriptionPlan::intervals()[$subscription->plan->billing_interval] ?? '' }})</span>
                    @endif
                </p>
                <p><strong>Toleransi keterlambatan:</strong> {{ $subscription->grace_days }} hari</p>
                @if ($billingScheduleHint && $subscription->starts_at)
                    <p class="text-muted small mb-0">Siklus: mulai {{ $subscription->starts_at->format('d/m/Y') }} → perpanjangan pertama {{ $billingScheduleHint->format('d/m/Y') }} · invoice H-{{ (int) config('billing.invoice_generate_days_before_due', 7) }}.</p>
                @endif
                <p><strong>Status:</strong> {{ App\Models\Subscription::statuses()[$subscription->status] ?? $subscription->status }}</p>
                @if ($subscription->notes)
                    <p><strong>Catatan:</strong> {{ $subscription->notes }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h3 class="card-title">Invoice langganan ini</h3></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>Nomor</th><th>Jatuh tempo</th><th>Total</th><th>Dibayar</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($subscription->invoices as $inv)
                    <tr>
                        <td><a href="{{ route('billing.invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
                        <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                        <td>{{ number_format($inv->amount, 0, ',', '.') }}</td>
                        <td>{{ number_format($inv->amount_paid, 0, ',', '.') }}</td>
                        <td>{{ App\Models\Invoice::statuses()[$inv->status] ?? $inv->status }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('billing.invoices.pdf', $inv) }}" class="btn btn-xs btn-default" target="_blank">PDF</a>
                            @can('delete', $inv)
                                <form method="post" action="{{ route('billing.subscriptions.invoices.destroy', [$subscription, $inv]) }}" class="d-inline" onsubmit="return confirm('Hapus invoice {{ $inv->invoice_number }}? Hanya untuk duplikat / salah buat.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Hapus</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center p-3 text-muted">Belum ada invoice.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        Hapus invoice hanya jika belum ada pembayaran terverifikasi (misalnya invoice dobel).
    </div>
</div>
@endsection
