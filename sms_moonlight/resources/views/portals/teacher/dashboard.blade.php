@extends('portals.layout')

@php
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

$title = 'Teacher Dashboard';
$portal = 'Teacher Portal';
$activeTab = $activeTab ?? request('tab');
$heading = [
    'students' => 'Students',
    'assignments' => 'Assignments',
    'attendance' => 'Attendance',
    'schedules' => 'Schedules',
    'college-grades' => 'College Grades',
][$activeTab] ?? 'Dashboard';
$selectedSchoolYear = $schoolYears->firstWhere('id', $selectedSchoolYearId);
$sidebarSchoolYear = $portalContext === 'instructor' ? $activeCollegeSchoolYear : $selectedSchoolYear;
$isCollegeInstructor = $canUseInstructorContext;
$photoPath = !empty($teacher->profile_photo)
        && Storage::disk('public')->exists($teacher->profile_photo)
            ? asset('uploads/' . $teacher->profile_photo)
            : null;

$query = request()->query();

$hasAssignedClass = filled($selectedClass);
$attendanceRate = $studentCount > 0 ? min(100, round(($todayAttendanceCount / $studentCount) * 100)) : 0;
$lateRate = $studentCount > 0 ? min(100, round(($todayLateCount / $studentCount) * 100)) : 0;
$onTimeRate = max(0, $attendanceRate - $lateRate);
$pendingSubmissionCount = $assignments->sum(
    fn ($assignment) => max(0, $studentCount - (int) $assignment->submissions_count)
);
@endphp

