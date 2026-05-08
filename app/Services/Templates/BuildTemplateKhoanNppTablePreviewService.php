<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;

class BuildTemplateKhoanNppTablePreviewService
{
    public function __construct(
        private readonly BuildTemplatePreviewSampleService $buildTemplatePreviewSampleService,
        private readonly ResolveTemplateCanvasSectionService $resolveTemplateCanvasSectionService,
        private readonly BuildRenderedKhoanNppRowsService $buildRenderedKhoanNppRowsService,
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

        $section = $this->resolveTemplateCanvasSectionService->resolve($mailTemplate, 'khoan-npp-table');

        if (! is_array($section)) {
            return null;
        }

        $sample = $this->buildTemplatePreviewSampleService->build('khoanNpp', $previewBatchId, $aggregatedRecordId);

        if (! $sample) {
            return null;
        }

        $khoanNpp = $sample['aggregatedPayload']['khoanNpp'] ?? null;

        if (! is_array($khoanNpp)) {
            return null;
        }

        $rendered = $this->buildRenderedKhoanNppRowsService->build($section['rows'] ?? [], $khoanNpp);

        return [
            'title' => sprintf('Chương trình khoán đặc biệt tháng %s', $sample['month']),
            'sourceSheet' => 'Khoán NPP',
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
                'programItems' => array_values(array_filter(
                    $khoanNpp['programItems'] ?? [],
                    static fn (mixed $item): bool => is_array($item),
                )),
                'grandTotal' => (string) ($khoanNpp['grandTotal'] ?? ''),
                'totalInWords' => (string) ($khoanNpp['totalInWords'] ?? ''),
            ],
        ];
    }
}
