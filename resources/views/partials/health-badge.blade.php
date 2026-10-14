@php
    // Jangan pakai $label — variabel itu sering dipakai loop filter di halaman induk (mis. daftar aplikasi).
    $healthStatus = $status ?? 'unknown';
    if (! isset($healthLabel)) {
        $healthLabel = App\Models\Application::healthStatuses()[$healthStatus] ?? $healthStatus;
    }
    $healthClass = [
        'up' => 'success',
        'down' => 'danger',
        'unknown' => 'secondary',
    ][$healthStatus] ?? 'secondary';
@endphp
<span class="badge badge-{{ $healthClass }}">{{ $healthLabel }}</span>
