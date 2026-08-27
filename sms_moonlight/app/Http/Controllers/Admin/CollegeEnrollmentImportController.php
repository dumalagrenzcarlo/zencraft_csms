<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Imports\CollegeEnrollmentImport;
use App\Models\CollegeProgram;
use App\Models\SchoolYear;
use App\Support\CollegeEnrollmentCourseAssigner;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use MoonShine\Support\Enums\ToastType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollegeEnrollmentImportController extends Controller
{
    public function import(Request $request, CollegeEnrollmentCourseAssigner $courseAssigner)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        $import = new CollegeEnrollmentImport($courseAssigner);
        Excel::import($import, $request->file('file'));

        $message = "Import completed. Rows processed: {$import->totalRows}. Created: {$import->created}. Existing enrolments updated: {$import->updated}. Error rows: 0. Duplicate rows in file: 0.";
        toast($message, ToastType::SUCCESS);

        return back()->with('success', $message);
    }

    public function template(): StreamedResponse
    {
        $activeSchoolYear = SchoolYear::query()->where('active', true)->first();
        $programCodes = CollegeProgram::query()->where('active', true)->orderBy('code')->pluck('code')->all();
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('College Enrolments');
        $sheet->fromArray([
            ['LRN', 'Student Name', 'Course Code', 'School Year', 'Semester', 'Year Level', 'Status'],
        ]);
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:G1');
        $sheet->getStyle('A2')->getNumberFormat()->setFormatCode('@');

        foreach (['A' => 18, 'B' => 30, 'C' => 18, 'D' => 18, 'E' => 20, 'F' => 14, 'G' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $this->addListValidation($sheet, 'E2', '"First Semester,Second Semester"');
        $this->addListValidation($sheet, 'G2', '"Enrolled,Pending,Completed,Withdrawn"');

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instructions');
        $instructions->fromArray([
            ['Field', 'Expected value'],
            ['LRN', 'Required. Must exactly match an existing student LRN. Format as text to preserve leading zeroes.'],
            ['Student Name', 'Optional reference only. The system identifies the student using LRN.'],
            ['Course Code', 'Required. Must match an active college course code: '.(implode(', ', $programCodes) ?: 'None configured').'.'],
            ['School Year', 'Display only. The system always uses the active school year: '.($activeSchoolYear?->school_year ?? 'None configured').'.'],
            ['Semester', 'Required. First Semester or Second Semester (1 and 2 are also accepted).'],
            ['Year Level', 'Required. Whole number from 1 up to the selected course duration.'],
            ['Status', 'Controls whether the enrolment is active. Enrolled: active in the student portal and assigned to matching classes. Pending: recorded but not active and classes are not assigned yet. Completed: retained as finished academic history. Withdrawn: retained as history but marks that the student left the course.'],
        ]);
        $instructions->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $instructions->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $instructions->getColumnDimension('A')->setWidth(22);
        $instructions->getColumnDimension('B')->setWidth(100);
        $instructions->getStyle('A1:B8')->getAlignment()->setWrapText(true)->setVertical('top');
        $instructions->getRowDimension(2)->setRowHeight(32);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'college-enrolment-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function addListValidation($sheet, string $range, string $formula): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(false)
            ->setShowErrorMessage(true)
            ->setShowDropDown(true)
            ->setFormula1($formula);
        $sheet->setDataValidation($range, $validation);
    }
}
