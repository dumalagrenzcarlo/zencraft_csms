<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicateAssignments = DB::table('class_students')
            ->selectRaw('student_id, school_year_id, COUNT(*) as assignment_count')
            ->groupBy('student_id', 'school_year_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateAssignments) {
            throw new \RuntimeException(
                'Cannot enforce one high-school class per school year while duplicate class assignments exist.'
            );
        }

        if (Schema::hasColumn('classes', 'is_college')) {
            Schema::table('classes', function (Blueprint $table): void {
                $table->dropColumn('is_college');
            });
        }

        Schema::table('class_students', function (Blueprint $table): void {
            $table->unique(
                ['student_id', 'school_year_id'],
                'class_students_student_school_year_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('class_students', function (Blueprint $table): void {
            $table->dropUnique('class_students_student_school_year_unique');
        });

        Schema::table('classes', function (Blueprint $table): void {
            $table->boolean('is_college')->default(false)->after('active');
        });
    }
};
