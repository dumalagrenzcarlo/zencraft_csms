<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicationDataController extends Controller
{
    private function validateToken(Request $request): bool
    {
        $token = (string) (
            $request->header('X-API-AUTHCODE')
            ?? $request->input('token')
            ?? ''
        );

        $expected = (string) DB::table('settings')
            ->where('settingName', 'api_authcode')
            ->value('settingValue');

        return $token !== '' && hash_equals($expected, $token);
    }

    // ----------------------------
    // GET SCHOOL DATA
    // ----------------------------
    public function getSchoolData(Request $request): JsonResponse
    {
        if (! $this->validateToken($request)) {
            return response()->json('Access Denied', 403);
        }

        $settings = DB::table('settings')
            ->whereIn('settingName', [
                'school_id',
                'school_name',
                'school_logo',
            ])
            ->pluck('settingValue', 'settingName');

        $appSettings = [
            'school_id' => $settings['school_id'] ?? null,
            'school_name' => $settings['school_name'] ?? null,
            'school_logo' => $settings['school_logo'] ?? null,
        ];

        return response()->json($appSettings);
    }

    // ----------------------------
    // DOWNLOAD STUDENT IMAGES (ZIP)
    // ----------------------------
    public function downloadStudentImages(Request $request)
    {
        if (! Setting::enabled('qr_code_enabled', true)) {
            return response('QR code features are disabled.', 404);
        }

        if (! $this->validateToken($request)) {
            return response('Access Denied', 403);
        }

        $baseDir = Storage::disk('public')->path('students');

        if (! is_dir($baseDir)) {
            return response('Student folder not found.', 404);
        }

        $zip = new \ZipArchive;
        Storage::disk('local')->makeDirectory('cache');
        $zipName = Storage::disk('local')->path('cache/student_images_'.now()->format('YmdHisv').'.zip');

        if ($zip->open($zipName, \ZipArchive::CREATE) !== true) {
            return response('Could not create zip file.', 500);
        }

        $this->addFilesToZip($zip, $baseDir, strlen($baseDir));

        $zip->close();

        return response()
            ->download($zipName, 'student_images.zip')
            ->deleteFileAfterSend(true);
    }

    // ----------------------------
    // DESKTOP APPLICATION UPDATES
    // ----------------------------
    public function downloadDesktopUpdate(Request $request, string $file): BinaryFileResponse
    {
        abort_unless($this->validateToken($request), 403, 'Access Denied');

        $file = rawurldecode($file);
        abort_if(
            $file === '' || basename($file) !== $file || preg_match('/^[A-Za-z0-9][A-Za-z0-9._() +\-]*$/', $file) !== 1,
            404
        );

        $updateDirectory = storage_path('app/application-updates');
        $resolvedDirectory = realpath($updateDirectory);
        $resolvedFile = realpath($updateDirectory.DIRECTORY_SEPARATOR.$file);

        abort_if(
            $resolvedDirectory === false
            || $resolvedFile === false
            || ! str_starts_with($resolvedFile, $resolvedDirectory.DIRECTORY_SEPARATOR)
            || ! is_file($resolvedFile),
            404
        );

        $headers = $file === 'latest.yml'
            ? ['Cache-Control' => 'no-store, no-cache, must-revalidate']
            : ['Cache-Control' => 'public, max-age=31536000, immutable'];

        return response()->file($resolvedFile, $headers);
    }

    // ----------------------------
    // RECURSIVE ZIP HELPER
    // ----------------------------
    private function addFilesToZip(\ZipArchive $zip, string $folder, int $baseLen): void
    {
        $items = scandir($folder);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = realpath($folder.DIRECTORY_SEPARATOR.$item);

            if (! $fullPath) {
                continue;
            }

            $localPath = str_replace('\\', '/', substr($fullPath, $baseLen));
            $localPath = ltrim($localPath, '/');

            if (strpos($localPath, '..') !== false) {
                continue;
            }

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($localPath);
                $this->addFilesToZip($zip, $fullPath, $baseLen);
            } elseif (is_file($fullPath)) {
                $zip->addFile($fullPath, $localPath);
            }
        }
    }
}
