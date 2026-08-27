<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Admin\CollegeEnrollmentImportController;
use App\Imports\CollegeEnrollmentImport;
use App\Models\CollegeEnrollment;
use App\Models\CollegeProgram;
use App\MoonShine\Layouts\CustomLayout;
use App\MoonShine\Resources\CollegeEnrollment\CollegeEnrollmentResource;
use App\MoonShine\Resources\CollegeEnrollment\Pages\CollegeEnrollmentIndexPage;
use App\Support\CollegeEnrollmentCourseAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class CollegeEnrollmentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_using_the_active_school_year_and_updates_a_reimport(): void
    {
        $activeSchoolYearId = $this->createSchoolYear('2026-2027', true);
        $inactiveSchoolYearId = $this->createSchoolYear('2025-2026', false);
        $studentId = $this->createStudent('000012345678901');
        $program = $this->createProgram();
        $programCourse = $program->courses()->create([
            'course_code' => 'IT 101',
            'description' => 'Introduction to Computing',
            'year_level' => 1,
            'semester' => 1,
            'units' => 3,
            'course_order' => 1,
        ]);
        $import = $this->makeImport();

        $import->collection($this->rows([
            ['000012345678901', 'Optional Name', 'bsit', '2025-2026', 'First Semester', 1, 'Enrolled'],
        ]));

        $this->assertSame(1, $import->created);
        $this->assertSame(0, $import->updated);
        $this->assertSame(1, $import->totalRows);
        $this->assertSame(0, $import->errorRows);
        $this->assertSame(0, $import->duplicateRows);
        $this->assertDatabaseHas('college_enrollments', [
            'student_id' => $studentId,
            'program_id' => $program->id,
            'school_year_id' => $activeSchoolYearId,
            'semester' => 1,
            'year_level' => 1,
            'status' => 'enrolled',
        ]);
        $this->assertDatabaseMissing('college_enrollments', ['school_year_id' => $inactiveSchoolYearId]);
        $this->assertDatabaseHas('college_enrollment_courses', [
            'program_course_id' => $programCourse->id,
            'offering_id' => null,
        ]);

        $reimport = $this->makeImport();
        $reimport->collection($this->rows([
            ['000012345678901', '', 'BSIT', '', 1, 1, 'Pending'],
        ]));

        $this->assertSame(0, $reimport->created);
        $this->assertSame(1, $reimport->updated);
        $this->assertSame(1, CollegeEnrollment::query()->count());
        $this->assertDatabaseCount('college_enrollment_courses', 1);
        $this->assertDatabaseHas('college_enrollments', [
            'student_id' => $studentId,
            'status' => 'pending',
        ]);
    }

    public function test_invalid_rows_are_reported_and_the_entire_import_is_rolled_back(): void
    {
        $this->createSchoolYear('2026-2027', true);
        $this->createStudent('COLLEGE-0001');
        $this->createProgram();
        $import = $this->makeImport();

        try {
            $import->collection($this->rows([
                ['COLLEGE-0001', '', 'BSIT', '', 2, 2, 'Completed'],
                ['UNKNOWN', '', 'INVALID', '', 'Summer', 9, 'Active'],
            ]));
            $this->fail('The invalid spreadsheet was accepted.');
        } catch (ValidationException $exception) {
            $messages = implode(' ', $exception->errors()['file']);
            $this->assertStringContainsString('Rows processed: 2. Imported: 0. Error rows: 1.', $messages);
            $this->assertStringContainsString('Row 3', $messages);
            $this->assertStringContainsString('does not match an existing student', $messages);
            $this->assertStringContainsString('Course Code INVALID was not found', $messages);
            $this->assertStringContainsString('Semester must be', $messages);
            $this->assertStringContainsString('Status must be', $messages);
        }

        $this->assertDatabaseCount('college_enrollments', 0);
    }

    public function test_duplicate_spreadsheet_rows_are_counted_in_the_failure_summary(): void
    {
        $this->createSchoolYear('2026-2027', true);
        $this->createStudent('COLLEGE-0001');
        $this->createProgram();
        $import = $this->makeImport();
        $duplicateRow = ['COLLEGE-0001', '', 'BSIT', '', 1, 1, 'Enrolled'];

        try {
            $import->collection($this->rows([$duplicateRow, $duplicateRow]));
            $this->fail('The duplicate spreadsheet rows were accepted.');
        } catch (ValidationException $exception) {
            $messages = implode(' ', $exception->errors()['file']);
            $this->assertStringContainsString('Rows processed: 2. Imported: 0. Error rows: 1. Duplicate rows in file: 1.', $messages);
            $this->assertStringContainsString('Row 3: duplicates spreadsheet row 2', $messages);
        }

        $this->assertSame(2, $import->totalRows);
        $this->assertSame(1, $import->errorRows);
        $this->assertSame(1, $import->duplicateRows);
        $this->assertDatabaseCount('college_enrollments', 0);
    }

    public function test_student_and_course_lookups_are_batched_for_large_files(): void
    {
        $this->createSchoolYear('2026-2027', true);
        $this->createProgram();
        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });
        $rows = collect(range(1, 50))
            ->map(static fn (int $number): array => [
                'UNKNOWN-'.$number,
                '',
                'BSIT',
                '',
                1,
                1,
                'Enrolled',
            ])
            ->all();

        try {
            $this->makeImport()->collection($this->rows($rows));
            $this->fail('The unknown students were accepted.');
        } catch (ValidationException) {
            $this->assertLessThanOrEqual(3, $queryCount);
        }
    }

    public function test_an_active_school_year_is_required(): void
    {
        $this->createSchoolYear('2025-2026', false);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No active school year is configured');

        $this->makeImport()->collection($this->rows([]));
    }

    public function test_the_instructions_worksheet_is_not_processed_as_import_data(): void
    {
        $activeSchoolYearId = $this->createSchoolYear('2026-2027', true);
        $studentId = $this->createStudent('COLLEGE-0001');
        $program = $this->createProgram();
        $workbook = new Spreadsheet;
        $workbook->getActiveSheet()->setTitle('College Enrolments')->fromArray([
            ['LRN', 'Student Name', 'Course Code', 'School Year', 'Semester', 'Year Level', 'Status'],
            ['COLLEGE-0001', '', 'BSIT', '2026-2027', 'First Semester', 1, 'Enrolled'],
        ]);
        $workbook->createSheet()->setTitle('Instructions')->fromArray([
            ['Field', 'Expected value'],
            ['LRN', 'Must match an existing student.'],
        ]);
        $temporaryFile = tmpfile();
        $temporaryPath = stream_get_meta_data($temporaryFile)['uri'];
        (new Xlsx($workbook))->save($temporaryPath);

        $import = $this->makeImport();
        Excel::import($import, $temporaryPath);

        $this->assertSame(1, $import->created);
        $this->assertDatabaseHas('college_enrollments', [
            'student_id' => $studentId,
            'program_id' => $program->id,
            'school_year_id' => $activeSchoolYearId,
        ]);

        $workbook->disconnectWorksheets();
        fclose($temporaryFile);
    }

    public function test_the_downloadable_template_contains_the_fields_active_year_and_instructions(): void
    {
        $this->createSchoolYear('2026-2027', true);
        $this->createProgram();
        $response = app(CollegeEnrollmentImportController::class)->template();

        ob_start();
        $response->sendContent();
        $contents = ob_get_clean();

        $temporaryFile = tmpfile();
        $temporaryPath = stream_get_meta_data($temporaryFile)['uri'];
        file_put_contents($temporaryPath, $contents);
        $workbook = IOFactory::load($temporaryPath);

        $this->assertSame(
            ['LRN', 'Student Name', 'Course Code', 'School Year', 'Semester', 'Year Level', 'Status'],
            $workbook->getSheetByName('College Enrolments')->rangeToArray('A1:G1')[0],
        );
        $this->assertSame(2, $workbook->getSheetByName('College Enrolments')->getHighestDataRow());
        $this->assertNull($workbook->getSheetByName('College Enrolments')->getCell('D2')->getValue());
        $this->assertSame('Instructions', $workbook->getSheet(1)->getTitle());
        $this->assertStringContainsString(
            'active in the student portal and assigned to matching classes',
            $workbook->getSheetByName('Instructions')->getCell('B8')->getValue(),
        );
        $this->assertStringContainsString(
            'Enrolled,Pending,Completed,Withdrawn',
            $workbook->getSheetByName('College Enrolments')->getDataValidation('G2')->getFormula1(),
        );

        $workbook->disconnectWorksheets();
        fclose($temporaryFile);
    }

    public function test_the_import_modal_stays_open_and_notifications_use_five_seconds(): void
    {
        $pageSource = file_get_contents((new ReflectionClass(CollegeEnrollmentIndexPage::class))->getFileName());
        $this->assertStringContainsString('->closeOutside(false)', $pageSource);
        $this->assertStringContainsString('->autoClose(false)', $pageSource);

        $layout = (new ReflectionClass(CustomLayout::class))->newInstanceWithoutConstructor();
        $script = (new ReflectionMethod($layout, 'notificationDurationScript'))->invoke($layout);

        $this->assertStringContainsString('setToastDuration(5000)', $script);
        $this->assertStringContainsString("document.addEventListener('moonshine:init'", $script);

        $themeCss = (new ReflectionMethod($layout, 'themeOverrides'))->invoke($layout);
        $this->assertStringContainsString('background-color: rgb(0 0 0 / 55%) !important;', $themeCss);
        $this->assertStringContainsString('.modal-content {', $themeCss);
        $this->assertStringContainsString('opacity: 1;', $themeCss);
    }

    public function test_the_enrolment_form_status_field_opens_the_shared_explanation_modal(): void
    {
        $statusField = app(CollegeEnrollmentResource::class)
            ->getFormFields()
            ->findByColumn('status');
        $label = $statusField->getLabel();

        $this->assertSame('', $statusField->getHint());
        $this->assertStringContainsString('<span>Status</span>', $label);
        $this->assertStringContainsString('<span class="required">*</span>', $label);
        $this->assertStringContainsString('Open enrolment status explanation', $label);
        $this->assertStringContainsString('icon-wrapper', $label);
        $this->assertStringContainsString('Open status explanation', $label);
        $this->assertStringContainsString('Enrolment Status Guide', $label);
        $this->assertStringContainsString('automatically assigned to matching classes', $label);
        $this->assertStringContainsString('modal_toggled', $label);
    }

    private function makeImport(): CollegeEnrollmentImport
    {
        return new CollegeEnrollmentImport(app(CollegeEnrollmentCourseAssigner::class));
    }

    private function rows(array $dataRows): Collection
    {
        return collect([
            collect(['LRN', 'Student Name', 'Course Code', 'School Year', 'Semester', 'Year Level', 'Status']),
            ...array_map(static fn (array $row): Collection => collect($row), $dataRows),
        ]);
    }

    private function createSchoolYear(string $name, bool $active): int
    {
        return (int) DB::table('school_year')->insertGetId([
            'school_year' => $name,
            'active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createStudent(string $lrn): int
    {
        return (int) DB::table('students')->insertGetId([
            'user_id' => null,
            'lrn' => $lrn,
            'lastname' => 'Dela Cruz',
            'firstname' => 'Juan',
            'middlename' => 'Santos',
            'gender' => 'Male',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createProgram(): CollegeProgram
    {
        return CollegeProgram::create([
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'duration_years' => 4,
            'active' => true,
        ]);
    }
}
