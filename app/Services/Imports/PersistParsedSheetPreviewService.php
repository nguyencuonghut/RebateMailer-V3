<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use RuntimeException;

class PersistParsedSheetPreviewService
{
    public function __construct(
        private readonly PersistImportBatchSheetRecordsService $persistImportBatchSheetRecordsService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    public function persist(ImportBatch $importBatch, array $preview): ImportBatch
    {
        $sheetName = trim((string) ($preview['sheetName'] ?? ''));
        $records = $preview['records'] ?? [];

        if ($sheetName === '' || ! is_array($records)) {
            throw new RuntimeException('Preview sheet không hợp lệ để persist.');
        }

        $persistableRecords = array_map(
            static function (array $record): array {
                return [
                    'customerCode' => $record['customerCode'] ?? '',
                    'rowNumber' => $record['rowNumber'] ?? 0,
                    'parsedPayload' => $record,
                ];
            },
            $records,
        );

        $this->persistImportBatchSheetRecordsService->replaceForSheet(
            $importBatch,
            $sheetName,
            $persistableRecords,
        );

        $workbookSummary = $importBatch->workbook_summary ?? [];
        $sheetPreviews = $workbookSummary['sheetPreviews'] ?? [];

        if (! is_array($sheetPreviews)) {
            $sheetPreviews = [];
        }

        $sheetPreviews[$sheetName] = $this->extractPreviewMetadata($preview);
        $workbookSummary['sheetPreviews'] = $sheetPreviews;

        $importBatch->forceFill([
            'workbook_summary' => $workbookSummary,
            'status' => $this->determineBatchStatus($workbookSummary),
        ])->save();

        return $importBatch->refresh();
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return array<string, mixed>
     */
    private function extractPreviewMetadata(array $preview): array
    {
        $metadata = $preview;
        unset($metadata['records']);
        $metadata['recordCount'] = (int) ($preview['recordCount'] ?? count($preview['records'] ?? []));

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $workbookSummary
     */
    private function determineBatchStatus(array $workbookSummary): string
    {
        $sheetPreviews = $workbookSummary['sheetPreviews'] ?? [];
        $boundarySheets = $workbookSummary['sheets'] ?? [];

        if (! is_array($sheetPreviews) || ! is_array($boundarySheets)) {
            return 'parsed_partial';
        }

        $presentSheetNames = array_values(array_map(
            static fn (array $sheet): string => (string) ($sheet['name'] ?? ''),
            array_filter(
                $boundarySheets,
                static fn (array $sheet): bool => (bool) ($sheet['present'] ?? false),
            ),
        ));

        if ($presentSheetNames === []) {
            return 'parsed_partial';
        }

        foreach ($presentSheetNames as $sheetName) {
            if ($sheetName === '' || ! isset($sheetPreviews[$sheetName])) {
                return 'parsed_partial';
            }
        }

        return 'parsed_complete';
    }
}
