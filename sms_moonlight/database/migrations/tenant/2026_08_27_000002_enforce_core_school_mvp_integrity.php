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
        $this->removeDuplicateRows('class_subjects', ['class_id', 'subject_id']);
        $this->removeDuplicateRows('class_student_grades', ['class_id', 'student_id', 'subject_id']);

        Schema::table('class_subjects', function (Blueprint $table): void {
            $table->unique(['class_id', 'subject_id'], 'class_subjects_class_subject_unique');
        });

        Schema::table('class_student_grades', function (Blueprint $table): void {
            $table->unique(
                ['class_id', 'student_id', 'subject_id'],
                'class_student_grades_class_student_subject_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('class_student_grades', function (Blueprint $table): void {
            $table->dropUnique('class_student_grades_class_student_subject_unique');
        });

        Schema::table('class_subjects', function (Blueprint $table): void {
            $table->dropUnique('class_subjects_class_subject_unique');
        });
    }

    /**
     * Keep the oldest canonical row before adding a uniqueness constraint.
     *
     * @param  list<string>  $columns
     */
    private function removeDuplicateRows(string $table, array $columns): void
    {
        $duplicates = DB::table($table)
            ->select($columns)
            ->selectRaw('MIN(id) AS keep_id')
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table($table)
                ->where(function ($query) use ($columns, $duplicate): void {
                    foreach ($columns as $column) {
                        $query->where($column, $duplicate->{$column});
                    }
                })
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }
    }
};
