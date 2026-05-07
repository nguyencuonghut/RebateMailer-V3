<?php

namespace App\Services\Templates;

use App\Models\ImportBatch;

class BuildTemplatePreviewSampleService
{
    /**
     * @return array<string, mixed>|null
     */
    public function build(?string $requiredSource = null): ?array
    {
        $batches = ImportBatch::query()
            ->whereIn('status', ['aggregated', 'validated_ready', 'validated_with_warnings'])
            ->whereHas('aggregatedRecords')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();

        foreach ($batches as $importBatch) {
            $sampleRecord = $importBatch->aggregatedRecords()
                ->orderBy('customer_code')
                ->get()
                ->first(function ($record) use ($requiredSource): bool {
                    if ($requiredSource === null) {
                        return true;
                    }

                    return is_array($record->aggregated_payload)
                        && data_get($record->aggregated_payload, $requiredSource) !== null;
                });

            if (! $sampleRecord) {
                continue;
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
                'aggregatedPayload' => $payload,
                'variables' => [
                    '{{tháng}}' => $month,
                    '{{mã & tên khách hàng}}' => $customerFullName,
                    '{{địa chỉ}}' => $address,
                    '{{thức ăn chăn nuôi}}' => $feedCategory,
                ],
            ];
        }

        return null;
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
