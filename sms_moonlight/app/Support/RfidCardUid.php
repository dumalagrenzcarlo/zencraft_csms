<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RfidCardUid
{
    public static function normalize(mixed $value): ?string
    {
        $uid = strtoupper(trim((string) $value));

        return $uid === '' ? null : $uid;
    }

    public static function ensureUnique(?string $uid, string $ownerTable, ?int $ownerId): void
    {
        if ($uid === null) {
            return;
        }

        $assignedToStudent = DB::table('students')
            ->where('rfid_card_uid', $uid)
            ->when($ownerTable === 'students' && $ownerId, fn ($query) => $query->where('id', '!=', $ownerId))
            ->exists();

        $assignedToTeacherOrStaff = DB::table('advisers')
            ->where('rfid_card_uid', $uid)
            ->when($ownerTable === 'advisers' && $ownerId, fn ($query) => $query->where('id', '!=', $ownerId))
            ->exists();

        if ($assignedToStudent || $assignedToTeacherOrStaff) {
            throw ValidationException::withMessages([
                'rfid_card_uid' => 'This RFID card is already assigned to another student, teacher, or staff member.',
            ]);
        }
    }
}
