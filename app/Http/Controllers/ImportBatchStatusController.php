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
        $status = $payload['importBatch']['status'];
        $processingError = $payload['processingError'];

        return response()->json([
            'status' => 'ok',
            'message' => 'Đã đọc trạng thái batch import thành công.',
            'toast' => $this->buildToast($status, $processingError),
            'data' => $payload,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildToast(string $status, ?string $processingError): ?array
    {
        return match ($status) {
            'aggregated', 'validated_ready', 'validated_with_warnings' => [
                'severity' => 'success',
                'summary' => 'Xử lý dữ liệu hoàn tất',
                'detail' => 'Batch import đã được xử lý xong. Trang sẽ nạp lại kết quả đã lưu trong hệ thống.',
                'life' => 5000,
            ],
            'failed' => [
                'severity' => 'error',
                'summary' => 'Xử lý dữ liệu thất bại',
                'detail' => $processingError ?? 'Batch import đã thất bại trong quá trình xử lý nền.',
                'life' => 6000,
            ],
            default => null,
        };
    }
}
