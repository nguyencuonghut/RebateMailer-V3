<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;

class WriteValidationStateService
{
    /**
     * Ghi kết quả validation vào từng aggregated record và cập nhật batch.
     *
     * @param  array<string, ValidationIssue[]>  $issuesByCustomerCode  key = customer_code
     */
    public function write(ImportBatch $importBatch, array $issuesByCustomerCode): ImportBatch
    {
        $totalErrors = 0;
        $totalWarnings = 0;

        $importBatch->aggregatedRecords()->each(
            function (ImportBatchAggregatedRecord $record) use ($issuesByCustomerCode, &$totalErrors, &$totalWarnings): void {
                $issues = $issuesByCustomerCode[$record->customer_code] ?? [];

                $errors = array_values(array_filter($issues, static fn (ValidationIssue $i): bool => $i->isBlocking));
                $warnings = array_values(array_filter($issues, static fn (ValidationIssue $i): bool => ! $i->isBlocking));

                $totalErrors += count($errors);
                $totalWarnings += count($warnings);

                $record->forceFill([
                    'validation_state' => [
                        'isValid' => count($errors) === 0,
                        'errors' => array_map(static fn (ValidationIssue $i): array => $i->toArray(), $errors),
                        'warnings' => array_map(static fn (ValidationIssue $i): array => $i->toArray(), $warnings),
                    ],
                ])->save();
            }
        );

        return $this->updateBatch($importBatch, $totalErrors, $totalWarnings);
    }

    private function updateBatch(ImportBatch $importBatch, int $totalErrors, int $totalWarnings): ImportBatch
    {
        $isReadyToDispatch = $totalErrors === 0;
        $status = ($totalErrors === 0 && $totalWarnings === 0) ? 'validated_ready' : 'validated_with_warnings';

        $workbookSummary = $importBatch->workbook_summary ?? [];
        $workbookSummary['validationSummary'] = [
            'totalErrors' => $totalErrors,
            'totalWarnings' => $totalWarnings,
            'isReadyToDispatch' => $isReadyToDispatch,
            'checkedAt' => now()->toIso8601String(),
        ];

        $importBatch->forceFill([
            'workbook_summary' => $workbookSummary,
            'status' => $status,
        ])->save();

        return $importBatch->refresh();
    }
}
