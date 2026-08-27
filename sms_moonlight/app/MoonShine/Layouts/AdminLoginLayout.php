<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Themes\HananPalette;
use App\Support\SchoolBranding;
use MoonShine\AssetManager\InlineCss;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use MoonShine\Crud\Traits\WithComponentsPusher;
use MoonShine\Laravel\Layouts\BaseLayout;
use MoonShine\UI\Components\Components;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Body;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Favicon;
use MoonShine\UI\Components\Layout\Html;
use MoonShine\UI\Components\Layout\Layout;
use MoonShine\UI\Components\Layout\Logo;

final class AdminLoginLayout extends BaseLayout
{
    use WithComponentsPusher;

    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = HananPalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
            InlineCss::make($this->loginThemeStyles()),
        ];
    }

    public function build(): Layout
    {
        return Layout::make([
            Html::make([
                // Login-page fragments are served by an authenticated MoonShine
                // route. Render assets directly so guests do not trigger a 401
                // request and MoonShine's empty error modal on page load.
                $this->getHeadComponent(withAssetsFragment: false),
                Body::make([
                    Div::make([
                        Div::make([
                            Div::make([
                                $this->getLogoComponent(),
                            ])->class('authentication-logo'),

                            Div::make([
                                Div::make([
                                    FlexibleRender::make('Admin Portal'),
                                ])->class('portal-eyebrow'),
                                Heading::make($this->getSchoolName())->h(1, false),
                                Div::make([
                                    FlexibleRender::make('Sign in to manage school records, classes, grades, and users.'),
                                ])->class('description'),
                            ])->class('authentication-header'),

                            Components::make($this->getPage()->getComponents()),

                            FlexibleRender::make(view('admin.back-to-portal-selection')),
                        ])->class('authentication-content'),

                        ...$this->getPushedComponents(),
                    ])->class('authentication admin-authentication'),
                ])->class('admin-login-body'),
            ])
                ->customAttributes([
                    'lang' => $this->getHeadLang(),
                ])
                ->withAlpineJs()
                ->when(
                    $this->hasThemes() || $this->isAlwaysDark(),
                    fn (Html $html): Html => $html->withThemes($this->isAlwaysDark())
                ),
        ]);
    }

    /**
     * @param  ColorManager  $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        $theme = array_merge([
            'primary' => '#8F160F',
            'secondary' => '#B32317',
            'background' => '#FFF7F1',
            'text' => '#2F1A17',
            'accent' => '#D7A83D',
            'alert' => '#D92D20',
            'surface' => '#FFFFFF',
        ], config('school_portal.theme', []));

        $colorManager->bulkAssign([
            'primary' => $theme['primary'],
            'primary-text' => '#FFFFFF',
            'secondary' => $theme['secondary'],
            'secondary-text' => '#FFFFFF',
            'body' => $theme['background'],
            'base.text' => $theme['text'],
            'base.stroke' => $theme['primary'],
            'base.default' => $theme['surface'],
            'error' => $theme['alert'],
            'error-text' => '#FFFFFF',
        ]);
    }

    protected function getLogoComponent(): Logo
    {
        $logoUrl = $this->getSchoolLogoUrl();

        return Logo::make(
            $this->getHomeUrl(),
            $logoUrl,
            null
        );
    }

    protected function getFaviconComponent(): Favicon
    {
        return Favicon::make([
            'apple-touch' => '/favicons/apple-touch-icon.png',
            '32' => '/favicons/favicon-32x32.png',
            '16' => '/favicons/favicon-16x16.png',
        ]);
    }

    private function getSchoolName(): string
    {
        return SchoolBranding::name();
    }

    private function getSchoolLogoUrl(): string
    {
        return SchoolBranding::logoUrl();
    }

    private function loginThemeStyles(): string
    {
        $theme = array_merge([
            'primary' => '#8F160F',
            'secondary' => '#B32317',
            'background' => '#FFF7F1',
            'text' => '#2F1A17',
            'accent' => '#D7A83D',
            'alert' => '#D92D20',
            'surface' => '#FFFFFF',
        ], config('school_portal.theme', []));

        return <<<CSS
            :root {
                --portal-primary: {$theme['primary']};
                --portal-secondary: {$theme['secondary']};
                --portal-background: {$theme['background']};
                --portal-text: {$theme['text']};
                --portal-accent: {$theme['accent']};
                --portal-alert: {$theme['alert']};
                --portal-surface: {$theme['surface']};
            }

            body.admin-login-body {
                min-height: 100vh;
                background: var(--portal-background) !important;
                color: var(--portal-text) !important;
            }

            .admin-authentication.authentication {
                display: flex;
                min-height: 100vh;
                align-items: center;
                justify-content: center;
                padding: 1.75rem 1.25rem;
                background: var(--portal-background);
            }

            .admin-authentication .authentication-content {
                width: 100%;
                max-width: 28rem;
                padding: 1.75rem 2rem 2rem;
                border: 1px solid color-mix(in srgb, var(--portal-primary) 28%, #ffffff);
                border-radius: 2rem;
                background: var(--portal-surface);
                box-shadow: 0 24px 70px color-mix(in srgb, var(--portal-primary) 16%, transparent);
            }

            .admin-authentication .authentication-content::before {
                display: none !important;
            }

            .admin-authentication .authentication-logo {
                width: 5.25rem;
                height: 5.25rem;
                margin: 0 auto;
            }

            .admin-authentication .authentication-logo .logo,
            .admin-authentication .authentication-logo a,
            .admin-authentication .authentication-logo img {
                display: block;
                width: 5.25rem !important;
                height: 5.25rem !important;
            }

            .admin-authentication .authentication-logo img {
                border: 1px solid color-mix(in srgb, var(--portal-primary) 12%, #ffffff);
                border-radius: 1.5rem;
                background: #ffffff;
                object-fit: contain;
                padding: 0.625rem;
                box-shadow: 0 1px 3px rgb(15 23 42 / 0.1);
            }

            .admin-authentication .authentication-header {
                margin-top: 1rem;
                margin-bottom: 1.35rem;
                padding-top: 0 !important;
                text-align: center;
            }

            .admin-authentication .authentication-header > * {
                margin-block: 0 !important;
            }

            .admin-authentication .portal-eyebrow {
                margin-top: 0 !important;
                color: var(--portal-primary);
                font-size: 0.75rem;
                font-weight: 800;
                letter-spacing: 0.22em;
                line-height: 1rem;
                text-transform: uppercase;
            }

            .admin-authentication .authentication-header h1 {
                margin-top: 0.45rem !important;
                color: var(--portal-text);
                font-size: 1.5rem;
                font-weight: 800;
                letter-spacing: 0;
                line-height: 2rem;
            }

            .admin-authentication .description {
                margin-top: 0.45rem !important;
                color: #64748b;
                font-size: 0.875rem;
                line-height: 1.5rem;
            }

            .admin-authentication .authentication-form {
                display: flex;
                flex-direction: column;
                gap: 0.9rem;
            }

            .admin-authentication .authentication-form.space-elements {
                row-gap: 0.9rem !important;
            }

            .admin-authentication .form-group,
            .admin-authentication .form-field {
                margin: 0 !important;
            }

            .admin-authentication label {
                margin-bottom: 0.375rem;
                color: var(--portal-text) !important;
                font-size: 0.875rem;
                font-weight: 700;
            }

            .admin-authentication input:not([type='checkbox']),
            .admin-authentication .form-input,
            .admin-authentication .form-control {
                min-height: 3rem;
                width: 100%;
                border: 1px solid color-mix(in srgb, var(--portal-primary) 22%, #ffffff) !important;
                border-radius: 1rem !important;
                background: #ffffff !important;
                color: var(--portal-text) !important;
                font-size: 0.875rem;
                outline: none;
                transition: border-color 160ms ease, box-shadow 160ms ease;
            }

            .admin-authentication input:not([type='checkbox']):focus,
            .admin-authentication .form-input:focus,
            .admin-authentication .form-control:focus {
                border-color: color-mix(in srgb, var(--portal-primary) 42%, #ffffff) !important;
                box-shadow: 0 0 0 4px color-mix(in srgb, var(--portal-primary) 16%, transparent) !important;
            }

            .admin-authentication .form-switcher,
            .admin-authentication .switcher {
                color: var(--portal-text);
                font-size: 0.875rem;
                font-weight: 600;
            }

            .admin-authentication .authentication-form .grid {
                margin-top: 0.35rem !important;
            }

            .admin-authentication .authentication-form .mt-3 {
                margin-top: 0 !important;
            }

            .admin-authentication .btn-primary {
                min-height: 3rem;
                width: 100%;
                border-radius: 1rem !important;
                background: var(--portal-primary) !important;
                color: #ffffff !important;
                font-weight: 800;
                box-shadow: none;
                transition: background-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
            }

            .admin-authentication .btn-primary:hover {
                background: var(--portal-secondary) !important;
                transform: translateY(-1px);
            }

            .admin-authentication .btn-primary:focus {
                box-shadow: 0 0 0 4px color-mix(in srgb, var(--portal-primary) 24%, transparent) !important;
            }

            .admin-authentication .alert,
            .admin-authentication .form-invalid-feedback,
            .admin-authentication .invalid-feedback {
                border-color: color-mix(in srgb, var(--portal-alert) 18%, #ffffff) !important;
                border-radius: 1rem;
                background: color-mix(in srgb, var(--portal-alert) 8%, #ffffff) !important;
                color: var(--portal-alert) !important;
                font-size: 0.875rem;
                font-weight: 600;
            }

            .admin-authentication .back-to-portal-selection {
                display: flex;
                min-height: 2.75rem;
                width: 100%;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                margin-top: 1rem;
                border: 1px solid #e2e8f0;
                border-radius: 1rem;
                background: #ffffff;
                color: #475569;
                font-size: 0.875rem;
                font-weight: 700;
                text-decoration: none;
                transition: border-color 160ms ease, background-color 160ms ease, color 160ms ease;
            }

            .admin-authentication .back-to-portal-selection:hover,
            .admin-authentication .back-to-portal-selection:focus-visible {
                border-color: #cbd5e1;
                background: #f8fafc;
                color: var(--portal-primary);
                outline: none;
            }

            @media (max-width: 640px) {
                .admin-authentication.authentication {
                    padding: 1rem;
                }

                .admin-authentication .authentication-content {
                    padding: 1.5rem;
                }
            }
            CSS;
    }
}