@section('content')
<div>
    <div>

        {{-- LEFT PROFILE PANEL --}}
        <section class="hidden">
            <div class="bg-indigo-600 p-6 sm:p-8 text-white">
              @php
                    $initials = collect(explode(' ', trim($teacher->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($name) => strtoupper(substr($name, 0, 1)))
                        ->implode('');
                @endphp

                <div
                    class="mx-auto h-28 w-28 sm:h-36 sm:w-36 overflow-hidden rounded-3xl border-4 border-white/20 bg-white/10">
                    
                    @if ($photoPath)
                        <img
                            class="h-full w-full object-cover"
                            src="{{ $photoPath }}"
                            alt="{{ $teacher->name }}">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-white/10 text-white">
                            <span class="text-4xl sm:text-5xl font-bold tracking-wide">
                                {{ $initials }}
                            </span>
                        </div>
                    @endif

                </div>

                <div class="mt-5 text-center">
                    <h2 class="text-xl sm:text-2xl font-bold break-words">
                        {{ $teacher->name }}
                    </h2>

                    <p class="mt-1 text-sm text-indigo-100">
                        {{ $isCollegeInstructor && $selectedClass ? 'Class Adviser & College Instructor' : ($isCollegeInstructor ? 'College Instructor' : 'Class Adviser') }}
                    </p>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-1">

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            School Year
                        </p>

                        <p class="mt-2 text-sm sm:text-base font-bold text-slate-800">
                            {{ $sidebarSchoolYear->school_year ?? '-' }}
                        </p>
                    </div>

                    @if ($selectedClass)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Advisory Grade & Section
                            </p>

                            <p class="mt-2 text-sm sm:text-base font-bold text-slate-800">
                                {{ $selectedClass?->grade?->grade ?? '-' }}
                                -
                                {{ $selectedClass?->section ?? '-' }}
                            </p>
                        </div>
                    @endif

                    @if ($isCollegeInstructor)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Assigned College Classes
                            </p>

                            <div class="mt-3 space-y-3">
                                @foreach ($collegeSchedules as $collegeSchedule)
                                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">
                                                    {{ $collegeSchedule->programCourse?->course_code ?? 'Class' }}
                                                    · {{ $collegeSchedule->programCourse?->description ?? 'College class' }}
                                                </p>
                                                <p class="mt-1 text-[11px] text-slate-500">
                                                    {{ $collegeSchedule->programCourse?->program?->code ?? 'College' }}
                                                    · {{ \App\Models\CollegeProgramCourse::yearLevelLabel($collegeSchedule->programCourse?->year_level) }}
                                                </p>
                                                <p class="mt-1 text-[11px] text-slate-500">
                                                    {{ $collegeSchedule->schoolYear?->school_year ?? 'School year' }}
                                                    · {{ \App\Models\CollegeProgramCourse::SEMESTERS[$collegeSchedule->programCourse?->semester] ?? 'Semester' }}
                                                </p>
                                            </div>
                                            <span class="shrink-0 rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-bold text-indigo-700">
                                                {{ $collegeSchedule->section }}
                                            </span>
                                        </div>

                                        <div class="mt-2 border-t border-slate-100 pt-2">
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                                Schedule
                                            </p>
                                            <p class="mt-1 text-xs font-semibold text-slate-700">
                                                {{ $collegeSchedule->schedule ?: 'Schedule not set' }}
                                            </p>
                                            @if ($collegeSchedule->room)
                                                <p class="mt-1 text-xs text-slate-500">
                                                    Room: {{ $collegeSchedule->room }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <!-- 
            <div class="rounded-2xl bg-slate-50 p-4 sm:col-span-2 lg:col-span-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Adviser
                </p>

                <p class="mt-2 text-sm sm:text-base font-bold text-slate-800 break-words">
                    {{ strtoupper($teacher->name) }}
                </p>
            </div> -->

                </div>
            </div>
        </section>

        {{-- MAIN CONTENT --}}
        <div class="flex min-w-0 flex-col gap-6">

            {{-- ACADEMIC CONTEXT --}}
            @if ($portalContext === 'adviser')
            <form method="GET" action="{{ route('teacher.dashboard') }}" class="portal-content-card grid gap-3 p-4 sm:grid-cols-2 sm:p-5">
                <input type="hidden" name="context" value="adviser">
                @if ($activeTab)
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                @endif
                <label class="block">
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500">School year</span>
                    <select name="school_year_id" onchange="this.form.submit()"
                        class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-[var(--hanan-primary)] focus:outline-none">
                        @forelse ($schoolYears as $schoolYear)
                            <option value="{{ $schoolYear->id }}" @selected((int) $selectedSchoolYearId === (int) $schoolYear->id)>
                                {{ $schoolYear->school_year }}
                            </option>
                        @empty
                            <option value="">No assigned school year</option>
                        @endforelse
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500">
                        Advisory class{{ $classes->count() > 1 ? ' ('.$classes->count().' assigned)' : '' }}
                    </span>
                    <select name="class_id" onchange="this.form.submit()" @disabled($classes->isEmpty())
                        class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-[var(--hanan-primary)] focus:outline-none disabled:text-slate-400">
                        @forelse ($classes as $classOption)
                            <option value="{{ $classOption->id }}" @selected((int) $selectedClass?->id === (int) $classOption->id)>
                                {{ $classOption->grade?->grade ?? 'Grade' }} - {{ $classOption->section }}
                            </option>
                        @empty
                            <option value="">No assigned class</option>
                        @endforelse
                    </select>
                </label>
            </form>
            @endif

            {{-- OVERVIEW --}}
            @if (! $activeTab)
            @if ($portalContext === 'instructor')
            <section class="order-1">
                <div class="mb-4">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">College workspace</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-[var(--hanan-primary)]">Assigned Classes</h2>
                    <p class="mt-1 text-sm text-slate-500">College schedules and grading are kept separate from your advisory class.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($collegeSchedules as $offering)
                        @php($course = $offering->programCourse)
                        <article class="portal-content-card p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">
                                        {{ $course?->program?->code ?? 'College' }} · Section {{ $offering->section }}
                                    </p>
                                    <h3 class="mt-2 text-lg font-extrabold text-slate-900">
                                        {{ $course?->course_code ?? 'Class' }}
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ $course?->description ?? 'College class' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">
                                    {{ $collegeGradeRecords->where('offering_id', $offering->id)->count() }} students
                                </span>
                            </div>
                            <dl class="mt-5 space-y-2 border-t border-slate-100 pt-4 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">School year</dt>
                                    <dd class="font-semibold text-slate-800">{{ $offering->schoolYear?->school_year ?? '-' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Schedule</dt>
                                    <dd class="text-right font-semibold text-slate-800">{{ $offering->schedule ?: 'Not set' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Room</dt>
                                    <dd class="font-semibold text-slate-800">{{ $offering->room ?: 'Not set' }}</dd>
                                </div>
                            </dl>
                            <a href="{{ route('teacher.dashboard', ['context' => 'instructor', 'tab' => 'college-grades', 'college_class_id' => $offering->id]) }}"
                                class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[var(--hanan-primary)] px-4 py-2 text-sm font-bold text-white">
                                Open Gradebook
                            </a>
                        </article>
                    @empty
                        <div class="portal-content-card px-6 py-12 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                            No active college classes are assigned to you.
                        </div>
                    @endforelse
                </div>
            </section>
            @else
            <section class="order-1">
                <!-- <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Academic dashboard</p>
                        <h2 class="mt-1 text-3xl font-black tracking-tight text-[var(--hanan-primary)] sm:text-4xl">Academic Overview</h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">{{ $selectedSchoolYear->school_year ?? 'Current school year' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm sm:text-right">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Active advisory class</p>
                        <p class="mt-1 text-sm font-extrabold text-[var(--hanan-primary)]">{{ $selectedClass?->grade?->grade ?? 'No grade' }} - {{ $selectedClass?->section ?? 'No section' }}</p>
                    </div>
                </div> -->

                <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-12">
                    <article class="portal-content-card col-span-2 p-5 sm:p-6 xl:col-span-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">Enrolled</span>
                        </div>
                        <p class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Total students</p>
                        <p class="mt-1 text-4xl font-black text-[var(--hanan-primary)]">{{ $studentCount }}</p>
                        <div class="mt-5 h-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full w-4/5 bg-blue-700"></div></div>
                    </article>

                    <article class="portal-content-card col-span-1 p-5 xl:col-span-2">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Male</p>
                        <p class="mt-3 text-3xl font-black text-blue-700">{{ $maleCount }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $studentCount > 0 ? round(($maleCount / $studentCount) * 100) : 0 }}% of class</p>
                    </article>

                    <article class="portal-content-card col-span-1 p-5 xl:col-span-2">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Female</p>
                        <p class="mt-3 text-3xl font-black text-pink-700">{{ $femaleCount }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $studentCount > 0 ? round(($femaleCount / $studentCount) * 100) : 0 }}% of class</p>
                    </article>

                    <article class="portal-content-card col-span-2 p-5 sm:p-6 xl:col-span-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-extrabold text-[var(--hanan-primary)]">Today’s Attendance</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ now()->format('F j, Y') }}</p>
                                @if ($selectedClass?->start_time && $selectedClass?->end_time)
                                    <p class="mt-1 text-xs font-semibold text-slate-600">
                                        Class time:
                                        {{ Carbon::parse($selectedClass->start_time)->format('h:i A') }}
                                        -
                                        {{ Carbon::parse($selectedClass->end_time)->format('h:i A') }}
                                    </p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-4xl font-black text-[var(--hanan-primary)]">{{ $todayAttendanceCount }}<span class="text-lg text-slate-500">/{{ $studentCount }}</span></p>
                                <p class="mt-1 text-sm font-extrabold text-amber-700">{{ $todayLateCount }} tardy/late</p>
                            </div>
                        </div>
                        <div class="mt-6 flex flex-wrap justify-between gap-2 text-xs font-bold">
                            <span class="text-emerald-700">{{ max(0, $todayAttendanceCount - $todayLateCount) }} on time</span>
                            <span class="text-amber-700">{{ $todayLateCount }} tardy/late</span>
                            <span class="text-red-700">{{ $todayAttendanceMissing }} pending</span>
                        </div>
                        <div class="mt-2 flex h-3 overflow-hidden rounded-full bg-red-100">
                            <div class="h-full bg-emerald-500" style="width: {{ $onTimeRate }}%"></div>
                            <div class="h-full bg-amber-500" style="width: {{ $lateRate }}%"></div>
                        </div>
                        @unless ($selectedClass?->start_time)
                            <p class="mt-2 text-xs font-semibold text-amber-700">Set this class's start time in Admin to calculate late arrivals.</p>
                        @endunless
                    </article>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <article class="flex min-h-28 items-center gap-4 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-amber-700 shadow-sm">🎂</div>
                        <div class="min-w-0">
                            <h3 class="font-extrabold text-amber-950">Birthdays Today</h3>
                            @if ($birthdayCelebrants->isEmpty())
                                <p class="mt-1 text-sm font-medium text-amber-800/75">No student birthdays for today.</p>
                            @else
                                <p class="mt-1 truncate text-sm font-medium text-amber-800/75">{{ $birthdayCelebrants->map(fn ($student) => $student->firstname.' '.$student->lastname)->join(', ') }}</p>
                            @endif
                        </div>
                    </article>
                    <a href="{{ route('teacher.dashboard', array_merge($query, ['tab' => 'assignments'])) }}" class="flex min-h-28 items-center gap-4 rounded-2xl border border-red-100 bg-red-50/60 p-5 transition hover:border-red-200">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-[var(--hanan-primary)] shadow-sm">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5h6M9 9h6M9 13h4"/><rect x="5" y="3" width="14" height="18" rx="2"/></svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-[var(--hanan-primary)]">Pending Submissions</h3>
                            <p class="mt-1 text-sm font-medium text-red-900/70">{{ $pendingSubmissionCount }} missing across {{ $assignments->count() }} assignment{{ $assignments->count() === 1 ? '' : 's' }}.</p>
                        </div>
                        <span class="ml-auto flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--hanan-primary)] text-white">→</span>
                    </a>
                </div>
            </section>
            @endif
            @endif


            {{-- SELECTED WORKSPACE --}}
            @if ($activeTab)
            <section class="order-2 min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                {{-- STUDENTS --}}
                @if ($activeTab === 'students')

                {{-- FILTERS + ACTIONS --}}
                <div class="border-b border-slate-200 p-4 sm:p-5 space-y-4">

                    {{-- ACTION TOOLBAR --}}
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" onclick="openStudentModal()" @disabled(! $hasAssignedClass)
                                class="inline-flex min-h-11 items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-300">
                                + Add Students
                            </button>
                            <form method="POST" action="{{ route('teacher.students.archive') }}"
                                onsubmit="return confirm('Archive all students for this school year?')"
                                class="inline-flex">
                                @csrf
                                <input type="hidden" name="class_id" value="{{ $selectedClass?->id }}">
                                <button type="submit"
                                    class="inline-flex min-h-11 items-center rounded-2xl border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                    Archive School Year
                                </button>
                            </form>
                        </div>

                        <details class="relative">
                            <summary class="inline-flex min-h-11 cursor-pointer list-none items-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                Export&hellip;
                            </summary>
                            <div class="absolute right-0 z-20 mt-2 grid min-w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl">
                                <a href="{{ route('teacher.students.export', ['class_id' => $selectedClass?->id]) }}"
                                    class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Student list</a>
                                @if (\App\Models\Setting::enabled('qr_code_enabled', true))
                                    <a href="{{ route('teacher.students.export-qr', ['class_id' => $selectedClass?->id]) }}"
                                        class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">QR codes</a>
                                @endif
                                <a href="{{ route('teacher.students.export-grades', ['class_id' => $selectedClass?->id]) }}"
                                    class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Grades PDF</a>
                            </div>
                        </details>
                    </div>

                    {{-- SEARCH FORM --}}
                    <form class="grid gap-3 md:grid-cols-2 xl:grid-cols-[auto_1fr_180px_auto]" method="GET"
                        action="{{ route('teacher.dashboard') }}">
                        <input type="hidden" name="tab" value="students">
                        <input type="hidden" name="class_id" value="{{ $selectedClass->id ?? '' }}">
                        <input type="hidden" name="school_year_id" value="{{ $selectedSchoolYearId }}">

                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="show_archived" value="1"
                                @checked(request()->boolean('show_archived'))
                            >
                            Show Archived
                        </label>

                        <label class="block">
                            <span class="sr-only">Search students</span>
                        <input
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none"
                            name="search" type="search" value="{{ request('search') }}"
                            placeholder="Search Student Name or Student Number">
                        </label>

                        <label class="block">
                            <span class="sr-only">Filter by gender</span>
                        <select
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none"
                            name="gender">
                            <option value="">All Genders</option>
                            <option value="Female" @selected(request('gender')==='Female' )>
                                Female
                            </option>

                            <option value="Male" @selected(request('gender')==='Male' )>
                                Male
                            </option>
                        </select>
                        </label>

                        <button
                            class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500"
                            type="submit">
                            Search
                        </button>
                    </form>
                </div>

                {{-- TABLE --}}
                <div class="w-full overflow-x-auto">
                    <table class="min-w-[640px] w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Student
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Student Number
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Gender
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Birthday
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Grade Status
                                </th>
                                
                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Notes
                                </th>

                               <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($students as $classStudent)
                            @php($student = $classStudent->student)

                            <tr class="transition hover:bg-slate-50">
                                <td class="px-3 py-2">
                                    <div class="flex min-w-[190px] items-center gap-2">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-xs font-bold text-indigo-700">
                                            {{ str($student->firstname ?? 'S')->substr(0, 1)->upper() }}
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ strtoupper($student->lastname ?? '-') }},
                                                {{ strtoupper($student->firstname ?? '-') }}
                                            </p>

                                            <p class="text-xs text-slate-500">
                                                {{ strtoupper($student->middlename ?? '-') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-2 text-xs text-slate-600">
                                    {{ $student->lrn ?? '-' }}
                                </td>

                                <td class="px-3 py-2 text-xs text-slate-600">
                                    {{ $student->gender ?? '-' }}
                                </td>

                                <td class="px-3 py-2 text-xs text-slate-600">
                                    {{ $student?->dob ? Carbon::parse($student->dob)->format('F j, Y') : '-' }}
                                </td>

                                <td class="px-3 py-2">
                                    @if ($classStudent->gradesAreSubmitted())
                                    <span
                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                        Submitted
                                    </span>
                                    @elseif ($classStudent->hidden_grade)
                                    <span
                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                        Hidden
                                    </span>
                                    @else
                                    <span
                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                        Visible
                                    </span>
                                    @endif
                                </td>

                               <td class="max-w-[220px] truncate px-3 py-2 text-xs text-slate-600">
                                   {{ $classStudent->notes ?? '-' }}
                                </td>

                                <td class="px-3 py-2 text-right">
                                    <div class="inline-flex overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                                        {{-- EDIT --}}
                                        <button
                                            type="button"
                                            onclick="openEditStudentModal(@js([
                                                'id' => $classStudent->id,
                                                'hidden_grade' => $classStudent->hidden_grade ? 1 : 0,
                                                'notes' => $classStudent->notes,
                                                'lrn' => $student->lrn,
                                                'lastname' => $student->lastname,
                                                'firstname' => $student->firstname,
                                                'middlename' => $student->middlename,
                                                'dob' => $student?->dob ? Carbon::parse($student->dob)->format('Y-m-d') : '',
                                                'is_4ps_member' => $student->is_4ps_member ? 1 : 0,
                                                'height' => $student->height,
                                                'weight' => $student->weight,
                                            ]))"
                                            class="min-h-11 border-r border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Edit
                                        </button>

                                        {{-- MANAGE GRADES --}}
                                        <button
                                            type="button"
                                            onclick="openGradesModal('{{ $classStudent->id }}')"
                                            class="min-h-11 border-r border-slate-200 px-3 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50"
                                        >
                                            Grades
                                        </button>

                                        {{-- DELETE --}}
                                        <form
                                            method="POST"
                                            action="{{ route('teacher.students.delete', $classStudent->id) }}"
                                            onsubmit="return confirm('Remove this student from class?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="min-h-11 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No students found for this class and school year.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                <div class="border-t border-slate-200 bg-slate-50 px-4 sm:px-6 py-4">
                    {{ $students->links() }}
                </div>
                @endif

                @includeWhen($portalContext === 'instructor' && $activeTab === 'college-grades', 'portals.teacher.partials.college-grades-tab', [
                    'collegeSchedules' => $collegeSchedules,
                    'collegeGradeRecords' => $collegeGradeRecords,
                    'selectedCollegeClass' => $selectedCollegeClass,
                    'filteredCollegeGradeRecords' => $filteredCollegeGradeRecords,
                    'collegeGradeSearch' => $collegeGradeSearch,
                    'collegeGradeStatus' => $collegeGradeStatus,
                ])

                {{-- ASSIGNMENTS --}}
                @includeWhen($activeTab === 'assignments', 'portals.teacher.partials.assignments-tab', [
                    'assignments' => $assignments,
                    'selectedClass' => $selectedClass,
                    'studentCount' => $studentCount,
                ])

                {{-- ATTENDANCE --}}
                @if ($activeTab === 'attendance')

                <div class="w-full overflow-x-auto">
                    {{-- ATTENDANCE SEARCH --}}
                    <div class="border-b border-slate-200 p-4 sm:p-5">
                        <form class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_180px_180px_auto]" method="GET"
                            action="{{ route('teacher.dashboard') }}">
                            <input type="hidden" name="tab" value="attendance">
                            <input type="hidden" name="context" value="{{ $portalContext }}">
                            @if ($portalContext === 'instructor')
                                <label class="md:col-span-2 xl:col-span-4">
                                    <span class="mb-1.5 block text-xs font-semibold text-slate-600">College class</span>
                                    <select name="college_class_id"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none">
                                        <option value="">All assigned classes</option>
                                        @foreach ($collegeSchedules as $collegeSchedule)
                                            <option value="{{ $collegeSchedule->id }}" @selected($selectedCollegeClass?->id === $collegeSchedule->id)>
                                                {{ $collegeSchedule->programCourse?->course_code ?? 'Class' }} · {{ $collegeSchedule->section }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            @else
                                <input type="hidden" name="class_id" value="{{ $selectedClass?->id }}">
                                <input type="hidden" name="school_year_id" value="{{ $selectedSchoolYearId }}">
                            @endif

                            <label>
                                <span class="mb-1.5 block text-xs font-semibold text-slate-600">Search attendance</span>
                                <input type="search" name="attendance_search" value="{{ request('attendance_search') }}"
                                    placeholder="Student name or Student Number"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none">
                            </label>

                            <label>
                                <span class="mb-1.5 block text-xs font-semibold text-slate-600">From</span>
                                <input type="date" name="attendance_from" value="{{ $attendanceFrom }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none">
                            </label>

                            <label>
                                <span class="mb-1.5 block text-xs font-semibold text-slate-600">To</span>
                                <input type="date" name="attendance_to" value="{{ $attendanceTo }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none">
                            </label>

                            <button type="submit"
                                class="attendance-search-button inline-flex h-11 min-w-[96px] self-end justify-self-end items-center justify-center rounded-xl bg-indigo-600 px-5 py-0 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                Search
                            </button>
                        </form>
                    </div>
                    <table class="min-w-[860px] w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Date
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Student Number
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Student
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Logged Time
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($attendance as $entry)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 text-xs">
                                    {{ $entry->currentdate?->format('F j, Y') ?? '-' }}
                                </td>

                                <td class="px-3 py-2 text-xs">
                                    {{ $entry->student->lrn ?? '-' }}
                                </td>

                                <td class="px-3 py-2 text-xs font-medium">
                                    {{ strtoupper($entry->student->lastname ?? '-') }},
                                    {{ strtoupper($entry->student->firstname ?? '') }}
                                </td>

                                <td class="px-3 py-2 text-xs font-semibold text-emerald-700">
                                    {{ $entry->logged_time?->format('h:i A') ?? '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No attendance records found for {{ $portalContext === 'instructor' ? 'the selected college students' : 'this class' }}.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 bg-slate-50 px-4 sm:px-6 py-4">
                    {{ $attendance->links() }}
                </div>
                @endif

                {{-- SCHEDULES --}}
                @if ($activeTab === 'schedules')
                <div class="w-full overflow-x-auto">
                    {{-- SCHEDULE ACTIONS --}}
                    <div class="border-b border-slate-200 p-4 sm:p-5">
                        <div class="flex items-center justify-between">

                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">
                                    Class Schedules
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Manage your class schedules and time frames.
                                </p>
                            </div>
                            <button type="button" onclick="openScheduleModal()"
                                class="inline-flex items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                + Add Schedule
                            </button>

                        </div>
                    </div>
                    <table class="min-w-[720px] w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Day
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Section
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Time Frame
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($schedules as $schedule)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 text-xs">
                                    {{ $schedule->day }}
                                </td>

                                <td class="px-3 py-2 text-xs">
                                    {{ $schedule->section }}
                                </td>

                                <td class="px-3 py-2 text-xs">
                                    {{ $schedule->time_frame }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No schedules found for this class.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @endif
            </section>
            @endif
        </div>
    </div>
</div>

{{-- ADD STUDENT MODAL --}}
<div id="addStudentModal" role="dialog" aria-modal="true" aria-labelledby="addStudentModalTitle"
    class="portal-dialog fixed inset-0 z-[80] hidden items-center justify-center bg-black/50 p-4">
    <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">

        {{-- HEADER --}}
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h3 id="addStudentModalTitle" class="text-xl font-bold text-slate-900">
                    Add Student
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Search existing students and add them to your class.
                </p>
            </div>

            <button type="button" onclick="closeStudentModal()" data-dialog-close aria-label="Close add student dialog"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100">
                &times;
            </button>
        </div>

        {{-- SEARCH --}}
        <div class="border-b border-slate-200 p-5">

            <input type="text" id="studentSearchInput" placeholder="Search Student Number or Student Name..."
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none">

        </div>

        <form id="bulkAddStudentsForm" method="POST" action="{{ route('teacher.students.store') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClass?->id }}">
            <input type="hidden" name="school_year_id" value="{{ $selectedSchoolYearId }}">

        {{-- STUDENT LIST --}}
        <div class="flex-1 overflow-y-auto">

            <table class="min-w-full text-sm">
                <thead class="sticky top-0 bg-slate-50">
                    <tr>
                        <th class="w-10 px-3 py-2 text-left">
                            <input
                                id="selectAllAvailableStudents"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                aria-label="Select all visible students">
                        </th>

                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Student Number
                        </th>

                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Student Name
                        </th>

                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Action
                        </th>

                    </tr>
                </thead>

                <tbody id="studentSearchTable" class="divide-y divide-slate-100">

                    @forelse ($availableStudents as $availableStudent)

                    @php($alreadyAdded = $students->pluck('student_id')->contains($availableStudent->id))

                    <tr class="student-search-row hover:bg-slate-50" data-search="{{ collect([$availableStudent->lrn, $availableStudent->firstname, $availableStudent->middlename, $availableStudent->lastname])->filter()->implode(' ') }}">
                        <td class="px-3 py-2">
                            @if(! $alreadyAdded)
                                <input
                                    type="checkbox"
                                    name="student_ids[]"
                                    value="{{ $availableStudent->id }}"
                                    class="student-select-checkbox h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    aria-label="Select {{ $availableStudent->lastname }}, {{ $availableStudent->firstname }}">
                            @endif
                        </td>

                        <td class="px-3 py-2 text-xs text-slate-700">
                            {{ $availableStudent->lrn }}
                        </td>

                        <td class="px-3 py-2 text-xs font-medium text-slate-900">
                            {{ strtoupper($availableStudent->lastname) }},
                            {{ strtoupper($availableStudent->firstname) }}
                            {{ strtoupper($availableStudent->middlename) }}
                        </td>

                        <td class="px-3 py-2 text-right">

                            @if($alreadyAdded)

                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                Already Added
                            </span>

                            @else

                            <button type="button"
                                onclick="submitSingleStudent('{{ $availableStudent->id }}')"
                                class="rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                                Add
                            </button>

                            @endif

                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">
                            No students available.
                        </td>
                    </tr>

                    @endforelse

                </tbody>
            </table>

        </div>

        {{-- FOOTER --}}
        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
            <div class="flex items-center justify-end gap-3">
                <p id="selectedStudentsCount" class="mr-auto text-xs font-semibold text-slate-500">
                    0 selected
                </p>

                <button type="button" onclick="closeStudentModal()" data-dialog-close
                    class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Close
                </button>

                <button type="submit"
                    id="bulkAddStudentsButton"
                    disabled
                    class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-300">
                    Add Selected
                </button>

            </div>
        </div>
        </form>
    </div>
</div>

{{-- EDIT STUDENT MODAL --}}
<div
    id="editStudentModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="editStudentModalTitle"
    class="portal-dialog fixed inset-0 z-[80] hidden items-center justify-center bg-black/50 p-4"
>
    <div class="flex max-h-[calc(100dvh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">

        {{-- HEADER --}}
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">

            <div>
                <h3 id="editStudentModalTitle" class="text-xl font-bold text-slate-900">
                    Edit Student
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Update student class settings{{ $teacherStudentDetailEditingEnabled ? ' and profile details' : '' }}.
                </p>
            </div>

            <button
                type="button"
                onclick="closeEditStudentModal()"
                data-dialog-close
                aria-label="Close edit student dialog"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100"
            >
                &times;
            </button>

        </div>

        {{-- FORM --}}
        <form
            method="POST"
            id="editStudentForm"
            class="min-h-0 overflow-y-auto p-6"
        >
            @csrf
            @method('PUT')

            <div class="space-y-5">
                @if ($teacherStudentDetailEditingEnabled)
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">LRN</span>
                            <input type="text" id="student_lrn" name="lrn" maxlength="15" required
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Birthday</span>
                            <input type="date" id="student_dob" name="dob"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Last Name</span>
                            <input type="text" id="student_lastname" name="lastname" maxlength="30" required
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">First Name</span>
                            <input type="text" id="student_firstname" name="firstname" maxlength="30" required
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Middle Name</span>
                            <input type="text" id="student_middlename" name="middlename" maxlength="30"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Height</span>
                            <input type="text" id="student_height" name="height" maxlength="10"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Weight</span>
                            <input type="text" id="student_weight" name="weight" maxlength="10"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none">
                        </label>

                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">
                            <input type="checkbox" id="student_is_4ps_member" name="is_4ps_member" value="1"
                                class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-semibold text-slate-700">4P's Member</span>
                        </label>
                    </div>
                @endif

                {{-- HIDE GRADE --}}
                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">

                    <input
                        type="checkbox"
                        id="hidden_grade"
                        name="hidden_grade"
                        value="1"
                        class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    >

                    <div>
                        <p class="font-semibold text-slate-800">
                            Hide Grade from Student
                        </p>

                        <p class="text-sm text-slate-500">
                            Student will not see grades in portal.
                        </p>
                    </div>

                </label>

                {{-- NOTES --}}
                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Notes
                    </label>

                    <textarea
                        id="student_notes"
                        name="notes"
                        rows="5"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none"
                        placeholder="Enter notes..."
                    ></textarea>

                </div>

            </div>

            {{-- FOOTER --}}
            <div class="mt-8 flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeEditStudentModal()"
                    data-dialog-close
                    class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>
</div>

{{-- GRADES MODAL --}}
<div id="assignmentSummaryModal" role="dialog" aria-modal="true" aria-labelledby="assignmentSummaryModalTitle"
    class="portal-dialog fixed inset-0 z-[80] hidden items-center justify-center bg-black/50 p-4">
    <div class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h3 id="assignmentSummaryModalTitle" class="text-xl font-bold text-slate-900">Assignment Summary</h3>
                <p class="mt-1 text-sm text-slate-500">Review student submissions and notes.</p>
            </div>

            <button type="button" onclick="closeAssignmentSummary()" data-dialog-close aria-label="Close assignment summary"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100">
                &times;
            </button>
        </div>

        <div id="assignmentSummaryContent" class="flex-1 overflow-y-auto p-6">
            <p class="text-sm text-slate-500">Loading submissions...</p>
        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex justify-end">
            <button type="button" onclick="closeAssignmentSummary()" data-dialog-close
                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Close
            </button>
        </div>
    </div>
</div>

<div id="gradesModal" role="dialog" aria-modal="true" aria-labelledby="gradesModalTitle"
    class="portal-dialog fixed inset-0 z-[80] hidden items-center justify-center bg-black/50 p-2 sm:p-4">
    <div class="flex max-h-[calc(100dvh-1rem)] w-full max-w-5xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl sm:max-h-[calc(100dvh-2rem)]">

        {{-- HEADER --}}
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-4 py-3 sm:px-6 sm:py-4">
            <div>
                <h3 id="gradesModalTitle" class="text-xl font-bold text-slate-900">
                    Student Grades
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                    View and manage student grades.
                </p>
            </div>

            <button type="button" onclick="closeGradesModal()" data-dialog-close aria-label="Close student grades"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100">
                &times;
            </button>
        </div>

        {{-- CONTENT --}}
        <div id="gradesModalContent" class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-5">
            <p class="text-sm text-slate-500">Loading grades...</p>
        </div>

        {{-- FOOTER --}}
        <div class="flex shrink-0 justify-end border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6">
            <button type="button" onclick="closeGradesModal()" data-dialog-close
                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Close
            </button>
        </div>

    </div>
</div>

{{-- ADD SCHEDULE MODAL --}}
<div id="addScheduleModal" role="dialog" aria-modal="true" aria-labelledby="addScheduleModalTitle"
    class="portal-dialog fixed inset-0 z-[80] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl">

        {{-- HEADER --}}
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h3 id="addScheduleModalTitle" class="text-xl font-bold text-slate-900">
                    Add Schedule
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Create a new adviser schedule.
                </p>
            </div>

            <button type="button" onclick="closeScheduleModal()" data-dialog-close aria-label="Close add schedule dialog"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100">
                &times;
            </button>
        </div>

        {{-- FORM --}}
        <form method="POST" action="{{ route('teacher.schedules.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClass?->id }}">

            <div class="grid gap-5">

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Day
                    </label>

                    <select name="day" required
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none">
                        <option value="">Select Day</option>
                        <option>Monday</option>
                        <option>Tuesday</option>
                        <option>Wednesday</option>
                        <option>Thursday</option>
                        <option>Friday</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Class section
                    </label>

                    <input type="text" value="{{ $selectedClass?->grade?->grade }} - {{ $selectedClass?->section }}" readonly
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-600">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Time Frame
                    </label>

                    <input type="text" name="time_frame" placeholder="8:00 AM - 10:00 AM" required
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:outline-none">
                </div>

            </div>

            {{-- FOOTER --}}
            <div class="mt-8 flex justify-end gap-3">

                <button type="button" onclick="closeScheduleModal()" data-dialog-close
                    class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Cancel
                </button>

                <button type="submit"
                    class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500">
                    Save Schedule
                </button>

            </div>
        </form>
    </div>
</div>

<script>
    const updateStudentRouteTemplate = @json(route('teacher.students.update', ['classStudent' => '__ID__']));
    const gradesModalRouteTemplate = @json(route('teacher.students.grades.modal', ['id' => '__ID__']));
    const collegeGradesModalRouteTemplate = @json(route('teacher.college-grades.modal', ['collegeEnrollmentCourse' => '__ID__']));
    const assignmentSummaryRouteTemplate = @json(route('teacher.assignments.summary', ['assignment' => '__ID__']));

    const studentSearchInput = document.getElementById('studentSearchInput');
    if (studentSearchInput) {
        const normalizeStudentSearch = (value) => String(value ?? '')
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase()
            .trim()
            .replace(/\s+/g, ' ');

        studentSearchInput.addEventListener('input', function() {
            const terms = normalizeStudentSearch(this.value).split(' ').filter(Boolean);
            document
                .querySelectorAll('.student-search-row')
                .forEach(function(row) {
                    const searchable = normalizeStudentSearch(row.dataset.search);
                    row.style.display = terms.every((term) => searchable.includes(term)) ? '' : 'none';
                });
            updateSelectedStudentsCount();
        });
    }

    const selectAllAvailableStudents = document.getElementById('selectAllAvailableStudents');
    const studentSelectCheckboxes = () => Array.from(document.querySelectorAll('.student-select-checkbox'));

    function visibleStudentCheckboxes() {
        return studentSelectCheckboxes().filter((checkbox) => {
            const row = checkbox.closest('.student-search-row');
            return row && row.style.display !== 'none';
        });
    }

    function updateSelectedStudentsCount() {
        const selected = studentSelectCheckboxes().filter((checkbox) => checkbox.checked).length;
        const count = document.getElementById('selectedStudentsCount');
        const button = document.getElementById('bulkAddStudentsButton');

        if (count) {
            count.textContent = `${selected} selected`;
        }

        if (button) {
            button.disabled = selected === 0;
        }

        if (selectAllAvailableStudents) {
            const visible = visibleStudentCheckboxes();
            const selectedVisible = visible.filter((checkbox) => checkbox.checked).length;
            selectAllAvailableStudents.checked = visible.length > 0 && selectedVisible === visible.length;
            selectAllAvailableStudents.indeterminate = selectedVisible > 0 && selectedVisible < visible.length;
        }
    }

    if (selectAllAvailableStudents) {
        selectAllAvailableStudents.addEventListener('change', function() {
            visibleStudentCheckboxes().forEach((checkbox) => {
                checkbox.checked = this.checked;
            });
            updateSelectedStudentsCount();
        });
    }

    studentSelectCheckboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', updateSelectedStudentsCount);
    });

    function submitSingleStudent(studentId) {
        studentSelectCheckboxes().forEach((checkbox) => {
            checkbox.checked = checkbox.value === String(studentId);
        });
        updateSelectedStudentsCount();
        document.getElementById('bulkAddStudentsForm')?.submit();
    }

    function openGradesModal(classStudentId) {
    const modal = document.getElementById('gradesModal');
    const content = document.getElementById('gradesModalContent');

    window.portalDialog.open(modal);

    content.innerHTML = `<p class="text-sm text-slate-500">Loading grades...</p>`;

    const url = gradesModalRouteTemplate.replace('__ID__', classStudentId);

    fetch(url)
        .then(res => res.text())
        .then(html => {
            content.innerHTML = html;
            initializeTeacherGradesModal();
        })
        .catch(() => {
            content.innerHTML = `<p class="text-red-500 text-sm">Failed to load grades.</p>`;
        });
}

function closeGradesModal() {
    const modal = document.getElementById('gradesModal');
    window.portalDialog.close(modal);
}

function openCollegeGradesModal(collegeEnrollmentCourseId) {
    const modal = document.getElementById('gradesModal');
    const content = document.getElementById('gradesModalContent');

    window.portalDialog.open(modal);
    content.innerHTML = `<p class="text-sm text-slate-500">Loading college grades...</p>`;

    const url = collegeGradesModalRouteTemplate.replace('__ID__', collegeEnrollmentCourseId);

    fetch(url)
        .then(res => res.text())
        .then(html => {
            content.innerHTML = html;
            initializeTeacherGradesModal();
        })
        .catch(() => {
            content.innerHTML = `<p class="text-red-500 text-sm">Failed to load college grades.</p>`;
        });
}

function openAssignmentSummary(assignmentId) {
    const modal = document.getElementById('assignmentSummaryModal');
    const content = document.getElementById('assignmentSummaryContent');

    window.portalDialog.open(modal);
    content.innerHTML = `<p class="text-sm text-slate-500">Loading submissions...</p>`;

    const url = assignmentSummaryRouteTemplate.replace('__ID__', assignmentId);

    fetch(url)
        .then(res => res.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(() => {
            content.innerHTML = `<p class="text-sm text-red-500">Failed to load assignment summary.</p>`;
        });
}

function closeAssignmentSummary() {
    const modal = document.getElementById('assignmentSummaryModal');
    window.portalDialog.close(modal);
}

    function openStudentModal() {
        const modal = document.getElementById('addStudentModal');
        window.portalDialog.open(modal);
    }

    function openEditStudentModal(student) {

        const modal = document.getElementById('editStudentModal');
        const form = document.getElementById('editStudentForm');

        form.action = updateStudentRouteTemplate.replace('__ID__', student.id);

        document.getElementById('hidden_grade').checked = student.hidden_grade == 1;
        document.getElementById('student_notes').value = student.notes ?? '';

        const setInputValue = (id, value) => {
            const field = document.getElementById(id);
            if (field) {
                field.value = value ?? '';
            }
        };

        setInputValue('student_lrn', student.lrn);
        setInputValue('student_lastname', student.lastname);
        setInputValue('student_firstname', student.firstname);
        setInputValue('student_middlename', student.middlename);
        setInputValue('student_dob', student.dob);
        setInputValue('student_height', student.height);
        setInputValue('student_weight', student.weight);

        const fourPsField = document.getElementById('student_is_4ps_member');
        if (fourPsField) {
            fourPsField.checked = student.is_4ps_member == 1;
        }

        window.portalDialog.open(modal);
    }

    function closeEditStudentModal() {

        const modal = document.getElementById('editStudentModal');

        window.portalDialog.close(modal);
    }

    function closeStudentModal() {
        const modal = document.getElementById('addStudentModal');
        window.portalDialog.close(modal);
    }

    function openScheduleModal() {
        const modal = document.getElementById('addScheduleModal');
        window.portalDialog.open(modal);
    }

    function closeScheduleModal() {
        const modal = document.getElementById('addScheduleModal');
        window.portalDialog.close(modal);
    }

    function initializeTeacherGradesModal() {
        const toggle = document.getElementById('editGradeToggle');
        const saveButton = document.getElementById('saveGradesButton');
        const submitButton = document.getElementById('submitGradesButton');
        const inputs = document.querySelectorAll('[data-grade-editable]');
        const rowAverageSpans = document.querySelectorAll('[data-row-average]');
        const summarySpans = document.querySelectorAll('[data-summary-quarter]');

        if (!toggle || !saveButton || !inputs.length) {
            return;
        }

        const recalculate = () => {
            const summary = { avg: [] };

            rowAverageSpans.forEach((span) => {
                const rowIndex = span.dataset.rowAverage;
                const included = span.dataset.rowIncluded === '1';
                const rowInputs = document.querySelectorAll(`.grade-quarter-input[data-row-index="${rowIndex}"]`);
                const values = {};

                rowInputs.forEach((input) => {
                    const quarter = input.dataset.quarter;
                    const value = parseFloat(input.value || '0');
                    values[quarter] = Number.isFinite(value) ? value : 0;
                });

                const rowValues = Object.values(values);
                const rowAverage = rowValues.length
                    ? rowValues.reduce((carry, item) => carry + item, 0) / rowValues.length
                    : 0;
                span.textContent = rowAverage.toFixed(2);

                if (included) {
                    Object.entries(values).forEach(([quarter, value]) => {
                        summary[quarter] = summary[quarter] || [];
                        summary[quarter].push(value || 0);
                    });
                    summary.avg.push(rowAverage);
                }
            });

            const average = (values) => values.length
                ? (values.reduce((carry, item) => carry + item, 0) / values.length).toFixed(2)
                : '0.00';

            summarySpans.forEach((span) => {
                const quarter = span.dataset.summaryQuarter;
                span.textContent = average(summary[quarter] ?? []);
            });
        };

        const updateState = () => {
            const enabled = toggle.checked;

            inputs.forEach((input) => {
                input.disabled = !enabled;
            });

            saveButton.disabled = !enabled;
            if (submitButton) {
                submitButton.disabled = !enabled;
            }

            if (enabled) {
                recalculate();
            }
        };

        toggle.addEventListener('change', updateState);
        inputs.forEach((input) => input.addEventListener('input', recalculate));

        updateState();
    }
</script>

@endsection
