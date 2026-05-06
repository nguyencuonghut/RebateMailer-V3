<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;

class PrepareAggregatePreviewService
{
    public function __construct(
        private readonly AnalyzeWorkbookBoundaryService $analyzeWorkbookBoundaryService,
        private readonly BuildWorkbookBoundaryPayloadService $buildWorkbookBoundaryPayloadService,
        private readonly PersistWorkbookBoundaryToImportBatchService $persistWorkbookBoundaryToImportBatchService,
        private readonly EnsureParsedSheetsPersistedForBatchService $ensureParsedSheetsPersistedForBatchService,
        private readonly AggregateImportPreviewService $aggregateImportPreviewService,
        private readonly PersistAggregatePreviewService $persistAggregatePreviewService,
        private readonly ReadPersistedAggregatePreviewService $readPersistedAggregatePreviewService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function prepare(ImportBatch $importBatch): array
    {
        $persistedPreview = $this->readPersistedAggregatePreviewService->read($importBatch);

        if ($persistedPreview !== null) {
            return $persistedPreview;
        }

        $workbookSummary = $importBatch->workbook_summary ?? [];

        if (! isset($workbookSummary['sheets']) || ! is_array($workbookSummary['sheets'])) {
            $analysis = $this->analyzeWorkbookBoundaryService->analyze($importBatch->stored_path);
            $boundary = $this->buildWorkbookBoundaryPayloadService->build($analysis);
            $importBatch = $this->persistWorkbookBoundaryToImportBatchService->persist($importBatch, $boundary);
        }

        $importBatch = $this->ensureParsedSheetsPersistedForBatchService->ensure($importBatch);
        $preview = $this->aggregateImportPreviewService->aggregate($importBatch);
        $importBatch = $this->persistAggregatePreviewService->persist($importBatch, $preview);

        return $this->readPersistedAggregatePreviewService->read($importBatch) ?? $preview;
    }
}
