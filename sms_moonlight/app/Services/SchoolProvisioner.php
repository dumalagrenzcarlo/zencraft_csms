<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\MoonshineUser;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Throwable;

class SchoolProvisioner
{
    /**
     * @param array{name:string,slug:string,timezone:string,plan_id:int,admin_name:string,admin_email:string,admin_password:string} $attributes
     */
    public function create(array $attributes): Tenant
    {
        $plan = Plan::query()->whereKey($attributes['plan_id'])->where('active', true)->firstOrFail();
        $trialEndsAt = now()->addDays(config('saas.trial_days', 30));
        $tenant = null;

        try {
            $tenant = DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($attributes, $plan, $trialEndsAt): Tenant {
                $tenant = Tenant::create([
                    'id' => (string) Str::uuid(),
                    'name' => $attributes['name'],
                    'slug' => $attributes['slug'],
                    'status' => Tenant::STATUS_TRIAL,
                    'timezone' => $attributes['timezone'],
                    'current_plan_id' => $plan->id,
                    'trial_ends_at' => $trialEndsAt,
                ]);

                foreach ($this->domainsFor($attributes['slug']) as $domain) {
                    $tenant->domains()->create(['domain' => $domain]);
                }

                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => 'trial',
                    'trial_ends_at' => $trialEndsAt,
                    'starts_at' => now(),
                ]);

                return $tenant;
            });

            $tenant->run(function () use ($attributes): void {
                MoonshineUserRole::query()->firstOrCreate(
                    ['id' => MoonshineUserRole::DEFAULT_ROLE_ID],
                    ['name' => 'Admin']
                );

                MoonshineUser::query()->create([
                    'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
                    'name' => $attributes['admin_name'],
                    'email' => strtolower($attributes['admin_email']),
                    'username' => 'admin',
                    'password' => Hash::make($attributes['admin_password']),
                    'must_change_password' => true,
                ]);

                DB::table('settings')->updateOrInsert(
                    ['settingName' => 'school_name'],
                    ['settingValue' => $attributes['name'], 'settingType' => 'text']
                );

                DB::table('settings')->updateOrInsert(
                    ['settingName' => 'api_authcode'],
                    ['settingValue' => Str::random(48), 'settingType' => 'text']
                );
            });

            $tenant->forceFill(['provisioned_at' => now()])->save();

            return $tenant->fresh(['domains', 'currentPlan', 'subscriptions']);
        } catch (Throwable $exception) {
            if ($tenant?->exists) {
                $tenant->delete();
            }

            throw $exception;
        }
    }

    /** @return list<string> */
    private function domainsFor(string $slug): array
    {
        $base = trim((string) config('saas.tenant_base_domain', 'localhost'), '.');

        return [
            "{$slug}.{$base}",
            "admin.{$slug}.{$base}",
            "teacher.{$slug}.{$base}",
            "student.{$slug}.{$base}",
        ];
    }
}
