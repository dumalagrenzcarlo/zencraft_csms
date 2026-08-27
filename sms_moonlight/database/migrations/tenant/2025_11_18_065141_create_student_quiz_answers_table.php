<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_quiz_answers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quiz_group_days_id');
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('answer_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->dateTime('record_created')->nullable()->useCurrent();
            $table->dateTime('record_updated')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('record_deleted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_quiz_answers');
    }
};