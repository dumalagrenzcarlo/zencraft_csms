@php
    $record = $collegeEnrollmentCourse;
    $class = $record->programCourse;
    $offering = $record->offering;
    $gradesReleased = $record->gradesAreSubmitted();
@endphp

<div class="space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-[var(--hanan-primary)]">
                    {{ $class?->course_code ?? 'Class' }}
                </p>
                <h4 class="mt-1 text-lg font-bold text-slate-900">
                    {{ $class?->description ?? 'College class' }}
                </h4>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $offering?->section ? 'Section '.$offering->section : 'Section not assigned' }}
                    · {{ $offering?->instructor?->name ?? 'Instructor not assigned' }}
                </p>
            </div>

            <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-bold {{ $gradesReleased ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $gradesReleased ? 'Grades released' : 'Not yet released' }}
            </span>
        </div>
    </div>

    @if ($gradesReleased)
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-[650px] w-full text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Prelim</th>
                        <th class="px-4 py-3 text-left">Midterm</th>
                        <th class="px-4 py-3 text-left">Pre-final</th>
                        <th class="px-4 py-3 text-left">Final</th>
                        <th class="px-4 py-3 text-left">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="font-semibold text-slate-800">
                        <td class="px-4 py-4">{{ $record->prelim_grade ?? '-' }}</td>
                        <td class="px-4 py-4">{{ $record->midterm_grade ?? '-' }}</td>
                        <td class="px-4 py-4">{{ $record->prefinal_grade ?? '-' }}</td>
                        <td class="px-4 py-4">{{ $record->final_grade ?? '-' }}</td>
                        <td class="px-4 py-4">{{ $record->remarks ?: '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-amber-300 bg-amber-50 px-5 py-8 text-center">
            <p class="font-semibold text-amber-900">Grades are not yet available.</p>
            <p class="mt-1 text-sm text-amber-700">Your instructor will release them after grading is complete.</p>
        </div>
    @endif
</div>
