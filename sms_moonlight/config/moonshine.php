<?php

use App\Http\Middleware\EnforcePortalDomain;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\MoonshineAuthenticate;
use App\Http\Middleware\TenantRouteContext;
use App\MoonShine\AuthPipelines\RedirectIntendedAfterLogin;
use App\MoonShine\Layouts\CustomLayout;
use App\MoonShine\Pages\AdminLoginPage;
use App\MoonShine\Pages\Dashboard;
use App\MoonShine\Themes\HananPalette;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MoonShine\Crud\Forms\FiltersForm;
use MoonShine\Crud\Forms\LoginForm;
use MoonShine\Laravel\Exceptions\MoonShineNotFoundException;
use MoonShine\Laravel\Http\Middleware\Authenticate;
use MoonShine\Laravel\Http\Middleware\ChangeLocale;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Pages\ErrorPage;
use MoonShine\Laravel\Pages\ProfilePage;

return [
    'title' => env('APP_TITLE', 'ZenCraft Systems'),
    'logo' => '/branding/hanan/hsph-logo.png',
    'logo_small' => '/branding/hanan/hsph-logo.png',

    // Default flags
    'use_migrations' => false,
    'use_notifications' => true,
    'use_database_notifications' => true,
    'use_routes' => true,
    'use_profile' => true,

    // Routing
    'domain' => env('MOONSHINE_DOMAIN'),
    'prefix' => env('MOONSHINE_ROUTE_PREFIX', 'admin'),
    'page_prefix' => env('MOONSHINE_PAGE_PREFIX', 'page'),
    'resource_prefix' => env('MOONSHINE_RESOURCE_PREFIX', 'resource'),
    'home_route' => 'moonshine.index',

    // Error handling
    'not_found_exception' => MoonShineNotFoundException::class,

    // Middleware
    'middleware' => [
        TenantRouteContext::class,
        EnsureTenantActive::class,
        EnforcePortalDomain::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
        ChangeLocale::class,
    ],

    // Storage
    'disk' => 'public',
    'disk_options' => [],
    'cache' => 'file',

    'favicons' => [
        'apple-touch' => '/favicons/apple-touch-icon.png',
        '32' => '/favicons/favicon-32x32.png',
        '16' => '/favicons/favicon-16x16.png',
    ],
    // Authentication and profile
    'auth' => [
        'enabled' => true,
        'guard' => 'moonshine',
        'model' => MoonshineUser::class,
        'middleware' => [
            Authenticate::class,
            MoonshineAuthenticate::class,
        ],
        'pipelines' => [
            RedirectIntendedAfterLogin::class,
        ],
    ],

    // Authentication and profile
    'user_fields' => [
        'username' => 'username',
        'password' => 'password',
        'name' => 'name',
        'avatar' => 'avatar',
    ],

    // Layout, palette, pages, forms
    'layout' => CustomLayout::class,
    'palette' => HananPalette::class,

    'forms' => [
        'login' => LoginForm::class,
        'filters' => FiltersForm::class,
    ],

    'pages' => [
        'dashboard' => Dashboard::class,
        'profile' => ProfilePage::class,
        'login' => AdminLoginPage::class,
        'error' => ErrorPage::class,
    ],

    // Localizations
    'locale' => 'en',
    'locale_key' => ChangeLocale::KEY,
    'locales' => [
        // en
    ],
    'login_redirect' => null,
];
