<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use Throwable;

class UpdateImportBatchLifecycleService
{
    public function markQueued(ImportBatch $importBatch): ImportBatch
    {
        $workbookSummary = $importBatch->workbook_summary ?? [];
        unset($workbookSummary['processingError']);

        $importBatch->forceFill([
            'status' => 'queued',
            'completed_at' => null,
            'workbook_summary' => $workbookSummary,
        ])->save();

        return $importBatch->refresh();
    }

    public function markProcessing(ImportBatch $importBatch): ImportBatch
    {
        $workbookSummary = $importBatch->workbook_summary ?? [];
        unset($workbookSummary['processingError']);

        $importBatch->forceFill([
            'status' => 'processing',
            'workbook_summary' => $workbookSummary,
        ])->save();

        return $importBatch->refresh();
    }

    public function markFailed(ImportBatch $importBatch, Throwable $exception): ImportBatch
    {
        $workbookSummary = $importBatch->workbook_summary ?? [];
        $workbookSummary['processingError'] = [
            'message' => $exception->getMessage(),
            'failedAt' => now()->toIso8601String(),
        ];

        $importBatch->forceFill([
            'status' => 'failed',
            'completed_at' => now(),
            'workbook_summary' => $workbookSummary,
        ])->save();

        return $importBatch->refresh();
    }
}
