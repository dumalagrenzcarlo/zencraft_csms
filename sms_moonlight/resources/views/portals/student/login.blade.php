<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <title>{{ \App\Support\SchoolBranding::name() }} Student Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $theme = array_merge([
            'primary' => '#8F160F',
            'secondary' => '#B32317',
            'background' => '#FFF7F1',
            'text' => '#2F1A17',
            'accent' => '#D7A83D',
            'alert' => '#D92D20',
            'surface' => '#FFFFFF',
        ], config('school_portal.theme', []));
    @endphp
    <style>
        :root {
            --portal-primary: {{ $theme['primary'] }};
            --portal-secondary: {{ $theme['secondary'] }};
            --portal-background: {{ $theme['background'] }};
            --portal-text: {{ $theme['text'] }};
            --portal-accent: {{ $theme['accent'] }};
            --portal-alert: {{ $theme['alert'] }};
            --portal-surface: {{ $theme['surface'] }};
        }
    </style>
</head>
<body class="min-h-screen text-[var(--portal-text)]" style="background: var(--portal-background);">
    @php
        $logo = \App\Support\SchoolBranding::logoUrl();
        $schoolName = \App\Support\SchoolBranding::name();
    @endphp

    <main class="flex min-h-screen items-center justify-center px-5 py-10">
        <section class="w-full max-w-md rounded-[2rem] border bg-[var(--portal-surface)] p-6 shadow-2xl sm:p-8" style="border-color: color-mix(in srgb, var(--portal-primary) 28%, #ffffff); box-shadow: 0 24px 70px color-mix(in srgb, var(--portal-primary) 16%, transparent);">
            <div class="text-center">
                <img src="{{ $logo }}" alt="School Logo" class="mx-auto h-24 w-24 rounded-3xl border bg-white object-contain p-3 shadow-sm" style="border-color: color-mix(in srgb, var(--portal-primary) 12%, #ffffff);">
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.22em]" style="color: var(--portal-primary);">Student Portal</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight" style="color: var(--portal-text);">{{ $schoolName }}</h1>
                <p class="mt-2 text-sm text-slate-500">Sign in to view your classes, assignments and activities, attendance, and grades.</p>
            </div>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border px-4 py-3 text-sm font-medium" style="border-color: color-mix(in srgb, var(--portal-alert) 18%, #ffffff); background: color-mix(in srgb, var(--portal-alert) 8%, #ffffff); color: var(--portal-alert);">{{ $errors->first() }}</div>
            @endif

            <form class="mt-6 space-y-4" method="POST" action="{{ route('student.login.submit') }}">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-semibold" style="color: var(--portal-text);" for="lrn">Student Number</label>
                    <input class="w-full rounded-2xl border bg-white px-4 py-3 text-sm outline-none transition focus:ring-4" style="border-color: color-mix(in srgb, var(--portal-primary) 22%, #ffffff); --tw-ring-color: color-mix(in srgb, var(--portal-primary) 16%, transparent);" id="lrn" name="lrn" type="text" value="{{ old('lrn') }}" required autofocus>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold" style="color: var(--portal-text);" for="password">Password</label>
                    <div class="relative">
                        <input class="w-full rounded-2xl border bg-white py-3 text-sm outline-none transition focus:ring-4" style="padding-left: 1rem; padding-right: 5rem; border-color: color-mix(in srgb, var(--portal-primary) 22%, #ffffff); --tw-ring-color: color-mix(in srgb, var(--portal-primary) 16%, transparent);" id="password" name="password" type="password" autocomplete="current-password" required>
                        <button class="password-visibility-toggle absolute inset-y-0 right-0 px-4 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-inset" style="color: var(--portal-primary); --tw-ring-color: color-mix(in srgb, var(--portal-primary) 45%, transparent);" type="button" aria-controls="password" aria-label="Show password" aria-pressed="false">Show</button>
                    </div>
                </div>
                <button class="w-full rounded-2xl px-4 py-3 font-bold text-white transition focus:outline-none focus:ring-4" style="background: var(--portal-primary); --tw-ring-color: color-mix(in srgb, var(--portal-primary) 24%, transparent);" type="submit">Sign in</button>
            </form>

            <a href="{{ route('portal.selection') }}"
                class="mt-4 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-[var(--portal-primary)] focus:outline-none focus:ring-4"
                style="--tw-ring-color: color-mix(in srgb, var(--portal-primary) 14%, transparent);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                Back to portal selection
            </a>

            @if(!empty($announcements) && $announcements->count())
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-slate-900">Announcements</p>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-500">{{ $announcements->count() }}</span>
                    </div>
                    <div class="mt-3 space-y-2">
                        @foreach($announcements->take(2) as $announcement)
                            <div class="border-t border-slate-200 pt-2 first:border-t-0 first:pt-0">
                                <p class="text-sm font-semibold text-slate-800">{{ $announcement->title }}</p>
                                <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ trim(strip_tags($announcement->content)) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </main>
    <script>
        document.querySelector('.password-visibility-toggle')?.addEventListener('click', (event) => {
            const button = event.currentTarget;
            const input = document.getElementById('password');
            const isVisible = input.type === 'text';

            input.type = isVisible ? 'password' : 'text';
            button.textContent = isVisible ? 'Show' : 'Hide';
            button.setAttribute('aria-label', `${isVisible ? 'Show' : 'Hide'} password`);
            button.setAttribute('aria-pressed', String(!isVisible));
        });
    </script>
</body>
</html>
