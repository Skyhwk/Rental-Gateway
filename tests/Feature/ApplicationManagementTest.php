<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_duplicate_port_on_same_gateway_node_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $client = Client::create([
            'company_name' => 'PT A',
            'pic_name' => 'A',
            'email' => 'a@test.local',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $payload = [
            'client_id' => $client->id,
            'name' => 'App One',
            'internal_host' => '127.0.0.1',
            'internal_port' => 1610,
            'access_status' => Application::ACCESS_ACTIVE,
            'primary_hostname' => 'one.test.local',
        ];

        $this->actingAs($admin)->post(route('applications.store'), $payload)->assertRedirect();

        $payload['name'] = 'App Two';
        $payload['primary_hostname'] = 'two.test.local';

        $this->actingAs($admin)->post(route('applications.store'), $payload)->assertSessionHasErrors('internal_port');
    }

    public function test_duplicate_hostname_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $client = Client::create([
            'company_name' => 'PT B',
            'pic_name' => 'B',
            'email' => 'b@test.local',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $base = [
            'client_id' => $client->id,
            'internal_host' => '127.0.0.1',
            'access_status' => Application::ACCESS_ACTIVE,
        ];

        $this->actingAs($admin)->post(route('applications.store'), array_merge($base, [
            'name' => 'First',
            'internal_port' => 1610,
            'primary_hostname' => 'dup.test.local',
        ]))->assertRedirect();

        $this->actingAs($admin)->post(route('applications.store'), array_merge($base, [
            'name' => 'Second',
            'internal_port' => 1611,
            'primary_hostname' => 'dup.test.local',
        ]))->assertSessionHasErrors();
    }
}
