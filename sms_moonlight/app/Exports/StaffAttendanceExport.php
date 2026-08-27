<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Adviser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffAttendanceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  Collection<int, object>  $rows
     */
    public function __construct(public readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Date',
            'Staff Member',
            'Type',
            'Position / Department',
            'Shift Start',
            'Shift End',
            'First Scan',
            'Last Scan',
            'Total Time',
            'Status',
            'Late By',
        ];
    }

    /**
     * @return list<string>
     */
    public function map($row): array
    {
        return [
            Carbon::parse($row->attendance_date)->format('Y-m-d'),
            $row->name,
            $row->staff_type === Adviser::TYPE_TEACHER ? 'Teacher' : 'Staff',
            collect([$row->rank, $row->major])->filter()->implode(' - '),
            $this->formatTime($row->shift_start_time),
            $this->formatTime($row->shift_end_time),
            $this->formatTime($row->first_scan),
            $this->formatTime($row->last_scan),
            $row->total_duration,
            $row->status,
            $row->late_minutes > 0 ? $row->late_duration : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        $sheet->freezePane('A2');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function formatTime(?string $time): string
    {
        return $time ? Carbon::parse($time)->format('h:i A') : '';
    }
}
