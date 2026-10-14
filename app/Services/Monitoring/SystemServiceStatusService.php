<?php

namespace App\Services\Monitoring;

use App\Services\Gateway\GatewayAgentClient;
use Symfony\Component\Process\Process;

class SystemServiceStatusService
{
    /** @var GatewayAgentClient */
    protected $agent;

    public function __construct(GatewayAgentClient $agent)
    {
        $this->agent = $agent;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function snapshot()
    {
        return [
            'apache2' => $this->serviceUnit('apache2'),
            'cloudflared' => $this->serviceUnit('cloudflared'),
            'gateway_agent' => $this->gatewayAgent(),
            'host' => $this->hostMetrics(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serviceUnit($unit)
    {
        $process = new Process(['systemctl', 'is-active', $unit]);
        $process->setTimeout(5);
        $process->run();

        $state = trim($process->getOutput() ?: $process->getErrorOutput());
        $active = $state === 'active';

        return [
            'name' => $unit,
            'active' => $active,
            'state' => $state ?: 'unknown',
            'label' => $active ? 'Aktif' : 'Tidak aktif',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function gatewayAgent()
    {
        if (! $this->agent->isEnabled()) {
            return [
                'name' => 'gateway_agent',
                'active' => false,
                'state' => 'disabled',
                'label' => 'Nonaktif di Laravel',
                'detail' => null,
            ];
        }

        $health = $this->agent->health();
        $ok = ($health['status'] ?? '') === 'ok';

        return [
            'name' => 'gateway_agent',
            'active' => $ok,
            'state' => $health['status'] ?? 'error',
            'label' => $ok ? 'OK' : 'Error',
            'detail' => $health,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function hostMetrics()
    {
        if (! config('monitoring.host_metrics.enabled', true)) {
            return ['available' => false];
        }

        $cached = cache()->get('monitoring.host_metrics');
        if (is_array($cached)) {
            return array_merge(['available' => true, 'source' => 'cache'], $cached);
        }

        return array_merge(['available' => true, 'source' => 'live'], $this->collectHostMetrics());
    }

    /**
     * @return array<string, mixed>
     */
    public function collectHostMetrics()
    {
        $metrics = [
            'collected_at' => now()->toIso8601String(),
            'load_1m' => null,
            'mem_total_mb' => null,
            'mem_available_mb' => null,
            'mem_used_percent' => null,
        ];

        if (is_readable('/proc/loadavg')) {
            $load = file_get_contents('/proc/loadavg');
            if ($load !== false) {
                $parts = preg_split('/\s+/', trim($load));
                $metrics['load_1m'] = isset($parts[0]) ? (float) $parts[0] : null;
            }
        }

        if (is_readable('/proc/meminfo')) {
            $info = file_get_contents('/proc/meminfo');
            if ($info !== false) {
                $totalKb = $this->meminfoValue($info, 'MemTotal');
                $availKb = $this->meminfoValue($info, 'MemAvailable');
                if ($totalKb > 0) {
                    $metrics['mem_total_mb'] = round($totalKb / 1024, 1);
                    $metrics['mem_available_mb'] = $availKb ? round($availKb / 1024, 1) : null;
                    if ($availKb) {
                        $used = max(0, $totalKb - $availKb);
                        $metrics['mem_used_percent'] = round(($used / $totalKb) * 100, 1);
                    }
                }
            }
        }

        $ttl = (int) config('monitoring.host_metrics.cache_ttl_seconds', 300);
        cache()->put('monitoring.host_metrics', $metrics, $ttl);

        return $metrics;
    }

    protected function meminfoValue($contents, $key)
    {
        if (preg_match('/^'.$key.':\s+(\d+)\s+kB/m', $contents, $m)) {
            return (int) $m[1];
        }

        return 0;
    }
}
