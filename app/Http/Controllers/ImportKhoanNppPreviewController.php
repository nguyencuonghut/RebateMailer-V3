<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\ParseKhoanNppPreviewService;
use App\Services\Imports\PersistParsedSheetPreviewService;
use App\Services\Imports\ReadPersistedSheetPreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportKhoanNppPreviewController extends Controller
{
    public function __construct(
        private readonly ParseKhoanNppPreviewService $parseKhoanNppPreviewService,
        private readonly PersistParsedSheetPreviewService $persistParsedSheetPreviewService,
        private readonly ReadPersistedSheetPreviewService $readPersistedSheetPreviewService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $importBatch = $request->importBatch();
            $preview = $this->readPersistedSheetPreviewService->read($importBatch, 'Khoán NPP');

            if ($preview === null) {
                $parsedPreview = $this->parseKhoanNppPreviewService->parse(
                    $request->resolvedStoredPath(),
                );
                $importBatch = $this->persistParsedSheetPreviewService->persist($importBatch, $parsedPreview);
                $preview = $this->readPersistedSheetPreviewService->read($importBatch, 'Khoán NPP');
            }
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

        if ($preview === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể đọc preview sheet Khoán NPP từ dữ liệu đã lưu.',
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Không thể preview sheet Khoán NPP',
                    'detail' => 'Không thể đọc preview sheet Khoán NPP từ dữ liệu đã lưu.',
                    'life' => 4000,
                ],
                'errors' => [
                    'khoanNpp' => ['Không thể đọc preview sheet Khoán NPP từ dữ liệu đã lưu.'],
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
