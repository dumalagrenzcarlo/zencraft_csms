<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quiz_quiz_group_days')
            ->orderByDesc('id')
            ->get(['id', 'quiz_group_days_id', 'quiz_id'])
            ->groupBy(fn ($row): string => $row->quiz_group_days_id.'-'.$row->quiz_id)
            ->each(function ($rows): void {
                DB::table('quiz_quiz_group_days')
                    ->whereIn('id', $rows->skip(1)->pluck('id'))
                    ->delete();
            });

        DB::table('student_quiz_answers')
            ->orderByDesc('id')
            ->get(['id', 'quiz_group_days_id', 'quiz_id', 'student_id'])
            ->groupBy(fn ($row): string => $row->quiz_group_days_id.'-'.$row->quiz_id.'-'.$row->student_id)
            ->each(function ($rows): void {
                DB::table('student_quiz_answers')
                    ->whereIn('id', $rows->skip(1)->pluck('id'))
                    ->delete();
            });

        Schema::table('quiz_quiz_group_days', function (Blueprint $table): void {
            $table->unique(
                ['quiz_group_days_id', 'quiz_id'],
                'quiz_day_question_unique'
            );
        });

        Schema::table('student_quiz_answers', function (Blueprint $table): void {
            $table->unique(
                ['quiz_group_days_id', 'quiz_id', 'student_id'],
                'student_quiz_answer_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('student_quiz_answers', function (Blueprint $table): void {
            $table->dropUnique('student_quiz_answer_unique');
        });

        Schema::table('quiz_quiz_group_days', function (Blueprint $table): void {
            $table->dropUnique('quiz_day_question_unique');
        });
    }
};
