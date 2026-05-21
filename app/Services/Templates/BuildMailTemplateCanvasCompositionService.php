<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Support\Templates\TemplatePartType;

class BuildMailTemplateCanvasCompositionService
{
    public function __construct(
        private readonly TemplatePartCatalogService $templatePartCatalogService,
        private readonly ResolveMailTemplateCanvasService $resolveMailTemplateCanvasService,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(?MailTemplate $mailTemplate): ?array
    {
        $canvas = $this->resolveMailTemplateCanvasService->resolve($mailTemplate);

        return $canvas ? $this->buildFromCanvas($canvas) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFromCanvas(MailTemplateCanvas $canvas): array
    {
        $bindings = $canvas->partBindings
            ->filter(fn ($binding): bool => $binding->templatePart !== null)
            ->keyBy(fn ($binding): string => $binding->templatePart->type);

        return [
            'canvasId' => $canvas->getKey(),
            'canvasName' => $canvas->name,
            'storageModel' => 'composition-db',
            'partSelections' => array_map(
                fn (TemplatePartType $partType): array => $this->buildCanvasPartSelection(
                    $partType,
                    $bindings->get($partType->value),
                ),
                TemplatePartType::cases(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCanvasPartSelection(TemplatePartType $partType, mixed $binding): array
    {
        $definition = $this->templatePartCatalogService->definition($partType);
        $version = $binding?->templatePartVersion;

        return [
            'partType' => $definition['type'],
            'code' => $definition['code'],
            'label' => $definition['label'],
            'kind' => $definition['kind'],
            'sourceSheet' => $definition['sourceSheet'],
            'maxActiveVersions' => $definition['maxActiveVersions'],
            'activePolicy' => $definition['maxActiveVersions'] === 1 ? 'single-active' : 'multi-active',
            'isConfigured' => $version !== null,
            'selectedVersion' => $version === null ? null : [
                'versionKey' => sprintf('template-part-version-%d', $version->getKey()),
                'versionLabel' => $version->version_label,
                'selectionMode' => 'composition-binding',
                'versionNo' => $version->version_no,
            ],
            'contentSummary' => $this->buildVersionContentSummary($partType, $version),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildVersionContentSummary(TemplatePartType $partType, mixed $version): array
    {
        if ($version === null) {
            return [
                'hasContent' => false,
                'rowCount' => 0,
                'textLength' => 0,
            ];
        }

        if ($partType->kind() === 'text') {
            $content = trim((string) ($version->text_template ?? ''));

            return [
                'hasContent' => $content !== '',
                'rowCount' => 0,
                'textLength' => mb_strlen($content),
            ];
        }

        if ($partType->kind() === 'composite') {
            $blocks = $version->structure_json['blocks'] ?? [];
            $configuredBlocks = collect(is_array($blocks) ? $blocks : [])
                ->filter(fn (mixed $block): bool => is_array($block))
                ->filter(function (array $block): bool {
                    return trim((string) ($block['signatureImageDataUrl'] ?? '')) !== ''
                        || trim((string) ($block['representativeRole'] ?? '')) !== ''
                        || trim((string) ($block['representativeName'] ?? '')) !== '';
                });

            return [
                'hasContent' => $configuredBlocks->isNotEmpty(),
                'rowCount' => $configuredBlocks->count(),
                'textLength' => 0,
            ];
        }

        $rows = $version->structure_json['rows'] ?? [];

        return [
            'hasContent' => is_array($rows) && count($rows) > 0,
            'rowCount' => is_array($rows) ? count($rows) : 0,
            'textLength' => 0,
        ];
    }
}
