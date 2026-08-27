<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Tests\TestCase;

class AdminAuthenticationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_renders_assets_without_an_authenticated_fragment_request(): void
    {
        $response = $this->get(route('moonshine.login'));

        $response
            ->assertOk()
            ->assertDontSee('_fragment-load=assets', false)
            ->assertDontSee('imgPopup()', false)
            ->assertSee("x-data=\"{ open: false, src: '', auto: true", false)
            ->assertSee('vendor/moonshine/assets/app.js', false);
    }

    public function test_authenticated_admin_uses_the_compatible_image_popup_markup(): void
    {
        $admin = $this->createMoonshineUser(
            MoonshineUserRole::DEFAULT_ROLE_ID,
            'dashboard-admin',
            'admin-password'
        );

        $response = $this
            ->actingAs($admin, 'moonshine')
            ->get(route('moonshine.index'));

        $response
            ->assertOk()
            ->assertDontSee('imgPopup()', false)
            ->assertSee("x-data=\"{ open: false, src: '', auto: true", false);
    }

    public function test_student_credentials_cannot_authenticate_on_the_admin_login(): void
    {
        $student = $this->createMoonshineUser(3, 'student-user', 'student-password');

        $response = $this->post(route('moonshine.authenticate'), [
            'username' => $student->username,
            'password' => 'student-password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest('moonshine');
    }

    public function test_existing_student_session_cannot_enter_the_admin_dashboard(): void
    {
        $student = $this->createMoonshineUser(3, 'student-user', 'student-password');

        $response = $this
            ->actingAs($student, 'moonshine')
            ->get(route('moonshine.index'));

        $response->assertRedirect(route('moonshine.login'));
        $this->assertGuest('moonshine');
    }

    public function test_guest_cannot_access_custom_admin_routes(): void
    {
        $response = $this->get(route('admin.students.export'));

        $response->assertRedirect(route('moonshine.login'));
        $this->assertGuest('moonshine');

        $this->get(route('admin.students.template'))
            ->assertRedirect(route('moonshine.login'));
    }

    public function test_portal_user_cannot_access_custom_admin_routes(): void
    {
        $student = $this->createMoonshineUser(3, 'custom-route-student', 'student-password');

        $response = $this
            ->actingAs($student, 'moonshine')
            ->get(route('admin.students.export'));

        $response->assertRedirect(route('moonshine.login'));
        $this->assertGuest('moonshine');
    }

    public function test_admin_can_access_custom_admin_routes(): void
    {
        $admin = $this->createMoonshineUser(
            MoonshineUserRole::DEFAULT_ROLE_ID,
            'custom-route-admin',
            'admin-password'
        );

        $response = $this
            ->actingAs($admin, 'moonshine')
            ->get(route('admin.students.export'));

        $response->assertOk();
        $this->assertAuthenticatedAs($admin, 'moonshine');
    }

    public function test_admin_credentials_can_still_authenticate(): void
    {
        $admin = $this->createMoonshineUser(
            MoonshineUserRole::DEFAULT_ROLE_ID,
            'admin-user',
            'admin-password'
        );

        $response = $this->post(route('moonshine.authenticate'), [
            'username' => $admin->username,
            'password' => 'admin-password',
        ]);

        $response->assertRedirect(route('moonshine.index'));
        $this->assertAuthenticatedAs($admin, 'moonshine');
    }

    private function createMoonshineUser(int $roleId, string $username, string $password): MoonshineUser
    {
        if (! MoonshineUserRole::query()->whereKey($roleId)->exists()) {
            (new MoonshineUserRole)->forceFill([
                'id' => $roleId,
                'name' => $roleId === MoonshineUserRole::DEFAULT_ROLE_ID ? 'Admin' : 'Portal User',
            ])->save();
        }

        $user = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => $roleId,
            'username' => $username,
            'email' => $username.'@example.test',
            'name' => $username,
            'password' => Hash::make($password),
        ]);
        $user->save();

        return $user;
    }
}
