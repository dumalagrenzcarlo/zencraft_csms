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
                ...$termValues,
                'avg' => count($termValues) > 0 ? round(array_sum($termValues) / count($termValues), 2) : 0,
                'included_in_average' => $includedInAverage,
            ];
        })
        ->sortBy('subject')
        ->values();

    $includedRows = $rows->filter(fn ($row) => $row['included_in_average'])->values();
    $summarySource = $includedRows->isNotEmpty() ? $includedRows : collect();

    $summary = collect($termKeys)
        ->mapWithKeys(fn ($termKey) => [$termKey => number_format((float) $summarySource->avg($termKey), 2)])
        ->put('avg', number_format((float) $summarySource->avg('avg'), 2))
        ->all();
@endphp

<div class="space-y-4">
    <!-- <div class="rounded-2xl border border-slate-200 p-4">
        <p class="font-semibold text-slate-800">
            {{ strtoupper($classStudent->student->lastname) }},
            {{ strtoupper($classStudent->student->firstname) }}
        </p>
        <p class="text-sm text-slate-500">
            Student Number: {{ $classStudent->student->lrn }}
        </p>
        <p class="text-sm text-slate-500">
            School Year: {{ $classStudent->schoolYear->school_year ?? '-' }}
        </p>
        <p class="text-sm text-slate-500">
            Grade & Section: {{ $classStudent->class->grade->grade ?? '-' }} - {{ $classStudent->class->section ?? '-' }}
        </p>
    </div> -->

    @if ($classStudent->hidden_grade)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800">
            Your grade hidden. Please visit your class adviser for more details
        </div>
    @else
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
                    @forelse($rows as $row)
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
                            </td>

                            @foreach ($termKeys as $termKey)
                                <td class="border border-slate-200 px-4 py-3 text-center text-slate-700">{{ number_format($row[$termKey], 2) }}</td>
                            @endforeach
                            <td class="border border-slate-200 px-4 py-3 text-center font-semibold text-slate-700">{{ number_format($row['avg'], 2) }}</td>
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
                                <td class="border border-slate-200 px-4 py-3 text-center text-slate-700">{{ $summary[$termKey] }}</td>
                            @endforeach
                            <td class="border border-slate-200 px-4 py-3 text-center text-slate-700">{{ $summary['avg'] }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif
</div>
