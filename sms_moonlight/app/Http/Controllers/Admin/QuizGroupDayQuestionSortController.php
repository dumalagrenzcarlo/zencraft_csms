<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\QuizGroupDay;
use App\Models\QuizQuizGroupDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizGroupDayQuestionSortController extends Controller
{
    public function store(Request $request, QuizGroupDay $quizGroupDay): JsonResponse
    {
        abort_unless((bool) config('school_portal.features.quiz_module'), 404);

        $payload = (string) $request->input('data', '');
        $ids = array_values(array_filter(array_map('trim', explode(',', $payload)), static fn (string $value): bool => $value !== '' && ctype_digit($value)));

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['data' => 'Question order contains duplicate records.']);
        }

        $existingIds = QuizQuizGroupDay::query()
            ->where('quiz_group_days_id', $quizGroupDay->id)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
        $requestedIds = collect($ids)->map(static fn ($id): int => (int) $id)->sort()->values()->all();

        if ($requestedIds !== $existingIds) {
            throw ValidationException::withMessages([
                'data' => 'Question order must contain every question assigned to this quiz day.',
            ]);
        }

        DB::transaction(function () use ($ids, $quizGroupDay): void {
            foreach ($ids as $position => $id) {
                QuizQuizGroupDay::query()
                    ->where('quiz_group_days_id', $quizGroupDay->id)
                    ->whereKey((int) $id)
                    ->update([
                        'record_order' => $position + 1,
                    ]);
            }
        });

        return response()->json([
            'message' => 'Question order updated successfully.',
        ]);
    }
}
