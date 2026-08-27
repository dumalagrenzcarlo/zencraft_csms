<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class TenantBackupService
{
    public function create(Tenant $tenant): TenantBackup
    {
        $disk = (string) config('saas.backup_disk', 'local');
        $backup = TenantBackup::query()->create(['tenant_id' => $tenant->id, 'status' => 'running', 'disk' => $disk, 'started_at' => now()]);
        $temporary = tempnam(sys_get_temp_dir(), 'zencraft-backup-');

        try {
            if ($temporary === false || ! function_exists('gzopen')) {
                throw new RuntimeException('A writable temporary directory and the PHP zlib extension are required.');
            }

            [$tables, $rows] = $tenant->run(fn (): array => $this->writeTenantArchive($temporary, $tenant));
            $path = sprintf('saas-backups/%s/%s-%d.jsonl.gz', $tenant->id, now()->format('Ymd-His'), $backup->id);
            $stream = fopen($temporary, 'rb');
            throw_unless(is_resource($stream) && Storage::disk($disk)->put($path, $stream), RuntimeException::class, 'Could not persist the backup archive.');
            if (is_resource($stream)) {
                fclose($stream);
            }

            $backup->forceFill([
                'status' => 'completed', 'path' => $path, 'checksum' => hash_file('sha256', $temporary),
                'size_bytes' => filesize($temporary) ?: 0, 'table_count' => $tables,
                'row_count' => $rows, 'completed_at' => now(),
            ])->save();

            return $backup->fresh();
        } catch (Throwable $exception) {
            $backup->forceFill(['status' => 'failed', 'failure_message' => mb_substr($exception->getMessage(), 0, 4000)])->save();
            throw $exception;
        } finally {
            if (is_string($temporary) && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    public function verify(TenantBackup $backup): bool
    {
        abort_unless($backup->path && Storage::disk($backup->disk)->exists($backup->path), 422, 'Backup archive is missing.');
        $source = Storage::disk($backup->disk)->readStream($backup->path);
        $temporary = tempnam(sys_get_temp_dir(), 'zencraft-verify-');

        try {
            throw_unless(is_resource($source) && is_string($temporary), RuntimeException::class, 'Could not open the backup archive.');
            $destination = fopen($temporary, 'wb');
            throw_unless(is_resource($destination), RuntimeException::class, 'Could not create a verification file.');
            stream_copy_to_stream($source, $destination);
            fclose($destination);
            fclose($source);

            throw_unless(hash_file('sha256', $temporary) === $backup->checksum, RuntimeException::class, 'Backup checksum does not match.');
            [$tables, $rows] = $this->inspectArchive($temporary);
            throw_unless($tables === $backup->table_count && $rows === $backup->row_count, RuntimeException::class, 'Backup contents do not match the recorded manifest.');

            $backup->forceFill(['status' => 'verified', 'verified_at' => now(), 'failure_message' => null])->save();

            return true;
        } catch (Throwable $exception) {
            $backup->forceFill(['status' => 'verification_failed', 'failure_message' => mb_substr($exception->getMessage(), 0, 4000)])->save();
            throw $exception;
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_string($temporary) && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function writeTenantArchive(string $path, Tenant $tenant): array
    {
        $archive = gzopen($path, 'wb9');
        throw_unless($archive !== false, RuntimeException::class, 'Could not create the compressed archive.');
        $tables = Schema::getTableListing();
        $rows = 0;
        gzwrite($archive, json_encode(['type' => 'manifest', 'version' => 1, 'tenant_id' => $tenant->id, 'created_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR)."\n");

        foreach ($tables as $table) {
            gzwrite($archive, json_encode(['type' => 'table', 'name' => $table], JSON_THROW_ON_ERROR)."\n");
            foreach (DB::table($table)->cursor() as $row) {
                gzwrite($archive, json_encode(['type' => 'row', 'table' => $table, 'data' => (array) $row], JSON_THROW_ON_ERROR)."\n");
                $rows++;
            }
        }
        gzclose($archive);

        return [count($tables), $rows];
    }

    private function inspectArchive(string $path): array
    {
        $archive = gzopen($path, 'rb');
        throw_unless($archive !== false, RuntimeException::class, 'Backup archive cannot be decompressed.');
        $tables = 0;
        $rows = 0;
        $manifest = false;

        while (! gzeof($archive)) {
            $line = gzgets($archive);
            if (! is_string($line) || trim($line) === '') {
                continue;
            }
            $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $manifest = $manifest || ($record['type'] ?? null) === 'manifest';
            $tables += ($record['type'] ?? null) === 'table' ? 1 : 0;
            $rows += ($record['type'] ?? null) === 'row' ? 1 : 0;
        }
        gzclose($archive);
        throw_unless($manifest, RuntimeException::class, 'Backup manifest is missing.');

        return [$tables, $rows];
    }
}
