<?php

namespace App\Services\Gateway;

use App\Models\Application;
use Illuminate\Support\Str;

class GatewayOrphanConfigService
{
    /** @var GatewayAgentClient */
    protected $client;

    public function __construct(GatewayAgentClient $client)
    {
        $this->client = $client;
    }

    public function appsConfigDir()
    {
        return rtrim(config('gateway.config_root'), '/').'/apps';
    }

    /**
     * @return list<int>
     */
    public function configFileApplicationIds()
    {
        $dir = $this->appsConfigDir();
        if (! is_dir($dir)) {
            return [];
        }

        $ids = [];
        foreach (glob($dir.'/app-*.conf') ?: [] as $path) {
            if (preg_match('/\/app-(\d+)\.conf$/', $path, $m)) {
                $ids[] = (int) $m[1];
            }
        }

        sort($ids);

        return $ids;
    }

    /**
     * @return list<int>
     */
    public function orphanApplicationIds()
    {
        $fileIds = $this->configFileApplicationIds();
        if ($fileIds === []) {
            return [];
        }

        $liveIds = Application::query()->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();

        return array_values(array_diff($fileIds, $liveIds));
    }

    /**
     * @return array<string, list<array{application_id: int, path: string, role: string}>>
     */
    public function hostnameConflicts()
    {
        $dir = $this->appsConfigDir();
        if (! is_dir($dir)) {
            return [];
        }

        $byHost = [];
        foreach (glob($dir.'/app-*.conf') ?: [] as $path) {
            if (! preg_match('/\/app-(\d+)\.conf$/', $path, $m)) {
                continue;
            }
            $applicationId = (int) $m[1];
            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }

            foreach ($this->parseHostnamesFromConfig($content) as $role => $hostname) {
                $hostname = Str::lower($hostname);
                $byHost[$hostname][] = [
                    'application_id' => $applicationId,
                    'path' => $path,
                    'role' => $role,
                ];
            }
        }

        return array_filter($byHost, function ($entries) {
            return count($entries) > 1;
        });
    }

    /**
     * @return array{removed: list<int>, failed: array<int, string>}
     */
    public function pruneOrphans()
    {
        $removed = [];
        $failed = [];

        if (! $this->client->isEnabled()) {
            return compact('removed', 'failed');
        }

        foreach ($this->orphanApplicationIds() as $applicationId) {
            try {
                $this->client->remove((string) $applicationId);
                $removed[] = $applicationId;
            } catch (\Throwable $e) {
                $failed[$applicationId] = $e->getMessage();
            }
        }

        return compact('removed', 'failed');
    }

    /**
     * @return list<string>
     */
    public function conflictingHostnamesForApplication(Application $application)
    {
        $hostnames = $application->domains->pluck('hostname')->filter()->map(function ($h) {
            return Str::lower((string) $h);
        })->unique()->values()->all();

        if ($hostnames === []) {
            return [];
        }

        $conflicts = $this->hostnameConflicts();
        $out = [];
        foreach ($hostnames as $hostname) {
            if (isset($conflicts[$hostname])) {
                $owners = collect($conflicts[$hostname])->pluck('application_id')->unique()->sort()->values()->all();
                if (count($owners) > 1 || (count($owners) === 1 && (int) $owners[0] !== (int) $application->id)) {
                    $out[] = $hostname;
                }
            }
        }

        return $out;
    }

    /**
     * @return array<string, string> role => hostname
     */
    protected function parseHostnamesFromConfig($content)
    {
        $hostnames = [];
        if (preg_match('/^\s*ServerName\s+(\S+)/mi', $content, $m)) {
            $hostnames['ServerName'] = $m[1];
        }
        if (preg_match_all('/^\s*ServerAlias\s+(.+)$/mi', $content, $matches)) {
            foreach ($matches[1] as $line) {
                foreach (preg_split('/\s+/', trim($line)) ?: [] as $alias) {
                    if ($alias !== '') {
                        $hostnames['ServerAlias:'.$alias] = $alias;
                    }
                }
            }
        }

        return $hostnames;
    }
}
