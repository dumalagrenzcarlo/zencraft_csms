<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_group_days', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->unsignedBigInteger('quiz_group_id');
            $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
            $table->integer('quiz_duration_seconds');
            $table->timestamp('record_created')->nullable()->useCurrent();
            $table->timestamp('record_updated')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('record_deleted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_group_days');
    }
};