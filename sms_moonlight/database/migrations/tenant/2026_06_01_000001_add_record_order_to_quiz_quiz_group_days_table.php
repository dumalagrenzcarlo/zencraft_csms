<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_quiz_group_days', function (Blueprint $table): void {
            $table->integer('record_order')->nullable()->after('quiz_group_days_id');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_quiz_group_days', function (Blueprint $table): void {
            $table->dropColumn('record_order');
        });
    }
};
