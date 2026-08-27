<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('advisers')->orderBy('id')->get() as $adviser) {
            $username = $this->uniqueUsernameFromName((string) $adviser->name, $adviser->user_id);
            $userId = DB::table('moonshine_users')->where('username', $username)->value('id');
            $userId = $userId ? (int) $userId : ($adviser->user_id ? (int) $adviser->user_id : null);

            if (! $userId || ! DB::table('moonshine_users')->where('id', $userId)->exists()) {
                $userId = (int) DB::table('moonshine_users')->insertGetId([
                    'moonshine_user_role_id' => 2,
                    'username' => $username,
                    'email' => $username . '@' . config('app.domain', 'localhost'),
                    'name' => $adviser->name,
                    'password' => Hash::make(config('school.default_config_teacher_password', 'teacher123')),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('advisers')
                    ->where('id', $adviser->id)
                    ->update(['user_id' => $userId]);

                continue;
            }

            DB::table('moonshine_users')->where('id', $userId)->update([
                'moonshine_user_role_id' => 2,
                'username' => $username,
                'email' => $username . '@' . config('app.domain', 'localhost'),
                'name' => $adviser->name,
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('students')->whereNotNull('lrn')->orderBy('id')->get() as $student) {
            $lrn = trim((string) $student->lrn);

            if ($lrn === '') {
                continue;
            }

            $userId = $student->user_id ? (int) $student->user_id : null;
            $existingUserId = DB::table('moonshine_users')->where('username', $lrn)->value('id');
            $fullName = trim(trim((string) $student->firstname) . ' ' . trim((string) $student->lastname));
            $userId = $existingUserId ? (int) $existingUserId : $userId;

            if (! $userId || ! DB::table('moonshine_users')->where('id', $userId)->exists()) {
                $userId = (int) DB::table('moonshine_users')->insertGetId([
                        'moonshine_user_role_id' => 3,
                        'username' => $lrn,
                        'email' => $lrn . '@' . config('app.domain', 'localhost'),
                        'name' => $fullName !== '' ? $fullName : $lrn,
                        'password' => Hash::make(config('school.default_config_student_password', 'student123')),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                DB::table('students')
                    ->where('id', $student->id)
                    ->update(['user_id' => $userId]);
            }

            DB::table('moonshine_users')->where('id', $userId)->update([
                'moonshine_user_role_id' => 3,
                'username' => $lrn,
                'email' => $lrn . '@' . config('app.domain', 'localhost'),
                'name' => $fullName !== '' ? $fullName : $lrn,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        //
    }

    private function uniqueUsernameFromName(string $name, ?int $ignoreUserId = null): string
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
            ? $parts->first() . '.' . $parts->last()
            : (string) Str::of($name)->slug('.');

        $baseUsername = $baseUsername !== '' ? $baseUsername : 'adviser';
        $username = $baseUsername;
        $counter = 2;

        while (
            DB::table('moonshine_users')
                ->where('username', $username)
                ->when($ignoreUserId, fn ($query) => $query->where('id', '<>', $ignoreUserId))
                ->exists()
        ) {
            $username = $baseUsername . '.' . $counter;
            $counter++;
        }

        return $username;
    }
};
