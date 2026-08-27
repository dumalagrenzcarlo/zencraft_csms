<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_platform_login(): void
    {
        $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get('/platform')
            ->assertRedirect('/platform/login');
    }

    public function test_active_platform_owner_can_open_dashboard(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'active' => true]);

        $this->actingAs($owner)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get('/platform')
            ->assertOk()
            ->assertSee('School workspaces');
    }

    public function test_non_platform_user_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'school', 'active' => true]);

        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get('/platform')
            ->assertForbidden();
    }

    public function test_platform_routes_are_not_exposed_on_tenant_hosts(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'active' => true]);

        $this->actingAs($owner)
            ->get('http://unknown.localhost/platform')
            ->assertNotFound();
    }
}
