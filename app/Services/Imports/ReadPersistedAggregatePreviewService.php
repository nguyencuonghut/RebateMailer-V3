<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;

class ReadPersistedAggregatePreviewService
{
    /**
     * @return array<string, mixed>|null
     */
    public function read(ImportBatch $importBatch): ?array
    {
        $aggregatePreview = $importBatch->workbook_summary['aggregatePreview'] ?? null;

        if (! is_array($aggregatePreview) || ! isset($aggregatePreview['summary'])) {
            return null;
        }

        $records = $importBatch->aggregatedRecords()
            ->orderBy('customer_code')
            ->get()
            ->map(static fn ($record): array => $record->aggregated_payload)
            ->all();

        return [
            'summary' => $aggregatePreview['summary'],
            'records' => $records,
            'nextStep' => $aggregatePreview['nextStep'] ?? null,
        ];
    }
}
