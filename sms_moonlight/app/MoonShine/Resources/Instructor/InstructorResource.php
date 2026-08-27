<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Instructor;

use App\Models\Adviser;
use App\Models\Instructor;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\MoonShine\Resources\Adviser\Pages\AdviserIndexPage;
use App\MoonShine\Resources\Instructor\Pages\DetailPage;
use App\MoonShine\Resources\Instructor\Pages\FormPage;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;

class InstructorResource extends AdviserResource
{
    public string $model = Instructor::class;

    public function uriKey(): string
    {
        return 'instructors';
    }

    public function getTitle(): string
    {
        return 'Instructors / Professors';
    }

    public function personnelType(): string
    {
        return Adviser::TYPE_INSTRUCTOR;
    }

    public function personnelLabel(): string
    {
        return 'Instructor';
    }

    protected function pages(): array
    {
        return [
            AdviserIndexPage::class,
            FormPage::class,
            DetailPage::class,
        ];
    }

    protected function afterCreated(DataWrapperContract $item): DataWrapperContract
    {
        /** @var Instructor $instructor */
        $instructor = $item->getOriginal()->refresh();
        $instructor->load('user');

        session()->flash('admin_created_adviser_credentials', [
            'title' => 'Instructor Credentials',
            'name' => (string) $instructor->name,
            'username' => (string) ($instructor->user?->username ?? ''),
            'password' => (string) config('school.default_config_teacher_password', 'teacher123'),
        ]);

        return $item;
    }
}
