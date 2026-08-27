<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_year', function (Blueprint $table): void {
            $table->id();
            $table->string('school_year', 10);
            $table->boolean('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_year');
    }
};