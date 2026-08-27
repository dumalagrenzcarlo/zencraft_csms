<?php

namespace App\MoonShine\Resources\PaymentType;

use App\Models\PaymentType;
use App\MoonShine\Resources\PaymentType\Pages\PaymentTypeDetailPage;
use App\MoonShine\Resources\PaymentType\Pages\PaymentTypeFormPage;
use App\MoonShine\Resources\PaymentType\Pages\PaymentTypeIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

class PaymentTypeResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = PaymentType::class;

    protected string $column = 'name';

    public function getTitle(): string
    {
        return 'Payment Types';
    }

    protected function pages(): array
    {
        return [
            PaymentTypeIndexPage::class,
            PaymentTypeFormPage::class,
            PaymentTypeDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'name',
            'notes',
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make(__('Name'), 'name'),
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Name'), 'name'),
            Textarea::make(__('Notes'), 'notes'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Name'), 'name')->required(),
            Textarea::make(__('Notes'), 'notes'),
        ];
    }

    public function detailsFields(): array
    {
        return $this->indexFields();
    }
}
