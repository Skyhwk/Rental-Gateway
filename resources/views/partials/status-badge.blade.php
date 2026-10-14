@php
    $map = [
        'active' => 'success',
        'inactive' => 'secondary',
        'archived' => 'dark',
        'suspended' => 'danger',
        'maintenance' => 'warning',
        'unknown' => 'secondary',
        'up' => 'success',
        'down' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
@endphp
<span class="badge badge-{{ $class }}">{{ $label ?? $status }}</span>
