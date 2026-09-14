<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

// Penulis file .xlsx yang ringan — gak perlu install package composer tambahan
// (maatwebsite/excel dsb), cukup pakai ext-zip bawaan PHP. Cukup buat kebutuhan
// ekspor rekap sederhana: 1 sheet, baris & kolom teks/angka, header bisa dibold.
class SimpleXlsxWriter
{
    private array $rows = [];

    private array $boldRowIndexes = [];

    public function __construct(private string $sheetName = 'Sheet1')
    {
        // Nama sheet di Excel gak boleh lebih dari 31 karakter & gak boleh ada karakter tertentu
        $this->sheetName = substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $sheetName), 0, 31) ?: 'Sheet1';
    }

    public function addRow(array $cells, bool $bold = false): static
    {
        if ($bold) {
            $this->boldRowIndexes[] = count($this->rows);
        }
        $this->rows[] = $cells;

        return $this;
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = storage_path('app/tmp-'.uniqid('xlsx_').'.xlsx');
        $this->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function save(string $path): void
    {
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());

        $zip->close();
    }

    private function colLetter(int $index): string
    {
        // 0 -> A, 1 -> B, ... 25 -> Z, 26 -> AA, dst.
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function sheetXml(): string
    {
        $maxCol = 0;
        foreach ($this->rows as $row) {
            $maxCol = max($maxCol, count($row));
        }
        $maxRow = count($this->rows);
        $dimensionRef = $maxRow > 0
            ? 'A1:'.$this->colLetter(max($maxCol - 1, 0)).$maxRow
            : 'A1';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<dimension ref="'.$dimensionRef.'"/>';
        $xml .= '<sheetData>';

        foreach ($this->rows as $r => $cells) {
            $rowNum = $r + 1;
            $isBold = in_array($r, $this->boldRowIndexes, true);
            $styleAttr = $isBold ? ' s="1"' : '';

            $xml .= '<row r="'.$rowNum.'">';
            foreach ($cells as $c => $value) {
                $ref = $this->colLetter($c).$rowNum;

                if ($value === null || $value === '') {
                    $xml .= '<c r="'.$ref.'"'.$styleAttr.'/>';
                } elseif (is_numeric($value)) {
                    $xml .= '<c r="'.$ref.'"'.$styleAttr.'><v>'.$value.'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'" t="inlineStr"'.$styleAttr.'><is><t xml:space="preserve">'.$this->esc((string) $value).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private function contentTypesXml(): string
    {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
          <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
          <Default Extension="xml" ContentType="application/xml"/>
          <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
          <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
          <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
        </Types>
        XML;
    }

    private function rootRelsXml(): string
    {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
          <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
        </Relationships>
        XML;
    }

    private function workbookXml(): string
    {
        $name = $this->esc($this->sheetName);

        return <<<XML
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
          <sheets>
            <sheet name="{$name}" sheetId="1" r:id="rId1"/>
          </sheets>
        </workbook>
        XML;
    }

    private function workbookRelsXml(): string
    {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
          <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
          <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
        </Relationships>
        XML;
    }

    private function stylesXml(): string
    {
        // Index 0 = normal, index 1 = bold (dipakai buat judul & header tabel)
        return <<<XML
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
          <fonts count="2">
            <font><sz val="11"/><name val="Calibri"/></font>
            <font><b/><sz val="11"/><name val="Calibri"/></font>
          </fonts>
          <fills count="2">
            <fill><patternFill patternType="none"/></fill>
            <fill><patternFill patternType="gray125"/></fill>
          </fills>
          <borders count="1"><border/></borders>
          <cellStyleXfs count="1"><xf numFmtId="0" fontId="0"/></cellStyleXfs>
          <cellXfs count="2">
            <xf numFmtId="0" fontId="0" xfId="0"/>
            <xf numFmtId="0" fontId="1" xfId="0" applyFont="1"/>
          </cellXfs>
        </styleSheet>
        XML;
    }
}
