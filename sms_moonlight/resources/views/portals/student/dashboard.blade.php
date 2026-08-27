@extends('portals.layout')

@php
    use Carbon\Carbon;

    $title = 'Student Dashboard';
    $portal = 'Student Portal';

    $activeTab = request('tab');

    $studentName = trim(
        $student->firstname . ' ' .
        $student->middlename . ' ' .
        $student->lastname
    );
    $portalGreeting = \App\Support\PortalGreeting::message();

    $photoPath = !empty($student->profile_photo)
        && Storage::disk('public')->exists($student->profile_photo)
            ? asset('uploads/' . $student->profile_photo)
            : null;

    $qrCodeEnabled = \App\Models\Setting::enabled('qr_code_enabled', true);
    $qrData = $student->lrn ?: (string) $student->id;
    $qrUrl = $qrCodeEnabled
        ? 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=0&data=' . urlencode($qrData)
        : null;

    $query = request()->query();
    $isHighSchoolStudent = $academicContext->isHighSchool();
    $isCollegeStudent = $academicContext->isCollege();
    $hasCollegeHistory = $collegeEnrollmentHistory->isNotEmpty();
    $hasEnrollmentConflict = $academicContext->hasConflict();
    $activeSchoolYear = $isCollegeStudent
        ? $collegeEnrollment?->schoolYear
        : $activeClassStudent?->schoolYear;
    $activeClass = $activeClassStudent?->class;

    $classCount = $isCollegeStudent ? $collegeCourses->count() : $classes->count();
    $attendanceCount = $attendance->total();
    $quizCount = $quizModuleEnabled ? $quizzes->count() : 0;

    $studentTabs = [
        'class' => $isCollegeStudent || $isHighSchoolStudent ? 'Class' : 'Enrollment',
        'attendance' => 'Attendance',
    ];

    if ($isHighSchoolStudent) {
        $studentTabs = [
            'class' => 'Class',
            'assignments' => 'Assignments and Activities',
            'attendance' => 'Attendance',
        ];
    }

    if ($hasCollegeHistory) {
        $studentTabs['history'] = 'Class History';
    }

    if ($paymentsModuleEnabled) {
        $studentTabs['payments'] = 'Payment History';
    }

    if ($quizModuleEnabled && $isHighSchoolStudent) {
        $studentTabs['quiz'] = 'Quiz of the Day';
    }

    if ($activeTab !== null && ! array_key_exists($activeTab, $studentTabs)) {
        $activeTab = null;
    }

    $heading = $studentTabs[$activeTab] ?? 'Dashboard';

    $pendingAssignmentCount = $assignments
        ->filter(fn ($assignment) => $assignment->submissions->isEmpty())
        ->count();

    $dashboardClasses = $isCollegeStudent
        ? $collegeCourses->take(4)
        : collect($activeClass?->classSubjects ?? [])->take(6);
    $releasedCollegeGrades = $collegeCourses
        ->filter(fn ($course) => $course->gradesAreSubmitted())
        ->sortByDesc('grades_submitted_at')
        ->values();
    $recentHighSchoolGrades = $grades->sortByDesc('updated_at')->take(4)->values();
    $releasedGradeCount = $isCollegeStudent
        ? $releasedCollegeGrades->count()
        : $grades->count();
    $recentPayments = $paymentsModuleEnabled ? $paymentHistories->take(3) : collect();
    $totalPayments = $paymentsModuleEnabled
        ? $paymentHistories->sum(fn ($payment) => (float) $payment->amount)
        : 0;
@endphp

