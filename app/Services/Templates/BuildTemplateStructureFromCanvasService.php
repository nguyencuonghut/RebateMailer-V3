<?php

namespace App\Services\Templates;

use App\Models\MailTemplateCanvas;

class BuildTemplateStructureFromCanvasService
{
    public function __construct(
        private readonly TemplatePartCatalogService $templatePartCatalogService,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(static function (array $row): array {
            $row['content'] = (string) ($row['content'] ?? '');

            return $row;
        }, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function build(MailTemplateCanvas $canvas): array
    {
        $sections = $canvas->partBindings
            ->sortBy('sort_order')
            ->map(function ($binding): ?array {
                $part = $binding->templatePart;
                $version = $binding->templatePartVersion;

                if (! $part || ! $version) {
                    return null;
                }

                $definition = collect($this->templatePartCatalogService->all())->firstWhere('type', $part->type);

                if (! is_array($definition)) {
                    return null;
                }

                return array_filter([
                    'type' => $part->type,
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'kind' => $definition['kind'],
                    'sourceSheet' => $definition['sourceSheet'],
                    'content' => $part->kind === 'text' ? $version->text_template : null,
                    'rows' => $part->kind === 'table' ? $this->normalizeRows($version->structure_json['rows'] ?? []) : null,
                ], static fn (mixed $value): bool => $value !== null);
            })
            ->filter()
            ->values()
            ->all();

        return [
            'version' => '2.0-R5',
            'sections' => $sections,
        ];
    }
}
