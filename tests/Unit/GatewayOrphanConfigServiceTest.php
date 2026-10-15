<?php

namespace Tests\Unit;

use App\Services\Gateway\GatewayOrphanConfigService;
use Tests\TestCase;

class GatewayOrphanConfigServiceTest extends TestCase
{
    public function test_hostname_conflicts_detects_duplicate_server_names(): void
    {
        $dir = sys_get_temp_dir().'/rgw-orphan-'.uniqid();
        $apps = $dir.'/apps';
        mkdir($apps, 0755, true);

        file_put_contents($apps.'/app-1.conf', <<<'CONF'
<VirtualHost *:8080>
    ServerName db.example.test
    Header always set X-Rental-Gateway-Status "active"
</VirtualHost>
CONF);
        file_put_contents($apps.'/app-2.conf', <<<'CONF'
<VirtualHost *:8080>
    ServerName db.example.test
    Header always set X-Rental-Gateway-Status "suspended"
</VirtualHost>
CONF);

        config(['gateway.config_root' => $dir]);

        $conflicts = app(GatewayOrphanConfigService::class)->hostnameConflicts();

        $this->assertArrayHasKey('db.example.test', $conflicts);
        $this->assertCount(2, $conflicts['db.example.test']);

        array_map('unlink', glob($apps.'/*') ?: []);
        rmdir($apps);
        rmdir($dir);
    }
}
