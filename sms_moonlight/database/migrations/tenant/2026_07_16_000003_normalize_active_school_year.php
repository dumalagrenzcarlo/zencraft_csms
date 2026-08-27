<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $activeId = DB::table('school_year')
            ->where('active', true)
            ->orderByDesc('id')
            ->value('id');

        if ($activeId === null) {
            return;
        }

        DB::table('school_year')
            ->where('id', '!=', $activeId)
            ->where('active', true)
            ->update(['active' => false]);
    }

    public function down(): void
    {
        // Previous active states cannot be reconstructed safely.
    }
};
