<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ClassSubject
 *
 * @property int $id
 * @property int $class_id
 * @property int $subject_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClassSubject extends Model
{
    protected $table = 'class_subjects';

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
        'class_id',
        'subject_id',
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
            'class_id' => 'integer',
            'subject_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassesModel::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
