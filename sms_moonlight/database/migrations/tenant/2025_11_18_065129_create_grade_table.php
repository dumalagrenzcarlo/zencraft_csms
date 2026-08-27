<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade', function (Blueprint $table): void {
            $table->id();
            $table->string('grade', 10);
            $table->string('status', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade');
    }
};