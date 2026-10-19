<?php

namespace App\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Writes the realisation report of a month as an Excel workbook laid out as
 * Form I (D-068): the title, the merged header and target cells, thin
 * borders and rupiah and percent formats, on A4 landscape. The workbook is
 * put together by hand with ZipArchive, so no spreadsheet package is needed.
 */
class WriteRevenueRealizationWorkbook
{
    /**
     * The title lines above the table; the month and year follow.
     *
     * @var list<string>
     */
    public const array TITLE = [
        'REALISASI PENERIMAAN PAJAK DAN PENERIMAAN LAIN-LAIN YANG SAH',
        'UPTD. PENDAPATAN DAERAH WILAYAH KOTA KUPANG PADA',
        'BADAN PENDAPATAN DAN ASET DAERAH PROVINSI NUSA TENGGARA TIMUR',
    ];

    /**
     * The first row of the table's figures.
     */
    private const int FIRST_ROW = 11;

    /**
     * The cell styles, by name, in the order of styles.xml.
     *
     * @var array<string, int>
     */
    private const array STYLES = [
        'title' => 1, 'header' => 2, 'label' => 3, 'form' => 4, 'number' => 5, 'item' => 6, 'sum' => 7, 'rupiah' => 8, 'percent' => 9,
        'boldLabel' => 10, 'boldRupiah' => 11, 'boldPercent' => 12, 'empty' => 13,
    ];

