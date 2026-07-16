<?php

namespace App\Services\Templates;

use App\Models\TemplatePart;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EnsureTemplatePartCatalogPersistedService
{
    /**
     * @var array<string, TemplatePart>|null
     */
    private ?array $partsByType = null;

    public function __construct(
        private readonly TemplatePartCatalogService $templatePartCatalogService,
    ) {
    }

    /**
     * @return array<string, TemplatePart>
     */
    public function ensure(): array
    {
        if ($this->partsByType !== null) {
            return $this->partsByType;
        }

        $definitions = $this->templatePartCatalogService->all();
        $definitionTypes = array_column($definitions, 'type');

        $existingParts = TemplatePart::query()
            ->whereIn(
                'type',
                $definitionTypes,
            )
            ->get()
            ->keyBy('type');

        if ($this->catalogMatchesDefinitions($definitions, $existingParts)) {
            return $this->partsByType = $existingParts->all();
        }

        $timestamp = Carbon::now();

        TemplatePart::query()->upsert(
            array_map(
                static fn (array $definition): array => [
                    'type' => $definition['type'],
                    'code' => $definition['code'],
                    'label' => $definition['label'],
                    'kind' => $definition['kind'],
                    'source_sheet' => $definition['sourceSheet'],
                    'max_active_versions' => $definition['maxActiveVersions'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                $definitions,
            ),
            ['type'],
            ['code', 'label', 'kind', 'source_sheet', 'max_active_versions', 'updated_at'],
        );

        return $this->partsByType = TemplatePart::query()
            ->whereIn('type', $definitionTypes)
            ->get()
            ->keyBy('type')
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     * @param  Collection<string, TemplatePart>  $existingParts
     */
    private function catalogMatchesDefinitions(array $definitions, Collection $existingParts): bool
    {
        foreach ($definitions as $definition) {
            $part = $existingParts->get($definition['type']);

            if (! $part instanceof TemplatePart) {
                return false;
            }

            if (
                $part->code !== $definition['code']
                || $part->label !== $definition['label']
                || $part->kind !== $definition['kind']
                || $part->source_sheet !== $definition['sourceSheet']
                || (int) $part->max_active_versions !== (int) $definition['maxActiveVersions']
            ) {
                return false;
            }
        }

        return true;
    }
}
