<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('grading_period_count')
                ->nullable()
                ->after('school_year_id');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table): void {
            $table->dropColumn('grading_period_count');
        });
    }
};
