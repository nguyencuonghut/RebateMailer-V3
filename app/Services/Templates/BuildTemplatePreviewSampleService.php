<?php

namespace App\Services\Templates;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use Illuminate\Support\Collection;

class BuildTemplatePreviewSampleService
{
    /**
     * @return array<int, string>
     */
    private function readyStatuses(): array
    {
        return ['aggregated', 'validated_ready', 'validated_with_warnings'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(?string $requiredSource = null, ?int $previewBatchId = null, ?int $aggregatedRecordId = null): ?array
    {
        if ($aggregatedRecordId !== null) {
            $selectedRecord = $this->eligibleRecords($requiredSource, $previewBatchId)
                ->firstWhere('id', $aggregatedRecordId);

            if ($selectedRecord) {
                return $this->buildSamplePayload($selectedRecord);
            }

            return null;
        }

        $defaultRecord = $this->eligibleRecords($requiredSource, $previewBatchId)->first();

        if (! $defaultRecord) {
            return null;
        }

        return $this->buildSamplePayload($defaultRecord);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildCustomerOptions(?string $requiredSource = null, ?int $previewBatchId = null): array
    {
        return $this->eligibleRecords($requiredSource, $previewBatchId)
            ->map(function (ImportBatchAggregatedRecord $record): array {
                $payload = is_array($record->aggregated_payload) ? $record->aggregated_payload : [];
                $customerCode = trim((string) ($payload['customerCode'] ?? $record->customer_code));
                $customerFullName = trim((string) ($payload['customerFullName'] ?? ''));
                $month = $this->resolveField($payload, 'month');
                $batchCode = (string) ($record->importBatch?->batch_code ?? '');

                return [
                    'recordId' => $record->getKey(),
                    'customerCode' => $customerCode,
                    'customerFullName' => $customerFullName,
                    'label' => $this->buildCustomerLabel($customerCode, $customerFullName),
                    'batchCode' => $batchCode,
                    'month' => $month,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildBatchOptions(): array
    {
        return ImportBatch::query()
            ->whereIn('status', $this->readyStatuses())
            ->whereHas('aggregatedRecords')
            ->with(['aggregatedRecords' => fn ($query) => $query->orderBy('customer_code')])
            ->orderByDesc('id')
            ->get()
            ->map(function (ImportBatch $batch): array {
                $firstRecord = $batch->aggregatedRecords->first();
                $payload = is_array($firstRecord?->aggregated_payload) ? $firstRecord->aggregated_payload : [];
                $month = $this->resolveField($payload, 'month');

                return [
                    'batchId' => $batch->getKey(),
                    'batchCode' => (string) $batch->batch_code,
                    'batchName' => (string) $batch->name,
                    'month' => $month,
                    'recordCount' => $batch->aggregatedRecords->count(),
                    'label' => collect([
                        trim((string) $batch->batch_code),
                        trim((string) $batch->name),
                    ])->filter(fn (string $item): bool => $item !== '')->implode(' | '),
                ];
            })
            ->values()
            ->all();
    }

    public function resolveSelectedBatchId(?int $previewBatchId = null, ?string $requiredSource = null): ?int
    {
        $eligibleRecords = $this->eligibleRecords($requiredSource);

        if ($previewBatchId !== null && $eligibleRecords->contains(
            fn (ImportBatchAggregatedRecord $record): bool => (int) $record->import_batch_id === $previewBatchId,
        )) {
            return $previewBatchId;
        }

        return $eligibleRecords->first()?->import_batch_id;
    }

    /**
     * @return Collection<int, ImportBatchAggregatedRecord>
     */
    private function eligibleRecords(?string $requiredSource = null, ?int $previewBatchId = null): Collection
    {
        return ImportBatchAggregatedRecord::query()
            ->with('importBatch')
            ->whereHas('importBatch', fn ($query) => $query
                ->whereIn('status', $this->readyStatuses()))
            ->when($previewBatchId !== null, fn ($query) => $query->where('import_batch_id', $previewBatchId))
            ->orderByDesc('import_batch_id')
            ->orderBy('customer_code')
            ->get()
            ->filter(function (ImportBatchAggregatedRecord $record) use ($requiredSource): bool {
                if ($requiredSource === null) {
                    return true;
                }

                return is_array($record->aggregated_payload)
                    && data_get($record->aggregated_payload, $requiredSource) !== null;
            })
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSamplePayload(ImportBatchAggregatedRecord $sampleRecord): array
    {
        $payload = is_array($sampleRecord->aggregated_payload) ? $sampleRecord->aggregated_payload : [];
        $importBatch = $sampleRecord->importBatch;

        $month = $this->resolveField($payload, 'month');
        $customerFullName = trim((string) ($payload['customerFullName'] ?? ''));
        $address = $this->resolveField($payload, 'address');
        $feedCategory = $this->resolveField($payload, 'feedCategory');

        return [
            'recordId' => $sampleRecord->getKey(),
            'batchId' => $importBatch?->getKey(),
            'batchCode' => (string) ($importBatch?->batch_code ?? ''),
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

    private function buildCustomerLabel(string $customerCode, string $customerFullName): string
    {
        if ($customerFullName === '') {
            return $customerCode;
        }

        if ($customerCode !== '' && str_starts_with($customerFullName, $customerCode)) {
            return $customerFullName;
        }

        return trim($customerCode.' - '.$customerFullName, ' -');
    }
}
