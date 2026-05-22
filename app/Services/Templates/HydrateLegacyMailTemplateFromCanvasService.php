<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\MailTemplateCanvasPart;

class HydrateLegacyMailTemplateFromCanvasService
{
    public function __construct(
        private readonly TemplatePartCatalogService $templatePartCatalogService,
    ) {
    }

    public function hydrate(MailTemplate $mailTemplate, MailTemplateCanvas $canvas): MailTemplate
    {
        $bindings = $canvas->partBindings()
            ->with(['templatePart', 'templatePartVersion'])
            ->orderBy('sort_order')
            ->get();

        $subjectTemplate = '';
        $sections = [];

        foreach ($bindings as $binding) {
            $part = $binding->templatePart;
            $version = $binding->templatePartVersion;

            if (! $part || ! $version) {
                continue;
            }

            $definition = collect($this->templatePartCatalogService->all())->firstWhere('type', $part->type);

            if (! is_array($definition)) {
                continue;
            }

            if ($part->type === 'subject') {
                $subjectTemplate = (string) ($version->text_template ?? '');
            }

            $section = array_filter([
                'type' => $part->type,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'kind' => $definition['kind'],
                'sourceSheet' => $definition['sourceSheet'],
                'content' => $part->kind === 'text' ? $version->text_template : null,
                'rows' => $part->kind === 'table' ? ($version->structure_json['rows'] ?? []) : null,
                'blocks' => $part->kind === 'composite' ? ($version->structure_json['blocks'] ?? []) : null,
            ], static fn (mixed $value): bool => $value !== null);

            $sections[] = $section;
        }

        $mailTemplate->forceFill([
            'name' => $canvas->name,
            'subject_template' => $subjectTemplate,
            'structure_json' => [
                'version' => '2.0-R5',
                'sections' => $sections,
            ],
            'is_active' => $canvas->is_active,
        ])->save();

        return $mailTemplate->refresh();
    }
}
