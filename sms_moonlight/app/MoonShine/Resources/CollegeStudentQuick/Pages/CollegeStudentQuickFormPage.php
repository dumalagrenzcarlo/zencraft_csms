<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeStudentQuick\Pages;

use App\MoonShine\Resources\CollegeStudentQuick\CollegeStudentQuickResource;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Pages\Crud\FormPage;

/**
 * @extends FormPage<CollegeStudentQuickResource>
 */
class CollegeStudentQuickFormPage extends FormPage
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(DataWrapperContract $item): array
    {
        $studentId = $this->getResource()->getItemID();

        return [
            'lrn' => [
                'required',
                'string',
                'max:15',
                Rule::unique('students', 'lrn')->ignore($studentId),
            ],
            'firstname' => ['required', 'string', 'max:30'],
            'lastname' => ['required', 'string', 'max:30'],
            'middlename' => ['required', 'string', 'max:30'],
            'gender' => ['required', Rule::in(['male', 'female'])],
        ];
    }
}
