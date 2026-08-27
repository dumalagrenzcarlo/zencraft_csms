<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {

            $table->date('dob')->nullable()->change();

            $table->string('address', 20)->nullable()->change();
            $table->string('birthplace', 50)->nullable()->change();

            $table->string('profile_photo', 200)->nullable()->change();

            $table->string('parent_guardian', 50)->nullable()->change();
            $table->string('parent_guardian_address', 60)->nullable()->change();
            $table->string('parent_guardian_relationship', 200)->nullable()->change();

            $table->boolean('is_4ps_member')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {

            $table->date('dob')->nullable(false)->change();

            $table->string('address', 20)->nullable(false)->change();
            $table->string('birthplace', 50)->nullable(false)->change();

            $table->string('profile_photo', 200)->nullable(false)->change();

            $table->string('parent_guardian', 50)->nullable(false)->change();
            $table->string('parent_guardian_address', 60)->nullable(false)->change();
            $table->string('parent_guardian_relationship', 200)->nullable(false)->change();

            $table->boolean('is_4ps_member')->nullable(false)->change();
        });
    }
};