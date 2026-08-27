<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SchoolLifecycleController extends Controller
{
    public function update(Request $request, Tenant $school): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', 'in:suspend,reactivate']]);

        if ($data['action'] === 'suspend') {
            $school->forceFill(['status' => Tenant::STATUS_SUSPENDED, 'suspended_at' => now()])->save();
        } else {
            abort_unless($school->currentSubscription?->permitsAccess(), 422, 'A valid subscription is required before reactivation.');
            $school->forceFill([
                'status' => $school->currentSubscription->status === 'trial' ? Tenant::STATUS_TRIAL : Tenant::STATUS_ACTIVE,
                'suspended_at' => null,
            ])->save();
        }

        PlatformAuditLog::query()->create([
            'user_id' => $request->user()->id, 'tenant_id' => $school->id,
            'event' => 'school.'.$data['action'], 'ip_address' => $request->ip(),
            'context' => [], 'created_at' => now(),
        ]);

        return back()->with('status', 'School lifecycle updated.');
    }
}
