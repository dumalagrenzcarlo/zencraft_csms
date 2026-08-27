<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payment_histories', function (Blueprint $table): void {
            $table->dateTime('payment_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_payment_histories', function (Blueprint $table): void {
            $table->date('payment_date')->nullable()->change();
        });
    }
};
