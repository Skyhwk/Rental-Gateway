<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\Gateway\GatewaySyncService;
use Illuminate\Http\Request;

class ApplicationGatewayController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:gateway.sync']);
    }

    public function sync(Request $request, Application $application, GatewaySyncService $syncService)
    {
        $this->authorize('view', $application);

        $log = $syncService->queueSync($application, 'manual', $request->user(), false);

        if (! $log) {
            return back()->with('error', 'Gateway Agent tidak aktif. Set GATEWAY_AGENT_ENABLED=true dan token di .env.');
        }

        $syncService->applySyncLog($log->id);
        $log->refresh();

        if ($log->sync_status === 'applied') {
            return back()->with('success', 'Konfigurasi gateway berhasil diterapkan.');
        }

        if ($log->sync_status === 'pending') {
            return back()->with('success', 'Sinkronisasi gateway dalam antrian.');
        }

        return back()->with('error', 'Sinkronisasi gagal: '.($log->error_message ?: 'unknown'));
    }

    public function retry(Request $request, Application $application, GatewaySyncService $syncService)
    {
        $this->authorize('view', $application);

        $log = $syncService->retryLatestFailed($application, $request->user());

        if (! $log) {
            return back()->with('error', 'Gateway Agent tidak aktif.');
        }

        $syncService->applySyncLog($log->id);
        $log->refresh();

        if ($log->sync_status === 'applied') {
            return back()->with('success', 'Retry berhasil — konfigurasi diterapkan.');
        }

        return back()->with('error', 'Retry gagal: '.($log->error_message ?: 'unknown'));
    }
}
