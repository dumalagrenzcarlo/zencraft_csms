<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('school_year_id');
            $table->boolean('hidden_grade')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_students');
    }
};