<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['settingName' => 'teacher_student_detail_editing_enabled'],
            [
                'settingValue' => '0',
                'settingType' => 'boolean',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('settingName', 'teacher_student_detail_editing_enabled')
            ->delete();
    }
};
