@extends('layouts.adminlte')

@section('title', 'Paket Langganan')
@section('page_title', 'Paket Langganan')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar paket</h3>
        @can('manage', App\Models\SubscriptionPlan::class)
            <div class="card-tools"><a href="{{ route('billing.plans.create') }}" class="btn btn-primary btn-sm">Tambah paket</a></div>
        @endcan
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Nama</th><th>Interval</th><th>Harga aktif</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($plans as $plan)
                    @php $price = $plan->getCurrentPriceRecord(); @endphp
                    <tr>
                        <td>{{ $plan->name }}</td>
                        <td>{{ App\Models\SubscriptionPlan::intervals()[$plan->billing_interval] ?? $plan->billing_interval }}</td>
                        <td>{{ $price ? number_format($price->amount, 0, ',', '.') : '—' }}</td>
                        <td>{{ $plan->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center p-3 text-muted">Belum ada paket.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
