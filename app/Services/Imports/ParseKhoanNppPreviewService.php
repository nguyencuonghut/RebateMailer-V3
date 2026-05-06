<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ParseKhoanNppPreviewService
{
    private const SHEET_NAME = 'Khoán NPP';

    /**
     * @var list<string>
     */
    private const FIXED_HEADERS = [
        'STT',
        'Tháng',
        'Mã số',
        'Mã & tên khách hàng',
        'Email',
        'Địa chỉ',
        'Thức ăn chăn nuôi',
        'Tổng cộng',
        'Bằng chữ',
    ];

    /**
     * @return array<string, mixed>
     */
    public function parse(string $storedPath): array
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

        if ($workbookXml === false || $workbookRelsXml === false) {
            $zip->close();

            throw new RuntimeException('Không thể xác định sheet Khoán NPP trong workbook.');
        }

        $workbook = simplexml_load_string($workbookXml);

        if ($workbook === false) {
            $zip->close();

            throw new RuntimeException('Không thể đọc danh sách sheet trong workbook.');
        }

        $sheetPath = $this->resolveSheetPath($workbook, $workbookRelsXml, self::SHEET_NAME);

        if ($sheetPath === null) {
            $zip->close();

            throw new RuntimeException('Workbook không có sheet Khoán NPP để preview.');
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('Không thể đọc dữ liệu sheet Khoán NPP từ workbook.');
        }

        $sharedStrings = $this->extractSharedStrings($sharedStringsXml ?: null);
        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false || ! isset($sheet->sheetData->row[0])) {
            throw new RuntimeException('Sheet Khoán NPP không có dữ liệu để preview.');
        }

        $headers = $this->extractHeaderRow($sheet->sheetData->row[0], $sharedStrings);
        $fixedHeaders = array_values(array_filter(
            $headers,
            static fn (string $header): bool => in_array($header, self::FIXED_HEADERS, true),
        ));
        $programBlocks = $this->extractProgramBlocks($headers);

        $records = [];
        $currentRecordIndex = null;
        $rowIndex = 0;

        foreach ($sheet->sheetData->row as $row) {
            if ($rowIndex === 0) {
                $rowIndex++;
                continue;
            }

            $indexedValues = $this->extractIndexedRowValues($row, $sharedStrings);

            if ($indexedValues === []) {
                $rowIndex++;
                continue;
            }

            $rowValues = [];
            foreach ($headers as $index => $header) {
                $rowValues[$header] = $indexedValues[$index] ?? '';
            }

            $customerCode = $rowValues['Mã số'] ?? '';

            if ($this->isMeaningfulCellValue($customerCode)) {
                $record = $this->buildPrimaryRecord($rowValues, $indexedValues, $programBlocks);

                if ($record !== null) {
                    $records[] = $record;
                    $currentRecordIndex = array_key_last($records);
                }

                $rowIndex++;
                continue;
            }

            if ($currentRecordIndex !== null) {
                $this->mergeContinuationRowIntoRecord(
                    $records[$currentRecordIndex],
                    $indexedValues,
                    $programBlocks,
                );
            }

            $rowIndex++;
        }

        return [
            'sheetName' => self::SHEET_NAME,
            'fixedHeaders' => $fixedHeaders,
            'programBlockCount' => count($programBlocks),
            'recordCount' => count($records),
            'records' => $records,
            'nextStep' => 'Sheet Khoán NPP đã được parse. Bước kế tiếp sẽ mở rộng parser cho sheet Cám cá.',
        ];
    }

    /**
     * @param array<string, string> $rowValues
     * @param array<int, string> $indexedValues
     * @param array<int, array<string, int|string>> $programBlocks
     * @return array<string, mixed>|null
     */
    private function buildPrimaryRecord(array $rowValues, array $indexedValues, array $programBlocks): ?array
    {
        if (! $this->rowHasMeaningfulValue($rowValues)) {
            return null;
        }

        $programItems = [];

        foreach ($programBlocks as $block) {
            $content = $indexedValues[(int) $block['contentIndex']] ?? '';
            $quantity = $indexedValues[(int) $block['quantityIndex']] ?? '';
            $supportRate = $indexedValues[(int) $block['supportRateIndex']] ?? '';
            $amount = $indexedValues[(int) $block['amountIndex']] ?? '';

            if (
                ! $this->isMeaningfulCellValue($content)
                && ! $this->isMeaningfulCellValue($quantity)
                && ! $this->isMeaningfulCellValue($supportRate)
                && ! $this->isMeaningfulCellValue($amount)
            ) {
                continue;
            }

            $programItems[] = [
                'programIndex' => $block['programIndex'],
                'content' => $content,
                'quantity' => $quantity,
                'supportRate' => $supportRate,
                'amount' => $amount,
            ];
        }

        return [
            'stt' => $rowValues['STT'] ?? '',
            'month' => $rowValues['Tháng'] ?? '',
            'customerCode' => $rowValues['Mã số'] ?? '',
            'customerFullName' => $rowValues['Mã & tên khách hàng'] ?? '',
            'email' => $rowValues['Email'] ?? '',
            'address' => $rowValues['Địa chỉ'] ?? '',
            'feedCategory' => $rowValues['Thức ăn chăn nuôi'] ?? '',
            'grandTotal' => $rowValues['Tổng cộng'] ?? '',
            'totalInWords' => $rowValues['Bằng chữ'] ?? '',
            'programItems' => $programItems,
        ];
    }

    /**
     * @param array<string, mixed> $record
     * @param array<int, string> $indexedValues
     * @param array<int, array<string, int|string>> $programBlocks
     */
    private function mergeContinuationRowIntoRecord(array &$record, array $indexedValues, array $programBlocks): void
    {
        foreach ($programBlocks as $block) {
            $programIndex = (int) $block['programIndex'];

            foreach ($record['programItems'] as &$programItem) {
                if ((int) $programItem['programIndex'] !== $programIndex) {
                    continue;
                }

                $continuationContent = $indexedValues[(int) $block['contentIndex']] ?? '';

                if ($this->isMeaningfulCellValue($continuationContent)) {
                    $programItem['content'] = trim($programItem['content'].' '.$continuationContent);
                }
            }
            unset($programItem);
        }
    }

    /**
     * @param list<string> $headers
     * @return array<int, array<string, int|string>>
     */
    private function extractProgramBlocks(array $headers): array
    {
        $programBlocks = [];

        foreach ($headers as $index => $header) {
            if (! preg_match('/^Nội dung CT\s*(\d+)$/u', $header, $matches)) {
                continue;
            }

            $programBlocks[] = [
                'programIndex' => (int) $matches[1],
                'contentIndex' => $index,
                'quantityIndex' => $index + 1,
                'supportRateIndex' => $index + 2,
                'amountIndex' => $index + 3,
            ];
        }

        return $programBlocks;
    }

    private function resolveSheetPath(SimpleXMLElement $workbook, string $workbookRelsXml, string $sheetName): ?string
    {
        $relsXml = simplexml_load_string($workbookRelsXml);

        if ($relsXml === false) {
            return null;
        }

        $relationshipNodes = $relsXml->xpath('/*[local-name()="Relationships"]/*[local-name()="Relationship"]');

        if ($relationshipNodes === false) {
            return null;
        }

        $targetsByRelationId = [];

        foreach ($relationshipNodes as $relationshipNode) {
            $relationId = trim((string) $relationshipNode['Id']);
            $target = trim((string) $relationshipNode['Target']);

            if ($relationId === '' || $target === '') {
                continue;
            }

            $targetsByRelationId[$relationId] = 'xl/'.$target;
        }

        foreach ($workbook->sheets->sheet as $sheetNode) {
            $currentSheetName = $this->normalizeHeader((string) $sheetNode['name']);
            $relationAttributes = $sheetNode->attributes('r', true);
            $relationId = trim((string) ($relationAttributes['id'] ?? ''));

            if ($currentSheetName !== $sheetName || $relationId === '') {
                continue;
            }

            return $targetsByRelationId[$relationId] ?? null;
        }

        return null;
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

            $sharedStrings[] = $this->normalizeHeader($text);
        }

        return $sharedStrings;
    }

    /**
     * @param list<string> $sharedStrings
     * @return list<string>
     */
    private function extractHeaderRow(SimpleXMLElement $headerRow, array $sharedStrings): array
    {
        $indexedValues = $this->extractIndexedRowValues($headerRow, $sharedStrings);

        if ($indexedValues === []) {
            return [];
        }

        ksort($indexedValues);

        return array_values(array_map(
            fn (string $value): string => $this->normalizeHeader($value),
            $indexedValues,
        ));
    }

    /**
     * @param list<string> $sharedStrings
     * @return array<int, string>
     */
    private function extractIndexedRowValues(SimpleXMLElement $row, array $sharedStrings): array
    {
        $values = [];

        foreach ($row->c as $cell) {
            $reference = (string) $cell['r'];
            $columnLetters = preg_replace('/\d+/', '', $reference) ?: '';

            if ($columnLetters === '') {
                continue;
            }

            $columnIndex = $this->columnLettersToIndex($columnLetters);
            $values[$columnIndex] = $this->extractCellValue($cell, $sharedStrings);
        }

        return $values;
    }

    private function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    /**
     * @param list<string> $sharedStrings
     */
    private function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $cellType = trim((string) $cell['t']);

        if (isset($cell->v)) {
            $rawValue = (string) $cell->v;

            if ($cellType === 's') {
                return $this->normalizeHeader((string) ($sharedStrings[(int) $rawValue] ?? ''));
            }

            return $this->normalizeScalarValue($rawValue);
        }

        if (isset($cell->is->t)) {
            return $this->normalizeHeader((string) $cell->is->t);
        }

        return '';
    }

    /**
     * @param array<string, string> $rowValues
     */
    private function rowHasMeaningfulValue(array $rowValues): bool
    {
        foreach ($rowValues as $value) {
            if ($this->isMeaningfulCellValue($value)) {
                return true;
            }
        }

        return false;
    }

    private function isMeaningfulCellValue(string $value): bool
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return false;
        }

        return ! in_array($normalized, ['0', '0.0', '0.00'], true);
    }

    private function normalizeHeader(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return trim($value);
    }

    private function normalizeScalarValue(string $value): string
    {
        return trim($value);
    }
}
