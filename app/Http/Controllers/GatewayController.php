<?php

namespace App\Http\Controllers;

use App\Services\Gateway\GatewayAgentClient;
use App\Services\Gateway\GatewayAgentServiceManager;
use App\Services\Gateway\GatewayInfrastructureService;
use App\Services\Monitoring\SystemServiceStatusService;
class GatewayController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:gateway.sync']);
    }

    public function index(
        GatewayAgentClient $client,
        SystemServiceStatusService $status,
        GatewayAgentServiceManager $serviceManager,
        GatewayInfrastructureService $infra
    ) {
        $services = $status->snapshot();
        $serviceStatus = $serviceManager->status();
        $gatewayInfra = $infra->diagnostics();

        return view('gateway.index', [
            'agentEnabled' => $client->isEnabled(),
            'agentHealth' => $services['gateway_agent']['detail'] ?? $client->health(),
            'agentOk' => ! empty($services['gateway_agent']['active']),
            'apache' => $services['apache2'] ?? [],
            'configRoot' => config('gateway.config_root'),
            'listenerPort' => config('gateway.listener_port'),
            'serviceStatus' => $serviceStatus,
            'serviceControlEnabled' => $serviceManager->isControlEnabled(),
            'systemdUnit' => config('gateway.service.systemd_unit'),
            'gatewayInfra' => $gatewayInfra,
        ]);
    }

    public function startAgent(GatewayAgentServiceManager $serviceManager)
    {
        $result = $serviceManager->start();

        return $this->serviceActionResponse($result);
    }

    public function restartAgent(GatewayAgentServiceManager $serviceManager)
    {
        $result = $serviceManager->restart();

        return $this->serviceActionResponse($result);
    }

    protected function serviceActionResponse(array $result)
    {
        if ($result['success']) {
            return redirect()
                ->route('gateway.index')
                ->with('success', $result['message']);
        }

        $detail = trim($result['output'] ?? '');
        $message = $result['message'];
        if ($detail !== '') {
            $message .= ' — '.$detail;
        }

        return redirect()
            ->route('gateway.index')
            ->with('error', $message);
    }
}
