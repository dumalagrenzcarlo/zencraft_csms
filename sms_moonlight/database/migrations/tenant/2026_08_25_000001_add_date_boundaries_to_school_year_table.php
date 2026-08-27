<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_year', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('school_year');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('school_year', function (Blueprint $table): void {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
