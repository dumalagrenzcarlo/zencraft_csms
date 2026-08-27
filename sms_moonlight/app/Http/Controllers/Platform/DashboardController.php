<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SupportAccess;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SupportAccess $supportAccess): View
    {
        $visible = $supportAccess->scopeVisible(Tenant::query(), $request->user());

        return view('platform.dashboard', [
            'schoolCount' => (clone $visible)->count(),
            'activeCount' => (clone $visible)->whereIn('status', [Tenant::STATUS_TRIAL, Tenant::STATUS_ACTIVE])->count(),
            'trialCount' => (clone $visible)->where('status', Tenant::STATUS_TRIAL)->count(),
            'billableUsers' => $request->user()->role === 'owner'
                ? Subscription::query()->sum('billable_users')
                : Subscription::query()->whereIn('tenant_id', (clone $visible)->select('id'))->sum('billable_users'),
            'schools' => (clone $visible)->with(['currentPlan', 'domains'])->latest()->limit(8)->get(),
        ]);
    }
}
