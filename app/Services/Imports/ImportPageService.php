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
                'code' => '1.6',
                'label' => 'Parser - Sheet Key Account',
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
                'code' => '1.7',
                'label' => 'Aggregator',
            ],
            'analysisPrep' => [
                'actionLabel' => 'Đọc cấu trúc workbook',
                'helperText' => 'Sau khi có receipt upload, bạn có thể đọc cấu trúc workbook rồi mở preview riêng cho sheet Key Account để kiểm tra cột rời rạc và các block Nội dung CT | SL | đ/kg | Thành tiền ngay trên UI.',
                'readyTitle' => 'Parser sheet Key Account đã sẵn sàng',
                'readyDescription' => 'Workbook đã được phân tích thành công. Khu vực bên dưới có thể mở preview riêng cho sheet Key Account với nhóm cột rời rạc và các block chương trình đã được chuẩn hóa.',
                'statusLabel' => 'Chưa đọc workbook',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Đọc workbook thành công',
                    'detail' => 'Workbook boundary đã sẵn sàng. Bạn có thể mở preview parser cho sheet Key Account.',
                    'life' => 4000,
                ],
            ],
            'toast' => [
                'severity' => 'info',
                'summary' => 'Khu vực import đã sẵn sàng',
                'detail' => 'Bạn có thể tải file Excel lên, đọc workbook và mở preview parser cho sheet Key Account.',
                'life' => 4000,
            ],
        ];
    }
}
