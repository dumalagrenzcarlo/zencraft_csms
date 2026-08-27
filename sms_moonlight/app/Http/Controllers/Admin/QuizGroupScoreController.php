<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\QuizGroup;
use App\Models\StudentQuizAnswer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuizGroupScoreController extends Controller
{
    public function show(Request $request, QuizGroup $quizGroup): View
    {
        abort_unless((bool) config('school_portal.features.quiz_module'), 404);

        $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $quizGroupDays = $quizGroup->quizGroupDays()
            ->with(['quiz_quiz_group_days.quiz.quizQuizAnswers', 'quiz_quiz_group_days.quiz'])
            ->get()
            ->sortBy(static fn ($day): int => array_search($day->day, $dayOrder, true) === false
                ? PHP_INT_MAX
                : array_search($day->day, $dayOrder, true))
            ->values();

        $selectedDay = (string) $request->string('day');
        $search = trim((string) $request->string('search'));
        $perPage = max(5, min(50, (int) $request->integer('per_page', 10)));
        $page = max(1, (int) $request->integer('page', 1));

        $answers = StudentQuizAnswer::query()
            ->with(['student', 'quiz.quizQuizAnswers', 'quizGroupDay'])
            ->whereHas('quizGroupDay', static function ($query) use ($quizGroup): void {
                $query->where('quiz_group_id', $quizGroup->id);
            })
            ->get();

        $rows = $answers
            ->groupBy(static fn (StudentQuizAnswer $answer): string => $answer->quiz_group_days_id.'-'.$answer->student_id)
            ->map(static function (Collection $group): array {
                /** @var StudentQuizAnswer $first */
                $first = $group->first();
                $student = $first->student;
                $quizGroupDay = $first->quizGroupDay;

                $totalAnswered = $group->count();
                $correctAnswers = $group->sum(static function (StudentQuizAnswer $answer): int {
                    $correctAnswerId = $answer->quiz?->quizQuizAnswers
                        ?->first(static fn ($item): bool => (bool) $item->is_correct_answer)?->answer_id;

                    return (int) $answer->answer_id === (int) $correctAnswerId ? 1 : 0;
                });
                $score = $totalAnswered > 0
                    ? round(($correctAnswers / $totalAnswered) * 100, 2)
                    : 0.0;

                return [
                    'day' => (string) ($quizGroupDay?->day ?? ''),
                    'lrn' => (string) ($student?->lrn ?? ''),
                    'name' => trim(implode(' ', array_filter([
                        (string) ($student?->firstname ?? ''),
                        (string) ($student?->middlename ?? ''),
                        (string) ($student?->lastname ?? ''),
                    ]))),
                    'total_answered' => $totalAnswered,
                    'correct_answers' => $correctAnswers,
                    'score' => $score,
                ];
            })
            ->values();

        if ($selectedDay !== '') {
            $rows = $rows->filter(static fn (array $row): bool => $row['day'] === $selectedDay)->values();
        }

        if ($search !== '') {
            $needle = Str::lower($search);

            $rows = $rows->filter(static function (array $row) use ($needle): bool {
                return Str::contains(Str::lower($row['lrn']), $needle)
                    || Str::contains(Str::lower($row['name']), $needle)
                    || Str::contains(Str::lower($row['day']), $needle);
            })->values();
        }

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
            ]
        );
        $paginator->appends($request->query());

        return view('admin.quiz-groups.scores', [
            'quizGroup' => $quizGroup,
            'quizGroupDays' => $quizGroupDays,
            'selectedDay' => $selectedDay,
            'search' => $search,
            'scores' => $paginator,
        ]);
    }
}
