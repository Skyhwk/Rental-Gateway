<?php

return [
    'agent' => [
        'enabled' => env('GATEWAY_AGENT_ENABLED', false),
        'base_url' => env('GATEWAY_AGENT_BASE_URL', 'http://127.0.0.1:8750'),
        'token' => env('GATEWAY_AGENT_TOKEN'),
        'timeout' => (int) env('GATEWAY_AGENT_TIMEOUT_SECONDS', 30),
    ],
    'config_root' => env('GATEWAY_CONFIG_ROOT', '/etc/apache2/rental-gateway'),
    'allowed_hosts' => array_filter(array_map('trim', explode(',', env('GATEWAY_ALLOWED_HOST', '127.0.0.1')))),
    // Port aplikasi client sering < 1024 (mis. 450); batasi via .env jika perlu.
    'port_min' => (int) env('GATEWAY_PORT_MIN', 1),
    'port_max' => (int) env('GATEWAY_PORT_MAX', 65535),
    'listener_port' => (int) env('GATEWAY_LISTENER_PORT', 8080),
    'static_root' => env('GATEWAY_STATIC_ROOT', base_path('gateway/static')),

    'service' => [
        'systemd_unit' => env('GATEWAY_AGENT_SYSTEMD_UNIT', 'rental-gateway-agent'),
        'control_enabled' => env('GATEWAY_AGENT_SERVICE_CONTROL', true),
        'ctl_command' => env('GATEWAY_AGENT_CTL', '/usr/local/sbin/rental-gateway-agent-ctl'),
    ],
];
