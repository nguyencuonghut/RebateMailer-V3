<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;

class BuildTemplateGreetingPreviewService
{
    public function __construct(
        private readonly RenderTemplateTextPreviewService $renderTemplateTextPreviewService,
        private readonly BuildTemplatePreviewSampleService $buildTemplatePreviewSampleService,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(?MailTemplate $mailTemplate): ?array
    {
        if (! $mailTemplate) {
            return null;
        }

        $sample = $this->buildTemplatePreviewSampleService->build();

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
        $sections = $mailTemplate->structure_json['sections'] ?? [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            if (($section['type'] ?? null) !== 'greeting') {
                continue;
            }

            return trim((string) ($section['content'] ?? ''));
        }

        return '';
    }
}
