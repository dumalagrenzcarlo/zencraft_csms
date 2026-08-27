<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('students')->whereNotNull('user_id')->orderBy('id')->get() as $student) {
            DB::table('student_access')->updateOrInsert(
                ['student_id' => $student->id],
                [
                    'user_id' => $student->user_id,
                    'active' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        //
    }
};
