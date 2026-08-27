<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use MoonShine\Laravel\Models\MoonshineUser;

final class SubscriptionLifecycle
{
    public function reconcileAll(): int
    {
        $updated = 0;

        Subscription::query()
            ->where(function ($query): void {
                $query->where(fn ($active) => $active->where('status', Subscription::STATUS_ACTIVE)->whereNotNull('cancel_at')->where('cancel_at', '<=', now()))
                    ->orWhere(fn ($pastDue) => $pastDue->where('status', Subscription::STATUS_PAST_DUE)->whereNotNull('grace_ends_at')->where('grace_ends_at', '<=', now()))
                    ->orWhere(fn ($trial) => $trial->where('status', Subscription::STATUS_TRIAL)->whereNotNull('trial_ends_at')->where('trial_ends_at', '<=', now()));
            })
            ->with('tenant')
            ->each(function (Subscription $subscription) use (&$updated): void {
                $subscription->forceFill([
                    'status' => Subscription::STATUS_CANCELLED,
                    'ends_at' => $subscription->ends_at ?? now(),
                    'grace_ends_at' => null,
                ])->save();
                $subscription->tenant?->forceFill(['status' => Tenant::STATUS_CANCELLED])->save();
                $updated++;
            });

        return $updated;
    }

    public function activate(Tenant $tenant, Plan $plan): Subscription
    {
        return DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($tenant, $plan): Subscription {
            $subscription = $tenant->currentSubscription()->first() ?? new Subscription(['tenant_id' => $tenant->id]);
            $billableUsers = $this->billableUsers($tenant);
            $this->ensureWithinPlanLimit($plan, $billableUsers);

            $subscription->fill([
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'billable_users' => $billableUsers,
                'starts_at' => $subscription->starts_at ?? now(),
                'renews_at' => now()->addMonth(),
                'trial_ends_at' => null,
                'grace_ends_at' => null,
                'cancel_at' => null,
                'ends_at' => null,
            ])->save();

            $tenant->forceFill([
                'status' => Tenant::STATUS_ACTIVE,
                'current_plan_id' => $plan->id,
                'trial_ends_at' => null,
                'suspended_at' => null,
            ])->save();

            return $subscription->fresh('plan');
        });
    }

    public function changePlan(Tenant $tenant, Plan $plan): Subscription
    {
        return $this->activate($tenant, $plan);
    }

    public function markPastDue(Tenant $tenant): Subscription
    {
        $subscription = $tenant->currentSubscription()->firstOrFail();
        $subscription->forceFill([
            'status' => Subscription::STATUS_PAST_DUE,
            'grace_ends_at' => now()->addDays((int) config('saas.billing_grace_days', 7)),
        ])->save();

        return $subscription->fresh();
    }

    public function cancel(Tenant $tenant, bool $immediately = false): Subscription
    {
        $subscription = $tenant->currentSubscription()->firstOrFail();

        if (! $immediately) {
            $subscription->forceFill(['cancel_at' => $subscription->renews_at ?? now()->addMonth()])->save();

            return $subscription->fresh();
        }

        $subscription->forceFill([
            'status' => Subscription::STATUS_CANCELLED,
            'cancel_at' => now(),
            'ends_at' => now(),
            'grace_ends_at' => null,
        ])->save();
        $tenant->forceFill(['status' => Tenant::STATUS_CANCELLED])->save();

        return $subscription->fresh();
    }

    public function synchronizeUsage(Tenant $tenant): int
    {
        $billableUsers = $this->billableUsers($tenant);
        $tenant->currentSubscription()->update(['billable_users' => $billableUsers]);

        return $billableUsers;
    }

    private function billableUsers(Tenant $tenant): int
    {
        return $tenant->run(fn (): int => MoonshineUser::query()->count());
    }

    private function ensureWithinPlanLimit(Plan $plan, int $billableUsers): void
    {
        abort_if($plan->max_users !== null && $billableUsers > $plan->max_users, 422, 'The school exceeds this plan user limit.');
    }
}
