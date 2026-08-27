<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * Class ClassStudent
 *
 * @property int $id
 * @property int $class_id
 * @property int $student_id
 * @property int $school_year_id
 * @property bool $hidden_grade
 * @property string|null $notes
 * @property Carbon|null $grades_submitted_at
 * @property int|null $grades_submitted_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClassStudent extends Model
{
    protected $table = 'class_students';

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
        'school_year_id',
        'hidden_grade',
        'notes',
        'grades_submitted_at',
        'grades_submitted_by',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'class_id' => 'integer',
            'student_id' => 'integer',
            'school_year_id' => 'integer',
            'hidden_grade' => 'boolean',
            'notes' => 'string',
            'grades_submitted_at' => 'datetime',
            'grades_submitted_by' => 'integer',
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

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function school_year(): BelongsTo
    {
        return $this->schoolYear();
    }

    public function grades(): HasMany
    {
        return $this->hasMany(ClassStudentGrade::class, 'student_id', 'student_id')
            ->where('class_id', $this->class_id);
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id', 'student_id');
    }

    public function gradesAreSubmitted(): bool
    {
        return filled($this->grades_submitted_at);
    }

    public function termCount(): int
    {
        $this->loadMissing('class.grade');

        return $this->class?->gradingPeriodCount() ?? 4;
    }

    /**
     * @return list<string>
     */
    public function termKeys(): array
    {
        $this->loadMissing('class.grade');

        return $this->class?->gradingPeriodKeys() ?? ['q1', 'q2', 'q3', 'q4'];
    }

    protected static function booted(): void
    {
        static::saving(function (ClassStudent $classStudent): void {
            $class = ClassesModel::query()->find($classStudent->class_id);

            if ($class) {
                $classStudent->school_year_id = $class->school_year_id;
            }

            if (! $classStudent->student_id || ! $classStudent->school_year_id) {
                return;
            }

            $alreadyAssigned = static::query()
                ->where('student_id', $classStudent->student_id)
                ->where('school_year_id', $classStudent->school_year_id)
                ->when($classStudent->exists, fn ($query) => $query->whereKeyNot($classStudent->getKey()))
                ->exists();

            if ($alreadyAssigned) {
                throw ValidationException::withMessages([
                    'student_id' => 'This student already has a high-school class for the selected school year.',
                ]);
            }
        });
    }
}
