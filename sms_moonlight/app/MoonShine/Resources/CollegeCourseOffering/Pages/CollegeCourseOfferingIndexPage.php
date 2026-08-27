<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeCourseOffering\Pages;

use App\Models\CollegeCourseOffering;
use App\Models\CollegeProgramCourse;
use App\Models\SchoolYear;
use App\MoonShine\Resources\CollegeCourseOffering\CollegeCourseOfferingResource;
use App\MoonShine\Resources\CollegeProgramCourse\CollegeProgramCourseResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FlexibleRender;

/**
 * @extends IndexPage<CollegeCourseOfferingResource>
 */
class CollegeCourseOfferingIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    protected function topLeftButtons(): ListOf
    {
        return parent::topLeftButtons()->add(
            ActionButton::make('Export')
                ->icon('arrow-down-tray')
                ->setUrl(route('admin.college-class-schedules.export', request()->query()))
        );
    }

    /**
     * @return list<ComponentContract>
     */
    protected function mainLayer(): array
    {
        $activeSchoolYear = SchoolYear::query()
            ->where('active', true)
            ->orderByDesc('id')
            ->first();

        $unscheduledCount = 0;

        if ($activeSchoolYear) {
            $blankScheduleCount = CollegeCourseOffering::query()
                ->where('school_year_id', $activeSchoolYear->getKey())
                ->where('active', true)
                ->unscheduled()
                ->count();

            $classesWithoutOfferingCount = CollegeProgramCourse::query()
                ->whereHas('program', fn ($program) => $program->where('active', true))
                ->whereDoesntHave('offerings', function ($offerings) use ($activeSchoolYear): void {
                    $offerings
                        ->where('school_year_id', $activeSchoolYear->getKey())
                        ->where('active', true);
                })
                ->count();

            $unscheduledCount = $blankScheduleCount + $classesWithoutOfferingCount;
        }

        return [
            FlexibleRender::make(view('admin.college-class-schedules.unscheduled-notice', [
                'unscheduledCount' => $unscheduledCount,
                'activeSchoolYear' => $activeSchoolYear?->school_year,
                'classesUrl' => app(CollegeProgramCourseResource::class)->getIndexPageUrl(),
            ])),
            ...parent::mainLayer(),
        ];
    }
}
