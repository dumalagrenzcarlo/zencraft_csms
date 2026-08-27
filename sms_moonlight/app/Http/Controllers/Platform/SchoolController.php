<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Services\SchoolProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(): View
    {
        return view('platform.schools.index', [
            'schools' => Tenant::query()->with(['currentPlan', 'domains'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('platform.schools.create', [
            'plans' => Plan::query()->where('active', true)->orderBy('monthly_price_cents')->get(),
        ]);
    }

    public function store(Request $request, SchoolProvisioner $provisioner): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'alpha_dash:ascii', 'min:3', 'max:50', Rule::unique('tenants', 'slug')],
            'timezone' => ['required', 'timezone:all'],
            'plan_id' => ['required', Rule::exists('plans', 'id')->where('active', true)],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:190'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $validated['slug'] = strtolower($validated['slug']);
        $school = $provisioner->create($validated);

        PlatformAuditLog::create([
            'user_id' => $request->user()->id,
            'tenant_id' => $school->id,
            'event' => 'school.provisioned',
            'ip_address' => $request->ip(),
            'context' => ['name' => $school->name, 'slug' => $school->slug],
            'created_at' => now(),
        ]);

        return redirect()->route('platform.schools.show', $school)->with('status', 'School workspace provisioned successfully.');
    }

    public function show(Tenant $school): View
    {
        return view('platform.schools.show', [
            'school' => $school->load(['currentPlan', 'domains', 'subscriptions.plan']),
        ]);
    }
}
