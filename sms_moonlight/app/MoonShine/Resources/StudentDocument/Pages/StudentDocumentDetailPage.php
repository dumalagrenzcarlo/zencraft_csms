<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\StudentDocument\Pages;

use App\MoonShine\Resources\StudentDocument\StudentDocumentResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;

/**
 * @extends DetailPage<StudentDocumentResource>
 */
class StudentDocumentDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return $this->getResource()->detailsFields();
    }
}
