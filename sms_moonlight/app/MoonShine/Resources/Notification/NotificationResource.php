<?php

namespace App\MoonShine\Resources\Notification;

use \App\Models\Notification;
use App\MoonShine\Resources\Notification\Pages\NotificationIndexPage;
use App\MoonShine\Resources\Notification\Pages\NotificationFormPage;
use App\MoonShine\Resources\Notification\Pages\NotificationDetailPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Laravel\Fields\Relationships\{BelongsTo,HasMany};
use MoonShine\UI\Fields\{
    ID,
    Checkbox,
    Text,
    Textarea,
    Date,
    Time,
    Number,
    Toggle,
    Json,
    Select
};

/**
 * Auto-generated MoonShine resource
 */
class NotificationResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = Notification::class;

    public function getTitle(): string
    {
        return 'Notifications';
    }

    protected function pages(): array
    {
        return [
            NotificationIndexPage::class,
            NotificationFormPage::class,
            NotificationDetailPage::class
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Type'), 'type'),
            Text::make(__('Notifiable Type'), 'notifiable_type'),
            Number::make(__('Notifiable Id'), 'notifiable_id'),
            Textarea::make(__('Data'), 'data'),
            Date::make(__('Read At'), 'read_at'),
        ];
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Type'), 'type'),
            Text::make(__('Notifiable Type'), 'notifiable_type'),
            Number::make(__('Notifiable Id'), 'notifiable_id'),
            Textarea::make(__('Data'), 'data'),
            Date::make(__('Read At'), 'read_at'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Type'), 'type'),
            Text::make(__('Notifiable Type'), 'notifiable_type'),
            Number::make(__('Notifiable Id'), 'notifiable_id'),
            Textarea::make(__('Data'), 'data'),
            Date::make(__('Read At'), 'read_at'),
        ];
    }
}
