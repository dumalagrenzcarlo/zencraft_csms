<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * Class Class
 *
 * @property int $id
 * @property int $adviser_id
 * @property int $grade_id
 * @property string $section
 * @property int $school_year_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property int|null $grading_period_count
 * @property string $status
 * @property int $active
 * @property bool $enable_assignments
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClassesModel extends Model
{
    protected $table = 'classes';

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
        'grade_id',
        'section',
        'school_year_id',
        'start_time',
        'end_time',
        'grading_period_count',
        'status',
        'active',
        'enable_assignments',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
        'active' => 1,
        'enable_assignments' => false,
        'grading_period_count' => 4,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'adviser_id' => 'integer',
            'grade_id' => 'integer',
            'section' => 'string',
            'school_year_id' => 'integer',
            'start_time' => 'string',
            'end_time' => 'string',
            'grading_period_count' => 'integer',
            'status' => 'string',
            'active' => 'integer',
            'enable_assignments' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Adviser::class, 'adviser_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function classStudents(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'class_id');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'class_subjects',
            'class_id',
            'subject_id'
        )
            ->withTimestamps()
            ->orderByRaw('subjects.record_order IS NULL')
            ->orderBy('subjects.record_order')
            ->orderBy('subjects.subject');
    }

    public function gradingPeriodCount(): int
    {
        $configuredCount = $this->grading_period_count
            ?? $this->grade?->termCount()
            ?? 4;

        return max(1, min(4, (int) $configuredCount));
    }

    /**
     * @return list<string>
     */
    public function gradingPeriodKeys(): array
    {
        return array_slice(['q1', 'q2', 'q3', 'q4'], 0, $this->gradingPeriodCount());
    }

    public function classStudentGrades(): HasMany
    {
        return $this->hasMany(ClassStudentGrade::class, 'class_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'class_id');
    }

    protected static function booted(): void
    {
        static::saving(function (ClassesModel $class): void {
            $isTeacher = Adviser::query()
                ->teachers()
                ->whereKey($class->adviser_id)
                ->exists();

            if (! $isTeacher) {
                throw ValidationException::withMessages([
                    'adviser_id' => 'High-school classes must be assigned to a teacher.',
                ]);
            }
        });
    }
}
