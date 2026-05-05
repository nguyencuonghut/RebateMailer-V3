<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\StoreImportUploadRequest;
use App\Services\Imports\StoreTemporaryImportFileService;
use Illuminate\Http\JsonResponse;

class ImportUploadController extends Controller
{
    public function __construct(
        private readonly StoreTemporaryImportFileService $storeTemporaryImportFileService,
    ) {
    }

    public function store(StoreImportUploadRequest $request): JsonResponse
    {
        $receipt = $this->storeTemporaryImportFileService->store($request->file('file'));

        return response()->json([
            'status' => 'ok',
            'message' => 'Tải file lên thành công.',
            'toast' => [
                'severity' => 'success',
                'summary' => 'Tải file thành công',
                'detail' => 'File Excel đã được tiếp nhận và lưu tạm cho bước preview tiếp theo.',
                'life' => 4000,
            ],
            'data' => $receipt,
        ]);
    }
}
