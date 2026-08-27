<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\OnboardingReadiness;
use Illuminate\Console\Command;

class LaunchReadiness extends Command
{
    protected $signature = 'saas:readiness {tenant? : Tenant ID or slug}';

    protected $description = 'Check commercial launch readiness across school tenants';

    public function handle(OnboardingReadiness $onboarding): int
    {
        $query = Tenant::query()->with(['currentSubscription', 'backups']);
        if ($identifier = $this->argument('tenant')) {
            $query->where(fn ($query) => $query->where('id', $identifier)->orWhere('slug', $identifier));
        }
        $rows = [];
        $ready = true;
        foreach ($query->get() as $tenant) {
            $check = $onboarding->inspect($tenant);
            $subscription = $tenant->currentSubscription?->permitsAccess() === true;
            $backup = $tenant->backups->whereNotNull('verified_at')->sortByDesc('verified_at')->first();
            $backupCurrent = $backup?->verified_at?->greaterThan(now()->subDays(2)) === true;
            $tenantReady = $check['ready'] && $subscription && $backupCurrent;
            $ready = $ready && $tenantReady;
            $rows[] = [$tenant->slug, $check['percent'].'%', $subscription ? 'yes' : 'no', $backupCurrent ? 'yes' : 'no', $tenantReady ? 'ready' : 'blocked'];
        }
        $this->table(['School', 'Onboarding', 'Subscription', 'Recent backup', 'Launch'], $rows);

        return $ready && $rows !== [] ? self::SUCCESS : self::FAILURE;
    }
}
