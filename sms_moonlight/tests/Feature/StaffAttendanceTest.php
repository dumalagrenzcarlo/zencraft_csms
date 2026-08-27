<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\StaffAttendanceExport;
use App\Models\Adviser;
use App\Models\Staff;
use App\MoonShine\Fields\TimePicker;
use App\MoonShine\Layouts\CustomLayout;
use App\MoonShine\Pages\StaffAttendanceDashboard;
use App\MoonShine\Pages\StudentAttendanceDashboard;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\MoonShine\Resources\AttendanceRecord\AttendanceRecordResource;
use App\MoonShine\Resources\Staff\StaffResource;
use App\Support\MoonShineTablePagination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use MoonShine\Laravel\Models\MoonshineUser;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'staff-attendance-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('settings')->updateOrInsert(
            ['settingName' => 'api_authcode'],
            [
                'settingValue' => self::TOKEN,
                'settingType' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function test_staff_records_do_not_create_teacher_portal_accounts(): void
    {
        $staff = Staff::create([
            'name' => 'Juan Dela Cruz',
            'rank' => 'Registrar',
            'major' => 'Records Office',
            'shift_start_time' => '08:00:00',
            'shift_end_time' => '17:00:00',
        ]);

        $this->assertSame(Adviser::TYPE_STAFF, $staff->staff_type);
        $this->assertNull($staff->user_id);
        $this->assertDatabaseMissing('moonshine_users', ['name' => 'Juan Dela Cruz']);
    }

    public function test_staff_rfid_scan_is_recorded_and_reported_as_late(): void
    {
        $staffId = DB::table('advisers')->insertGetId([
            'name' => 'Juan Dela Cruz',
            'rank' => 'Registrar',
            'major' => 'Records Office',
            'staff_type' => Adviser::TYPE_STAFF,
            'shift_start_time' => '08:00:00',
            'shift_end_time' => '17:00:00',
            'rfid_card_uid' => 'STAFF-CARD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('X-API-AUTHCODE', self::TOKEN)
            ->postJson('/api/attendance/rfid', [
                'rfid_card_uid' => 'STAFF-CARD',
                'currentdate' => '2026-07-28',
                'time' => '09:15:00',
            ])
            ->assertCreated()
            ->assertJson([
                'person_type' => 'staff',
                'person_id' => $staffId,
                'recorded' => true,
            ]);

        request()->query->set('start_date', '2026-07-28');
        request()->query->set('end_date', '2026-07-28');
        $page = app(StaffAttendanceDashboard::class);
        $components = (new ReflectionMethod($page, 'components'))->invoke($page);
        $html = collect($components)->map(static fn ($component): string => (string) $component)->implode('');

        $this->assertStringContainsString('Juan Dela Cruz', $html);
        $this->assertStringContainsString('Late', $html);
        $this->assertStringContainsString('1 hr 15 min', $html);
        $this->assertStringContainsString('Export to Excel', $html);
    }

    public function test_staff_attendance_export_uses_selected_date_range(): void
    {
        $staffId = DB::table('advisers')->insertGetId([
            'name' => 'Juan Dela Cruz',
            'rank' => 'Registrar',
            'major' => 'Records Office',
            'staff_type' => Adviser::TYPE_STAFF,
            'shift_start_time' => '08:00:00',
            'shift_end_time' => '17:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('attendance_record')->insert([
            'adviser_id' => $staffId,
            'amlogin' => '00:00:00',
            'amlogout' => '00:00:00',
            'pmlogin' => '00:00:00',
            'pmlogout' => '00:00:00',
            'currentdate' => '2026-07-28',
            'logged_time' => '09:15:00',
            'source' => 'rfid',
        ]);
        $admin = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 1,
            'username' => 'attendance-admin',
            'email' => 'attendance-admin@example.test',
            'name' => 'Attendance Admin',
            'password' => Hash::make('password'),
        ]);
        $admin->save();
        Excel::fake();

        $this->actingAs($admin, 'moonshine')
            ->get(route('admin.staff-attendance.export', [
                'start_date' => '2026-07-28',
                'end_date' => '2026-07-28',
            ]))
            ->assertOk();

        Excel::assertDownloaded(
            'teacher-staff-attendance-2026-07-28-to-2026-07-28.xlsx',
            function (StaffAttendanceExport $export): bool {
                $row = $export->rows->first();

                return $export->rows->count() === 1
                    && $row->name === 'Juan Dela Cruz'
                    && $row->late_duration === '1 hr 15 min';
            },
        );
    }

    public function test_staff_dashboard_uses_moonshine_table_filters_and_calculates_total_time(): void
    {
        $staffId = DB::table('advisers')->insertGetId([
            'name' => 'Juan Dela Cruz',
            'rank' => 'Registrar',
            'major' => 'Records Office',
            'staff_type' => Adviser::TYPE_STAFF,
            'shift_start_time' => '08:00:00',
            'shift_end_time' => '17:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $teacherId = DB::table('advisers')->insertGetId([
            'name' => 'Maria Santos',
            'rank' => 'Teacher I',
            'major' => 'English',
            'staff_type' => Adviser::TYPE_TEACHER,
            'shift_start_time' => '08:00:00',
            'shift_end_time' => '17:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            [$staffId, '07:45:00'],
            [$staffId, '17:15:00'],
            [$teacherId, '08:30:00'],
            [$teacherId, '16:30:00'],
        ] as [$adviserId, $loggedTime]) {
            DB::table('attendance_record')->insert([
                'adviser_id' => $adviserId,
                'amlogin' => '00:00:00',
                'amlogout' => '00:00:00',
                'pmlogin' => '00:00:00',
                'pmlogout' => '00:00:00',
                'currentdate' => '2026-07-28',
                'logged_time' => $loggedTime,
                'source' => 'rfid',
            ]);
        }

        request()->query->replace([
            'start_date' => '2026-07-28',
            'end_date' => '2026-07-28',
            'search' => 'registrar',
            'staff_type' => Adviser::TYPE_STAFF,
            'status' => 'On time',
        ]);

        $page = app(StaffAttendanceDashboard::class);
        $components = (new ReflectionMethod($page, 'components'))->invoke($page);
        $html = collect($components)->map(static fn ($component): string => (string) $component)->implode('');

        $this->assertStringContainsString('Total time', $html);
        $this->assertStringContainsString('9 hrs 30 min', $html);
        $this->assertStringContainsString('name="search"', $html);
        $this->assertStringContainsString('name="staff_type"', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('Juan Dela Cruz', $html);
        $this->assertStringNotContainsString('Maria Santos', $html);
    }

    public function test_attendance_table_pagination_preserves_filters(): void
    {
        request()->query->replace([
            'search' => 'Juan',
            'status' => 'On time',
            'staff_page' => '2',
        ]);

        [$pageItems, $paginator] = MoonShineTablePagination::make(
            collect(range(1, 16)),
            'staff_page',
        );

        $this->assertSame([16], $pageItems->all());
        $this->assertSame(2, $paginator->getCurrentPage());
        $this->assertSame(16, $paginator->getTotal());
        $this->assertSame('staff_page', $paginator->getPageName());
        $this->assertStringContainsString('search=Juan', (string) $paginator->getPrevPageUrl());
        $this->assertStringContainsString('status=On%20time', (string) $paginator->getPrevPageUrl());
    }

    public function test_attendance_env_switch_hides_personnel_rfid_fields_and_excludes_scanner_identity(): void
    {
        config()->set('school_portal.features.teacher_staff_attendance', false);

        $teacherId = DB::table('advisers')->insertGetId([
            'name' => 'Teacher Maria',
            'rank' => 'Teacher I',
            'major' => 'English',
            'staff_type' => Adviser::TYPE_TEACHER,
            'rfid_card_uid' => 'TEACHER-CARD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacherFields = collect(app(AdviserResource::class)->indexFields())
            ->map(static fn ($field): string => $field->getColumn())
            ->all();
        $staffFields = collect(app(StaffResource::class)->indexFields())
            ->map(static fn ($field): string => $field->getColumn())
            ->all();

        $this->assertNotContains('rfid_card_uid', $teacherFields);
        $this->assertNotContains('rfid_card_uid', $staffFields);

        $this->getJson('/api/rfid/cards?token='.self::TOKEN)
            ->assertOk()
            ->assertJsonMissing(['person_id' => $teacherId]);
    }

    public function test_student_attendance_resource_excludes_employee_records(): void
    {
        $studentId = DB::table('students')->insertGetId([
            'lrn' => '000123456789',
            'lastname' => 'Santos',
            'firstname' => 'Ana',
            'middlename' => 'Reyes',
            'gender' => 'Female',
        ]);
        $staffId = DB::table('advisers')->insertGetId([
            'name' => 'Juan Dela Cruz',
            'rank' => 'Registrar',
            'major' => 'Records Office',
            'staff_type' => Adviser::TYPE_STAFF,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studentAttendanceId = DB::table('attendance_record')->insertGetId([
            'student_id' => $studentId,
            'amlogin' => '00:00:00',
            'amlogout' => '00:00:00',
            'pmlogin' => '00:00:00',
            'pmlogout' => '00:00:00',
            'currentdate' => '2026-07-28',
            'logged_time' => '08:00:00',
            'source' => 'rfid',
        ]);
        DB::table('attendance_record')->insert([
            'adviser_id' => $staffId,
            'amlogin' => '00:00:00',
            'amlogout' => '00:00:00',
            'pmlogin' => '00:00:00',
            'pmlogout' => '00:00:00',
            'currentdate' => '2026-07-28',
            'logged_time' => '08:00:00',
            'source' => 'rfid',
        ]);

        $this->assertSame(
            [$studentAttendanceId],
            app(AttendanceRecordResource::class)->getQuery()->pluck('id')->all(),
        );
    }

    public function test_sidebar_uses_requested_attendance_and_staff_groups(): void
    {
        $source = file_get_contents((new ReflectionClass(CustomLayout::class))->getFileName());
        $announcementPosition = strpos($source, "label: 'Announcements'");
        $attendancePosition = strpos($source, "MenuGroup::make('Attendance'");

        $this->assertNotFalse($announcementPosition);
        $this->assertNotFalse($attendancePosition);
        $this->assertGreaterThan($announcementPosition, $attendancePosition);
        $this->assertStringContainsString("label: 'Student Attendance'", $source);
        $this->assertStringContainsString("label: 'Staff Attendance'", $source);
        $this->assertStringContainsString("\$staffEnabled ? 'Students & Staff' : 'Students & Teachers'", $source);
    }

    public function test_student_dashboard_uses_date_filters_and_shift_fields_use_time_pickers(): void
    {
        $studentId = DB::table('students')->insertGetId([
            'lrn' => '000123456789',
            'lastname' => 'Santos',
            'firstname' => 'Ana',
            'middlename' => 'Reyes',
            'gender' => 'Female',
        ]);
        DB::table('attendance_record')->insert([
            'student_id' => $studentId,
            'amlogin' => '00:00:00',
            'amlogout' => '00:00:00',
            'pmlogin' => '00:00:00',
            'pmlogout' => '00:00:00',
            'currentdate' => '2026-07-28',
            'logged_time' => '07:30:00',
            'source' => 'rfid',
        ]);
        DB::table('attendance_record')->insert([
            'student_id' => $studentId,
            'amlogin' => '00:00:00',
            'amlogout' => '00:00:00',
            'pmlogin' => '00:00:00',
            'pmlogout' => '00:00:00',
            'currentdate' => '2026-07-28',
            'logged_time' => '17:00:00',
            'source' => 'rfid',
        ]);

        request()->query->replace([
            'start_date' => '2026-07-28',
            'end_date' => '2026-07-28',
            'search' => '000123456789',
            'status' => 'On time',
        ]);
        $page = app(StudentAttendanceDashboard::class);
        $components = (new ReflectionMethod($page, 'components'))->invoke($page);
        $html = collect($components)->map(static fn ($component): string => (string) $component)->implode('');

        $this->assertStringContainsString('Ana Reyes Santos', $html);
        $this->assertStringContainsString('name="start_date"', $html);
        $this->assertStringContainsString('name="end_date"', $html);
        $this->assertStringContainsString('name="search"', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('Total time', $html);
        $this->assertStringContainsString('9 hrs 30 min', $html);

        $teacherShiftFields = collect(app(AdviserResource::class)->formFields())
            ->filter(static fn ($field): bool => in_array($field->getColumn(), ['shift_start_time', 'shift_end_time'], true));
        $staffShiftFields = collect(app(StaffResource::class)->formFields())
            ->filter(static fn ($field): bool => in_array($field->getColumn(), ['shift_start_time', 'shift_end_time'], true));

        $this->assertCount(2, $teacherShiftFields);
        $this->assertCount(2, $staffShiftFields);
        $this->assertTrue($teacherShiftFields->every(static fn ($field): bool => $field instanceof TimePicker));
        $this->assertTrue($staffShiftFields->every(static fn ($field): bool => $field instanceof TimePicker));
    }
}
