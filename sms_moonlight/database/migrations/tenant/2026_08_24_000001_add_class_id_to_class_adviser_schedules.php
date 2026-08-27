<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_adviser_schedules', function (Blueprint $table): void {
            $table->foreignId('class_id')
                ->nullable()
                ->after('adviser_id')
                ->constrained('classes')
                ->nullOnDelete();
        });

        DB::table('class_adviser_schedules')
            ->orderBy('id')
            ->each(function ($schedule): void {
                $classIds = DB::table('classes')
                    ->where('adviser_id', $schedule->adviser_id)
                    ->where('section', $schedule->section)
                    ->limit(2)
                    ->pluck('id');

                if ($classIds->count() === 1) {
                    DB::table('class_adviser_schedules')
                        ->where('id', $schedule->id)
                        ->update(['class_id' => $classIds->first()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('class_adviser_schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('class_id');
        });
    }
};
