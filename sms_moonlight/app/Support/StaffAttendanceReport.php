<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Adviser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StaffAttendanceReport
{
    /**
     * @return Collection<int, object>
     */
    public function rows(string $startDate, string $endDate): Collection
    {
        return DB::table('attendance_record')
            ->join('advisers', 'advisers.id', '=', 'attendance_record.adviser_id')
            ->whereIn('advisers.staff_type', $this->personnelTypes())
            ->whereDate('attendance_record.currentdate', '>=', $startDate)
            ->whereDate('attendance_record.currentdate', '<=', $endDate)
            ->groupBy(
                'attendance_record.currentdate',
                'advisers.id',
                'advisers.name',
                'advisers.rank',
                'advisers.major',
                'advisers.staff_type',
                'advisers.shift_start_time',
                'advisers.shift_end_time',
            )
            ->orderByDesc('attendance_record.currentdate')
            ->orderBy('advisers.name')
            ->select([
                'attendance_record.currentdate as attendance_date',
                'advisers.id',
                'advisers.name',
                'advisers.rank',
                'advisers.major',
                'advisers.staff_type',
                'advisers.shift_start_time',
                'advisers.shift_end_time',
            ])
            ->selectRaw('MIN(attendance_record.logged_time) as first_scan')
            ->selectRaw('MAX(attendance_record.logged_time) as last_scan')
            ->get()
            ->map(function (object $row): object {
                $row->status = 'On time';
                $row->late_minutes = 0;
                $row->total_minutes = 0;

                if ($row->first_scan && $row->last_scan) {
                    $firstScan = $this->parseTime($row->first_scan);
                    $lastScan = $this->parseTime($row->last_scan);

                    if ($firstScan && $lastScan) {
                        $row->total_minutes = (int) round(
                            $firstScan->diffInMinutes($lastScan, true)
                        );
                    }
                }

                if ($row->shift_start_time && $row->first_scan) {
                    $shiftStart = $this->parseTime($row->shift_start_time);
                    $firstScan = $this->parseTime($row->first_scan);

                    if ($shiftStart && $firstScan && $firstScan->gt($shiftStart)) {
                        $row->status = 'Late';

                        $row->late_minutes = (int) round(
                            $shiftStart->diffInMinutes($firstScan)
                        );
                    }
                }

                $row->late_duration = self::formatDuration($row->late_minutes);
                $row->total_duration = self::formatDuration($row->total_minutes);

                return $row;
            });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    public function filterRows(
        Collection $rows,
        ?string $search = null,
        ?string $staffType = null,
        ?string $status = null,
    ): Collection {
        $search = mb_strtolower(trim((string) $search));

        return $rows
            ->when($search !== '', static function (Collection $rows) use ($search): Collection {
                return $rows->filter(static function (object $row) use ($search): bool {
                    $haystack = mb_strtolower(implode(' ', [
                        $row->name,
                        $row->rank,
                        $row->major,
                    ]));

                    return str_contains($haystack, $search);
                });
            })
            ->when(
                in_array($staffType, $this->personnelTypes(), true),
                static fn (Collection $rows): Collection => $rows->where('staff_type', $staffType),
            )
            ->when(
                in_array($status, ['On time', 'Late'], true),
                static fn (Collection $rows): Collection => $rows->where('status', $status),
            )
            ->values();
    }

    /**
     * Parse a time value that may be stored as either:
     *
     * HH:MM
     * HH:MM:SS
     */
    private function parseTime(?string $time): ?Carbon
    {
        if (blank($time)) {
            return null;
        }

        $time = trim($time);

        // Database may contain HH:MM.
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            $time .= ':00';
        }

        try {
            return Carbon::createFromFormat('H:i:s', $time);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function range(?string $startDate, ?string $endDate): array
    {
        $today = now()->toDateString();

        $startDate = $this->validDate((string) $startDate) ?? $today;
        $endDate = $this->validDate((string) $endDate) ?? $today;

        if ($endDate < $startDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    public static function formatDuration(int $minutes): string
    {
        $hours = intdiv(max(0, $minutes), 60);
        $remainingMinutes = max(0, $minutes) % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' '.($hours === 1 ? 'hr' : 'hrs');
        }

        if ($remainingMinutes > 0 || $hours === 0) {
            $parts[] = $remainingMinutes.' min';
        }

        return implode(' ', $parts);
    }

    /**
     * @return list<string>
     */
    public function personnelTypes(): array
    {
        return filter_var(
            config('school_portal.features.staff_module', true),
            FILTER_VALIDATE_BOOLEAN,
        )
            ? [Adviser::TYPE_TEACHER, Adviser::TYPE_STAFF]
            : [Adviser::TYPE_TEACHER];
    }

    private function validDate(string $value): ?string
    {
        try {
            return Carbon::createFromFormat(
                'Y-m-d',
                trim($value)
            )->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
