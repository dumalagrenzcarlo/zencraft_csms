<?php

declare(strict_types=1);

namespace App\Services\Exports;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class StudentWorkbookExporter
{
    private const PROFILE_HEADERS = [
        'Student Number',
        'FirstName',
        'LastName',
        'MiddleName',
        'Gender (Male/Female)',
        'Birthday (YYYY-MM-DD)',
        'Address',
        'Birthplace',
        'Parent or Guardian',
        'Parent or Guardian Address',
        'Relationship',
        '4Ps member? (Yes/No)',
        'Weight',
        'Height',
        'Elementary School Id',
        ' Elementary School Name',
        ' Elementary School Address',
        'Elementary School Grade',
        'Elementary School Citation',
    ];

    /**
     * @param  Collection<int, Student>  $students
     */
    public function downloadProfiles(Collection $students, string $downloadName): BinaryFileResponse
    {
        $path = $this->buildWorkbook(
            'Student-Profiling-LEYNES',
            self::PROFILE_HEADERS,
            $students->map(fn (Student $student): array => $this->profileRow($student))->all()
        );

        return response()
            ->download($path, $downloadName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  Collection<int, Student>  $students
     */
    public function downloadQrCodes(Collection $students, string $downloadName): Response
    {
        $cards = $students
            ->map(fn (Student $student): array => [
                'qr' => Builder::create()
                    ->data((string) ($student->lrn ?: $student->id))
                    ->size(320)
                    ->margin(8)
                    ->build()
                    ->getDataUri(),
                'label' => $this->qrLabel($student),
            ])
            ->values();

        $pdf = Pdf::loadView('exports.student-qr-codes', [
            'pages' => $cards->chunk(6),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
            ]);

        return $pdf->download($downloadName);
    }

    public function downloadTemplate(string $downloadName): BinaryFileResponse
    {
        $path = $this->buildWorkbook(
            'Student-Profiling-LEYNES',
            self::PROFILE_HEADERS,
            []
        );

        return response()
            ->download($path, $downloadName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    private function profileRow(Student $student): array
    {
        return [
            (string) ($student->lrn ?? ''),
            (string) ($student->firstname ?? ''),
            (string) ($student->lastname ?? ''),
            (string) ($student->middlename ?? ''),
            (string) ($student->gender ?? ''),
            $student->dob ? $student->dob->format('Y-m-d') : '',
            (string) ($student->address ?? ''),
            (string) ($student->birthplace ?? ''),
            (string) ($student->parent_guardian ?? ''),
            (string) ($student->parent_guardian_address ?? ''),
            (string) ($student->parent_guardian_relationship ?? ''),
            $student->is_4ps_member ? 'Yes' : 'No',
            (string) ($student->weight ?? ''),
            (string) ($student->height ?? ''),
            (string) ($student->elementary_school_id ?? ''),
            (string) ($student->elementary_school_name ?? ''),
            (string) ($student->elementary_school_address ?? ''),
            (string) ($student->elementary_school_grade ?? ''),
            (string) ($student->elementary_school_citation ?? ''),
        ];
    }

    private function qrLabel(Student $student): string
    {
        $name = collect([
            $student->lastname ? $student->lastname.',' : null,
            $student->firstname,
            $student->middlename,
        ])->filter()->implode(' ');

        return strtoupper((string) ($student->lrn ?: $student->id).' - '.$name);
    }

    private function qrRow(Student $student): array
    {
        return [
            (string) ($student->lrn ?? ''),
            $this->fullName($student),
            (string) ($student->lrn ?: $student->id),
        ];
    }

    private function fullName(Student $student): string
    {
        return trim(implode(' ', array_filter([
            (string) ($student->firstname ?? ''),
            (string) ($student->middlename ?? ''),
            (string) ($student->lastname ?? ''),
        ])));
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function buildWorkbook(string $sheetName, array $headers, array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'students_');

        if ($path === false) {
            throw new \RuntimeException('Unable to create temporary file.');
        }

        $xlsxPath = $path.'.xlsx';
        @unlink($path);

        $zip = new ZipArchive;

        if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create XLSX archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('docProps/app.xml', $this->appProps([$sheetName]));
        $zip->addFromString('docProps/core.xml', $this->coreProps());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml($headers, $rows));

        $zip->close();

        return $xlsxPath;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
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

    /**
     * @param  array<int, string>  $sheetNames
     */
    private function appProps(array $sheetNames): string
    {
        $titles = '';

        foreach ($sheetNames as $sheetName) {
            $titles .= '<vt:lpstr>'.$this->escape($sheetName).'</vt:lpstr>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Microsoft Excel</Application>'
            .'<DocSecurity>0</DocSecurity>'
            .'<ScaleCrop>false</ScaleCrop>'
            .'<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>1</vt:i4></vt:variant></vt:vector></HeadingPairs>'
            .'<TitlesOfParts><vt:vector size="1" baseType="lpstr">'.$titles.'</vt:vector></TitlesOfParts>'
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

    private function workbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView activeTab="0"/></bookViews>'
            .'<sheets><sheet name="'.$this->escape($this->sheetName($sheetName)).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    private function sheetXml(array $headers, array $rows): string
    {
        $columnCount = max(1, count($headers));
        $lastColumn = $this->columnLetter($columnCount);
        $lastRow = max(1, count($rows) + 1);
        $dimension = 'A1:'.$lastColumn.$lastRow;

        $cols = '';
        foreach ($headers as $index => $header) {
            $width = min(max(strlen((string) $header) + 2, 14), 38);
            $column = $index + 1;
            $cols .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="'.$dimension.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="20"/>'
            .($cols !== '' ? '<cols>'.$cols.'</cols>' : '')
            .'<sheetData>';

        $xml .= '<row r="1" spans="1:'.$columnCount.'">';
        foreach ($headers as $index => $header) {
            $cellRef = $this->columnLetter($index + 1).'1';
            $xml .= $this->inlineStringCell($cellRef, (string) $header, 1);
        }
        $xml .= '</row>';

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $xml .= '<row r="'.$rowNumber.'" spans="1:'.$columnCount.'">';
            for ($column = 1; $column <= $columnCount; $column++) {
                $cellRef = $this->columnLetter($column).$rowNumber;
                $value = (string) ($row[$column - 1] ?? '');
                $xml .= $this->inlineStringCell($cellRef, $value, 0);
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>'
            .'<pageMargins left="0.5" right="0.5" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            .'</worksheet>';

        return $xml;
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font>
            <sz val="11"/>
            <name val="Calibri"/>
        </font>
        <font>
            <b/>
            <sz val="11"/>
            <color rgb="FFFFFFFF"/>
            <name val="Calibri"/>
        </font>
    </fonts>
    <fills count="3">
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
    </fills>
    <borders count="1">
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
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyBorder="1"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
    </cellXfs>
</styleSheet>
XML;
    }

    private function inlineStringCell(string $ref, string $value, int $styleId): string
    {
        return '<c r="'.$ref.'" t="inlineStr" s="'.$styleId.'"><is><t>'.$this->escape($value).'</t></is></c>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private function sheetName(string $name): string
    {
        $base = Str::of($name)
            ->ascii()
            ->replaceMatches('/[\\\\\\/\\?\\*\\[\\]:]/', ' ')
            ->squish()
            ->toString();

        return Str::limit($base !== '' ? $base : 'Students', 31, '');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
