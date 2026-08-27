<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moonshine_users', function (Blueprint $table): void {
            if (! Schema::hasColumn('moonshine_users', 'username')) {
                $table->string('username', 190)->nullable()->unique()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('moonshine_users', function (Blueprint $table): void {
            if (Schema::hasColumn('moonshine_users', 'username')) {
                $table->dropUnique('moonshine_users_username_unique');
                $table->dropColumn('username');
            }
        });
    }
};
