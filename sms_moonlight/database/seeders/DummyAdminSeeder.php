<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use RuntimeException;

class DummyAdminSeeder extends Seeder
{
    /**
     * Create an explicitly labeled admin account for local/staging MySQL only.
     */
    public function run(): void
    {
        $this->assertSafeEnvironment();

        $email = trim((string) config('dummy_admin.email'));
        $username = trim((string) config('dummy_admin.username'));
        $name = trim((string) config('dummy_admin.name'));
        $password = (string) config('dummy_admin.password');

        if ($email === '' || $username === '' || $name === '') {
            throw new RuntimeException('Dummy admin name, email, and username must not be empty.');
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('DUMMY_ADMIN_PASSWORD must contain at least 12 characters.');
        }

        $usernameOwner = MoonshineUser::query()
            ->where('username', $username)
            ->where('email', '!=', $email)
            ->first();

        if ($usernameOwner !== null) {
            throw new RuntimeException("The dummy admin username [{$username}] is already used by another account.");
        }

        DB::transaction(function () use ($email, $username, $name, $password): void {
            DB::table('moonshine_user_roles')->updateOrInsert(
                ['id' => MoonshineUserRole::DEFAULT_ROLE_ID],
                [
                    'name' => 'Admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $attributes = [
                'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
                'name' => $name,
                'username' => $username,
                'password' => Hash::make($password),
            ];

            if (Schema::hasColumn('moonshine_users', 'must_change_password')) {
                $attributes['must_change_password'] = false;
            }

            MoonshineUser::query()->updateOrCreate(['email' => $email], $attributes);
        });

        $this->command?->info("Dummy admin [{$email}] is ready for local/staging use.");
    }

    private function assertSafeEnvironment(): void
    {
        if (! (bool) config('dummy_admin.enabled')) {
            throw new RuntimeException('Set DUMMY_ADMIN_ENABLED=true before running DummyAdminSeeder.');
        }

        if (! app()->environment(['local', 'staging'])) {
            throw new RuntimeException('DummyAdminSeeder may run only when APP_ENV is local or staging.');
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('DummyAdminSeeder may run only on a MySQL/MariaDB connection.');
        }
    }
}
