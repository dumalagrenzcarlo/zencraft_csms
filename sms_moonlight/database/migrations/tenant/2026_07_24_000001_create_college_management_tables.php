<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_programs', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 180);
            $table->unsignedTinyInteger('duration_years')->default(4);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('college_curricula', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained('college_programs')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('effective_year', 20)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['program_id', 'name']);
        });

        Schema::create('college_curriculum_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('college_curricula')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('prerequisite_subject_id')->nullable()->constrained('subjects')->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->unsignedTinyInteger('semester');
            $table->decimal('units', 5, 2)->default(3);
            $table->timestamps();

            $table->unique(
                ['curriculum_id', 'subject_id', 'year_level', 'semester'],
                'college_curriculum_subject_unique'
            );
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

        Schema::create('college_course_offerings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('term_id')->constrained('college_terms')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('instructor_id')->constrained('advisers')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('section', 50);
            $table->string('schedule', 150)->nullable();
            $table->string('room', 80)->nullable();
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['term_id', 'subject_id', 'section'], 'college_offering_unique');
        });

        Schema::create('college_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('program_id')->constrained('college_programs')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('curriculum_id')->constrained('college_curricula')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('term_id')->constrained('college_terms')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->string('status', 30)->default('enrolled');
            $table->timestamps();

            $table->unique(['student_id', 'term_id']);
        });

        Schema::create('college_enrollment_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('college_enrollments')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('offering_id')->constrained('college_course_offerings')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('prelim_grade', 5, 2)->nullable();
            $table->decimal('midterm_grade', 5, 2)->nullable();
            $table->decimal('prefinal_grade', 5, 2)->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->string('remarks', 30)->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'offering_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_enrollment_courses');
        Schema::dropIfExists('college_enrollments');
        Schema::dropIfExists('college_course_offerings');
        Schema::dropIfExists('college_terms');
        Schema::dropIfExists('college_curriculum_subjects');
        Schema::dropIfExists('college_curricula');
        Schema::dropIfExists('college_programs');
    }
};
