<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\ParseCamCaPreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportCamCaPreviewController extends Controller
{
    public function __construct(
        private readonly ParseCamCaPreviewService $parseCamCaPreviewService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $preview = $this->parseCamCaPreviewService->parse(
                $request->string('storedPath')->toString(),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Không thể preview sheet Cám cá',
                    'detail' => $exception->getMessage(),
                    'life' => 4000,
                ],
                'errors' => [
                    'camCa' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Đã parse sheet Cám cá thành công.',
            'toast' => [
                'severity' => 'success',
                'summary' => 'Preview sheet Cám cá đã sẵn sàng',
                'detail' => 'Hệ thống đã parse riêng sheet Cám cá và hiển thị preview dữ liệu chiết khấu cám cá trên UI.',
                'life' => 4000,
            ],
            'data' => $preview,
        ]);
    }
}
