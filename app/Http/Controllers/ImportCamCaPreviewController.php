<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\ParseCamCaPreviewService;
use App\Services\Imports\PersistParsedSheetPreviewService;
use App\Services\Imports\ReadPersistedSheetPreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportCamCaPreviewController extends Controller
{
    public function __construct(
        private readonly ParseCamCaPreviewService $parseCamCaPreviewService,
        private readonly PersistParsedSheetPreviewService $persistParsedSheetPreviewService,
        private readonly ReadPersistedSheetPreviewService $readPersistedSheetPreviewService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $importBatch = $request->importBatch();
            $preview = $this->readPersistedSheetPreviewService->read($importBatch, 'Cám cá');

            if ($preview === null) {
                $parsedPreview = $this->parseCamCaPreviewService->parse(
                    $request->resolvedStoredPath(),
                );
                $importBatch = $this->persistParsedSheetPreviewService->persist($importBatch, $parsedPreview);
                $preview = $this->readPersistedSheetPreviewService->read($importBatch, 'Cám cá');
            }
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

        if ($preview === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể đọc preview sheet Cám cá từ dữ liệu đã lưu.',
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Không thể preview sheet Cám cá',
                    'detail' => 'Không thể đọc preview sheet Cám cá từ dữ liệu đã lưu.',
                    'life' => 4000,
                ],
                'errors' => [
                    'camCa' => ['Không thể đọc preview sheet Cám cá từ dữ liệu đã lưu.'],
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
