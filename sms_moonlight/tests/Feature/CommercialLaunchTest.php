<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionLifecycle;
use App\Services\SupportAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommercialLaunchTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_state_enforces_school_availability(): void
    {
        [$tenant, $subscription] = $this->tenantWithSubscription();
        $this->assertTrue($tenant->fresh()->isAvailable());

        $subscription->update(['status' => Subscription::STATUS_PAST_DUE, 'grace_ends_at' => now()->subMinute()]);
        $this->assertFalse($tenant->fresh()->isAvailable());

        $subscription->update(['grace_ends_at' => now()->addDay()]);
        $this->assertTrue($tenant->fresh()->isAvailable());

        $tenant->update(['status' => Tenant::STATUS_SUSPENDED, 'suspended_at' => now()]);
        $this->assertFalse($tenant->fresh()->isAvailable());
    }

    public function test_support_can_only_see_school_with_active_grant(): void
    {
        [$tenant] = $this->tenantWithSubscription();
        $owner = User::factory()->create(['role' => 'owner', 'active' => true]);
        $support = User::factory()->create(['role' => 'support', 'active' => true]);
        $access = app(SupportAccess::class);

        $this->assertFalse($access->allows($support, $tenant));
        $grant = $access->grant($tenant, $support, $owner, 'Investigate a reported portal issue.');
        $this->assertTrue($access->allows($support, $tenant));
        $access->revoke($grant);
        $this->assertFalse($access->allows($support, $tenant));
    }

    public function test_expired_subscription_states_are_reconciled(): void
    {
        [$tenant, $subscription] = $this->tenantWithSubscription();
        $subscription->update(['status' => Subscription::STATUS_PAST_DUE, 'grace_ends_at' => now()->subMinute()]);

        $this->assertSame(1, app(SubscriptionLifecycle::class)->reconcileAll());
        $this->assertSame(Subscription::STATUS_CANCELLED, $subscription->fresh()->status);
        $this->assertSame(Tenant::STATUS_CANCELLED, $tenant->fresh()->status);
    }

    public function test_support_cannot_open_owner_only_commercial_actions(): void
    {
        [$tenant] = $this->tenantWithSubscription();
        $support = User::factory()->create(['role' => 'support', 'active' => true]);

        $this->actingAs($support)->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->patch(route('platform.schools.lifecycle', $tenant), ['action' => 'suspend'])
            ->assertForbidden();
    }

    public function test_readiness_endpoint_reports_healthy_dependencies(): void
    {
        $this->withServerVariables(['HTTP_HOST' => 'localhost'])->get('/health/ready')
            ->assertOk()->assertJsonPath('status', 'ready');
    }

    private function tenantWithSubscription(): array
    {
        $plan = Plan::query()->create(['name' => 'Starter', 'slug' => 'starter-'.Str::random(6), 'included_users' => 10, 'max_users' => 20, 'monthly_price_cents' => 1000, 'active' => true]);
        $tenant = Tenant::query()->create(['id' => (string) Str::uuid(), 'name' => 'Test School', 'slug' => 'school-'.Str::random(6), 'status' => Tenant::STATUS_ACTIVE, 'timezone' => 'Asia/Manila', 'current_plan_id' => $plan->id]);
        $subscription = Subscription::query()->create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'status' => Subscription::STATUS_ACTIVE, 'starts_at' => now()]);

        return [$tenant, $subscription];
    }
}
