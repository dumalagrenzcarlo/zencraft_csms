<?php

declare(strict_types=1);

use App\Models\CollegeEnrollment;
use App\Support\CollegeEnrollmentCourseAssigner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('college_enrollment_courses', 'program_course_id')) {
            Schema::table('college_enrollment_courses', function (Blueprint $table): void {
                $table->foreignId('program_course_id')
                    ->nullable()
                    ->after('enrollment_id')
                    ->constrained('college_curriculum_subjects')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        DB::table('college_enrollment_courses')
            ->whereNull('program_course_id')
            ->whereNotNull('offering_id')
            ->orderBy('id')
            ->get()
            ->each(function ($record): void {
                $programCourseId = DB::table('college_course_offerings')
                    ->where('id', $record->offering_id)
                    ->value('program_course_id');

                DB::table('college_enrollment_courses')
                    ->where('id', $record->id)
                    ->update(['program_course_id' => $programCourseId]);
            });

        DB::table('college_enrollment_courses')
            ->select('enrollment_id', 'program_course_id')
            ->whereNotNull('program_course_id')
            ->groupBy('enrollment_id', 'program_course_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function ($duplicate): void {
                $records = DB::table('college_enrollment_courses')
                    ->where('enrollment_id', $duplicate->enrollment_id)
                    ->where('program_course_id', $duplicate->program_course_id)
                    ->orderByRaw('CASE WHEN prelim_grade IS NOT NULL OR midterm_grade IS NOT NULL OR prefinal_grade IS NOT NULL OR final_grade IS NOT NULL OR grades_submitted_at IS NOT NULL THEN 0 ELSE 1 END')
                    ->orderBy('id')
                    ->get();

                DB::table('college_enrollment_courses')
                    ->whereIn('id', $records->skip(1)->pluck('id'))
                    ->delete();
            });

        // MySQL may use the composite unique index to support the enrollment_id
        // foreign key, while offering_id also needs its own supporting index.
        // Give both foreign keys standalone indexes before dropping the composite.
        if (! Schema::hasIndex('college_enrollment_courses', ['enrollment_id'])) {
            Schema::table('college_enrollment_courses', function (Blueprint $table): void {
                $table->index('enrollment_id', 'college_enrollment_courses_enrollment_id_index');
            });
        }

        if (! Schema::hasIndex('college_enrollment_courses', ['offering_id'])) {
            Schema::table('college_enrollment_courses', function (Blueprint $table): void {
                $table->index('offering_id', 'college_enrollment_courses_offering_id_index');
            });
        }

        if (Schema::hasIndex(
            'college_enrollment_courses',
            ['enrollment_id', 'offering_id'],
            'unique'
        )) {
            Schema::table('college_enrollment_courses', function (Blueprint $table): void {
                $table->dropUnique(['enrollment_id', 'offering_id']);
            });
        }

        Schema::table('college_enrollment_courses', function (Blueprint $table): void {
            $table->foreignId('offering_id')->nullable()->change();
        });

        if (! Schema::hasIndex('college_enrollment_courses', 'college_enrollment_program_class_unique')) {
            Schema::table('college_enrollment_courses', function (Blueprint $table): void {
                $table->unique(['enrollment_id', 'program_course_id'], 'college_enrollment_program_class_unique');
            });
        }

        $assigner = app(CollegeEnrollmentCourseAssigner::class);
        CollegeEnrollment::query()
            ->where('status', 'enrolled')
            ->orderBy('id')
            ->eachById(
                static fn (CollegeEnrollment $enrollment) => $assigner->assignAvailableCourses($enrollment)
            );
    }

    public function down(): void
    {
        // Enrollment-class records may no longer have schedules, so this data migration is not reversible safely.
    }
};
