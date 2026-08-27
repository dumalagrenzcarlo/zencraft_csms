<div>
    <div class="border-b border-slate-200 p-4 sm:p-5">
        <h3 class="text-lg font-semibold text-slate-900">College Gradebook</h3>
        <p class="mt-1 text-sm text-slate-500">
            Select a class first, then search and filter its students to manage their grades.
        </p>
    </div>

    <div class="border-b border-slate-200 bg-slate-50/70 p-4 sm:p-5">
        <form method="GET" action="{{ route('teacher.dashboard') }}" class="grid gap-3 lg:grid-cols-[minmax(240px,1.4fr)_minmax(220px,1fr)_170px_auto]">
            <input type="hidden" name="context" value="instructor">
            <input type="hidden" name="tab" value="college-grades">

            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500">College class</span>
                <select
                    name="college_class_id"
                    onchange="this.form.submit()"
                    class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 focus:border-[var(--hanan-primary)] focus:outline-none"
                >
                    <option value="">Select a class</option>
                    @foreach ($collegeSchedules as $offering)
                        @php
                            $course = $offering->programCourse;
                        @endphp
                        <option value="{{ $offering->id }}" @selected((int) $selectedCollegeClass?->id === (int) $offering->id)>
                            {{ $course?->course_code ?? 'Class' }} · {{ $course?->description ?? 'College class' }} · {{ $offering->section }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Search students</span>
                <input
                    type="search"
                    name="college_grade_search"
                    value="{{ $collegeGradeSearch }}"
                    placeholder="Name or student number"
                    @disabled(! $selectedCollegeClass)
                    class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-[var(--hanan-primary)] focus:outline-none disabled:bg-slate-100 disabled:text-slate-400"
                >
            </label>

            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Grade status</span>
                <select
                    name="college_grade_status"
                    @disabled(! $selectedCollegeClass)
                    class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 focus:border-[var(--hanan-primary)] focus:outline-none disabled:bg-slate-100 disabled:text-slate-400"
                >
                    <option value="">All statuses</option>
                    <option value="draft" @selected($collegeGradeStatus === 'draft')>Draft</option>
                    <option value="submitted" @selected($collegeGradeStatus === 'submitted')>Submitted</option>
                </select>
            </label>

            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    @disabled(! $selectedCollegeClass)
                    class="min-h-11 rounded-xl bg-[var(--hanan-primary)] px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Apply
                </button>
                @if ($selectedCollegeClass && ($collegeGradeSearch !== '' || $collegeGradeStatus !== ''))
                    <a
                        href="{{ route('teacher.dashboard', ['context' => 'instructor', 'tab' => 'college-grades', 'college_class_id' => $selectedCollegeClass->id]) }}"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="p-4 sm:p-5">
        @if ($collegeSchedules->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500">
                No college classes are assigned to this instructor.
            </div>
        @elseif (! $selectedCollegeClass)
            <div class="rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/50 px-6 py-12 text-center">
                <p class="font-semibold text-slate-800">Select a class to view its students</p>
                <p class="mt-1 text-sm text-slate-500">Use the College class filter above to open a gradebook.</p>
            </div>
        @else
            @php
                $course = $selectedCollegeClass->programCourse;
                $totalStudents = $collegeGradeRecords->where('offering_id', $selectedCollegeClass->id)->count();
            @endphp

            <section class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="flex flex-col gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h4 class="font-bold text-slate-900">
                            {{ $course?->course_code ?? 'Class' }} - {{ $course?->description ?? 'College class' }}
                        </h4>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $course?->program?->code ?? 'College' }}
                            · {{ \App\Models\CollegeProgramCourse::yearLevelLabel($course?->year_level) }}
                            · {{ \App\Models\CollegeProgramCourse::SEMESTERS[$course?->semester] ?? 'Semester' }}
                            · Section {{ $selectedCollegeClass->section }}
                        </p>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">
                        {{ $filteredCollegeGradeRecords->count() }} of {{ $totalStudents }} student{{ $totalStudents === 1 ? '' : 's' }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[900px] w-full text-sm">
                        <thead class="bg-white">
                            <tr class="border-b border-slate-200">
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Prelim</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Midterm</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Pre-final</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Final</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Remarks</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($filteredCollegeGradeRecords as $record)
                                @php
                                    $student = $record->enrollment?->student;
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-3">
                                        <p class="font-semibold text-slate-900">
                                            {{ strtoupper($student?->lastname ?? '-') }}, {{ strtoupper($student?->firstname ?? '-') }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $student?->lrn ?? '-' }}</p>
                                    </td>
                                    @foreach (['prelim_grade', 'midterm_grade', 'prefinal_grade', 'final_grade'] as $field)
                                        <td class="px-3 py-3 text-center text-slate-700">
                                            {{ filled($record->{$field}) ? number_format((float) $record->{$field}, 2) : '-' }}
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-3 text-slate-700">{{ $record->remarks ?: '-' }}</td>
                                    <td class="px-3 py-3">
                                        @if ($record->gradesAreSubmitted())
                                            <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Submitted</span>
                                        @else
                                            <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Draft</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <button
                                            type="button"
                                            onclick="openCollegeGradesModal('{{ $record->id }}')"
                                            class="min-h-10 rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"
                                        >
                                            {{ $record->gradesAreSubmitted() ? 'View Grades' : 'Manage Grades' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">
                                        @if ($totalStudents === 0)
                                            No students are enrolled in this class.
                                        @else
                                            No students match the current search and status filters.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</div>
