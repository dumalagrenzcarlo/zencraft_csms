<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Assignment
 *
 * @property int $id
 * @property int $class_id
 * @property int $adviser_id
 * @property string $title
 * @property string|null $notes
 * @property string $file_path
 * @property string $file_name
 * @property Carbon $deadline
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Assignment extends Model
{
    protected $table = 'assignments';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'class_id',
        'adviser_id',
        'title',
        'notes',
        'file_path',
        'file_name',
        'deadline',
        'sent_at',
    ];

    protected $attributes = [
        'notes' => null,
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'class_id' => 'integer',
            'adviser_id' => 'integer',
            'title' => 'string',
            'notes' => 'string',
            'file_path' => 'string',
            'file_name' => 'string',
            'deadline' => 'datetime',
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassesModel::class, 'class_id');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Adviser::class, 'adviser_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }
}
