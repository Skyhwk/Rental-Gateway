<?php

return [
    'health' => [
        'timeout_seconds' => (int) env('MONITORING_HEALTH_TIMEOUT', 5),
        // Path probe ke backend client (host:port aplikasi). BUKAN /v1/health (itu Gateway Agent :8750).
        'path' => env('MONITORING_HEALTH_PATH', '/'),
        'success_status_min' => 200,
        'success_status_max' => 399,
    ],
    'host_metrics' => [
        'enabled' => env('MONITORING_HOST_METRICS', true),
        'cache_ttl_seconds' => (int) env('MONITORING_HOST_METRICS_TTL', 300),
    ],
];
