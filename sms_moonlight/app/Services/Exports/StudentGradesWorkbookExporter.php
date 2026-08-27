<?php

declare(strict_types=1);

namespace App\Services\Exports;

use App\Models\ClassStudent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class StudentGradesWorkbookExporter
{
    /**
     * @param  Collection<int, ClassStudent>  $classStudents
     */
    public function download(Collection $classStudents, string $downloadName): BinaryFileResponse
    {
        $path = $this->buildWorkbook($classStudents);

        return response()
            ->download($path, $downloadName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  Collection<int, ClassStudent>  $classStudents
     */
    private function buildWorkbook(Collection $classStudents): string
    {
        $sheets = $classStudents->isNotEmpty()
            ? $classStudents->values()->map(fn (ClassStudent $classStudent): array => $this->buildStudentSheet($classStudent))->all()
            : [$this->buildEmptySheet()];

        $path = tempnam(sys_get_temp_dir(), 'student_grades_');

        if ($path === false) {
            throw new \RuntimeException('Unable to create temporary file.');
        }

        $xlsxPath = $path.'.xlsx';
        @unlink($path);

        $zip = new ZipArchive;

        if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create XLSX archive.');
        }

        $sheetCount = count($sheets);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes($sheetCount));
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('docProps/app.xml', $this->appProps($sheets));
        $zip->addFromString('docProps/core.xml', $this->coreProps());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships($sheetCount));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        foreach ($sheets as $index => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet'.($index + 1).'.xml',
                $this->sheetXml($sheet)
            );
        }

        $zip->close();

        return $xlsxPath;
    }

    private function buildStudentSheet(ClassStudent $classStudent): array
    {
        $classStudent->loadMissing([
            'student',
            'class.grade',
            'class.classSubjects.subject',
        ]);

        $termKeys = $classStudent->termKeys();
        $grades = \App\Models\ClassStudentGrade::query()
            ->with('subject')
            ->where('class_id', $classStudent->class_id)
            ->where('student_id', $classStudent->student_id)
            ->get();

        $classSubjects = collect($classStudent->class?->classSubjects ?? [])->values();

        $rows = $classSubjects
            ->map(function ($classSubject) use ($grades, $termKeys): array {
                $grade = $grades->firstWhere('subject_id', $classSubject->subject_id);
                $includedInAverage = (bool) ($classSubject->subject?->include_in_average ?? false);
                $termValues = [];

                foreach ($termKeys as $termKey) {
                    $termValues[$termKey] = (float) ($grade->{$termKey} ?? 0);
                }

                return [
                    'subject' => strtoupper((string) ($classSubject->subject?->subject ?? '-')),
                    ...$termValues,
                    'avg' => count($termValues) > 0 ? round(array_sum($termValues) / count($termValues), 2) : 0,
                    'included_in_average' => $includedInAverage,
                ];
            })
            ->sortBy('subject')
            ->values();

        $includedRows = $rows->filter(static fn (array $row): bool => $row['included_in_average'])->values();
        $summarySource = $includedRows->isNotEmpty() ? $includedRows : collect();

        $summary = collect($termKeys)
            ->mapWithKeys(fn (string $termKey): array => [$termKey => round((float) ($summarySource->avg($termKey) ?? 0), 2)])
            ->put('avg', round((float) ($summarySource->avg('avg') ?? 0), 2))
            ->all();

        $studentName = trim(implode(', ', array_filter([
            strtoupper((string) ($classStudent->student?->lastname ?? '')),
            strtoupper((string) ($classStudent->student?->firstname ?? '')),
        ])));

        return [
            'sheetName' => $this->sheetName($classStudent, $studentName),
            'studentName' => $studentName !== '' ? $studentName : 'STUDENT',
            'lrn' => (string) ($classStudent->student?->lrn ?? ''),
            'termKeys' => $termKeys,
            'rows' => $rows->all(),
            'summary' => $summary,
        ];
    }

    private function buildEmptySheet(): array
    {
        return [
            'sheetName' => 'No Data',
            'studentName' => 'NO STUDENTS FOUND',
            'lrn' => '',
            'termKeys' => ['q1', 'q2', 'q3', 'q4'],
            'rows' => [],
            'summary' => ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'avg' => 0],
        ];
    }

    private function sheetName(ClassStudent $classStudent, string $studentName): string
    {
        $classStudent->loadMissing(['class.grade', 'schoolYear']);

        $className = collect([
            $classStudent->schoolYear?->school_year,
            $classStudent->class?->grade?->grade,
            $classStudent->class?->section,
            $studentName,
        ])->filter()->implode(' ');

        $base = Str::of($className)
            ->ascii()
            ->replaceMatches('/[\\\\\\/\\?\\*\\[\\]:]/', ' ')
            ->squish()
            ->upper()
            ->toString();

        $suffix = ' #'.$classStudent->id;
        $base = Str::limit(trim($base), 31 - strlen($suffix), '');

        return ($base !== '' ? $base : 'CLASS').$suffix;
    }

    private function contentTypes(int $sheetCount): string
    {
        $sheetOverrides = '';

        for ($i = 1; $i <= $sheetCount; $i++) {
            $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$sheetOverrides
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(array $sheets): string
    {
        $sheetNodes = '';

        foreach ($sheets as $index => $sheet) {
            $sheetName = $this->escape($sheet['sheetName']);
            $sheetNodes .= '<sheet name="'.$sheetName.'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView activeTab="0"/></bookViews>'
            .'<sheets>'.$sheetNodes.'</sheets>'
            .'</workbook>';
    }

    private function workbookRelationships(int $sheetCount): string
    {
        $relationships = '';

        for ($i = 1; $i <= $sheetCount; $i++) {
            $relationships .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        $relationships .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships
            .'</Relationships>';
    }

    private function appProps(array $sheets): string
    {
        $titles = '';
        foreach ($sheets as $sheet) {
            $titles .= '<vt:lpstr>'.$this->escape($sheet['sheetName']).'</vt:lpstr>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Microsoft Excel</Application>'
            .'<DocSecurity>0</DocSecurity>'
            .'<ScaleCrop>false</ScaleCrop>'
            .'<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>'.count($sheets).'</vt:i4></vt:variant></vt:vector></HeadingPairs>'
            .'<TitlesOfParts><vt:vector size="'.count($sheets).'" baseType="lpstr">'.$titles.'</vt:vector></TitlesOfParts>'
            .'<Company></Company>'
            .'<LinksUpToDate>false</LinksUpToDate>'
            .'<SharedDoc>false</SharedDoc>'
            .'<HyperlinksChanged>false</HyperlinksChanged>'
            .'<AppVersion>16.0300</AppVersion>'
            .'</Properties>';
    }

    private function coreProps(): string
    {
        $now = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>ZenCraft Systems</dc:creator>'
            .'<cp:lastModifiedBy>ZenCraft Systems</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="3">
        <font>
            <sz val="11"/>
            <name val="Calibri"/>
        </font>
        <font>
            <b/>
            <sz val="14"/>
            <color rgb="FFFFFFFF"/>
            <name val="Calibri"/>
        </font>
        <font>
            <b/>
            <sz val="11"/>
            <name val="Calibri"/>
        </font>
    </fonts>
    <fills count="4">
        <fill>
            <patternFill patternType="none"/>
        </fill>
        <fill>
            <patternFill patternType="gray125"/>
        </fill>
        <fill>
            <patternFill patternType="solid">
                <fgColor rgb="FF2563EB"/>
                <bgColor indexed="64"/>
            </patternFill>
        </fill>
        <fill>
            <patternFill patternType="solid">
                <fgColor rgb="FFF8FAFC"/>
                <bgColor indexed="64"/>
            </patternFill>
        </fill>
    </fills>
    <borders count="2">
        <border>
            <left/>
            <right/>
            <top/>
            <bottom/>
            <diagonal/>
        </border>
        <border>
            <left style="thin"><color rgb="FFD1D5DB"/></left>
            <right style="thin"><color rgb="FFD1D5DB"/></right>
            <top style="thin"><color rgb="FFD1D5DB"/></top>
            <bottom style="thin"><color rgb="FFD1D5DB"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="9">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
        <xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
        <xf numFmtId="2" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf numFmtId="2" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
    </cellXfs>
</styleSheet>
XML;
    }

    private function sheetXml(array $sheet): string
    {
        $rows = $sheet['rows'];
        $termKeys = $sheet['termKeys'] ?? ['q1', 'q2', 'q3', 'q4'];
        $termLabels = [
            'q1' => 'Grading Period 1',
            'q2' => 'Grading Period 2',
            'q3' => 'Grading Period 3',
            'q4' => 'Grading Period 4',
        ];
        $rowCount = count($rows);
        $columnCount = count($termKeys) + 2;
        $lastColumn = $this->columnName($columnCount);
        $lastRow = 6 + $rowCount + ($rowCount > 0 ? 1 : 0);
        $dimension = $rowCount > 0 ? 'A1:'.$lastColumn.$lastRow : 'A1:'.$lastColumn.'5';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="'.$dimension.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="20"/>'
            .'<cols>'
            .'<col min="1" max="1" width="32" customWidth="1"/>'
            .'<col min="2" max="'.$columnCount.'" width="12" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>';

        $xml .= $this->rowXml(1, [
            $this->textCell('A1', $sheet['studentName'] === 'NO STUDENTS FOUND' ? 'No student grades available' : 'Student Grades', 1),
        ], 22);

        $xml .= $this->rowXml(2, [
            $this->textCell('A2', 'Student', 2),
            $this->textCell('B2', $sheet['studentName'], 3),
        ]);

        $xml .= $this->rowXml(3, [
            $this->textCell('A3', 'Student Number', 2),
            $this->textCell('B3', $sheet['lrn'] !== '' ? $sheet['lrn'] : '-', 3),
        ]);

        $headerCells = [$this->textCell('A5', 'Subject', 4)];

        foreach ($termKeys as $index => $termKey) {
            $headerCells[] = $this->textCell($this->columnName($index + 2).'5', $termLabels[$termKey], 4);
        }

        $averageColumn = $this->columnName($columnCount);
        $headerCells[] = $this->textCell($averageColumn.'5', 'AVG', 4);

        $xml .= $this->rowXml(5, $headerCells, null, $columnCount);

        $rowIndex = 6;
        foreach ($rows as $row) {
            $cells = [
                $this->textCell('A'.$rowIndex, $row['subject'], 5),
            ];

            foreach ($termKeys as $index => $termKey) {
                $cells[] = $this->numberCell($this->columnName($index + 2).$rowIndex, (float) $row[$termKey], 6);
            }

            $cells[] = $this->numberCell($averageColumn.$rowIndex, (float) $row['avg'], 6);

            $xml .= $this->rowXml($rowIndex, $cells, null, $columnCount);
            $rowIndex++;
        }

        if ($rowCount > 0) {
            $summaryRow = $rowIndex;
            $cells = [
                $this->textCell('A'.$summaryRow, 'Average', 7),
            ];

            foreach ($termKeys as $index => $termKey) {
                $cells[] = $this->numberCell($this->columnName($index + 2).$summaryRow, (float) $sheet['summary'][$termKey], 8);
            }

            $cells[] = $this->numberCell($averageColumn.$summaryRow, (float) $sheet['summary']['avg'], 8);

            $xml .= $this->rowXml($summaryRow, $cells, null, $columnCount);
        }

        $xml .= '</sheetData>';
        $xml .= '<mergeCells count="1"><mergeCell ref="A1:'.$lastColumn.'1"/></mergeCells>';
        $xml .= '<pageMargins left="0.5" right="0.5" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    /**
     * @param  array<int, string>  $cells
     */
    private function rowXml(int $rowNumber, array $cells, ?int $height = null, int $columnCount = 6): string
    {
        $attributes = ' r="'.$rowNumber.'" spans="1:'.$columnCount.'"';

        if ($height !== null) {
            $attributes .= ' ht="'.$height.'" customHeight="1"';
        }

        return '<row'.$attributes.'>'.implode('', $cells).'</row>';
    }

    private function textCell(string $ref, string $value, int $styleId): string
    {
        return '<c r="'.$ref.'" t="inlineStr" s="'.$styleId.'"><is><t>'.$this->escape($value).'</t></is></c>';
    }

    private function numberCell(string $ref, float $value, int $styleId): string
    {
        return '<c r="'.$ref.'" t="inlineStr" s="'.$styleId.'"><is><t>'.$this->escape(number_format($value, 2, '.', '')).'</t></is></c>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
