<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\StoreImportUploadRequest;
use App\Services\Imports\CreateImportBatchService;
use App\Services\Imports\StoreTemporaryImportFileService;
use Illuminate\Http\JsonResponse;

class ImportUploadController extends Controller
{
    public function __construct(
        private readonly CreateImportBatchService $createImportBatchService,
        private readonly StoreTemporaryImportFileService $storeTemporaryImportFileService,
    ) {
    }

    public function store(StoreImportUploadRequest $request): JsonResponse
    {
        $receipt = $this->storeTemporaryImportFileService->store($request->file('file'));
        $batchName = trim($request->string('batch_name')->toString());
        $importBatch = $this->createImportBatchService->create(
            $batchName !== '' ? $batchName : pathinfo($receipt['originalFileName'], PATHINFO_FILENAME),
            $receipt['originalFileName'],
            $receipt['storedPath'],
            $request->user()?->getKey(),
        );

        $receipt['importBatch'] = [
            'id' => $importBatch->id,
            'batchCode' => $importBatch->batch_code,
            'name' => $importBatch->name,
            'status' => $importBatch->status,
        ];

        return response()->json([
            'status' => 'ok',
            'message' => 'Tải file lên thành công.',
            'toast' => [
                'severity' => 'success',
                'summary' => 'Tải file thành công',
                'detail' => 'File Excel đã được tiếp nhận, lưu tạm và tạo batch import cho bước preview tiếp theo.',
                'life' => 4000,
            ],
            'data' => $receipt,
        ]);
    }
}
