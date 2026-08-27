<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class OperationalFileStorage
{
    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($this->safePath($directory), 'local');
    }

    public function download(string $path, string $downloadName): BinaryFileResponse
    {
        $path = $this->safePath($path);

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return response()->download(
                    Storage::disk($disk)->path($path),
                    $this->safeDownloadName($downloadName)
                );
            }
        }

        abort(404);
    }

    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $path = $this->safePath((string) $path);
        Storage::disk('local')->delete($path);
        Storage::disk('public')->delete($path);
    }

    private function safePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        abort_if($path === '' || str_contains($path, '..') || str_contains($path, "\0"), 404);

        return $path;
    }

    private function safeDownloadName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?: 'download';

        return mb_substr($name, 0, 180);
    }
}
