<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    protected function makeApplication()
    {
        $client = Client::create([
            'company_name' => 'PT Access',
            'pic_name' => 'A',
            'email' => 'a@test.local',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $application = Application::create([
            'gateway_node_id' => 1,
            'client_id' => $client->id,
            'name' => 'App',
            'slug' => 'app-access',
            'internal_host' => '127.0.0.1',
            'internal_port' => 1710,
            'access_status' => Application::ACCESS_ACTIVE,
        ]);
        $application->domains()->create(['hostname' => 'access.test.local', 'is_primary' => true]);

        return $application;
    }

    public function test_suspend_requires_reason_and_creates_access_log(): void
    {
        Config::set('gateway.agent.enabled', false);

        $admin = User::factory()->create();
        $admin->assignRole('Administrator');
        $application = $this->makeApplication();

        $this->actingAs($admin)->put(route('applications.access.update', $application), [
            'access_status' => Application::ACCESS_SUSPENDED,
            'reason' => 'Tunggakan invoice bulan ini',
        ])->assertRedirect(route('applications.show', $application));

        $this->assertDatabaseHas('access_logs', [
            'application_id' => $application->id,
            'from_status' => Application::ACCESS_ACTIVE,
            'to_status' => Application::ACCESS_SUSPENDED,
        ]);

        $application->refresh();
        $this->assertSame(Application::ACCESS_SUSPENDED, $application->access_status);
    }

    public function test_suspend_triggers_gateway_sync_when_agent_enabled(): void
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
        $application = $this->makeApplication();

        $this->actingAs($admin)->put(route('applications.access.update', $application), [
            'access_status' => Application::ACCESS_SUSPENDED,
            'reason' => 'Maintenance billing policy',
        ]);

        $application->refresh();
        $this->assertSame(Application::ACCESS_SUSPENDED, $application->gateway_applied_status);
    }

    public function test_same_status_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');
        $application = $this->makeApplication();

        $this->actingAs($admin)->from(route('applications.show', $application))
            ->put(route('applications.access.update', $application), [
                'access_status' => Application::ACCESS_ACTIVE,
                'reason' => 'Tidak ada perubahan status',
            ])->assertSessionHasErrors('access_status');
    }
}
