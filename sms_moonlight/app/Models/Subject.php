<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Subject
 *
 * @property int $id
 * @property string $subject
 * @property string $include_in_average
 * @property int|null $record_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Subject extends Model
{
    protected $table = 'subjects';

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
        'subject',
        'include_in_average',
        'record_order',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'record_order' => 'NULL',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'subject' => 'string',
            'include_in_average' => 'boolean',
            'record_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'subject_id');
    }

    public function classStudentGrades(): HasMany
    {
        return $this->hasMany(ClassStudentGrade::class, 'subject_id');
    }

}
