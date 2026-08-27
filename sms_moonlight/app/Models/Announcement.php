<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\AnnouncementHtml;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Announcement
 *
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $target_audience
 * @property Carbon|null $expiry_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Announcement extends Model
{
    protected $table = 'announcements';

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
        'title',
        'content',
        'target_audience',
        'expiry_date',
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
            'title' => 'string',
            'content' => 'string',
            'target_audience' => 'string',
            'expiry_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Sanitize formatted announcement content on both write and read.
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => AnnouncementHtml::sanitize($value),
            set: fn (?string $value): string => AnnouncementHtml::sanitize($value),
        );
    }
}
