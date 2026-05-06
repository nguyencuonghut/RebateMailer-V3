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
                'code' => '1.2-E',
                'label' => 'Đếm số dòng dữ liệu và nhận diện sheet rỗng',
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
                'code' => '1.2-F',
                'label' => 'Chuẩn hóa preview workbook boundary trên UI',
            ],
            'analysisPrep' => [
                'actionLabel' => 'Đọc cấu trúc workbook',
                'helperText' => 'Sau khi có receipt upload, bạn có thể đọc cấu trúc workbook để xem danh sách sheet, header line 1 và số dòng dữ liệu của từng sheet import hợp lệ.',
                'readyTitle' => 'Đọc workbook, header và số dòng dữ liệu',
                'readyDescription' => 'Workbook đã được phân tích thành công. Khu vực preview bên dưới đang hiển thị sheet hợp lệ, header line 1, số dòng dữ liệu và trạng thái rỗng của từng sheet import.',
                'statusLabel' => 'Chưa đọc workbook',
                'toast' => [
                    'severity' => 'success',
                    'summary' => 'Đọc workbook thành công',
                    'detail' => 'Hệ thống đã đọc được danh sách sheet, header line 1 và số dòng dữ liệu của các sheet import hợp lệ.',
                    'life' => 4000,
                ],
            ],
            'toast' => [
                'severity' => 'info',
                'summary' => 'Khu vực import đã sẵn sàng',
                'detail' => 'Bạn có thể tải file Excel lên và xem ngay số dòng dữ liệu của các sheet import hợp lệ.',
                'life' => 4000,
            ],
        ];
    }
}
