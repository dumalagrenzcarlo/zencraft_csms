<?php

namespace App\MoonShine\Resources\Setting;

use App\Models\Setting;
use App\MoonShine\Resources\Setting\Pages\SettingDetailPage;
use App\MoonShine\Resources\Setting\Pages\SettingFormPage;
use App\MoonShine\Resources\Setting\Pages\SettingIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

#[Icon('cog')]
#[Group('moonshine::ui.resource.system', 'users', translatable: true)]
#[Order(2)]
class SettingResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = Setting::class;

    public function getTitle(): string
    {
        return 'Settings';
    }

    protected function pages(): array
    {
        return [
            SettingIndexPage::class,
            SettingFormPage::class,
            SettingDetailPage::class,
        ];
    }

    public function indexFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Setting Name'), 'settingName'),
            Text::make(__('Setting Type'), 'settingType'),
            Text::make(__('Setting Value'), 'settingValue'),
            Text::make(__('Setting File Value'), 'settingFileValue'),
            Text::make(__('Setting JSON Value'), 'settingJSONValue'),
        ];
    }

    public function detailsFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Setting Name'), 'settingName'),
            Text::make(__('Setting Value'), 'settingValue'),
            Text::make(__('Setting Type'), 'settingType'),
            Text::make(__('Setting File Value'), 'settingFileValue'),
            Text::make(__('Setting JSON Value'), 'settingJSONValue'),
        ];
    }

    public function formFields(): array
    {
        /** @var Setting|null $setting */
        $setting = $this->getItem();
        $type = $setting?->settingType ?? 'text';

        $fields = [
            ID::make(__('Id'), 'id'),
        ];

        if ($setting === null) {
            $fields[] = Text::make(__('Setting Name'), 'settingName');
        } else {
            $fields[] = Preview::make(__('Setting Name'), 'settingName')
                ->changeFill(fn (mixed $setting): string => $setting instanceof Setting
                    ? (string) $setting->settingName
                    : ''
                );
        }

        if ($setting === null) {
            $fields[] = Select::make(__('Setting Type'), 'settingType')
                ->options($this->settingTypeOptions());
        } else {
            $fields[] = Preview::make(__('Setting Type'), 'settingType')
                ->changeFill(fn (mixed $setting): string => $setting instanceof Setting
                    ? ($this->settingTypeOptions()[$setting->settingType] ?? ucfirst($setting->settingType))
                    : ''
                );
        }

        $fields[] = Preview::make('Saved Value', 'settingValue')
            ->changeFill(fn (mixed $setting): string => $setting instanceof Setting
                ? $this->displayValue($setting)
                : ''
            );

        match ($type) {
            'file' => $fields[] = File::make('Upload File', 'settingFileValue')
                ->dir('settings')
                ->disk('public')
                ->allowedExtensions(['jpg', 'jpeg', 'png', 'pdf', 'webp', 'svg', 'gif']),
            'boolean' => $fields[] = Checkbox::make(__('Enabled'), 'settingValue'),
            'json' => $fields[] = Textarea::make(__('JSON Value'), 'settingValue'),
            default => $fields[] = Textarea::make(__('Setting Value'), 'settingValue'),
        };

        return $fields;
    }

    /**
     * @return array<string, string>
     */
    private function settingTypeOptions(): array
    {
        return [
            'text' => 'Text',
            'file' => 'File',
            'json' => 'Json',
            'boolean' => 'Boolean',
        ];
    }

    private function displayValue(Setting $setting): string
    {
        return match ($setting->settingType) {
            'boolean' => filter_var($setting->settingValue, FILTER_VALIDATE_BOOLEAN) ? 'Enabled' : 'Disabled',
            'file' => filled($setting->settingValue) ? (string) $setting->settingValue : 'No file selected',
            'json' => filled($setting->settingValue) ? (string) $setting->settingValue : 'No JSON value set',
            default => (string) ($setting->settingValue ?? ''),
        };
    }
}
