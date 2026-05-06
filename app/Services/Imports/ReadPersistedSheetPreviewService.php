<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;

class ReadPersistedSheetPreviewService
{
    /**
     * @return array<string, mixed>|null
     */
    public function read(ImportBatch $importBatch, string $sheetName): ?array
    {
        $sheetPreviews = $importBatch->workbook_summary['sheetPreviews'] ?? null;

        if (! is_array($sheetPreviews) || ! isset($sheetPreviews[$sheetName]) || ! is_array($sheetPreviews[$sheetName])) {
            return null;
        }

        $records = $importBatch->sheetRecords()
            ->where('sheet_name', $sheetName)
            ->orderBy('row_number')
            ->get()
            ->map(static fn ($record): array => $record->parsed_payload)
            ->all();

        $preview = $sheetPreviews[$sheetName];
        $preview['sheetName'] = $sheetName;
        $preview['recordCount'] = count($records);
        $preview['records'] = $records;

        return $preview;
    }
}
