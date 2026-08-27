<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\StudentPaymentHistory\Pages;

use App\MoonShine\Resources\StudentPaymentHistory\StudentPaymentHistoryResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\ListOf;

/**
 * @extends DetailPage<StudentPaymentHistoryResource>
 */
class StudentPaymentHistoryDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return $this->getResource()->detailsFields();
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function modifyDetailComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }
}
