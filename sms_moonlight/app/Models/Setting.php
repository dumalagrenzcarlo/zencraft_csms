<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Class Setting
 *
 * @property int $id
 * @property string $settingName
 * @property string|null $settingValue
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Setting extends Model
{
    protected $table = 'settings';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'settingName',
        'settingValue',
        'settingFileValue',
        'settingJSONValue',
        'settingType',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'settingType' => 'text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'settingName' => 'string',
            'settingValue' => 'string',
            'settingFileValue' => 'string',
            'settingJSONValue' => 'string',
            'settingType' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getSettingFileValueAttribute(?string $value): ?string
    {
        return $this->settingType === 'file'
            ? ($value ?: $this->settingValue)
            : $value;
    }

    public function getSettingJsonValueAttribute(?string $value): ?string
    {
        return $this->settingType === 'json'
            ? ($value ?: $this->settingValue)
            : $value;
    }

    protected static function booted(): void
    {
        static::saving(function (Setting $item) {

            switch ($item->settingType) {

                case 'file':
                    if (filled($item->settingFileValue)) {
                        $item->settingValue = $item->settingFileValue;
                    }
                    break;

                case 'boolean':
                    $item->settingValue = filter_var($item->settingValue, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                    break;
            }
        });

        static::saved(function ($setting) {
            Cache::forget('school.settings');

            if ($setting->settingName !== 'school_logo') {
                return;
            }

            \App\Services\ImageHelper\FaviconService::generate(
                Storage::disk('public')->path($setting->settingValue)
            );
        });

        static::deleted(fn () => Cache::forget('school.settings'));
    }

    public static function enabled(string $name, bool $default = false): bool
    {
        $value = config('school.'.$name);

        if ($value === null) {
            $value = static::query()
                ->where('settingName', $name)
                ->value('settingValue');
        }

        return $value === null
            ? $default
            : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
