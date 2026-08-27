<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\StudentDocument\Pages;

use App\MoonShine\Resources\StudentDocument\StudentDocumentResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;

/**
 * @extends IndexPage<StudentDocumentResource>
 */
class StudentDocumentIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return $this->getResource()->indexFields();
    }
}
