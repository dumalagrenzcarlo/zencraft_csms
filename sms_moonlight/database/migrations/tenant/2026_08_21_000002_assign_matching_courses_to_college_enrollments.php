<?php

declare(strict_types=1);

use App\Models\CollegeEnrollment;
use App\Support\CollegeEnrollmentCourseAssigner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The enrollment-class schema is introduced by the later 000004 migration.
        // That migration runs this same backfill after adding program_course_id.
        if (! Schema::hasColumn('college_enrollment_courses', 'program_course_id')) {
            return;
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
        // Course assignments are real enrollment records and must not be deleted on rollback.
    }
};
