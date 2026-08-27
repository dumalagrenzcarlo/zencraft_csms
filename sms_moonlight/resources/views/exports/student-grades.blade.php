<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.4; }
        .grade-report { page-break-after: always; }
        .grade-report:last-child { page-break-after: auto; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 14px; padding-bottom: 9px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .meta { margin-bottom: 14px; width: 100%; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .meta .label { color: #6b7280; width: 85px; }
        table.grades { border-collapse: collapse; width: 100%; }
        table.grades th, table.grades td { border: 1px solid #d1d5db; padding: 6px; }
        table.grades th { background: #1f2937; color: #fff; text-align: center; }
        table.grades td { text-align: center; }
        table.grades td.subject { text-align: left; }
        tr.average td { background: #f3f4f6; font-weight: bold; }
        .note { color: #6b7280; font-size: 9px; margin-top: 10px; }
    </style>
</head>
<body>
@foreach ($classStudents as $classStudent)
    @php
        $classStudent->loadMissing(['student', 'class.grade', 'class.classSubjects.subject', 'schoolYear', 'grades.subject']);
        $termKeys = $classStudent->termKeys();
        $termLabels = [
            'q1' => 'Grading Period 1',
            'q2' => 'Grading Period 2',
            'q3' => 'Grading Period 3',
            'q4' => 'Grading Period 4',
        ];
        $rows = collect($classStudent->class?->classSubjects ?? [])
            ->map(function ($classSubject) use ($classStudent, $termKeys) {
                $grade = $classStudent->grades->firstWhere('subject_id', $classSubject->subject_id);
                $termValues = [];
                foreach ($termKeys as $termKey) {
                    $termValues[$termKey] = (float) ($grade->{$termKey} ?? 0);
                }
                return [
                    'subject' => strtoupper((string) ($classSubject->subject?->subject ?? '-')),
                    ...$termValues,
                    'avg' => count($termValues) ? round(array_sum($termValues) / count($termValues), 2) : 0,
                    'included_in_average' => (bool) ($classSubject->subject?->include_in_average ?? false),
                ];
            })
            ->sortBy('subject')
            ->values();
        $summarySource = $rows->filter(fn ($row) => $row['included_in_average'])->values();
        $summary = collect($termKeys)
            ->mapWithKeys(fn ($termKey) => [$termKey => number_format((float) $summarySource->avg($termKey), 2)])
            ->put('avg', number_format((float) $summarySource->avg('avg'), 2))
            ->all();
        $student = $classStudent->student;
        $class = $classStudent->class;
    @endphp
    <section class="grade-report">
        <div class="header">
            <h1>Student Grades</h1>
            <div class="muted">Generated {{ now()->format('F j, Y g:i A') }}</div>
        </div>
        <table class="meta">
            <tr>
                <td class="label">Student</td>
                <td>{{ strtoupper($student->lastname ?? '') }}, {{ strtoupper($student->firstname ?? '') }}</td>
                <td class="label">Student Number</td>
                <td>{{ $student->lrn ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Class</td>
                <td>{{ $class->grade->grade ?? '-' }} - {{ $class->section ?? '-' }}</td>
                <td class="label">School Year</td>
                <td>{{ $classStudent->schoolYear->school_year ?? '-' }}</td>
            </tr>
        </table>
        <table class="grades">
            <thead>
                <tr>
                    <th style="text-align:left">Subject</th>
                    @foreach ($termKeys as $termKey)
                        <th>{{ $termLabels[$termKey] }}</th>
                    @endforeach
                    <th>Average</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="subject">
                            {{ $row['subject'] }}
                            @unless ($row['included_in_average'])
                                <span class="muted">(not included in average)</span>
                            @endunless
                        </td>
                        @foreach ($termKeys as $termKey)
                            <td>{{ number_format($row[$termKey], 2) }}</td>
                        @endforeach
                        <td>{{ number_format($row['avg'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($termKeys) + 2 }}">No subjects or grades found.</td></tr>
                @endforelse
                @if ($rows->isNotEmpty())
                    <tr class="average">
                        <td class="subject">Average</td>
                        @foreach ($termKeys as $termKey)
                            <td>{{ $summary[$termKey] }}</td>
                        @endforeach
                        <td>{{ $summary['avg'] }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
        <div class="note">This PDF reflects the grade terms configured for {{ $class->grade->grade ?? 'this grade level' }}.</div>
    </section>
@endforeach
</body>
</html>
