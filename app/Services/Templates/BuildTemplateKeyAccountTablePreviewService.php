<?php

namespace App\Services\Templates;

use App\Models\ImportBatch;
use App\Models\MailTemplate;

class BuildTemplateKeyAccountTablePreviewService
{
    public function __construct(
        private readonly BuildTemplatePreviewSampleService $buildTemplatePreviewSampleService,
        private readonly ResolveTemplateCanvasSectionService $resolveTemplateCanvasSectionService,
        private readonly BuildRenderedKeyAccountRowsService $buildRenderedKeyAccountRowsService,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(?MailTemplate $mailTemplate, ?int $previewBatchId = null, ?int $aggregatedRecordId = null): ?array
    {
        if (! $mailTemplate) {
            return null;
        }

        $section = $this->resolveTemplateCanvasSectionService->resolve($mailTemplate, 'key-account-table');

        if (! is_array($section)) {
            return null;
        }

        $sample = $this->buildTemplatePreviewSampleService->build('keyAccount', $previewBatchId, $aggregatedRecordId);

        if (! $sample) {
            return null;
        }

        $keyAccount = $sample['aggregatedPayload']['keyAccount'] ?? null;

        if (! is_array($keyAccount)) {
            return null;
        }

        $valueMap = $this->buildValueMap($keyAccount);
        $valueMap = $this->mergeParsedColumnHeadersIntoValueMap($sample['batchId'] ?? null, $valueMap);
        $rendered = $this->buildRenderedKeyAccountRowsService->build(
            $section['rows'] ?? [],
            $valueMap,
            array_values(array_filter($keyAccount['programItems'] ?? [], static fn (mixed $item): bool => is_array($item))),
            (string) ($keyAccount['grandTotal'] ?? ''),
            (string) ($keyAccount['totalInWords'] ?? ''),
        );

        return [
            'title' => sprintf('Chiết khấu tháng %s', $sample['month']),
            'sourceSheet' => 'Key Account',
            'rows' => $rendered['rows'],
            'errors' => $rendered['errors'],
            'sample' => [
                'recordId' => $sample['recordId'],
                'batchId' => $sample['batchId'],
                'batchCode' => $sample['batchCode'],
                'customerCode' => $sample['customerCode'],
                'customerFullName' => $sample['customerFullName'],
                'month' => $sample['month'],
            ],
            'sampleData' => [
                'programItems' => array_values(array_filter($keyAccount['programItems'] ?? [], static fn (mixed $item): bool => is_array($item))),
                'grandTotal' => (string) ($keyAccount['grandTotal'] ?? ''),
                'totalInWords' => (string) ($keyAccount['totalInWords'] ?? ''),
            ],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, valuePreview: string, quantityPreview: string, supportRatePreview: string, amountPreview: string, defaultValueColumn: string}>
     */
    public function buildBindingOptions(?int $previewBatchId = null, ?int $aggregatedRecordId = null): array
    {
        $sample = $this->buildTemplatePreviewSampleService->build('keyAccount', $previewBatchId, $aggregatedRecordId);

        if (! $sample) {
            return [];
        }

        $valueMap = is_array($sample['aggregatedPayload']['keyAccount'] ?? null)
            ? $this->buildValueMap($sample['aggregatedPayload']['keyAccount'])
            : [];
        $valueMap = $this->mergeParsedColumnHeadersIntoValueMap($sample['batchId'] ?? null, $valueMap);

        return collect($valueMap)
            ->map(fn (array $entry, string $key): array => [
                'key' => $key,
                'label' => $key,
                'valuePreview' => (string) ($entry[$entry['defaultValueColumn']] ?? ''),
                'quantityPreview' => (string) ($entry['quantity'] ?? ''),
                'supportRatePreview' => (string) ($entry['supportRate'] ?? ''),
                'amountPreview' => (string) ($entry['amount'] ?? ''),
                'defaultValueColumn' => (string) ($entry['defaultValueColumn'] ?? 'amount'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $keyAccount
     * @return array<string, array{quantity: string, supportRate: string, amount: string, defaultValueColumn: string}>
     */
    private function buildValueMap(array $keyAccount): array
    {
        $map = [
            'Tổng sản lượng' => [
                'quantity' => (string) ($keyAccount['totalQuantity'] ?? ''),
                'supportRate' => '',
                'amount' => '',
                'defaultValueColumn' => 'quantity',
            ],
            'Doanh thu' => [
                'quantity' => '',
                'supportRate' => '',
                'amount' => (string) ($keyAccount['revenue'] ?? ''),
                'defaultValueColumn' => 'amount',
            ],
            'Chiết khấu theo hóa đơn' => [
                'quantity' => '',
                'supportRate' => '',
                'amount' => (string) ($keyAccount['invoiceDiscount'] ?? ''),
                'defaultValueColumn' => 'amount',
            ],
        ];

        foreach (($keyAccount['discreteItems'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $map[$label] = [
                'quantity' => '',
                'supportRate' => '',
                'amount' => (string) ($item['value'] ?? ''),
                'defaultValueColumn' => 'amount',
            ];
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private function resolveParsedValueHeaders(mixed $batchId): array
    {
        if (! is_numeric($batchId)) {
            return [];
        }

        $importBatch = ImportBatch::query()->find((int) $batchId);

        if (! $importBatch) {
            return [];
        }

        $sheetPreview = $importBatch->workbook_summary['sheetPreviews']['Key Account'] ?? null;

        if (! is_array($sheetPreview)) {
            return [];
        }

        $excludedFixedHeaders = [
            'STT',
            'Tháng',
            'Mã số',
            'Mã & tên khách hàng',
            'Email',
            'Địa chỉ',
            'Thức ăn chăn nuôi',
            'Tổng cộng',
            'Bằng chữ',
        ];

        $fixedHeaders = array_values(array_filter(
            $sheetPreview['fixedHeaders'] ?? [],
            static fn (mixed $header): bool => is_string($header)
                && trim($header) !== ''
                && ! in_array($header, $excludedFixedHeaders, true),
        ));

        $discreteHeaders = array_values(array_filter(
            $sheetPreview['discreteHeaders'] ?? [],
            static fn (mixed $header): bool => is_string($header) && trim($header) !== '',
        ));

        return array_values(array_unique([...$fixedHeaders, ...$discreteHeaders]));
    }

    /**
     * @param  array<string, array{quantity: string, supportRate: string, amount: string, defaultValueColumn: string}>  $valueMap
     * @return array<string, array{quantity: string, supportRate: string, amount: string, defaultValueColumn: string}>
     */
    private function mergeParsedColumnHeadersIntoValueMap(mixed $batchId, array $valueMap): array
    {
        foreach ($this->resolveParsedValueHeaders($batchId) as $header) {
            if (! array_key_exists($header, $valueMap)) {
                $valueMap[$header] = [
                    'quantity' => '',
                    'supportRate' => '',
                    'amount' => '',
                    'defaultValueColumn' => $header === 'Tổng sản lượng' ? 'quantity' : 'amount',
                ];
            }
        }

        return $valueMap;
    }
}
