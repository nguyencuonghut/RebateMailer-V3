<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\ParseTongHopPreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportTongHopPreviewController extends Controller
{
    public function __construct(
        private readonly ParseTongHopPreviewService $parseTongHopPreviewService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $preview = $this->parseTongHopPreviewService->parse(
                $request->string('storedPath')->toString(),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Không thể preview sheet Tổng hợp',
                    'detail' => $exception->getMessage(),
                    'life' => 4000,
                ],
                'errors' => [
                    'tongHop' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Đã parse sheet Tổng hợp thành công.',
            'toast' => [
                'severity' => 'success',
                'summary' => 'Preview sheet Tổng hợp đã sẵn sàng',
                'detail' => 'Hệ thống đã parse riêng sheet Tổng hợp và hiển thị preview dữ liệu trên UI.',
                'life' => 4000,
            ],
            'data' => $preview,
        ]);
    }
}
