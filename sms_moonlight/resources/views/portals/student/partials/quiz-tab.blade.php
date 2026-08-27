@if ($todayQuizzes->isEmpty())
    <div class="px-6 py-16 text-center">
        <p class="text-lg font-semibold text-slate-900">
            No quiz today. come back tomorrow
        </p>
        <p class="mt-2 text-sm text-slate-500">
            Your adviser has not assigned a quiz for today yet.
        </p>
    </div>
@else
    <div class="space-y-4 p-4 sm:p-6">
        @foreach ($todayQuizzes as $quizGroupDay)
            <div class="rounded-2xl border border-slate-200 p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Today
                        </p>
                        <h3 class="mt-1 text-xl font-bold text-slate-900">
                            {{ $quizGroupDay->title ?? 'Quiz of the Day' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $quizGroupDay->day }}
                            -
                            {{ $quizGroupDay->quizGroup->grade->grade ?? '-' }}
                            -
                            {{ $quizGroupDay->quizGroup->schoolYear->school_year ?? '-' }}
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('student.quiz.submit', ['quizGroupDay' => $quizGroupDay]) }}" class="mt-4 space-y-4">
                    @csrf

                    @foreach ($quizGroupDay->quiz_quiz_group_days as $quizLink)
                        @php
                            $quiz = $quizLink->quiz;
                            $answerOptions = collect($quiz?->quizQuizAnswers ?? []);
                            $existingAnswer = $quizzes->first(fn ($answer) => (int) $answer->quiz_group_days_id === (int) $quizGroupDay->id && (int) $answer->quiz_id === (int) $quizLink->quiz_id);
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="font-semibold text-slate-900">
                                {{ $quiz->question ?? '-' }}
                            </p>

                            <div class="mt-3 grid gap-2">
                                @foreach($answerOptions as $option)
                                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                        <input
                                            type="radio"
                                            name="answers[{{ $quizLink->quiz_id }}]"
                                            value="{{ $option->answer_id }}"
                                            required
                                            @checked((int) ($existingAnswer?->answer_id ?? 0) === (int) $option->answer_id)
                                            class="text-indigo-600 focus:ring-indigo-500">
                                        <span>{{ $option->answer->answer ?? '-' }}</span>
                                    </label>
                                @endforeach

                                @if($answerOptions->isEmpty())
                                    <p class="text-sm text-slate-500">No answers linked yet.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if($quizGroupDay->quiz_quiz_group_days->isEmpty())
                        <p class="text-sm text-slate-500">
                            No quiz questions linked yet.
                        </p>
                    @endif

                    @if($quizGroupDay->quiz_quiz_group_days->isNotEmpty())
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500">
                                Submit Quiz
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        @endforeach
    </div>
@endif
