<?php

namespace App\Console\Commands;

use App\Services\Monitoring\SystemServiceStatusService;
use Illuminate\Console\Command;

class MonitoringCollectHostMetricsCommand extends Command
{
    protected $signature = 'monitoring:collect-host-metrics';

    protected $description = 'Kumpulkan load/RAM host ke cache monitoring';

    public function handle(SystemServiceStatusService $status)
    {
        if (! config('monitoring.host_metrics.enabled', true)) {
            $this->warn('Host metrics dinonaktifkan.');

            return 0;
        }

        $metrics = $status->collectHostMetrics();
        $this->info('Metrics: load_1m='.($metrics['load_1m'] ?? '-').', RAM used='.($metrics['mem_used_percent'] ?? '-').'%');

        return 0;
    }
}
