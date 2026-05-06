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
                'code' => '1.4',
                'label' => 'Parser - Sheet Khoán NPP',
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
                'code' => '1.5',
                'label' => 'Parser - Sheet Cám cá',
            ],
            'analysisPrep' => [
                'actionLabel' => 'Đọc cấu trúc workbook',
                'helperText' => 'Sau khi có receipt upload, bạn có thể đọc cấu trúc workbook rồi mở preview riêng cho sheet Khoán NPP để kiểm tra block chương trình khoán ngay trên UI.',
                'readyTitle' => 'Parser sheet Khoán NPP đã sẵn sàng',
                'readyDescription' => 'Workbook đã được phân tích thành công. Khu vực bên dưới có thể mở preview riêng cho sheet Khoán NPP với block Nội dung CT | SL | đ/kg | Thành tiền đã chuẩn hóa.',
                'statusLabel' => 'Chưa đọc workbook',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Đọc workbook thành công',
                    'detail' => 'Workbook boundary đã sẵn sàng. Bạn có thể mở preview parser cho sheet Khoán NPP.',
                    'life' => 4000,
                ],
            ],
            'toast' => [
                'severity' => 'info',
                'summary' => 'Khu vực import đã sẵn sàng',
                'detail' => 'Bạn có thể tải file Excel lên, đọc workbook và mở preview parser cho sheet Khoán NPP.',
                'life' => 4000,
            ],
        ];
    }
}
