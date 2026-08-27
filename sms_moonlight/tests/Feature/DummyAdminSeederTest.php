<?php

namespace Tests\Feature;

use Database\Seeders\DummyAdminSeeder;
use RuntimeException;
use Tests\TestCase;

class DummyAdminSeederTest extends TestCase
{
    public function test_it_is_disabled_by_default(): void
    {
        config()->set('dummy_admin.enabled', false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Set DUMMY_ADMIN_ENABLED=true');

        (new DummyAdminSeeder)->run();
    }

    public function test_it_rejects_non_local_or_staging_environments(): void
    {
        config()->set('dummy_admin.enabled', true);
        app()->detectEnvironment(fn (): string => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('only when APP_ENV is local or staging');

        (new DummyAdminSeeder)->run();
    }

    public function test_it_rejects_non_mysql_connections(): void
    {
        config()->set('dummy_admin.enabled', true);
        app()->detectEnvironment(fn (): string => 'local');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('only on a MySQL/MariaDB connection');

        (new DummyAdminSeeder)->run();
    }
}
