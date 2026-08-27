<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SubscriptionLifecycle;
use Illuminate\Console\Command;

class ReconcileSubscriptions extends Command
{
    protected $signature = 'saas:subscriptions:reconcile';

    protected $description = 'Apply expired trials, grace periods, and scheduled subscription cancellations';

    public function handle(SubscriptionLifecycle $lifecycle): int
    {
        $count = $lifecycle->reconcileAll();
        $this->info("Reconciled {$count} subscription(s).");

        return self::SUCCESS;
    }
}
