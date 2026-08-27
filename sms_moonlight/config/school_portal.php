<?php

declare(strict_types=1);

$baseDomain = env('SCHOOL_BASE_DOMAIN');

return [
    'base_domain' => $baseDomain,
    'timezone' => env('SCHOOL_TIMEZONE', 'Asia/Manila'),

    'dashboard' => [
        'cache_seconds' => (int) env('DASHBOARD_CACHE_SECONDS', 30),
    ],

    'domains' => [
        'admin' => env('ADMIN_PORTAL_DOMAIN', $baseDomain ? 'admin.'.$baseDomain : null),
        'teacher' => env('TEACHER_PORTAL_DOMAIN', $baseDomain ? 'teacher.'.$baseDomain : null),
        'student' => env('STUDENT_PORTAL_DOMAIN', $baseDomain ? 'student.'.$baseDomain : null),
    ],

    'theme' => [
        'primary' => env('SCHOOL_THEME_PRIMARY', '#F7BC3B'),
        'secondary' => env('SCHOOL_THEME_SECONDARY', '#D97706'),
        'background' => env('SCHOOL_THEME_BACKGROUND', '#F8F1E5'),
        'text' => env('SCHOOL_THEME_TEXT', '#454548'),
        'accent' => env('SCHOOL_THEME_ACCENT', '#38BDF8'),
        'alert' => env('SCHOOL_THEME_ALERT', '#E94560'),
        'surface' => env('SCHOOL_THEME_SURFACE', '#FFFFFF'),
        'dark_background' => env('SCHOOL_THEME_DARK_BACKGROUND', '#1F2937'),
        'dark_text' => env('SCHOOL_THEME_DARK_TEXT', '#E5E7EB'),
    ],

    'features' => [
        'college_module' => env('COLLEGE_MODULE_ENABLED', true),
        'quiz_module' => env('QUIZ_MODULE_ENABLED', false),
        'staff_module' => env('STAFF_MODULE_ENABLED', true),
        'teacher_staff_attendance' => env('TEACHER_STAFF_ATTENDANCE_ENABLED', true),
        'easter_eggs' => env('EASTER_EGGS_ENABLED', true),
        'payments_module' => env(
            'PAYMENTS_MODULE_ENABLED',
            env('PAYMENT_MODULE_ENABLED', false)
        ),
    ],

    'payments' => [
        'authorized_admin_username' => env('PAYMENTS_ADMIN_USERNAME'),
        'unlock_minutes' => max(1, (int) env('PAYMENTS_UNLOCK_MINUTES', 15)),
    ],
];
