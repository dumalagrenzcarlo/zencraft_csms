<?php

namespace App\Services\ImageHelper;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FaviconService
{
    public static function generate(string $sourcePath): void
    {
        $manager = new ImageManager(new Driver());

        $image = $manager->read($sourcePath);

        // Create favicon directory
        if (!file_exists(public_path('favicons'))) {
            mkdir(public_path('favicons'), 0755, true);
        }

        $image->cover(16, 16)
            ->save(public_path('favicons/favicon-16x16.png'));

        $manager->read($sourcePath)
            ->cover(32, 32)
            ->save(public_path('favicons/favicon-32x32.png'));

        $manager->read($sourcePath)
            ->cover(180, 180)
            ->save(public_path('favicons/apple-touch-icon.png'));
    }
}