<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\ParseKhoanNppPreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportKhoanNppPreviewController extends Controller
{
    public function __construct(
        private readonly ParseKhoanNppPreviewService $parseKhoanNppPreviewService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $preview = $this->parseKhoanNppPreviewService->parse(
                $request->string('storedPath')->toString(),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Không thể preview sheet Khoán NPP',
                    'detail' => $exception->getMessage(),
                    'life' => 4000,
                ],
                'errors' => [
                    'khoanNpp' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Đã parse sheet Khoán NPP thành công.',
            'toast' => [
                'severity' => 'success',
                'summary' => 'Preview sheet Khoán NPP đã sẵn sàng',
                'detail' => 'Hệ thống đã parse riêng sheet Khoán NPP và hiển thị preview dữ liệu khoán trên UI.',
                'life' => 4000,
            ],
            'data' => $preview,
        ]);
    }
}
