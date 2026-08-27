<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantMigrationInventory extends Command
{
    protected $signature = 'saas:migration:inventory {tenant : Tenant ID or slug}';

    protected $description = 'Report key tenant record counts for migration reconciliation';

    public function handle(): int
    {
        $identifier = $this->argument('tenant');
        $tenant = Tenant::query()->where('id', $identifier)->orWhere('slug', $identifier)->firstOrFail();
        $counts = $tenant->run(fn (): array => collect([
            'students', 'advisers', 'classes', 'class_students', 'attendance_record',
            'assignments', 'announcements', 'college_enrollments', 'student_payment_histories', 'quiz_group',
        ])->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all());

        $this->table(['Dataset', 'Records'], collect($counts)->map(fn (int $count, string $table): array => [$table, $count])->values()->all());
        $this->line(json_encode(['tenant_id' => $tenant->id, 'generated_at' => now()->toIso8601String(), 'counts' => $counts], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
