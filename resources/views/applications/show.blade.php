@extends('layouts.adminlte')

@section('title', $application->name)
@section('page_title', $application->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('applications.index') }}">Aplikasi</a></li>
    <li class="breadcrumb-item active">{{ $application->name }}</li>
@endsection

@section('content')
<div class="mb-3">
    <a href="{{ route('applications.index') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke daftar</a>
    @can('update', $application)
        <a href="{{ route('applications.edit', $application) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Ubah</a>
    @endcan
    @can('gateway.sync')
        <form method="post" action="{{ route('applications.gateway.sync', $application) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-sync"></i> Sinkronkan</button>
        </form>
        @if ($syncState === 'failed')
            <form method="post" action="{{ route('applications.gateway.retry', $application) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-redo"></i> Retry Sync</button>
            </form>
        @endif
    @endcan
    <a href="{{ route('clients.show', $application->client) }}" class="btn btn-default btn-sm">Client: {{ $application->client->company_name }}</a>
</div>

@can('gateway.sync')
    @if (! empty($gatewayInfra) && ! $gatewayInfra['ready'])
        <div class="alert alert-warning">
            <strong>Gateway belum melayani traffic publik.</strong>
            Sinkronisasi ke file <code>{{ $gatewayInfra['apps_config_dir'] }}</code> bisa sukses, tetapi
            @if (! $gatewayInfra['listener_open'])
                Apache tidak listen di port <code>{{ $gatewayInfra['listener_port'] }}</code> — pasang <code>deploy/apache/rental-gateway.conf</code> (<code>a2enconf rental-gateway</code>).
            @else
                belum ada include / route tunnel ke gateway.
            @endif
            Selama tunnel/vhost lama masih mengarah ke <code>{{ $application->internal_host }}:{{ $application->internal_port }}</code>, suspend/aktif dari panel <em>tidak</em> mengubah akses domain publik.
        </div>
    @endif
    @if (! empty($gatewayHostnameConflicts))
        <div class="alert alert-danger">
            <strong>Hostname bentrok di Apache gateway.</strong>
            Beberapa file vhost lama masih memetakan
            @foreach ($gatewayHostnameConflicts as $host)
                <code>{{ $host }}</code>@if (! $loop->last), @endif
            @endforeach
            ke backend (mode aktif). Apache memakai vhost <em>pertama</em> yang cocok — status suspend di panel bisa sudah benar tetapi situs tetap hidup.
            Jalankan <code>php artisan gateway:prune-orphans</code> atau klik <em>Sinkronkan</em> setelah membersihkan config yatim
            @if (! empty($gatewayInfra['orphan_config_ids']))
                (yatim: app-{{ implode(', app-', $gatewayInfra['orphan_config_ids']) }}).
            @endif
        </div>
    @elseif (! empty($gatewayInfra['orphan_config_ids']))
        <div class="alert alert-warning">
            <strong>Config gateway yatim.</strong> File untuk aplikasi yang sudah dihapus masih ada:
            app-{{ implode(', app-', $gatewayInfra['orphan_config_ids']) }}.
            Jalankan <code>php artisan gateway:prune-orphans</code> agar suspend/aktif efektif.
        </div>
    @endif
@endcan

