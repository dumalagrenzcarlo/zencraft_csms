<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeEnrollment\Pages;

use App\MoonShine\Resources\CollegeEnrollment\CollegeEnrollmentResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Modal;

/**
 * @extends IndexPage<CollegeEnrollmentResource>
 */
class CollegeEnrollmentIndexPage extends IndexPage
{
    protected function topLeftButtons(): ListOf
    {
        return parent::topLeftButtons()->add(
            ActionButton::make('Import')
                ->icon('arrow-up-tray')
                ->primary()
                ->inModal(
                    'Import College Enrolments',
                    view('admin.college-enrollments.import-modal')->render(),
                    builder: static fn (Modal $modal): Modal => $modal
                        ->closeOutside(false)
                        ->autoClose(false),
                )
        );
    }
}
