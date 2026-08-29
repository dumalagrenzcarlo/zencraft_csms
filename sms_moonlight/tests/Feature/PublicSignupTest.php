<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MoonshineUser;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Notifications\VerifySchoolAdminEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicSignupTest extends TestCase
{
    use RefreshDatabase;

    private array $tenantsToDelete = [];

    protected function tearDown(): void
    {
        tenancy()->end();

        foreach ($this->tenantsToDelete as $tenantId) {
            Tenant::query()->find($tenantId)?->delete();
        }

        parent::tearDown();
    }

    public function test_a_user_can_create_and_verify_a_free_workspace(): void
    {
        Notification::fake();
        $this->freePlan();

        $response = $this->withSession(['signup_captcha_answer' => 11])
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->post('/signup', $this->signupPayload());

        $response->assertOk()->assertSee('Check your email');

        $school = Tenant::query()->where('slug', 'community-school')->firstOrFail();
        $this->tenantsToDelete[] = $school->id;

        $this->assertSame(Tenant::STATUS_ACTIVE, $school->status);
        $this->assertNull($school->trial_ends_at);
        $this->assertTrue((bool) $school->getAttribute('signup_requires_email_verification'));
        $this->assertSame('admin@community.test', $school->getAttribute('signup_admin_email'));
        $this->assertSame('tenant_sqlite', $school->getInternal('db_connection'));
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $school->id,
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
        ]);

        $school->run(function (): void {
            $admin = MoonshineUser::query()->where('username', 'admin')->firstOrFail();

            $this->assertSame('admin@community.test', $admin->email);
            $this->assertFalse($admin->must_change_password);
        });

        Notification::assertSentOnDemand(VerifySchoolAdminEmail::class);

        tenancy()->end();

        $verificationUrl = URL::temporarySignedRoute(
            'signup.verify',
            now()->addHour(),
            ['tenant' => $school->id, 'email' => 'admin@community.test']
        );

        $this->get($verificationUrl)
            ->assertRedirect(url('/community-school/admin/login'));

        $school = $school->fresh();
        $this->assertFalse((bool) $school->getAttribute('signup_requires_email_verification'));
        $this->assertNotNull($school->getAttribute('signup_email_verified_at'));
    }

    public function test_signup_rejects_an_incorrect_security_answer(): void
    {
        Notification::fake();
        $this->freePlan();

        $this->withSession(['signup_captcha_answer' => 9])
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->post('/signup', $this->signupPayload())
            ->assertSessionHasErrors('captcha_answer');

        $this->assertDatabaseMissing('tenants', ['slug' => 'community-school']);
        Notification::assertNothingSent();
    }

    private function freePlan(): Plan
    {
        return Plan::query()->create([
            'name' => 'Free',
            'slug' => 'free-signup',
            'included_users' => 111,
            'max_users' => 111,
            'monthly_price_cents' => 0,
            'active' => true,
        ]);
    }

    private function signupPayload(): array
    {
        return [
            'school_name' => 'Community School',
            'slug' => 'community-school',
            'timezone' => 'Asia/Manila',
            'admin_name' => 'School Admin',
            'admin_email' => 'admin@community.test',
            'admin_password' => 'SecurePassword123!',
            'admin_password_confirmation' => 'SecurePassword123!',
            'captcha_answer' => 11,
            'website' => '',
            'terms' => '1',
        ];
    }
}
