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
                'code' => '1.2-I',
                'label' => 'Chốt smoke test cho Workbook Boundary',
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
                'code' => '1.3',
                'label' => 'Parser - Sheet Tổng hợp',
            ],
            'analysisPrep' => [
                'actionLabel' => 'Đọc cấu trúc workbook',
                'helperText' => 'Sau khi có receipt upload, bạn có thể đọc cấu trúc workbook trên boundary đã ổn định và verify được bằng smoke test trước khi đi sang parser sheet Tổng hợp.',
                'readyTitle' => 'Workbook Boundary đã sẵn sàng bàn giao cho Task 1.3',
                'readyDescription' => 'Workbook đã được phân tích thành công. Boundary hiện tại đã có smoke test cho happy path và failure path, đủ an toàn để chuyển sang parser sheet Tổng hợp.',
                'statusLabel' => 'Chưa đọc workbook',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Đọc workbook thành công',
                    'detail' => 'Hệ thống đã khóa xong Workbook Boundary và sẵn sàng chuyển sang parser sheet Tổng hợp.',
                    'life' => 4000,
                ],
            ],
            'toast' => [
                'severity' => 'info',
                'summary' => 'Khu vực import đã sẵn sàng',
                'detail' => 'Bạn có thể tải file Excel lên và xác nhận Workbook Boundary đã sẵn sàng để chuyển sang Task 1.3.',
                'life' => 4000,
            ],
        ];
    }
}
