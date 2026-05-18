<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ImportBatchDeleteController extends Controller
{
    public function __invoke(ImportBatch $importBatch): RedirectResponse
    {
        if ($importBatch->campaigns()->exists()) {
            abort(Response::HTTP_FORBIDDEN, 'Đợt nhập này đã được dùng để tạo chiến dịch gửi mail, không thể xóa.');
        }

        $importBatch->deleteWithFile();

        return redirect()->route('imports.index');
    }
}
