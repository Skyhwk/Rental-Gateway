<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\User;
use App\Services\Monitoring\ApplicationHealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    protected function makeApplication($port = 4500)
    {
        $client = Client::create([
            'company_name' => 'PT Mon',
            'pic_name' => 'M',
            'email' => 'm@test.local',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $application = Application::create([
            'gateway_node_id' => 1,
            'client_id' => $client->id,
            'name' => 'Mon App',
            'slug' => 'mon-app',
            'internal_host' => '127.0.0.1',
            'internal_port' => $port,
            'access_status' => Application::ACCESS_ACTIVE,
        ]);
        $application->domains()->create(['hostname' => 'mon.test.local', 'is_primary' => true]);

        return $application;
    }

    public function test_health_probe_marks_up_on_200(): void
    {
        Http::fake(['127.0.0.1:4501/*' => Http::response('ok', 200)]);

        $app = $this->makeApplication(4501);
        $app->load('primaryDomain');
        app(ApplicationHealthCheckService::class)->checkAndPersist($app);

        $app->refresh();
        $this->assertSame(Application::HEALTH_UP, $app->health_status);
        $this->assertSame(200, $app->health_http_code);
    }

    public function test_health_probe_ignores_gateway_agent_path_in_env(): void
    {
        config(['monitoring.health.path' => '/v1/health']);
        Http::fake(['127.0.0.1:4502/' => Http::response('ok', 200)]);

        $app = $this->makeApplication(4502);
        $this->assertSame('/', $app->resolvedHealthCheckPath());

        app(ApplicationHealthCheckService::class)->checkAndPersist($app);
        $app->refresh();
        $this->assertSame(Application::HEALTH_UP, $app->health_status);
    }

    public function test_health_probe_sends_primary_domain_as_host_header(): void
    {
        Http::fake(function ($request) {
            $this->assertSame('mon.test.local', $request->header('Host')[0] ?? null);

            return Http::response('ok', 200);
        });

        $app = $this->makeApplication(4503);
        app(ApplicationHealthCheckService::class)->checkAndPersist($app->fresh(['primaryDomain']));
    }

    public function test_monitoring_page_requires_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)->get(route('monitoring.index'))->assertOk();
    }
}
