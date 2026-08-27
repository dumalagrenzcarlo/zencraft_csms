<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->string('source_event_id', 100)->nullable()->after('source');
            $table->unique('source_event_id', 'attendance_source_event_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->dropUnique('attendance_source_event_unique');
            $table->dropColumn('source_event_id');
        });
    }
};
