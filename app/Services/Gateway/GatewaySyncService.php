<?php

namespace App\Services\Gateway;

use App\Jobs\ApplyGatewayConfigJob;
use App\Models\Application;
use App\Models\GatewayConfigVersion;
use App\Models\GatewaySyncLog;
use App\Models\User;
use Illuminate\Support\Str;

class GatewaySyncService
{
    /** @var GatewayAgentClient */
    protected $client;

    public function __construct(GatewayAgentClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param  bool  $dispatchJob  false = hanya buat log (mis. sync manual langsung applySyncLog)
     */
    public function queueSync(Application $application, $source = 'system', User $user = null, $dispatchJob = true)
    {
        if (! $this->client->isEnabled()) {
            return null;
        }

        $log = GatewaySyncLog::create([
            'application_id' => $application->id,
            'idempotency_key' => (string) Str::uuid(),
            'desired_status' => $application->access_status,
            'sync_status' => GatewaySyncLog::STATUS_PENDING,
            'requested_by' => $user ? $user->id : null,
            'source' => $source,
        ]);

        if ($dispatchJob) {
            ApplyGatewayConfigJob::dispatch($log->id);
        }

        return $log;
    }

    public function applySyncLog($syncLogId)
    {
        $log = GatewaySyncLog::with('application.domains')->findOrFail($syncLogId);
        $application = $log->application;

        if (! $this->client->isEnabled()) {
            $log->update([
                'sync_status' => GatewaySyncLog::STATUS_FAILED,
                'error_message' => 'Gateway Agent tidak aktif.',
            ]);

            return $log;
        }

        $hostnames = $application->domains->pluck('hostname')->filter()->values()->all();
        if (empty($hostnames)) {
            $log->update([
                'sync_status' => GatewaySyncLog::STATUS_FAILED,
                'error_message' => 'Tidak ada domain untuk aplikasi ini.',
            ]);

            return $log;
        }

        $payload = [
            'application_id' => (string) $application->id,
            'hostnames' => $hostnames,
            'internal_host' => $application->internal_host,
            'internal_port' => (int) $application->internal_port,
            'access_mode' => $application->access_status,
            'websocket_enabled' => (bool) data_get($application->deployment_meta, 'websocket_enabled', false),
            'websocket_path' => (string) data_get($application->deployment_meta, 'websocket_path', '/ws'),
        ];

        try {
            $result = $this->client->apply($payload, $log->idempotency_key);

            if (($result['status'] ?? '') === 'busy') {
                ApplyGatewayConfigJob::dispatch($log->id)->delay(now()->addSeconds($result['retry_after'] ?? 5));

                return $log;
            }

            if (($result['status'] ?? '') === 'applied') {
                $version = GatewayConfigVersion::create([
                    'application_id' => $application->id,
                    'checksum' => $result['checksum'] ?? '',
                    'config_snapshot_path' => $result['config_version'] ?? null,
                ]);

                $application->update([
                    'gateway_applied_status' => $application->access_status,
                ]);

                $log->update([
                    'sync_status' => GatewaySyncLog::STATUS_APPLIED,
                    'error_message' => null,
                    'gateway_config_version_id' => $version->id,
                ]);

                return $log;
            }

            $log->update([
                'sync_status' => GatewaySyncLog::STATUS_FAILED,
                'error_message' => $this->formatAgentFailure($result),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'sync_status' => GatewaySyncLog::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log->fresh();
    }

    public function retryLatestFailed(Application $application, User $user = null)
    {
        $failed = $application->gatewaySyncLogs()
            ->where('sync_status', GatewaySyncLog::STATUS_FAILED)
            ->latest()
            ->first();

        if (! $failed) {
            return $this->queueSync($application, 'retry', $user, false);
        }

        return $this->queueSync($application, 'retry', $user, false);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function formatAgentFailure(array $result)
    {
        $status = (string) ($result['status'] ?? '');

        if ($status === 'unauthorized') {
            return 'Gateway Agent menolak token. Restart agent dengan GATEWAY_AGENT_TOKEN yang sama (.env Laravel), atau jalankan gateway-agent/run.sh.';
        }

        if ($status === 'validation_error') {
            $errors = $result['errors'] ?? [];

            return is_array($errors)
                ? 'Validasi agent: '.json_encode($errors, JSON_UNESCAPED_UNICODE)
                : 'Validasi agent gagal.';
        }

        if ($status === 'failed' && ! empty($result['error'])) {
            $message = (string) $result['error'];
            if (! empty($result['stderr'])) {
                $message .= ' — '.Str::limit((string) $result['stderr'], 500);
            }

            return $message;
        }

        if (! empty($result['error'])) {
            return (string) $result['error'];
        }

        return 'Respons agent tidak dikenali (status: '.($status ?: 'kosong').').';
    }
}
