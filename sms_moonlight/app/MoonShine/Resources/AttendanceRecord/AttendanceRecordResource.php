<?php

namespace App\MoonShine\Resources\AttendanceRecord;

use App\Models\AttendanceRecord;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\AttendanceRecord\Pages\AttendanceRecordDetailPage;
use App\MoonShine\Resources\AttendanceRecord\Pages\AttendanceRecordFormPage;
use App\MoonShine\Resources\AttendanceRecord\Pages\AttendanceRecordIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * Auto-generated MoonShine resource
 */
class AttendanceRecordResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = AttendanceRecord::class;

    public function getTitle(): string
    {
        return 'Student Attendance';
    }

    protected function pages(): array
    {
        return [
            AttendanceRecordIndexPage::class,
            AttendanceRecordFormPage::class,
            AttendanceRecordDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'student_id',
            'student' => ['firstname', 'middlename', 'lastname', 'lrn'],
            'adviser_id',
            'adviser' => ['name'],
            'currentdate',
            'logged_time',
            'source',
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make(__('Student / Teacher Keyword'), 'person_keyword')
                ->hint(__('Search by student name, Student Number, or teacher name.'))
                ->onApply(function ($query, $value) {
                    $keyword = trim((string) $value);

                    if ($keyword === '') {
                        return $query;
                    }

                    $search = '%'.$keyword.'%';

                    return $query->where(function ($personQuery) use ($search): void {
                        $personQuery
                            ->whereHas('student', function ($studentQuery) use ($search): void {
                                $studentQuery->where(function ($nameQuery) use ($search): void {
                                    $nameQuery
                                        ->where('lrn', 'like', $search)
                                        ->orWhere('firstname', 'like', $search)
                                        ->orWhere('middlename', 'like', $search)
                                        ->orWhere('lastname', 'like', $search);
                                });
                            })
                            ->orWhereHas('adviser', function ($teacherQuery) use ($search): void {
                                $teacherQuery->where('name', 'like', $search);
                            });
                    });
                }),
            DateRange::make(__('From / To Date'), 'currentdate'),
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder
            ->whereNotNull('student_id')
            ->with(['student', 'adviser']);
    }

    public function indexFields(): array
    {
        return [
            Text::make(__('Type'), 'attendee_type'),
            Text::make(__('Attendee'), 'attendee_name'),
            // Text::make(__('Amlogin'), 'amlogin'),
            // Text::make(__('Amlogout'), 'amlogout'),
            // Text::make(__('Pmlogin'), 'pmlogin'),
            // Text::make(__('Pmlogout'), 'pmlogout'),
            Date::make(__('Date'), 'currentdate')->format('M d, Y'),
            Date::make(__('Logged Time'), 'logged_time')->format('h:i A'),
            Text::make(__('Source'), 'source'),
            Text::make(__('Tardiness'), 'tardiness_status'),
        ];
    }

    public function formFields(): array
    {
        return [
            // Text::make(__('Id'), 'id'),
            StudentBelongsTo::make(__('Student'))->nullable(),
            BelongsTo::make(__('Teacher'), 'adviser',
                fn ($item) => $item->name)
                ->nullable(),
            // Text::make(__('Amlogin'), 'amlogin'),
            // Text::make(__('Amlogout'), 'amlogout'),
            // Text::make(__('Pmlogin'), 'pmlogin'),
            // Text::make(__('Pmlogout'), 'pmlogout'),
            Date::make(__('Currentdate'), 'currentdate')->format('d.m.Y'),
            Date::make(__('Logged Time'), 'logged_time')->format('H:i'),
            Text::make(__('Source'), 'source'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Type'), 'attendee_type'),
            Text::make(__('Attendee'), 'attendee_name'),
            // Text::make(__('Amlogin'), 'amlogin'),
            // Text::make(__('Amlogout'), 'amlogout'),
            // Text::make(__('Pmlogin'), 'pmlogin'),
            // Text::make(__('Pmlogout'), 'pmlogout'),
            Date::make(__('Currentdate'), 'currentdate')->format('d.m.Y'),
            Date::make(__('Logged Time'), 'logged_time')->format('H:i'),
            Text::make(__('Source'), 'source'),
            Text::make(__('Tardiness'), 'tardiness_status'),
        ];
    }
}
