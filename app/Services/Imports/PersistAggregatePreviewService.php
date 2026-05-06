<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;

class PersistAggregatePreviewService
{
    public function __construct(
        private readonly PersistImportBatchAggregatedRecordsService $persistImportBatchAggregatedRecordsService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    public function persist(ImportBatch $importBatch, array $preview): ImportBatch
    {
        $records = $preview['records'] ?? [];
        $summary = $preview['summary'] ?? [];

        if (! is_array($records) || ! is_array($summary)) {
            return $importBatch;
        }

        $this->persistImportBatchAggregatedRecordsService->replaceForBatch($importBatch, $records);

        $workbookSummary = $importBatch->workbook_summary ?? [];
        $workbookSummary['aggregatePreview'] = [
            'summary' => $summary,
            'recordCount' => count($records),
            'nextStep' => $preview['nextStep'] ?? null,
        ];

        $importBatch->forceFill([
            'workbook_summary' => $workbookSummary,
            'status' => 'aggregated',
        ])->save();

        return $importBatch->refresh();
    }
}
