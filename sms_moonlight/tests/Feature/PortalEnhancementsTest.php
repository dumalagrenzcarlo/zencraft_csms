<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Setting;
use App\MoonShine\Resources\Announcement\AnnouncementResource;
use App\Services\Exports\StudentGradesPdfExporter;
use App\Support\AnnouncementHtml;
use App\Support\PortalGreeting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\UI\Fields\Select;
use Tests\TestCase;

class PortalEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcement_audiences_do_not_include_college(): void
    {
        $audienceField = collect(app(AnnouncementResource::class)->formFields())
            ->first(fn ($field) => $field instanceof Select && $field->getColumn() === 'target_audience');

        $values = collect($audienceField?->getValues()->toArray() ?? [])
            ->pluck('label', 'value')
            ->all();

        $this->assertSame([
            'both' => 'Both',
            'students' => 'Students',
            'teachers' => 'Teachers',
        ], $values);
        $this->assertArrayNotHasKey('college', $values);
    }

    public function test_announcement_html_is_sanitized_and_safe_formatting_is_preserved(): void
    {
        $announcement = Announcement::create([
            'title' => 'Formatted notice',
            'content' => '<h2 onclick="alert(1)">Hello</h2><p>Read <strong>this</strong>.</p><script>alert(1)</script><a href="javascript:alert(1)">Unsafe</a><a href="https://example.com" target="_blank">Safe</a>',
            'target_audience' => 'students',
        ]);

        $this->assertStringContainsString('<h2>Hello</h2>', $announcement->content);
        $this->assertStringContainsString('<strong>this</strong>', $announcement->content);
        $this->assertStringContainsString('href="https://example.com"', $announcement->content);
        $this->assertStringContainsString('rel="noopener noreferrer"', $announcement->content);
        $this->assertStringNotContainsString('onclick', $announcement->content);
        $this->assertStringNotContainsString('<script', $announcement->content);
        $this->assertStringNotContainsString('javascript:', $announcement->content);
        $this->assertSame($announcement->content, AnnouncementHtml::sanitize($announcement->content));
    }

    public function test_portal_announcements_open_in_a_shared_modal(): void
    {
        $layout = file_get_contents(resource_path('views/portals/layout.blade.php'));
        $menu = file_get_contents(resource_path('views/portals/partials/announcement-menu.blade.php'));

        $this->assertStringContainsString('id="portalAnnouncementModal"', $layout);
        $this->assertStringContainsString('window.openPortalAnnouncement', $layout);
        $this->assertStringContainsString('Read announcement', $menu);
        $this->assertStringContainsString('{!! $announcement->content !!}', $menu);
    }

    public function test_qr_setting_defaults_to_enabled_and_blocks_qr_only_api_when_disabled(): void
    {
        $this->assertDatabaseHas('settings', [
            'settingName' => 'qr_code_enabled',
            'settingValue' => '1',
            'settingType' => 'boolean',
        ]);
        $this->assertDatabaseHas('settings', [
            'settingName' => 'rfid_enabled',
            'settingValue' => '1',
            'settingType' => 'boolean',
        ]);

        config()->set('school.qr_code_enabled', '0');

        $this->assertFalse(Setting::enabled('qr_code_enabled', true));
        $this->getJson('/api/validatetoken')->assertNotFound();
        $this->getJson('/api/autosync')->assertUnauthorized();
        $this->postJson('/api/attendance/sync')->assertUnauthorized();
        $this->get('/api/application/student-images')->assertNotFound();
    }

    public function test_rfid_setting_blocks_only_rfid_features_when_disabled(): void
    {
        config()->set('school.rfid_enabled', '0');

        $this->assertFalse(Setting::enabled('rfid_enabled', true));
        $this->getJson('/api/rfid/cards')->assertNotFound();
        $this->postJson('/api/attendance/rfid')->assertNotFound();

        $this->getJson('/api/autosync')->assertUnauthorized();
        $this->postJson('/api/attendance/sync')->assertUnauthorized();
    }

    public function test_bulk_grade_export_downloads_a_pdf(): void
    {
        $response = app(StudentGradesPdfExporter::class)->download(
            collect(),
            'all-student-grades.pdf'
        );

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'all-student-grades.pdf',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_student_portal_contains_the_payment_history_tab_and_new_assignment_label(): void
    {
        $dashboard = file_get_contents(resource_path('views/portals/student/dashboard.blade.php'));
        $paymentHistory = file_get_contents(resource_path('views/portals/student/partials/payment-history-tab.blade.php'));

        $this->assertStringContainsString("\$studentTabs['payments'] = 'Payment History'", $dashboard);
        $this->assertStringContainsString("'assignments' => 'Assignments and Activities'", $dashboard);
        $this->assertStringContainsString('name="class_search"', $dashboard);
        $this->assertStringContainsString('name="payment_search"', $paymentHistory);
        $this->assertStringNotContainsString('Academic status', $dashboard);
        $this->assertFileExists(resource_path('views/portals/student/partials/payment-history-tab.blade.php'));
    }

    public function test_portal_deployment_fallbacks_cover_dashboard_grid_and_dialog_stacking(): void
    {
        $layout = file_get_contents(resource_path('views/portals/layout.blade.php'));
        $studentDashboard = file_get_contents(resource_path('views/portals/student/dashboard.blade.php'));
        $teacherDashboard = file_get_contents(resource_path('views/portals/teacher/dashboard.blade.php'));

        $this->assertStringContainsString('.portal-dashboard-grid', $layout);
        $this->assertStringContainsString('z-index: 10000 !important', $layout);
        $this->assertStringContainsString('document.body.appendChild(dialog)', $layout);
        $this->assertStringContainsString('portal-dashboard-grid gap-4', $studentDashboard);
        $this->assertStringContainsString('portal-dialog fixed', $teacherDashboard);
        $this->assertStringContainsString("studentSearchInput.addEventListener('input'", $teacherDashboard);
    }

    public function test_all_login_pages_link_back_to_the_main_portal_selection(): void
    {
        $selectionUrl = route('portal.selection');

        $this->get(route('portal.selection'))
            ->assertOk()
            ->assertSee('Choose your portal');

        $this->get(route('student.login'))
            ->assertOk()
            ->assertSee('Back to portal selection')
            ->assertSee('href="'.$selectionUrl.'"', false);

        $this->get(route('teacher.login'))
            ->assertOk()
            ->assertSee('Back to portal selection')
            ->assertSee('href="'.$selectionUrl.'"', false);

        $adminLayout = file_get_contents(app_path('MoonShine/Layouts/AdminLoginLayout.php'));
        $adminBackLink = file_get_contents(resource_path('views/admin/back-to-portal-selection.blade.php'));

        $this->assertStringContainsString("view('admin.back-to-portal-selection')", $adminLayout);
        $this->assertStringContainsString("route('portal.selection')", $adminBackLink);
    }

    public function test_portal_greeting_changes_with_the_time_of_day(): void
    {
        $timezone = 'Asia/Manila';

        $this->assertSame('Good Morning', PortalGreeting::message(CarbonImmutable::parse('2026-07-25 08:00', $timezone)));
        $this->assertSame('Good Afternoon', PortalGreeting::message(CarbonImmutable::parse('2026-07-25 14:00', $timezone)));
        $this->assertSame('Good Evening', PortalGreeting::message(CarbonImmutable::parse('2026-07-25 20:00', $timezone)));
        $this->assertSame('Good Evening', PortalGreeting::message(CarbonImmutable::parse('2026-07-25 02:00', $timezone)));
    }
}
