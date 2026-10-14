<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\Application;
use App\Models\User;
use App\Services\Gateway\GatewaySyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccessControlService
{
    /** @var GatewaySyncService */
    protected $gatewaySync;

    public function __construct(GatewaySyncService $gatewaySync)
    {
        $this->gatewaySync = $gatewaySync;
    }

    public function changeStatus(Application $application, $toStatus, $reason, User $user = null, $source = AccessLog::SOURCE_MANUAL)
    {
        $allowed = array_keys(Application::accessStatuses());
        if (! in_array($toStatus, $allowed, true)) {
            throw ValidationException::withMessages(['access_status' => ['Status tidak valid.']]);
        }

        $reason = trim((string) $reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => ['Alasan wajib diisi.']]);
        }

        if ($application->access_status === $toStatus) {
            throw ValidationException::withMessages(['access_status' => ['Status sudah '.$toStatus.'.']]);
        }

        return DB::transaction(function () use ($application, $toStatus, $reason, $user, $source) {
            $from = $application->access_status;

            AccessLog::create([
                'application_id' => $application->id,
                'user_id' => $user ? $user->id : null,
                'from_status' => $from,
                'to_status' => $toStatus,
                'reason' => $reason,
                'source' => $source,
            ]);

            $application->update(['access_status' => $toStatus]);

            $this->gatewaySync->queueSync($application->fresh(), 'access.'.$toStatus, $user);

            return $application->fresh();
        });
    }

    public function gatewaySyncState(Application $application)
    {
        $desired = $application->access_status;
        $applied = $application->gateway_applied_status;

        if ($desired === $applied) {
            return 'synced';
        }

        $latest = $application->latestGatewaySyncLog;
        if (! $latest) {
            return 'unknown';
        }

        if ($latest->sync_status === 'pending') {
            return 'pending';
        }

        if ($latest->sync_status === 'failed') {
            return 'failed';
        }

        return 'unknown';
    }
}
