<?php

namespace App\Services\Imports;

use Throwable;

class PresentImportProcessingErrorService
{
    public function present(Throwable $exception): string
    {
        return $this->presentMessage($exception->getMessage());
    }

    public function presentMessage(?string $message): string
    {
        $message = trim((string) $message);

        if ($message === '') {
            return $this->genericMessage();
        }

        if ($this->isSafeDomainMessage($message)) {
            return $message;
        }

        return $this->genericMessage();
    }

    private function isSafeDomainMessage(string $message): bool
    {
        $safeMessages = [
            'Preview sheet không hợp lệ để persist.',
            'Không thể mở workbook Excel đã tải lên.',
            'Không tìm thấy cấu trúc workbook trong file Excel.',
            'Không thể đọc danh sách sheet trong workbook.',
            'Không thể truy xuất danh sách sheet trong workbook.',
            'Batch import chưa có workbook boundary để xác định các sheet cần parse.',
            'Sheet không thuộc contract parser của aggregator.',
            'Sheet không thuộc contract persistence parsed record.',
            'Parsed record phải có customerCode.',
            'Parsed record phải có rowNumber hợp lệ.',
            'Parsed record phải có parsedPayload dạng mảng.',
            'Aggregated record phải có customerCode.',
            'Aggregated record phải có customerType.',
            'Aggregated record phải có sourceSheets dạng mảng.',
            'Không thể xác định sheet Khoán NPP trong workbook.',
            'Workbook không có sheet Khoán NPP để preview.',
            'Không thể đọc dữ liệu sheet Khoán NPP từ workbook.',
            'Sheet Khoán NPP không có dữ liệu để preview.',
            'Không thể xác định sheet Tổng hợp trong workbook.',
            'Workbook không có sheet Tổng hợp để preview.',
            'Không thể đọc dữ liệu sheet Tổng hợp từ workbook.',
            'Sheet Tổng hợp không có dữ liệu để preview.',
            'Không thể xác định sheet Key Account trong workbook.',
            'Workbook không có sheet Key Account để preview.',
            'Không thể đọc dữ liệu sheet Key Account từ workbook.',
            'Sheet Key Account không có dữ liệu để preview.',
            'Không thể xác định sheet Cám cá trong workbook.',
            'Workbook không có sheet Cám cá để preview.',
            'Không thể đọc dữ liệu sheet Cám cá từ workbook.',
            'Sheet Cám cá không có dữ liệu để preview.',
        ];

        return in_array($message, $safeMessages, true);
    }

    private function genericMessage(): string
    {
        return 'Hệ thống không thể xử lý file Excel của đợt nhập này. Vui lòng kiểm tra lại cấu trúc 4 sheet import và dữ liệu đầu vào trước khi thử lại.';
    }
}
