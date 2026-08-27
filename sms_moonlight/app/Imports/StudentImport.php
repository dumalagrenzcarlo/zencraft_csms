<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\ENV;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

class StudentImport implements ToCollection
{
    public int $created = 0;

    public int $updated = 0;

    public function collection(Collection $rows): void
    {
        @set_time_limit(0);

        if (! $this->hasStudentHeader($rows->first())) {
            return;
        }

        $rows->shift();

        $defaultPassword = DB::table('settings')
            ->where('settingName', 'default_config_student_password')
            ->value('settingValue') ?? 'student123';
        $defaultPasswordHash = Hash::make($defaultPassword);
        $mustChangePasswordExists = Schema::hasColumn('moonshine_users', 'must_change_password');
        $appDomain = ENV::get('APP_DOMAIN', 'localhost');

        DB::transaction(function () use ($rows, $defaultPasswordHash, $mustChangePasswordExists, $appDomain): void {
            foreach ($rows as $row) {
                $lrn = trim((string) ($row[0] ?? ''));

                if ($lrn === '') {
                    continue;
                }

                $now = now();
                $fullname = trim(
                    trim((string) ($row[1] ?? '')).' '.
                    trim((string) ($row[2] ?? ''))
                );

                $user = DB::table('moonshine_users')->where('username', $lrn)->first();

                if (! $user) {
                    $userData = [
                        'moonshine_user_role_id' => 3,
                        'username' => $lrn,
                        'email' => $lrn.'@'.$appDomain,
                        'name' => $fullname,
                        'password' => $defaultPasswordHash,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if ($mustChangePasswordExists) {
                        $userData['must_change_password'] = true;
                    }

                    $userId = (int) DB::table('moonshine_users')->insertGetId($userData);
                } else {
                    $userId = (int) $user->id;

                    DB::table('moonshine_users')
                        ->where('id', $userId)
                        ->update([
                            'moonshine_user_role_id' => 3,
                            'username' => $lrn,
                            'email' => $lrn.'@'.$appDomain,
                            'name' => $fullname,
                            'updated_at' => $now,
                        ]);
                }

                $studentData = [
                    'user_id' => $userId,
                    'lrn' => $lrn,
                    'firstname' => trim((string) ($row[1] ?? '')),
                    'lastname' => trim((string) ($row[2] ?? '')),
                    'middlename' => trim((string) ($row[3] ?? '')),
                    'gender' => trim((string) ($row[4] ?? '')),
                    'dob' => $this->parseBirthday($row[5] ?? null),
                    'address' => trim((string) ($row[6] ?? '')),
                    'birthplace' => trim((string) ($row[7] ?? '')),
                    'parent_guardian' => trim((string) ($row[8] ?? '')),
                    'parent_guardian_address' => trim((string) ($row[9] ?? '')),
                    'parent_guardian_relationship' => trim((string) ($row[10] ?? '')),
                    'is_4ps_member' => strtolower(trim((string) ($row[11] ?? ''))) === 'yes',
                    'weight' => $row[12] ?? null,
                    'height' => $row[13] ?? null,
                    'elementary_school_id' => trim((string) ($row[14] ?? '')),
                    'elementary_school_name' => trim((string) ($row[15] ?? '')),
                    'elementary_school_address' => trim((string) ($row[16] ?? '')),
                    'elementary_school_grade' => trim((string) ($row[17] ?? '')),
                    'elementary_school_citation' => trim((string) ($row[18] ?? '')),
                    'updated_at' => $now,
                ];

                $student = DB::table('students')->where('lrn', $lrn)->first();

                if (! $student) {
                    $studentData['created_at'] = $now;
                    $studentId = (int) DB::table('students')->insertGetId($studentData);
                    $this->created++;
                } else {
                    $studentId = (int) $student->id;
                    DB::table('students')->where('id', $studentId)->update($studentData);
                    $this->updated++;
                }

                DB::table('student_access')->updateOrInsert(
                    ['student_id' => $studentId],
                    [
                        'student_id' => $studentId,
                        'user_id' => $userId,
                        'active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        });
    }

    private function parseBirthday(mixed $value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return Carbon::instance(
                SpreadsheetDate::excelToDateTimeObject($value)
            )->toDateString();
        }

        return Carbon::parse($value)->toDateString();
    }

    private function hasStudentHeader(mixed $headerRow): bool
    {
        if ($headerRow instanceof Collection) {
            $headerRow = $headerRow->all();
        }

        if (! is_array($headerRow)) {
            return false;
        }

        $headers = array_map(
            static fn (mixed $header): string => strtolower(
                preg_replace('/[^a-z0-9]+/i', '', trim((string) $header)) ?? ''
            ),
            $headerRow
        );

        return ($headers[0] ?? '') === 'lrn'
            && ($headers[1] ?? '') === 'firstname'
            && ($headers[2] ?? '') === 'lastname'
            && ($headers[3] ?? '') === 'middlename'
            && str_starts_with($headers[4] ?? '', 'gender')
            && (
                str_starts_with($headers[5] ?? '', 'birthday')
                || str_starts_with($headers[5] ?? '', 'dob')
            );
    }
}
