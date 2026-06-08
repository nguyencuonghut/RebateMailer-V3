<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;

class BuildTemplateGreetingPreviewService
{
    public function __construct(
        private readonly RenderTemplateTextPreviewService $renderTemplateTextPreviewService,
        private readonly BuildTemplatePreviewSampleService $buildTemplatePreviewSampleService,
        private readonly ResolveTemplateCanvasSectionService $resolveTemplateCanvasSectionService,
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

        $sample = $this->buildTemplatePreviewSampleService->build(null, $previewBatchId, $aggregatedRecordId);

        if (! $sample) {
            return null;
        }

        $templateText = $this->resolveGreetingTemplate($mailTemplate);
        $rendered = $this->renderTemplateTextPreviewService->render($templateText, $sample['variables']);

        return [
            'templateText' => $templateText,
            'renderedText' => $rendered['renderedText'],
            'renderedHtml' => $this->buildRenderedHtml((string) $rendered['renderedText']),
            'errors' => $rendered['errors'],
            'sample' => [
                'recordId' => $sample['recordId'],
                'batchId' => $sample['batchId'],
                'batchCode' => $sample['batchCode'],
                'customerCode' => $sample['customerCode'],
                'customerFullName' => $sample['customerFullName'],
                'month' => $sample['month'],
                'address' => $sample['address'],
                'feedCategory' => $sample['feedCategory'],
            ],
        ];
    }

    private function resolveGreetingTemplate(MailTemplate $mailTemplate): string
    {
        return trim((string) ($this->resolveTemplateCanvasSectionService->resolve($mailTemplate, 'greeting')['content'] ?? ''));
    }

    private function buildRenderedHtml(string $renderedText): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $renderedText) ?: [];

        $htmlLines = array_map(function (string $line): string {
            $trimmed = trim($line);

            if ($trimmed === '') {
                return '';
            }

            if (preg_match('/^(Kính gửi\s+)(.+)$/u', $trimmed, $matches) === 1) {
                return e($matches[1]).'<strong>'.e($matches[2]).'</strong>';
            }

            if (preg_match('/^(Địa chỉ:\s*)(.+)$/u', $trimmed, $matches) === 1) {
                return e($matches[1]).'<strong>'.e($matches[2]).'</strong>';
            }

            if (preg_match('/^(Thức ăn chăn nuôi:\s*)(.+)$/u', $trimmed, $matches) === 1) {
                return e($matches[1]).'<strong>'.e($matches[2]).'</strong>';
            }

            return e($trimmed);
        }, $lines);

        return implode('<br>', $htmlLines);
    }
}
