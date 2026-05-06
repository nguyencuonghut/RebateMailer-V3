<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\Imports\BuildImportBatchStatusPayloadService;
use Illuminate\Http\JsonResponse;

class ImportBatchStatusController extends Controller
{
    public function __construct(
        private readonly BuildImportBatchStatusPayloadService $buildImportBatchStatusPayloadService,
    ) {
    }

    public function show(ImportBatch $importBatch): JsonResponse
    {
        $payload = $this->buildImportBatchStatusPayloadService->build($importBatch);

        return response()->json([
            'status' => 'ok',
            'message' => 'Đã đọc trạng thái batch import thành công.',
            'toast' => $this->buildToast($payload),
            'data' => $payload,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildToast(array $payload): ?array
    {
        $status = $payload['importBatch']['status'];
        $processingError = $payload['processingError'];
        $batchCode = $payload['importBatch']['batchCode'];
        $parsedSheetCount = $payload['parsedSheetCount'];
        $aggregatedRecordCount = $payload['aggregatedRecordCount'];

        return match ($status) {
            'aggregated', 'validated_ready', 'validated_with_warnings' => [
                'severity' => 'success',
                'summary' => 'Xử lý dữ liệu hoàn tất',
                'detail' => sprintf(
                    'Đợt nhập %s đã xử lý xong. Hệ thống đã parse %d sheet hợp lệ và hợp nhất %d khách hàng. Trang sẽ nạp lại kết quả đã lưu trong hệ thống.',
                    $batchCode,
                    $parsedSheetCount,
                    $aggregatedRecordCount,
                ),
                'life' => 6500,
            ],
            'failed' => [
                'severity' => 'error',
                'summary' => 'Xử lý dữ liệu thất bại',
                'detail' => sprintf(
                    'Đợt nhập %s thất bại trong quá trình xử lý nền. %s',
                    $batchCode,
                    $processingError ?? 'Vui lòng kiểm tra file Excel, dữ liệu đầu vào và log hệ thống trước khi thử lại.',
                ),
                'life' => 8000,
            ],
            default => null,
        };
    }
}
