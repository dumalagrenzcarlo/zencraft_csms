<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 200);
            $table->string('rank', 200);
            $table->string('major', 200);
            $table->string('profile_photo', 200);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisers');
    }
};