@section('content')
<div class="min-h-screen">

    <div>

        {{-- LEFT PROFILE SIDEBAR --}}
        <section class="hidden">

            {{-- HEADER --}}
            <div class="bg-indigo-600 p-8 text-white">
 
                @php
                    $initials = collect(explode(' ', trim($student->firstname . ' ' . $student->lastname)))
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
                            alt="{{ $student->name }}">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-white/10 text-white">
                            <span class="text-4xl sm:text-5xl font-bold tracking-wide">
                                {{ $initials }}
                            </span>
                        </div>
                    @endif

                </div>

                <div class="mt-6 text-center">
                    <h2 class="text-2xl font-bold tracking-tight">
                        {{ $studentName ?: 'Student' }}
                    </h2>

                    <p class="mt-2 text-sm text-indigo-100">
                        Student Number
                    </p>

                    <div class="mt-2 inline-flex rounded-full bg-white/10 px-4 py-2 text-sm font-semibold backdrop-blur">
                        {{ $student->lrn ?? '-' }}
                    </div>

                    <div class="mt-3">
                        <span class="inline-flex rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white">
                            {{ $academicContext->label() }}
                        </span>
                    </div>
                </div>
            </div> 
            <div class="p-5 sm:p-6">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-1">

            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    School Year
                </p>

                <p class="mt-2 text-sm sm:text-base font-bold text-slate-800">
                    {{ $activeSchoolYear->school_year ?? '-' }}
                </p>
            </div>

            @if ($isCollegeStudent)
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Academic Term
                    </p>

                    <p class="mt-2 text-sm sm:text-base font-bold text-slate-800">
                        {{ $collegeSemesterName ?? '-' }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Course & Year Level
                    </p>

                    <p class="mt-2 text-sm sm:text-base font-bold text-slate-800">
                        {{ $collegeEnrollment->program->code ?? '-' }}
                        · {{ \App\Models\CollegeProgramCourse::yearLevelLabel($collegeEnrollment->year_level) }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Enrolled Classes
                    </p>

                    <p class="mt-2 text-2xl font-bold text-slate-800">{{ $collegeCourses->count() }}</p>
                    <p class="mt-1 text-xs text-slate-500">View schedules and instructors in the Class tab.</p>
                </div>
            @elseif ($isHighSchoolStudent)
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Grade & Section
                    </p>

                    <p class="mt-2 text-sm sm:text-base font-bold text-slate-800">
                        {{ $activeClass->grade->grade ?? '-' }}
                        -
                        {{ $activeClass->section ?? '-' }}
                    </p>
                </div>
            @elseif ($hasEnrollmentConflict)
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-red-600">Needs administrator review</p>
                    <p class="mt-2 text-sm font-semibold text-red-900">{{ $academicContext->conflictReason }}</p>
                </div>
            @else
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-700">No active enrollment</p>
                    <p class="mt-2 text-sm text-amber-900">Contact the school office if your enrollment should already be active.</p>
                </div>
            @endif

        </div>
    </div>
        </section>

        {{-- MAIN CONTENT --}}
        <div class="flex min-w-0 flex-col gap-6">

            {{-- DASHBOARD OVERVIEW --}}
            @if (! $activeTab)
            <section class="order-1 space-y-4">
                <div class="portal-content-card overflow-hidden">
                    <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Academic dashboard</p>
                            <h2 class="mt-1 truncate text-2xl font-bold tracking-tight text-[var(--hanan-primary)] sm:text-3xl">
                                {{ $portalGreeting }}, {{ $student->firstname ?? 'Student' }}!
                            </h2>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-normal text-slate-600 sm:text-sm">
                                <span>{{ $activeSchoolYear->school_year ?? 'Current school year' }}</span>
                                @if ($isHighSchoolStudent)
                                    <span aria-hidden="true" class="text-slate-300">•</span>
                                    <span>{{ $activeClass->grade->grade ?? 'Grade' }} - {{ $activeClass->section ?? 'Section' }}</span>
                                @elseif ($isCollegeStudent)
                                    <span aria-hidden="true" class="text-slate-300">•</span>
                                    <span>{{ $collegeEnrollment->program->code ?? 'Course' }}</span>
                                    <span aria-hidden="true" class="text-slate-300">•</span>
                                    <span>{{ \App\Models\CollegeProgramCourse::yearLevelLabel($collegeEnrollment->year_level) }}</span>
                                    <span aria-hidden="true" class="text-slate-300">•</span>
                                    <span>{{ $collegeSemesterName }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <div class="text-right">
                                <p class="text-[10px] font-medium uppercase tracking-[0.14em] text-slate-400">Student number</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $student->lrn ?? '-' }}</p>
                            </div>
                            @if ($qrCodeEnabled)
                                <img class="h-12 w-12 rounded-xl border border-slate-200 bg-white p-1" src="{{ $qrUrl }}" alt="Student QR Code">
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <a href="{{ route('student.dashboard', ['tab' => 'class']) }}" class="portal-content-card group flex items-center gap-3 p-4 transition hover:border-red-200">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-[var(--hanan-primary)]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-2xl font-semibold text-slate-900">{{ $classCount }}</p>
                            <p class="truncate text-xs font-medium uppercase tracking-wide text-slate-500">Classes</p>
                        </div>
                    </a>
                    <a href="{{ route('student.dashboard', ['tab' => 'attendance']) }}" class="portal-content-card group flex items-center gap-3 p-4 transition hover:border-emerald-200">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-2xl font-semibold text-slate-900">{{ $attendanceCount }}</p>
                            <p class="truncate text-xs font-medium uppercase tracking-wide text-slate-500">Check-ins</p>
                        </div>
                    </a>
                    <a href="{{ route('student.dashboard', ['tab' => 'class']) }}" class="portal-content-card group flex items-center gap-3 p-4 transition hover:border-blue-200">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-2xl font-semibold text-slate-900">{{ $releasedGradeCount }}</p>
                            <p class="truncate text-xs font-medium uppercase tracking-wide text-slate-500">Released grades</p>
                        </div>
                    </a>
                    @if ($paymentsModuleEnabled)
                        <a href="{{ route('student.dashboard', ['tab' => 'payments']) }}" class="portal-content-card group flex items-center gap-3 p-4 transition hover:border-amber-200">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-xl font-semibold text-slate-900">₱{{ number_format($totalPayments, 2) }}</p>
                                <p class="truncate text-xs font-medium uppercase tracking-wide text-slate-500">Payments recorded</p>
                            </div>
                        </a>
                    @else
                        <article class="portal-content-card flex items-center gap-3 p-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-2xl font-semibold text-slate-900">{{ $dashboardAnnouncements->count() }}</p>
                                <p class="truncate text-xs font-medium uppercase tracking-wide text-slate-500">Announcements</p>
                            </div>
                        </article>
                    @endif
                </div>

                @if ($hasEnrollmentConflict)
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4" role="alert">
                        <p class="text-sm font-bold text-red-900">Enrollment needs administrator review</p>
                        <p class="mt-1 text-sm text-red-700">{{ $academicContext->conflictReason }}</p>
                    </div>
                @elseif (! $isHighSchoolStudent && ! $isCollegeStudent)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-bold text-amber-900">No active enrollment found</p>
                        <p class="mt-1 text-sm text-amber-700">Current classes will appear after your enrollment is activated.</p>
                    </div>
                @endif

                <div class="portal-dashboard-grid gap-4">
                    <article class="portal-content-card portal-dashboard-span-8 overflow-hidden xl:col-span-8">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-5">
                            <div>
                                <h3 class="font-semibold text-slate-900">Your Classes</h3>
                                <p class="text-xs text-slate-500">Current academic load and class details</p>
                            </div>
                            <a href="{{ route('student.dashboard', ['tab' => 'class']) }}" class="text-xs font-medium text-[var(--hanan-primary)] hover:underline">View all</a>
                        </div>

                        @if ($isCollegeStudent)
                            <div class="divide-y divide-slate-100">
                                @forelse ($dashboardClasses as $collegeCourse)
                                    @php($offering = $collegeCourse->offering)
                                    @php($programClass = $collegeCourse->programCourse)
                                    <div class="grid gap-2 px-4 py-3.5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-5">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-slate-900">{{ $programClass?->course_code ?? 'Class' }}</span>
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $offering?->section ?? 'No section' }}</span>
                                            </div>
                                            <p class="mt-0.5 truncate text-sm text-slate-600">{{ $programClass?->description ?? 'Description not set' }}</p>
                                        </div>
                                        <div class="text-xs text-slate-500 sm:text-right">
                                            <p class="font-normal text-slate-700">{{ $offering?->schedule ?: 'Schedule not set' }}</p>
                                            <p class="mt-0.5">{{ $offering?->room ?: 'Room not set' }} · {{ $offering?->instructor?->name ?? 'Instructor not assigned' }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-5 py-8 text-center text-sm text-slate-500">No classes assigned yet.</div>
                                @endforelse
                            </div>
                        @elseif ($isHighSchoolStudent)
                            <div class="p-4 sm:p-5">
                                <div class="flex flex-col gap-3 rounded-xl bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-lg font-black text-slate-900">{{ $activeClass->grade->grade ?? 'Grade' }} - {{ $activeClass->section ?? 'Section' }}</p>
                                        <p class="mt-1 text-sm text-slate-600">Adviser: {{ $activeClass->adviser?->name ?? 'Not assigned' }}</p>
                                    </div>
                                    <span class="text-xs font-bold text-slate-500">{{ $activeSchoolYear->school_year ?? 'Current school year' }}</span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @forelse ($dashboardClasses as $classSubject)
                                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">{{ $classSubject->subject?->name ?? 'Subject' }}</span>
                                    @empty
                                        <p class="text-sm text-slate-500">No subjects assigned yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        @else
                            <div class="px-5 py-8 text-center text-sm text-slate-500">Your active enrollment will appear here.</div>
                        @endif
                    </article>

                    <article class="portal-content-card portal-dashboard-span-4 overflow-hidden xl:col-span-4">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-5">
                            <div>
                                <h3 class="font-semibold text-slate-900">School Updates</h3>
                                <p class="text-xs text-slate-500">Latest announcements and reminders</p>
                            </div>
                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-[var(--hanan-primary)]">{{ $dashboardAnnouncements->count() }}</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @if ($assignmentNotifications->isNotEmpty())
                                <a href="{{ route('student.dashboard', ['tab' => 'assignments']) }}" class="block bg-indigo-50 px-4 py-3.5 transition hover:opacity-80 sm:px-5">
                                    <p class="text-xs font-bold uppercase tracking-wide text-[var(--hanan-primary)]">Assignment reminder</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $assignmentNotifications->count() }} new {{ \Illuminate\Support\Str::plural('assignment', $assignmentNotifications->count()) }}</p>
                                </a>
                            @endif
                            @forelse ($dashboardAnnouncements as $announcement)
                                <button type="button" class="block w-full px-4 py-3.5 text-left transition hover:bg-slate-50 sm:px-5"
                                    onclick="openPortalAnnouncement('portal-announcement-{{ $announcement->id }}', @js($announcement->title), @js($announcement->created_at?->format('F j, Y') ?? ''))">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="line-clamp-1 text-sm font-medium text-slate-900">{{ $announcement->title }}</p>
                                        <time class="shrink-0 text-[11px] text-slate-400">{{ $announcement->created_at?->format('M j') }}</time>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ \Illuminate\Support\Str::limit(trim(strip_tags($announcement->content)), 100) }}</p>
                                </button>
                            @empty
                                <div class="px-5 py-7 text-center">
                                    <p class="text-sm font-semibold text-slate-700">No active announcements</p>
                                    <p class="mt-1 text-xs text-slate-500">New school notices will appear here.</p>
                                </div>
                            @endforelse
                        </div>
                    </article>

                    <article class="portal-content-card portal-dashboard-span-8 overflow-hidden xl:col-span-8">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-5">
                            <div>
                                <h3 class="font-semibold text-slate-900">Recent Grades</h3>
                                <p class="text-xs text-slate-500">Your latest recorded academic results</p>
                            </div>
                            <a href="{{ route('student.dashboard', ['tab' => 'class']) }}" class="text-xs font-medium text-[var(--hanan-primary)] hover:underline">View classes</a>
                        </div>
                        <div class="overflow-x-auto">
                            @if ($isCollegeStudent && $releasedCollegeGrades->isNotEmpty())
                                <table class="min-w-[620px] w-full text-left text-sm">
                                    <thead class="bg-slate-50 text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                        <tr><th class="px-5 py-2.5">Class</th><th class="px-4 py-2.5">Prelim</th><th class="px-4 py-2.5">Midterm</th><th class="px-4 py-2.5">Pre-final</th><th class="px-4 py-2.5">Final</th><th class="px-5 py-2.5 text-right">Status</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($releasedCollegeGrades->take(4) as $gradeRecord)
                                            <tr>
                                                <td class="px-5 py-3 font-medium text-slate-900">{{ $gradeRecord->programCourse?->course_code ?? 'Class' }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $gradeRecord->prelim_grade ?? '-' }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $gradeRecord->midterm_grade ?? '-' }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $gradeRecord->prefinal_grade ?? '-' }}</td>
                                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $gradeRecord->final_grade ?? '-' }}</td>
                                                <td class="px-5 py-3 text-right"><button type="button" onclick="openCollegeGradesModal('{{ $gradeRecord->id }}')" class="text-xs font-medium text-[var(--hanan-primary)] hover:underline">View</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @elseif ($isHighSchoolStudent && $recentHighSchoolGrades->isNotEmpty())
                                <table class="min-w-[620px] w-full text-left text-sm">
                                    <thead class="bg-slate-50 text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                        <tr><th class="px-5 py-2.5">Subject</th><th class="px-4 py-2.5">Q1</th><th class="px-4 py-2.5">Q2</th><th class="px-4 py-2.5">Q3</th><th class="px-5 py-2.5">Q4</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($recentHighSchoolGrades as $gradeRecord)
                                            <tr><td class="px-5 py-3 font-medium text-slate-900">{{ $gradeRecord->subject?->name ?? 'Subject' }}</td><td class="px-4 py-3">{{ $gradeRecord->q1 ?: '-' }}</td><td class="px-4 py-3">{{ $gradeRecord->q2 ?: '-' }}</td><td class="px-4 py-3">{{ $gradeRecord->q3 ?: '-' }}</td><td class="px-5 py-3">{{ $gradeRecord->q4 ?: '-' }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="px-5 py-7 text-center">
                                    <p class="text-sm font-semibold text-slate-700">No grades released yet</p>
                                    <p class="mt-1 text-xs text-slate-500">Released grades will appear here automatically.</p>
                                </div>
                            @endif
                        </div>
                    </article>

                    <article class="portal-content-card portal-dashboard-span-4 overflow-hidden xl:col-span-4">
                        @if ($paymentsModuleEnabled)
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-5">
                                <div><h3 class="font-semibold text-slate-900">Payment Summary</h3><p class="text-xs text-slate-500">Latest recorded transactions</p></div>
                                <a href="{{ route('student.dashboard', ['tab' => 'payments']) }}" class="text-xs font-medium text-[var(--hanan-primary)] hover:underline">View all</a>
                            </div>
                            <div class="px-4 py-3.5 sm:px-5">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Total recorded</p>
                                <p class="mt-1 text-2xl font-semibold text-slate-900">₱{{ number_format($totalPayments, 2) }}</p>
                            </div>
                            <div class="divide-y divide-slate-100 border-t border-slate-100">
                                @forelse ($recentPayments as $payment)
                                    <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                                        <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-800">{{ $payment->paymentType?->name ?? 'Payment' }}</p><p class="text-xs text-slate-500">{{ $payment->payment_date?->format('M j, Y') ?? 'Date not set' }}</p></div>
                                        <p class="shrink-0 text-sm font-semibold text-slate-900">₱{{ number_format((float) $payment->amount, 2) }}</p>
                                    </div>
                                @empty
                                    <p class="px-5 py-5 text-center text-sm text-slate-500">No payments recorded.</p>
                                @endforelse
                            </div>
                        @else
                            <div class="border-b border-slate-100 px-4 py-3.5 sm:px-5"><h3 class="font-semibold text-slate-900">Academic Details</h3><p class="text-xs text-slate-500">Current enrollment summary</p></div>
                            <dl class="divide-y divide-slate-100 px-4 sm:px-5">
                                <div class="flex justify-between gap-4 py-3"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">School year</dt><dd class="text-sm font-bold text-slate-800">{{ $activeSchoolYear->school_year ?? '-' }}</dd></div>
                                <div class="flex justify-between gap-4 py-3"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Enrollment</dt><dd class="text-sm font-bold text-slate-800">{{ $academicContext->label() }}</dd></div>
                                @if ($isCollegeStudent)<div class="flex justify-between gap-4 py-3"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Term</dt><dd class="text-sm font-bold text-slate-800">{{ $collegeSemesterName }}</dd></div>@endif
                            </dl>
                        @endif
                    </article>
                </div>
            </section>
            @endif

            {{-- BIRTHDAYS --}}
            @if (! $activeTab && $birthdayCelebrants->count() > 0)
            <section class="order-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Birthdays Today</h3>
                        <p class="text-xs text-slate-500">Celebrate your classmates today.</p>
                    </div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                        {{ $birthdayCelebrants->count() }}
                    </span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($birthdayCelebrants as $celebrant)
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ strtoupper($celebrant->lastname . ', ' . $celebrant->firstname) }}
                        </span>
                    @endforeach
                </div>
            </section>
            @endif
 
            {{-- SUMMARY CARDS --}}
            <!-- <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Classes Enrolled
                    </p>

                    <h3 class="mt-3 text-4xl font-bold text-slate-900">
                        {{ $classCount }}
                    </h3>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Attendance Records
                    </p>

                    <h3 class="mt-3 text-4xl font-bold text-emerald-600">
                        {{ $attendanceCount }}
                    </h3>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Quiz Records
                    </p>

                    <h3 class="mt-3 text-4xl font-bold text-indigo-600">
                        {{ $quizCount }}
                    </h3>
                </div>

            </section> -->

            {{-- SELECTED WORKSPACE --}}
            @if ($activeTab)
            <section class="order-2 min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                {{-- CLASS TAB --}}
                @if ($activeTab === 'class')

                    <div class="border-b border-slate-200 p-4 sm:p-5">
                        <form method="GET" action="{{ route('student.dashboard') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <input type="hidden" name="tab" value="class">

                            <div class="flex-1">
                                <label for="class-search" class="mb-2 block text-sm font-semibold text-slate-700">Search classes</label>
                                <input
                                    id="class-search"
                                    type="search"
                                    name="class_search"
                                    value="{{ request('class_search') }}"
                                    placeholder="Search class, section, instructor, schedule, or room"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none"
                                >
                            </div>

                            <button type="submit" class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500">
                                Search
                            </button>
                        </form>
                    </div>

                    @if ($isCollegeStudent)
                        <div class="overflow-x-auto">
                            <table class="min-w-[860px] w-full border-collapse text-left text-sm">
                                <caption class="sr-only">Enrolled college classes</caption>
                                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th scope="col" class="px-5 py-4">Section</th>
                                        <th scope="col" class="px-5 py-4">Class</th>
                                        <th scope="col" class="px-5 py-4">Instructor</th>
                                        <th scope="col" class="px-5 py-4">Schedule</th>
                                        <th scope="col" class="px-5 py-4">Room</th>
                                        <th scope="col" class="px-5 py-4">Semester</th>
                                        <th scope="col" class="px-5 py-4 text-right">Grades</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($collegeCourses as $collegeCourse)
                                        @php($offering = $collegeCourse->offering)
                                        @php($programClass = $collegeCourse->programCourse)
                                        <tr class="align-top transition hover:bg-slate-50/80">
                                            <td class="px-5 py-4 font-bold text-[var(--hanan-primary)]">
                                                {{ $offering?->section ?? '-' }}
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="font-bold text-slate-900">{{ $programClass?->course_code ?? '-' }}</p>
                                                <p class="mt-1 text-slate-600">{{ $programClass?->description ?? 'Class description not set' }}</p>
                                            </td>
                                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $offering?->instructor?->name ?? 'Not assigned' }}</td>
                                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $offering?->schedule ?: 'Not set' }}</td>
                                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $offering?->room ?: 'Not set' }}</td>
                                            <td class="px-5 py-4 text-slate-600">{{ $collegeSemesterName ?? 'Current term' }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <button
                                                    type="button"
                                                    onclick="openCollegeGradesModal('{{ $collegeCourse->id }}')"
                                                    class="inline-flex min-h-10 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                                                >
                                                    View Grades
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <p class="font-semibold text-slate-800">No enrolled classes</p>
                                                <p class="mt-1 text-sm text-slate-500">Classes will appear after they are added to your active term enrollment.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @elseif ($isHighSchoolStudent)
                    <div class="divide-y divide-slate-100 sm:hidden">
                        @forelse ($classes as $classStudent)
                            @php($class = $classStudent->class)
                            <article class="p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 font-bold text-indigo-700">
                                        {{ str($class->adviser->name ?? 'A')->substr(0, 1)->upper() }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold uppercase text-slate-900">{{ $class->adviser->name ?? '-' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">Class adviser</p>
                                    </div>
                                </div>
                                <dl class="mt-4 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-3 text-sm">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Class</dt>
                                        <dd class="mt-1 font-semibold uppercase text-slate-800">
                                            {{ $class->grade->grade ?? '-' }} - {{ $class->section ?? '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">School year</dt>
                                        <dd class="mt-1 font-semibold text-slate-800">
                                            {{ $classStudent->schoolYear->school_year ?? $class->schoolYear->school_year ?? '-' }}
                                        </dd>
                                    </div>
                                </dl>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button type="button" onclick="openGradesModal('{{ $classStudent->id }}')"
                                        class="inline-flex min-h-11 items-center rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                                        View grades
                                    </button>
                                    <a href="{{ route('student.classes.grades.download', ['classStudent' => $classStudent]) }}"
                                        class="inline-flex min-h-11 items-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                        Download PDF
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="px-5 py-12 text-center text-sm text-slate-500">No class record found.</div>
                        @endforelse
                    </div>

                <div class="hidden overflow-x-auto sm:block">

                        <table class="min-w-full">

                            <thead class="bg-slate-50">
                                <tr>

                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Adviser
                                    </th>

                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Grade
                                    </th>

                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Section
                                    </th>

                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        School Year
                                    </th>

                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Actions
                                    </th>

                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                @forelse ($classes as $classStudent)

                                    @php($class = $classStudent->class)

                                    <tr class="transition hover:bg-slate-50">

                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-4">

                                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 font-bold text-indigo-700">
                                                    {{ str($class->adviser->name ?? 'A')->substr(0, 1)->upper() }}
                                                </div>

                                                <div>
                                                    <p class="font-semibold text-slate-900 uppercase">
                                                        {{ $class->adviser->name ?? '-' }}
                                                    </p>

                                                    <p class="text-sm text-slate-500">
                                                        Adviser
                                                    </p>
                                                </div>

                                            </div>
                                        </td>

                                        <td class="px-6 py-5 text-sm font-medium text-slate-700 uppercase">
                                            {{ $class->grade->grade ?? '-' }}
                                        </td>

                                        <td class="px-6 py-5 text-sm text-slate-600 uppercase">
                                            {{ $class->section ?? '-' }}
                                        </td>

                                        <td class="px-6 py-5 text-sm text-slate-600">
                                            {{ $classStudent->schoolYear->school_year ?? $class->schoolYear->school_year ?? '-' }}
                                        </td>

                                        <td class="px-6 py-5 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onclick="openGradesModal('{{ $classStudent->id }}')"
                                                    class="inline-flex min-h-11 items-center rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                                                >
                                                    View
                                                </button>

                                                <a
                                                    href="{{ route('student.classes.grades.download', ['classStudent' => $classStudent]) }}"
                                                    class="inline-flex min-h-11 items-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                                >
                                                    Download PDF
                                                </a>
                                            </div>
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                            No class record found.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>
                    @elseif ($hasEnrollmentConflict)
                        <div class="px-5 py-12 text-center">
                            <p class="font-semibold text-red-900">Classes are temporarily unavailable</p>
                            <p class="mt-1 text-sm text-red-600">An administrator must resolve the active enrollment conflict first.</p>
                        </div>
                    @else
                        <div class="px-5 py-12 text-center">
                            <p class="font-semibold text-slate-800">No active enrollment</p>
                            <p class="mt-1 text-sm text-slate-500">Your current classes will appear here after enrollment is activated.</p>
                        </div>
                    @endif

                {{-- SEMESTER HISTORY TAB --}}
                @elseif ($activeTab === 'history' && $hasCollegeHistory)

                    @include('portals.student.partials.college-semester-history-tab', [
                        'collegeEnrollmentHistory' => $collegeEnrollmentHistory,
                        'historyEnrollment' => $historyEnrollment,
                    ])

                {{-- ATTENDANCE TAB --}}
                @elseif ($activeTab === 'attendance')

                    <div class="border-b border-slate-200 p-4 sm:p-5">
                        <form method="GET" action="{{ route('student.dashboard') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <input type="hidden" name="tab" value="attendance">

                            <div class="flex-1">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Search attendance
                                </label>
                                <input
                                    type="search"
                                    name="attendance_search"
                                    value="{{ request('attendance_search') }}"
                                    placeholder="Search date or time"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none"
                                >
                            </div>

                            <button
                                type="submit"
                                class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500"
                            >
                                Search
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">

                        <table class="min-w-full">

                            <thead class="bg-slate-50">
                                <tr>

                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Date
                                    </th>

                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Logged Time
                                    </th>

                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                @forelse ($attendance as $entry)

                                    <tr class="hover:bg-slate-50">

                                        <td class="px-6 py-5 text-sm text-slate-700">
                                            {{ $entry->currentdate?->format('F j, Y') ?? '-' }}
                                        </td>

                                        <td class="px-6 py-5">
                                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                {{ $entry->logged_time?->format('h:i A') ?? '-' }}
                                            </span>
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="2" class="px-6 py-12 text-center text-sm text-slate-500">
                                            No attendance records found.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    <div class="border-t border-slate-200 bg-slate-50 px-4 sm:px-6 py-4">
                        {{ $attendance->links() }}
                    </div>

                {{-- ASSIGNMENTS TAB --}}
                @elseif ($activeTab === 'assignments')

                    @include('portals.student.partials.assignments-tab', [
                        'assignments' => $assignments,
                        'assignmentNotifications' => $assignmentNotifications,
                    ])

                @elseif ($activeTab === 'payments' && $paymentsModuleEnabled)

                    @include('portals.student.partials.payment-history-tab', [
                        'paymentHistories' => $paymentHistories,
                    ])

                {{-- QUIZ TAB --}}
                @elseif ($activeTab === 'quiz' && $quizModuleEnabled)

                    @include('portals.student.partials.quiz-tab', [
                        'todayQuizzes' => $todayQuizzes,
                        'quizzes' => $quizzes,
                    ])

                @endif

            </section>
            @endif

        </div>

    </div>

</div>

<div id="studentGradesModal" role="dialog" aria-modal="true" aria-labelledby="studentGradesModalTitle"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-4xl rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h3 id="studentGradesModalTitle" class="text-xl font-bold text-slate-900">
                    Student Grades
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                    View your subject grades.
                </p>
            </div>

            <button type="button" onclick="closeStudentGradesModal()" data-dialog-close aria-label="Close student grades"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100">
                &times;
            </button>
        </div>

        <div id="studentGradesModalContent" class="p-6">
            <p class="text-sm text-slate-500">Loading grades...</p>
        </div>

        <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" onclick="closeStudentGradesModal()" data-dialog-close
                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    const studentGradesModalRouteTemplate = @json(route('student.classes.grades.modal', ['classStudent' => '__ID__']));
    const studentCollegeGradesModalRouteTemplate = @json(route('student.college-classes.grades.modal', ['collegeEnrollmentCourse' => '__ID__']));

    function openGradesModal(classStudentId) {
        const modal = document.getElementById('studentGradesModal');
        const content = document.getElementById('studentGradesModalContent');

        window.portalDialog.open(modal);
        content.innerHTML = `<p class="text-sm text-slate-500">Loading grades...</p>`;

        const url = studentGradesModalRouteTemplate.replace('__ID__', classStudentId);

        fetch(url)
            .then((response) => response.text())
            .then((html) => {
                content.innerHTML = html;
            })
            .catch(() => {
                content.innerHTML = `<p class="text-sm text-red-500">Failed to load grades.</p>`;
            });
    }

    function openCollegeGradesModal(collegeEnrollmentCourseId) {
        const modal = document.getElementById('studentGradesModal');
        const content = document.getElementById('studentGradesModalContent');

        window.portalDialog.open(modal);
        content.innerHTML = `<p class="text-sm text-slate-500">Loading grades...</p>`;

        const url = studentCollegeGradesModalRouteTemplate.replace('__ID__', collegeEnrollmentCourseId);

        fetch(url)
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Unable to load grades');
                }

                return response.text();
            })
            .then((html) => {
                content.innerHTML = html;
            })
            .catch(() => {
                content.innerHTML = `<p class="text-sm text-red-500">Failed to load grades.</p>`;
            });
    }

    function closeStudentGradesModal() {
        const modal = document.getElementById('studentGradesModal');
        window.portalDialog.close(modal);
    }
</script>
@endsection