<div class="row">
    <div class="col-lg-6">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Routing & Backend</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Domain utama</dt>
                    <dd class="col-sm-8"><code>{{ optional($application->primaryDomain)->hostname ?? '—' }}</code></dd>
                    <dt class="col-sm-4">Domain lain</dt>
                    <dd class="col-sm-8">
                        @php $others = $application->domains->where('is_primary', false); @endphp
                        @forelse ($others as $d)
                            <div><code>{{ $d->hostname }}</code></div>
                        @empty
                            —
                        @endforelse
                    </dd>
                    <dt class="col-sm-4">Backend</dt>
                    <dd class="col-sm-8"><code>{{ $application->internal_host }}:{{ $application->internal_port }}</code></dd>
                    <dt class="col-sm-4">Gateway node</dt>
                    <dd class="col-sm-8">{{ optional($application->gatewayNode)->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title">Status akses & gateway</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Status akses (desired)</dt>
                    <dd class="col-sm-7">@include('partials.status-badge', ['status' => $application->access_status, 'label' => App\Models\Application::accessStatuses()[$application->access_status] ?? $application->access_status])</dd>
                    <dt class="col-sm-5">Gateway (applied)</dt>
                    <dd class="col-sm-7">
                        @if ($application->gateway_applied_status)
                            @include('partials.status-badge', ['status' => $application->gateway_applied_status, 'label' => App\Models\Application::accessStatuses()[$application->gateway_applied_status] ?? $application->gateway_applied_status])
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-sm-5">Sinkronisasi</dt>
                    <dd class="col-sm-7">@include('partials.gateway-sync-badge', ['syncState' => $syncState])</dd>
                    <dt class="col-sm-5">Health backend</dt>
                    <dd class="col-sm-7">
                        @include('partials.health-badge', ['status' => $application->health_status])
                        <span class="text-muted small d-block">Probe: <code>{{ $application->healthProbeUrl() }}</code>
                            @if ($application->healthProbeHostname())
                                · Host: <code>{{ $application->healthProbeHostname() }}</code>
                            @endif
                        </span>
                        @if ($application->health_checked_at)
                            <span class="text-muted small d-block">
                                {{ $application->health_checked_at->format('d/m/Y H:i') }}
                                @if ($application->health_http_code) · HTTP {{ $application->health_http_code }} @endif
                                @if ($application->health_last_error && $application->health_last_error !== 'HTTP '.$application->health_http_code)
                                    · {{ $application->health_last_error }}
                                @endif
                            </span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@can('applications.manage')
<div class="row">
    <div class="col-lg-8">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Kontrol akses</h3></div>
            <form method="post" action="{{ route('applications.access.update', $application) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label for="access_status">Ubah status</label>
                        <select name="access_status" id="access_status" class="form-control" required>
                            @foreach (App\Models\Application::accessStatuses() as $value => $label)
                                <option value="{{ $value }}" {{ $application->access_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="reason">Alasan <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" rows="3" class="form-control" required minlength="5" placeholder="Contoh: tunggakan invoice #INV-001 / maintenance database"></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Terapkan status & sync gateway</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="callout callout-info">
            <p class="mb-1"><strong>Aktif</strong> — proxy ke backend.</p>
            <p class="mb-1"><strong>Ditangguhkan</strong> — halaman suspend (tanpa backend).</p>
            <p class="mb-0"><strong>Pemeliharaan</strong> — halaman maintenance.</p>
        </div>
    </div>
</div>
@endcan

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Histori status akses</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Waktu</th><th>Dari</th><th>Ke</th><th>Admin</th><th>Alasan</th></tr></thead>
                    <tbody>
                        @forelse ($accessLogs as $log)
                            <tr>
                                <td class="small">{{ $log->created_at }}</td>
                                <td>{{ $log->from_status ?: '—' }}</td>
                                <td>{{ $log->to_status }}</td>
                                <td>{{ optional($log->user)->name ?? '—' }}</td>
                                <td class="small">{{ Str::limit($log->reason, 80) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center p-3">Belum ada perubahan status.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Log sinkronisasi gateway</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Waktu</th><th>Desired</th><th>Status</th><th>Sumber</th><th>Error</th></tr></thead>
                    <tbody>
                        @forelse ($syncLogs as $log)
                            <tr>
                                <td class="small">{{ $log->created_at }}</td>
                                <td>{{ $log->desired_status }}</td>
                                <td>{{ $log->sync_status }}</td>
                                <td>{{ $log->source }}</td>
                                <td class="text-danger small">{{ $log->error_message }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center p-3">Belum ada sinkronisasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if ($application->technical_notes)
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Catatan teknis</h3></div>
        <div class="card-body">{{ $application->technical_notes }}</div>
    </div>
@endif
@endsection
