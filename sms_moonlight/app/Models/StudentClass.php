<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StudentClass
 *
 * @property int $id
 * @property int $student_id
 * @property int $grade_id
 * @property string $school_year
 * @property string $section
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StudentClass extends Model
{
    protected $table = 'student_class';

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
        'student_id',
        'grade_id',
        'school_year',
        'section',
        'status',
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
            'grade_id' => 'integer',
            'school_year' => 'string',
            'section' => 'string',
            'status' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }
}
