@php
    $classSubjects = collect($classStudent->class?->classSubjects ?? [])
        ->values();
    $termKeys = $classStudent->termKeys();
    $termLabels = [
        'q1' => 'Grading Period 1',
        'q2' => 'Grading Period 2',
        'q3' => 'Grading Period 3',
        'q4' => 'Grading Period 4',
    ];
    $isSubmitted = $classStudent->gradesAreSubmitted();

    $rows = $classSubjects
        ->map(function ($classSubject) use ($classStudent, $termKeys) {
            $grade = $classStudent->grades->firstWhere('subject_id', $classSubject->subject_id);
            $includedInAverage = (bool) ($classSubject->subject?->include_in_average ?? false);
            $termValues = [];

            foreach ($termKeys as $termKey) {
                $termValues[$termKey] = (float) ($grade->{$termKey} ?? 0);
            }

            return [
                'subject_id' => $classSubject->subject_id,
                'subject' => strtoupper($classSubject->subject->subject ?? '-'),
                'record_order' => $classSubject->subject?->record_order ?? PHP_INT_MAX,
                ...$termValues,
                'avg' => count($termValues) > 0 ? round(array_sum($termValues) / count($termValues), 2) : 0,
                'included_in_average' => $includedInAverage,
            ];
        })
        ->sortBy([
            ['record_order', 'asc'],
            ['subject', 'asc'],
        ])
        ->values();

    $includedRows = $rows
        ->filter(function ($row) use ($classSubjects) {
            $classSubject = $classSubjects->firstWhere('subject_id', $row['subject_id']);

            return (bool) ($classSubject?->subject?->include_in_average ?? false);
        })
        ->values();

    $summarySource = $includedRows->isNotEmpty() ? $includedRows : collect();

    $summary = collect($termKeys)
        ->mapWithKeys(fn ($termKey) => [$termKey => number_format((float) $summarySource->avg($termKey), 2)])
        ->put('avg', number_format((float) $summarySource->avg('avg'), 2))
        ->all();
@endphp

<form
    method="POST"
    action="{{ route('teacher.students.grades.save', $classStudent->id) }}"
    id="gradesForm"
    class="space-y-4"
>
    @csrf

    <div class="rounded-2xl border border-slate-200 p-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="font-semibold text-slate-800">
                    {{ strtoupper($classStudent->student->lastname) }},
                    {{ strtoupper($classStudent->student->firstname) }}
                </p>
                <p class="text-sm text-slate-500">
                    Student Number: {{ $classStudent->student->lrn }}
                </p>
            </div>

            @if ($isSubmitted)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    Submitted {{ $classStudent->grades_submitted_at?->format('M d, Y g:i A') }}
                </div>
            @else
                <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <input
                        type="checkbox"
                        id="editGradeToggle"
                        class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    <span class="text-sm font-semibold text-slate-700">Edit Grade</span>
                </label>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="w-full border-collapse text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="border border-slate-200 px-4 py-3 text-left font-semibold text-slate-700">Subject</th>
                    @foreach ($termKeys as $termKey)
                        <th class="border border-slate-200 px-4 py-3 text-center font-semibold text-slate-700">{{ $termLabels[$termKey] }}</th>
                    @endforeach
                    <th class="border border-slate-200 px-4 py-3 text-center font-semibold text-slate-700">AVG</th>
                </tr>
            </thead>

            <tbody class="bg-white">
                @forelse($rows as $index => $row)
                    <tr>
                        <td class="border border-slate-200 px-4 py-3 font-medium text-slate-800">
                            <div class="flex items-center gap-2">
                                <span>{{ $row['subject'] }}</span>

                                @if (! $row['included_in_average'])
                                    <span
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700"
                                        title="Not included in average"
                                        aria-label="Not included in average"
                                    >
                                        i
                                    </span>
                                @endif
                            </div>
                            <input type="hidden" name="grades[{{ $index }}][subject_id]" value="{{ $row['subject_id'] }}">
                        </td>

                        @foreach ($termKeys as $quarter)
                            <td class="border border-slate-200 px-3 py-2 text-center text-slate-700">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    inputmode="decimal"
                                    name="grades[{{ $index }}][{{ $quarter }}]"
                                    value="{{ number_format($row[$quarter], 2, '.', '') }}"
                                    data-grade-input="1"
                                    data-grade-editable="1"
                                    data-row-index="{{ $index }}"
                                    data-quarter="{{ $quarter }}"
                                    class="grade-quarter-input w-20 rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-center text-sm text-slate-700 focus:border-indigo-500 focus:outline-none"
                                    disabled
                                >
                            </td>
                        @endforeach

                        <td class="border border-slate-200 px-4 py-3 text-center font-semibold text-slate-700">
                            <span
                                data-row-average="{{ $index }}"
                                data-row-included="{{ $row['included_in_average'] ? 1 : 0 }}"
                            >
                                {{ number_format($row['avg'], 2) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($termKeys) + 2 }}" class="border border-slate-200 px-4 py-6 text-center text-slate-500">
                            No subjects or grades found.
                        </td>
                    </tr>
                @endforelse

                @if($rows->isNotEmpty())
                    <tr class="bg-slate-50 font-semibold">
                        <td class="border border-slate-200 px-4 py-3 text-slate-800">Average</td>
                        @foreach ($termKeys as $termKey)
                            <td class="border border-slate-200 px-4 py-3 text-center text-slate-700">
                                <span data-summary-quarter="{{ $termKey }}">{{ $summary[$termKey] }}</span>
                            </td>
                        @endforeach
                        <td class="border border-slate-200 px-4 py-3 text-center text-slate-700">
                            <span data-summary-quarter="avg">{{ $summary['avg'] }}</span>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if (! $isSubmitted)
        <div class="flex flex-wrap justify-end gap-3 pt-2">
            <button
                type="submit"
                name="action"
                value="save"
                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                id="saveGradesButton"
                disabled
            >
                Save Grades
            </button>

            <button
                type="submit"
                name="action"
                value="submit"
                class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                id="submitGradesButton"
                disabled
                onclick="return confirm('Submit final grades for this student? Grades cannot be edited after submission.')"
            >
                Submit Final Grades
            </button>
        </div>
    @endif
</form>
