<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ValidateTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Setting::enabled('qr_code_enabled', true)) {
            return response()->json('QR code features are disabled.', 404);
        }

        $token = (string) (
            $request->input('token')
            ?? $request->query('token')
            ?? ''
        );

        $expectedToken = (string) DB::table('settings')
            ->where('settingName', 'api_authcode')
            ->value('settingValue');

        if (
            $token === ''
            || $expectedToken === ''
            || ! hash_equals($expectedToken, $token)
        ) {
            return response()->json('Access Denied');
        }

        return response()->json('Access Granted');
    }
}
