<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeEnrollmentCourse\Pages;

use App\MoonShine\Resources\CollegeEnrollmentCourse\CollegeEnrollmentCourseResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;

/**
 * @extends IndexPage<CollegeEnrollmentCourseResource>
 */
class CollegeEnrollmentCourseIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    protected function topLeftButtons(): ListOf
    {
        return parent::topLeftButtons()->add(
            ActionButton::make('Export')
                ->icon('arrow-down-tray')
                ->setUrl(route('admin.college-grades.export', request()->query()))
        );
    }
}
