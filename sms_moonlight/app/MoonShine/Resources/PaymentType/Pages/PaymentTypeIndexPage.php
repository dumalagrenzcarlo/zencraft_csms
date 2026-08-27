<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\PaymentType\Pages;

use App\MoonShine\Resources\PaymentType\PaymentTypeResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;

/**
 * @extends IndexPage<PaymentTypeResource>
 */
class PaymentTypeIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return $this->getResource()->indexFields();
    }

    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }
}
