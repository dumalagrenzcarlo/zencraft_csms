<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Hash;
use LogicException;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;

final class RoleDefaultPassword
{
    public const TEACHER_ROLE_ID = 2;

    public const STUDENT_ROLE_ID = 3;

    public function reset(MoonshineUser $user): void
    {
        $role = $this->role($user);

        $user->forceFill([
            'password' => Hash::make($this->passwordForRole($role)),
            'must_change_password' => $role !== 'admin',
        ])->save();
    }

    public function passwordFor(MoonshineUser $user): string
    {
        return $this->passwordForRole($this->role($user));
    }

    private function role(MoonshineUser $user): string
    {
        return match ((int) $user->moonshine_user_role_id) {
            MoonshineUserRole::DEFAULT_ROLE_ID => 'admin',
            self::TEACHER_ROLE_ID => 'teacher',
            self::STUDENT_ROLE_ID => 'student',
            default => throw new LogicException('No default password is configured for this user role.'),
        };
    }

    private function passwordForRole(string $role): string
    {
        [$configKey, $fallback] = match ($role) {
            'admin' => ['school.default_config_admin_password', 'admin123'],
            'teacher' => ['school.default_config_teacher_password', 'teacher123'],
            'student' => ['school.default_config_student_password', 'student123'],
        };

        $password = (string) config($configKey);

        return filled($password) ? $password : $fallback;
    }
}
