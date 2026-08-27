<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CollegeStudentQuick;

use App\Models\Student;
use App\MoonShine\Resources\CollegeStudentQuick\Pages\CollegeStudentQuickFormPage;
use MoonShine\Crud\Contracts\Page\DetailPageContract;
use MoonShine\Crud\Contracts\Page\IndexPageContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

class CollegeStudentQuickResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    protected string $model = Student::class;

    protected string $column = 'lrn';

    public function getTitle(): string
    {
        return 'Quick Add College Student';
    }

    protected function pages(): array
    {
        return [
            IndexPageContract::class,
            CollegeStudentQuickFormPage::class,
            DetailPageContract::class,
        ];
    }

    protected function search(): array
    {
        return ['lrn', 'firstname', 'lastname', 'middlename'];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            Text::make('Student Number', 'lrn'),
            Text::make('First Name', 'firstname'),
            Text::make('Last Name', 'lastname'),
            Text::make('Middle Name', 'middlename'),
            Select::make('Gender', 'gender')->options($this->genderOptions()),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            Text::make('Student Number', 'lrn')
                ->hint('This will also be the student portal username.')
                ->required(),
            Text::make('First Name', 'firstname')->required(),
            Text::make('Last Name', 'lastname')->required(),
            Text::make('Middle Name', 'middlename')->required(),
            Select::make('Gender', 'gender')
                ->options($this->genderOptions())
                ->required(),
        ];
    }

    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }

    /**
     * @return array<string, string>
     */
    private function genderOptions(): array
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
        ];
    }
}
