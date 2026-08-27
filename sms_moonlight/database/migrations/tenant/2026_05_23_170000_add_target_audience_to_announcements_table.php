<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            if (! Schema::hasColumn('announcements', 'target_audience')) {
                $table->enum('target_audience', ['students', 'teachers', 'both'])
                    ->default('both')
                    ->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            if (Schema::hasColumn('announcements', 'target_audience')) {
                $table->dropColumn('target_audience');
            }
        });
    }
};
