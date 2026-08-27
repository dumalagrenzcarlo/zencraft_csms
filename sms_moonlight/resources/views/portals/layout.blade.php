<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Portal' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/file-upload-preview.css') }}">
    <script src="{{ asset('js/file-upload-preview.js') }}" defer></script>
</head>
<body class="portal-theme min-h-screen bg-slate-100 text-slate-900">
    @php
        $theme = array_merge([
            'primary' => '#F7BC3B',
            'secondary' => '#D97706',
            'background' => '#F8F1E5',
            'text' => '#454548',
            'accent' => '#38BDF8',
            'alert' => '#E94560',
        ], config('school_portal.theme', []));

        $portalName = $portal ?? 'Portal';
        $headingText = $heading ?? 'ZenCraft Systems';
        $isStudentPortal = $portalName === 'Student Portal';
        $isTeacherPortal = $portalName === 'Teacher Portal';
        $isAuthenticatedPortal = $isStudentPortal || $isTeacherPortal;

        $portalLogoUrl = \App\Support\SchoolBranding::logoUrl();

        $portalHomeRoute = match (true) {
            $isStudentPortal => route('student.dashboard'),
            $isTeacherPortal => route('teacher.dashboard', ['context' => $portalContext ?? 'adviser']),
            default => url('/'),
        };

        $portalUserName = $isTeacherPortal
            ? ($teacher->name ?? 'Teacher')
            : trim(collect([
                $student->firstname ?? null,
                $student->middlename ?? null,
                $student->lastname ?? null,
            ])->filter()->implode(' '));
        $portalUserName = filled($portalUserName) ? $portalUserName : ($isTeacherPortal ? 'Teacher' : 'Student');
        $portalUserRole = $isTeacherPortal
            ? (($portalContext ?? 'adviser') === 'instructor' ? 'College Instructor' : 'Class Adviser')
            : 'Student';
        $portalProfilePhoto = $photoPath ?? null;
        $portalInitials = collect(explode(' ', $portalUserName))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
        $activePortalTab = $activeTab ?? request('tab');
        $portalNavItems = $isTeacherPortal
            ? (($portalContext ?? 'adviser') === 'instructor' ? [
                ['tab' => 'college-grades', 'label' => 'College Grades'],
                ['tab' => 'attendance', 'label' => 'Attendance'],
            ] : [
                ['tab' => 'students', 'label' => 'Students'],
                ['tab' => 'assignments', 'label' => 'Assignments and Activities'],
                ['tab' => 'attendance', 'label' => 'Attendance'],
                ['tab' => 'schedules', 'label' => 'Schedules'],
            ])
            : [
                ['tab' => 'class', 'label' => 'Class'],
                ...(($isHighSchoolStudent ?? false) ? [['tab' => 'assignments', 'label' => 'Assignments']] : []),
                ['tab' => 'attendance', 'label' => 'Attendance'],
                ...(($paymentsModuleEnabled ?? false) ? [['tab' => 'payments', 'label' => 'Payments']] : []),
                ...(($quizModuleEnabled ?? false) && ($isHighSchoolStudent ?? false)
                    ? [['tab' => 'quiz', 'label' => 'Quiz of the Day']]
                    : []),
            ];

        $headerAnnouncements = collect();
        if (($isStudentPortal || $isTeacherPortal) && \Illuminate\Support\Facades\Auth::guard('moonshine')->check()) {
            $announcementAudiences = $isTeacherPortal ? ['teachers', 'both'] : ['students', 'both'];

            $headerAnnouncements = \App\Models\Announcement::query()
                ->whereIn('target_audience', $announcementAudiences)
                ->where(function ($query): void {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>=', now());
                })
                ->orderByDesc('created_at')
                ->get();
        }
    @endphp
    <style>
        :root {
            --hanan-primary: {{ $theme['primary'] }};
            --hanan-secondary: {{ $theme['secondary'] }};
            --hanan-bg-light: {{ $theme['background'] }};
            --hanan-text-dark: {{ $theme['text'] }};
            --hanan-accent-blue: {{ $theme['accent'] }};
            --hanan-alert-red: {{ $theme['alert'] }};
        }
        body {
            background: #f1f5f9;
            color: var(--hanan-text-dark);
        }
        .portal-theme .text-sky-600,
        .portal-theme .hover\:text-sky-700:hover,
        .portal-theme .text-indigo-600,
        .portal-theme .text-indigo-700,
        .portal-theme .text-indigo-900,
        .portal-theme .text-indigo-950 {
            color: var(--hanan-primary) !important;
        }
        .portal-theme .bg-indigo-600,
        .portal-theme .bg-slate-900 {
            background: var(--hanan-primary) !important;
            color: #ffffff !important;
        }
        .portal-theme .hover\:bg-indigo-500:hover,
        .portal-theme .hover\:bg-slate-700:hover {
            background: var(--hanan-secondary) !important;
            color: #ffffff !important;
        }
        .portal-theme .bg-indigo-600 *,
        .portal-theme .bg-slate-900 * {
            color: inherit;
        }
        .portal-theme .bg-indigo-50,
        .portal-theme .bg-indigo-100 {
            background: color-mix(in srgb, var(--hanan-primary) 10%, #ffffff) !important;
        }
        .portal-theme .border-indigo-100,
        .portal-theme .border-indigo-200 {
            border-color: color-mix(in srgb, var(--hanan-primary) 18%, #ffffff) !important;
        }
        .portal-theme .focus\:border-indigo-500:focus {
            border-color: var(--hanan-primary) !important;
        }
        .portal-theme .focus\:ring-indigo-500:focus,
        .portal-theme .focus\:ring-indigo-100:focus {
            --tw-ring-color: color-mix(in srgb, var(--hanan-primary) 20%, transparent) !important;
        }
        .portal-theme .bg-amber-500 {
            background: var(--hanan-accent-blue) !important;
            color: #ffffff !important;
        }
        .portal-theme .hover\:bg-amber-400:hover {
            background: color-mix(in srgb, var(--hanan-accent-blue) 82%, #000000) !important;
            color: #ffffff !important;
        }
        #portal-page-loader {
            position: fixed;
            z-index: 99999;
            inset: 0;
            display: none;
            place-items: center;
            background: rgb(241 245 249 / 0.78);
            backdrop-filter: blur(2px);
        }
        #portal-page-loader.is-visible { display: grid; }
        .portal-page-loader-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #ffffff;
            padding: 0.9rem 1.1rem;
            box-shadow: 0 16px 45px rgb(15 23 42 / 0.14);
            font-size: 0.9rem;
            font-weight: 700;
        }
        .portal-page-loader-spinner {
            width: 1.5rem;
            height: 1.5rem;
            border: 3px solid color-mix(in srgb, var(--hanan-primary) 20%, #ffffff);
            border-top-color: var(--hanan-primary);
            border-radius: 9999px;
            animation: portal-loader-spin 0.7s linear infinite;
        }
        .portal-tab-scroller {
            scrollbar-width: none;
        }
        .portal-tab-scroller::-webkit-scrollbar {
            display: none;
        }
        .portal-shell {
            min-height: 100vh;
            background:
                radial-gradient(circle at 84% -8%, color-mix(in srgb, var(--hanan-primary) 6%, transparent), transparent 26rem),
                #f7f9fb;
        }
        .portal-sidebar {
            box-shadow: 1px 0 0 rgb(226 232 240 / 0.9);
        }
        .portal-sidebar-link {
            color: #58413f;
        }
        .portal-sidebar-link:hover {
            background: #f2f4f6;
            color: var(--hanan-primary);
        }
        .portal-sidebar-link.is-active {
            background: var(--hanan-primary);
            color: #fff;
            box-shadow: 0 8px 20px color-mix(in srgb, var(--hanan-primary) 16%, transparent);
        }
        .portal-sidebar-link.is-active * {
            color: inherit;
        }
        .portal-mobile-overlay {
            opacity: 0;
            pointer-events: none;
            transition: opacity 180ms ease;
        }
        .portal-mobile-overlay.is-open {
            opacity: 1;
            pointer-events: auto;
        }
        #portalSidebar {
            transition: translate 220ms ease;
        }
        #portalSidebar.is-open {
            translate: 0;
        }
        .portal-content-card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 12px 38px rgb(15 23 42 / 0.035);
        }
        .portal-dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
        }
        .portal-dialog {
            position: fixed !important;
            inset: 0 !important;
            z-index: 10000 !important;
        }
        .portal-announcements > summary::-webkit-details-marker {
            display: none;
        }
        .announcement-html {
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.7;
        }
        .announcement-html > * + * {
            margin-top: 0.85rem;
        }
        .announcement-html h1,
        .announcement-html h2,
        .announcement-html h3,
        .announcement-html h4 {
            color: #0f172a;
            font-weight: 800;
            line-height: 1.25;
        }
        .announcement-html h1 { font-size: 1.5rem; }
        .announcement-html h2 { font-size: 1.25rem; }
        .announcement-html h3,
        .announcement-html h4 { font-size: 1.05rem; }
        .announcement-html ul {
            list-style: disc;
            padding-left: 1.5rem;
        }
        .announcement-html ol {
            list-style: decimal;
            padding-left: 1.5rem;
        }
        .announcement-html blockquote {
            border-left: 3px solid var(--hanan-primary);
            padding-left: 1rem;
            color: #64748b;
        }
        .announcement-html a {
            color: var(--hanan-primary);
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .announcement-html code {
            border-radius: 0.35rem;
            background: #f1f5f9;
            padding: 0.1rem 0.35rem;
            color: #334155;
            font-size: 0.875em;
        }
        .announcement-html pre {
            overflow-x: auto;
            border-radius: 0.75rem;
            background: #0f172a;
            padding: 1rem;
            color: #e2e8f0;
        }
        .announcement-html pre code {
            background: transparent;
            padding: 0;
            color: inherit;
        }
        @media (min-width: 1024px) {
            #portalSidebar {
                translate: 0;
            }
        }
        @media (min-width: 1280px) {
            .portal-dashboard-grid {
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }
            .portal-dashboard-span-4 { grid-column: span 4 / span 4; }
            .portal-dashboard-span-8 { grid-column: span 8 / span 8; }
        }
        @keyframes portal-loader-spin { to { transform: rotate(360deg); } }
    </style>
    <div class="portal-shell">
        @if ($isAuthenticatedPortal)
            <div id="portalMobileOverlay" class="portal-mobile-overlay fixed inset-0 z-[55] bg-slate-950/45 backdrop-blur-sm lg:hidden" onclick="closePortalSidebar()"></div>
            <aside id="portalSidebar" class="portal-sidebar fixed inset-y-0 left-0 z-[60] flex w-[280px] -translate-x-full flex-col overflow-y-auto bg-white p-5 lg:translate-x-0">
                <div class="flex items-center justify-between gap-3 px-1">
                    <a href="{{ $portalHomeRoute }}" class="flex min-w-0 items-center gap-3">
                        @if (filled($portalLogoUrl))
                            <img src="{{ $portalLogoUrl }}" alt="School Logo" class="h-11 w-11 shrink-0 rounded-xl object-cover">
                        @else
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[var(--hanan-primary)] text-sm font-black text-white">HS</div>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black uppercase tracking-[0.08em] text-[var(--hanan-primary)]">{{ config('app.name', 'School Portal') }}</p>
                            <p class="truncate text-xs font-medium text-slate-500">{{ $portalName }}</p>
                        </div>
                    </a>
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 lg:hidden" onclick="closePortalSidebar()" aria-label="Close navigation">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                    </button>
                </div>

                <a href="{{ $isTeacherPortal ? route('teacher.profile', ['context' => $portalContext ?? 'adviser']) : route('student.profile') }}" class="mt-7 overflow-hidden rounded-2xl bg-[var(--hanan-primary)] p-5 text-center text-white shadow-sm transition hover:opacity-95">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-white/25 bg-white/10">
                        @if ($portalProfilePhoto)
                            <img src="{{ $portalProfilePhoto }}" alt="{{ $portalUserName }}" class="h-full w-full object-cover">
                        @else
                            <span class="text-xl font-black">{{ $portalInitials }}</span>
                        @endif
                    </div>
                    <p class="mt-3 whitespace-normal break-words text-base font-extrabold leading-tight">{{ $portalUserName }}</p>
                    <p class="mt-0.5 text-[11px] font-bold uppercase tracking-[0.16em] text-white/75">{{ $portalUserRole }}</p>
                </a>

                @if ($isTeacherPortal && ($canUseAdviserContext ?? false) && ($canUseInstructorContext ?? false))
                    <div class="mt-5 rounded-2xl bg-slate-100 p-1" aria-label="Teacher workspace">
                        <div class="grid grid-cols-2 gap-1">
                            <a href="{{ route('teacher.dashboard', ['context' => 'adviser']) }}"
                                class="rounded-xl px-2 py-2.5 text-center text-xs font-bold transition {{ ($portalContext ?? 'adviser') === 'adviser' ? 'bg-white text-[var(--hanan-primary)] shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                                Adviser
                            </a>
                            <a href="{{ route('teacher.dashboard', ['context' => 'instructor']) }}"
                                class="rounded-xl px-2 py-2.5 text-center text-xs font-bold transition {{ ($portalContext ?? 'adviser') === 'instructor' ? 'bg-white text-[var(--hanan-primary)] shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                                Instructor
                            </a>
                        </div>
                    </div>
                @endif

                <nav aria-label="{{ $portalName }}" class="mt-7 flex flex-1 flex-col gap-1.5">
                    <a href="{{ $portalHomeRoute }}" class="portal-sidebar-link {{ request()->routeIs($isTeacherPortal ? 'teacher.dashboard' : 'student.dashboard') && !request()->has('tab') ? 'is-active' : '' }} flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        Dashboard
                    </a>
                    @foreach ($portalNavItems as $navItem)
                        <a href="{{ route($isTeacherPortal ? 'teacher.dashboard' : 'student.dashboard', array_filter(['context' => $isTeacherPortal ? ($portalContext ?? 'adviser') : null, 'tab' => $navItem['tab']])) }}" class="portal-sidebar-link {{ $activePortalTab === $navItem['tab'] ? 'is-active' : '' }} flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold">
                            @switch($navItem['tab'])
                                @case('students')
                                @case('class')
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                                    @break
                                @case('assignments')
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
                                    @break
                                @case('college-grades')
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5V6.75L12 3l8 3.75V19.5"/><path d="M8 10h8M8 14h8M8 18h8"/></svg>
                                    @break
                                @case('attendance')
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m9 11 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                    @break
                                @case('schedules')
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                                    @break
                                @case('payments')
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                                    @break
                                @case('quiz')
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.7.3-1 .8-1 1.7M12 17h.01"/></svg>
                                    @break
                            @endswitch
                            {{ $navItem['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="mt-5 flex flex-col gap-1.5 border-t border-slate-200 pt-5">
                    @if (! $isTeacherPortal && ($hasCollegeHistory ?? false))
                        <a href="{{ route('student.dashboard', ['tab' => 'history']) }}" class="portal-sidebar-link {{ $activePortalTab === 'history' ? 'is-active' : '' }} flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 3-6.7"/>
                                <path d="M3 4v5h5M12 7v5l3 2"/>
                            </svg>
                            Class History
                        </a>
                    @endif
                    <a href="{{ $isTeacherPortal ? route('teacher.profile', ['context' => $portalContext ?? 'adviser']) : route('student.profile') }}" class="portal-sidebar-link flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        Profile
                    </a>
                </div>
            </aside>
        @endif

        <div class="{{ $isAuthenticatedPortal ? 'lg:pl-[280px]' : '' }}">
            <header class="sticky top-0 z-50 border-b border-slate-200/90 bg-white/95 backdrop-blur">
                <div class="flex min-h-[74px] items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($isAuthenticatedPortal)
                            <button id="portalMenuButton" type="button" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden" onclick="openPortalSidebar()" aria-label="Open navigation" aria-controls="portalSidebar" aria-expanded="false">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                            </button>
                        @endif
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h1 class="truncate text-lg font-bold text-[var(--hanan-primary)] sm:text-2xl">{{ $portalName }}</h1>
                                <span class="hidden text-slate-300 sm:inline">/</span>
                                <span class="hidden truncate text-sm font-normal text-slate-600 sm:inline">{{ $headingText }}</span>
                            </div>
                            <p class="truncate text-xs text-slate-500 lg:hidden">{{ $portalUserName }}</p>
                        </div>
                    </div>

                    <nav aria-label="Account" class="flex shrink-0 items-center gap-1.5 text-sm sm:gap-2">
                        @if ($isAuthenticatedPortal)
                            <details class="portal-announcements relative">
                                <summary class="relative inline-flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 hover:text-[var(--hanan-primary)]" aria-label="View announcements">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                                    @if ($headerAnnouncements->isNotEmpty())
                                        <span class="absolute right-0.5 top-0.5 inline-flex min-h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white">{{ $headerAnnouncements->count() > 99 ? '99+' : $headerAnnouncements->count() }}</span>
                                    @endif
                                </summary>
                                @include('portals.partials.announcement-menu', ['headerAnnouncements' => $headerAnnouncements])
                            </details>
                            <a class="hidden min-h-11 items-center rounded-xl px-3 font-normal text-slate-600 hover:bg-slate-100 hover:text-[var(--hanan-primary)] sm:inline-flex" href="{{ $isTeacherPortal ? route('teacher.password.form') : route('student.password.form') }}">Change Password</a>
                            <form method="POST" action="{{ $isTeacherPortal ? route('teacher.logout') : route('student.logout') }}">
                                @csrf
                                <button class="min-h-11 rounded-full bg-[var(--hanan-primary)] px-4 py-2 font-semibold text-white transition hover:opacity-90 sm:px-6" type="submit">Logout</button>
                            </form>
                        @endif
                    </nav>
                </div>
            </header>

    @if ($isStudentPortal || $isTeacherPortal)
        <div
            id="portalAnnouncementModal"
            class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="portalAnnouncementTitle"
        >
            <div class="flex max-h-[min(42rem,calc(100vh-2rem))] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-sky-600">Announcement</p>
                        <h2 id="portalAnnouncementTitle" class="mt-1 text-xl font-bold text-slate-900"></h2>
                        <p id="portalAnnouncementDate" class="mt-1 text-xs font-medium text-slate-500"></p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                        aria-label="Close announcement"
                        data-dialog-close
                        onclick="closePortalAnnouncement()"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
                    <div id="portalAnnouncementContent" class="announcement-html"></div>
                </div>
            </div>
        </div>
    @endif

            <main class="mx-auto {{ $isTeacherPortal ? 'max-w-[1440px]' : 'max-w-[1320px]' }} px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @yield('content')
            </main>
        </div>
    </div>
    <div id="portal-page-loader" role="status" aria-live="polite" aria-label="Loading page">
        <div class="portal-page-loader-card">
            <span class="portal-page-loader-spinner" aria-hidden="true"></span>
            <span>Loading page…</span>
        </div>
    </div>
    <script>
        window.openPortalSidebar = () => {
            document.getElementById('portalSidebar')?.classList.add('is-open');
            document.getElementById('portalMobileOverlay')?.classList.add('is-open');
            document.getElementById('portalMenuButton')?.setAttribute('aria-expanded', 'true');
            document.body.classList.add('overflow-hidden');
        };
        window.closePortalSidebar = () => {
            document.getElementById('portalSidebar')?.classList.remove('is-open');
            document.getElementById('portalMobileOverlay')?.classList.remove('is-open');
            document.getElementById('portalMenuButton')?.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('overflow-hidden');
        };

        (() => {
            let activeDialog = null;
            let returnFocus = null;
            const focusableSelector = [
                'a[href]',
                'button:not([disabled])',
                'input:not([disabled])',
                'select:not([disabled])',
                'textarea:not([disabled])',
                '[tabindex]:not([tabindex="-1"])',
            ].join(',');

            window.portalDialog = {
                open(dialog) {
                    if (!dialog) return;
                    returnFocus = document.activeElement;
                    activeDialog = dialog;
                    dialog.classList.add('portal-dialog');
                    if (dialog.parentElement !== document.body) {
                        document.body.appendChild(dialog);
                    }
                    dialog.classList.remove('hidden');
                    dialog.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                    requestAnimationFrame(() => dialog.querySelector(focusableSelector)?.focus());
                },
                close(dialog) {
                    if (!dialog) return;
                    dialog.classList.add('hidden');
                    dialog.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                    activeDialog = null;
                    if (returnFocus instanceof HTMLElement) returnFocus.focus();
                },
            };

            window.openPortalAnnouncement = (templateId, title, date) => {
                const dialog = document.getElementById('portalAnnouncementModal');
                const template = document.getElementById(templateId);
                if (!dialog || !template) return;

                document.getElementById('portalAnnouncementTitle').textContent = title;
                document.getElementById('portalAnnouncementDate').textContent = date;
                document.getElementById('portalAnnouncementContent').innerHTML = template.innerHTML;
                const menu = template.closest('.portal-announcements');
                menu?.removeAttribute('open');
                menu?.querySelector('summary')?.focus();
                window.portalDialog.open(dialog);
            };

            window.closePortalAnnouncement = () => {
                window.portalDialog.close(document.getElementById('portalAnnouncementModal'));
            };

            document.addEventListener('keydown', (event) => {
                if (!activeDialog) return;
                if (event.key === 'Escape') {
                    event.preventDefault();
                    activeDialog.querySelector('[data-dialog-close]')?.click();
                    return;
                }
                if (event.key !== 'Tab') return;

                const focusable = Array.from(activeDialog.querySelectorAll(focusableSelector))
                    .filter((element) => element instanceof HTMLElement && element.offsetParent !== null);
                if (!focusable.length) return;

                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            });

            document.addEventListener('click', (event) => {
                document.querySelectorAll('.portal-announcements[open]').forEach((menu) => {
                    if (!menu.contains(event.target)) menu.removeAttribute('open');
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                document.querySelectorAll('.portal-announcements[open]').forEach((menu) => {
                    menu.removeAttribute('open');
                    menu.querySelector('summary')?.focus();
                });
            });
        })();

        (() => {
            let showTimer;
            let safetyTimer;
            const loader = document.getElementById('portal-page-loader');
            const hide = () => {
                clearTimeout(showTimer);
                clearTimeout(safetyTimer);
                loader.classList.remove('is-visible');
            };
            const show = () => {
                clearTimeout(showTimer);
                showTimer = setTimeout(() => {
                    loader.classList.add('is-visible');
                    safetyTimer = setTimeout(hide, 15000);
                }, 120);
            };

            document.addEventListener('click', (event) => {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                const link = event.target.closest('a[href]');
                if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
                const url = new URL(link.href, window.location.href);
                if (url.origin !== window.location.origin || (url.hash && url.pathname === window.location.pathname)) return;
                if (url.pathname.includes('/export') || url.pathname.includes('/download')) return;
                show();
            });
            document.addEventListener('submit', (event) => {
                if (!event.defaultPrevented) show();
            });
            window.addEventListener('pageshow', hide);
            window.addEventListener('beforeunload', show);
        })();
    </script>
    @stack('scripts')
</body>
</html>
