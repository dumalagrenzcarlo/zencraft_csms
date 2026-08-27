<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Adviser;
use App\Support\MoonShineTablePagination;
use App\Support\StaffAttendanceReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\Support\Enums\Color;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Text;

#[SkipMenu]
class StaffAttendanceDashboard extends Page
{
    public function getTitle(): string
    {
        return 'Staff Attendance Dashboard';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): array
    {
        $report = app(StaffAttendanceReport::class);
        [$startDate, $endDate] = $report->range(
            request()->query('start_date'),
            request()->query('end_date'),
        );
        $personnelTypes = $report->personnelTypes();
        $staffCount = DB::table('advisers')
            ->whereIn('staff_type', $personnelTypes)
            ->count();

        $rows = $report->rows($startDate, $endDate);
        $filters = [
            'search' => trim((string) request()->query('search')),
            'staff_type' => (string) request()->query('staff_type'),
            'status' => (string) request()->query('status'),
        ];
        $filteredRows = $report->filterRows(
            $rows,
            $filters['search'],
            $filters['staff_type'],
            $filters['status'],
        );

        $presentCount = $rows->count();
        $lateCount = $rows->where('status', 'Late')->count();
        $reportDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        return [
            Grid::make([
                Column::make([
                    Box::make('Daily Summary', [
                        Grid::make([
                            ValueMetric::make('Teachers & Staff')
                                ->value(fn () => $staffCount)
                                ->icon('user-group')
                                ->columnSpan(3, 6),
                            ValueMetric::make('Attendance Entries')
                                ->value(fn () => $presentCount)
                                ->icon('clipboard-document-check')
                                ->columnSpan(3, 6),
                            ValueMetric::make('Late')
                                ->value(fn () => $lateCount)
                                ->icon('clock')
                                ->columnSpan(3, 6),
                            ValueMetric::make('Missing Entries')
                                ->value(fn () => max(0, ($staffCount * $reportDays) - $presentCount))
                                ->icon('exclamation-triangle')
                                ->columnSpan(3, 6),
                        ], gap: 2),
                    ]),
                ], colSpan: 12),
            ]),
            Box::make(
                sprintf(
                    'Staff attendance from %s to %s',
                    Carbon::parse($startDate)->format('F j, Y'),
                    Carbon::parse($endDate)->format('F j, Y'),
                ),
                [
                    FlexibleRender::make(view('admin.staff-attendance-dashboard', [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'filters' => $filters,
                    ])),
                    $this->attendanceTable($filteredRows),
                ],
            ),
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function attendanceTable(Collection $rows): TableBuilder
    {
        $items = $rows->map(static fn (object $row): array => [
            'attendance_date' => Carbon::parse($row->attendance_date)->format('M j, Y'),
            'name' => $row->name,
            'staff_type' => $row->staff_type === Adviser::TYPE_TEACHER ? 'Teacher' : 'Staff',
            'position' => collect([$row->rank, $row->major])->filter()->implode(' · ') ?: '—',
            'shift' => self::timeRange($row->shift_start_time, $row->shift_end_time),
            'first_scan' => self::formatTime($row->first_scan),
            'last_scan' => self::formatTime($row->last_scan),
            'total_time' => $row->total_duration,
            'status' => $row->status.($row->late_minutes > 0 ? ' ('.$row->late_duration.')' : ''),
        ]);
        [$pageItems, $paginator] = MoonShineTablePagination::make($items, 'staff_page');

        return TableBuilder::make([
            Text::make('Date', 'attendance_date'),
            Text::make('Staff member', 'name'),
            Text::make('Type', 'staff_type'),
            Text::make('Position / Department', 'position'),
            Text::make('Shift', 'shift'),
            Text::make('First scan', 'first_scan'),
            Text::make('Last scan', 'last_scan'),
            Text::make('Total time', 'total_time'),
            Text::make('Status', 'status')->badge(
                static fn (mixed $value): Color => str_starts_with((string) $value, 'Late')
                    ? Color::WARNING
                    : Color::SUCCESS,
            ),
        ], $pageItems)
            ->withoutKey()
            ->withNotFound()
            ->paginator($paginator)
            ->headAttributes(['class' => 'whitespace-nowrap'])
            ->tdAttributes(static fn (): array => ['class' => 'px-4 py-4 align-middle']);
    }

    private static function formatTime(?string $time): string
    {
        return $time ? Carbon::parse($time)->format('h:i A') : '—';
    }

    private static function timeRange(?string $start, ?string $end): string
    {
        if (! $start && ! $end) {
            return '—';
        }

        return self::formatTime($start).' – '.self::formatTime($end);
    }
}
