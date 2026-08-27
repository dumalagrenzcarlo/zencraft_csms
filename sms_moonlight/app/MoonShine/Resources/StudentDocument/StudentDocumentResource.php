<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\StudentDocument;

use App\Models\StudentDocument;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\StudentDocument\Pages\StudentDocumentDetailPage;
use App\MoonShine\Resources\StudentDocument\Pages\StudentDocumentFormPage;
use App\MoonShine\Resources\StudentDocument\Pages\StudentDocumentIndexPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Textarea;

class StudentDocumentResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = StudentDocument::class;

    protected string $column = 'file';

    public function getTitle(): string
    {
        return 'Documents';
    }

    protected function pages(): array
    {
        return [
            StudentDocumentIndexPage::class,
            StudentDocumentFormPage::class,
            StudentDocumentDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'file',
            'notes',
            'student' => ['lrn', 'firstname', 'lastname', 'middlename'],
        ];
    }

    protected function filters(): iterable
    {
        return [
            StudentBelongsTo::make(__('Student')),
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            StudentBelongsTo::make(__('Student')),
            $this->fileField(),
            Textarea::make(__('Notes'), 'notes'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            StudentBelongsTo::make(__('Student'))->required(),
            $this->fileField()->required(),
            Textarea::make(__('Notes'), 'notes')->nullable(),
        ];
    }

    public function detailsFields(): array
    {
        return $this->indexFields();
    }

    private function fileField(): File
    {
        return File::make(__('File'), 'file')
            ->dir('student-documents')
            ->disk('public')
            ->customName(static fn (UploadedFile $file): string => Str::uuid().'/'.self::originalFileName($file))
            ->names(static fn (string $path): string => basename(str_replace('\\', '/', $path)))
            ->allowedExtensions([
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv',
                'jpg', 'jpeg', 'png', 'webp', 'txt',
            ]);
    }

    private static function originalFileName(UploadedFile $file): string
    {
        return basename(str_replace('\\', '/', $file->getClientOriginalName()));
    }
}
