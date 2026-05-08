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
    public function build(?MailTemplate $mailTemplate, ?int $aggregatedRecordId = null): ?array
    {
        if (! $mailTemplate) {
            return null;
        }

        $sample = $this->buildTemplatePreviewSampleService->build(null, $aggregatedRecordId);

        if (! $sample) {
            return null;
        }

        $templateText = $this->resolveGreetingTemplate($mailTemplate);
        $rendered = $this->renderTemplateTextPreviewService->render($templateText, $sample['variables']);

        return [
            'templateText' => $templateText,
            'renderedText' => $rendered['renderedText'],
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
}
