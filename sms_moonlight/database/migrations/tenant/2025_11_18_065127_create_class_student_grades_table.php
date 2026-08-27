<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_student_grades', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('grade_id');
            $table->unsignedBigInteger('subject_id');
            $table->integer('q1');
            $table->integer('q2');
            $table->integer('q3');
            $table->integer('q4');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_student_grades');
    }
};