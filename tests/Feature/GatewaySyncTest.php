<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\User;
use App\Services\Gateway\GatewaySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewaySyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_gateway_sync_updates_applied_status_when_agent_returns_success(): void
    {
        Config::set('gateway.agent.enabled', true);
        Config::set('gateway.agent.token', 'test-token');
        Config::set('gateway.agent.base_url', 'http://127.0.0.1:8750');

        Http::fake([
            '127.0.0.1:8750/v1/apply' => Http::response([
                'status' => 'applied',
                'checksum' => 'sha256:abc',
                'config_version' => 'v1',
            ], 200),
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $client = Client::create([
            'company_name' => 'PT GW',
            'pic_name' => 'X',
            'email' => 'gw@test.local',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $application = Application::create([
            'gateway_node_id' => 1,
            'client_id' => $client->id,
            'name' => 'App GW',
            'slug' => 'app-gw',
            'internal_host' => '127.0.0.1',
            'internal_port' => 1700,
            'access_status' => Application::ACCESS_ACTIVE,
        ]);
        $application->domains()->create(['hostname' => 'gw.test.local', 'is_primary' => true]);

        $log = app(GatewaySyncService::class)->queueSync($application, 'test', $admin);
        app(GatewaySyncService::class)->applySyncLog($log->id);

        $application->refresh();
        $this->assertSame(Application::ACCESS_ACTIVE, $application->gateway_applied_status);
        $this->assertSame('applied', $log->fresh()->sync_status);
    }
}
