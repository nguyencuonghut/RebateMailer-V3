<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\ParseKeyAccountPreviewService;
use App\Services\Imports\PersistParsedSheetPreviewService;
use App\Services\Imports\ReadPersistedSheetPreviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportKeyAccountPreviewController extends Controller
{
    public function __construct(
        private readonly ParseKeyAccountPreviewService $parseKeyAccountPreviewService,
        private readonly PersistParsedSheetPreviewService $persistParsedSheetPreviewService,
        private readonly ReadPersistedSheetPreviewService $readPersistedSheetPreviewService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $importBatch = $request->importBatch();
            $preview = $this->readPersistedSheetPreviewService->read($importBatch, 'Key Account');

            if ($preview === null) {
                $parsedPreview = $this->parseKeyAccountPreviewService->parse(
                    $request->resolvedStoredPath(),
                );
                $importBatch = $this->persistParsedSheetPreviewService->persist($importBatch, $parsedPreview);
                $preview = $this->readPersistedSheetPreviewService->read($importBatch, 'Key Account');
            }
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

        if ($preview === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể đọc preview sheet Key Account từ dữ liệu đã lưu.',
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Không thể preview sheet Key Account',
                    'detail' => 'Không thể đọc preview sheet Key Account từ dữ liệu đã lưu.',
                    'life' => 4000,
                ],
                'errors' => [
                    'keyAccount' => ['Không thể đọc preview sheet Key Account từ dữ liệu đã lưu.'],
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
