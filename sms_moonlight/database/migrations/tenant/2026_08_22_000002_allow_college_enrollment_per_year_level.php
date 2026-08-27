<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL uses the existing composite unique index to support the
        // student_id foreign key. Add a permanent standalone index first.
        if (! Schema::hasIndex('college_enrollments', ['student_id'])) {
            Schema::table('college_enrollments', function (Blueprint $table): void {
                $table->index('student_id', 'college_enrollments_student_id_index');
            });
        }

        if (Schema::hasIndex('college_enrollments', 'college_enrollment_unique')) {
            Schema::table('college_enrollments', function (Blueprint $table): void {
                $table->dropUnique('college_enrollment_unique');
            });
        }

        if (! Schema::hasIndex('college_enrollments', 'college_enrollment_year_level_unique')) {
            Schema::table('college_enrollments', function (Blueprint $table): void {
                $table->unique(
                    ['student_id', 'school_year_id', 'semester', 'year_level'],
                    'college_enrollment_year_level_unique'
                );
            });
        }
    }

    public function down(): void
    {
        $hasMultipleYearLevels = DB::table('college_enrollments')
            ->selectRaw('student_id, school_year_id, semester, COUNT(*) as enrollment_count')
            ->groupBy('student_id', 'school_year_id', 'semester')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasMultipleYearLevels) {
            throw new RuntimeException(
                'Cannot restore the previous enrollment constraint while students have multiple year levels in the same school year and semester.'
            );
        }

        if (! Schema::hasIndex('college_enrollments', ['student_id'])) {
            Schema::table('college_enrollments', function (Blueprint $table): void {
                $table->index('student_id', 'college_enrollments_student_id_index');
            });
        }

        if (Schema::hasIndex('college_enrollments', 'college_enrollment_year_level_unique')) {
            Schema::table('college_enrollments', function (Blueprint $table): void {
                $table->dropUnique('college_enrollment_year_level_unique');
            });
        }

        if (! Schema::hasIndex('college_enrollments', 'college_enrollment_unique')) {
            Schema::table('college_enrollments', function (Blueprint $table): void {
                $table->unique(
                    ['student_id', 'school_year_id', 'semester'],
                    'college_enrollment_unique'
                );
            });
        }
    }
};
