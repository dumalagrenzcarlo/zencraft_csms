<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Services\OnboardingReadiness;
use App\Services\SupportAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OnboardingController extends Controller
{
    public function update(Request $request, Tenant $school, OnboardingReadiness $readiness, SupportAccess $access): RedirectResponse
    {
        abort_unless($access->allows($request->user(), $school), 403);
        $result = $readiness->synchronize($school);
        PlatformAuditLog::query()->create([
            'user_id' => $request->user()->id,
            'tenant_id' => $school->id,
            'event' => 'onboarding.checked',
            'ip_address' => $request->ip(),
            'context' => ['percent' => $result['percent'], 'ready' => $result['ready']],
            'created_at' => now(),
        ]);

        return back()->with('status', $result['ready']
            ? 'School onboarding is complete.'
            : "School onboarding is {$result['percent']}% complete.");
    }
}
