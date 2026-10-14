@extends('layouts.adminlte')

@section('title', $client->company_name)
@section('page_title', $client->company_name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Client</a></li>
    <li class="breadcrumb-item active">{{ $client->company_name }}</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        @can('update', $client)
            <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Ubah</a>
        @endcan
        @can('create', App\Models\Application::class)
            <a href="{{ route('applications.create', ['client_id' => $client->id]) }}" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Tambah Aplikasi</a>
        @endcan
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Informasi Client</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">PIC</dt>
                    <dd class="col-sm-9">{{ $client->pic_name }}</dd>
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $client->email }}</dd>
                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">@include('partials.status-badge', ['status' => $client->status, 'label' => App\Models\Client::statuses()[$client->status] ?? $client->status])</dd>
                    <dt class="col-sm-3">Telepon</dt>
                    <dd class="col-sm-9">{{ $client->phone ?: '—' }}</dd>
                    <dt class="col-sm-3">Alamat</dt>
                    <dd class="col-sm-9">{{ $client->address ?: '—' }}</dd>
                    <dt class="col-sm-3">Catatan internal</dt>
                    <dd class="col-sm-9">{{ $client->internal_notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Aplikasi Disewa</h3></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Domain utama</th>
                    <th>Port</th>
                    <th>Status akses</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($client->applications as $app)
                    <tr>
                        <td>{{ $app->name }}</td>
                        <td><code>{{ optional($app->primaryDomain)->hostname ?? '—' }}</code></td>
                        <td><code>{{ $app->internal_host }}:{{ $app->internal_port }}</code></td>
                        <td>@include('partials.status-badge', ['status' => $app->access_status, 'label' => App\Models\Application::accessStatuses()[$app->access_status] ?? $app->access_status])</td>
                        <td><a href="{{ route('applications.show', $app) }}" class="btn btn-xs btn-info">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted p-3">Belum ada aplikasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
