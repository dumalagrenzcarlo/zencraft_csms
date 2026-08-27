@php
    $theme = array_merge([
        'primary' => '#F7BC3B',
        'secondary' => '#D97706',
        'background' => '#F8F1E5',
        'text' => '#303238',
        'accent' => '#38BDF8',
        'alert' => '#E94560',
        'surface' => '#FFFFFF',
    ], config('school_portal.theme', []));

    $schoolLogoUrl = \App\Support\SchoolBranding::logoUrl();
    $schoolName = \App\Support\SchoolBranding::name();
    $quizModuleEnabled = (bool) config('school_portal.features.quiz_module');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $schoolName }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --portal-primary: {{ $theme['primary'] }};
            --portal-secondary: {{ $theme['secondary'] }};
            --portal-background: {{ $theme['background'] }};
            --portal-text: {{ $theme['text'] }};
            --portal-accent: {{ $theme['accent'] }};
            --portal-alert: {{ $theme['alert'] }};
            --portal-surface: {{ $theme['surface'] }};
            --portal-muted: color-mix(in srgb, var(--portal-text) 64%, #ffffff);
            --portal-line: color-mix(in srgb, var(--portal-primary) 24%, #ffffff);
            --portal-soft: color-mix(in srgb, var(--portal-primary) 7%, #ffffff);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--portal-text);
            background: var(--portal-background);
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        main {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }

        .welcome-panel {
            width: min(100%, 760px);
            border: 1px solid var(--portal-line);
            border-radius: 32px;
            background: color-mix(in srgb, var(--portal-surface) 94%, transparent);
            box-shadow: 0 24px 70px color-mix(in srgb, var(--portal-primary) 16%, transparent);
            overflow: hidden;
        }

        .brand-area {
            padding: 36px 28px 28px;
            text-align: center;
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--portal-primary) 9%, #ffffff), transparent 70%),
                var(--portal-surface);
        }

        .school-logo-frame {
            display: inline-flex;
            width: 112px;
            height: 112px;
            align-items: center;
            justify-content: center;
            border: 1px solid color-mix(in srgb, var(--portal-primary) 14%, #ffffff);
            border-radius: 28px;
            background: #ffffff;
            box-shadow: 0 16px 34px color-mix(in srgb, var(--portal-primary) 12%, transparent);
        }

        .school-logo {
            width: 88px;
            height: 88px;
            object-fit: contain;
        }

        .portal-label {
            margin: 22px 0 0;
            color: var(--portal-primary);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }

        h1 {
            margin: 10px 0 0;
            color: var(--portal-text);
            font-size: clamp(1.9rem, 5vw, 3.25rem);
            font-weight: 800;
            line-height: 1.08;
        }

        .welcome-copy {
            width: min(100%, 520px);
            margin: 14px auto 0;
            color: var(--portal-muted);
            font-size: 1rem;
            line-height: 1.65;
        }

        .portal-list {
            display: grid;
            gap: 12px;
            padding: 24px;
            border-top: 1px solid color-mix(in srgb, var(--portal-primary) 12%, #ffffff);
            background: var(--portal-soft);
        }

        .portal-link {
            display: grid;
            grid-template-columns: 52px 1fr auto;
            align-items: center;
            gap: 16px;
            min-height: 88px;
            padding: 16px;
            border: 1px solid color-mix(in srgb, var(--portal-primary) 12%, #ffffff);
            border-radius: 22px;
            color: inherit;
            text-decoration: none;
            background: var(--portal-surface);
            box-shadow: 0 12px 28px color-mix(in srgb, var(--portal-primary) 8%, transparent);
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        .portal-link:hover,
        .portal-link:focus-visible {
            border-color: var(--portal-action);
            box-shadow: 0 18px 42px color-mix(in srgb, var(--portal-action) 16%, transparent);
            outline: none;
            transform: translateY(-2px);
        }

        .portal-icon {
            display: inline-flex;
            width: 52px;
            height: 52px;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            color: #ffffff;
            background: var(--portal-action);
        }

        .portal-title {
            display: block;
            color: var(--portal-text);
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .portal-description {
            display: block;
            margin-top: 4px;
            color: var(--portal-muted);
            font-size: 0.92rem;
            line-height: 1.45;
        }

        .portal-arrow {
            display: inline-flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            color: #ffffff;
            background: var(--portal-action);
            font-weight: 800;
        }

        .admin {
            --portal-action: var(--portal-primary);
        }

        .teacher {
            --portal-action: var(--portal-secondary);
        }

        .student {
            --portal-action: var(--portal-accent);
        }

        @media (max-width: 640px) {
            main {
                align-items: flex-start;
                padding: 18px;
            }

            .welcome-panel {
                border-radius: 28px;
            }

            .brand-area {
                padding: 28px 20px 24px;
            }

            .school-logo-frame {
                width: 96px;
                height: 96px;
                border-radius: 24px;
            }

            .school-logo {
                width: 76px;
                height: 76px;
            }

            .portal-list {
                padding: 16px;
            }

            .portal-link {
                grid-template-columns: 48px 1fr;
                min-height: 84px;
                padding: 14px;
            }

            .portal-icon {
                width: 48px;
                height: 48px;
                border-radius: 16px;
            }

            .portal-arrow {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main>
        <section class="welcome-panel" aria-labelledby="page-title">
            <div class="brand-area">
                <div class="school-logo-frame">
                    <img class="school-logo" src="{{ $schoolLogoUrl }}" alt="{{ $schoolName }} logo">
                </div>

                <p class="portal-label">School Management Portal</p>
                <h1 id="page-title">{{ $schoolName }}</h1>
                <p class="welcome-copy">Choose your portal to continue to your dashboard, records, assignments and activities, grades, and school tools.</p>
            </div>

            <nav class="portal-list" aria-label="Portal navigation">
                <a class="portal-link admin" href="{{ url('/admin') }}">
                    <span class="portal-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3L4 6.5V12C4 17 7.4 20.7 12 21.8C16.6 20.7 20 17 20 12V6.5L12 3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M9 12L11 14L15.5 9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>
                        <span class="portal-title">Admin Portal</span>
                        <span class="portal-description">
                            Manage school setup, users, records, reports{{ $quizModuleEnabled ? ', quizzes' : '' }}, and announcements.
                        </span>
                    </span>
                    <span class="portal-arrow" aria-hidden="true">-&gt;</span>
                </a>

                <a class="portal-link teacher" href="{{ route('teacher.login') }}">
                    <span class="portal-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M4 5.5C4 4.7 4.7 4 5.5 4H20V18H5.5C4.7 18 4 17.3 4 16.5V5.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M4 16.5C4 15.7 4.7 15 5.5 15H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M8 8H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>
                        <span class="portal-title">Teacher Portal</span>
                        <span class="portal-description">Open your class dashboard, student lists, attendance, grades, and schedules.</span>
                    </span>
                    <span class="portal-arrow" aria-hidden="true">-&gt;</span>
                </a>

                <a class="portal-link student" href="{{ route('student.login') }}">
                    <span class="portal-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 12C14.2 12 16 10.2 16 8C16 5.8 14.2 4 12 4C9.8 4 8 5.8 8 8C8 10.2 9.8 12 12 12Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M5 20C5.8 16.9 8.5 15 12 15C15.5 15 18.2 16.9 19 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>
                        <span class="portal-title">Student Portal</span>
                        <span class="portal-description">
                            View your profile, classes, grades, assignments and activities{{ $quizModuleEnabled ? ', quizzes' : '' }}, and announcements.
                        </span>
                    </span>
                    <span class="portal-arrow" aria-hidden="true">-&gt;</span>
                </a>
            </nav>
        </section>
    </main>
</body>
</html>
