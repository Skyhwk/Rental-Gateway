<?php

namespace App\Console\Commands;

use App\Services\Monitoring\ApplicationHealthCheckService;
use Illuminate\Console\Command;

class MonitoringHealthCheckCommand extends Command
{
    protected $signature = 'monitoring:health-check-applications';

    protected $description = 'Probe HTTP backend setiap aplikasi dan perbarui health_status';

    public function handle(ApplicationHealthCheckService $health)
    {
        $counts = $health->checkAll();
        $this->info(sprintf(
            'Health check selesai — up: %d, down: %d, unknown: %d',
            $counts['up'] ?? 0,
            $counts['down'] ?? 0,
            $counts['unknown'] ?? 0
        ));

        return 0;
    }
}
