<?php

namespace App\Services\Monitoring;

use App\Models\Application;
use Illuminate\Support\Facades\Http;

class ApplicationHealthCheckService
{
    /**
     * @return array{status: string, http_code: ?int, error: ?string}
     */
    public function probe(Application $application)
    {
        $host = $application->internal_host;
        $port = (int) $application->internal_port;
        $path = $application->resolvedHealthCheckPath();
        $url = sprintf('http://%s:%d%s', $host, $port, $path);
        $timeout = (int) config('monitoring.health.timeout_seconds', 5);
        $min = (int) config('monitoring.health.success_status_min', 200);
        $max = (int) config('monitoring.health.success_status_max', 399);
        $probeHost = $application->healthProbeHostname();

        try {
            $pending = Http::timeout($timeout)
                ->withOptions(['connect_timeout' => $timeout]);

            if ($probeHost) {
                $pending = $pending->withHeaders(['Host' => $probeHost]);
            }

            $response = $pending->get($url);

            $code = $response->status();
            if ($code >= $min && $code <= $max) {
                return [
                    'status' => Application::HEALTH_UP,
                    'http_code' => $code,
                    'error' => null,
                ];
            }

            return [
                'status' => Application::HEALTH_DOWN,
                'http_code' => $code,
                'error' => 'HTTP '.$code,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => Application::HEALTH_DOWN,
                'http_code' => null,
                'error' => \Illuminate\Support\Str::limit($e->getMessage(), 480),
            ];
        }
    }

    public function checkAndPersist(Application $application)
    {
        $result = $this->probe($application);

        $application->update([
            'health_status' => $result['status'],
            'health_checked_at' => now(),
            'health_http_code' => $result['http_code'],
            'health_last_error' => $result['error'],
        ]);

        return $application->fresh();
    }

    public function checkAll()
    {
        $counts = ['up' => 0, 'down' => 0, 'unknown' => 0];

        Application::query()->orderBy('id')->chunkById(50, function ($apps) use (&$counts) {
            foreach ($apps as $application) {
                $app = $this->checkAndPersist($application);
                $key = $app->health_status;
                if (isset($counts[$key])) {
                    $counts[$key]++;
                } else {
                    $counts['unknown']++;
                }
            }
        });

        return $counts;
    }
}
