@php
    $historyCourses = $historyEnrollment?->courses
        ?->sortBy(fn ($course) => $course->programCourse?->course_order)
        ->values() ?? collect();
    $historySemester = $historyEnrollment
        ? (\App\Models\CollegeProgramCourse::SEMESTERS[$historyEnrollment->semester] ?? 'Semester '.$historyEnrollment->semester)
        : null;
@endphp

<div class="border-b border-slate-200 p-4 sm:p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-xl font-black text-slate-900">Class History</h2>
            <p class="mt-1 text-sm text-slate-500">Review classes and released grades from your previous academic records.</p>
        </div>

        <form method="GET" action="{{ route('student.dashboard') }}" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-2xl">
            <input type="hidden" name="tab" value="history">
            <label for="history-enrollment" class="sr-only">Select semester record</label>
            <select id="history-enrollment" name="history_enrollment" class="min-h-11 min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:outline-none">
                @foreach ($collegeEnrollmentHistory as $enrollmentRecord)
                    <option value="{{ $enrollmentRecord->id }}" @selected((int) $historyEnrollment?->id === (int) $enrollmentRecord->id)>
                        {{ $enrollmentRecord->schoolYear?->school_year ?? 'School Year' }} ·
                        {{ $enrollmentRecord->program?->code ?? 'Course' }} ·
                        {{ \App\Models\CollegeProgramCourse::yearLevelLabel($enrollmentRecord->year_level) }} ·
                        {{ \App\Models\CollegeProgramCourse::SEMESTERS[$enrollmentRecord->semester] ?? 'Semester' }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-500">View record</button>
        </form>
    </div>
</div>

@if ($historyEnrollment)
    <div class="grid gap-3 border-b border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-5">
        <div><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">School Year</p><p class="mt-1 font-bold text-slate-800">{{ $historyEnrollment->schoolYear?->school_year ?? '-' }}</p></div>
        <div><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Course</p><p class="mt-1 font-bold text-slate-800">{{ $historyEnrollment->program?->code ?? '-' }}</p></div>
        <div><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Year Level</p><p class="mt-1 font-bold text-slate-800">{{ \App\Models\CollegeProgramCourse::yearLevelLabel($historyEnrollment->year_level) }}</p></div>
        <div><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Semester</p><p class="mt-1 font-bold text-slate-800">{{ $historySemester }}</p></div>
        <div><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Status</p><p class="mt-1 font-bold text-slate-800">{{ ucfirst($historyEnrollment->status) }}</p></div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1040px] w-full border-collapse text-left text-sm">
            <caption class="sr-only">Classes and grades for the selected semester</caption>
            <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">Class</th>
                    <th class="px-4 py-3">Instructor</th>
                    <th class="px-4 py-3">Prelim</th>
                    <th class="px-4 py-3">Midterm</th>
                    <th class="px-4 py-3">Pre-final</th>
                    <th class="px-4 py-3">Final</th>
                    <th class="px-4 py-3">Remarks</th>
                    <th class="px-5 py-3 text-right">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($historyCourses as $historyCourse)
                    @php($gradesReleased = $historyCourse->gradesAreSubmitted())
                    <tr class="align-top transition hover:bg-slate-50/80">
                        <td class="px-5 py-4">
                            <p class="font-black text-slate-900">{{ $historyCourse->programCourse?->course_code ?? 'Class' }}</p>
                            <p class="mt-1 max-w-xs text-slate-600">{{ $historyCourse->programCourse?->description ?? 'Description not set' }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $historyCourse->offering?->section ?? 'No section' }}</p>
                        </td>
                        <td class="px-4 py-4 font-semibold text-slate-700">{{ $historyCourse->offering?->instructor?->name ?? 'Not assigned' }}</td>
                        <td class="px-4 py-4">{{ $gradesReleased ? ($historyCourse->prelim_grade ?? '-') : 'Not released' }}</td>
                        <td class="px-4 py-4">{{ $gradesReleased ? ($historyCourse->midterm_grade ?? '-') : 'Not released' }}</td>
                        <td class="px-4 py-4">{{ $gradesReleased ? ($historyCourse->prefinal_grade ?? '-') : 'Not released' }}</td>
                        <td class="px-4 py-4 font-bold text-slate-900">{{ $gradesReleased ? ($historyCourse->final_grade ?? '-') : 'Not released' }}</td>
                        <td class="px-4 py-4">{{ $gradesReleased ? ($historyCourse->remarks ?: '-') : '-' }}</td>
                        <td class="px-5 py-4 text-right">
                            <button type="button" onclick="openCollegeGradesModal('{{ $historyCourse->id }}')" class="inline-flex min-h-10 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100">View Grades</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center"><p class="font-semibold text-slate-800">No classes recorded</p><p class="mt-1 text-sm text-slate-500">This semester record does not have assigned classes.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="px-5 py-12 text-center"><p class="font-semibold text-slate-800">No semester history found</p><p class="mt-1 text-sm text-slate-500">Previous college enrollment records will appear here.</p></div>
@endif
