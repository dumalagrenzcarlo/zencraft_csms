<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_group', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_year_id');
            $table->unsignedBigInteger('grade_id');
            $table->text('week');
            $table->dateTime('record_created')->nullable()->useCurrent();
            $table->dateTime('record_updated')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('record_deleted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_group');
    }
};