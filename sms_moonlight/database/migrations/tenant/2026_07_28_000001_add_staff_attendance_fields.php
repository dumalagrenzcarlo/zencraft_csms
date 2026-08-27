<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisers', function (Blueprint $table): void {
            $table->time('shift_start_time')->nullable()->after('staff_type');
            $table->time('shift_end_time')->nullable()->after('shift_start_time');
        });

        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->index(['adviser_id', 'currentdate'], 'attendance_adviser_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->dropIndex('attendance_adviser_date_idx');
        });

        Schema::table('advisers', function (Blueprint $table): void {
            $table->dropColumn(['shift_start_time', 'shift_end_time']);
        });
    }
};
