<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\PlatformAuditLog;
use App\Models\SupportAccessGrant;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SupportAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SupportAccessController extends Controller
{
    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'support',
            'active' => true,
        ]);

        PlatformAuditLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'platform_support_user.created',
            'ip_address' => $request->ip(),
            'context' => ['support_user_id' => $user->id, 'email' => $user->email],
            'created_at' => now(),
        ]);

        return back()->with('status', 'Support user created and ready for access assignments.');
    }

    public function store(Request $request, Tenant $school, SupportAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'support_user_id' => ['required', Rule::exists('users', 'id')->where('role', 'support')->where('active', true)],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $grant = $access->grant($school, User::query()->findOrFail($data['support_user_id']), $request->user(), $data['reason']);
        $this->audit($request, $school, 'support_access.granted', ['grant_id' => $grant->id, 'expires_at' => $grant->expires_at]);

        return back()->with('status', 'Temporary support access granted.');
    }

    public function destroy(Request $request, Tenant $school, SupportAccessGrant $grant, SupportAccess $access): RedirectResponse
    {
        abort_unless($grant->tenant_id === $school->id, 404);
        $access->revoke($grant);
        $this->audit($request, $school, 'support_access.revoked', ['grant_id' => $grant->id]);

        return back()->with('status', 'Support access revoked.');
    }

    private function audit(Request $request, Tenant $tenant, string $event, array $context): void
    {
        PlatformAuditLog::query()->create([
            'user_id' => $request->user()->id, 'tenant_id' => $tenant->id, 'event' => $event,
            'ip_address' => $request->ip(), 'context' => $context, 'created_at' => now(),
        ]);
    }
}
