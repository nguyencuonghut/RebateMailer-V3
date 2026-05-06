<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\AnalyzeWorkbookBoundaryService;
use App\Services\Imports\BuildWorkbookBoundaryPayloadService;
use App\Services\Imports\PersistWorkbookBoundaryToImportBatchService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ImportWorkbookAnalysisController extends Controller
{
    public function __construct(
        private readonly AnalyzeWorkbookBoundaryService $analyzeWorkbookBoundaryService,
        private readonly BuildWorkbookBoundaryPayloadService $buildWorkbookBoundaryPayloadService,
        private readonly PersistWorkbookBoundaryToImportBatchService $persistWorkbookBoundaryToImportBatchService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        try {
            $analysis = $this->analyzeWorkbookBoundaryService->analyze(
                $request->resolvedStoredPath(),
            );
            $workbookBoundary = $this->buildWorkbookBoundaryPayloadService->build($analysis);
            $importBatch = $this->persistWorkbookBoundaryToImportBatchService->persist(
                $request->importBatch(),
                $workbookBoundary,
            );
            $workbookBoundary['importBatch'] = [
                'id' => $importBatch->id,
                'batchCode' => $importBatch->batch_code,
                'status' => $importBatch->status,
            ];
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'toast' => [
                    'severity' => 'error',
                    'summary' => 'Không thể đọc workbook',
                    'detail' => $exception->getMessage(),
                    'life' => 4000,
                ],
                'errors' => [
                    'workbook' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Đã đọc cấu trúc workbook thành công.',
            'toast' => [
                'severity' => 'success',
                'summary' => 'Đọc workbook thành công',
                'detail' => 'Hệ thống đã nhận diện được danh sách sheet từ file Excel đã tải lên.',
                'life' => 4000,
            ],
            'data' => $workbookBoundary,
        ]);
    }
}
