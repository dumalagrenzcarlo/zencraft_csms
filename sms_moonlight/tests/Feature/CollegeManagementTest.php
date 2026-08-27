<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Admin\CollegeExportController;
use App\Models\Adviser;
use App\Models\CollegeCourseOffering;
use App\Models\CollegeEnrollment;
use App\Models\CollegeEnrollmentCourse;
use App\Models\CollegeProgram;
use App\Models\CollegeProgramCourse;
use App\Models\Student;
use App\MoonShine\Resources\CollegeCourseOffering\CollegeCourseOfferingResource;
use App\MoonShine\Resources\CollegeEnrollment\CollegeEnrollmentResource;
use App\MoonShine\Resources\CollegeEnrollmentCourse\CollegeEnrollmentCourseResource;
use App\MoonShine\Resources\CollegeProgram\CollegeProgramResource;
use App\MoonShine\Resources\CollegeProgramCourse\CollegeProgramCourseResource;
use App\MoonShine\Resources\CollegeStudentQuick\CollegeStudentQuickResource;
use App\MoonShine\Resources\Instructor\InstructorResource;
use App\MoonShine\Resources\SchoolYear\SchoolYearResource;
use App\Support\CollegeEnrollmentCourseAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\TypeCasts\ModelDataWrapper;
use Tests\TestCase;

class CollegeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_college_schema_matches_the_program_year_semester_workbook_structure(): void
    {
        foreach ([
            'college_programs',
            'college_curriculum_subjects',
            'college_course_offerings',
            'college_enrollments',
            'college_enrollment_courses',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertFalse(Schema::hasTable('college_curricula'));
        $this->assertFalse(Schema::hasTable('college_terms'));
        $this->assertTrue(Schema::hasColumns('college_curriculum_subjects', [
            'program_id',
            'course_code',
            'description',
            'year_level',
            'semester',
            'units',
            'course_order',
        ]));
        $this->assertFalse(Schema::hasColumn('college_curriculum_subjects', 'curriculum_id'));
        $this->assertFalse(Schema::hasColumn('college_enrollments', 'curriculum_id'));
        $this->assertFalse(Schema::hasColumn('college_enrollments', 'term_id'));
        $this->assertTrue(Schema::hasColumns('college_enrollments', [
            'program_id',
            'school_year_id',
            'semester',
            'year_level',
        ]));
        $this->assertTrue(Schema::hasColumns('college_enrollment_courses', [
            'grades_submitted_at',
            'grades_submitted_by',
            'remarks',
        ]));
    }

    public function test_college_admin_only_exposes_the_simplified_resources(): void
    {
        foreach ([
            CollegeProgramResource::class,
            CollegeProgramCourseResource::class,
            CollegeCourseOfferingResource::class,
            CollegeEnrollmentResource::class,
            CollegeEnrollmentCourseResource::class,
        ] as $resourceClass) {
            $resource = app($resourceClass);

            $this->assertNotEmpty($resource->getPages()->toArray(), $resourceClass);
            $this->assertGreaterThan(0, $resource->getIndexFields()->count(), $resourceClass);
            $this->assertGreaterThan(0, $resource->getFormFields()->count(), $resourceClass);
        }

        $enrollmentColumns = app(CollegeEnrollmentResource::class)
            ->getFormFields()
            ->map(fn ($field) => $field->getColumn())
            ->values()
            ->all();

        $this->assertContains('school_year_id', $enrollmentColumns);
        $this->assertContains('semester', $enrollmentColumns);
        $this->assertNotContains('curriculum_id', $enrollmentColumns);
        $this->assertNotContains('term_id', $enrollmentColumns);
        $this->assertSame('school_year', app(SchoolYearResource::class)->getColumn());
    }

    public function test_college_instructor_index_shows_rfid_registration_actions(): void
    {
        $resource = app(InstructorResource::class);
        $buttonIcons = $resource->getIndexPage()
            ?->getButtons()
            ->map(static fn ($button): string => $button->getIconValue())
            ->all();

        $this->assertGreaterThanOrEqual(
            2,
            count(array_filter($buttonIcons, static fn (string $icon): bool => $icon === 'credit-card')),
        );
        $this->assertContains(
            'rfid_card_uid',
            collect($resource->indexFields())
                ->map(static fn ($field): string => $field->getColumn())
                ->all(),
        );
    }

    public function test_college_instructor_uses_concrete_form_and_detail_pages(): void
    {
        $resource = app(InstructorResource::class);

        $this->assertInstanceOf(
            \App\MoonShine\Resources\Adviser\Pages\AdviserFormPage::class,
            $resource->getFormPage(),
        );
        $this->assertInstanceOf(
            \App\MoonShine\Resources\Adviser\Pages\AdviserDetailPage::class,
            $resource->getDetailPage(),
        );
    }

    public function test_college_menu_separates_course_and_class_and_orders_the_setup_pages(): void
    {
        $layoutSource = file_get_contents(
            (new \ReflectionClass(\App\MoonShine\Layouts\CustomLayout::class))->getFileName()
        );
        $collegeMenuStart = strpos($layoutSource, "MenuGroup::make('College Management'");
        $collegeMenuEnd = strpos($layoutSource, ']),', $collegeMenuStart);
        $collegeMenu = substr($layoutSource, $collegeMenuStart, $collegeMenuEnd - $collegeMenuStart);

        $instructorsPosition = strpos($collegeMenu, "label: 'Instructors / Professors'");
        $enrollmentsPosition = strpos($collegeMenu, "label: 'Student Enrollments'");
        $coursePosition = strpos($collegeMenu, "CollegeProgramResource::class, label: 'Course'");
        $classPosition = strpos($collegeMenu, "CollegeProgramCourseResource::class, label: 'Class'");
        $schedulesPosition = strpos($collegeMenu, "CollegeCourseOfferingResource::class, label: 'Schedules'");
        $gradesPosition = strpos($collegeMenu, "CollegeEnrollmentCourseResource::class, label: 'Grades'");

        $this->assertNotFalse($instructorsPosition);
        $this->assertNotFalse($enrollmentsPosition);
        $this->assertNotFalse($coursePosition);
        $this->assertNotFalse($classPosition);
        $this->assertNotFalse($schedulesPosition);
        $this->assertNotFalse($gradesPosition);
        $this->assertGreaterThan($coursePosition, $classPosition);
        $this->assertGreaterThan($classPosition, $schedulesPosition);
        $this->assertGreaterThan($schedulesPosition, $gradesPosition);
        $this->assertGreaterThan($gradesPosition, $instructorsPosition);
        $this->assertGreaterThan($instructorsPosition, $enrollmentsPosition);
        $this->assertStringNotContainsString("label: 'Course and Class'", $collegeMenu);
    }

    public function test_college_resources_expose_filters_and_export_buttons(): void
    {
        $gradeResource = app(CollegeEnrollmentCourseResource::class);
        $scheduleResource = app(CollegeCourseOfferingResource::class);
        $courseResource = app(CollegeProgramResource::class);

        $this->assertSame(
            ['student_keyword', 'program_id', 'school_year_id', 'year_level', 'semester', 'offering_id', 'remarks'],
            collect((new \ReflectionMethod($gradeResource, 'filters'))->invoke($gradeResource))
                ->map(static fn ($field): string => $field->getColumn())->all(),
        );
        $this->assertSame(
            ['school_year_id', 'program_id', 'program_course_id', 'instructor_id', 'active'],
            collect((new \ReflectionMethod($scheduleResource, 'filters'))->invoke($scheduleResource))
                ->map(static fn ($field): string => $field->getColumn())->all(),
        );
        $this->assertSame(
            ['code', 'name', 'active'],
            collect((new \ReflectionMethod($courseResource, 'filters'))->invoke($courseResource))
                ->map(static fn ($field): string => $field->getColumn())->all(),
        );

        $this->assertStringContainsString(
            route('admin.college-grades.export'),
            collect((new \ReflectionMethod($gradeResource->getIndexPage(), 'topLeftButtons'))
                ->invoke($gradeResource->getIndexPage())->toArray())->last()->getUrl(),
        );
        $this->assertStringContainsString(
            route('admin.college-class-schedules.export'),
            collect((new \ReflectionMethod($scheduleResource->getIndexPage(), 'topLeftButtons'))
                ->invoke($scheduleResource->getIndexPage())->toArray())->last()->getUrl(),
        );
        $this->assertStringContainsString(
            route('admin.college-courses.export'),
            collect((new \ReflectionMethod($courseResource->getIndexPage(), 'topLeftButtons'))
                ->invoke($courseResource->getIndexPage())->toArray())->last()->getUrl(),
        );
    }

    public function test_college_exports_honor_the_current_filters(): void
    {
        $records = $this->createSharedRecords();
        $includedProgram = $this->createProgram('BSIT', 'Included Course');
        $includedClass = $this->createProgramCourse($includedProgram, 'INCLUDED 101');
        $excludedProgram = $this->createProgram('BSBA', 'Excluded Course');
        $this->createProgramCourse($excludedProgram, 'EXCLUDED 101');

        $enrollment = $this->createEnrollment($records, $includedProgram);
        $offering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $includedClass->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'INCLUDED-SECTION',
            'schedule' => 'M/W/F 8:00 AM',
            'room' => 'INCLUDED-ROOM',
            'capacity' => 40,
            'active' => true,
        ]);
        CollegeEnrollmentCourse::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('offering_id', $offering->id)
            ->update(['remarks' => 'Passed', 'final_grade' => 95]);

        $controller = app(CollegeExportController::class);
        $gradeCsv = $this->streamedContent($controller->grades(Request::create('/admin/college-grades/export', 'GET', [
            'filter' => ['remarks' => 'Passed'],
        ])));
        $scheduleCsv = $this->streamedContent($controller->schedules(Request::create('/admin/college-class-schedules/export', 'GET', [
            'filter' => ['program_id' => $includedProgram->id],
        ])));
        $courseCsv = $this->streamedContent($controller->courses(Request::create('/admin/college-courses/export', 'GET', [
            'filter' => ['code' => 'BSIT'],
        ])));

        $this->assertStringContainsString('COLLEGE-0001', $gradeCsv);
        $this->assertStringContainsString('INCLUDED 101', $gradeCsv);
        $this->assertStringContainsString('INCLUDED-SECTION', $scheduleCsv);
        $this->assertStringContainsString('Included Course', $courseCsv);
        $this->assertStringNotContainsString('BSBA', $courseCsv);
    }

    public function test_college_enrollments_can_be_filtered_by_academic_context_and_status(): void
    {
        $resource = app(CollegeEnrollmentResource::class);
        $filters = (new \ReflectionMethod($resource, 'filters'))->invoke($resource);

        $this->assertSame(
            ['student_id', 'program_id', 'school_year_id', 'semester', 'year_level', 'status'],
            collect($filters)->map(static fn ($field): string => $field->getColumn())->all(),
        );
    }

    public function test_course_class_includes_student_enrollments_and_grades(): void
    {
        $resource = app(CollegeCourseOfferingResource::class);

        $formField = $resource->getFormFields(withOutside: true)
            ->findByColumn('enrollmentCourses');
        $detailField = $resource->getDetailFields(withOutside: true)
            ->findByColumn('enrollmentCourses');

        $this->assertInstanceOf(HasMany::class, $formField);
        $this->assertInstanceOf(HasMany::class, $detailField);
        $this->assertTrue($formField->isCreatable());
    }

    public function test_class_schedule_page_reports_unscheduled_classes_and_form_relationships_are_searchable(): void
    {
        $records = $this->createSharedRecords();
        $program = $this->createProgram();
        $course = $this->createProgramCourse($program);
        $courseWithoutSchedule = $this->createProgramCourse(
            $program,
            code: 'PROG 102',
            description: 'Programming 2',
            order: 2
        );

        CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $course->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1A',
            'schedule' => null,
            'active' => true,
        ]);
        CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $course->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1B',
            'schedule' => 'M/W/F 8:00-9:00 AM',
            'active' => true,
        ]);

        $resource = app(CollegeCourseOfferingResource::class);
        $formFields = $resource->getFormFields();

        $this->assertTrue($formFields->findByColumn('program_course_id')->isSearchable());
        $this->assertTrue($formFields->findByColumn('instructor_id')->isSearchable());
        $this->assertTrue($formFields->findByColumn('program_course_id')->isAsyncSearch());
        $this->assertTrue($formFields->findByColumn('instructor_id')->isAsyncSearch());
        $this->assertSame(
            'Search by course, class code, year level, or semester',
            $formFields->findByColumn('program_course_id')->getAttribute('placeholder')
        );
        $this->assertSame(
            'Search instructor or professor by name, rank, or department',
            $formFields->findByColumn('instructor_id')->getAttribute('placeholder')
        );
        $this->assertSame(20, $formFields->findByColumn('program_course_id')->getAsyncSearchCount());
        $this->assertSame(20, $formFields->findByColumn('instructor_id')->getAsyncSearchCount());
        $courseSearch = $formFields->findByColumn('program_course_id')->getAsyncSearchQuery();
        $instructorSearch = $formFields->findByColumn('instructor_id')->getAsyncSearchQuery();

        $this->assertSame(
            [$course->id, $courseWithoutSchedule->id],
            $courseSearch(CollegeProgramCourse::query(), 'BSIT first year first semester')->pluck('id')->all()
        );
        $this->assertSame(
            [$records['instructor_id']],
            $instructorSearch(\App\Models\Instructor::query(), 'Ada Computer')->pluck('id')->all()
        );

        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]
        );
        $admin = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 1,
            'username' => 'async-schedule-admin',
            'email' => 'async-schedule-admin@example.test',
            'name' => 'Async Schedule Admin',
            'password' => Hash::make('password'),
        ]);
        $admin->save();

        $courseResponse = $this->actingAs($admin, 'moonshine')->getJson(route('moonshine.async-search', [
            'pageUri' => 'form-page',
            'resourceUri' => $resource->getUriKey(),
            '_relation' => 'programCourse',
            'query' => 'BSIT first year first semester',
        ]));
        $courseResponse
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['value' => (string) $course->id])
            ->assertJsonFragment(['value' => (string) $courseWithoutSchedule->id]);

        $instructorResponse = $this->getJson(route('moonshine.async-search', [
            'pageUri' => 'form-page',
            'resourceUri' => $resource->getUriKey(),
            '_relation' => 'instructor',
            'query' => 'Ada Computer',
        ]));
        $instructorResponse
            ->assertOk()
            ->assertJsonPath('0.value', (string) $records['instructor_id']);

        $this->assertSame(1, CollegeCourseOffering::query()->unscheduled()->count());

        $indexPage = $resource->getIndexPage();
        $mainLayer = (new \ReflectionMethod($indexPage, 'mainLayer'))->invoke($indexPage);
        $notice = (string) $mainLayer[0]->render();

        $this->assertStringContainsString('2 schedule assignments needed', $notice);
        $this->assertStringContainsString('Includes Classes with no schedule entry', $notice);
        $this->assertStringContainsString('schedule-coverage--warning', $notice);
        $this->assertStringContainsString('Review Classes', $notice);
    }

    public function test_new_class_continues_to_schedule_with_active_school_year_and_class_selected(): void
    {
        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]
        );
        $admin = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 1,
            'username' => 'schedule-flow-admin',
            'email' => 'schedule-flow-admin@example.test',
            'name' => 'Schedule Flow Admin',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $admin->save();
        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $program = $this->createProgram();
        $resource = app(CollegeProgramCourseResource::class);
        $response = $this->actingAs($admin, 'moonshine')
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('moonshine.crud.store', [
                'resourceUri' => $resource->getUriKey(),
            ]), [
                'program_id' => $program->id,
                'course_code' => 'PROG 101',
                'description' => 'Programming 1',
                'year_level' => 1,
                'semester' => 1,
                'units' => 3,
                'course_order' => 1,
                '_redirect' => 'the-parent-course-screen',
            ]);

        $response->assertCreated();
        $programCourse = CollegeProgramCourse::query()->where('course_code', 'PROG 101')->firstOrFail();
        $redirect = $response->json('redirect');
        $this->assertStringContainsString('college-course-offering-resource/form-page', $redirect);
        $this->assertStringContainsString('program_course_id='.$programCourse->id, $redirect);
        $this->assertStringContainsString('school_year_id='.$schoolYearId, $redirect);

        request()->query->replace([
            'program_course_id' => $programCourse->id,
            'school_year_id' => $schoolYearId,
        ]);
        $scheduleFields = app(CollegeCourseOfferingResource::class)->getFormFields();

        $this->assertSame(
            $schoolYearId,
            $scheduleFields->findByColumn('school_year_id')->getDefault()?->id
        );
        $this->assertSame(
            $programCourse->id,
            $scheduleFields->findByColumn('program_course_id')->getDefault()?->id
        );
    }

    public function test_program_courses_are_independent_from_high_school_subjects(): void
    {
        $program = $this->createProgram('BSPSY', 'Bachelor of Science in Psychology');
        $description = 'Practicum in Psychology (OJT) - 300 hours I. Clinical Setting - 200 hours II. - 100 hours';

        $course = $this->createProgramCourse(
            $program,
            code: 'PSY 206',
            description: $description,
            yearLevel: 4,
            units: 0
        );

        $this->assertSame('PSY 206', $course->course_code);
        $this->assertSame($description, $course->description);
        $this->assertSame('0.00', $course->units);
        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_course_codes_are_scoped_to_the_program_year_and_semester(): void
    {
        $bsit = $this->createProgram('BSIT', 'Bachelor of Science in Information Technology');
        $bscrim = $this->createProgram('BSCRIM', 'Bachelor of Science in Criminology');

        $bsitFirstYear = $this->createProgramCourse(
            $bsit,
            code: 'NSTP 1',
            description: 'National Service Training Program 1',
            yearLevel: 1,
        );
        $bscrimFirstYear = $this->createProgramCourse(
            $bscrim,
            code: 'NSTP 1',
            description: 'National Service Training Program 1',
            yearLevel: 1,
        );
        $bsitSecondYear = $this->createProgramCourse(
            $bsit,
            code: 'NSTP 1',
            description: 'National Service Training Program 1',
            yearLevel: 2,
        );

        $this->assertNotSame($bsitFirstYear->id, $bscrimFirstYear->id);
        $this->assertNotSame($bsitFirstYear->id, $bsitSecondYear->id);
        $this->assertSame(1, $bsitFirstYear->fresh()->year_level);
        $this->assertSame(1, $bscrimFirstYear->fresh()->year_level);
        $this->assertSame(2, $bsitSecondYear->fresh()->year_level);
        $this->assertSame('BSIT', $bsitFirstYear->fresh()->program->code);
        $this->assertSame('BSCRIM', $bscrimFirstYear->fresh()->program->code);
        $this->assertDatabaseCount('college_curriculum_subjects', 3);
    }

    public function test_duplicate_course_code_in_the_same_program_year_and_semester_is_rejected(): void
    {
        $program = $this->createProgram();
        $course = $this->createProgramCourse($program, code: 'NSTP 1');

        try {
            $this->createProgramCourse($program, code: '  nstp 1  ');
            $this->fail('A duplicate program course was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This class code already exists for the selected course, year level, and semester.',
                $exception->errors()['course_code'][0]
            );
        }

        $course->description = 'Updated NSTP description';
        $course->save();

        $this->assertSame('Updated NSTP description', $course->fresh()->description);
        $this->assertDatabaseCount('college_curriculum_subjects', 1);
    }

    public function test_program_course_labels_and_detail_filters_are_consistent(): void
    {
        $resource = app(CollegeProgramCourseResource::class);

        $this->assertSame(
            'Class Code',
            $resource->getIndexFields()->findByColumn('course_code')->getLabel()
        );
        $this->assertSame(
            'Class Code',
            $resource->getFormFields()->findByColumn('course_code')->getLabel()
        );
        $this->assertSame(
            'Select a course',
            $resource->getFormFields()->findByColumn('program_id')->getAttribute('placeholder')
        );
        $this->assertSame(
            'Select a year level',
            $resource->getFormFields()->findByColumn('year_level')->getAttribute('placeholder')
        );
        $this->assertSame(
            'Select a semester',
            $resource->getFormFields()->findByColumn('semester')->getAttribute('placeholder')
        );
        $this->assertSame(
            'Select a prerequisite class (optional)',
            $resource->getFormFields()
                ->findByColumn('prerequisite_program_course_id')
                ->getAttribute('placeholder')
        );
        $this->assertSame([
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
        ], CollegeProgramCourse::YEAR_LEVELS);

        $programResourceSource = file_get_contents(
            (new \ReflectionClass(CollegeProgramResource::class))->getFileName()
        );

        $this->assertStringContainsString("'data-college-course-filter' => 'year'", $programResourceSource);
        $this->assertStringContainsString("'data-college-course-filter' => 'semester'", $programResourceSource);
        $this->assertStringContainsString("class('college-course-filters items-end gap-3')", $programResourceSource);
        $this->assertStringContainsString("'data-college-course-row' => ''", $programResourceSource);

        $layoutSource = file_get_contents(
            (new \ReflectionClass(\App\MoonShine\Layouts\CustomLayout::class))->getFileName()
        );
        $this->assertStringNotContainsString('refreshAfterCollegeCourseModalClosesScript()', $layoutSource);
        $this->assertStringContainsString('classScheduleSubmitLabelScript()', $layoutSource);
        $this->assertStringContainsString('Save and Create Schedule', $layoutSource);
        $this->assertStringContainsString("form.querySelector('[name=\"_method\"]')", $layoutSource);
        $this->assertStringContainsString('.alert-error', $layoutSource);
        $this->assertStringContainsString('background-color: #b91c1c', $layoutSource);

        $layout = (new \ReflectionClass(\App\MoonShine\Layouts\CustomLayout::class))
            ->newInstanceWithoutConstructor();
        $themeOverrides = new \ReflectionMethod($layout, 'themeOverrides');
        $css = $themeOverrides->invoke($layout);

        $this->assertStringContainsString(
            '.js-table-builder-container .flex:has(> .college-course-filters)',
            $css
        );
        $this->assertStringContainsString(
            'grid-template-columns: repeat(3, minmax(0, 1fr));',
            $css
        );

        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $admin = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 1,
            'username' => 'college.admin',
            'email' => 'college.admin@example.test',
            'name' => 'College Admin',
            'password' => Hash::make('password'),
        ]);
        $admin->save();

        $program = $this->createProgram();
        $this->createProgramCourse($program);

        $this->actingAs($admin, 'moonshine')
            ->get(app(CollegeProgramResource::class)->getDetailPageUrl($program->id))
            ->assertOk()
            ->assertSee('All Years')
            ->assertSee('All Semesters')
            ->assertSee('data-college-course-row', false);
    }

    public function test_program_courses_are_ordered_by_year_semester_and_workbook_order(): void
    {
        $program = $this->createProgram();
        $this->createProgramCourse($program, code: 'FOURTH', yearLevel: 4, semester: 2, order: 1);
        $this->createProgramCourse($program, code: 'SECOND', yearLevel: 1, semester: 2, order: 1);
        $this->createProgramCourse($program, code: 'FIRST-B', yearLevel: 1, semester: 1, order: 2);
        $this->createProgramCourse($program, code: 'FIRST-A', yearLevel: 1, semester: 1, order: 1);

        $this->assertSame(
            ['FIRST-A', 'FIRST-B', 'SECOND', 'FOURTH'],
            $program->courses()->pluck('course_code')->all()
        );
    }

    public function test_course_class_uses_school_year_program_course_and_instructor(): void
    {
        $records = $this->createSharedRecords();
        $program = $this->createProgram();
        $course = $this->createProgramCourse($program);
        $offering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $course->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1A',
            'schedule' => 'M/W/F 8:00-9:00 AM',
            'room' => 'Lab 1',
            'capacity' => 40,
        ]);

        $this->assertSame($course->id, $offering->programCourse->id);
        $this->assertSame($records['school_year_id'], $offering->schoolYear->id);
        $this->assertSame(
            'BSIT - PROG 101 - Programming 1 - 1st Year - First Semester - BSIT-1A',
            $offering->display_name
        );

        $columns = app(CollegeCourseOfferingResource::class)
            ->getFormFields()
            ->map(fn ($field) => $field->getColumn())
            ->values()
            ->all();

        $this->assertContains('school_year_id', $columns);
        $this->assertContains('program_course_id', $columns);
        $this->assertContains('instructor_id', $columns);
        $this->assertNotContains('term_id', $columns);
        $this->assertNotContains('curriculum_subject_id', $columns);
        $this->assertInstanceOf(
            InstructorResource::class,
            app(CollegeCourseOfferingResource::class)
                ->getFormFields()
                ->findByColumn('instructor_id')
                ->getResource()
        );
    }

    public function test_course_class_rejects_a_high_school_teacher(): void
    {
        $records = $this->createSharedRecords();
        $teacher = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'High School Teacher',
            'rank' => 'Teacher I',
            'major' => 'Mathematics',
        ]));
        $program = $this->createProgram();
        $course = $this->createProgramCourse($program);

        $this->expectException(ValidationException::class);

        CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $course->id,
            'instructor_id' => $teacher->id,
            'section' => 'BSIT-1A',
        ]);
    }

    public function test_college_enrollment_uses_program_school_year_semester_and_year_level(): void
    {
        $records = $this->createSharedRecords();
        $program = $this->createProgram();
        $enrollment = CollegeEnrollment::create([
            'student_id' => $records['student_id'],
            'program_id' => $program->id,
            'school_year_id' => $records['school_year_id'],
            'semester' => 1,
            'year_level' => 1,
            'status' => 'enrolled',
        ]);

        $this->assertSame('BSIT', $enrollment->program->code);
        $this->assertSame('2026-2027', $enrollment->schoolYear->school_year);
        $this->assertStringContainsString('First Semester', $enrollment->display_name);
    }

    public function test_college_enrollment_is_assigned_to_matching_available_course_classes(): void
    {
        $records = $this->createSharedRecords();
        $program = $this->createProgram();
        $firstCourse = $this->createProgramCourse($program, code: 'PROG 101', order: 1);
        $secondCourse = $this->createProgramCourse($program, code: 'PROG 102', order: 2);
        $unscheduledCourse = $this->createProgramCourse($program, code: 'PROG 103', order: 3);

        $fullOffering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $firstCourse->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1A',
            'capacity' => 1,
        ]);
        $availableOffering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $firstCourse->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1B',
            'capacity' => 40,
        ]);
        $anotherAvailableOffering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $firstCourse->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1C',
            'capacity' => 40,
        ]);
        $secondOffering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $secondCourse->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1A',
            'capacity' => 40,
        ]);

        $otherStudentId = DB::table('students')->insertGetId([
            'lrn' => 'COLLEGE-FULL-001',
            'firstname' => 'Existing',
            'middlename' => 'Course',
            'lastname' => 'Student',
            'gender' => 'Male',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherEnrollment = CollegeEnrollment::create([
            'student_id' => $otherStudentId,
            'program_id' => $program->id,
            'school_year_id' => $records['school_year_id'],
            'semester' => 1,
            'year_level' => 1,
            'status' => 'enrolled',
        ]);
        CollegeEnrollmentCourse::create([
            'enrollment_id' => $otherEnrollment->id,
            'offering_id' => $fullOffering->id,
        ]);

        $enrollment = $this->createEnrollment($records, $program);
        $resource = app(CollegeEnrollmentResource::class);
        $afterCreated = new \ReflectionMethod($resource, 'afterCreated');
        $afterCreated->invoke($resource, new ModelDataWrapper($enrollment));

        $this->assertCount(3, $enrollment->courses()->get());
        $this->assertEqualsCanonicalizing(
            [$firstCourse->id, $secondCourse->id, $unscheduledCourse->id],
            $enrollment->courses()->pluck('program_course_id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$availableOffering->id, $secondOffering->id],
            $enrollment->courses()->pluck('offering_id')->filter()->all()
        );
        $this->assertFalse($enrollment->courses()->where('offering_id', $anotherAvailableOffering->id)->exists());

        $lateOffering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $unscheduledCourse->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1A',
            'capacity' => 40,
        ]);

        $this->assertSame(
            $lateOffering->id,
            $enrollment->courses()->where('program_course_id', $unscheduledCourse->id)->value('offering_id')
        );
        $this->assertSame(
            0,
            app(CollegeEnrollmentCourseAssigner::class)->assignAvailableCourses($enrollment)
        );
    }

    public function test_enrolled_course_must_match_school_year_program_year_and_semester(): void
    {
        $records = $this->createSharedRecords();
        $otherSchoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2027-2028',
            'active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $program = $this->createProgram();
        $course = $this->createProgramCourse($program);
        $offering = CollegeCourseOffering::create([
            'school_year_id' => $otherSchoolYearId,
            'program_course_id' => $course->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1A',
        ]);
        $enrollment = $this->createEnrollment($records, $program);

        $this->expectException(ValidationException::class);

        CollegeEnrollmentCourse::create([
            'enrollment_id' => $enrollment->id,
            'offering_id' => $offering->id,
        ]);
    }

    public function test_enrolled_course_rejects_another_program_or_year_or_semester(): void
    {
        $records = $this->createSharedRecords();
        $program = $this->createProgram();
        $otherProgram = $this->createProgram('BSTM', 'Bachelor of Science in Tourism Management');
        $enrollment = $this->createEnrollment($records, $program);

        foreach ([
            $this->createProgramCourse($otherProgram),
            $this->createProgramCourse($program, code: 'PROG 401', yearLevel: 4),
            $this->createProgramCourse($program, code: 'PROG 102', semester: 2),
        ] as $index => $course) {
            $offering = CollegeCourseOffering::create([
                'school_year_id' => $records['school_year_id'],
                'program_course_id' => $course->id,
                'instructor_id' => $records['instructor_id'],
                'section' => 'SECTION-'.$index,
            ]);

            try {
                CollegeEnrollmentCourse::create([
                    'enrollment_id' => $enrollment->id,
                    'offering_id' => $offering->id,
                ]);
                $this->fail('A mismatched course class was accepted.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_college_enrollment_allows_a_new_year_level_and_rejects_an_exact_duplicate(): void
    {
        $records = $this->createSharedRecords();
        $program = $this->createProgram();
        $firstYearEnrollment = $this->createEnrollment($records, $program);

        $secondYearClass = $this->createProgramCourse(
            $program,
            code: 'PROG 201',
            description: 'Programming 2',
            yearLevel: 2
        );
        $secondYearEnrollment = CollegeEnrollment::create([
            'student_id' => $records['student_id'],
            'program_id' => $program->id,
            'school_year_id' => $records['school_year_id'],
            'semester' => 1,
            'year_level' => 2,
            'status' => 'enrolled',
        ]);

        app(CollegeEnrollmentCourseAssigner::class)->assignAvailableCourses($secondYearEnrollment);

        $this->assertDatabaseHas('college_enrollments', [
            'id' => $secondYearEnrollment->id,
            'student_id' => $records['student_id'],
            'year_level' => 2,
            'status' => 'enrolled',
        ]);
        $this->assertDatabaseHas('college_enrollments', [
            'id' => $firstYearEnrollment->id,
            'year_level' => 1,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('college_enrollment_courses', [
            'enrollment_id' => $secondYearEnrollment->id,
            'program_course_id' => $secondYearClass->id,
        ]);

        try {
            CollegeEnrollment::create([
                'student_id' => $records['student_id'],
                'program_id' => $program->id,
                'school_year_id' => $records['school_year_id'],
                'semester' => 1,
                'year_level' => 2,
                'status' => 'enrolled',
            ]);
            $this->fail('A duplicate college enrollment was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This student is already enrolled for the selected school year, semester, and year level. Open the existing enrollment instead.',
                $exception->errors()['student_id'][0] ?? null
            );
        }
    }

    public function test_one_teacher_login_can_serve_high_school_and_college(): void
    {
        DB::table('moonshine_user_roles')->insert([
            [
                'id' => 2,
                'name' => 'Teacher',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Student',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $records = $this->createSharedRecords();
        DB::table('advisers')
            ->where('id', $records['instructor_id'])
            ->update([
                'staff_type' => Adviser::TYPE_TEACHER,
                'is_college_instructor' => true,
            ]);
        $student = Student::query()->findOrFail($records['student_id']);
        $studentUser = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 3,
            'username' => 'college.student',
            'email' => 'college.student@example.test',
            'name' => 'College Student',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $studentUser->save();
        $student->update(['user_id' => $studentUser->id]);

        $teacherUser = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 2,
            'username' => 'prof.ada',
            'email' => 'prof.ada@example.test',
            'name' => 'Prof. Ada Lovelace',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $teacherUser->save();
        DB::table('advisers')
            ->where('id', $records['instructor_id'])
            ->update(['user_id' => $teacherUser->id]);

        $program = $this->createProgram();
        $course = $this->createProgramCourse($program);
        $offering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $course->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1A',
            'schedule' => 'M/W/F 8:00-9:00 AM',
            'room' => 'Lab 1',
        ]);
        $enrollment = $this->createEnrollment($records, $program);
        $gradeRecord = CollegeEnrollmentCourse::create([
            'enrollment_id' => $enrollment->id,
            'offering_id' => $offering->id,
            'prelim_grade' => 88,
            'midterm_grade' => 89,
            'prefinal_grade' => 90,
            'final_grade' => 91,
            'remarks' => 'Passed',
            'grades_submitted_at' => now(),
            'grades_submitted_by' => $teacherUser->id,
        ]);

        $newClass = $this->createProgramCourse(
            $program,
            code: 'SUB 1',
            description: 'Newly Added Class',
            order: 2
        );

        $this->assertDatabaseHas('college_enrollment_courses', [
            'enrollment_id' => $enrollment->id,
            'program_course_id' => $newClass->id,
            'offering_id' => null,
        ]);

        $historyClass = $this->createProgramCourse(
            $program,
            code: 'PROG 201',
            description: 'Historical Programming Class',
            yearLevel: 2,
            order: 1
        );
        $historyEnrollment = CollegeEnrollment::create([
            'student_id' => $student->id,
            'program_id' => $program->id,
            'school_year_id' => $records['school_year_id'],
            'semester' => 1,
            'year_level' => 2,
            'status' => 'completed',
        ]);
        CollegeEnrollmentCourse::create([
            'enrollment_id' => $historyEnrollment->id,
            'program_course_id' => $historyClass->id,
            'prelim_grade' => 82,
            'midterm_grade' => 83,
            'prefinal_grade' => 84,
            'final_grade' => 85,
            'remarks' => 'Passed',
            'grades_submitted_at' => now(),
            'grades_submitted_by' => $teacherUser->id,
        ]);

        $this->actingAs($studentUser, 'moonshine')
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Academic dashboard')
            ->assertSee('Your Classes')
            ->assertSee('School Updates')
            ->assertSee('Recent Grades')
            ->assertSee('PROG 101')
            ->assertSee('SUB 1')
            ->assertSee('Newly Added Class')
            ->assertSee('91.00');

        $this->get(route('student.dashboard', [
            'tab' => 'history',
            'history_enrollment' => $historyEnrollment->id,
        ]))
            ->assertOk()
            ->assertSee('Class History')
            ->assertSee('Historical Programming Class')
            ->assertSee('2nd Year')
            ->assertSee('Completed')
            ->assertSee('85.00')
            ->assertSee('Passed');

        $this->actingAs($studentUser, 'moonshine')
            ->get(route('student.dashboard', ['tab' => 'class']))
            ->assertOk()
            ->assertSee('PROG 101')
            ->assertSee('Programming 1')
            ->assertSee('SUB 1')
            ->assertSee('Newly Added Class')
            ->assertSee('BSIT-1A')
            ->assertSee('Class')
            ->assertSee('View Grades')
            ->assertSee('<table', false)
            ->assertDontSee('Courses');

        $this->get(route('student.college-classes.grades.modal', $gradeRecord))
            ->assertOk()
            ->assertSee('PROG 101')
            ->assertSee('Grades released')
            ->assertSee('88.00')
            ->assertSee('91.00')
            ->assertSee('Passed');

        $otherStudentUser = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 3,
            'username' => 'other.college.student',
            'email' => 'other.college.student@example.test',
            'name' => 'Other College Student',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $otherStudentUser->save();
        Student::create([
            'user_id' => $otherStudentUser->id,
            'lrn' => 'COLLEGE-0002',
            'lastname' => 'Other',
            'firstname' => 'Student',
            'middlename' => '',
            'gender' => 'Male',
        ]);

        $this->actingAs($otherStudentUser, 'moonshine')
            ->get(route('student.college-classes.grades.modal', $gradeRecord))
            ->assertNotFound();

        $this->actingAs($studentUser, 'moonshine')
            ->get(route('student.dashboard', [
                'tab' => 'class',
                'class_search' => 'Programming 1',
            ]))
            ->assertOk()
            ->assertSee('PROG 101')
            ->assertSee('Programming 1');

        $this->get(route('student.dashboard', [
            'tab' => 'class',
            'class_search' => 'No matching class',
        ]))
            ->assertOk()
            ->assertSee('No enrolled classes')
            ->assertDontSee('PROG 101');

        $this->actingAs($teacherUser, 'moonshine')
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('PROG 101')
            ->assertSee('Programming 1')
            ->assertSee('1st Year');

        $this->assertDatabaseCount('advisers', 1);
        $this->assertSame(
            [$records['instructor_id']],
            app(InstructorResource::class)->getQuery()->pluck('id')->all()
        );
    }

    public function test_instructor_can_save_submit_and_then_no_longer_edit_college_grades(): void
    {
        DB::table('moonshine_user_roles')->insert([
            'id' => 2,
            'name' => 'Teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $records = $this->createSharedRecords();
        $teacherUser = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 2,
            'username' => 'prof.grades',
            'email' => 'prof.grades@example.test',
            'name' => 'Prof. Grades',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $teacherUser->save();
        DB::table('advisers')
            ->where('id', $records['instructor_id'])
            ->update(['user_id' => $teacherUser->id]);

        $program = $this->createProgram();
        $course = $this->createProgramCourse($program);
        $offering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $course->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1A',
        ]);
        $enrollment = $this->createEnrollment($records, $program);
        $gradeRecord = CollegeEnrollmentCourse::create([
            'enrollment_id' => $enrollment->id,
            'offering_id' => $offering->id,
        ]);
        DB::table('attendance_record')->insert([
            'student_id' => $records['student_id'],
            'amlogin' => '07:45:00',
            'amlogout' => '12:00:00',
            'pmlogin' => '13:00:00',
            'pmlogout' => '17:00:00',
            'currentdate' => now()->toDateString(),
            'logged_time' => '07:45:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacherUser, 'moonshine')
            ->get(route('teacher.dashboard', ['tab' => 'college-grades']))
            ->assertOk()
            ->assertSee('College Gradebook')
            ->assertSee('Select a class to view its students')
            ->assertDontSee('Manage Grades');

        $this->get(route('teacher.dashboard', [
            'context' => 'instructor',
            'tab' => 'attendance',
            'college_class_id' => $offering->id,
            'attendance_search' => 'Juan Cruz',
        ]))
            ->assertOk()
            ->assertSee('Attendance')
            ->assertSee('College class')
            ->assertSee('COLLEGE-0001')
            ->assertSee('DELA CRUZ')
            ->assertSee('07:45 AM')
            ->assertSee('attendance-search-button', false)
            ->assertSee('h-11 min-w-[96px] self-end justify-self-end', false);

        $this->get(route('teacher.dashboard', [
            'context' => 'instructor',
            'tab' => 'college-grades',
            'college_class_id' => $offering->id,
        ]))
            ->assertOk()
            ->assertSee('COLLEGE-0001')
            ->assertSee('1 of 1 student')
            ->assertSee('Manage Grades');

        $this->get(route('teacher.dashboard', ['context' => 'instructor']))
            ->assertOk()
            ->assertSee('college_class_id='.$offering->id, false);

        $this->get(route('teacher.dashboard', [
            'context' => 'instructor',
            'tab' => 'college-grades',
            'college_class_id' => $offering->id,
            'college_grade_search' => 'No matching student',
        ]))
            ->assertOk()
            ->assertSee('No students match the current search and status filters.')
            ->assertSee('0 of 1 student');

        $this->get(route('teacher.dashboard', [
            'context' => 'instructor',
            'tab' => 'college-grades',
            'college_class_id' => $offering->id,
            'college_grade_status' => 'submitted',
        ]))
            ->assertOk()
            ->assertSee('No students match the current search and status filters.')
            ->assertSee('0 of 1 student');

        $this->get(route('teacher.college-grades.modal', $gradeRecord))
            ->assertOk()
            ->assertSee('Pre-final')
            ->assertSee('Remarks')
            ->assertSee('Submit Final Grades');

        $this->post(route('teacher.college-grades.save', $gradeRecord), [
            'prelim_grade' => 89,
            'remarks' => 'Incomplete',
            'action' => 'save',
        ])
            ->assertRedirect(route('teacher.dashboard', [
                'context' => 'instructor',
                'tab' => 'college-grades',
                'college_class_id' => $offering->id,
            ]))
            ->assertSessionHas('success', 'College grades saved successfully.');

        $gradeRecord->refresh();
        $this->assertSame('89.00', $gradeRecord->prelim_grade);
        $this->assertSame('Incomplete', $gradeRecord->remarks);
        $this->assertFalse($gradeRecord->gradesAreSubmitted());

        $this->post(route('teacher.college-grades.save', $gradeRecord), [
            'prelim_grade' => 89,
            'midterm_grade' => 90,
            'prefinal_grade' => 91,
            'remarks' => 'Passed',
            'action' => 'submit',
        ])->assertSessionHasErrors('final_grade');

        $this->assertFalse($gradeRecord->fresh()->gradesAreSubmitted());

        $this->post(route('teacher.college-grades.save', $gradeRecord), [
            'prelim_grade' => 89,
            'midterm_grade' => 90,
            'prefinal_grade' => 91,
            'final_grade' => 92,
            'remarks' => 'Passed',
            'action' => 'submit',
        ])
            ->assertRedirect(route('teacher.dashboard', [
                'context' => 'instructor',
                'tab' => 'college-grades',
                'college_class_id' => $offering->id,
            ]))
            ->assertSessionHas('success', 'College grades submitted successfully.');

        $submittedRecord = $gradeRecord->fresh();
        $this->assertTrue($submittedRecord->gradesAreSubmitted());
        $this->assertSame($teacherUser->id, $submittedRecord->grades_submitted_by);
        $this->assertSame('Passed', $submittedRecord->remarks);

        $this->post(route('teacher.college-grades.save', $gradeRecord), [
            'prelim_grade' => 50,
            'midterm_grade' => 50,
            'prefinal_grade' => 50,
            'final_grade' => 50,
            'remarks' => 'Failed',
            'action' => 'save',
        ])->assertSessionHasErrors('grades');

        $lockedRecord = $gradeRecord->fresh();
        $this->assertSame('89.00', $lockedRecord->prelim_grade);
        $this->assertSame('Passed', $lockedRecord->remarks);
    }

    public function test_instructor_cannot_manage_another_instructors_college_grades(): void
    {
        DB::table('moonshine_user_roles')->insert([
            'id' => 2,
            'name' => 'Teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $records = $this->createSharedRecords();
        $program = $this->createProgram();
        $course = $this->createProgramCourse($program);
        $offering = CollegeCourseOffering::create([
            'school_year_id' => $records['school_year_id'],
            'program_course_id' => $course->id,
            'instructor_id' => $records['instructor_id'],
            'section' => 'BSIT-1A',
        ]);
        $enrollment = $this->createEnrollment($records, $program);
        $gradeRecord = CollegeEnrollmentCourse::create([
            'enrollment_id' => $enrollment->id,
            'offering_id' => $offering->id,
        ]);

        $otherUser = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 2,
            'username' => 'prof.other',
            'email' => 'prof.other@example.test',
            'name' => 'Other Instructor',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $otherUser->save();
        Adviser::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Instructor',
            'rank' => 'Instructor',
            'major' => 'Computer Science',
            'staff_type' => Adviser::TYPE_INSTRUCTOR,
        ]);

        $this->actingAs($otherUser, 'moonshine')
            ->get(route('teacher.college-grades.modal', $gradeRecord))
            ->assertNotFound();

        $this->post(route('teacher.college-grades.save', $gradeRecord), [
            'prelim_grade' => 95,
            'action' => 'save',
        ])->assertNotFound();

        $this->assertNull($gradeRecord->fresh()->prelim_grade);
    }

    public function test_quick_add_student_remains_available_from_enrollment(): void
    {
        $studentField = app(CollegeEnrollmentResource::class)
            ->getFormFields()
            ->findByColumn('student_id');

        $this->assertTrue($studentField->isCreatable());
        $this->assertInstanceOf(CollegeStudentQuickResource::class, $studentField->getResource());
    }

    public function test_dual_role_teacher_switches_between_separate_adviser_and_instructor_workspaces(): void
    {
        DB::table('moonshine_user_roles')->insert([
            'id' => 2,
            'name' => 'Teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 2,
            'username' => 'dual.role',
            'email' => 'dual.role@example.test',
            'name' => 'Dual Role Teacher',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $user->save();

        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $gradeId = DB::table('grade')->insertGetId([
            'grade' => 'Grade 10',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $teacherId = DB::table('advisers')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Dual Role Teacher',
            'rank' => 'Teacher III',
            'major' => 'Mathematics',
            'staff_type' => Adviser::TYPE_TEACHER,
            'is_college_instructor' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('classes')->insert([
            'adviser_id' => $teacherId,
            'grade_id' => $gradeId,
            'section' => 'A',
            'school_year_id' => $schoolYearId,
            'status' => 'active',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $course = $this->createProgramCourse($this->createProgram());
        CollegeCourseOffering::create([
            'school_year_id' => $schoolYearId,
            'program_course_id' => $course->id,
            'instructor_id' => $teacherId,
            'section' => 'BSIT-1A',
            'schedule' => 'M/W/F 8:00-9:00 AM',
            'room' => 'Lab 1',
        ]);

        $this->actingAs($user, 'moonshine')
            ->get(route('teacher.dashboard', ['context' => 'adviser']))
            ->assertOk()
            ->assertSee('Class Adviser')
            ->assertSee('Students')
            ->assertSee('Assignments and Activities')
            ->assertDontSee('College Gradebook');

        $this->get(route('teacher.dashboard', ['context' => 'instructor']))
            ->assertOk()
            ->assertSee('College Instructor')
            ->assertSee('Assigned Classes')
            ->assertSee('PROG 101')
            ->assertSee('College Grades')
            ->assertDontSee('Assignments and Activities');

        $this->get(route('teacher.dashboard', [
            'context' => 'adviser',
            'tab' => 'college-grades',
        ]))
            ->assertOk()
            ->assertDontSee('College Gradebook');
    }

    private function createProgram(
        string $code = 'BSIT',
        string $name = 'Bachelor of Science in Information Technology'
    ): CollegeProgram {
        return CollegeProgram::create([
            'code' => $code,
            'name' => $name,
            'duration_years' => 4,
            'active' => true,
        ]);
    }

    private function streamedContent(\Symfony\Component\HttpFoundation\StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    private function createProgramCourse(
        CollegeProgram $program,
        string $code = 'PROG 101',
        string $description = 'Programming 1',
        int $yearLevel = 1,
        int $semester = 1,
        float $units = 3,
        int $order = 1
    ): CollegeProgramCourse {
        return $program->courses()->create([
            'course_code' => $code,
            'description' => $description,
            'year_level' => $yearLevel,
            'semester' => $semester,
            'units' => $units,
            'course_order' => $order,
        ]);
    }

    /**
     * @param  array{school_year_id: int, instructor_id: int, student_id: int}  $records
     */
    private function createEnrollment(array $records, CollegeProgram $program): CollegeEnrollment
    {
        return CollegeEnrollment::create([
            'student_id' => $records['student_id'],
            'program_id' => $program->id,
            'school_year_id' => $records['school_year_id'],
            'semester' => 1,
            'year_level' => 1,
            'status' => 'enrolled',
        ]);
    }

    /**
     * @return array{school_year_id: int, instructor_id: int, student_id: int}
     */
    private function createSharedRecords(): array
    {
        $now = now();
        $schoolYearId = DB::table('school_year')->insertGetId([
            'school_year' => '2026-2027',
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $instructorId = DB::table('advisers')->insertGetId([
            'user_id' => null,
            'name' => 'Prof. Ada Lovelace',
            'rank' => 'Instructor',
            'major' => 'Computer Science',
            'staff_type' => Adviser::TYPE_INSTRUCTOR,
            'profile_photo' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $studentId = DB::table('students')->insertGetId([
            'user_id' => null,
            'lrn' => 'COLLEGE-0001',
            'lastname' => 'Dela Cruz',
            'firstname' => 'Juan',
            'middlename' => 'Santos',
            'gender' => 'Male',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'school_year_id' => $schoolYearId,
            'instructor_id' => $instructorId,
            'student_id' => $studentId,
        ];
    }
}
