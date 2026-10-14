@extends('layouts.adminlte')

@section('title', 'Aplikasi')
@section('page_title', 'Aplikasi')

@section('breadcrumb')
    <li class="breadcrumb-item active">Aplikasi</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Aplikasi</h3>
        <div class="card-tools">
            @can('create', App\Models\Application::class)
                <a href="{{ route('applications.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah</a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="form-inline mb-3 flex-wrap">
            <input type="search" name="q" class="form-control mr-2 mb-2" placeholder="Cari nama/domain..." value="{{ request('q') }}">
            <select name="access_status" class="form-control mr-2 mb-2">
                <option value="">Semua status akses</option>
                @foreach ($accessStatuses as $value => $accessLabel)
                    <option value="{{ $value }}" {{ request('access_status') === $value ? 'selected' : '' }}>{{ $accessLabel }}</option>
                @endforeach
            </select>
            <select name="health_status" class="form-control mr-2 mb-2">
                <option value="">Semua health</option>
                @foreach ($healthStatuses as $value => $healthFilterLabel)
                    <option value="{{ $value }}" {{ request('health_status') === $value ? 'selected' : '' }}>{{ $healthFilterLabel }}</option>
                @endforeach
            </select>
            <button class="btn btn-default mb-2" type="submit">Filter</button>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Client</th>
                        <th>Domain utama</th>
                        <th>Backend</th>
                        <th>Akses</th>
                        <th>Health</th>
                        <th width="90"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td>{{ $application->name }}</td>
                            <td>{{ optional($application->client)->company_name }}</td>
                            <td><code>{{ optional($application->primaryDomain)->hostname ?? '—' }}</code></td>
                            <td><code>{{ $application->internal_host }}:{{ $application->internal_port }}</code></td>
                            <td>@include('partials.status-badge', ['status' => $application->access_status, 'label' => $accessStatuses[$application->access_status] ?? $application->access_status])</td>
                            <td>@include('partials.health-badge', ['status' => $application->health_status])</td>
                            <td><a href="{{ route('applications.show', $application) }}" class="btn btn-xs btn-info">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada aplikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($applications->hasPages())
        <div class="card-footer">{{ $applications->links() }}</div>
    @endif
</div>
@endsection
