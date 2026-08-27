<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_access', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_access');
    }
};