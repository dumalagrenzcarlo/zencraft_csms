<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Adviser;
use App\Models\ClassesModel;
use App\Models\Grade;
use App\Models\Instructor;
use App\Models\SchoolYear;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\MoonShine\Resources\ClassesModel\ClassesModelResource;
use App\MoonShine\Resources\Instructor\InstructorResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Models\MoonshineUser;
use Tests\TestCase;

class ClassAdviserAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_and_instructor_modules_share_the_table_but_show_separate_staff(): void
    {
        $teacher = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'High School Teacher',
            'rank' => 'Teacher I',
            'major' => 'English',
            'is_college_instructor' => true,
        ]));
        $instructor = Instructor::withoutEvents(fn () => Instructor::query()->create([
            'name' => 'College Professor',
            'rank' => 'Professor',
            'major' => 'Computer Science',
        ]));

        $this->assertSame('advisers', $teacher->getTable());
        $this->assertSame('advisers', $instructor->getTable());
        $this->assertSame(Adviser::TYPE_TEACHER, $teacher->staff_type);
        $this->assertSame(Adviser::TYPE_INSTRUCTOR, $instructor->staff_type);
        $this->assertSame([$teacher->id], app(AdviserResource::class)->getQuery()->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$teacher->id, $instructor->id],
            app(InstructorResource::class)->getQuery()->pluck('id')->all()
        );
        $this->assertTrue($teacher->isCollegeInstructor());
        $this->assertNotNull(Instructor::query()->find($teacher->id));
    }

    public function test_high_school_class_rejects_a_college_instructor(): void
    {
        $schoolYear = SchoolYear::query()->create([
            'school_year' => '2026-2027',
            'active' => true,
        ]);
        $grade = Grade::query()->create([
            'grade' => 'Grade 7',
            'status' => 'active',
        ]);
        $instructor = Instructor::withoutEvents(fn () => Instructor::query()->create([
            'name' => 'College Professor',
            'rank' => 'Professor',
            'major' => 'Computer Science',
        ]));

        $this->expectException(ValidationException::class);

        ClassesModel::query()->create([
            'adviser_id' => $instructor->id,
            'grade_id' => $grade->id,
            'section' => 'A',
            'school_year_id' => $schoolYear->id,
        ]);
    }

    public function test_adviser_with_a_class_remains_available_for_additional_classes(): void
    {
        $currentYear = SchoolYear::query()->create([
            'school_year' => '2026-2027',
            'active' => true,
        ]);
        $otherYear = SchoolYear::query()->create([
            'school_year' => '2025-2026',
            'active' => false,
        ]);
        $grade = Grade::query()->create([
            'grade' => 'Grade 7',
            'status' => 'active',
        ]);
        $assigned = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'Assigned Teacher',
            'rank' => 'Teacher I',
            'major' => 'English',
        ]));
        $available = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'Available Teacher',
            'rank' => 'Teacher II',
            'major' => 'Mathematics',
        ]));

        $class = ClassesModel::query()->create([
            'adviser_id' => $assigned->id,
            'grade_id' => $grade->id,
            'section' => 'A',
            'school_year_id' => $currentYear->id,
        ]);

        $currentYearIds = Adviser::query()
            ->availableForSchoolYear($currentYear->id)
            ->pluck('id');
        $otherYearIds = Adviser::query()
            ->availableForSchoolYear($otherYear->id)
            ->pluck('id');
        $editIds = Adviser::query()
            ->availableForSchoolYear($currentYear->id, $class->id)
            ->pluck('id');

        $this->assertContains($assigned->id, $currentYearIds);
        $this->assertContains($available->id, $currentYearIds);
        $this->assertContains($assigned->id, $otherYearIds);
        $this->assertContains($assigned->id, $editIds);

        $adviserField = app(ClassesModelResource::class)
            ->getFormFields()
            ->onlyFields(withApplyWrappers: true)
            ->findByColumn('adviser_id');

        $this->assertInstanceOf(BelongsTo::class, $adviserField);
        $this->assertTrue($adviserField->isSearchable());
        $this->assertFalse($adviserField->isReactive());
    }

    public function test_class_form_lists_each_adviser_name_once(): void
    {
        $canonical = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'Teacher One',
            'rank' => 'Teacher I',
            'major' => 'English',
        ]));
        $duplicate = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => ' teacher one ',
            'rank' => 'Teacher I',
            'major' => 'English',
        ]));
        $other = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'Teacher Two',
            'rank' => 'Teacher II',
            'major' => 'Mathematics',
        ]));

        $resource = app(ClassesModelResource::class);
        $options = (new \ReflectionMethod($resource, 'uniqueAdviserOptions'))
            ->invoke($resource, Adviser::query(), null)
            ->get();

        $this->assertSame([$canonical->id, $other->id], $options->modelKeys());
        $this->assertSame(
            ['teacher one', 'teacher two'],
            $options->pluck('name')->map(fn (string $name): string => mb_strtolower(trim($name)))->all()
        );

        $editOptions = (new \ReflectionMethod($resource, 'uniqueAdviserOptions'))
            ->invoke($resource, Adviser::query(), $duplicate->id)
            ->get();

        $this->assertContains($duplicate->id, $editOptions->modelKeys());
        $this->assertNotContains($canonical->id, $editOptions->modelKeys());
        $this->assertSame(
            ['teacher one', 'teacher two'],
            $editOptions->pluck('name')
                ->map(fn (string $name): string => mb_strtolower(trim($name)))
                ->sort()
                ->values()
                ->all()
        );
    }

    public function test_creating_another_class_persists_the_exact_selected_adviser(): void
    {
        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]
        );
        $admin = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 1,
            'username' => 'class-adviser-admin',
            'email' => 'class-adviser-admin@example.test',
            'name' => 'Class Adviser Admin',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $admin->save();

        $schoolYear = SchoolYear::query()->create([
            'school_year' => '2026-2027',
            'active' => true,
        ]);
        $grade = Grade::query()->create([
            'grade' => 'Grade 11',
            'status' => 'active',
        ]);
        $selectedAdviser = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'Selected Adviser',
            'rank' => 'Teacher II',
            'major' => 'English',
        ]));
        $nextAdviser = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'Next Adviser',
            'rank' => 'Teacher III',
            'major' => 'Mathematics',
        ]));
        ClassesModel::query()->create([
            'adviser_id' => $selectedAdviser->id,
            'grade_id' => $grade->id,
            'section' => 'Existing Section',
            'school_year_id' => $schoolYear->id,
        ]);

        $response = $this->actingAs($admin, 'moonshine')
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('moonshine.crud.store', [
                'resourceUri' => app(ClassesModelResource::class)->getUriKey(),
            ]), [
                'school_year_id' => $schoolYear->id,
                'adviser_id' => $selectedAdviser->id,
                'grade_id' => $grade->id,
                'section' => 'New Section',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'grading_period_count' => 4,
                'active' => 1,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('classes', [
            'section' => 'New Section',
            'adviser_id' => $selectedAdviser->id,
        ]);
        $this->assertDatabaseMissing('classes', [
            'section' => 'New Section',
            'adviser_id' => $nextAdviser->id,
        ]);
    }

    public function test_adviser_portal_can_switch_between_multiple_assigned_classes(): void
    {
        DB::table('moonshine_user_roles')->insert([
            'id' => 2,
            'name' => 'Teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 2,
            'username' => 'multi.class.adviser',
            'email' => 'multi.class.adviser@example.test',
            'name' => 'Multi Class Adviser',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $user->save();

        $schoolYear = SchoolYear::query()->create([
            'school_year' => '2026-2027',
            'active' => true,
        ]);
        $grade = Grade::query()->create([
            'grade' => 'Grade 11',
            'status' => 'active',
        ]);
        $adviser = Adviser::withoutEvents(fn () => Adviser::query()->create([
            'user_id' => $user->id,
            'name' => 'Multi Class Adviser',
            'rank' => 'Teacher III',
            'major' => 'English',
        ]));
        $firstClass = ClassesModel::query()->create([
            'adviser_id' => $adviser->id,
            'grade_id' => $grade->id,
            'section' => 'STEM A',
            'school_year_id' => $schoolYear->id,
        ]);
        $secondClass = ClassesModel::query()->create([
            'adviser_id' => $adviser->id,
            'grade_id' => $grade->id,
            'section' => 'STEM B',
            'school_year_id' => $schoolYear->id,
        ]);

        $this->actingAs($user, 'moonshine')
            ->get(route('teacher.dashboard', [
                'context' => 'adviser',
                'school_year_id' => $schoolYear->id,
                'class_id' => $secondClass->id,
            ]))
            ->assertOk()
            ->assertSee('Advisory class (2 assigned)')
            ->assertSee('STEM A')
            ->assertSee('STEM B')
            ->assertViewHas('classes', fn ($classes): bool => $classes->modelKeys() === [$firstClass->id, $secondClass->id])
            ->assertViewHas('selectedClass', fn ($class): bool => $class->is($secondClass));

        $this->post(route('teacher.schedules.store'), [
            'class_id' => $secondClass->id,
            'day' => 'Monday',
            'time_frame' => '8:00 AM - 9:00 AM',
        ])
            ->assertRedirect(route('teacher.dashboard', [
                'tab' => 'schedules',
                'class_id' => $secondClass->id,
                'school_year_id' => $schoolYear->id,
            ]));

        $this->assertDatabaseHas('class_adviser_schedules', [
            'adviser_id' => $adviser->id,
            'class_id' => $secondClass->id,
            'section' => 'STEM B',
        ]);
    }
}
