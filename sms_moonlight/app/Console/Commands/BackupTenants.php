<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantBackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupTenants extends Command
{
    protected $signature = 'saas:backup {tenant? : Tenant ID or slug} {--verify : Verify each archive after creation}';

    protected $description = 'Create portable, compressed backups for one or all school tenants';

    public function handle(TenantBackupService $backups): int
    {
        $query = Tenant::query();
        if ($identifier = $this->argument('tenant')) {
            $query->where(fn ($query) => $query->where('id', $identifier)->orWhere('slug', $identifier));
        }
        $tenants = $query->get();
        if ($tenants->isEmpty()) {
            $this->error('No matching school tenant was found.');

            return self::FAILURE;
        }

        $failures = 0;
        foreach ($tenants as $tenant) {
            try {
                $backup = $backups->create($tenant);
                if ($this->option('verify')) {
                    $backups->verify($backup);
                }
                $this->info("{$tenant->slug}: {$backup->path}");
            } catch (Throwable $exception) {
                $failures++;
                $this->error("{$tenant->slug}: {$exception->getMessage()}");
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
