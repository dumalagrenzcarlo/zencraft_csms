<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CollegeCourseOffering;
use App\Models\CollegeEnrollment;
use App\Models\CollegeEnrollmentCourse;
use App\Models\CollegeProgramCourse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CollegeEnrollmentCourseAssigner
{
    /**
     * Assign courses for an imported batch with a fixed number of database queries.
     *
     * @param  Collection<int, CollegeEnrollment>  $enrollments
     */
    public function assignAvailableCoursesToMany(Collection $enrollments): int
    {
        $enrollments = $enrollments
            ->filter(static fn (CollegeEnrollment $enrollment): bool => $enrollment->status === 'enrolled')
            ->values();

        if ($enrollments->isEmpty()) {
            return 0;
        }

        $enrollmentIds = $enrollments->pluck('id');
        $programIds = $enrollments->pluck('program_id')->unique();
        $schoolYearIds = $enrollments->pluck('school_year_id')->unique();
        $existingAssignments = CollegeEnrollmentCourse::query()
            ->whereIn('enrollment_id', $enrollmentIds)
            ->get()
            ->keyBy(static fn (CollegeEnrollmentCourse $assignment): string => implode('|', [
                $assignment->enrollment_id,
                $assignment->program_course_id,
            ]));
        $programCourses = CollegeProgramCourse::query()
            ->whereIn('program_id', $programIds)
            ->orderBy('course_order')
            ->orderBy('id')
            ->get();
        $coursesByContext = $programCourses->groupBy(static fn (CollegeProgramCourse $course): string => implode('|', [
            $course->program_id,
            $course->year_level,
            $course->semester,
        ]));
        $offeringsByCourse = CollegeCourseOffering::query()
            ->withCount('enrollmentCourses')
            ->whereIn('school_year_id', $schoolYearIds)
            ->whereIn('program_course_id', $programCourses->pluck('id'))
            ->where('active', true)
            ->orderBy('id')
            ->get()
            ->groupBy(static fn (CollegeCourseOffering $offering): string => implode('|', [
                $offering->school_year_id,
                $offering->program_course_id,
            ]));

        $now = now();
        $inserts = [];
        $offeringUpdates = [];

        foreach ($enrollments as $enrollment) {
            $context = implode('|', [$enrollment->program_id, $enrollment->year_level, $enrollment->semester]);

            foreach ($coursesByContext->get($context, collect()) as $programCourse) {
                $assignmentKey = implode('|', [$enrollment->id, $programCourse->id]);
                $existing = $existingAssignments->get($assignmentKey);
                $offering = $this->leastLoadedOffering(
                    $offeringsByCourse->get(
                        implode('|', [$enrollment->school_year_id, $programCourse->id]),
                        collect(),
                    ),
                );

                if ($existing) {
                    if (! $existing->offering_id && $offering) {
                        $offeringUpdates[$existing->id] = $offering->id;
                        $offering->setAttribute(
                            'enrollment_courses_count',
                            (int) $offering->enrollment_courses_count + 1,
                        );
                    }

                    continue;
                }

                $inserts[] = [
                    'enrollment_id' => $enrollment->id,
                    'program_course_id' => $programCourse->id,
                    'offering_id' => $offering?->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($offering) {
                    $offering->setAttribute(
                        'enrollment_courses_count',
                        (int) $offering->enrollment_courses_count + 1,
                    );
                }
            }
        }

        foreach ($offeringUpdates as $assignmentId => $offeringId) {
            CollegeEnrollmentCourse::query()->whereKey($assignmentId)->update(['offering_id' => $offeringId]);
        }

        foreach (array_chunk($inserts, 1000) as $chunk) {
            CollegeEnrollmentCourse::query()->insert($chunk);
        }

        return count($inserts);
    }

    public function assignAvailableCourses(CollegeEnrollment $enrollment): int
    {
        if ($enrollment->status !== 'enrolled') {
            return 0;
        }

        return DB::transaction(function () use ($enrollment): int {
            $existingAssignments = CollegeEnrollmentCourse::query()
                ->where('enrollment_id', $enrollment->id)
                ->get()
                ->keyBy('program_course_id');

            $programCourses = CollegeProgramCourse::query()
                ->where('program_id', $enrollment->program_id)
                ->where('year_level', $enrollment->year_level)
                ->where('semester', $enrollment->semester)
                ->orderBy('course_order')
                ->orderBy('id')
                ->get()
                ->values();

            $assigned = 0;

            foreach ($programCourses as $programCourse) {
                $existing = $existingAssignments->get($programCourse->id);
                $offering = $this->availableOffering($enrollment, $programCourse);

                if ($existing) {
                    if (! $existing->offering_id && $offering) {
                        $existing->update(['offering_id' => $offering->id]);
                    }

                    continue;
                }

                CollegeEnrollmentCourse::create([
                    'enrollment_id' => $enrollment->id,
                    'program_course_id' => $programCourse->id,
                    'offering_id' => $offering?->id,
                ]);
                $assigned++;
            }

            return $assigned;
        });
    }

    private function availableOffering(
        CollegeEnrollment $enrollment,
        CollegeProgramCourse $programCourse
    ): ?CollegeCourseOffering {
        return CollegeCourseOffering::query()
            ->withCount('enrollmentCourses')
            ->where('school_year_id', $enrollment->school_year_id)
            ->where('program_course_id', $programCourse->id)
            ->where('active', true)
            ->orderBy('enrollment_courses_count')
            ->orderBy('id')
            ->get()
            ->first(
                static fn (CollegeCourseOffering $offering): bool => (int) $offering->enrollment_courses_count < (int) $offering->capacity
            );
    }

    /**
     * @param  Collection<int, CollegeCourseOffering>  $offerings
     */
    private function leastLoadedOffering(Collection $offerings): ?CollegeCourseOffering
    {
        return $offerings
            ->filter(
                static fn (CollegeCourseOffering $offering): bool => (int) $offering->enrollment_courses_count < (int) $offering->capacity
            )
            ->sortBy([
                ['enrollment_courses_count', 'asc'],
                ['id', 'asc'],
            ])
            ->first();
    }
}
