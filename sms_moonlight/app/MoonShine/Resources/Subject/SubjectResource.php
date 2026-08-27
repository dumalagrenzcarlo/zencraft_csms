<?php

namespace App\MoonShine\Resources\Subject;

use App\Models\Subject;
use App\MoonShine\Resources\Subject\Pages\SubjectDetailPage;
use App\MoonShine\Resources\Subject\Pages\SubjectFormPage;
use App\MoonShine\Resources\Subject\Pages\SubjectIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * Auto-generated MoonShine resource
 */
class SubjectResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = Subject::class;

    protected string $column = 'subject';

    public function getTitle(): string
    {
        return 'Subjects';
    }

    protected function pages(): array
    {
        return [
            SubjectIndexPage::class,
            SubjectFormPage::class,
            SubjectDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'subject',
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Subject'), 'subject'),
            Checkbox::make(__('Include In Average'), 'include_in_average'),
            Number::make(__('Record Order'), 'record_order'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Subject'), 'subject'),
            Checkbox::make(__('Include In Average'), 'include_in_average'),
            Number::make(__('Record Order'), 'record_order'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Subject'), 'subject'),
            Checkbox::make(__('Include In Average'), 'include_in_average'),
            Number::make(__('Record Order'), 'record_order'),
        ];
    }
}
