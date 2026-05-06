<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\PrepareAggregatePreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportAggregatePreviewController extends Controller
{
    public function __construct(
        private readonly PrepareAggregatePreviewService $prepareAggregatePreviewService,
    ) {
    }

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
                    'summary' => 'Không thể preview aggregator',
                    'detail' => $exception->getMessage(),
                    'life' => 4000,
                ],
                'errors' => [
                    'aggregator' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Đã gom dữ liệu import theo Mã số thành công.',
            'toast' => [
                'severity' => 'success',
                'summary' => 'Preview aggregator đã sẵn sàng',
                'detail' => 'Hệ thống đã gom dữ liệu từ 4 sheet theo Mã số và hiển thị preview hợp nhất trên UI.',
                'life' => 4000,
            ],
            'data' => $preview,
        ]);
    }
}
