<?php

declare(strict_types=1);

namespace App\Services\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class StudentGradesPdfExporter
{
    public function download(Collection $classStudents, string $downloadName): Response
    {
        $pdf = Pdf::loadView('exports.student-grades', [
            'classStudents' => $classStudents->values(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->download($downloadName);
    }
}
