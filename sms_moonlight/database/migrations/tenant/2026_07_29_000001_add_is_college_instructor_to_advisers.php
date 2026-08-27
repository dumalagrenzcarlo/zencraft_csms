<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisers', function (Blueprint $table): void {
            $table->boolean('is_college_instructor')
                ->default(false)
                ->after('staff_type')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('advisers', function (Blueprint $table): void {
            $table->dropIndex(['is_college_instructor']);
            $table->dropColumn('is_college_instructor');
        });
    }
};
