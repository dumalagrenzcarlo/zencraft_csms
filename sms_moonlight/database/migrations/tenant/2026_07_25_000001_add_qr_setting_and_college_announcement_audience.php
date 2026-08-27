<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['settingName' => 'qr_code_enabled'],
            [
                'settingValue' => '1',
                'settingType' => 'boolean',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Schema::table('announcements', function (Blueprint $table): void {
            $table->enum('target_audience', ['students', 'college', 'teachers', 'both'])
                ->default('both')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('announcements')
            ->where('target_audience', 'college')
            ->update(['target_audience' => 'students']);

        Schema::table('announcements', function (Blueprint $table): void {
            $table->enum('target_audience', ['students', 'teachers', 'both'])
                ->default('both')
                ->change();
        });

        DB::table('settings')->where('settingName', 'qr_code_enabled')->delete();
    }
};
