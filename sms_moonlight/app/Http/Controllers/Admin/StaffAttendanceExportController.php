<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\StaffAttendanceExport;
use App\Support\StaffAttendanceReport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaffAttendanceExportController extends Controller
{
    public function __invoke(Request $request, StaffAttendanceReport $report): BinaryFileResponse
    {
        abort_unless((bool) config('school_portal.features.teacher_staff_attendance'), 404);

        [$startDate, $endDate] = $report->range(
            $request->query('start_date'),
            $request->query('end_date'),
        );

        $rows = $report->filterRows(
            $report->rows($startDate, $endDate),
            $request->query('search'),
            $request->query('staff_type'),
            $request->query('status'),
        );

        return Excel::download(
            new StaffAttendanceExport($rows),
            "teacher-staff-attendance-{$startDate}-to-{$endDate}.xlsx",
        );
    }
}
