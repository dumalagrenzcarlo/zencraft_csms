<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Services\SubscriptionLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function update(Request $request, Tenant $school, SubscriptionLifecycle $lifecycle): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'change_plan', 'past_due', 'cancel_at_period_end', 'cancel_now', 'sync_usage'])],
            'plan_id' => ['nullable', Rule::exists('plans', 'id')->where('active', true)],
        ]);
        $action = $data['action'];

        if (in_array($action, ['activate', 'change_plan'], true)) {
            abort_unless(filled($data['plan_id'] ?? null), 422, 'Select a plan.');
            $lifecycle->activate($school, Plan::query()->findOrFail($data['plan_id']));
        } elseif ($action === 'past_due') {
            $lifecycle->markPastDue($school);
        } elseif ($action === 'cancel_at_period_end') {
            $lifecycle->cancel($school);
        } elseif ($action === 'cancel_now') {
            $lifecycle->cancel($school, true);
        } else {
            $lifecycle->synchronizeUsage($school);
        }

        PlatformAuditLog::query()->create([
            'user_id' => $request->user()->id,
            'tenant_id' => $school->id,
            'event' => 'billing.'.$action,
            'ip_address' => $request->ip(),
            'context' => ['plan_id' => $data['plan_id'] ?? null],
            'created_at' => now(),
        ]);

        return back()->with('status', 'Billing state updated successfully.');
    }
}
