<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Imports\StudentImport;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use ReflectionMethod;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    public function test_non_student_worksheet_is_ignored(): void
    {
        $import = new StudentImport;

        $import->collection(new Collection([
            new Collection([
                'LRN',
                'Student',
                'Source Sheet',
                'Field',
                'Issue / action taken',
            ]),
            new Collection([
                '108226190071',
                'ALMOSARA, GINO',
                'BIGNOTEA MALE',
                'Birthday',
                'missing / unreadable in source (-) — needs manual entry',
            ]),
        ]));

        $this->assertSame(0, $import->created);
        $this->assertSame(0, $import->updated);
    }

    public function test_excel_serial_birthday_is_converted_to_the_calendar_date(): void
    {
        $excelSerial = SpreadsheetDate::dateTimeToExcel(
            new \DateTimeImmutable('2010-07-12')
        );

        $this->assertSame('2010-07-12', $this->parseBirthday($excelSerial));
    }

    public function test_formatted_birthday_remains_supported(): void
    {
        $this->assertSame('2010-07-12', $this->parseBirthday('2010-07-12'));
        $this->assertNull($this->parseBirthday(''));
    }

    private function parseBirthday(mixed $value): ?string
    {
        $method = new ReflectionMethod(StudentImport::class, 'parseBirthday');

        return $method->invoke(new StudentImport, $value);
    }
}
