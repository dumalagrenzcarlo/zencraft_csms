<?php

declare(strict_types=1);

use App\Models\CollegeEnrollment;
use App\Support\CollegeEnrollmentCourseAssigner;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
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
        // Assigned class-grade records may contain grades later, so they are not removed on rollback.
    }
};
