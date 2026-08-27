<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ClassStudent;
use App\Models\CollegeEnrollment;

final class StudentAcademicContext
{
    public const HIGH_SCHOOL = 'high_school';

    public const COLLEGE = 'college';

    public const NONE = 'none';

    public const CONFLICT = 'conflict';

    public function __construct(
        public readonly string $type,
        public readonly ?ClassStudent $highSchoolClass = null,
        public readonly ?CollegeEnrollment $collegeEnrollment = null,
        public readonly ?string $conflictReason = null,
    ) {}

    public function isHighSchool(): bool
    {
        return $this->type === self::HIGH_SCHOOL;
    }

    public function isCollege(): bool
    {
        return $this->type === self::COLLEGE;
    }

    public function hasConflict(): bool
    {
        return $this->type === self::CONFLICT;
    }

    public function label(): string
    {
        return match ($this->type) {
            self::HIGH_SCHOOL => 'High School',
            self::COLLEGE => 'College',
            self::CONFLICT => 'Enrollment Conflict',
            default => 'Not Enrolled',
        };
    }
}
