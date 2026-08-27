<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moonshine_users', function (Blueprint $table): void {
            if (! Schema::hasColumn('moonshine_users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('password');
            }
        });

        DB::table('moonshine_users')
            ->whereIn('moonshine_user_role_id', [2, 3])
            ->update(['must_change_password' => true]);

        Schema::table('grade', function (Blueprint $table): void {
            if (! Schema::hasColumn('grade', 'term_count')) {
                $table->unsignedTinyInteger('term_count')->default(4)->after('status');
            }
        });

        Schema::table('class_students', function (Blueprint $table): void {
            if (! Schema::hasColumn('class_students', 'grades_submitted_at')) {
                $table->timestamp('grades_submitted_at')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('class_students', 'grades_submitted_by')) {
                $table->unsignedBigInteger('grades_submitted_by')->nullable()->after('grades_submitted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_students', function (Blueprint $table): void {
            if (Schema::hasColumn('class_students', 'grades_submitted_by')) {
                $table->dropColumn('grades_submitted_by');
            }

            if (Schema::hasColumn('class_students', 'grades_submitted_at')) {
                $table->dropColumn('grades_submitted_at');
            }
        });

        Schema::table('grade', function (Blueprint $table): void {
            if (Schema::hasColumn('grade', 'term_count')) {
                $table->dropColumn('term_count');
            }
        });

        Schema::table('moonshine_users', function (Blueprint $table): void {
            if (Schema::hasColumn('moonshine_users', 'must_change_password')) {
                $table->dropColumn('must_change_password');
            }
        });
    }
};
