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
use Illuminate\Support\Str;
use MoonShine\Laravel\Models\MoonshineUser;

/**
 * Class Adviser
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $rank
 * @property string $major
 * @property bool $is_college_instructor
 * @property string $profile_photo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Adviser extends Model
{
    public const TYPE_TEACHER = 'teacher';

    public const TYPE_INSTRUCTOR = 'instructor';

    public const TYPE_STAFF = 'staff';

    protected $table = 'advisers';

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
        'user_id',
        'rfid_card_uid',
        'name',
        'rank',
        'major',
        'staff_type',
        'is_college_instructor',
        'shift_start_time',
        'shift_end_time',
        'profile_photo',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'staff_type' => self::TYPE_TEACHER,
        'is_college_instructor' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'rfid_card_uid' => 'string',
            'name' => 'string',
            'rank' => 'string',
            'major' => 'string',
            'staff_type' => 'string',
            'is_college_instructor' => 'boolean',
            'shift_start_time' => 'string',
            'shift_end_time' => 'string',
            'profile_photo' => 'string',
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

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'adviser_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassesModel::class, 'adviser_id');
    }

    public function collegeCourseOfferings(): HasMany
    {
        return $this->hasMany(CollegeCourseOffering::class, 'instructor_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassAdviserSchedule::class, 'adviser_id');
    }

    /**
     * Return teachers who may advise a class.
     *
     * Advisers may own multiple classes, including within the same school year.
     * The arguments remain for compatibility with the reactive class form.
     */
    public function scopeAvailableForSchoolYear(
        Builder $query,
        ?int $schoolYearId,
        ?int $ignoreClassId = null
    ): Builder {
        return $query->where('staff_type', self::TYPE_TEACHER);
    }

    public function scopeTeachers(Builder $query): Builder
    {
        return $query->where('staff_type', self::TYPE_TEACHER);
    }

    public function scopeInstructors(Builder $query): Builder
    {
        return $query->eligibleCollegeInstructors();
    }

    public function scopeEligibleCollegeInstructors(Builder $query): Builder
    {
        return $query->where(function (Builder $personnel): void {
            $personnel
                ->where('staff_type', self::TYPE_INSTRUCTOR)
                ->orWhere(function (Builder $teacher): void {
                    $teacher
                        ->where('staff_type', self::TYPE_TEACHER)
                        ->where('is_college_instructor', true);
                });
        });
    }

    public function scopeVisibleAsPersonnelType(Builder $query, string $personnelType): Builder
    {
        return $personnelType === self::TYPE_INSTRUCTOR
            ? $query->eligibleCollegeInstructors()
            : $query->where('staff_type', $personnelType);
    }

    public function isCollegeInstructor(): bool
    {
        return $this->staff_type === self::TYPE_INSTRUCTOR
            || (
                $this->staff_type === self::TYPE_TEACHER
                && $this->is_college_instructor
            );
    }

    public function scopeStaff(Builder $query): Builder
    {
        return $query->where('staff_type', self::TYPE_STAFF);
    }

    protected static function booted(): void
    {
        static::saving(function (Adviser $adviser): void {
            RfidCardUid::ensureUnique(
                RfidCardUid::normalize($adviser->rfid_card_uid),
                $adviser->getTable(),
                $adviser->getKey(),
            );
        });

        static::saved(function (Adviser $adviser): void {
            if ($adviser->staff_type !== self::TYPE_STAFF) {
                $adviser->syncMoonshineUser();
            }
        });
    }

    public function syncMoonshineUser(): void
    {
        $username = static::uniqueUsernameForName((string) $this->name, $this->user_id);
        $currentUser = MoonshineUser::query()
            ->where('username', $username)
            ->first();

        if (! $currentUser && $this->user_id) {
            $currentUser = MoonshineUser::query()->find($this->user_id);
        }

        if (! $currentUser) {
            $currentUser = new MoonshineUser;
            $userData = [
                'password' => Hash::make(config('school.default_config_teacher_password', 'teacher123')),
            ];

            if (Schema::hasColumn('moonshine_users', 'must_change_password')) {
                $userData['must_change_password'] = true;
            }

            $currentUser->forceFill($userData);
        }

        $currentUser->forceFill([
            'moonshine_user_role_id' => 2,
            'username' => $username,
            'email' => $username.'@'.config('app.domain', 'localhost'),
            'name' => $this->name,
        ]);
        $currentUser->save();

        if ((int) $this->user_id !== (int) $currentUser->id) {
            $this->updateQuietly([
                'user_id' => $currentUser->id,
            ]);
        }
    }

    private static function uniqueUsernameForName(string $name, ?int $ignoreUserId = null): string
    {
        $parts = Str::of($name)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s-]/', '')
            ->replaceMatches('/[\s-]+/', ' ')
            ->trim()
            ->explode(' ')
            ->filter()
            ->values();

        $baseUsername = $parts->count() >= 2
            ? $parts->first().'.'.$parts->last()
            : (string) Str::of($name)->slug('.');

        $baseUsername = $baseUsername !== '' ? $baseUsername : 'adviser';
        $username = $baseUsername;
        $counter = 2;

        while (
            MoonshineUser::query()
                ->where('username', $username)
                ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
                ->exists()
        ) {
            $username = $baseUsername.'.'.$counter;
            $counter++;
        }

        return $username;
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo) {
            $path = ltrim((string) $this->profile_photo, '/');

            return asset('uploads/'.(str_starts_with($path, 'advisers/') ? $path : 'advisers/'.$path));
        }

        return $this->generateInitialsAvatar();
    }

    private function getInitials(?string $name): string
    {
        $words = explode(' ', trim($name ?? ''));

        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return substr($initials, 0, 2); // max 2 letters
    }

    private function getAvatarColor(string $name): string
    {
        $colors = [
            '#3B82F6', // blue
            '#10B981', // green
            '#F59E0B', // amber
            '#EF4444', // red
            '#8B5CF6', // purple
            '#06B6D4', // cyan
        ];

        $index = crc32($name) % count($colors);

        return $colors[$index];
    }

    private function generateInitialsAvatar(int $size = 120): string
    {
        $initials = $this->getInitials($this->name);
        $bg = $this->getAvatarColor($this->name);

        $radius = $size / 2;
        $fontSize = $size * 0.35;

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
            <rect width="{$size}" height="{$size}" rx="{$radius}" fill="{$bg}"/>
            <text x="50%" y="50%" text-anchor="middle"
                dominant-baseline="middle"
                font-size="{$fontSize}"
                fill="#ffffff"
                font-family="Arial, sans-serif"
                font-weight="bold">
                {$initials}
            </text>
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
