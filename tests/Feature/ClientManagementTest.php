<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_viewer_can_list_clients_but_not_create(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $this->actingAs($user)->get(route('clients.index'))->assertOk();
        $this->actingAs($user)->get(route('clients.create'))->assertForbidden();
    }

    public function test_administrator_can_create_client(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $response = $this->actingAs($user)->post(route('clients.store'), [
            'company_name' => 'PT Contoh',
            'pic_name' => 'Budi',
            'email' => 'budi@contoh.test',
            'phone' => '08123456789',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['company_name' => 'PT Contoh']);
    }
}
