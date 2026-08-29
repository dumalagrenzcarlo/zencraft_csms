<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Notifications\VerifySchoolAdminEmail;
use App\Services\SchoolProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicSignupController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless(config('saas.public_signup.enabled'), 404);

        $left = random_int(2, 9);
        $right = random_int(1, 9);
        $request->session()->put('signup_captcha_answer', $left + $right);

        return view('signup.create', compact('left', 'right'));
    }

    public function store(Request $request, SchoolProvisioner $provisioner): View
    {
        abort_unless(config('saas.public_signup.enabled'), 404);

        $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);

        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required', 'alpha_dash:ascii', 'min:3', 'max:50',
                Rule::notIn(config('saas.reserved_tenant_slugs', [])),
                Rule::unique('tenants', 'slug'),
            ],
            'timezone' => ['required', 'timezone:all'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:190'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
            'captcha_answer' => ['required', 'integer'],
            'website' => ['nullable', 'max:0'],
            'terms' => ['accepted'],
        ]);

        $expectedAnswer = $request->session()->get('signup_captcha_answer');

        if ($expectedAnswer === null || (int) $validated['captcha_answer'] !== (int) $expectedAnswer) {
            throw ValidationException::withMessages([
                'captcha_answer' => 'Please check the answer and try again.',
            ]);
        }

        $freePlan = Plan::query()
            ->where('active', true)
            ->where('monthly_price_cents', 0)
            ->orderBy('id')
            ->first();

        if ($freePlan === null) {
            throw ValidationException::withMessages([
                'school_name' => 'Free signup is temporarily unavailable. Please contact ZenCraft support.',
            ]);
        }

        $school = $provisioner->create([
            'name' => $validated['school_name'],
            'slug' => $validated['slug'],
            'timezone' => $validated['timezone'],
            'plan_id' => $freePlan->id,
            'admin_name' => $validated['admin_name'],
            'admin_email' => strtolower($validated['admin_email']),
            'admin_password' => $validated['admin_password'],
            'admin_must_change_password' => false,
        ]);

        $requiresVerification = (bool) config('saas.public_signup.require_email_verification', false);

        $school->setAttribute('signup_requires_email_verification', $requiresVerification);
        $school->setAttribute('signup_admin_email', strtolower($validated['admin_email']));
        $school->setAttribute('signup_email_verified_at', $requiresVerification ? null : now()->toISOString());
        $school->save();

        if ($requiresVerification) {
            Notification::route('mail', strtolower($validated['admin_email']))
                ->notify(new VerifySchoolAdminEmail(
                    $school->id,
                    $school->name,
                    $school->slug,
                    strtolower($validated['admin_email'])
                ));
        }

        $request->session()->forget('signup_captcha_answer');

        return view('signup.created', [
            'school' => $school,
            'email' => strtolower($validated['admin_email']),
            'requiresVerification' => $requiresVerification,
        ]);
    }

    public function verify(Request $request, string $tenant, string $email): RedirectResponse
    {
        abort_unless(config('saas.public_signup.enabled'), 404);

        $school = Tenant::query()->findOrFail($tenant);
        abort_unless(hash_equals((string) $school->getAttribute('signup_admin_email'), strtolower($email)), 403);

        $school->setAttribute('signup_requires_email_verification', false);
        $school->setAttribute('signup_email_verified_at', now()->toISOString());
        $school->save();

        return redirect(url($school->slug.'/admin/login'))
            ->with('status', 'Email verified. You can now sign in.');
    }
}
