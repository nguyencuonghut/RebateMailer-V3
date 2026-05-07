<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Support\Templates\TemplatePartType;

class BuildMailTemplateCanvasCompositionService
{
    public function __construct(
        private readonly TemplatePartCatalogService $templatePartCatalogService,
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

        $sections = collect($mailTemplate->structure_json['sections'] ?? [])
            ->filter(static fn (mixed $section): bool => is_array($section))
            ->mapWithKeys(static fn (array $section): array => [
                (string) ($section['type'] ?? '') => $section,
            ]);

        return [
            'canvasId' => $mailTemplate->getKey(),
            'canvasName' => $mailTemplate->name,
            'storageModel' => 'prototype-monolith-bridge',
            'partSelections' => array_map(
                fn (TemplatePartType $partType): array => $this->buildPartSelection(
                    $mailTemplate,
                    $partType,
                    $sections->get($partType->value),
                ),
                TemplatePartType::cases(),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $section
     * @return array<string, mixed>
     */
    private function buildPartSelection(MailTemplate $mailTemplate, TemplatePartType $partType, ?array $section): array
    {
        $definition = $this->templatePartCatalogService->definition($partType);

        return [
            'partType' => $definition['type'],
            'code' => $definition['code'],
            'label' => $definition['label'],
            'kind' => $definition['kind'],
            'sourceSheet' => $definition['sourceSheet'],
            'maxActiveVersions' => $definition['maxActiveVersions'],
            'activePolicy' => $definition['maxActiveVersions'] === 1 ? 'single-active' : 'multi-active',
            'isConfigured' => is_array($section),
            'selectedVersion' => [
                'versionKey' => sprintf('legacy-mail-template-%d:%s', $mailTemplate->getKey(), $partType->value),
                'versionLabel' => sprintf('Legacy %s', $definition['label']),
                'selectionMode' => 'prototype-inline',
                'mailTemplateId' => $mailTemplate->getKey(),
            ],
            'contentSummary' => $this->buildContentSummary($partType, $section),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $section
     * @return array<string, mixed>
     */
    private function buildContentSummary(TemplatePartType $partType, ?array $section): array
    {
        if (! is_array($section)) {
            return [
                'hasContent' => false,
                'rowCount' => 0,
                'textLength' => 0,
            ];
        }

        if ($partType->kind() === 'text') {
            $content = trim((string) ($section['content'] ?? ''));

            return [
                'hasContent' => $content !== '',
                'rowCount' => 0,
                'textLength' => mb_strlen($content),
            ];
        }

        $rows = $section['rows'] ?? [];

        return [
            'hasContent' => is_array($rows) && count($rows) > 0,
            'rowCount' => is_array($rows) ? count($rows) : 0,
            'textLength' => 0,
        ];
    }
}
