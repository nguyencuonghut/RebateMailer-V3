<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImportBatchJob;
use App\Http\Requests\Imports\AnalyzeWorkbookBoundaryRequest;
use App\Services\Imports\UpdateImportBatchLifecycleService;
use Illuminate\Http\JsonResponse;

class ImportProcessBatchController extends Controller
{
    public function __construct(
        private readonly UpdateImportBatchLifecycleService $updateImportBatchLifecycleService,
    ) {
    }

    public function store(AnalyzeWorkbookBoundaryRequest $request): JsonResponse
    {
        $importBatch = $request->importBatch();

        if (in_array($importBatch->status, ['aggregated', 'validated_ready', 'validated_with_warnings'], true)) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Batch import này đã được xử lý xong.',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Batch đã xử lý xong',
                    'detail' => 'Đợt import này đã có dữ liệu hoàn chỉnh trong hệ thống, không cần đưa lại vào hàng đợi.',
                    'life' => 4000,
                ],
                'data' => [
                    'importBatch' => [
                        'id' => $importBatch->id,
                        'batchCode' => $importBatch->batch_code,
                        'status' => $importBatch->status,
                    ],
                ],
            ]);
        }

        if (in_array($importBatch->status, ['queued', 'processing'], true)) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Batch import đang nằm trong hàng đợi xử lý.',
                'toast' => [
                    'severity' => 'info',
                    'summary' => 'Batch đang được xử lý',
                    'detail' => 'Hệ thống đang tiếp tục xử lý batch này ở background. Trang sẽ tự cập nhật khi hoàn tất.',
                    'life' => 4000,
                ],
                'data' => [
                    'importBatch' => [
                        'id' => $importBatch->id,
                        'batchCode' => $importBatch->batch_code,
                        'status' => $importBatch->status,
                    ],
                ],
            ], 202);
        }

        $importBatch = $this->updateImportBatchLifecycleService->markQueued($importBatch);
        ProcessImportBatchJob::dispatch($importBatch->id);

        return response()->json([
            'status' => 'ok',
            'message' => 'Batch import đã được đưa vào hàng đợi xử lý.',
            'toast' => [
                'severity' => 'info',
                'summary' => 'Đã đưa vào hàng đợi xử lý',
                'detail' => 'Batch import sẽ được worker xử lý ở background. Trang sẽ tự cập nhật khi hoàn tất.',
                'life' => 4000,
            ],
            'data' => [
                'importBatch' => [
                    'id' => $importBatch->id,
                    'batchCode' => $importBatch->batch_code,
                    'status' => $importBatch->status,
                ],
            ],
        ], 202);
    }
}
