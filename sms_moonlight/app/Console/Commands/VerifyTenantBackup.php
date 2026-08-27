<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TenantBackup;
use App\Services\TenantBackupService;
use Illuminate\Console\Command;

class VerifyTenantBackup extends Command
{
    protected $signature = 'saas:backup:verify {backup : Central tenant_backups record ID}';

    protected $description = 'Verify a tenant backup checksum and archive manifest';

    public function handle(TenantBackupService $backups): int
    {
        $backup = TenantBackup::query()->findOrFail($this->argument('backup'));
        $backups->verify($backup);
        $this->info("Backup {$backup->id} verified successfully.");

        return self::SUCCESS;
    }
}
