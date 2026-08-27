<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ClassStudent;
use App\Models\CollegeEnrollment;
use App\Models\Student;

final class StudentAcademicContextResolver
{
    public function resolve(Student $student): StudentAcademicContext
    {
        $highSchoolClasses = ClassStudent::query()
            ->with([
                'class.grade',
                'class.adviser',
                'class.classSubjects.subject',
                'schoolYear',
            ])
            ->where('student_id', $student->id)
            ->whereHas('schoolYear', fn ($query) => $query->where('active', true))
            ->whereHas('class', fn ($query) => $query->where('active', true))
            ->orderByDesc('id')
            ->get();

        $collegeEnrollments = config('school_portal.features.college_module')
            ? CollegeEnrollment::query()
                ->with([
                    'program',
                    'schoolYear',
                    'courses.programCourse',
                    'courses.offering.programCourse',
                    'courses.offering.instructor',
                ])
                ->where('student_id', $student->id)
                ->where('status', 'enrolled')
                ->whereHas('schoolYear', fn ($query) => $query->where('active', true))
                ->orderByDesc('id')
                ->get()
            : collect();

        $highSchoolClass = $highSchoolClasses->first();
        $collegeEnrollment = $collegeEnrollments->first();

        if ($highSchoolClasses->count() > 1) {
            return new StudentAcademicContext(
                StudentAcademicContext::CONFLICT,
                $highSchoolClass,
                $collegeEnrollment,
                'More than one active high-school class is assigned for the current school year.',
            );
        }

        if ($collegeEnrollments->count() > 1) {
            return new StudentAcademicContext(
                StudentAcademicContext::CONFLICT,
                $highSchoolClass,
                $collegeEnrollment,
                'More than one college enrollment is active for the current academic term.',
            );
        }

        if ($highSchoolClass && $collegeEnrollment) {
            return new StudentAcademicContext(
                StudentAcademicContext::CONFLICT,
                $highSchoolClass,
                $collegeEnrollment,
                'Both a high-school class and a college enrollment are active.',
            );
        }

        if ($collegeEnrollment) {
            return new StudentAcademicContext(
                StudentAcademicContext::COLLEGE,
                collegeEnrollment: $collegeEnrollment,
            );
        }

        if ($highSchoolClass) {
            return new StudentAcademicContext(
                StudentAcademicContext::HIGH_SCHOOL,
                highSchoolClass: $highSchoolClass,
            );
        }

        return new StudentAcademicContext(StudentAcademicContext::NONE);
    }
}
