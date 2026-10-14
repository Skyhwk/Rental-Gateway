<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\Client;
use App\Services\AccessControlService;
use App\Services\ApplicationService;
use App\Services\Gateway\GatewayAgentClient;
use App\Services\Gateway\GatewayInfrastructureService;
use App\Services\Gateway\GatewaySyncService;
use App\Services\Monitoring\ApplicationHealthCheckService;
use App\Support\HostnameNormalizer;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /** @var ApplicationService */
    protected $applications;

    public function __construct(ApplicationService $applications)
    {
        $this->applications = $applications;
        $this->authorizeResource(Application::class, 'application');
    }

    public function index(Request $request)
    {
        $query = Application::query()->with(['client', 'primaryDomain']);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('domains', function ($dq) use ($search) {
                        $dq->where('hostname', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($status = $request->get('access_status')) {
            $query->where('access_status', $status);
        }

        if ($health = $request->get('health_status')) {
            $query->where('health_status', $health);
        }

        $applications = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('applications.index', [
            'applications' => $applications,
            'accessStatuses' => Application::accessStatuses(),
            'healthStatuses' => Application::healthStatuses(),
        ]);
    }

    public function create()
    {
        $application = new Application([
            'internal_host' => '127.0.0.1',
            'access_status' => Application::ACCESS_ACTIVE,
        ]);

        if ($clientId = request('client_id')) {
            $application->client_id = (int) $clientId;
        }

        return view('applications.create', $this->formData($application));
    }

    public function store(StoreApplicationRequest $request)
    {
        $validated = $request->validated();
        $primary = [HostnameNormalizer::normalize($validated['primary_hostname'])];
        $additional = HostnameNormalizer::parseList($validated['additional_hostnames'] ?? '');
        $additional = array_values(array_diff($additional, $primary));

        unset($validated['primary_hostname'], $validated['additional_hostnames']);

        $application = $this->applications->create($validated, $primary, $additional);
        app(GatewaySyncService::class)->queueSync($application, 'application.create', $request->user());
        app(ApplicationHealthCheckService::class)->checkAndPersist($application->fresh(['primaryDomain']));

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Aplikasi berhasil ditambahkan.');
    }

    public function show(Application $application)
    {
        $application->load(['client', 'domains', 'gatewayNode', 'latestGatewaySyncLog']);
        $syncLogs = $application->gatewaySyncLogs()->latest()->limit(10)->get();
        $accessLogs = $application->accessLogs()->with('user')->latest()->limit(20)->get();
        $syncState = app(AccessControlService::class)->gatewaySyncState($application);

        $gatewayInfra = app(GatewayInfrastructureService::class)->diagnostics();

        return view('applications.show', compact('application', 'syncLogs', 'accessLogs', 'syncState', 'gatewayInfra'));
    }

    public function edit(Application $application)
    {
        $application->load('domains');

        return view('applications.edit', $this->formData($application));
    }

    public function update(UpdateApplicationRequest $request, Application $application)
    {
        $validated = $request->validated();
        $primary = [HostnameNormalizer::normalize($validated['primary_hostname'])];
        $additional = HostnameNormalizer::parseList($validated['additional_hostnames'] ?? '');
        $additional = array_values(array_diff($additional, $primary));

        unset($validated['primary_hostname'], $validated['additional_hostnames']);

        $application = $this->applications->update($application, $validated, $primary, $additional);
        app(GatewaySyncService::class)->queueSync($application, 'application.update', $request->user());
        app(ApplicationHealthCheckService::class)->checkAndPersist($application->fresh(['primaryDomain']));

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Aplikasi diperbarui.');
    }

    public function destroy(Application $application, GatewayAgentClient $agentClient)
    {
        if ($agentClient->isEnabled()) {
            try {
                $agentClient->remove((string) $application->id);
            } catch (\Throwable $e) {
                return back()->with('error', 'Gagal menghapus config gateway: '.$e->getMessage());
            }
        }

        $application->delete();

        return redirect()
            ->route('applications.index')
            ->with('success', 'Aplikasi dihapus.');
    }

    protected function formData(Application $application)
    {
        $primary = $application->domains->firstWhere('is_primary', true);
        $additional = $application->domains
            ->where('is_primary', false)
            ->pluck('hostname')
            ->implode("\n");

        return [
            'application' => $application,
            'clients' => Client::orderBy('company_name')->get(),
            'accessStatuses' => Application::accessStatuses(),
            'allowedHosts' => config('gateway.allowed_hosts', ['127.0.0.1']),
            'primaryHostname' => $primary ? $primary->hostname : old('primary_hostname'),
            'additionalHostnames' => $additional ?: old('additional_hostnames'),
        ];
    }
}
