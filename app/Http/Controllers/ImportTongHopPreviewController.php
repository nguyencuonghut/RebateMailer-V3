<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\ParseTongHopPreviewService;
use App\Services\Imports\PersistParsedSheetPreviewService;
use App\Services\Imports\ReadPersistedSheetPreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportTongHopPreviewController extends Controller
{
    public function __construct(
        private readonly ParseTongHopPreviewService $parseTongHopPreviewService,
        private readonly PersistParsedSheetPreviewService $persistParsedSheetPreviewService,
        private readonly ReadPersistedSheetPreviewService $readPersistedSheetPreviewService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $importBatch = $request->importBatch();
            $preview = $this->readPersistedSheetPreviewService->read($importBatch, 'Tổng hợp');

            if ($preview === null) {
                $parsedPreview = $this->parseTongHopPreviewService->parse(
                    $request->resolvedStoredPath(),
                );
                $importBatch = $this->persistParsedSheetPreviewService->persist($importBatch, $parsedPreview);
                $preview = $this->readPersistedSheetPreviewService->read($importBatch, 'Tổng hợp');
            }
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

        if ($preview === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể đọc preview sheet Tổng hợp từ dữ liệu đã lưu.',
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Không thể preview sheet Tổng hợp',
                    'detail' => 'Không thể đọc preview sheet Tổng hợp từ dữ liệu đã lưu.',
                    'life' => 4000,
                ],
                'errors' => [
                    'tongHop' => ['Không thể đọc preview sheet Tổng hợp từ dữ liệu đã lưu.'],
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
