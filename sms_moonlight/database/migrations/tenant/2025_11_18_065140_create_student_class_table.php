<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_class', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('grade_id');
            $table->string('school_year', 200);
            $table->string('section', 200);
            $table->string('status', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class');
    }
};