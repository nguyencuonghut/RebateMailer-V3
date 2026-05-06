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
                'code' => '1.5',
                'label' => 'Parser - Sheet Cám cá',
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
                'code' => '1.6',
                'label' => 'Parser - Sheet Key Account',
            ],
            'analysisPrep' => [
                'actionLabel' => 'Đọc cấu trúc workbook',
                'helperText' => 'Sau khi có receipt upload, bạn có thể đọc cấu trúc workbook rồi mở preview riêng cho sheet Cám cá để kiểm tra cột rời rạc và các cặp CT | Thành tiền ngay trên UI.',
                'readyTitle' => 'Parser sheet Cám cá đã sẵn sàng',
                'readyDescription' => 'Workbook đã được phân tích thành công. Khu vực bên dưới có thể mở preview riêng cho sheet Cám cá với cột rời rạc và các cặp CT | Thành tiền đã được tách riêng.',
                'statusLabel' => 'Chưa đọc workbook',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Đọc workbook thành công',
                    'detail' => 'Workbook boundary đã sẵn sàng. Bạn có thể mở preview parser cho sheet Cám cá.',
                    'life' => 4000,
                ],
            ],
            'toast' => [
                'severity' => 'info',
                'summary' => 'Khu vực import đã sẵn sàng',
                'detail' => 'Bạn có thể tải file Excel lên, đọc workbook và mở preview parser cho sheet Cám cá.',
                'life' => 4000,
            ],
        ];
    }
}
