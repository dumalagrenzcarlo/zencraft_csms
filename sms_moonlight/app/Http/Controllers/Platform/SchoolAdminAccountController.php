<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\MoonshineUser;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class SchoolAdminAccountController extends Controller
{
    public function update(Request $request, Tenant $school): RedirectResponse
    {
        $data = $request->validate([
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $updated = $school->run(function () use ($data): bool {
            $admin = MoonshineUser::query()
                ->where('moonshine_user_role_id', 1)
                ->orderBy('id')
                ->first();

            if ($admin === null) {
                return false;
            }

            $admin->forceFill([
                'password' => Hash::make($data['admin_password']),
                'must_change_password' => true,
            ])->save();

            return true;
        });

        if (! $updated) {
            return back()->withErrors(['admin_password' => 'No school administrator account was found.']);
        }

        PlatformAuditLog::query()->create([
            'user_id' => $request->user()->id,
            'tenant_id' => $school->id,
            'event' => 'school_admin.password_reset',
            'ip_address' => $request->ip(),
            'context' => ['must_change_password' => true],
            'created_at' => now(),
        ]);

        return back()->with('status', 'The school administrator temporary password was updated.');
    }
}
