<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\PrepareAggregatePreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportProcessBatchController extends Controller
{
    public function __construct(
        private readonly PrepareAggregatePreviewService $prepareAggregatePreviewService,
    ) {
    }

    /**
     * Chạy toàn bộ pipeline: analyze workbook → parse 4 sheet → aggregate → persist.
     * Được gọi tự động từ FE ngay sau khi upload thành công.
     */
    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $preview = $this->prepareAggregatePreviewService->prepare(
                $request->importBatch(),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Xử lý dữ liệu thất bại',
                    'detail' => $exception->getMessage(),
                    'life' => 5000,
                ],
                'errors' => [
                    'batch' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Đã xử lý và lưu dữ liệu import thành công.',
            'toast' => [
                'severity' => 'success',
                'summary' => 'Xử lý dữ liệu hoàn tất',
                'detail' => 'Dữ liệu đã được parse và tổng hợp theo Mã số. Bạn có thể xem kết quả trong các tab bên dưới.',
                'life' => 4000,
            ],
            'data' => $preview,
        ]);
    }
}
