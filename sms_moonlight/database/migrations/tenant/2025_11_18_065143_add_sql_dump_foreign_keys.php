<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->foreign('student_id', 'fk_attendance_record_student_id')->references('id')->on('students');
        });

        Schema::table('advisers', function (Blueprint $table): void {
            $table->foreign('user_id', 'advisers_user_id_foreign')->references('id')->on('moonshine_users');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->foreign('user_id', 'students_user_id_foreign')->references('id')->on('moonshine_users');
        });

        Schema::table('student_access', function (Blueprint $table): void {
            $table->foreign('user_id', 'student_access_user_id_foreign')->references('id')->on('moonshine_users');
        });

        Schema::table('class_adviser_schedules', function (Blueprint $table): void {
            $table->foreign('adviser_id', 'class_adviser_schedules_ibfk_1')->references('id')->on('advisers');
        });

        Schema::table('class_students', function (Blueprint $table): void {
            $table->foreign('class_id', 'fk_class_students_class_id')->references('id')->on('classes');
            $table->foreign('student_id', 'fk_class_students_student_id')->references('id')->on('students');
        });

        Schema::table('class_student_grades', function (Blueprint $table): void {
            $table->foreign('class_id', 'fk_class_student_grades_class_id')->references('id')->on('classes');
            $table->foreign('grade_id', 'fk_class_student_grades_grade_id')->references('id')->on('grade');
            $table->foreign('student_id', 'fk_class_student_grades_student_id')->references('id')->on('students');
            $table->foreign('subject_id', 'fk_class_student_grades_subject_id')->references('id')->on('subjects');
        });

        Schema::table('class_subjects', function (Blueprint $table): void {
            $table->foreign('class_id', 'fk_class_subjects_class_id')->references('id')->on('classes');
            $table->foreign('subject_id', 'fk_class_subjects_subject_id')->references('id')->on('subjects');
        });

        Schema::table('quiz_group', function (Blueprint $table): void {
            $table->foreign('school_year_id', 'quiz_group_ibfk_1')->references('id')->on('school_year');
            $table->foreign('grade_id', 'quiz_group_ibfk_2')->references('id')->on('grade');
        });

        Schema::table('quiz_group_days', function (Blueprint $table): void {
            $table->foreign('quiz_group_id', 'quiz_group_days_ibfk_1')->references('id')->on('quiz_group')->onDelete('cascade');
        });

        Schema::table('quiz_quiz_answers', function (Blueprint $table): void {
            $table->foreign('quiz_id', 'quiz_quiz_answers_ibfk_1')->references('id')->on('quizzes');
            $table->foreign('answer_id', 'quiz_quiz_answers_ibfk_2')->references('id')->on('quiz_answers');
        });

        Schema::table('quiz_quiz_group_days', function (Blueprint $table): void {
            $table->foreign('quiz_group_days_id', 'quiz_quiz_group_days_ibfk_1')->references('id')->on('quiz_group_days');
            $table->foreign('quiz_id', 'quiz_quiz_group_days_ibfk_2')->references('id')->on('quizzes');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_quiz_group_days', function (Blueprint $table): void {
            $table->dropForeign('quiz_quiz_group_days_ibfk_1');
            $table->dropForeign('quiz_quiz_group_days_ibfk_2');
        });

        Schema::table('quiz_quiz_answers', function (Blueprint $table): void {
            $table->dropForeign('quiz_quiz_answers_ibfk_1');
            $table->dropForeign('quiz_quiz_answers_ibfk_2');
        });

        Schema::table('quiz_group_days', function (Blueprint $table): void {
            $table->dropForeign('quiz_group_days_ibfk_1');
        });

        Schema::table('quiz_group', function (Blueprint $table): void {
            $table->dropForeign('quiz_group_ibfk_1');
            $table->dropForeign('quiz_group_ibfk_2');
        });

        Schema::table('class_subjects', function (Blueprint $table): void {
            $table->dropForeign('fk_class_subjects_class_id');
            $table->dropForeign('fk_class_subjects_subject_id');
        });

        Schema::table('class_student_grades', function (Blueprint $table): void {
            $table->dropForeign('fk_class_student_grades_class_id');
            $table->dropForeign('fk_class_student_grades_grade_id');
            $table->dropForeign('fk_class_student_grades_student_id');
            $table->dropForeign('fk_class_student_grades_subject_id');
        });

        Schema::table('class_students', function (Blueprint $table): void {
            $table->dropForeign('fk_class_students_class_id');
            $table->dropForeign('fk_class_students_student_id');
        });

        Schema::table('class_adviser_schedules', function (Blueprint $table): void {
            $table->dropForeign('class_adviser_schedules_ibfk_1');
        });

        Schema::table('student_access', function (Blueprint $table): void {
            $table->dropForeign('student_access_user_id_foreign');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropForeign('students_user_id_foreign');
        });

        Schema::table('advisers', function (Blueprint $table): void {
            $table->dropForeign('advisers_user_id_foreign');
        });

        Schema::table('attendance_record', function (Blueprint $table): void {
            $table->dropForeign('fk_attendance_record_student_id');
        });
    }
};