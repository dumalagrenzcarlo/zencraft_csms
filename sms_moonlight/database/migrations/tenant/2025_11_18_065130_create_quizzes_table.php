<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table): void {
            $table->id();
            $table->text('question');
            $table->dateTime('record_created')->nullable()->useCurrent();
            $table->dateTime('record_updated')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('record_deleted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};