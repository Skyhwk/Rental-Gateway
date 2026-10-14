<?php

namespace App\Http\Controllers;

use App\Services\Monitoring\ApplicationHealthCheckService;
use App\Services\Monitoring\SystemServiceStatusService;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:monitoring.view']);
    }

    public function index(SystemServiceStatusService $status)
    {
        return view('monitoring.index', [
            'services' => $status->snapshot(),
            'healthSummary' => [
                'up' => \App\Models\Application::where('health_status', \App\Models\Application::HEALTH_UP)->count(),
                'down' => \App\Models\Application::where('health_status', \App\Models\Application::HEALTH_DOWN)->count(),
                'unknown' => \App\Models\Application::where('health_status', \App\Models\Application::HEALTH_UNKNOWN)->count(),
            ],
        ]);
    }

    public function runHealthChecks(Request $request, ApplicationHealthCheckService $health)
    {
        abort_unless($request->user()->can('applications.manage'), 403);

        $counts = $health->checkAll();

        return redirect()
            ->route('monitoring.index')
            ->with('success', sprintf(
                'Health check selesai — up: %d, down: %d.',
                $counts['up'] ?? 0,
                $counts['down'] ?? 0
            ));
    }
}
