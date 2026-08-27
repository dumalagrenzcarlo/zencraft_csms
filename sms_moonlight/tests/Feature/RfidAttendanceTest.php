<?php

namespace Tests\Feature;

use App\Models\Adviser;
use App\MoonShine\Pages\RfidChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class RfidAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'rfid-test-token';

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
            ]
        );
    }

    public function test_student_rfid_scan_records_attendance_and_skips_quick_duplicate(): void
    {
        $studentId = $this->createStudent('000123456789', '04 aB 91');

        $payload = [
            'rfid_card_uid' => '04 AB 91',
            'currentdate' => '2026-07-26',
            'time' => '07:30:00',
        ];

        $this->withHeader('X-API-AUTHCODE', self::TOKEN)
            ->postJson('/api/attendance/rfid', $payload)
            ->assertCreated()
            ->assertJson([
                'recorded' => true,
                'person_type' => 'student',
                'person_id' => $studentId,
                'name' => 'Ana Santos',
            ]);

        $this->withHeader('X-API-AUTHCODE', self::TOKEN)
            ->postJson('/api/attendance/rfid', [
                ...$payload,
                'time' => '07:35:00',
            ])
            ->assertOk()
            ->assertJson([
                'recorded' => false,
                'message' => 'Duplicate scan skipped',
            ]);

        $this->assertDatabaseHas('attendance_record', [
            'student_id' => $studentId,
            'adviser_id' => null,
            'currentdate' => '2026-07-26',
            'logged_time' => '07:30:00',
            'source' => 'rfid',
        ]);
        $this->assertDatabaseCount('attendance_record', 1);
    }

    public function test_teacher_rfid_scan_records_teacher_attendance(): void
    {
        $teacherId = $this->createTeacher('Teacher Maria', '100200300');

        $this->withHeader('X-API-AUTHCODE', self::TOKEN)
            ->postJson('/api/attendance/rfid', [
                'card_uid' => '100200300',
                'currentdate' => '2026-07-26',
                'time' => '06:55:00',
            ])
            ->assertCreated()
            ->assertJson([
                'recorded' => true,
                'person_type' => 'teacher',
                'person_id' => $teacherId,
                'name' => 'Teacher Maria',
            ]);

        $this->assertDatabaseHas('attendance_record', [
            'student_id' => null,
            'adviser_id' => $teacherId,
            'source' => 'rfid',
        ]);
    }

    public function test_rfid_directory_returns_assigned_students_and_teachers(): void
    {
        $studentId = $this->createStudent('000123456789', 'STUDENT-CARD');
        $teacherId = $this->createTeacher('Teacher Maria', 'TEACHER-CARD');

        $this->getJson('/api/rfid/cards?token='.self::TOKEN)
            ->assertOk()
            ->assertJsonFragment([
                'person_type' => 'student',
                'person_id' => $studentId,
                'rfid_card_uid' => 'STUDENT-CARD',
            ])
            ->assertJsonFragment([
                'person_type' => 'teacher',
                'person_id' => $teacherId,
                'rfid_card_uid' => 'TEACHER-CARD',
            ]);
    }

    public function test_autosync_directory_returns_students_and_teachers_with_scanner_identifiers(): void
    {
        $studentId = $this->createStudent('000123456789', 'STUDENT-CARD');
        $teacherId = $this->createTeacher('Teacher Maria', 'TEACHER-CARD');

        $this->getJson('/api/autosync?token='.self::TOKEN)
            ->assertOk()
            ->assertJsonFragment([
                'person_type' => 'student',
                'person_id' => $studentId,
                'student_id' => $studentId,
                'teacher_id' => null,
                'identifier' => 'STUDENT-CARD',
                'lrn' => '000123456789',
            ])
            ->assertJsonFragment([
                'person_type' => 'staff',
                'person_id' => $teacherId,
                'student_id' => null,
                'teacher_id' => $teacherId,
                'identifier' => 'TEACHER-CARD',
                'lrn' => null,
                'name' => 'Teacher Maria',
            ]);
    }

    public function test_autosync_directory_falls_back_when_rfid_card_uid_is_missing(): void
    {
        $studentId = $this->createStudent('000123456789', 'STUDENT-CARD');
        $teacherId = $this->createTeacher('Teacher Maria', 'TEACHER-CARD');

        DB::table('students')->where('id', $studentId)->update(['rfid_card_uid' => null]);
        DB::table('advisers')->where('id', $teacherId)->update(['rfid_card_uid' => null]);

        $this->getJson('/api/autosync?token='.self::TOKEN)
            ->assertOk()
            ->assertJsonFragment([
                'person_type' => 'student',
                'person_id' => $studentId,
                'identifier' => '000123456789',
            ])
            ->assertJsonFragment([
                'person_type' => 'staff',
                'person_id' => $teacherId,
                'identifier' => (string) $teacherId,
            ]);
    }

    public function test_autosync_directory_remains_available_for_rfid_when_qr_is_disabled(): void
    {
        config()->set('school.qr_code_enabled', '0');

        $studentId = $this->createStudent('000123456789', 'STUDENT-CARD');
        $teacherId = $this->createTeacher('Teacher Maria', 'TEACHER-CARD');
        $studentWithoutCard = $this->createStudent('000987654321', 'UNUSED-CARD');

        DB::table('students')->where('id', $studentWithoutCard)->update(['rfid_card_uid' => null]);

        $this->getJson('/api/autosync?token='.self::TOKEN)
            ->assertOk()
            ->assertJsonFragment([
                'person_type' => 'student',
                'person_id' => $studentId,
                'identifier' => 'STUDENT-CARD',
            ])
            ->assertJsonFragment([
                'person_type' => 'staff',
                'person_id' => $teacherId,
                'identifier' => 'TEACHER-CARD',
            ])
            ->assertJsonMissing([
                'person_id' => $studentWithoutCard,
            ]);
    }

    public function test_teacher_record_id_sync_payload_records_teacher_attendance(): void
    {
        $teacherId = $this->createTeacher('Teacher Maria', 'TEACHER-CARD');

        $this->withHeader('X-API-AUTHCODE', self::TOKEN)
            ->postJson('/api/attendance/sync', [
                'records' => [[
                    'teacher_id' => $teacherId,
                    'currentdate' => '2026-07-26',
                    'time' => '07:45:00',
                ]],
            ])
            ->assertOk()
            ->assertJson([
                'received' => 1,
                'inserted' => 1,
                'skipped' => 0,
            ]);

        $this->assertDatabaseHas('attendance_record', [
            'student_id' => null,
            'adviser_id' => $teacherId,
            'source' => 'scanner',
        ]);
    }

    public function test_existing_student_sync_payload_remains_supported_when_qr_is_disabled(): void
    {
        config()->set('school.qr_code_enabled', '0');

        $studentId = $this->createStudent('000123456789', 'STUDENT-CARD');

        $this->withHeader('X-API-AUTHCODE', self::TOKEN)
            ->postJson('/api/attendance/sync', [
                'records' => [[
                    'student_id' => $studentId,
                    'currentdate' => '2026-07-26',
                    'time' => '07:45:00',
                ]],
            ])
            ->assertOk()
            ->assertJson([
                'received' => 1,
                'inserted' => 1,
                'skipped' => 0,
            ]);

        $this->assertDatabaseHas('attendance_record', [
            'student_id' => $studentId,
            'adviser_id' => null,
            'source' => 'scanner',
        ]);
    }

    public function test_unassigned_card_is_rejected(): void
    {
        $this->withHeader('X-API-AUTHCODE', self::TOKEN)
            ->postJson('/api/attendance/rfid', [
                'rfid_card_uid' => 'UNKNOWN-CARD',
                'currentdate' => '2026-07-26',
                'time' => '08:00:00',
            ])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'RFID card is not assigned to a student, teacher, or staff member.',
            ]);
    }

    public function test_admin_rfid_checker_identifies_an_assigned_student(): void
    {
        $studentId = $this->createStudent('000123456789', 'CHECK-ME');
        request()->query->set('rfid_card_uid', 'check-me');

        $page = app(RfidChecker::class);
        $componentsMethod = new ReflectionMethod($page, 'components');
        $components = $componentsMethod->invoke($page);
        $html = (string) collect($components)->first();

        $this->assertStringContainsString('Card assigned', $html);
        $this->assertStringContainsString('Student', $html);
        $this->assertStringContainsString((string) $studentId, $html);
        $this->assertStringContainsString('000123456789', $html);
        $this->assertStringContainsString('Ana Santos', $html);
        $this->assertStringContainsString('View Student Record', $html);
        $this->assertMatchesRegularExpression(
            '/href="[^"]+'.preg_quote((string) $studentId, '/').'[^"]*"/',
            $html,
        );
    }

    public function test_one_card_cannot_be_assigned_to_both_a_student_and_teacher(): void
    {
        $this->createStudent('000123456789', 'SHARED-CARD');

        $this->expectException(ValidationException::class);

        Adviser::create([
            'name' => 'Teacher Maria',
            'rank' => 'Teacher I',
            'major' => 'English',
            'staff_type' => Adviser::TYPE_TEACHER,
            'rfid_card_uid' => 'shared-card',
        ]);
    }

    private function createStudent(string $lrn, string $uid): int
    {
        return DB::table('students')->insertGetId([
            'lrn' => $lrn,
            'rfid_card_uid' => strtoupper($uid),
            'lastname' => 'Santos',
            'firstname' => 'Ana',
            'middlename' => 'Reyes',
            'gender' => 'female',
        ]);
    }

    private function createTeacher(string $name, string $uid): int
    {
        return DB::table('advisers')->insertGetId([
            'name' => $name,
            'rank' => 'Teacher I',
            'major' => 'English',
            'staff_type' => Adviser::TYPE_TEACHER,
            'rfid_card_uid' => strtoupper($uid),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
