@extends('layouts.adminlte')

@section('title', 'Gateway')
@section('page_title', 'Gateway Apache')

@section('breadcrumb')
    <li class="breadcrumb-item active">Gateway</li>
@endsection

@section('content')
@if (! $gatewayInfra['ready'])
    <div class="alert alert-warning">
        Port gateway <code>{{ $gatewayInfra['listener_port'] }}</code> belum listen di server.
        File vhost ada ({{ $gatewayInfra['app_config_count'] }}), tapi traffic publik belum lewat gateway sampai Apache include + tunnel diarahkan ke <code>http://127.0.0.1:{{ $gatewayInfra['listener_port'] }}</code>.
        Lihat <code>deploy/apache/rental-gateway.conf</code>.
    </div>
@endif
<div class="row">
    <div class="col-md-6">
        <div class="card card-outline {{ $agentEnabled ? 'card-success' : 'card-warning' }}">
            <div class="card-header"><h3 class="card-title">Gateway Agent</h3></div>
            <div class="card-body">
                <p><strong>Laravel:</strong>
                    @if ($agentEnabled)
                        <span class="badge badge-success">Enabled</span>
                    @else
                        <span class="badge badge-warning">Disabled</span>
                    @endif
                </p>
                <p class="mb-1"><strong>Systemd (<code>{{ $systemdUnit }}</code>):</strong>
                    @if (! empty($serviceStatus['state']) && $serviceStatus['state'] === 'active')
                        <span class="badge badge-success">active</span>
                    @else
                        <span class="badge badge-danger">{{ $serviceStatus['state'] ?? 'inactive' }}</span>
                    @endif
                </p>
                <p class="mb-1"><strong>Health HTTP:</strong>
                    @if ($agentOk)
                        <span class="badge badge-success">OK</span>
                    @else
                        <span class="badge badge-danger">Error</span>
                    @endif
                    @if (! empty($agentHealth['last_apply_at']))
                        <span class="text-muted small"> · last apply {{ $agentHealth['last_apply_at'] }}</span>
                    @endif
                </p>
                <p class="mb-1"><strong>Apache2:</strong>
                    @if (! empty($apache['active']))
                        <span class="badge badge-success">{{ $apache['label'] ?? 'active' }}</span>
                    @else
                        <span class="badge badge-secondary">{{ $apache['state'] ?? 'unknown' }}</span>
                    @endif
                </p>
                <p class="mb-1"><strong>Config root:</strong> <code>{{ $configRoot }}</code></p>
                <p class="mb-3"><strong>Listener port:</strong> <code>{{ $listenerPort }}</code></p>

                @if ($serviceControlEnabled)
                    <form method="post" action="{{ route('gateway.agent.start') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-play"></i> Start agent</button>
                    </form>
                    <form method="post" action="{{ route('gateway.agent.restart') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-redo"></i> Restart agent</button>
                    </form>
                @else
                    <p class="text-muted small mb-0">Kontrol systemd dinonaktifkan di konfigurasi.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Boot & operasi</h3></div>
            <div class="card-body">
                <ol class="pl-3 mb-0">
                    <li>Auto-start: <code>systemctl enable {{ $systemdUnit }}</code> (via <code>install-gateway-agent-service.sh</code>)</li>
                    <li>Include Apache: <code>gateway/apache/rental-gateway.conf.sample</code></li>
                    <li>Aktifkan <code>mod_headers</code> bila belum</li>
                    <li>Sinkronkan per aplikasi dari halaman detail</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
