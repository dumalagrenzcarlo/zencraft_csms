<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table): void {
            $table->boolean('enable_assignments')->default(false)->after('is_college');
        });

        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('adviser_id');
            $table->string('title', 200);
            $table->text('notes')->nullable();
            $table->string('file_path', 300);
            $table->string('file_name', 255);
            $table->dateTime('deadline');
            $table->timestamps();

            $table->index(['class_id', 'deadline']);
            $table->index('adviser_id');
        });

        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('student_id');
            $table->string('file_path', 300);
            $table->string('file_name', 255);
            $table->text('notes')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');

        Schema::table('classes', function (Blueprint $table): void {
            $table->dropColumn('enable_assignments');
        });
    }
};
