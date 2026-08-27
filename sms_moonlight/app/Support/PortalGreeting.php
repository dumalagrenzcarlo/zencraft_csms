<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

final class PortalGreeting
{
    public static function message(?CarbonInterface $time = null): string
    {
        $hour = ($time ?? now(config('school_portal.timezone', 'Asia/Manila')))->hour;

        return match (true) {
            $hour >= 5 && $hour < 12 => 'Good Morning',
            $hour >= 12 && $hour < 18 => 'Good Afternoon',
            default => 'Good Evening',
        };
    }
}
