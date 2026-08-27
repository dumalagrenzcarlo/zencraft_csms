<?php

declare(strict_types=1);

namespace App\MoonShine\Themes;

use MoonShine\Contracts\ColorManager\PaletteContract;

final class HananPalette implements PaletteContract
{
    public function getDescription(): string
    {
        return 'School configurable palette';
    }

    public function getColors(): array
    {
        $theme = config('school_portal.theme');

        return [
            'body' => $theme['surface'],
            'primary' => $theme['primary'],
            'primary-text' => '#FFFFFF',
            'secondary' => $theme['secondary'],
            'secondary-text' => '#FFFFFF',
            'base' => [
                'text' => $theme['text'],
                'stroke' => $theme['secondary'],
                'default' => $theme['surface'],
                50 => $theme['surface'],
                100 => '#FFF7F1',
                200 => '#FDEDE8',
                300 => '#F6D2CA',
                400 => '#E9A89D',
                500 => '#D27A6E',
                600 => '#B32317',
                700 => '#8F160F',
                800 => '#6E120D',
                900 => '#2F1A17',
            ],
            'success' => '#16A34A',
            'success-text' => '#14532D',
            'warning' => $theme['primary'],
            'warning-text' => '#78350F',
            'error' => $theme['alert'],
            'error-text' => '#7F1D1D',
            'info' => $theme['accent'],
            'info-text' => '#0C4A6E',
        ];
    }

    public function getDarkColors(): array
    {
        $theme = config('school_portal.theme');

        return [
            'body' => $theme['dark_background'],
            'primary' => $theme['primary'],
            'primary-text' => '#FFFFFF',
            'secondary' => $theme['secondary'],
            'secondary-text' => '#FFFFFF',
            'base' => [
                'text' => $theme['dark_text'],
                'stroke' => '#4B5563',
                'default' => '#111827',
                50 => '#1F2937',
                100 => '#253041',
                200 => '#2D3B4E',
                300 => '#37465A',
                400 => '#44566F',
                500 => '#546A87',
                600 => '#6A7FA0',
                700 => '#8095B8',
                800 => '#9CB0CF',
                900 => '#C4D1E4',
            ],
            'success' => '#22C55E',
            'success-text' => '#DCFCE7',
            'warning' => $theme['primary'],
            'warning-text' => '#FEF3C7',
            'error' => $theme['alert'],
            'error-text' => '#FEE2E2',
            'info' => $theme['accent'],
            'info-text' => '#E0F2FE',
        ];
    }
}
