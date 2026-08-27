<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class MoonshineUser
 *
 * @property int $id
 * @property int $moonshine_user_role_id
 * @property string $email
 * @property string|null $username
 * @property string $password
 * @property bool $must_change_password
 * @property string $name
 * @property string|null $avatar
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property MoonshineUserRole $moonshineUserRole
 */
class MoonshineUser extends Model
{
    protected $table = 'moonshine_users';

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
        'moonshine_user_role_id',
        'email',
        'username',
        'password',
        'must_change_password',
        'name',
        'avatar',
        'remember_token',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'moonshine_user_role_id' => '1',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'moonshine_user_role_id' => 'integer',
            'email' => 'string',
            'username' => 'string',
            'password' => 'string',
            'must_change_password' => 'boolean',
            'name' => 'string',
            'avatar' => 'string',
            'remember_token' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MoonshineUserRole, $this>
     */
    public function moonshineUserRole(): BelongsTo
    {
        return $this->belongsTo(MoonshineUserRole::class, 'moonshine_user_role_id');
    }
}
