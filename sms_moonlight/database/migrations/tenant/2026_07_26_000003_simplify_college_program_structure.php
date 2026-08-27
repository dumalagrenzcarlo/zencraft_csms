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
            $table->foreignId('program_id')
                ->nullable()
                ->after('id')
                ->constrained('college_programs')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('prerequisite_program_course_id')
                ->nullable()
                ->after('program_id');
            $table->foreign(
                'prerequisite_program_course_id',
                'ccs_prereq_program_course_fk'
            )
                ->references('id')
                ->on('college_curriculum_subjects')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        DB::table('college_curriculum_subjects')
            ->orderBy('id')
            ->eachById(function ($course): void {
                $programId = DB::table('college_curricula')
                    ->where('id', $course->curriculum_id)
                    ->value('program_id');

                DB::table('college_curriculum_subjects')
                    ->where('id', $course->id)
                    ->update([
                        'program_id' => $programId,
                        'prerequisite_program_course_id' => $course->prerequisite_curriculum_subject_id,
                    ]);
            });

        Schema::table('college_course_offerings', function (Blueprint $table): void {
            $table->foreignId('school_year_id')
                ->nullable()
                ->after('id')
                ->constrained('school_year')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('program_course_id')
                ->nullable()
                ->after('school_year_id')
                ->constrained('college_curriculum_subjects')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::table('college_course_offerings')
            ->orderBy('id')
            ->eachById(function ($offering): void {
                $schoolYearId = DB::table('college_terms')
                    ->where('id', $offering->term_id)
                    ->value('school_year_id');

                DB::table('college_course_offerings')
                    ->where('id', $offering->id)
                    ->update([
                        'school_year_id' => $schoolYearId,
                        'program_course_id' => $offering->curriculum_subject_id,
                    ]);
            });

        Schema::table('college_enrollments', function (Blueprint $table): void {
            $table->foreignId('school_year_id')
                ->nullable()
                ->after('program_id')
                ->constrained('school_year')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unsignedTinyInteger('semester')->default(1)->after('school_year_id');
        });

        DB::table('college_enrollments')
            ->orderBy('id')
            ->eachById(function ($enrollment): void {
                $term = DB::table('college_terms')->where('id', $enrollment->term_id)->first();

                DB::table('college_enrollments')
                    ->where('id', $enrollment->id)
                    ->update([
                        'school_year_id' => $term?->school_year_id,
                        'semester' => $term?->semester ?? 1,
                    ]);
        });

        Schema::table('college_course_offerings', function (Blueprint $table): void {
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->index('term_id', 'college_offering_term_temp_idx');
            }
            $table->dropUnique('college_offering_unique');
            $table->dropConstrainedForeignId('curriculum_subject_id');
            $table->dropConstrainedForeignId('term_id');
            $table->unique(
                ['school_year_id', 'program_course_id', 'section'],
                'college_offering_unique'
            );
        });

        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->index('curriculum_id', 'ccs_curriculum_temp_idx');
            }
            $table->dropUnique('college_curriculum_course_unique');
            if (DB::connection()->getDriverName() === 'sqlite') {
                $table->dropForeign(['prerequisite_curriculum_subject_id']);
            } else {
                $table->dropForeign('ccs_prereq_curriculum_fk');
            }
            $table->dropColumn('prerequisite_curriculum_subject_id');
            $table->dropConstrainedForeignId('curriculum_id');
            $table->unique(
                ['program_id', 'course_code', 'year_level', 'semester'],
                'college_program_course_unique'
            );
        });

        Schema::table('college_enrollments', function (Blueprint $table): void {
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->index('student_id', 'college_enrollment_student_temp_idx');
            }
            $table->dropUnique(['student_id', 'term_id']);
            $table->dropConstrainedForeignId('curriculum_id');
            $table->dropConstrainedForeignId('term_id');
            $table->unique(
                ['student_id', 'school_year_id', 'semester'],
                'college_enrollment_unique'
            );
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->dropIndex('college_enrollment_student_temp_idx');
            }
        });

        Schema::dropIfExists('college_terms');
        Schema::dropIfExists('college_curricula');
    }

    public function down(): void
    {
        Schema::create('college_curricula', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained('college_programs')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('effective_year', 20)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['program_id', 'name']);
        });

        DB::table('college_programs')
            ->orderBy('id')
            ->eachById(function ($program): void {
                DB::table('college_curricula')->insert([
                    'id' => $program->id,
                    'program_id' => $program->id,
                    'name' => $program->name,
                    'effective_year' => null,
                    'active' => $program->active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::create('college_terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_year')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedTinyInteger('semester');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->unique(['school_year_id', 'semester']);
        });

        $schoolYearIds = DB::table('college_enrollments')
            ->pluck('school_year_id')
            ->merge(DB::table('college_course_offerings')->pluck('school_year_id'))
            ->filter()
            ->unique()
            ->values();

        foreach ($schoolYearIds as $schoolYearId) {
            foreach ([1, 2] as $semester) {
                DB::table('college_terms')->insert([
                    'school_year_id' => $schoolYearId,
                    'semester' => $semester,
                    'start_date' => null,
                    'end_date' => null,
                    'active' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            $table->foreignId('curriculum_id')
                ->nullable()
                ->after('id')
                ->constrained('college_curricula')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('prerequisite_curriculum_subject_id')
                ->nullable()
                ->after('curriculum_id');
            $table->foreign(
                'prerequisite_curriculum_subject_id',
                'ccs_prereq_curriculum_fk'
            )
                ->references('id')
                ->on('college_curriculum_subjects')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        DB::table('college_curriculum_subjects')
            ->orderBy('id')
            ->eachById(function ($course): void {
                DB::table('college_curriculum_subjects')
                    ->where('id', $course->id)
                    ->update([
                        'curriculum_id' => $course->program_id,
                        'prerequisite_curriculum_subject_id' => $course->prerequisite_program_course_id,
                    ]);
            });

        Schema::table('college_enrollments', function (Blueprint $table): void {
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->index('student_id', 'college_enrollment_student_temp_idx');
            }
            $table->dropUnique('college_enrollment_unique');
            $table->foreignId('curriculum_id')
                ->nullable()
                ->after('program_id')
                ->constrained('college_curricula')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('term_id')
                ->nullable()
                ->after('curriculum_id')
                ->constrained('college_terms')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::table('college_enrollments')
            ->orderBy('id')
            ->eachById(function ($enrollment): void {
                $termId = DB::table('college_terms')
                    ->where('school_year_id', $enrollment->school_year_id)
                    ->where('semester', $enrollment->semester)
                    ->value('id');

                DB::table('college_enrollments')
                    ->where('id', $enrollment->id)
                    ->update([
                        'curriculum_id' => $enrollment->program_id,
                        'term_id' => $termId,
                    ]);
            });

        Schema::table('college_course_offerings', function (Blueprint $table): void {
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->index('school_year_id', 'college_offering_year_temp_idx');
            }
            $table->dropUnique('college_offering_unique');
            $table->foreignId('term_id')
                ->nullable()
                ->after('id')
                ->constrained('college_terms')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
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
                $semester = DB::table('college_curriculum_subjects')
                    ->where('id', $offering->program_course_id)
                    ->value('semester');
                $termId = DB::table('college_terms')
                    ->where('school_year_id', $offering->school_year_id)
                    ->where('semester', $semester)
                    ->value('id');

                DB::table('college_course_offerings')
                    ->where('id', $offering->id)
                    ->update([
                        'term_id' => $termId,
                        'curriculum_subject_id' => $offering->program_course_id,
                    ]);
            });

        Schema::table('college_course_offerings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('program_course_id');
            $table->dropConstrainedForeignId('school_year_id');
            $table->unique(
                ['term_id', 'curriculum_subject_id', 'section'],
                'college_offering_unique'
            );
        });

        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->index('program_id', 'ccs_program_temp_idx');
            }
            $table->dropUnique('college_program_course_unique');
            if (DB::connection()->getDriverName() === 'sqlite') {
                $table->dropForeign(['prerequisite_program_course_id']);
            } else {
                $table->dropForeign('ccs_prereq_program_course_fk');
            }
            $table->dropColumn('prerequisite_program_course_id');
            $table->dropConstrainedForeignId('program_id');
            $table->unique(
                ['curriculum_id', 'course_code', 'year_level', 'semester'],
                'college_curriculum_course_unique'
            );
        });

        Schema::table('college_enrollments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('school_year_id');
            $table->dropColumn('semester');
            $table->unique(['student_id', 'term_id']);
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->dropIndex('college_enrollment_student_temp_idx');
            }
        });
    }
};
