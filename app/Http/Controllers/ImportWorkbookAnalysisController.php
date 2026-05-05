<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\AnalyzeWorkbookBoundaryService;
use Illuminate\Http\JsonResponse;

class ImportWorkbookAnalysisController extends Controller
{
    public function __construct(
        private readonly AnalyzeWorkbookBoundaryService $analyzeWorkbookBoundaryService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        $workbookBoundary = $this->analyzeWorkbookBoundaryService->analyze(
            $request->string('storedPath')->toString(),
        );

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
