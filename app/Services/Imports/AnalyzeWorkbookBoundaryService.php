<?php

namespace App\Services\Imports;

use RuntimeException;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class AnalyzeWorkbookBoundaryService
{
    /**
     * @var list<string>
     */
    private const EXPECTED_SHEETS = [
        'Tổng hợp',
        'Khoán NPP',
        'Cám cá',
        'Key Account',
    ];

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $storedPath): array
    {
        $absolutePath = Storage::disk('local')->path($storedPath);

        $zip = new ZipArchive();
        $openResult = $zip->open($absolutePath);

        if ($openResult !== true) {
            throw new RuntimeException('Không thể mở workbook Excel đã tải lên.');
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($workbookXml === false) {
            $zip->close();

            throw new RuntimeException('Không tìm thấy cấu trúc workbook trong file Excel.');
        }

        $xml = simplexml_load_string($workbookXml);

        if ($xml === false) {
            $zip->close();

            throw new RuntimeException('Không thể đọc danh sách sheet trong workbook.');
        }

        $sheetNodes = $xml->xpath('/*[local-name()="workbook"]/*[local-name()="sheets"]/*[local-name()="sheet"]');

        if ($sheetNodes === false) {
            $zip->close();

            throw new RuntimeException('Không thể truy xuất danh sách sheet trong workbook.');
        }

        $detectedSheets = array_values(array_filter(array_map(
            static fn ($sheet): string => trim((string) $sheet['name']),
            $sheetNodes,
        )));
        $missingSheets = array_values(array_diff(self::EXPECTED_SHEETS, $detectedSheets));
        $unexpectedSheets = array_values(array_diff($detectedSheets, self::EXPECTED_SHEETS));
        $headerRowBySheet = [];
        $dataRowCountBySheet = [];
        $emptyStateBySheet = [];

        if ($workbookRelsXml !== false) {
            $sheetPathByName = $this->resolveSheetPathsByName($xml, $workbookRelsXml);
            $sharedStrings = $this->extractSharedStrings($sharedStringsXml ?: null);

            foreach (array_values(array_intersect(self::EXPECTED_SHEETS, $detectedSheets)) as $sheetName) {
                $sheetPath = $sheetPathByName[$sheetName] ?? null;

                if ($sheetPath === null) {
                    continue;
                }

                $sheetXml = $zip->getFromName($sheetPath);

                if ($sheetXml === false) {
                    continue;
                }

                $headerRowBySheet[$sheetName] = $this->extractFirstRowHeaders($sheetXml, $sharedStrings);
                $dataRowCountBySheet[$sheetName] = $this->countDataRows($sheetXml, $sharedStrings);
                $emptyStateBySheet[$sheetName] = ($dataRowCountBySheet[$sheetName] ?? 0) === 0;
            }
        }

        $zip->close();

        return [
            'storedPath' => $storedPath,
            'sheetCount' => count($detectedSheets),
            'expectedSheets' => self::EXPECTED_SHEETS,
            'detectedSheets' => $detectedSheets,
            'missingSheets' => $missingSheets,
            'unexpectedSheets' => $unexpectedSheets,
            'headerRowBySheet' => $headerRowBySheet,
            'dataRowCountBySheet' => $dataRowCountBySheet,
            'emptyStateBySheet' => $emptyStateBySheet,
            'nextStep' => 'Workbook boundary contract đã được khóa. Các task parser tiếp theo sẽ chỉ cần mở rộng dữ liệu cho từng sheet hợp lệ.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function resolveSheetPathsByName(SimpleXMLElement $workbookXml, string $workbookRelsXml): array
    {
        $relsXml = simplexml_load_string($workbookRelsXml);

        if ($relsXml === false) {
            return [];
        }

        $relationshipNodes = $relsXml->xpath('/*[local-name()="Relationships"]/*[local-name()="Relationship"]');

        if ($relationshipNodes === false) {
            return [];
        }

        $targetByRelationId = [];

        foreach ($relationshipNodes as $relationshipNode) {
            $relationId = trim((string) $relationshipNode['Id']);
            $target = trim((string) $relationshipNode['Target']);

            if ($relationId === '' || $target === '') {
                continue;
            }

            $targetByRelationId[$relationId] = 'xl/'.$target;
        }

        $sheetPathByName = [];

        foreach ($workbookXml->sheets->sheet as $sheetNode) {
            $sheetName = trim((string) $sheetNode['name']);
            $relationAttributes = $sheetNode->attributes('r', true);
            $relationId = trim((string) ($relationAttributes['id'] ?? ''));

            if ($sheetName === '' || $relationId === '') {
                continue;
            }

            if (! isset($targetByRelationId[$relationId])) {
                continue;
            }

            $sheetPathByName[$sheetName] = $targetByRelationId[$relationId];
        }

        return $sheetPathByName;
    }

    /**
     * @return list<string>
     */
    private function extractSharedStrings(?string $sharedStringsXml): array
    {
        if ($sharedStringsXml === null || $sharedStringsXml === '') {
            return [];
        }

        $xml = simplexml_load_string($sharedStringsXml);

        if ($xml === false) {
            return [];
        }

        $sharedStrings = [];

        foreach ($xml->si as $stringItem) {
            $text = '';

            if (isset($stringItem->t)) {
                $text = (string) $stringItem->t;
            } else {
                foreach ($stringItem->r as $run) {
                    $text .= (string) $run->t;
                }
            }

            $sharedStrings[] = trim($text);
        }

        return $sharedStrings;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<string>
     */
    private function extractFirstRowHeaders(string $sheetXml, array $sharedStrings): array
    {
        $xml = simplexml_load_string($sheetXml);

        if ($xml === false || ! isset($xml->sheetData->row[0])) {
            return [];
        }

        $headers = [];
        $firstRow = $xml->sheetData->row[0];

        foreach ($firstRow->c as $cell) {
            $cellType = trim((string) $cell['t']);
            $rawValue = isset($cell->v) ? (string) $cell->v : '';

            $headerValue = $rawValue;

            if ($cellType === 's') {
                $headerValue = $sharedStrings[(int) $rawValue] ?? '';
            }

            $headers[] = trim($headerValue);
        }

        return $headers;
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private function countDataRows(string $sheetXml, array $sharedStrings): int
    {
        $xml = simplexml_load_string($sheetXml);

        if ($xml === false || ! isset($xml->sheetData)) {
            return 0;
        }

        $dataRowCount = 0;
        $rowIndex = 0;

        foreach ($xml->sheetData->row as $row) {
            if ($rowIndex === 0) {
                $rowIndex++;

                continue;
            }

            if ($this->rowHasMeaningfulValue($row, $sharedStrings)) {
                $dataRowCount++;
            }

            $rowIndex++;
        }

        return $dataRowCount;
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private function rowHasMeaningfulValue(SimpleXMLElement $row, array $sharedStrings): bool
    {
        foreach ($row->c as $cell) {
            $value = $this->extractCellValue($cell, $sharedStrings);

            if (trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $cellType = trim((string) $cell['t']);

        if (isset($cell->v)) {
            $rawValue = (string) $cell->v;

            if ($cellType === 's') {
                return (string) ($sharedStrings[(int) $rawValue] ?? '');
            }

            return $rawValue;
        }

        if (isset($cell->is->t)) {
            return (string) $cell->is->t;
        }

        return '';
    }
}
