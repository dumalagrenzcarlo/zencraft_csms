<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionExpirationRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->post('/teacher/testing/expired', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
        Route::middleware('web')->post('/student/testing/expired', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
        Route::middleware('web')->post('/admin/testing/expired', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
        Route::middleware('web')->post('/testing/expired', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
    }

    public function test_expired_teacher_request_redirects_to_teacher_login(): void
    {
        $response = $this->post('/teacher/testing/expired');

        $response->assertRedirect(route('teacher.login'));
        $response->assertSessionHasErrors([
            'username' => 'Your session expired. Please sign in again.',
        ]);
    }

    public function test_expired_student_request_redirects_to_student_login(): void
    {
        $response = $this->post('/student/testing/expired');

        $response->assertRedirect(route('student.login'));
        $response->assertSessionHasErrors([
            'lrn' => 'Your session expired. Please sign in again.',
        ]);
    }

    public function test_expired_admin_request_redirects_to_admin_login(): void
    {
        $response = $this->post('/admin/testing/expired');

        $response->assertRedirect(route('moonshine.login'));
        $response->assertSessionHasErrors([
            'username' => 'Your session expired. Please sign in again.',
        ]);
    }

    public function test_portal_domain_is_used_when_routes_have_no_portal_prefix(): void
    {
        config()->set('school_portal.domains.teacher', 'teacher.example.test');

        $response = $this->call('POST', 'http://teacher.example.test/testing/expired', server: [
            'HTTP_HOST' => 'teacher.example.test',
            'SERVER_NAME' => 'teacher.example.test',
        ]);

        $response->assertRedirect(route('teacher.login'));
    }

    public function test_json_requests_keep_the_419_response(): void
    {
        $this->postJson('/student/testing/expired')
            ->assertStatus(419)
            ->assertJsonPath('message', 'CSRF token mismatch.');
    }
}
