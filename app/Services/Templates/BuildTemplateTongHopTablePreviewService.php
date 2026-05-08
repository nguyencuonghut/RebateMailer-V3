<?php

namespace App\Services\Templates;

use App\Models\ImportBatch;
use App\Models\MailTemplate;

class BuildTemplateTongHopTablePreviewService
{
    public function __construct(
        private readonly BuildTemplatePreviewSampleService $buildTemplatePreviewSampleService,
        private readonly GenerateTemplateRowNumberingService $generateTemplateRowNumberingService,
        private readonly ResolveTemplateCanvasSectionService $resolveTemplateCanvasSectionService,
        private readonly BuildRenderedTongHopRowsService $buildRenderedTongHopRowsService,
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

        $section = $this->resolveTongHopSection($mailTemplate);

        if ($section === null) {
            return null;
        }

        $sample = $this->buildTemplatePreviewSampleService->build('tongHop', $previewBatchId, $aggregatedRecordId);

        if (! $sample) {
            return null;
        }

        $tongHop = $sample['aggregatedPayload']['tongHop'] ?? null;

        if (! is_array($tongHop)) {
            return null;
        }

        $rows = $this->generateTemplateRowNumberingService->generate($section['rows'] ?? []);
        $valueMap = $this->buildValueMap($tongHop);
        $valueMap = $this->mergeParsedColumnHeadersIntoValueMap($sample['batchId'] ?? null, $valueMap);
        $rendered = $this->buildRenderedTongHopRowsService->build($rows, $valueMap);

        return [
            'title' => sprintf('Chế độ tháng %s', $sample['month']),
            'sourceSheet' => 'Tổng hợp',
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
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, valuePreview: string}>
     */
    public function buildBindingOptions(?int $previewBatchId = null, ?int $aggregatedRecordId = null): array
    {
        $sample = $this->buildTemplatePreviewSampleService->build('tongHop', $previewBatchId, $aggregatedRecordId);

        if (! $sample) {
            return [];
        }

        $columnHeaders = $this->resolveParsedColumnHeaders($sample['batchId'] ?? null);

        if ($columnHeaders === []) {
            $tongHop = $sample['aggregatedPayload']['tongHop'] ?? null;

            if (! is_array($tongHop)) {
                return [];
            }

            $columnHeaders = array_keys($this->buildValueMap($tongHop));
        }

        $valueMap = is_array($sample['aggregatedPayload']['tongHop'] ?? null)
            ? $this->buildValueMap($sample['aggregatedPayload']['tongHop'])
            : [];
        $valueMap = $this->mergeParsedColumnHeadersIntoValueMap($sample['batchId'] ?? null, $valueMap);

        $optionKeys = array_values(array_unique([
            ...$columnHeaders,
            ...array_keys($valueMap),
        ]));

        return collect($optionKeys)
            ->filter(static fn (mixed $header): bool => is_string($header) && trim($header) !== '')
            ->values()
            ->map(fn (string $header): array => [
                'key' => $header,
                'label' => $header,
                'valuePreview' => (string) ($valueMap[$header] ?? ''),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveTongHopSection(MailTemplate $mailTemplate): ?array
    {
        return $this->resolveTemplateCanvasSectionService->resolve($mailTemplate, 'tong-hop-table');
    }

    /**
     * @param  array<string, mixed>  $tongHop
     * @return array<string, string>
     */
    private function buildValueMap(array $tongHop): array
    {
        $map = [
            'STT' => (string) ($tongHop['stt'] ?? ''),
            'Tháng' => (string) ($tongHop['month'] ?? ''),
            'Mã số' => (string) ($tongHop['customerCode'] ?? ''),
            'Mã & tên khách hàng' => (string) ($tongHop['customerFullName'] ?? ''),
            'Tên khách hàng' => (string) ($tongHop['customerName'] ?? ''),
            'Email' => (string) ($tongHop['email'] ?? ''),
            'Địa chỉ' => (string) ($tongHop['address'] ?? ''),
            'Thức ăn chăn nuôi' => (string) ($tongHop['feedCategory'] ?? ''),
            'Tổng sản lượng (gồm cám thủy sản)' => (string) ($tongHop['totalQuantity'] ?? ''),
            'Doanh thu (gồm cám thủy sản)' => (string) ($tongHop['revenue'] ?? ''),
            'Tiền chiết khấu theo Hóa đơn' => (string) ($tongHop['invoiceDiscount'] ?? ''),
            'Thưởng cam kết tháng' => (string) ($tongHop['commitmentBonus'] ?? ''),
            'Chiết khấu cám cá' => (string) ($tongHop['fishFeedDiscount'] ?? ''),
            'Chiết khấu khác ( Không thể hiện trên hóa đơn)' => (string) ($tongHop['otherDiscount'] ?? ''),
            'Tổng cộng' => (string) ($tongHop['grandTotal'] ?? ''),
            'Cộng' => (string) ($tongHop['grandTotal'] ?? ''),
            'Bằng chữ' => (string) ($tongHop['totalInWords'] ?? ''),
        ];

        foreach (($tongHop['dynamicItems'] ?? []) as $item) {
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
    private function resolveParsedColumnHeaders(mixed $batchId): array
    {
        if (! is_numeric($batchId)) {
            return [];
        }

        $importBatch = ImportBatch::query()->find((int) $batchId);

        if (! $importBatch) {
            return [];
        }

        $sheetPreview = $importBatch->workbook_summary['sheetPreviews']['Tổng hợp'] ?? null;

        if (! is_array($sheetPreview)) {
            return [];
        }

        $fixedHeaders = array_values(array_filter(
            $sheetPreview['fixedHeaders'] ?? [],
            static fn (mixed $header): bool => is_string($header) && trim($header) !== '',
        ));

        $dynamicHeaders = array_values(array_filter(
            $sheetPreview['dynamicHeaders'] ?? [],
            static fn (mixed $header): bool => is_string($header) && trim($header) !== '',
        ));

        return array_values(array_unique([...$fixedHeaders, ...$dynamicHeaders]));
    }

    /**
     * @param  array<string, string>  $valueMap
     * @return array<string, string>
     */
    private function mergeParsedColumnHeadersIntoValueMap(mixed $batchId, array $valueMap): array
    {
        foreach ($this->resolveParsedColumnHeaders($batchId) as $header) {
            if (! array_key_exists($header, $valueMap)) {
                $valueMap[$header] = '';
            }
        }

        return $valueMap;
    }
}
