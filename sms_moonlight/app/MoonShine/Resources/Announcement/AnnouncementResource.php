<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Announcement;

use App\Models\Announcement;
use App\MoonShine\Resources\Announcement\Pages\AnnouncementDetailPage;
use App\MoonShine\Resources\Announcement\Pages\AnnouncementFormPage;
use App\MoonShine\Resources\Announcement\Pages\AnnouncementIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\TinyMce\Fields\TinyMce;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<Announcement>
 */
class AnnouncementResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = Announcement::class;

    public function getTitle(): string
    {
        return 'Announcements';
    }

    protected function pages(): array
    {
        return [
            AnnouncementIndexPage::class,
            AnnouncementFormPage::class,
            AnnouncementDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'title',
            'content',
            'target_audience',
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make('Title', 'title'),
            $this->audienceField(),
            Date::make('Expiry Date', 'expiry_date'),
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Title'), 'title'),
            Textarea::make(__('Content'), 'content')->unescape(),
            $this->audienceField(),
            Date::make(__('Expiry Date'), 'expiry_date'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Title'), 'title'),
            TinyMce::make(__('Content'), 'content'),
            $this->audienceField(),
            Date::make(__('Expiry Date'), 'expiry_date'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Title'), 'title'),
            Textarea::make(__('Content'), 'content')->unescape(),
            $this->audienceField(),
            Date::make(__('Expiry Date'), 'expiry_date'),
        ];
    }

    private function audienceField(): Select
    {
        return Select::make(__('Target Audience'), 'target_audience')
            ->options([
                'both' => 'Both',
                'students' => 'Students',
                'teachers' => 'Teachers',
            ]);
    }
}
