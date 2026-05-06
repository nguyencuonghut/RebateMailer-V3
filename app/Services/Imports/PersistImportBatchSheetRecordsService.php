<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\ImportBatchSheetRecord;
use RuntimeException;

class PersistImportBatchSheetRecordsService
{
    /**
     * @var array<string, string>
     */
    private const CUSTOMER_TYPE_BY_SHEET = [
        'Tổng hợp' => 'Khách thường',
        'Khoán NPP' => 'Khách thường',
        'Cám cá' => 'Khách thường',
        'Key Account' => 'Key Account',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, ImportBatchSheetRecord>
     */
    public function replaceForSheet(ImportBatch $importBatch, string $sheetName, array $records): array
    {
        $customerType = self::CUSTOMER_TYPE_BY_SHEET[$sheetName] ?? null;

        if ($customerType === null) {
            throw new RuntimeException('Sheet không thuộc contract persistence parsed record.');
        }

        $importBatch->sheetRecords()
            ->where('sheet_name', $sheetName)
            ->delete();

        $persistedRecords = [];

        foreach ($records as $record) {
            $customerCode = trim((string) ($record['customerCode'] ?? ''));
            $rowNumber = (int) ($record['rowNumber'] ?? 0);
            $parsedPayload = $record['parsedPayload'] ?? null;

            if ($customerCode === '') {
                throw new RuntimeException('Parsed record phải có customerCode.');
            }

            if ($rowNumber <= 0) {
                throw new RuntimeException('Parsed record phải có rowNumber hợp lệ.');
            }

            if (! is_array($parsedPayload)) {
                throw new RuntimeException('Parsed record phải có parsedPayload dạng mảng.');
            }

            $persistedRecords[] = ImportBatchSheetRecord::query()->create([
                'import_batch_id' => $importBatch->id,
                'sheet_name' => $sheetName,
                'customer_code' => $customerCode,
                'row_number' => $rowNumber,
                'customer_type_inferred' => $customerType,
                'parsed_payload' => $parsedPayload,
            ]);
        }

        return $persistedRecords;
    }
}
