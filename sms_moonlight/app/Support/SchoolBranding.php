<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class SchoolBranding
{
    public static function name(): string
    {
        $name = self::settingValue('school_name');

        return $name !== ''
            ? $name
            : config('school.school_name', config('app.name', 'School Portal'));
    }

    public static function logoUrl(): string
    {
        $path = self::logoPath();

        if ($path === '') {
            return asset('branding/hanan/hsph-logo.png');
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        return self::uploadUrl($path);
    }

    public static function logoPath(): string
    {
        return ltrim(self::settingValue('school_logo'), '/');
    }

    private static function settingValue(string $name): string
    {
        return trim((string) config("school.{$name}", ''));
    }

    private static function uploadUrl(string $path): string
    {
        $path = collect(explode('/', ltrim($path, '/')))
            ->filter(static fn (string $segment): bool => $segment !== '')
            ->map(static fn (string $segment): string => rawurlencode($segment))
            ->implode('/');

        return Storage::disk('public')->url($path);
    }
}
