<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DesktopApplicationUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'desktop-update-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('settings')->insert([
            'settingName' => 'api_authcode',
            'settingValue' => self::TOKEN,
            'settingType' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_scanner_can_download_an_authorized_update_file(): void
    {
        $directory = storage_path('app/application-updates');
        $file = 'test-'.Str::uuid().'.blockmap';
        $path = $directory.DIRECTORY_SEPARATOR.$file;

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, 'update contents');

        try {
            $this->get("/api/application/desktop-updates/{$file}")
                ->assertForbidden();

            $this->withHeader('X-API-AUTHCODE', self::TOKEN)
                ->get("/api/application/desktop-updates/{$file}")
                ->assertOk()
                ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
        } finally {
            @unlink($path);
        }
    }

    public function test_update_endpoint_rejects_path_traversal(): void
    {
        $this->withHeader('X-API-AUTHCODE', self::TOKEN)
            ->get('/api/application/desktop-updates/%252e%252e%252f.env')
            ->assertNotFound();
    }
}
