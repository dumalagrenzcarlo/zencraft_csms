<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('announcements')
            ->where('target_audience', 'college')
            ->update(['target_audience' => 'students']);

        Schema::table('announcements', function (Blueprint $table): void {
            $table->enum('target_audience', ['students', 'teachers', 'both'])
                ->default('both')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->enum('target_audience', ['students', 'college', 'teachers', 'both'])
                ->default('both')
                ->change();
        });
    }
};
