<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class SchoolYear
 *
 * @property int $id
 * @property string $school_year
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SchoolYear extends Model
{
    protected $table = 'school_year';

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
        'school_year',
        'start_date',
        'end_date',
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
            'school_year' => 'string',
            'start_date' => 'date',
            'end_date' => 'date',
            'active' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (SchoolYear $schoolYear): void {
            if (! $schoolYear->active) {
                return;
            }

            static::query()
                ->whereKeyNot($schoolYear->getKey())
                ->where('active', true)
                ->update(['active' => false]);
        });
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassesModel::class, 'school_year_id');
    }

    public function collegeEnrollments(): HasMany
    {
        return $this->hasMany(CollegeEnrollment::class, 'school_year_id');
    }

    public function collegeCourseOfferings(): HasMany
    {
        return $this->hasMany(CollegeCourseOffering::class, 'school_year_id');
    }

    public function classStudents(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'school_year_id');
    }

    public function quizGroups(): HasMany
    {
        return $this->hasMany(QuizGroup::class, 'school_year_id');
    }
}
