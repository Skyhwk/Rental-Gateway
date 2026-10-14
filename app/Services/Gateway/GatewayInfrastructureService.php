<?php

namespace App\Services\Gateway;

class GatewayInfrastructureService
{
    public function listenerPort()
    {
        return (int) config('gateway.listener_port', 8080);
    }

    public function isListenerOpen()
    {
        $port = $this->listenerPort();
        $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }

    public function appsConfigPath()
    {
        return rtrim(config('gateway.config_root'), '/').'/apps';
    }

    public function isReadyForTraffic()
    {
        return $this->isListenerOpen() && is_dir($this->appsConfigPath());
    }

    /**
     * @return array<string, mixed>
     */
    public function diagnostics()
    {
        $port = $this->listenerPort();
        $open = $this->isListenerOpen();
        $appsDir = $this->appsConfigPath();
        $appFiles = is_dir($appsDir) ? count(glob($appsDir.'/*.conf') ?: []) : 0;

        return [
            'listener_port' => $port,
            'listener_open' => $open,
            'apps_config_dir' => $appsDir,
            'app_config_count' => $appFiles,
            'ready' => $open && $appFiles > 0,
        ];
    }
}
