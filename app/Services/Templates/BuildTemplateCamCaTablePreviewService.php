<?php

namespace App\Services\Templates;

use App\Models\ImportBatch;
use App\Models\MailTemplate;

class BuildTemplateCamCaTablePreviewService
{
    /**
     * @var array<int, list<string>>
     */
    private array $parsedValueHeadersCache = [];

    public function __construct(
        private readonly BuildTemplatePreviewSampleService $buildTemplatePreviewSampleService,
        private readonly ResolveTemplateCanvasSectionService $resolveTemplateCanvasSectionService,
        private readonly BuildRenderedCamCaRowsService $buildRenderedCamCaRowsService,
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

        $section = $this->resolveTemplateCanvasSectionService->resolve($mailTemplate, 'cam-ca-table');

        if (! is_array($section)) {
            return null;
        }

        $sample = $this->buildTemplatePreviewSampleService->build('camCa', $previewBatchId, $aggregatedRecordId);

        if (! $sample) {
            return null;
        }

        $camCa = $sample['aggregatedPayload']['camCa'] ?? null;

        if (! is_array($camCa)) {
            return null;
        }

        $valueMap = $this->buildValueMap($camCa);
        $valueMap = $this->mergeParsedColumnHeadersIntoValueMap($sample['batchId'] ?? null, $valueMap);
        $rendered = $this->buildRenderedCamCaRowsService->build(
            $section['rows'] ?? [],
            $valueMap,
            array_values(array_filter($camCa['programItems'] ?? [], static fn (mixed $item): bool => is_array($item))),
            (string) ($camCa['grandTotal'] ?? ''),
            (string) ($camCa['totalInWords'] ?? ''),
        );

        return [
            'title' => sprintf('Chiết khấu cám cá tháng %s', $sample['month']),
            'sourceSheet' => 'Cám cá',
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
                'programItems' => array_values(array_filter($camCa['programItems'] ?? [], static fn (mixed $item): bool => is_array($item))),
                'grandTotal' => (string) ($camCa['grandTotal'] ?? ''),
                'totalInWords' => (string) ($camCa['totalInWords'] ?? ''),
            ],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, valuePreview: string}>
     */
    public function buildBindingOptions(?int $previewBatchId = null, ?int $aggregatedRecordId = null): array
    {
        $sample = $this->buildTemplatePreviewSampleService->build('camCa', $previewBatchId, $aggregatedRecordId);

        if (! $sample) {
            return [];
        }

        $valueMap = is_array($sample['aggregatedPayload']['camCa'] ?? null)
            ? $this->buildValueMap($sample['aggregatedPayload']['camCa'])
            : [];
        $valueMap = $this->mergeParsedColumnHeadersIntoValueMap($sample['batchId'] ?? null, $valueMap);

        return collect($valueMap)
            ->map(fn (string $valuePreview, string $key): array => [
                'key' => $key,
                'label' => $key,
                'valuePreview' => $valuePreview,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $camCa
     * @return array<string, string>
     */
    private function buildValueMap(array $camCa): array
    {
        $map = [
            'Tổng sản lượng' => (string) ($camCa['totalQuantity'] ?? ''),
            'Doanh thu' => (string) ($camCa['revenue'] ?? ''),
            'Tiền chiết khấu theo Hóa đơn' => (string) ($camCa['invoiceDiscount'] ?? ''),
            'Chiết khấu khác ( Không thể hiện trên hóa đơn)' => (string) ($camCa['otherDiscount'] ?? ''),
        ];

        foreach (($camCa['discreteItems'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $map[$label] = (string) ($item['value'] ?? '');
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

        $batchKey = (int) $batchId;

        if (array_key_exists($batchKey, $this->parsedValueHeadersCache)) {
            return $this->parsedValueHeadersCache[$batchKey];
        }

        $importBatch = ImportBatch::query()->find($batchKey);

        if (! $importBatch) {
            return $this->parsedValueHeadersCache[$batchKey] = [];
        }

        $sheetPreview = $importBatch->workbook_summary['sheetPreviews']['Cám cá'] ?? null;

        if (! is_array($sheetPreview)) {
            return $this->parsedValueHeadersCache[$batchKey] = [];
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

        return $this->parsedValueHeadersCache[$batchKey] = array_values(array_unique([...$fixedHeaders, ...$discreteHeaders]));
    }

    /**
     * @param  array<string, string>  $valueMap
     * @return array<string, string>
     */
    private function mergeParsedColumnHeadersIntoValueMap(mixed $batchId, array $valueMap): array
    {
        foreach ($this->resolveParsedValueHeaders($batchId) as $header) {
            if (! array_key_exists($header, $valueMap)) {
                $valueMap[$header] = '';
            }
        }

        return $valueMap;
    }
}
