@extends('layouts.adminlte')

@section('title', 'Client')
@section('page_title', 'Client')

@section('breadcrumb')
    <li class="breadcrumb-item active">Client</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Client</h3>
        <div class="card-tools">
            @can('create', App\Models\Client::class)
                <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah</a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="form-inline mb-3 flex-wrap">
            <input type="search" name="q" class="form-control mr-2 mb-2" placeholder="Cari..." value="{{ request('q') }}">
            <select name="status" class="form-control mr-2 mb-2">
                <option value="">Semua status</option>
                @foreach (App\Models\Client::statuses() as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-default mb-2" type="submit">Filter</button>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm">
                <thead>
                    <tr>
                        <th>Perusahaan</th>
                        <th>PIC</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-center">Aplikasi</th>
                        <th width="90"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td>{{ $client->company_name }}</td>
                            <td>{{ $client->pic_name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>@include('partials.status-badge', ['status' => $client->status, 'label' => App\Models\Client::statuses()[$client->status] ?? $client->status])</td>
                            <td class="text-center">{{ $client->applications_count }}</td>
                            <td><a href="{{ route('clients.show', $client) }}" class="btn btn-xs btn-info">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Belum ada client.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($clients->hasPages())
        <div class="card-footer">{{ $clients->links() }}</div>
    @endif
</div>
@endsection
