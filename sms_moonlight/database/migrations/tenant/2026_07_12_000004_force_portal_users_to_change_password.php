<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('moonshine_users') || ! Schema::hasColumn('moonshine_users', 'must_change_password')) {
            return;
        }

        DB::table('moonshine_users')
            ->whereIn('moonshine_user_role_id', [2, 3])
            ->update(['must_change_password' => true]);

        if (Schema::hasTable('advisers') && Schema::hasColumn('advisers', 'user_id')) {
            DB::table('moonshine_users')
                ->whereIn('id', DB::table('advisers')->select('user_id')->whereNotNull('user_id'))
                ->update(['must_change_password' => true]);
        }

        if (Schema::hasTable('student_access') && Schema::hasColumn('student_access', 'user_id')) {
            DB::table('moonshine_users')
                ->whereIn('id', DB::table('student_access')->select('user_id')->whereNotNull('user_id'))
                ->update(['must_change_password' => true]);
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'user_id')) {
            DB::table('moonshine_users')
                ->whereIn('id', DB::table('students')->select('user_id')->whereNotNull('user_id'))
                ->update(['must_change_password' => true]);
        }
    }

    public function down(): void
    {
        //
    }
};
