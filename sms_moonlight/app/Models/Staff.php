<?php

declare(strict_types=1);

namespace App\Models;

class Staff extends Adviser
{
    protected $table = 'advisers';

    protected $attributes = [
        'staff_type' => self::TYPE_STAFF,
    ];
}
