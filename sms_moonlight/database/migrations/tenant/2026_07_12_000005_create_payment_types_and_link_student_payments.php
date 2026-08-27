<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_types')) {
            Schema::create('payment_types', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('student_payment_histories', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_payment_histories', 'payment_type_id')) {
                $table->unsignedBigInteger('payment_type_id')->nullable()->after('student_id');
                $table->index('payment_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_payment_histories', function (Blueprint $table): void {
            if (Schema::hasColumn('student_payment_histories', 'payment_type_id')) {
                $table->dropIndex(['payment_type_id']);
                $table->dropColumn('payment_type_id');
            }
        });

        Schema::dropIfExists('payment_types');
    }
};
