<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moonshine_users', function (Blueprint $table) {

            // 1. Drop unique constraint on username
            $table->dropUnique('moonshine_users_username_unique');

            // 2. Make sure username is nullable but NOT unique
            $table->string('username', 190)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('moonshine_users', function (Blueprint $table) {

            // Re-add unique constraint
            $table->string('username', 190)->nullable()->unique()->change();
        });
    }
};