@php
    $record = $collegeEnrollmentCourse;
    $student = $record->enrollment?->student;
    $course = $record->programCourse ?? $record->offering?->programCourse;
    $isSubmitted = $record->gradesAreSubmitted();
    $gradeFields = [
        'prelim_grade' => 'Prelim',
        'midterm_grade' => 'Midterm',
        'prefinal_grade' => 'Pre-final',
        'final_grade' => 'Final',
    ];
@endphp

<form
    method="POST"
    action="{{ route('teacher.college-grades.save', $record) }}"
    class="space-y-4"
>
    @csrf

    <div class="rounded-2xl border border-slate-200 p-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="font-semibold text-slate-800">
                    {{ strtoupper($student?->lastname ?? '-') }}, {{ strtoupper($student?->firstname ?? '-') }}
                </p>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $student?->lrn ?? 'No student number' }}
                    · {{ $course?->course_code ?? 'Class' }}
                    · Section {{ $record->offering?->section ?? '-' }}
                </p>
            </div>

            @if ($isSubmitted)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    Submitted {{ $record->grades_submitted_at?->format('M d, Y g:i A') }}
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

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($gradeFields as $field => $label)
            <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $label }}</span>
                <input
                    type="number"
                    name="{{ $field }}"
                    value="{{ filled($record->{$field}) ? number_format((float) $record->{$field}, 2, '.', '') : '' }}"
                    min="0"
                    max="100"
                    step="0.01"
                    inputmode="decimal"
                    data-grade-editable="1"
                    class="grade-quarter-input w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none"
                    disabled
                >
            </label>
        @endforeach
    </div>

    <label class="block">
        <span class="mb-1.5 block text-sm font-semibold text-slate-700">Remarks</span>
        <select
            name="remarks"
            data-grade-editable="1"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none"
            disabled
        >
            <option value="">Select remarks</option>
            @foreach (['Passed', 'Failed', 'Incomplete', 'Dropped'] as $remark)
                <option value="{{ $remark }}" @selected($record->remarks === $remark)>{{ $remark }}</option>
            @endforeach
        </select>
    </label>

    @if (! $isSubmitted)
        <div class="flex flex-wrap justify-end gap-3 pt-2">
            <button
                type="submit"
                name="action"
                value="save"
                id="saveGradesButton"
                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                disabled
            >
                Save Grades
            </button>
            <button
                type="submit"
                name="action"
                value="submit"
                id="submitGradesButton"
                class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                onclick="return confirm('Submit final college grades for this student? Grades and remarks cannot be edited after submission.')"
                disabled
            >
                Submit Final Grades
            </button>
        </div>
    @endif
</form>
