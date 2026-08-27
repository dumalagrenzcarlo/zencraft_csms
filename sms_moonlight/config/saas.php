<?php

declare(strict_types=1);

return [
    'platform_domain' => env('PLATFORM_DOMAIN', 'localhost'),
    'tenant_base_domain' => env('TENANT_BASE_DOMAIN', 'localhost'),
    'trial_days' => (int) env('SAAS_TRIAL_DAYS', 30),
];
