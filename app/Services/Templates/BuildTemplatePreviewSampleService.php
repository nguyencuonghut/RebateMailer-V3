<?php

namespace App\Services\Templates;

use App\Models\ImportBatch;

class BuildTemplatePreviewSampleService
{
    /**
     * @return array<string, mixed>|null
     */
    public function build(): ?array
    {
        $importBatch = ImportBatch::query()
            ->whereIn('status', ['aggregated', 'validated_ready', 'validated_with_warnings'])
            ->whereHas('aggregatedRecords')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        if (! $importBatch) {
            return null;
        }

        $sampleRecord = $importBatch->aggregatedRecords()
            ->orderBy('customer_code')
            ->first();

        if (! $sampleRecord) {
            return null;
        }

        $payload = $sampleRecord->aggregated_payload;

        $month = $this->resolveField($payload, 'month');
        $customerFullName = trim((string) ($payload['customerFullName'] ?? ''));
        $address = $this->resolveField($payload, 'address');
        $feedCategory = $this->resolveField($payload, 'feedCategory');

        return [
            'batchId' => $importBatch->getKey(),
            'batchCode' => $importBatch->batch_code,
            'customerCode' => (string) ($payload['customerCode'] ?? ''),
            'customerFullName' => $customerFullName,
            'month' => $month,
            'address' => $address,
            'feedCategory' => $feedCategory,
            'variables' => [
                '{{tháng}}' => $month,
                '{{mã & tên khách hàng}}' => $customerFullName,
                '{{địa chỉ}}' => $address,
                '{{thức ăn chăn nuôi}}' => $feedCategory,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveField(array $payload, string $field): string
    {
        foreach (['tongHop', 'khoanNpp', 'camCa', 'keyAccount'] as $sourceKey) {
            $value = trim((string) data_get($payload, $sourceKey.'.'.$field, ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
