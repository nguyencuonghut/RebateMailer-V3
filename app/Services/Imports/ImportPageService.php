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
                'code' => '1.7',
                'label' => 'Aggregator',
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
                'code' => '1.8',
                'label' => 'Validation',
            ],
            'analysisPrep' => [
                'actionLabel' => 'Đọc cấu trúc workbook',
                'helperText' => 'Sau khi có receipt upload, bạn có thể đọc cấu trúc workbook rồi mở preview aggregator để kiểm tra dữ liệu đã được gom theo Mã số ngay trên UI.',
                'readyTitle' => 'Aggregator đã sẵn sàng',
                'readyDescription' => 'Workbook đã được phân tích thành công. Khu vực bên dưới có thể mở preview dữ liệu hợp nhất giữa Khách thường và Key Account theo Mã số.',
                'statusLabel' => 'Chưa đọc workbook',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Đọc workbook thành công',
                    'detail' => 'Workbook boundary đã sẵn sàng. Bạn có thể mở preview aggregator theo Mã số.',
                    'life' => 4000,
                ],
            ],
            'toast' => [
                'severity' => 'info',
                'summary' => 'Khu vực import đã sẵn sàng',
                'detail' => 'Bạn có thể tải file Excel lên, đọc workbook và mở preview aggregator theo Mã số.',
                'life' => 4000,
            ],
        ];
    }
}
