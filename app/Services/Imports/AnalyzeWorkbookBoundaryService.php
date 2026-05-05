<?php

namespace App\Services\Imports;

use RuntimeException;
use ZipArchive;
use Illuminate\Support\Facades\Storage;

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
        $zip->close();

        if ($workbookXml === false) {
            throw new RuntimeException('Không tìm thấy cấu trúc workbook trong file Excel.');
        }

        $xml = simplexml_load_string($workbookXml);

        if ($xml === false) {
            throw new RuntimeException('Không thể đọc danh sách sheet trong workbook.');
        }

        $sheetNodes = $xml->xpath('/*[local-name()="workbook"]/*[local-name()="sheets"]/*[local-name()="sheet"]');

        if ($sheetNodes === false) {
            throw new RuntimeException('Không thể truy xuất danh sách sheet trong workbook.');
        }

        $detectedSheets = array_values(array_filter(array_map(
            static fn ($sheet): string => trim((string) $sheet['name']),
            $sheetNodes,
        )));
        $missingSheets = array_values(array_diff(self::EXPECTED_SHEETS, $detectedSheets));
        $unexpectedSheets = array_values(array_diff($detectedSheets, self::EXPECTED_SHEETS));

        return [
            'storedPath' => $storedPath,
            'sheetCount' => count($detectedSheets),
            'expectedSheets' => self::EXPECTED_SHEETS,
            'detectedSheets' => $detectedSheets,
            'missingSheets' => $missingSheets,
            'unexpectedSheets' => $unexpectedSheets,
            'nextStep' => 'Workbook đã được nhận diện. Bước kế tiếp sẽ đọc line 1 của từng sheet import hợp lệ.',
        ];
    }
}
