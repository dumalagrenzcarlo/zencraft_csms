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
            DB::statement("ALTER TABLE classes MODIFY status VARCHAR(50) NOT NULL DEFAULT 'active'");
            DB::statement('ALTER TABLE classes MODIFY active TINYINT(1) NOT NULL DEFAULT 1');

            return;
        }

        if ($this->isPostgres()) {
            DB::statement("ALTER TABLE classes ALTER COLUMN status SET DEFAULT 'active'");
            DB::statement('ALTER TABLE classes ALTER COLUMN active SET DEFAULT true');

            return;
        }

        Schema::table('classes', function (Blueprint $table): void {
            $table->string('status', 50)->default('active')->change();
            $table->boolean('active')->default(true)->change();
        });
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        if ($this->isMysql()) {
            DB::statement('ALTER TABLE classes MODIFY status VARCHAR(50) NOT NULL');
            DB::statement('ALTER TABLE classes MODIFY active TINYINT(1) NOT NULL');

            return;
        }

        if ($this->isPostgres()) {
            DB::statement('ALTER TABLE classes ALTER COLUMN status DROP DEFAULT');
            DB::statement('ALTER TABLE classes ALTER COLUMN active DROP DEFAULT');

            return;
        }

        Schema::table('classes', function (Blueprint $table): void {
            $table->string('status', 50)->default(null)->change();
            $table->boolean('active')->default(null)->change();
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
