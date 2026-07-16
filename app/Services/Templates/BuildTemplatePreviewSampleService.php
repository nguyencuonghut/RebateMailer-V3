<?php

namespace App\Services\Templates;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use Illuminate\Support\Collection;

class BuildTemplatePreviewSampleService
{
    /**
     * @var array<string, Collection<int, ImportBatchAggregatedRecord>>
     */
    private array $eligibleRecordsCache = [];

    /**
     * @var array<string, ImportBatchAggregatedRecord|null>
     */
    private array $selectedRecordCache = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $samplePayloadCache = [];

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
            $selectedRecord = $this->findSelectedRecord($aggregatedRecordId, $previewBatchId);

            if ($selectedRecord && $this->recordHasSource($selectedRecord, $requiredSource)) {
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
        $batches = ImportBatch::query()
            ->whereIn('status', $this->readyStatuses())
            ->whereHas('aggregatedRecords')
            ->withCount('aggregatedRecords')
            ->orderByDesc('id')
            ->get();

        $firstRecordIds = ImportBatchAggregatedRecord::query()
            ->selectRaw('MIN(id) as id')
            ->whereIn('import_batch_id', $batches->pluck('id')->all())
            ->groupBy('import_batch_id')
            ->pluck('id')
            ->filter()
            ->values();

        $firstRecordsByBatch = ImportBatchAggregatedRecord::query()
            ->whereKey($firstRecordIds)
            ->get()
            ->keyBy('import_batch_id');

        return $batches
            ->map(function (ImportBatch $batch) use ($firstRecordsByBatch): array {
                $firstRecord = $firstRecordsByBatch->get($batch->getKey());
                $payload = is_array($firstRecord?->aggregated_payload) ? $firstRecord->aggregated_payload : [];
                $month = $this->resolveField($payload, 'month');

                return [
                    'batchId' => $batch->getKey(),
                    'batchCode' => (string) $batch->batch_code,
                    'batchName' => (string) $batch->name,
                    'month' => $month,
                    'recordCount' => (int) $batch->aggregated_records_count,
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
        if ($requiredSource !== null) {
            $eligibleRecords = $this->eligibleRecords($requiredSource);

            if ($previewBatchId !== null && $eligibleRecords->contains(
                fn (ImportBatchAggregatedRecord $record): bool => (int) $record->import_batch_id === $previewBatchId,
            )) {
                return $previewBatchId;
            }

            return $eligibleRecords->first()?->import_batch_id;
        }

        if ($previewBatchId !== null && $this->hasEligibleRecordsForBatch($previewBatchId)) {
            return $previewBatchId;
        }

        return $this->firstEligibleRecord()?->import_batch_id;
    }

    public function rememberSelectedRecord(ImportBatchAggregatedRecord $record): void
    {
        $record->loadMissing('importBatch');

        foreach ([
            '*|'.$record->getKey(),
            $record->import_batch_id.'|'.$record->getKey(),
        ] as $cacheKey) {
            $this->selectedRecordCache[$cacheKey] = $record;
        }
    }

    /**
     * @return Collection<int, ImportBatchAggregatedRecord>
     */
    private function eligibleRecords(?string $requiredSource = null, ?int $previewBatchId = null): Collection
    {
        $cacheKey = ($requiredSource ?? '*').'|'.($previewBatchId ?? '*');

        if (array_key_exists($cacheKey, $this->eligibleRecordsCache)) {
            return $this->eligibleRecordsCache[$cacheKey];
        }

        return $this->eligibleRecordsCache[$cacheKey] = ImportBatchAggregatedRecord::query()
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

    private function findSelectedRecord(int $aggregatedRecordId, ?int $previewBatchId = null): ?ImportBatchAggregatedRecord
    {
        $cacheKey = ($previewBatchId ?? '*').'|'.$aggregatedRecordId;

        if (array_key_exists($cacheKey, $this->selectedRecordCache)) {
            return $this->selectedRecordCache[$cacheKey];
        }

        return $this->selectedRecordCache[$cacheKey] = ImportBatchAggregatedRecord::query()
            ->with('importBatch')
            ->whereKey($aggregatedRecordId)
            ->whereHas('importBatch', fn ($query) => $query->whereIn('status', $this->readyStatuses()))
            ->when($previewBatchId !== null, fn ($query) => $query->where('import_batch_id', $previewBatchId))
            ->first();
    }

    private function hasEligibleRecordsForBatch(int $previewBatchId): bool
    {
        return ImportBatchAggregatedRecord::query()
            ->where('import_batch_id', $previewBatchId)
            ->whereHas('importBatch', fn ($query) => $query->whereIn('status', $this->readyStatuses()))
            ->exists();
    }

    private function firstEligibleRecord(): ?ImportBatchAggregatedRecord
    {
        return ImportBatchAggregatedRecord::query()
            ->with('importBatch')
            ->whereHas('importBatch', fn ($query) => $query->whereIn('status', $this->readyStatuses()))
            ->orderByDesc('import_batch_id')
            ->orderBy('customer_code')
            ->first();
    }

    private function recordHasSource(ImportBatchAggregatedRecord $record, ?string $requiredSource): bool
    {
        if ($requiredSource === null) {
            return true;
        }

        return is_array($record->aggregated_payload)
            && data_get($record->aggregated_payload, $requiredSource) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSamplePayload(ImportBatchAggregatedRecord $sampleRecord): array
    {
        $recordId = (int) $sampleRecord->getKey();

        if (array_key_exists($recordId, $this->samplePayloadCache)) {
            return $this->samplePayloadCache[$recordId];
        }

        $payload = is_array($sampleRecord->aggregated_payload) ? $sampleRecord->aggregated_payload : [];
        $importBatch = $sampleRecord->importBatch;

        $month = $this->resolveField($payload, 'month');
        $customerFullName = trim((string) ($payload['customerFullName'] ?? ''));
        $address = $this->resolveField($payload, 'address');
        $feedCategory = $this->resolveField($payload, 'feedCategory');

        return $this->samplePayloadCache[$recordId] = [
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
