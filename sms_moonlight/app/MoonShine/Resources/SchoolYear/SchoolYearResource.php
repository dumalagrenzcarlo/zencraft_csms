<?php

namespace App\MoonShine\Resources\SchoolYear;

use App\Models\SchoolYear;
use App\MoonShine\Resources\SchoolYear\Pages\SchoolYearDetailPage;
use App\MoonShine\Resources\SchoolYear\Pages\SchoolYearFormPage;
use App\MoonShine\Resources\SchoolYear\Pages\SchoolYearIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * Auto-generated MoonShine resource
 */
class SchoolYearResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = SchoolYear::class;

    protected string $column = 'school_year';

    public function getTitle(): string
    {
        return 'School Years';
    }

    protected function pages(): array
    {
        return [
            SchoolYearIndexPage::class,
            SchoolYearFormPage::class,
            SchoolYearDetailPage::class,
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('School Year'), 'school_year'),
            Date::make(__('Start Date'), 'start_date')->format('M d, Y'),
            Date::make(__('End Date'), 'end_date')->format('M d, Y'),
            Checkbox::make(__('Active'), 'active'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('School Year'), 'school_year'),
            Date::make(__('Start Date'), 'start_date')->required(),
            Date::make(__('End Date'), 'end_date')->required(),
            Checkbox::make(__('Active'), 'active'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('School Year'), 'school_year'),
            Date::make(__('Start Date'), 'start_date')->format('M d, Y'),
            Date::make(__('End Date'), 'end_date')->format('M d, Y'),
            Checkbox::make(__('Active'), 'active'),
        ];
    }
}
