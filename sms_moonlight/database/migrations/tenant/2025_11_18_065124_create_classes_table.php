<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('adviser_id');
            $table->unsignedBigInteger('grade_id');
            $table->string('section', 200);
            $table->unsignedBigInteger('school_year_id');
            $table->string('status', 50);
            $table->boolean('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};