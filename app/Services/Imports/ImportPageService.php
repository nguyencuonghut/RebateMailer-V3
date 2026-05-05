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
                'code' => '1.2-C',
                'label' => 'Khóa boundary đúng 4 sheet import hợp lệ',
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
                'code' => '1.2-D',
                'label' => 'Đọc header line 1 cho từng sheet hợp lệ',
            ],
            'analysisPrep' => [
                'actionLabel' => 'Đọc cấu trúc workbook',
                'helperText' => 'Sau khi có receipt upload, bạn có thể đọc cấu trúc workbook để đối chiếu danh sách sheet thực tế với đúng 4 sheet import hợp lệ.',
                'readyTitle' => 'Đọc cấu trúc workbook và khóa boundary 4 sheet',
                'readyDescription' => 'Workbook đã được phân tích thành công. Khu vực preview bên dưới đang hiển thị sheet hợp lệ, sheet thiếu và sheet ngoài contract import.',
                'statusLabel' => 'Chưa đọc workbook',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Đọc workbook thành công',
                    'detail' => 'Hệ thống đã đối chiếu workbook với đúng 4 sheet import hợp lệ.',
                    'life' => 4000,
                ],
            ],
            'toast' => [
                'severity' => 'info',
                'summary' => 'Khu vực import đã sẵn sàng',
                'detail' => 'Bạn có thể tải file Excel lên và kiểm tra workbook có khớp đúng 4 sheet import hợp lệ hay không.',
                'life' => 4000,
            ],
        ];
    }
}
