<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;

class BuildTemplateSubjectPreviewService
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

        $templateText = $this->resolveSubjectTemplate($mailTemplate);
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
            ],
        ];
    }

    private function resolveSubjectTemplate(MailTemplate $mailTemplate): string
    {
        $section = $this->resolveTemplateCanvasSectionService->resolve($mailTemplate, 'subject');
        $content = trim((string) ($section['content'] ?? ''));

        return $content !== '' ? $content : $mailTemplate->subject_template;
    }
}
