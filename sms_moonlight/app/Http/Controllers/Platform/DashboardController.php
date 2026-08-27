<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('platform.dashboard', [
            'schoolCount' => Tenant::query()->count(),
            'activeCount' => Tenant::query()->whereIn('status', [Tenant::STATUS_TRIAL, Tenant::STATUS_ACTIVE])->count(),
            'trialCount' => Tenant::query()->where('status', Tenant::STATUS_TRIAL)->count(),
            'billableUsers' => Subscription::query()->sum('billable_users'),
            'schools' => Tenant::query()->with(['currentPlan', 'domains'])->latest()->limit(8)->get(),
        ]);
    }
}
