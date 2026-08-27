<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table): void {
            $table->boolean('is_college')->default(false)->after('active');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->string('tshirt_size', 20)->nullable()->after('elementary_school_citation');
        });

        Schema::create('student_payment_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('student_id');
        });

        DB::table('settings')->updateOrInsert(
            ['settingName' => 'use_jhs_fields'],
            ['settingValue' => '0', 'settingType' => 'boolean', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payment_histories');

        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('tshirt_size');
        });

        Schema::table('classes', function (Blueprint $table): void {
            $table->dropColumn('is_college');
        });

        DB::table('settings')->where('settingName', 'use_jhs_fields')->delete();
    }
};
