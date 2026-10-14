@extends('layouts.adminlte')

@section('title', 'Monitoring')
@section('page_title', 'Monitoring & Layanan')

@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $healthSummary['up'] }}</h3>
                <p>Backend OK</p>
            </div>
            <div class="icon"><i class="fas fa-heartbeat"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $healthSummary['down'] }}</h3>
                <p>Backend down</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $healthSummary['unknown'] }}</h3>
                <p>Belum dicek</p>
            </div>
            <div class="icon"><i class="fas fa-question-circle"></i></div>
        </div>
    </div>
</div>

@can('applications.manage')
<form method="post" action="{{ route('monitoring.health-check') }}" class="mb-3">
    @csrf
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-stethoscope"></i> Jalankan health check sekarang</button>
</form>
@endcan

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Status layanan host</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Layanan</th><th>Status</th><th>Detail</th></tr></thead>
                    <tbody>
                        @foreach (['apache2', 'cloudflared', 'gateway_agent'] as $key)
                            @php $svc = $services[$key] ?? []; @endphp
                            <tr>
                                <td><code>{{ $svc['name'] ?? $key }}</code></td>
                                <td>
                                    @if (! empty($svc['active']))
                                        <span class="badge badge-success">{{ $svc['label'] ?? 'OK' }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ $svc['label'] ?? 'Down' }}</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    @if ($key === 'gateway_agent' && ! empty($svc['detail']))
                                        last_apply: {{ $svc['detail']['last_apply_at'] ?? '—' }}
                                    @else
                                        {{ $svc['state'] ?? '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        @php $host = $services['host'] ?? []; @endphp
        <div class="card">
            <div class="card-header"><h3 class="card-title">Host (load / RAM)</h3></div>
            <div class="card-body">
                @if (empty($host['available']))
                    <p class="text-muted mb-0">Metrik host tidak tersedia.</p>
                @else
                    <p class="mb-1"><strong>Load (1m):</strong> {{ $host['load_1m'] ?? '—' }}</p>
                    <p class="mb-1"><strong>RAM terpakai:</strong> {{ isset($host['mem_used_percent']) ? $host['mem_used_percent'].'%' : '—' }}</p>
                    <p class="mb-0 text-muted small">Sumber: {{ $host['source'] ?? 'live' }} · {{ $host['collected_at'] ?? '' }}</p>
                @endif
            </div>
        </div>
        <div class="card card-outline card-info">
            <div class="card-body small mb-0">
                <p class="mb-1"><strong>Background (Supervisor)</strong></p>
                <ul class="pl-3 mb-0 text-muted">
                    <li><code>rental-manager-schedule</code> — jadwal Laravel (health check tiap 5 menit)</li>
                    <li><code>rental-manager-worker</code> — antrian job (<code>QUEUE_CONNECTION=database</code>)</li>
                </ul>
                <p class="mt-2 mb-0 text-muted">Tidak perlu cron <code>schedule:run</code> jika Supervisor schedule sudah RUNNING.</p>
            </div>
        </div>
    </div>
</div>
@endsection
