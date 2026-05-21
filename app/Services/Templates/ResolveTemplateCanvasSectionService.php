<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;

class ResolveTemplateCanvasSectionService
{
    public function __construct(
        private readonly ResolveMailTemplateCanvasService $resolveMailTemplateCanvasService,
        private readonly TemplatePartCatalogService $templatePartCatalogService,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(?MailTemplate $mailTemplate, string $partType): ?array
    {
        $canvas = $this->resolveMailTemplateCanvasService->resolve($mailTemplate);

        if (! $canvas) {
            return null;
        }

        $binding = $canvas->partBindings
            ->first(fn ($item): bool => $item->templatePart?->type === $partType);

        $part = $binding?->templatePart;
        $version = $binding?->templatePartVersion;

        if (! $part || ! $version) {
            return null;
        }

        $definition = collect($this->templatePartCatalogService->all())->firstWhere('type', $partType);

        if (! is_array($definition)) {
            return null;
        }

        return array_filter([
            'type' => $partType,
            'label' => $definition['label'],
            'description' => $definition['description'],
            'kind' => $definition['kind'],
            'sourceSheet' => $definition['sourceSheet'],
            'content' => $part->kind === 'text' ? $version->text_template : null,
            'rows' => $part->kind === 'table' ? ($version->structure_json['rows'] ?? []) : null,
            'blocks' => $part->kind === 'composite' ? ($version->structure_json['blocks'] ?? []) : null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
