<?php

namespace App\Services\Gateway;

use Symfony\Component\Process\Process;

class GatewayAgentServiceManager
{
    public function isControlEnabled()
    {
        return (bool) config('gateway.service.control_enabled', true);
    }

    /**
     * @return array{success: bool, message: string, state: ?string, output: string}
     */
    public function status()
    {
        return $this->runCtl('is-active', 'Status layanan agent');
    }

    /**
     * @return array{success: bool, message: string, state: ?string, output: string}
     */
    public function start()
    {
        $current = $this->runCtl('is-active', 'Cek status', false);
        if ($current['success'] && trim($current['output']) === 'active') {
            return [
                'success' => true,
                'message' => 'Gateway Agent sudah berjalan.',
                'state' => 'active',
                'output' => $current['output'],
            ];
        }

        $result = $this->runCtl('start', 'Memulai Gateway Agent');
        if ($result['success']) {
            sleep(1);
            $verify = $this->runCtl('is-active', 'Verifikasi', false);
            $result['state'] = trim($verify['output']);
            if ($verify['success'] && $result['state'] === 'active') {
                $result['message'] = 'Gateway Agent berhasil di-start.';
            } else {
                $result['success'] = false;
                $result['message'] = 'Perintah start OK tetapi layanan belum active. Cek: journalctl -u '.config('gateway.service.systemd_unit');
            }
        }

        return $result;
    }

    /**
     * @return array{success: bool, message: string, state: ?string, output: string}
     */
    public function restart()
    {
        $result = $this->runCtl('restart', 'Restart Gateway Agent');
        if ($result['success']) {
            sleep(1);
            $verify = $this->runCtl('is-active', 'Verifikasi', false);
            $result['state'] = trim($verify['output']);
            if ($verify['success'] && $result['state'] === 'active') {
                $result['message'] = 'Gateway Agent berhasil di-restart.';
            } else {
                $result['success'] = false;
                $result['message'] = 'Restart dijalankan tetapi layanan tidak active.';
            }
        }

        return $result;
    }

    /**
     * @return array{success: bool, message: string, state: ?string, output: string}
     */
    protected function runCtl($action, $label, $requireSuccess = true)
    {
        if (! $this->isControlEnabled()) {
            return [
                'success' => false,
                'message' => 'Kontrol layanan agent dinonaktifkan (GATEWAY_AGENT_SERVICE_CONTROL).',
                'state' => null,
                'output' => '',
            ];
        }

        $binary = (string) config('gateway.service.ctl_command');
        if (! is_executable($binary)) {
            return [
                'success' => false,
                'message' => 'Script kontrol tidak ditemukan. Jalankan install-gateway-agent-service.sh di server.',
                'state' => null,
                'output' => '',
            ];
        }

        $process = new Process(['sudo', '-n', $binary, $action]);
        $process->setTimeout(60);
        $process->run();

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());
        $exitOk = $process->isSuccessful();

        if ($action === 'is-active') {
            $state = trim($process->getOutput());
            $active = $state === 'active';

            return [
                'success' => $active || ! $requireSuccess,
                'message' => $active ? 'Layanan active.' : ('Layanan: '.($state ?: 'tidak active')),
                'state' => $state ?: 'unknown',
                'output' => $output,
            ];
        }

        if (! $exitOk && $requireSuccess) {
            $hint = str_contains($output, 'password') || str_contains($output, 'not allowed')
                ? ' Sudoers belum dipasang untuk www-data.'
                : '';

            return [
                'success' => false,
                'message' => $label.' gagal.'.$hint,
                'state' => null,
                'output' => $output,
            ];
        }

        return [
            'success' => true,
            'message' => $label.' berhasil.',
            'state' => null,
            'output' => $output,
        ];
    }
}
