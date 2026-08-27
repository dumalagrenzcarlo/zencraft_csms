<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\PaymentType\Pages;

use App\MoonShine\Resources\PaymentType\PaymentTypeResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Components\Layout\Box;

/**
 * @extends DetailPage<PaymentTypeResource>
 */
class PaymentTypeDetailPage extends DetailPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make($this->getResource()->detailsFields()),
        ];
    }
}
