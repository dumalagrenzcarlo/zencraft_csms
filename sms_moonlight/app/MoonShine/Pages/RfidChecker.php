<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Adviser;
use App\Models\Setting;
use App\Models\Student;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\MoonShine\Resources\Student\StudentResource;
use App\MoonShine\Resources\Staff\StaffResource;
use App\Support\RfidCardUid;
use App\Support\TeacherStaffAttendance;
use Illuminate\Support\Facades\Schema;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\FlexibleRender;

class RfidChecker extends Page
{
    public function getTitle(): string
    {
        return 'RFID Checker';
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        $uid = RfidCardUid::normalize(request()->query('rfid_card_uid'));
        $searched = $uid !== null;
        $assignment = null;
        $error = null;

        if (! Setting::enabled('rfid_enabled', true)) {
            $error = 'RFID features are disabled in Settings.';
        } elseif (
            ! Schema::hasColumn('students', 'rfid_card_uid')
            || ! Schema::hasColumn('advisers', 'rfid_card_uid')
        ) {
            $error = 'RFID support is not installed yet. Run php artisan migrate and try again.';
        } elseif ($searched) {
            $student = Student::query()
                ->where('rfid_card_uid', $uid)
                ->first();
            $personnelTypes = filter_var(
                config('school_portal.features.staff_module', true),
                FILTER_VALIDATE_BOOLEAN,
            )
                ? [Adviser::TYPE_TEACHER, Adviser::TYPE_STAFF]
                : [Adviser::TYPE_TEACHER];
            $teacher = TeacherStaffAttendance::enabled()
                ? Adviser::query()
                    ->whereIn('staff_type', $personnelTypes)
                    ->where('rfid_card_uid', $uid)
                    ->with('user')
                    ->first()
                : null;

            if ($student && $teacher) {
                $error = 'This RFID card is assigned to more than one person.';
            } elseif ($student) {
                $assignment = [
                    'type' => 'Student',
                    'record_id' => $student->id,
                    'identifier_label' => 'Student Number',
                    'identifier' => $student->lrn,
                    'name' => trim($student->firstname.' '.$student->lastname),
                    'record_url' => app(StudentResource::class)->getDetailPageUrl($student->id),
                ];
            } elseif ($teacher) {
                $isStaff = $teacher->staff_type === Adviser::TYPE_STAFF;
                $assignment = [
                    'type' => $isStaff ? 'Staff' : 'Teacher',
                    'record_id' => $teacher->id,
                    'identifier_label' => $isStaff ? 'Staff ID' : 'Username',
                    'identifier' => $isStaff
                        ? 'S-'.$teacher->id
                        : ($teacher->user?->username ?: 'T-'.$teacher->id),
                    'name' => $teacher->name,
                    'record_url' => $isStaff
                        ? app(StaffResource::class)->getDetailPageUrl($teacher->id)
                        : app(AdviserResource::class)->getDetailPageUrl($teacher->id),
                ];
            }
        }

        return [
            FlexibleRender::make(view('admin.rfid-checker', [
                'uid' => $uid,
                'searched' => $searched,
                'assignment' => $assignment,
                'error' => $error,
            ])),
        ];
    }
}
