<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ClassAdviserSchedule
 *
 * @property int $id
 * @property int $adviser_id
 * @property int|null $class_id
 * @property string $day
 * @property string $section
 * @property string $time_frame
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClassAdviserSchedule extends Model
{
    protected $table = 'class_adviser_schedules';

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
        'adviser_id',
        'class_id',
        'day',
        'section',
        'time_frame',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'adviser_id' => 'integer',
            'class_id' => 'integer',
            'day' => 'string',
            'section' => 'string',
            'time_frame' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Adviser::class, 'adviser_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassesModel::class, 'class_id');
    }
}
