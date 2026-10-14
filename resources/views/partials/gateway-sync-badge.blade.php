@php
    $map = [
        'synced' => 'success',
        'pending' => 'warning',
        'failed' => 'danger',
        'unknown' => 'secondary',
    ];
    $labels = [
        'synced' => 'Sinkron',
        'pending' => 'Menunggu',
        'failed' => 'Gagal',
        'unknown' => 'Belum sync',
    ];
    $class = $map[$syncState] ?? 'secondary';
    $label = $labels[$syncState] ?? $syncState;
@endphp
<span class="badge badge-{{ $class }}">{{ $label }}</span>
