<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;

class BuildImportBatchStatusPayloadService
{
    /**
     * @return array<string, mixed>
     */
    public function build(ImportBatch $importBatch): array
    {
        $batch = $importBatch->fresh()->loadCount(['sheetRecords', 'aggregatedRecords']);
        $workbookSummary = $batch->workbook_summary ?? [];
        $status = (string) $batch->status;
        $processingError = $workbookSummary['processingError']['message'] ?? null;
        $isCompleted = in_array($status, ['aggregated', 'validated_ready', 'validated_with_warnings'], true);
        $isFailed = $status === 'failed';

        return [
            'importBatch' => [
                'id' => $batch->getKey(),
                'batchCode' => $batch->batch_code,
                'status' => $status,
                'startedAt' => optional($batch->started_at)->toIso8601String(),
                'completedAt' => optional($batch->completed_at)->toIso8601String(),
            ],
            'lifecycle' => [
                'isQueued' => $status === 'queued',
                'isProcessing' => $status === 'processing',
                'isCompleted' => $isCompleted,
                'isFailed' => $isFailed,
                'isTerminal' => $isCompleted || $isFailed,
                'stateLabel' => $this->stateLabel($status),
            ],
            'workbookBoundaryReady' => isset($workbookSummary['contract'], $workbookSummary['sheets']),
            'parsedSheetCount' => count($workbookSummary['sheetPreviews'] ?? []),
            'aggregateReady' => is_array($workbookSummary['aggregatePreview'] ?? null),
            'parsedRecordCount' => $batch->sheet_records_count,
            'aggregatedRecordCount' => $batch->aggregated_records_count,
            'processingError' => is_string($processingError) && $processingError !== '' ? $processingError : null,
        ];
    }

    private function stateLabel(string $status): string
    {
        return match ($status) {
            'uploaded' => 'Đã tải file lên',
            'queued' => 'Đang chờ xử lý',
            'processing' => 'Đang xử lý nền',
            'workbook_analyzed' => 'Đã phân tích workbook',
            'parsed_partial' => 'Đã parse một phần dữ liệu',
            'parsed_complete' => 'Đã parse đủ 4 sheet',
            'aggregated' => 'Đã hợp nhất dữ liệu',
            'validated_ready' => 'Đã validation và sẵn sàng sử dụng',
            'validated_with_warnings' => 'Đã validation, có cảnh báo',
            'failed' => 'Xử lý batch thất bại',
            default => $status,
        };
    }
}
