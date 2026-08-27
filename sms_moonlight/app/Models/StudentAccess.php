<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MoonShine\Laravel\Models\MoonshineUser;

/**
 * Class StudentAccess
 *
 * @property int $id
 * @property int $student_id
 * @property int $user_id
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StudentAccess extends Model
{
    protected $table = 'student_access';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'student_id',
        'user_id',
        'active',
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
            'student_id' => 'integer',
            'user_id' => 'integer',
            'active' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(MoonshineUser::class, 'user_id');
    }

    public function scopeActiveForPortal(Builder $query): Builder
    {
        return $query
            ->where('active', 1)
            ->whereHas('student', static fn (Builder $studentQuery): Builder => $studentQuery->active());
    }
}
