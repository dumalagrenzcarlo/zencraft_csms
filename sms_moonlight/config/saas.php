<?php

declare(strict_types=1);

return [
    'platform_domain' => env('PLATFORM_DOMAIN', 'localhost'),
    'tenant_base_domain' => env('TENANT_BASE_DOMAIN', 'localhost'),
    'trial_days' => (int) env('SAAS_TRIAL_DAYS', 30),
    'billing_grace_days' => max(0, (int) env('SAAS_BILLING_GRACE_DAYS', 7)),
    'support_access_minutes' => max(5, (int) env('SAAS_SUPPORT_ACCESS_MINUTES', 60)),
    'backup_disk' => env('SAAS_BACKUP_DISK', 'local'),
    'free_tenant_database_connection' => env('SAAS_FREE_TENANT_DB_CONNECTION', 'tenant_sqlite'),
    'reserved_tenant_slugs' => [
        'admin', 'api', 'build', 'favicons', 'health', 'images', 'platform',
        'signup', 'storage', 'student', 'teacher', 'up', 'uploaded-files', 'uploads',
    ],
    'public_signup' => [
        'enabled' => env('SAAS_PUBLIC_SIGNUP_ENABLED', true),
        'verification_expiration_hours' => max(1, (int) env('SAAS_SIGNUP_VERIFICATION_HOURS', 24)),
    ],
];
