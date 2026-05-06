<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\ParseKeyAccountPreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportKeyAccountPreviewController extends Controller
{
    public function __construct(
        private readonly ParseKeyAccountPreviewService $parseKeyAccountPreviewService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $preview = $this->parseKeyAccountPreviewService->parse(
                $request->string('storedPath')->toString(),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Không thể preview sheet Key Account',
                    'detail' => $exception->getMessage(),
                    'life' => 4000,
                ],
                'errors' => [
                    'keyAccount' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Đã parse sheet Key Account thành công.',
            'toast' => [
                'severity' => 'success',
                'summary' => 'Preview sheet Key Account đã sẵn sàng',
                'detail' => 'Hệ thống đã parse riêng sheet Key Account và hiển thị preview dữ liệu khách Key Account trên UI.',
                'life' => 4000,
            ],
            'data' => $preview,
        ]);
    }
}
