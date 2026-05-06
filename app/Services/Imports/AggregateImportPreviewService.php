<?php

namespace App\Services\Imports;

class AggregateImportPreviewService
{
    public function __construct(
        private readonly ParseTongHopPreviewService $parseTongHopPreviewService,
        private readonly ParseKhoanNppPreviewService $parseKhoanNppPreviewService,
        private readonly ParseCamCaPreviewService $parseCamCaPreviewService,
        private readonly ParseKeyAccountPreviewService $parseKeyAccountPreviewService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function aggregate(string $storedPath): array
    {
        $tongHopPreview = $this->parseTongHopPreviewService->parse($storedPath);
        $khoanNppPreview = $this->parseKhoanNppPreviewService->parse($storedPath);
        $camCaPreview = $this->parseCamCaPreviewService->parse($storedPath);
        $keyAccountPreview = $this->parseKeyAccountPreviewService->parse($storedPath);

        $recordsByCustomerCode = [];

        foreach ($tongHopPreview['records'] as $record) {
            $code = $record['customerCode'];
            $recordsByCustomerCode[$code] ??= $this->makeBaseRecord($code, 'Khách thường');
            $recordsByCustomerCode[$code]['sourceSheets'][] = 'Tổng hợp';
            $recordsByCustomerCode[$code]['tongHop'] = $record;
        }

        foreach ($khoanNppPreview['records'] as $record) {
            $code = $record['customerCode'];
            $recordsByCustomerCode[$code] ??= $this->makeBaseRecord($code, 'Khách thường');
            $recordsByCustomerCode[$code]['sourceSheets'][] = 'Khoán NPP';
            $recordsByCustomerCode[$code]['khoanNpp'] = $record;
        }

        foreach ($camCaPreview['records'] as $record) {
            $code = $record['customerCode'];
            $recordsByCustomerCode[$code] ??= $this->makeBaseRecord($code, 'Khách thường');
            $recordsByCustomerCode[$code]['sourceSheets'][] = 'Cám cá';
            $recordsByCustomerCode[$code]['camCa'] = $record;
        }

        foreach ($keyAccountPreview['records'] as $record) {
            $code = $record['customerCode'];
            $recordsByCustomerCode[$code] ??= $this->makeBaseRecord($code, 'Key Account');
            $recordsByCustomerCode[$code]['customerType'] = 'Key Account';
            $recordsByCustomerCode[$code]['sourceSheets'][] = 'Key Account';
            $recordsByCustomerCode[$code]['keyAccount'] = $record;
        }

        $records = array_values(array_map(function (array $record): array {
            $record['sourceSheets'] = array_values(array_unique($record['sourceSheets']));

            return $record;
        }, $recordsByCustomerCode));

        usort($records, static fn (array $left, array $right): int => strcmp($left['customerCode'], $right['customerCode']));

        $normalCustomerCount = count(array_filter(
            $records,
            static fn (array $record): bool => $record['customerType'] === 'Khách thường',
        ));
        $keyAccountCustomerCount = count(array_filter(
            $records,
            static fn (array $record): bool => $record['customerType'] === 'Key Account',
        ));

        return [
            'summary' => [
                'totalCustomerCount' => count($records),
                'normalCustomerCount' => $normalCustomerCount,
                'keyAccountCustomerCount' => $keyAccountCustomerCount,
            ],
            'records' => $records,
            'nextStep' => 'Dữ liệu đã được gom theo Mã số. Bước kế tiếp sẽ thêm validation để đánh dấu thiếu email, xung đột phân loại và các bất thường dữ liệu.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function makeBaseRecord(string $customerCode, string $customerType): array
    {
        return [
            'customerCode' => $customerCode,
            'customerType' => $customerType,
            'sourceSheets' => [],
            'tongHop' => null,
            'khoanNpp' => null,
            'camCa' => null,
            'keyAccount' => null,
        ];
    }
}
