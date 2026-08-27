<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_students', function (Blueprint $table): void {
            $table->index(['school_year_id', 'student_id'], 'class_students_school_year_student_idx');
        });

        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->index(['student_id', 'currentdate'], 'attendance_student_date_idx');
        });

        Schema::table('student_payment_histories', function (Blueprint $table): void {
            $table->index(['student_id', 'payment_type_id'], 'student_payments_student_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('class_students', function (Blueprint $table): void {
            $table->dropIndex('class_students_school_year_student_idx');
        });

        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->dropIndex('attendance_student_date_idx');
        });

        Schema::table('student_payment_histories', function (Blueprint $table): void {
            $table->dropIndex('student_payments_student_type_idx');
        });
    }
};
