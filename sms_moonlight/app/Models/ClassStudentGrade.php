<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ClassStudentGrade
 *
 * @property int $id
 * @property int $class_id
 * @property int $student_id
 * @property int $grade_id
 * @property int $subject_id
 * @property int $q1
 * @property int $q2
 * @property int $q3
 * @property int $q4
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClassStudentGrade extends Model
{
    protected $table = 'class_student_grades';

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
        'student_id',
        'grade_id',
        'subject_id',
        'q1',
        'q2',
        'q3',
        'q4',
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
            'student_id' => 'integer',
            'grade_id' => 'integer',
            'subject_id' => 'integer',
            'q1' => 'integer',
            'q2' => 'integer',
            'q3' => 'integer',
            'q4' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassesModel::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
