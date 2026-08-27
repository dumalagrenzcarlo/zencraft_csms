<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Instructor extends Adviser
{
    protected $attributes = [
        'staff_type' => self::TYPE_INSTRUCTOR,
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope(
            'college_instructors',
            fn (Builder $query) => $query->where(function (Builder $personnel) use ($query): void {
                $personnel
                    ->where(
                        $query->qualifyColumn('staff_type'),
                        self::TYPE_INSTRUCTOR
                    )
                    ->orWhere(function (Builder $teacher) use ($query): void {
                        $teacher
                            ->where(
                                $query->qualifyColumn('staff_type'),
                                self::TYPE_TEACHER
                            )
                            ->where(
                                $query->qualifyColumn('is_college_instructor'),
                                true
                            );
                    });
            })
        );

        static::creating(function (Instructor $instructor): void {
            $instructor->staff_type = self::TYPE_INSTRUCTOR;
        });
    }
}
