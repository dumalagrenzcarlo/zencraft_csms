<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('college_course_offerings', function (Blueprint $table): void {
            $table->foreignId('curriculum_subject_id')
                ->nullable()
                ->after('term_id')
                ->constrained('college_curriculum_subjects')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::table('college_course_offerings')
            ->orderBy('id')
            ->eachById(function ($offering): void {
                $curriculumSubjectId = DB::table('college_curriculum_subjects')
                    ->where('subject_id', $offering->subject_id)
                    ->orderBy('id')
                    ->value('id');

                if ($curriculumSubjectId) {
                    DB::table('college_course_offerings')
                        ->where('id', $offering->id)
                        ->update(['curriculum_subject_id' => $curriculumSubjectId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('college_course_offerings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('curriculum_subject_id');
        });
    }
};
