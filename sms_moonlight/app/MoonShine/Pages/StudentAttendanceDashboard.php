<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Support\MoonShineTablePagination;
use App\Support\StaffAttendanceReport;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
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
class StudentAttendanceDashboard extends Page
{
    public function getTitle(): string
    {
        return 'Student Attendance Dashboard';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): array
    {
        [$startDate, $endDate] = $this->reportRange();
        $activeSchoolYearId = DB::table('school_year')
            ->where('active', true)
            ->value('id');

        $studentCount = $activeSchoolYearId
            ? DB::table('class_students')
                ->where('school_year_id', $activeSchoolYearId)
                ->distinct('student_id')
                ->count('student_id')
            : DB::table('students')
                ->where(fn ($query) => $query->whereNull('archived')->orWhere('archived', false))
                ->count();

        $rowsQuery = DB::table('attendance_record')
            ->join('students', 'students.id', '=', 'attendance_record.student_id')
            ->leftJoin('class_students', function (JoinClause $join) use ($activeSchoolYearId): void {
                $join->on('class_students.student_id', '=', 'students.id');

                if ($activeSchoolYearId) {
                    $join->where('class_students.school_year_id', $activeSchoolYearId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('classes', 'classes.id', '=', 'class_students.class_id')
            ->whereNotNull('attendance_record.student_id')
            ->whereDate('attendance_record.currentdate', '>=', $startDate)
            ->whereDate('attendance_record.currentdate', '<=', $endDate)
            ->groupBy(
                'attendance_record.currentdate',
                'students.id',
                'students.lrn',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.start_time',
            )
            ->orderByDesc('attendance_record.currentdate')
            ->orderBy('students.lastname')
            ->orderBy('students.firstname')
            ->select([
                'attendance_record.currentdate as attendance_date',
                'students.id',
                'students.lrn',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'classes.start_time',
            ])
            ->selectRaw('MIN(attendance_record.logged_time) as first_scan')
            ->selectRaw('MAX(attendance_record.logged_time) as last_scan');

        $rows = $rowsQuery->get()->map(function (object $row): object {
            $row->status = 'On time';
            $row->late_minutes = 0;
            $row->total_minutes = 0;

            if ($row->first_scan && $row->last_scan) {
                $row->total_minutes = (int) round(
                    Carbon::parse($row->first_scan)->diffInMinutes(Carbon::parse($row->last_scan), true)
                );
            }

            if ($row->start_time && $row->first_scan && $row->first_scan > $row->start_time) {
                $row->status = 'Late';
                $row->late_minutes = Carbon::createFromFormat('H:i:s', $row->start_time)
                    ->diffInMinutes(Carbon::createFromFormat('H:i:s', $row->first_scan));
            }

            $row->late_duration = StaffAttendanceReport::formatDuration((int) round($row->late_minutes));
            $row->total_duration = StaffAttendanceReport::formatDuration($row->total_minutes);

            return $row;
        });

        $filters = [
            'search' => trim((string) request()->query('search')),
            'status' => (string) request()->query('status'),
        ];
        $filteredRows = $this->filterRows($rows, $filters['search'], $filters['status']);

        $attendanceEntries = $rows->count();
        $lateCount = $rows->where('status', 'Late')->count();
        $reportDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        return [
            Grid::make([
                Column::make([
                    Box::make('Attendance Summary', [
                        Grid::make([
                            ValueMetric::make('Enrolled Students')
                                ->value(fn () => $studentCount)
                                ->icon('users')
                                ->columnSpan(3, 6),
                            ValueMetric::make('Attendance Entries')
                                ->value(fn () => $attendanceEntries)
                                ->icon('clipboard-document-check')
                                ->columnSpan(3, 6),
                            ValueMetric::make('Late')
                                ->value(fn () => $lateCount)
                                ->icon('clock')
                                ->columnSpan(3, 6),
                            ValueMetric::make('Missing Entries')
                                ->value(fn () => max(0, ($studentCount * $reportDays) - $attendanceEntries))
                                ->icon('exclamation-triangle')
                                ->columnSpan(3, 6),
                        ], gap: 2),
                    ]),
                ], colSpan: 12),
            ]),
            Box::make(
                sprintf(
                    'Student attendance from %s to %s',
                    Carbon::parse($startDate)->format('F j, Y'),
                    Carbon::parse($endDate)->format('F j, Y'),
                ),
                [
                    FlexibleRender::make(view('admin.student-attendance-dashboard', [
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
     * @return Collection<int, object>
     */
    private function filterRows(Collection $rows, string $search, string $status): Collection
    {
        $search = mb_strtolower($search);

        return $rows
            ->when($search !== '', static function (Collection $rows) use ($search): Collection {
                return $rows->filter(static function (object $row) use ($search): bool {
                    $name = trim($row->firstname.' '.$row->middlename.' '.$row->lastname);

                    return str_contains(mb_strtolower($row->lrn.' '.$name), $search);
                });
            })
            ->when(
                in_array($status, ['On time', 'Late'], true),
                static fn (Collection $rows): Collection => $rows->where('status', $status),
            )
            ->values();
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function attendanceTable(Collection $rows): TableBuilder
    {
        $items = $rows->map(static fn (object $row): array => [
            'attendance_date' => Carbon::parse($row->attendance_date)->format('M j, Y'),
            'lrn' => $row->lrn,
            'name' => trim($row->firstname.' '.$row->middlename.' '.$row->lastname),
            'class_start' => self::formatTime($row->start_time),
            'first_scan' => self::formatTime($row->first_scan),
            'last_scan' => self::formatTime($row->last_scan),
            'total_time' => $row->total_duration,
            'status' => $row->status.($row->late_minutes > 0 ? ' ('.$row->late_duration.')' : ''),
        ]);
        [$pageItems, $paginator] = MoonShineTablePagination::make($items, 'student_page');

        return TableBuilder::make([
            Text::make('Date', 'attendance_date'),
            Text::make('Student Number', 'lrn'),
            Text::make('Student', 'name'),
            Text::make('Class Start', 'class_start'),
            Text::make('First Scan', 'first_scan'),
            Text::make('Last Scan', 'last_scan'),
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

    /**
     * @return array{0: string, 1: string}
     */
    private function reportRange(): array
    {
        $today = now()->toDateString();
        $startDate = $this->validDate((string) request()->query('start_date', '')) ?? $today;
        $endDate = $this->validDate((string) request()->query('end_date', '')) ?? $today;

        if ($endDate < $startDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    private function validDate(string $value): ?string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', trim($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
