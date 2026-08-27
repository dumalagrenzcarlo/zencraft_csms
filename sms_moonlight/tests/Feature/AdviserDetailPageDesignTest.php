<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Adviser;
use App\MoonShine\Resources\Adviser\AdviserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Tests\TestCase;

class AdviserDetailPageDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_adviser_detail_view_has_information_sections_and_photo_edit_affordance(): void
    {
        $teacher = new Adviser([
            'name' => 'John Wick',
            'rank' => 'Teacher I',
            'major' => 'English',
            'is_college_instructor' => true,
            'shift_start_time' => null,
            'shift_end_time' => null,
        ]);

        $html = view('admin.advisers.detail', [
            'teacher' => $teacher,
            'backUrl' => '/admin/teachers',
            'editUrl' => '/admin/teachers/4/edit',
            'shiftStart' => 'Not set',
            'shiftEnd' => 'Not set',
            'showRfid' => true,
        ])->render();

        $this->assertStringContainsString('Adviser actions', $html);
        $this->assertStringContainsString('Personal &amp; Employment Details', $html);
        $this->assertStringContainsString('Work Schedule', $html);
        $this->assertStringContainsString('College Instructor', $html);
        $this->assertStringContainsString('Change photo', $html);
        $this->assertStringContainsString('aria-label="Change profile photo for John Wick"', $html);
        $this->assertStringContainsString('/admin/teachers/4/edit#profile_photo', $html);
        $this->assertStringContainsString('adviser-photo-action:hover .adviser-photo-action__overlay', $html);
    }

    public function test_authenticated_admin_can_render_the_designed_adviser_detail_page(): void
    {
        MoonshineUserRole::query()->firstOrCreate(
            ['id' => MoonshineUserRole::DEFAULT_ROLE_ID],
            ['name' => 'Admin'],
        );

        $admin = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
            'username' => 'design-admin',
            'email' => 'design-admin@example.test',
            'name' => 'Design Admin',
            'password' => Hash::make('admin-password'),
        ]);
        $admin->save();

        $teacher = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'John Wick',
            'rank' => 'Teacher I',
            'major' => 'English',
            'is_college_instructor' => true,
        ]));

        $response = $this
            ->actingAs($admin, 'moonshine')
            ->get(app(AdviserResource::class)->getDetailPageUrl($teacher->getKey()));

        $response
            ->assertOk()
            ->assertSee('Adviser Information')
            ->assertSee('Personal &amp; Employment Details', false)
            ->assertSee('Work Schedule')
            ->assertSee('Change photo')
            ->assertSee('data-adviser-delete-control', false);
    }
}
