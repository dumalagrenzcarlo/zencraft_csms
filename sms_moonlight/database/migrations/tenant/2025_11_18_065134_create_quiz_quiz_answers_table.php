<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_quiz_answers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quiz_id')->nullable();
            $table->unsignedBigInteger('answer_id')->nullable();
            $table->boolean('is_correct_answer')->nullable();
            $table->dateTime('record_created')->nullable()->useCurrent();
            $table->dateTime('record_updated')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('record_deleted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_quiz_answers');
    }
};