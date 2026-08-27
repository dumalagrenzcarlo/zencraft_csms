<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\MoonshineUser;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private array $tenantsToDelete = [];

    protected function tearDown(): void
    {
        tenancy()->end();

        foreach ($this->tenantsToDelete as $tenantId) {
            Tenant::query()->find($tenantId)?->delete();
        }

        parent::tearDown();
    }

    public function test_owner_can_provision_an_isolated_school_workspace(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'active' => true]);
        $plan = $this->plan();

        $response = $this->actingAs($owner)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->post('/platform/schools', $this->schoolPayload($plan));

        $school = Tenant::query()->where('slug', 'sample-academy')->firstOrFail();
        $this->tenantsToDelete[] = $school->id;

        $response->assertRedirect(route('platform.schools.show', $school));
        $this->assertSame(4, $school->domains()->count());
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $school->id,
            'plan_id' => $plan->id,
            'status' => 'trial',
        ]);

        $school->run(function (): void {
            $this->assertDatabaseHas('moonshine_users', [
                'username' => 'admin',
                'email' => 'admin@sample.test',
                'must_change_password' => 1,
            ]);
            $this->assertSame('Sample Academy', DB::table('settings')->where('settingName', 'school_name')->value('settingValue'));
        });
    }

    public function test_school_databases_do_not_share_records(): void
    {
        $plan = $this->plan();
        $provisioner = app(SchoolProvisioner::class);
        $first = $provisioner->create($this->schoolPayload($plan));
        $second = $provisioner->create([
            ...$this->schoolPayload($plan),
            'name' => 'Second School',
            'slug' => 'second-school',
            'admin_email' => 'admin@second.test',
        ]);
        $this->tenantsToDelete = [$first->id, $second->id];

        $first->run(fn () => DB::table('settings')->updateOrInsert(
            ['settingName' => 'isolation_marker'],
            ['settingValue' => 'first', 'settingType' => 'text']
        ));

        $second->run(function (): void {
            $this->assertNull(DB::table('settings')->where('settingName', 'isolation_marker')->value('settingValue'));
            $this->assertSame('admin@second.test', MoonshineUser::query()->where('username', 'admin')->value('email'));
        });
    }

    public function test_suspended_school_is_blocked_before_portal_access(): void
    {
        $school = app(SchoolProvisioner::class)->create($this->schoolPayload($this->plan()));
        $this->tenantsToDelete[] = $school->id;
        $school->forceFill(['status' => Tenant::STATUS_SUSPENDED, 'suspended_at' => now()])->save();

        $this->get('http://sample-academy.localhost/')
            ->assertStatus(423);
    }

    private function plan(): Plan
    {
        return Plan::query()->firstOrCreate(
            ['slug' => 'starter'],
            ['name' => 'Starter', 'included_users' => 500, 'max_users' => 500, 'monthly_price_cents' => 500000, 'active' => true]
        );
    }

    private function schoolPayload(Plan $plan): array
    {
        return [
            'name' => 'Sample Academy',
            'slug' => 'sample-academy',
            'timezone' => 'Asia/Manila',
            'plan_id' => $plan->id,
            'admin_name' => 'School Admin',
            'admin_email' => 'admin@sample.test',
            'admin_password' => 'Temporary123!',
            'admin_password_confirmation' => 'Temporary123!',
        ];
    }
}
