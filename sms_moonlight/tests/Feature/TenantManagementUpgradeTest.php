<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantManagementUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private ?string $tenantId = null;

    protected function tearDown(): void
    {
        tenancy()->end();

        if ($this->tenantId !== null) {
            Tenant::query()->find($this->tenantId)?->delete();
        }

        parent::tearDown();
    }

    public function test_active_platform_users_can_sign_in(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@zencraft.test',
            'password' => Hash::make('PlatformPassword123!'),
            'role' => 'owner',
            'active' => true,
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->post('/platform/login', [
                'email' => $owner->email,
                'password' => 'PlatformPassword123!',
            ])
            ->assertRedirect(route('platform.dashboard'));

        $this->assertAuthenticatedAs($owner);
    }

    public function test_owner_can_see_admin_details_and_create_a_support_user(): void
    {
        [$owner, $school] = $this->school();

        $this->actingAs($owner)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get(route('platform.schools.show', $school))
            ->assertOk()
            ->assertSee('Admin account details')
            ->assertSee('School Administrator')
            ->assertSee('admin@sample.test')
            ->assertSee('No support users yet');

        $this->actingAs($owner)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->post(route('platform.support-users.store'), [
                'name' => 'Support Operator',
                'email' => 'support@zencraft.test',
                'password' => 'SupportPassword123!',
                'password_confirmation' => 'SupportPassword123!',
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'name' => 'Support Operator',
            'email' => 'support@zencraft.test',
            'role' => 'support',
            'active' => true,
        ]);

        $this->actingAs($owner)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get(route('platform.schools.show', $school))
            ->assertSee('Support Operator')
            ->assertSee('support@zencraft.test');
    }

    public function test_owner_can_reset_the_school_admin_password_and_the_admin_can_sign_in(): void
    {
        [$owner, $school] = $this->school();

        $this->actingAs($owner)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->patch(route('platform.schools.admin-account.update', $school), [
                'admin_password' => 'NewSchoolPassword123!',
                'admin_password_confirmation' => 'NewSchoolPassword123!',
            ])
            ->assertSessionHas('status');

        auth()->guard('web')->logout();

        $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->post('/sample-academy/admin/authenticate', [
                'username' => 'admin',
                'password' => 'NewSchoolPassword123!',
            ])
            ->assertRedirect('/sample-academy/admin');

        $this->assertAuthenticated('moonshine');
    }

    public function test_create_school_screen_uses_the_guided_tenant_layout(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'active' => true]);
        $this->plan();

        $this->actingAs($owner)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get(route('platform.schools.create'))
            ->assertOk()
            ->assertSee('provision-layout', false)
            ->assertSee('School details')
            ->assertSee('Initial school administrator')
            ->assertSee('What happens next');
    }

    /** @return array{User,Tenant} */
    private function school(): array
    {
        $owner = User::factory()->create(['role' => 'owner', 'active' => true]);
        $school = app(SchoolProvisioner::class)->create([
            'name' => 'Sample Academy',
            'slug' => 'sample-academy',
            'timezone' => 'Asia/Manila',
            'plan_id' => $this->plan()->id,
            'admin_name' => 'School Administrator',
            'admin_email' => 'admin@sample.test',
            'admin_password' => 'TemporaryPassword123!',
            'admin_password_confirmation' => 'TemporaryPassword123!',
        ]);
        $this->tenantId = $school->id;

        return [$owner, $school];
    }

    private function plan(): Plan
    {
        return Plan::query()->firstOrCreate(
            ['slug' => 'free'],
            ['name' => 'Free', 'included_users' => 111, 'max_users' => 111, 'monthly_price_cents' => 0, 'active' => true]
        );
    }
}
