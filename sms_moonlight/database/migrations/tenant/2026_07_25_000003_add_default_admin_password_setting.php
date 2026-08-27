<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        if (DB::table('settings')->where('settingName', 'default_config_admin_password')->exists()) {
            return;
        }

        DB::table('settings')->insert([
            'settingName' => 'default_config_admin_password',
            'settingValue' => 'admin123',
            'settingType' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('settingName', 'default_config_admin_password')
            ->delete();
    }
};
