<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_record', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('student_id');

            $table->time('amlogin');
            $table->time('amlogout');
            $table->time('pmlogin');
            $table->time('pmlogout');
            $table->date('currentdate');
            $table->time('logged_time')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_record');
    }
};