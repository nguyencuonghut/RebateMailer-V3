<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use RuntimeException;

class PersistImportBatchAggregatedRecordsService
{
    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, ImportBatchAggregatedRecord>
     */
    public function replaceForBatch(ImportBatch $importBatch, array $records): array
    {
        $importBatch->aggregatedRecords()->delete();

        $persistedRecords = [];

        foreach ($records as $record) {
            $customerCode = trim((string) ($record['customerCode'] ?? ''));
            $customerType = trim((string) ($record['customerType'] ?? ''));
            $sourceSheets = $record['sourceSheets'] ?? null;

            if ($customerCode === '') {
                throw new RuntimeException('Aggregated record phải có customerCode.');
            }

            if ($customerType === '') {
                throw new RuntimeException('Aggregated record phải có customerType.');
            }

            if (! is_array($sourceSheets)) {
                throw new RuntimeException('Aggregated record phải có sourceSheets dạng mảng.');
            }

            $persistedRecords[] = ImportBatchAggregatedRecord::query()->create([
                'import_batch_id' => $importBatch->id,
                'customer_code' => $customerCode,
                'customer_type' => $customerType,
                'source_sheets' => array_values($sourceSheets),
                'aggregated_payload' => $record,
                'validation_state' => null,
            ]);
        }

        return $persistedRecords;
    }
}
