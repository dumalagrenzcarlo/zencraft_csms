<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

final class TeacherStaffAttendance
{
    public static function enabled(): bool
    {
        return filter_var(
            config('school_portal.features.teacher_staff_attendance', true),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public static function rfidEnabled(): bool
    {
        return self::enabled() && Setting::enabled('rfid_enabled', true);
    }
}
