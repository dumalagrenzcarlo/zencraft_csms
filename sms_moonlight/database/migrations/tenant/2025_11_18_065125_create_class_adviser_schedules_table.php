<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_adviser_schedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('adviser_id');
            $table->string('day', 10);
            $table->string('section', 100);
            $table->string('time_frame', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_adviser_schedules');
    }
};