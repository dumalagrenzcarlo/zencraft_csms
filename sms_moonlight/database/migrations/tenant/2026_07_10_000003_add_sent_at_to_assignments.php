<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            $table->dateTime('sent_at')->nullable()->after('deadline');
            $table->index('sent_at');
        });

        DB::table('assignments')
            ->whereNull('sent_at')
            ->update([
                'sent_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            $table->dropIndex(['sent_at']);
            $table->dropColumn('sent_at');
        });
    }
};
