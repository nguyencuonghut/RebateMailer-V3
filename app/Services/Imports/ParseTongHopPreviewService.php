<?php

namespace App\Services\Imports;

use RuntimeException;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class ParseTongHopPreviewService
{
    public function __construct(
        private readonly NormalizeImportedEmailListService $normalizeImportedEmailListService,
    ) {
    }

    private const SHEET_NAME = 'Tổng hợp';

    /**
     * @var list<string>
     */
    private const FIXED_HEADERS = [
        'STT',
        'Tháng',
        'Mã số',
        'Mã & tên khách hàng',
        'Tên khách hàng',
        'Email',
        'Địa chỉ',
        'Thức ăn chăn nuôi',
        'Tổng sản lượng (gồm cám thủy sản)',
        'Doanh thu (gồm cám thủy sản)',
        'Tiền chiết khấu theo Hóa đơn',
        'Thưởng cam kết tháng',
        'Chiết khấu cám cá',
        'Chiết khấu khác ( Không thể hiện trên hóa đơn)',
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

            throw new RuntimeException('Không thể xác định sheet Tổng hợp trong workbook.');
        }

        $workbook = simplexml_load_string($workbookXml);

        if ($workbook === false) {
            $zip->close();

            throw new RuntimeException('Không thể đọc danh sách sheet trong workbook.');
        }

        $sheetPath = $this->resolveSheetPath($workbook, $workbookRelsXml, self::SHEET_NAME);

        if ($sheetPath === null) {
            $zip->close();

            throw new RuntimeException('Workbook không có sheet Tổng hợp để preview.');
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('Không thể đọc dữ liệu sheet Tổng hợp từ workbook.');
        }

        $sharedStrings = $this->extractSharedStrings($sharedStringsXml ?: null);
        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false || ! isset($sheet->sheetData->row[0])) {
            throw new RuntimeException('Sheet Tổng hợp không có dữ liệu để preview.');
        }

        $headers = $this->extractHeaderRow($sheet->sheetData->row[0], $sharedStrings);
        $fixedHeaders = array_values(array_filter(
            $headers,
            static fn (string $header): bool => in_array($header, self::FIXED_HEADERS, true),
        ));
        $dynamicHeaders = array_values(array_filter(
            $headers,
            static fn (string $header): bool => $header !== '' && ! in_array($header, self::FIXED_HEADERS, true),
        ));

        $records = [];
        $rowIndex = 0;

        foreach ($sheet->sheetData->row as $row) {
            if ($rowIndex === 0) {
                $rowIndex++;

                continue;
            }

            $excelRowNumber = $this->extractRowNumber($row, $rowIndex + 1);
            $record = $this->buildRecord($row, $headers, $dynamicHeaders, $sharedStrings, $excelRowNumber);

            if ($record === null) {
                $rowIndex++;

                continue;
            }

            $records[] = $record;
            $rowIndex++;
        }

        return [
            'sheetName' => self::SHEET_NAME,
            'fixedHeaders' => $fixedHeaders,
            'dynamicHeaders' => $dynamicHeaders,
            'recordCount' => count($records),
            'records' => $records,
            'nextStep' => 'Sheet Tổng hợp đã được parse. Bước kế tiếp sẽ mở rộng parser cho sheet Khoán NPP.',
        ];
    }

    /**
     * @return array<string, string>|null
     */
    private function buildRecord(
        SimpleXMLElement $row,
        array $headers,
        array $dynamicHeaders,
        array $sharedStrings,
        int $rowNumber,
    ): ?array {
        $indexedValues = $this->extractIndexedRowValues($row, $sharedStrings);
        $rowValues = [];

        foreach ($headers as $index => $header) {
            $rowValues[$header] = $indexedValues[$index] ?? '';
        }

        if (! $this->rowHasMeaningfulValue($rowValues)) {
            return null;
        }

        $dynamicItems = [];

        foreach ($dynamicHeaders as $header) {
            $value = $rowValues[$header] ?? '';

            if (! $this->isMeaningfulCellValue($value)) {
                continue;
            }

            $dynamicItems[] = [
                'label' => $header,
                'value' => $value,
            ];
        }

        $email = trim((string) ($rowValues['Email'] ?? ''));

        return [
            'rowNumber' => $rowNumber,
            'stt' => $rowValues['STT'] ?? '',
            'month' => $rowValues['Tháng'] ?? '',
            'customerCode' => $rowValues['Mã số'] ?? '',
            'customerFullName' => $rowValues['Mã & tên khách hàng'] ?? '',
            'customerName' => $rowValues['Tên khách hàng'] ?? '',
            'email' => $email,
            'emails' => $this->normalizeImportedEmailListService->normalize($email),
            'address' => $rowValues['Địa chỉ'] ?? '',
            'feedCategory' => $rowValues['Thức ăn chăn nuôi'] ?? '',
            'totalQuantity' => $rowValues['Tổng sản lượng (gồm cám thủy sản)'] ?? '',
            'revenue' => $rowValues['Doanh thu (gồm cám thủy sản)'] ?? '',
            'invoiceDiscount' => $rowValues['Tiền chiết khấu theo Hóa đơn'] ?? '',
            'commitmentBonus' => $rowValues['Thưởng cam kết tháng'] ?? '',
            'fishFeedDiscount' => $rowValues['Chiết khấu cám cá'] ?? '',
            'otherDiscount' => $rowValues['Chiết khấu khác ( Không thể hiện trên hóa đơn)'] ?? '',
            'grandTotal' => $rowValues['Tổng cộng'] ?? '',
            'totalInWords' => $rowValues['Bằng chữ'] ?? '',
            'dynamicItems' => $dynamicItems,
        ];
    }

    private function extractRowNumber(SimpleXMLElement $row, int $fallback): int
    {
        $rowNumber = (int) ($row['r'] ?? 0);

        return $rowNumber > 0 ? $rowNumber : $fallback;
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
     * @param  list<string>  $sharedStrings
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
     * @param  list<string>  $sharedStrings
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
     * @param  list<string>  $sharedStrings
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
     * @param  array<string, string>  $rowValues
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
