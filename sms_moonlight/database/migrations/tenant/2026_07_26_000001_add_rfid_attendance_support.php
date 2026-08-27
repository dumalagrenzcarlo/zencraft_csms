<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('rfid_card_uid', 100)->nullable()->unique()->after('lrn');
        });

        Schema::table('advisers', function (Blueprint $table): void {
            $table->string('rfid_card_uid', 100)->nullable()->unique()->after('user_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('attendance_record', function (Blueprint $table): void {
                $table->dropForeign('fk_attendance_record_student_id');
            });
        }

        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->unsignedBigInteger('adviser_id')->nullable()->after('student_id');
            $table->string('source', 20)->default('manual')->after('logged_time');

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('student_id', 'fk_attendance_record_student_id')
                    ->references('id')
                    ->on('students');
            }

            $table->foreign('adviser_id', 'fk_attendance_record_adviser_id')
                ->references('id')
                ->on('advisers');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_record', function (Blueprint $table): void {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('fk_attendance_record_adviser_id');
                $table->dropForeign('fk_attendance_record_student_id');
            }

            $table->dropColumn(['adviser_id', 'source']);
        });

        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id')->nullable(false)->change();

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('student_id', 'fk_attendance_record_student_id')
                    ->references('id')
                    ->on('students');
            }
        });

        Schema::table('advisers', function (Blueprint $table): void {
            $table->dropUnique(['rfid_card_uid']);
            $table->dropColumn('rfid_card_uid');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique(['rfid_card_uid']);
            $table->dropColumn('rfid_card_uid');
        });
    }
};
