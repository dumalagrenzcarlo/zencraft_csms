<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('uploads:repair {path? : Optional upload path, for example settings/logo.png} {--check : Only report status without copying files}', function (): int {
    $path = $this->argument('path');
    $checkOnly = (bool) $this->option('check');
    $publicRoot = rtrim(Storage::disk('public')->path(''), DIRECTORY_SEPARATOR);
    $legacyRoot = storage_path('app/public');

    if (! $checkOnly) {
        File::ensureDirectoryExists($publicRoot);
    }

    $this->info('Public uploads root: ' . $publicRoot);
    $this->info('Legacy storage root: ' . $legacyRoot);

    if (filled($path)) {
        $relativePath = ltrim(str_replace('\\', '/', (string) $path), '/');
        $publicPath = Storage::disk('public')->path($relativePath);
        $legacyPath = storage_path('app/public/' . $relativePath);

        $this->line('Checking: ' . $relativePath);

        if (is_file($publicPath)) {
            $this->info('FOUND in public uploads: ' . $publicPath);

            return self::SUCCESS;
        }

        if (is_file($legacyPath)) {
            $this->warn('FOUND only in legacy storage: ' . $legacyPath);

            if (! $checkOnly) {
                File::ensureDirectoryExists(dirname($publicPath));
                File::copy($legacyPath, $publicPath);
                $this->info('Copied to public uploads: ' . $publicPath);
            }

            return self::SUCCESS;
        }

        $this->error('MISSING from both public uploads and legacy storage.');

        return self::FAILURE;
    }

    $copied = 0;
    $alreadyPublic = 0;

    if (is_dir($legacyRoot)) {
        foreach (File::allFiles($legacyRoot) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $publicPath = Storage::disk('public')->path($relativePath);

            if (is_file($publicPath)) {
                $alreadyPublic++;
                continue;
            }

            if (! $checkOnly) {
                File::ensureDirectoryExists(dirname($publicPath));
                File::copy($file->getPathname(), $publicPath);
            }

            $copied++;
        }
    }

    $this->info($checkOnly ? "Files needing copy: {$copied}" : "Files copied: {$copied}");
    $this->line("Files already public: {$alreadyPublic}");

    $settingFiles = DB::table('settings')
        ->where(function ($query): void {
            $query->where('settingType', 'file')
                ->orWhere('settingName', 'school_logo');
        })
        ->whereNotNull('settingValue')
        ->pluck('settingValue', 'settingName');

    foreach ($settingFiles as $settingName => $settingPath) {
        $relativePath = ltrim(str_replace('\\', '/', (string) $settingPath), '/');

        if ($relativePath === '') {
            continue;
        }

        $existsInPublic = is_file(Storage::disk('public')->path($relativePath));
        $existsInLegacy = is_file(storage_path('app/public/' . $relativePath));
        $status = $existsInPublic ? 'public' : ($existsInLegacy ? 'legacy only' : 'missing');

        $this->line("{$settingName}: {$relativePath} [{$status}]");
    }

    return self::SUCCESS;
})->purpose('Copy legacy storage uploads to the configured public uploads folder and report missing setting files');
