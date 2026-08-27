<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('lrn', 15);
            $table->string('lastname', 30);
            $table->string('firstname', 30);
            $table->string('middlename', 30);
            $table->string('gender', 10);
            $table->date('dob');
            $table->string('address', 20);
            $table->string('birthplace', 50);
            $table->string('profile_photo', 200);
            $table->string('parent_guardian', 50);
            $table->string('parent_guardian_address', 60);
            $table->string('parent_guardian_relationship', 200);
            $table->boolean('is_4ps_member');
            $table->string('weight', 10)->nullable();
            $table->string('height', 10)->nullable();
            $table->string('form137path', 200)->nullable();
            $table->string('elementary_school_name', 200)->nullable();
            $table->string('elementary_school_id', 100)->nullable();
            $table->string('elementary_school_address', 300)->nullable();
            $table->string('elementary_school_grade', 10)->nullable();
            $table->string('elementary_school_citation', 10)->nullable();
            $table->boolean('deworming_grade_7')->nullable();
            $table->boolean('deworming_grade_8')->nullable();
            $table->boolean('deworming_grade_9')->nullable();
            $table->boolean('deworming_grade_10')->nullable();
            $table->boolean('archived')->nullable();
            $table->dateTime('archive_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};