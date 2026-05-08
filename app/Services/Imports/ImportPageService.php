<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use Illuminate\Support\Facades\Storage;

class ImportPageService
{
    public function __construct(
        private readonly ReadPersistedSheetPreviewService $readPersistedSheetPreviewService,
        private readonly ReadPersistedAggregatePreviewService $readPersistedAggregatePreviewService,
        private readonly PresentImportProcessingErrorService $presentImportProcessingErrorService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getIndexPageData(bool $canManageImports, ?int $selectedBatchId = null): array
    {
        $selectedBatch = $selectedBatchId === null
            ? null
            : ImportBatch::query()
                ->with(['uploader'])
                ->withCount(['sheetRecords', 'aggregatedRecords'])
                ->find($selectedBatchId);

        return [
            'title' => 'Import dữ liệu',
            'description' => 'Tiếp nhận file Excel chiết khấu, xử lý dữ liệu theo batch và xem lại kết quả đã lưu trong hệ thống.',
            'currentSlice' => [
                'code' => '1.7',
                'label' => 'Aggregator',
            ],
            'canManageImports' => $canManageImports,
            'uploadPolicy' => [
                'acceptedExtension' => '.xlsx',
                'acceptedMimeLabel' => 'Tệp Excel (.xlsx)',
            ],
            'acceptedSheets' => [
                'Tổng hợp',
                'Khoán NPP',
                'Cám cá',
                'Key Account',
            ],
            'nextSlice' => [
                'code' => '1.8',
                'label' => 'Validation',
            ],
            'analysisPrep' => [
                'actionLabel' => 'Đọc cấu trúc workbook',
                'helperText' => 'Thông tin cấu trúc tệp Excel được dùng để kiểm tra nhanh tình trạng 4 sheet nhập liệu hợp lệ của đợt nhập hiện tại.',
                'readyTitle' => 'Cấu trúc tệp Excel đã sẵn sàng',
                'readyDescription' => 'Tệp Excel đã được phân tích và lưu vào đợt nhập. Khu vực bên dưới hiển thị lại cấu trúc đã được lưu trong hệ thống.',
                'statusLabel' => 'Chưa phân tích tệp Excel',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Phân tích tệp Excel thành công',
                    'detail' => 'Cấu trúc tệp Excel đã sẵn sàng và có thể dùng để rà nhanh cấu trúc nhập dữ liệu của đợt nhập.',
                    'life' => 4000,
                ],
            ],
            'toast' => $selectedBatch === null
                ? [
                    'severity' => 'info',
                    'summary' => '',
                    'detail' => '',
                    'life' => 0,
                ]
                : [
                    'severity' => 'info',
                    'summary' => '',
                    'detail' => '',
                    'life' => 0,
                ],
            'activeBatchId' => $selectedBatch?->getKey(),
            'initialUploadReceipt' => $selectedBatch ? $this->buildInitialUploadReceipt($selectedBatch) : null,
            'initialBatchProcessingError' => $selectedBatch ? $this->initialBatchProcessingError($selectedBatch) : null,
            'initialWorkbookBoundary' => $selectedBatch ? $this->buildInitialWorkbookBoundary($selectedBatch) : null,
            'initialTongHopPreview' => $selectedBatch ? $this->readPersistedSheetPreviewService->read($selectedBatch, 'Tổng hợp') : null,
            'initialKhoanNppPreview' => $selectedBatch ? $this->readPersistedSheetPreviewService->read($selectedBatch, 'Khoán NPP') : null,
            'initialCamCaPreview' => $selectedBatch ? $this->readPersistedSheetPreviewService->read($selectedBatch, 'Cám cá') : null,
            'initialKeyAccountPreview' => $selectedBatch ? $this->readPersistedSheetPreviewService->read($selectedBatch, 'Key Account') : null,
            'initialAggregatePreview' => $selectedBatch ? $this->readPersistedAggregatePreviewService->read($selectedBatch) : null,
            'importHistory' => $this->buildImportHistory(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildImportHistory(): array
    {
        return ImportBatch::query()
            ->with(['uploader'])
            ->withCount(['sheetRecords', 'aggregatedRecords'])
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (ImportBatch $importBatch): array => [
                'id' => $importBatch->getKey(),
                'batchCode' => $importBatch->batch_code,
                'batchName' => $importBatch->name,
                'originalFileName' => $importBatch->original_file_name,
                'status' => $importBatch->status,
                'uploadedBy' => $importBatch->uploader?->name ?? 'Không xác định',
                'uploadedAt' => $this->formatBatchTimestamp($importBatch),
                'parsedRecordCount' => $importBatch->sheet_records_count,
                'aggregatedRecordCount' => $importBatch->aggregated_records_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInitialUploadReceipt(ImportBatch $importBatch): array
    {
        $fileSize = null;

        if ($importBatch->stored_path !== '' && Storage::disk('local')->exists($importBatch->stored_path)) {
            $fileSize = Storage::disk('local')->size($importBatch->stored_path);
        }

        return [
            'originalFileName' => $importBatch->original_file_name,
            'size' => $fileSize,
            'storedPath' => $importBatch->stored_path,
            'uploadedAt' => optional($importBatch->started_at)->toIso8601String(),
            'importBatch' => [
                'id' => $importBatch->getKey(),
                'batchCode' => $importBatch->batch_code,
                'name' => $importBatch->name,
                'status' => $importBatch->status,
            ],
            'nextStep' => 'Bạn đang xem lại một đợt nhập dữ liệu đã được lưu trong hệ thống.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildInitialWorkbookBoundary(ImportBatch $importBatch): ?array
    {
        $workbookSummary = $importBatch->workbook_summary ?? [];

        if (! isset($workbookSummary['contract']) || ! isset($workbookSummary['sheets'])) {
            return null;
        }

        return [
            'storedPath' => $importBatch->stored_path,
            'importBatch' => [
                'id' => $importBatch->getKey(),
                'batchCode' => $importBatch->batch_code,
                'name' => $importBatch->name,
                'status' => $importBatch->status,
            ],
            'contract' => $workbookSummary['contract'],
            'summary' => $workbookSummary['summary'] ?? [],
            'expectedSheets' => $workbookSummary['expectedSheets'] ?? [],
            'detectedSheets' => $workbookSummary['detectedSheets'] ?? [],
            'missingSheets' => $workbookSummary['missingSheets'] ?? [],
            'unexpectedSheets' => $workbookSummary['unexpectedSheets'] ?? [],
            'sheets' => $workbookSummary['sheets'] ?? [],
            'nextStep' => $workbookSummary['nextStep'] ?? null,
        ];
    }

    private function formatBatchTimestamp(ImportBatch $importBatch): ?string
    {
        return optional($importBatch->started_at)->toIso8601String();
    }

    private function initialBatchProcessingError(ImportBatch $importBatch): ?string
    {
        $message = $importBatch->workbook_summary['processingError']['message'] ?? null;

        return is_string($message) && $message !== ''
            ? $this->presentImportProcessingErrorService->presentMessage($message)
            : null;
    }
}
