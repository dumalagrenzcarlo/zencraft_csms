<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        if ($this->isMysql()) {
            DB::statement('ALTER TABLE students MODIFY address VARCHAR(400) NULL');
            DB::statement('ALTER TABLE students MODIFY parent_guardian_address VARCHAR(400) NULL');
            DB::statement('ALTER TABLE students MODIFY elementary_school_address VARCHAR(400) NULL');

            return;
        }

        if ($this->isPostgres()) {
            DB::statement('ALTER TABLE students ALTER COLUMN address TYPE VARCHAR(400)');
            DB::statement('ALTER TABLE students ALTER COLUMN parent_guardian_address TYPE VARCHAR(400)');
            DB::statement('ALTER TABLE students ALTER COLUMN elementary_school_address TYPE VARCHAR(400)');

            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->string('address', 400)->nullable()->change();
            $table->string('parent_guardian_address', 400)->nullable()->change();
            $table->string('elementary_school_address', 400)->nullable()->change();
        });
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        if ($this->isMysql()) {
            DB::statement('ALTER TABLE students MODIFY address VARCHAR(20) NULL');
            DB::statement('ALTER TABLE students MODIFY parent_guardian_address VARCHAR(60) NULL');
            DB::statement('ALTER TABLE students MODIFY elementary_school_address VARCHAR(300) NULL');

            return;
        }

        if ($this->isPostgres()) {
            DB::statement('ALTER TABLE students ALTER COLUMN address TYPE VARCHAR(20)');
            DB::statement('ALTER TABLE students ALTER COLUMN parent_guardian_address TYPE VARCHAR(60)');
            DB::statement('ALTER TABLE students ALTER COLUMN elementary_school_address TYPE VARCHAR(300)');

            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->string('address', 20)->nullable()->change();
            $table->string('parent_guardian_address', 60)->nullable()->change();
            $table->string('elementary_school_address', 300)->nullable()->change();
        });
    }

    private function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    private function isMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function isPostgres(): bool
    {
        return DB::getDriverName() === 'pgsql';
    }
};
