<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeProgram\Pages;

use App\MoonShine\Resources\CollegeProgram\CollegeProgramResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;

/**
 * @extends IndexPage<CollegeProgramResource>
 */
class CollegeProgramIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    protected function topLeftButtons(): ListOf
    {
        return parent::topLeftButtons()->add(
            ActionButton::make('Export')
                ->icon('arrow-down-tray')
                ->setUrl(route('admin.college-courses.export', request()->query()))
        );
    }
}
