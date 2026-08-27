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
            DB::statement('ALTER TABLE students MODIFY birthplace VARCHAR(400) NULL');

            return;
        }

        if ($this->isPostgres()) {
            DB::statement('ALTER TABLE students ALTER COLUMN birthplace TYPE VARCHAR(400)');

            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->string('birthplace', 400)->nullable()->change();
        });
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        if ($this->isMysql()) {
            DB::statement('ALTER TABLE students MODIFY birthplace VARCHAR(50) NULL');

            return;
        }

        if ($this->isPostgres()) {
            DB::statement('ALTER TABLE students ALTER COLUMN birthplace TYPE VARCHAR(50)');

            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->string('birthplace', 50)->nullable()->change();
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
