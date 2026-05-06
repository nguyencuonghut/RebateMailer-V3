<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\Imports\PrepareAggregatePreviewService;
use App\Services\Imports\UpdateImportBatchLifecycleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessImportBatchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $importBatchId,
    ) {
    }

    public function handle(
        PrepareAggregatePreviewService $prepareAggregatePreviewService,
        UpdateImportBatchLifecycleService $updateImportBatchLifecycleService,
    ): void {
        $importBatch = ImportBatch::query()->find($this->importBatchId);

        if (! $importBatch instanceof ImportBatch) {
            return;
        }

        if (in_array($importBatch->status, ['aggregated', 'validated_ready', 'validated_with_warnings'], true)) {
            return;
        }

        $importBatch = $updateImportBatchLifecycleService->markProcessing($importBatch);

        try {
            $prepareAggregatePreviewService->prepare($importBatch);
        } catch (Throwable $exception) {
            $updateImportBatchLifecycleService->markFailed($importBatch, $exception);

            throw $exception;
        }
    }
}
