<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local/staging dummy administrator
    |--------------------------------------------------------------------------
    |
    | This account is intentionally opt-in and is created only by the dedicated
    | DummyAdminSeeder. Never enable it in production.
    |
    */
    'enabled' => env('DUMMY_ADMIN_ENABLED', false),
    'name' => env('DUMMY_ADMIN_NAME', 'Dummy Admin (Local/Staging Only)'),
    'email' => env('DUMMY_ADMIN_EMAIL', 'dummy.admin@example.test'),
    'username' => env('DUMMY_ADMIN_USERNAME', 'dummy-admin'),
    'password' => env('DUMMY_ADMIN_PASSWORD'),
];
