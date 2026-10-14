@extends('layouts.adminlte')

@section('title', 'Langganan')
@section('page_title', 'Langganan')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar langganan</h3>
        @can('create', App\Models\Subscription::class)
            <div class="card-tools"><a href="{{ route('billing.subscriptions.create') }}" class="btn btn-primary btn-sm">Buat langganan</a></div>
        @endcan
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr><th>Client</th><th>Aplikasi</th><th>Paket</th><th>Harga</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $sub)
                    <tr>
                        <td>{{ $sub->client->company_name }}</td>
                        <td>{{ $sub->application->name }}</td>
                        <td>{{ $sub->plan->name }}</td>
                        <td>{{ number_format($sub->price_amount, 0, ',', '.') }}</td>
                        <td>{{ $statuses[$sub->status] ?? $sub->status }}</td>
                        <td><a href="{{ route('billing.subscriptions.show', $sub) }}" class="btn btn-xs btn-info">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center p-3 text-muted">Belum ada langganan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($subscriptions->hasPages())<div class="card-footer">{{ $subscriptions->links() }}</div>@endif
</div>
@endsection
