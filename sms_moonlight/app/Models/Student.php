<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\RfidCardUid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use MoonShine\Laravel\Models\MoonshineUser;

/**
 * Class Student
 *
 * @property int $id
 * @property int $user_id
 * @property string $lrn
 * @property string $lastname
 * @property string $firstname
 * @property string $middlename
 * @property string $gender
 * @property string $dob
 * @property string $address
 * @property string $birthplace
 * @property string $profile_photo
 * @property string $parent_guardian
 * @property string $parent_guardian_address
 * @property string $parent_guardian_relationship
 * @property int $is_4ps_member
 * @property string|null $weight
 * @property string|null $height
 * @property string|null $elementary_school_name
 * @property string|null $elementary_school_id
 * @property string|null $elementary_school_address
 * @property string|null $elementary_school_grade
 * @property string|null $elementary_school_citation
 * @property string|null $tshirt_size
 * @property int|null $deworming_grade_7
 * @property int|null $deworming_grade_8
 * @property int|null $deworming_grade_9
 * @property int|null $deworming_grade_10
 * @property string|null $archived
 * @property string|null $archive_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Student extends Model
{
    protected $table = 'students';

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
        'user_id',
        'lrn',
        'rfid_card_uid',
        'lastname',
        'firstname',
        'middlename',
        'gender',
        'dob',
        'address',
        'birthplace',
        'profile_photo',
        'parent_guardian',
        'parent_guardian_address',
        'parent_guardian_relationship',
        'is_4ps_member',
        'weight',
        'height',
        'elementary_school_name',
        'elementary_school_id',
        'elementary_school_address',
        'elementary_school_grade',
        'elementary_school_citation',
        'tshirt_size',
        'deworming_grade_7',
        'deworming_grade_8',
        'deworming_grade_9',
        'deworming_grade_10',
        'archived',
        'archive_date',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'weight' => null,
        'height' => null,
        'elementary_school_name' => null,
        'elementary_school_id' => null,
        'elementary_school_address' => null,
        'elementary_school_grade' => null,
        'elementary_school_citation' => null,
        'tshirt_size' => null,
        'deworming_grade_7' => null,
        'deworming_grade_8' => null,
        'deworming_grade_9' => null,
        'deworming_grade_10' => null,
        'archived' => null,
        'archive_date' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'lrn' => 'string',
            'rfid_card_uid' => 'string',
            'lastname' => 'string',
            'firstname' => 'string',
            'middlename' => 'string',
            'gender' => 'string',
            'dob' => 'date',
            'address' => 'string',
            'birthplace' => 'string',
            'profile_photo' => 'string',
            'parent_guardian' => 'string',
            'parent_guardian_address' => 'string',
            'parent_guardian_relationship' => 'string',
            'is_4ps_member' => 'boolean',
            'weight' => 'string',
            'height' => 'string',
            'elementary_school_name' => 'string',
            'elementary_school_id' => 'string',
            'elementary_school_address' => 'string',
            'elementary_school_grade' => 'string',
            'elementary_school_citation' => 'string',
            'tshirt_size' => 'string',
            'deworming_grade_7' => 'boolean',
            'deworming_grade_8' => 'boolean',
            'deworming_grade_9' => 'boolean',
            'deworming_grade_10' => 'boolean',
            'archived' => 'boolean',
            'archive_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(MoonshineUser::class, 'user_id');
    }

    public function setRfidCardUidAttribute(mixed $value): void
    {
        $this->attributes['rfid_card_uid'] = RfidCardUid::normalize($value);
    }

    public function classStudents(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'student_id');
    }

    public function classStudentGrades(): HasMany
    {
        return $this->hasMany(ClassStudentGrade::class, 'student_id');
    }

    public function studentClasses(): HasMany
    {
        return $this->hasMany(StudentClass::class, 'student_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function studentQuizAnswers(): HasMany
    {
        return $this->hasMany(StudentQuizAnswer::class, 'student_id');
    }

    public function paymentHistories(): HasMany
    {
        return $this->hasMany(StudentPaymentHistory::class, 'student_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'student_id');
    }

    public function collegeEnrollments(): HasMany
    {
        return $this->hasMany(CollegeEnrollment::class, 'student_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(static function (Builder $query): void {
            $query->whereNull('archived')->orWhere('archived', false);
        });
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('archived', true);
    }

    protected static function booted(): void
    {
        static::saving(function (Student $student): void {
            RfidCardUid::ensureUnique(
                RfidCardUid::normalize($student->rfid_card_uid),
                $student->getTable(),
                $student->getKey(),
            );
        });

        static::saved(function (Student $student): void {
            $student->syncMoonshineUser();
        });
    }

    public function syncMoonshineUser(): void
    {
        $lrn = trim((string) $this->lrn);

        if ($lrn === '') {
            return;
        }

        $fullname = trim(
            trim((string) ($this->firstname ?? '')).' '.
            trim((string) ($this->lastname ?? ''))
        );

        $user = MoonshineUser::query()
            ->where('username', $lrn)
            ->first();

        if (! $user && $this->user_id) {
            $user = MoonshineUser::query()->find($this->user_id);
        }

        if (! $user) {
            $user = new MoonshineUser;
            $userData = [
                'password' => Hash::make(config('school.default_config_student_password', 'student123')),
            ];

            if (Schema::hasColumn('moonshine_users', 'must_change_password')) {
                $userData['must_change_password'] = true;
            }

            $user->forceFill($userData);
        }

        $user->forceFill([
            'moonshine_user_role_id' => 3,
            'username' => $lrn,
            'email' => $lrn.'@'.config('app.domain', 'localhost'),
            'name' => $fullname !== '' ? $fullname : $lrn,
        ]);
        $user->save();

        if ((int) $this->user_id !== (int) $user->id) {
            $this->updateQuietly([
                'user_id' => $user->id,
            ]);
        }

        StudentAccess::query()->updateOrCreate(
            ['student_id' => $this->id],
            [
                'user_id' => $user->id,
                'active' => $this->archived ? 0 : 1,
            ]
        );
    }
}
