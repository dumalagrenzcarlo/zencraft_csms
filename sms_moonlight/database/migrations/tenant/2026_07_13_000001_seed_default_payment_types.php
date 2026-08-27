<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['Cash', 'Credit'] as $paymentType) {
            DB::table('payment_types')->updateOrInsert(
                ['name' => $paymentType],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        // Keep these shared defaults on rollback because they may already have
        // payments assigned or may have existed before this migration ran.
    }
};
