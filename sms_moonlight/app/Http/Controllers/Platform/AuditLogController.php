<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Models\PlatformAuditLog;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(): View
    {
        return view('platform.audit.index', [
            'logs' => PlatformAuditLog::query()->with(['user', 'tenant'])->latest('created_at')->paginate(50),
        ]);
    }
}
