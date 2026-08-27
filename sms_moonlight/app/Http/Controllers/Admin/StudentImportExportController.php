<?php

namespace App\Http\Controllers\Admin;

use App\Imports\StudentImport;
use App\Models\Adviser;
use App\Models\Student;
use App\Services\Exports\StudentGradesPdfExporter;
use App\Services\Exports\StudentWorkbookExporter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportExportController extends Controller
{
    public function __construct(
        private StudentWorkbookExporter $exporter,
        private StudentGradesPdfExporter $gradesExporter
    ) {}

    public function showImportForm()
    {
        return view('admin.students.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:20480',
            ],
        ]);

        try {

            $import = new StudentImport;

            Excel::import(
                $import,
                $request->file('file')
            );

            return back()->with(
                'success',
                "Import completed. {$import->created} students created, {$import->updated} students updated."
            );

        } catch (\Throwable $e) {

            report($e);

            return back()
                ->withErrors([
                    'file' => $e->getMessage(),
                ])
                ->withInput();
        }
    }

    public function export(Request $request)
    {
        $classId = $request->integer('class_id');
        $students = Student::query()
            ->active()
            ->when($classId > 0, fn ($query) => $query->whereHas(
                'classStudents',
                fn ($classStudents) => $classStudents->where('class_id', $classId)
            ))
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        return $this->exporter->downloadProfiles(
            $students,
            $classId > 0 ? "class-{$classId}-students.xlsx" : 'students.xlsx'
        );
    }

    public function exportQr()
    {
        abort_unless(\App\Models\Setting::enabled('qr_code_enabled', true), 404);

        return $this->exporter->downloadQrCodes(
            Student::query()
                ->active()
                ->orderBy('lastname')
                ->orderBy('firstname')
                ->get(),
            'student-qr-codes.pdf'
        );
    }

    public function exportArchived(Request $request)
    {
        $filters = (array) $request->query('filter', []);
        $search = trim((string) $request->query('search', ''));
        $archiveDate = (array) ($filters['archive_date'] ?? []);

        $students = Student::query()
            ->archived()
            ->when($search !== '', static function ($query) use ($search): void {
                $query->where(static function ($query) use ($search): void {
                    $query->where('lrn', 'like', "%{$search}%")
                        ->orWhere('firstname', 'like', "%{$search}%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['class_id'] ?? null), fn ($query) => $query->whereHas(
                'classStudents',
                fn ($classStudents) => $classStudents->where('class_id', $filters['class_id']),
            ))
            ->when(filled($filters['gender'] ?? null), fn ($query) => $query->where('gender', $filters['gender']))
            ->when(filled($archiveDate['from'] ?? null), fn ($query) => $query->whereDate('archive_date', '>=', $archiveDate['from']))
            ->when(filled($archiveDate['to'] ?? null), fn ($query) => $query->whereDate('archive_date', '<=', $archiveDate['to']))
            ->orderByDesc('archive_date')
            ->orderBy('lastname')
            ->get();

        return $this->exporter->downloadProfiles(
            $students,
            'archived-students-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function exportAdvisers(): StreamedResponse
    {
        $filename = 'advisers-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'ID',
                'Username',
                'Name',
                'Rank',
                'Major',
            ]);

            Adviser::query()
                ->teachers()
                ->with('user:id,username')
                ->orderBy('name')
                ->orderBy('id')
                ->chunk(500, function ($advisers) use ($output): void {
                    foreach ($advisers as $adviser) {
                        fputcsv($output, [
                            $adviser->id,
                            $adviser->user?->username,
                            $adviser->name,
                            $adviser->rank,
                            $adviser->major,
                        ]);
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportStaff(): StreamedResponse
    {
        $filename = 'staff-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'ID',
                'RFID Card UID',
                'Name',
                'Position / Rank',
                'Department / Office',
                'Shift Start',
                'Shift End',
            ]);

            Adviser::query()
                ->staff()
                ->orderBy('name')
                ->orderBy('id')
                ->chunk(500, function ($staffMembers) use ($output): void {
                    foreach ($staffMembers as $staffMember) {
                        fputcsv($output, [
                            $staffMember->id,
                            $staffMember->rfid_card_uid,
                            $staffMember->name,
                            $staffMember->rank,
                            $staffMember->major,
                            $staffMember->shift_start_time,
                            $staffMember->shift_end_time,
                        ]);
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadGrades(Request $request, Student $student): \Symfony\Component\HttpFoundation\Response
    {
        $classStudentId = $request->integer('class_student_id');
        $classStudents = $student->classStudents()
            ->with(['student', 'class.classSubjects.subject', 'grades.subject'])
            ->when($classStudentId > 0, fn ($query) => $query->whereKey($classStudentId))
            ->orderByDesc('school_year_id')
            ->orderByDesc('id')
            ->get();

        abort_if($classStudents->isEmpty(), 404);

        $studentName = (string) str($student->lastname.'-'.$student->firstname)->slug();
        $scope = $classStudentId > 0 ? '-class-'.$classStudentId : '-all-classes';

        return $this->gradesExporter->download(
            $classStudents,
            'student-grades-'.($studentName ?: $student->id).$scope.'-'.now()->format('Ymd-His').'.pdf'
        );
    }
}
