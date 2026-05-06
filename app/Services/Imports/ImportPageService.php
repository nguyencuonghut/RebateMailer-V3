<?php

namespace App\Services\Imports;

class ImportPageService
{
    /**
     * @return array<string, mixed>
     */
    public function getIndexPageData(bool $canManageImports): array
    {
        return [
            'title' => 'Import dữ liệu',
            'description' => 'Khu vực tiếp nhận file Excel chiết khấu hàng tháng và chuẩn bị cho luồng preview dữ liệu rebate.',
            'currentSlice' => [
                'code' => '1.3',
                'label' => 'Parser - Sheet Tổng hợp',
            ],
            'canManageImports' => $canManageImports,
            'uploadPolicy' => [
                'acceptedExtension' => '.xlsx',
                'acceptedMimeLabel' => 'Excel Workbook (.xlsx)',
            ],
            'acceptedSheets' => [
                'Tổng hợp',
                'Khoán NPP',
                'Cám cá',
                'Key Account',
            ],
            'nextSlice' => [
                'code' => '1.4',
                'label' => 'Parser - Sheet Khoán NPP',
            ],
            'analysisPrep' => [
                'actionLabel' => 'Đọc cấu trúc workbook',
                'helperText' => 'Sau khi có receipt upload, bạn có thể đọc cấu trúc workbook rồi mở preview riêng cho sheet Tổng hợp để kiểm tra parser Khách thường ngay trên UI.',
                'readyTitle' => 'Parser sheet Tổng hợp đã sẵn sàng',
                'readyDescription' => 'Workbook đã được phân tích thành công. Khu vực bên dưới có thể mở preview riêng cho sheet Tổng hợp với cột cố định, cột động và dữ liệu mẫu đã chuẩn hóa.',
                'statusLabel' => 'Chưa đọc workbook',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Đọc workbook thành công',
                    'detail' => 'Workbook boundary đã sẵn sàng. Bạn có thể mở preview parser cho sheet Tổng hợp.',
                    'life' => 4000,
                ],
            ],
            'toast' => [
                'severity' => 'info',
                'summary' => 'Khu vực import đã sẵn sàng',
                'detail' => 'Bạn có thể tải file Excel lên, đọc workbook và mở preview parser cho sheet Tổng hợp.',
                'life' => 4000,
            ],
        ];
    }
}
