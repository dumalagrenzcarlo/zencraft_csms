<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AssignmentSubmission
 *
 * @property int $id
 * @property int $assignment_id
 * @property int $student_id
 * @property string $file_path
 * @property string $file_name
 * @property string|null $notes
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AssignmentSubmission extends Model
{
    protected $table = 'assignment_submissions';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'assignment_id',
        'student_id',
        'file_path',
        'file_name',
        'notes',
        'submitted_at',
    ];

    protected $attributes = [
        'notes' => null,
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'assignment_id' => 'integer',
            'student_id' => 'integer',
            'file_path' => 'string',
            'file_name' => 'string',
            'notes' => 'string',
            'submitted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
