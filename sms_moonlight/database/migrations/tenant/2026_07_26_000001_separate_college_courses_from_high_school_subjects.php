<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            $table->string('course_code', 30)->default('')->after('curriculum_id');
            $table->string('description', 255)->default('')->after('course_code');
            $table->unsignedSmallInteger('course_order')->default(0)->after('units');
            $table->foreignId('prerequisite_curriculum_subject_id')
                ->nullable()
                ->after('subject_id');
            $table->foreign(
                'prerequisite_curriculum_subject_id',
                'ccs_prereq_curriculum_fk'
            )
                ->references('id')
                ->on('college_curriculum_subjects')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $legacyCourses = DB::table('college_curriculum_subjects')
            ->leftJoin('subjects', 'subjects.id', '=', 'college_curriculum_subjects.subject_id')
            ->select([
                'college_curriculum_subjects.id',
                'college_curriculum_subjects.curriculum_id',
                'college_curriculum_subjects.subject_id',
                'college_curriculum_subjects.prerequisite_subject_id',
                'subjects.subject as legacy_description',
            ])
            ->orderBy('college_curriculum_subjects.id')
            ->get();

        foreach ($legacyCourses as $course) {
            $prerequisiteCurriculumSubjectId = null;

            if ($course->prerequisite_subject_id) {
                $prerequisiteCurriculumSubjectId = DB::table('college_curriculum_subjects')
                    ->where('curriculum_id', $course->curriculum_id)
                    ->where('subject_id', $course->prerequisite_subject_id)
                    ->orderBy('id')
                    ->value('id');
            }

            DB::table('college_curriculum_subjects')
                ->where('id', $course->id)
                ->update([
                    'course_code' => 'LEGACY-'.$course->id,
                    'description' => $course->legacy_description ?: 'Legacy college course',
                    'course_order' => $course->id,
                    'prerequisite_curriculum_subject_id' => $prerequisiteCurriculumSubjectId,
                ]);
        }

        Schema::table('college_course_offerings', function (Blueprint $table): void {
            $table->index('term_id', 'college_offering_term_temp_idx');
            $table->dropUnique('college_offering_unique');
            $table->dropConstrainedForeignId('subject_id');
            $table->unique(
                ['term_id', 'curriculum_subject_id', 'section'],
                'college_offering_unique'
            );
            $table->dropIndex('college_offering_term_temp_idx');
        });

        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            $table->index('curriculum_id', 'ccs_curriculum_temp_idx');
            $table->dropUnique('college_curriculum_subject_unique');
            $table->dropConstrainedForeignId('prerequisite_subject_id');
            $table->dropConstrainedForeignId('subject_id');
            $table->unique(
                ['curriculum_id', 'course_code', 'year_level', 'semester'],
                'college_curriculum_course_unique'
            );
            $table->dropIndex('ccs_curriculum_temp_idx');
        });
    }

    public function down(): void
    {
        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            $table->foreignId('subject_id')
                ->nullable()
                ->after('curriculum_id')
                ->constrained('subjects')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('prerequisite_subject_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('subjects')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $courses = DB::table('college_curriculum_subjects')
            ->orderBy('id')
            ->get();

        foreach ($courses as $course) {
            $subjectId = DB::table('subjects')
                ->where('subject', mb_substr($course->description, 0, 50))
                ->value('id');

            if (! $subjectId) {
                $subjectId = DB::table('subjects')->insertGetId([
                    'subject' => mb_substr($course->description, 0, 50),
                    'include_in_average' => true,
                    'record_order' => null,
                    'record_orders' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('college_curriculum_subjects')
                ->where('id', $course->id)
                ->update(['subject_id' => $subjectId]);
        }

        foreach ($courses as $course) {
            if (! $course->prerequisite_curriculum_subject_id) {
                continue;
            }

            $prerequisiteSubjectId = DB::table('college_curriculum_subjects')
                ->where('id', $course->prerequisite_curriculum_subject_id)
                ->value('subject_id');

            DB::table('college_curriculum_subjects')
                ->where('id', $course->id)
                ->update(['prerequisite_subject_id' => $prerequisiteSubjectId]);
        }

        Schema::table('college_course_offerings', function (Blueprint $table): void {
            $table->index('term_id', 'college_offering_term_temp_idx');
            $table->dropUnique('college_offering_unique');
            $table->foreignId('subject_id')
                ->nullable()
                ->after('curriculum_subject_id')
                ->constrained('subjects')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::table('college_course_offerings')
            ->orderBy('id')
            ->eachById(function ($offering): void {
                $subjectId = DB::table('college_curriculum_subjects')
                    ->where('id', $offering->curriculum_subject_id)
                    ->value('subject_id');

                DB::table('college_course_offerings')
                    ->where('id', $offering->id)
                    ->update(['subject_id' => $subjectId]);
            });

        Schema::table('college_course_offerings', function (Blueprint $table): void {
            $table->unique(['term_id', 'subject_id', 'section'], 'college_offering_unique');
            $table->dropIndex('college_offering_term_temp_idx');
        });

        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            $table->index('curriculum_id', 'ccs_curriculum_temp_idx');
            $table->dropUnique('college_curriculum_course_unique');
            if (DB::connection()->getDriverName() === 'sqlite') {
                $table->dropForeign(['prerequisite_curriculum_subject_id']);
            } else {
                $table->dropForeign('ccs_prereq_curriculum_fk');
            }
            $table->dropColumn('prerequisite_curriculum_subject_id');
            $table->dropColumn(['course_code', 'description', 'course_order']);
            $table->unique(
                ['curriculum_id', 'subject_id', 'year_level', 'semester'],
                'college_curriculum_subject_unique'
            );
            $table->dropIndex('ccs_curriculum_temp_idx');
        });
    }
};
