<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Services\Exports\StudentWorkbookExporter;
use Tests\TestCase;

class StudentQrPdfExporterTest extends TestCase
{
    public function test_qr_export_downloads_as_a_pdf(): void
    {
        $students = collect([
            new Student([
                'id' => 101,
                'lrn' => '123456789012',
                'lastname' => 'Dela Cruz',
                'firstname' => 'Juan',
                'middlename' => 'Santos',
            ]),
        ]);

        $response = app(StudentWorkbookExporter::class)->downloadQrCodes(
            $students,
            'student-qr-codes.pdf'
        );

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'student-qr-codes.pdf',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }
}