    /**
     * Write the report to a temporary file and give its path; the caller
     * deletes it once sent.
     *
     * @param  array{tahun: int, bulan: int, baris: list<array<string, mixed>>}  $report
     */
    public function handle(array $report): string
    {
        $path = tempnam(sys_get_temp_dir(), 'realisasi');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The realisation workbook could not be created.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->packageRelationships());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($report));
        $zip->close();

        return $path;
    }

    /**
     * The worksheet: title, header, the column numbers and a row per row of
     * the report.
     *
     * @param  array{tahun: int, bulan: int, baris: list<array<string, mixed>>}  $report
     */
    private function sheet(array $report): string
    {
        $month = Str::upper(Carbon::create($report['tahun'], $report['bulan'])->locale('id')->translatedFormat('F'));
        $rows = [];
        $merges = [];

        foreach ([...self::TITLE, "BULAN {$month}", "TAHUN {$report['tahun']}"] as $index => $line) {
            $rows[$index + 1] = [$this->text('A', $index + 1, $line, 'title')];
            $merges[] = 'A'.($index + 1).':G'.($index + 1);
        }

        $rows[7] = [$this->text('A', 7, 'GABUNGAN', 'label'), $this->text('G', 7, 'Form. I', 'form')];

        $rows[8] = [$this->text('A', 8, 'NO.', 'header'), $this->text('B', 8, 'JENIS PENERIMAAN', 'header'), $this->text('C', 8, 'TARGET (Rp)', 'header'),
            $this->text('D', 8, 'REALISASI PENERIMAAN GABUNGAN', 'header'), $this->blank('E', 8, 'header'), $this->blank('F', 8, 'header'),
            $this->text('G', 8, 'PCT', 'header')];
        $rows[9] = [$this->blank('A', 9, 'header'), $this->blank('B', 9, 'header'), $this->blank('C', 9, 'header'),
            $this->text('D', 9, 'S/D BULAN LALU (Rp)', 'header'), $this->text('E', 9, 'BULAN INI (Rp)', 'header'),
            $this->text('F', 9, 'S/D BULAN INI (Rp)', 'header'), $this->blank('G', 9, 'header')];
        array_push($merges, 'A8:A9', 'B8:B9', 'C8:C9', 'D8:F8', 'G8:G9');

        $rows[10] = array_map(fn (string $column, int $number): string => $this->value($column, 10, $number, 'number'), range('A', 'G'), range(1, 7));

        foreach ($report['baris'] as $index => $line) {
            $row = self::FIRST_ROW + $index;
            $rows[$row] = $this->line($row, $line);

            if ($line['baris'] > 1) {
                $end = $row + $line['baris'] - 1;
                array_push($merges, "A{$row}:A{$end}", "C{$row}:C{$end}");
            } elseif (in_array($line['jenis'], ['jumlah', 'total'], true)) {
                $merges[] = "A{$row}:B{$row}";
            }
        }

        $heights = [9 => 30];
        $sheetData = '';
        foreach ($rows as $number => $cells) {
            $height = isset($heights[$number]) ? " ht=\"{$heights[$number]}\" customHeight=\"1\"" : '';
            $sheetData .= "<row r=\"{$number}\"{$height}>".implode('', $cells).'</row>';
        }

        $columns = '';
        foreach ([6, 34, 20, 20, 18, 20, 9] as $index => $width) {
            $column = $index + 1;
            $columns .= "<col min=\"{$column}\" max=\"{$column}\" width=\"{$width}\" customWidth=\"1\"/>";
        }

        $mergeCells = implode('', array_map(fn (string $range): string => "<mergeCell ref=\"{$range}\"/>", $merges));

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            ."<cols>{$columns}</cols>"
            ."<sheetData>{$sheetData}</sheetData>"
            .'<mergeCells count="'.count($merges)."\">{$mergeCells}</mergeCells>"
            .'<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
            .'<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .'</worksheet>';
    }

    /**
     * The cells of a report row. Cells covered by a merge keep their borders.
     *
     * @param  array<string, mixed>  $line
     * @return list<string>
     */
    private function line(int $row, array $line): array
    {
        $bold = in_array($line['jenis'], ['jumlah', 'total'], true);
        $rupiah = $bold ? 'boldRupiah' : 'rupiah';

        if ($bold) {
            $cells = [$this->text('A', $row, $line['jenis'] === 'total' ? 'T O T A L' : $line['uraian'], 'boldLabel'), $this->blank('B', $row, 'boldLabel')];
        } else {
            $cells = [
                $line['no'] === null ? $this->blank('A', $row, 'number') : $this->text('A', $row, $line['no'], 'number'),
                $this->text('B', $row, $line['uraian'], $line['jenis'] === 'subjumlah' ? 'sum' : 'item'),
            ];
        }

        $cells[] = $line['target'] === null ? $this->blank('C', $row, $rupiah) : $this->value('C', $row, $line['target'], $rupiah);
        $cells[] = $this->value('D', $row, $line['lalu'], $rupiah);
        $cells[] = $this->value('E', $row, $line['ini'], $rupiah);
        $cells[] = $this->value('F', $row, $line['sampaiIni'], $rupiah);
        $cells[] = $line['persen'] === null
            ? $this->blank('G', $row, $bold ? 'boldPercent' : 'percent')
            : $this->value('G', $row, $line['persen'] / 100, $bold ? 'boldPercent' : 'percent');

        return $cells;
    }

    private function text(string $column, int $row, string $text, string $style): string
    {
        $text = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "<c r=\"{$column}{$row}\" s=\"".self::STYLES[$style]."\" t=\"inlineStr\"><is><t xml:space=\"preserve\">{$text}</t></is></c>";
    }

    private function value(string $column, int $row, int|float $value, string $style): string
    {
        return "<c r=\"{$column}{$row}\" s=\"".self::STYLES[$style]."\"><v>{$value}</v></c>";
    }

    private function blank(string $column, int $row, string $style): string
    {
        return "<c r=\"{$column}{$row}\" s=\"".self::STYLES[$style].'"/>';
    }

    /**
     * The fonts, borders, number formats and cell styles named in STYLES.
     */
    private function styles(): string
    {
        $border = 'borderId="1" applyBorder="1"';
        $center = '<alignment horizontal="center" vertical="center" wrapText="1"/>';
        $middle = '<alignment vertical="center"/>';
        $xfs = [
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>',
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>',
            "<xf numFmtId=\"0\" fontId=\"1\" fillId=\"0\" {$border} xfId=\"0\" applyFont=\"1\" applyAlignment=\"1\">{$center}</xf>",
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>',
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="right"/></xf>',
            "<xf numFmtId=\"0\" fontId=\"0\" fillId=\"0\" {$border} xfId=\"0\" applyAlignment=\"1\">{$center}</xf>",
            "<xf numFmtId=\"0\" fontId=\"0\" fillId=\"0\" {$border} xfId=\"0\" applyAlignment=\"1\"><alignment vertical=\"center\" indent=\"1\"/></xf>",
            "<xf numFmtId=\"0\" fontId=\"0\" fillId=\"0\" {$border} xfId=\"0\" applyAlignment=\"1\"><alignment vertical=\"center\" indent=\"3\"/></xf>",
            "<xf numFmtId=\"164\" fontId=\"0\" fillId=\"0\" {$border} xfId=\"0\" applyNumberFormat=\"1\" applyAlignment=\"1\">{$middle}</xf>",
            "<xf numFmtId=\"165\" fontId=\"0\" fillId=\"0\" {$border} xfId=\"0\" applyNumberFormat=\"1\" applyAlignment=\"1\">{$middle}</xf>",
            "<xf numFmtId=\"0\" fontId=\"1\" fillId=\"0\" {$border} xfId=\"0\" applyFont=\"1\" applyAlignment=\"1\">{$center}</xf>",
            "<xf numFmtId=\"164\" fontId=\"1\" fillId=\"0\" {$border} xfId=\"0\" applyFont=\"1\" applyNumberFormat=\"1\" applyAlignment=\"1\">{$middle}</xf>",
            "<xf numFmtId=\"165\" fontId=\"1\" fillId=\"0\" {$border} xfId=\"0\" applyFont=\"1\" applyNumberFormat=\"1\" applyAlignment=\"1\">{$middle}</xf>",
            "<xf numFmtId=\"0\" fontId=\"0\" fillId=\"0\" {$border} xfId=\"0\"/>",
        ];

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0;-#,##0;&quot;-&quot;"/><numFmt numFmtId="165" formatCode="0.00%"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/><family val="2"/></font><font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="'.count($xfs).'">'.implode('', $xfs).'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Form I" sheetId="1" r:id="rId1"/></sheets>'
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

    private function packageRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }
}
