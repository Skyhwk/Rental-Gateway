<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\Monitoring\SystemServiceStatusService;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:dashboard.view']);
    }

    public function index(SystemServiceStatusService $systemStatus)
    {
        $services = auth()->user()->can('monitoring.view')
            ? $systemStatus->snapshot()
            : null;

        return view('home', [
            'totalClients' => Client::count(),
            'totalApplications' => Application::count(),
            'activeApplications' => Application::where('access_status', Application::ACCESS_ACTIVE)->count(),
            'suspendedApplications' => Application::where('access_status', Application::ACCESS_SUSPENDED)->count(),
            'maintenanceApplications' => Application::where('access_status', Application::ACCESS_MAINTENANCE)->count(),
            'healthUp' => Application::where('health_status', Application::HEALTH_UP)->count(),
            'healthDown' => Application::where('health_status', Application::HEALTH_DOWN)->count(),
            'unpaidInvoices' => Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])->count(),
            'overdueInvoices' => Invoice::where('status', Invoice::STATUS_OVERDUE)->count(),
            'services' => $services,
        ]);
    }
}
