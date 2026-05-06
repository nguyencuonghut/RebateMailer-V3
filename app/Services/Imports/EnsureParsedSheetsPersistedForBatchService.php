<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use RuntimeException;

class EnsureParsedSheetsPersistedForBatchService
{
    public function __construct(
        private readonly ParseTongHopPreviewService $parseTongHopPreviewService,
        private readonly ParseKhoanNppPreviewService $parseKhoanNppPreviewService,
        private readonly ParseCamCaPreviewService $parseCamCaPreviewService,
        private readonly ParseKeyAccountPreviewService $parseKeyAccountPreviewService,
        private readonly PersistParsedSheetPreviewService $persistParsedSheetPreviewService,
    ) {
    }

    public function ensure(ImportBatch $importBatch): ImportBatch
    {
        $workbookSummary = $importBatch->workbook_summary ?? [];
        $boundarySheets = $workbookSummary['sheets'] ?? null;

        if (! is_array($boundarySheets)) {
            throw new RuntimeException('Batch import chưa có workbook boundary để xác định các sheet cần parse.');
        }

        $sheetPreviews = $workbookSummary['sheetPreviews'] ?? [];

        foreach ($boundarySheets as $sheet) {
            $sheetName = (string) ($sheet['name'] ?? '');
            $present = (bool) ($sheet['present'] ?? false);

            if ($sheetName === '' || ! $present) {
                continue;
            }

            if (is_array($sheetPreviews) && isset($sheetPreviews[$sheetName]) && is_array($sheetPreviews[$sheetName])) {
                continue;
            }

            $preview = $this->parsePreviewForSheet($sheetName, $importBatch->stored_path);
            $importBatch = $this->persistParsedSheetPreviewService->persist($importBatch, $preview);
            $workbookSummary = $importBatch->workbook_summary ?? [];
            $sheetPreviews = $workbookSummary['sheetPreviews'] ?? [];
        }

        return $importBatch->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePreviewForSheet(string $sheetName, string $storedPath): array
    {
        return match ($sheetName) {
            'Tổng hợp' => $this->parseTongHopPreviewService->parse($storedPath),
            'Khoán NPP' => $this->parseKhoanNppPreviewService->parse($storedPath),
            'Cám cá' => $this->parseCamCaPreviewService->parse($storedPath),
            'Key Account' => $this->parseKeyAccountPreviewService->parse($storedPath),
            default => throw new RuntimeException('Sheet không thuộc contract parser của aggregator.'),
        };
    }
}
