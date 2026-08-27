<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection(config('tenancy.database.central_connection'))->select('select 1');
            $storageReady = is_dir(storage_path('framework')) && is_writable(storage_path('framework'));

            return response()->json([
                'status' => $storageReady ? 'ready' : 'not_ready',
                'checks' => ['database' => 'ok', 'storage' => $storageReady ? 'ok' : 'failed'],
            ], $storageReady ? 200 : 503);
        } catch (Throwable) {
            return response()->json(['status' => 'not_ready', 'checks' => ['database' => 'failed']], 503);
        }
    }
}
