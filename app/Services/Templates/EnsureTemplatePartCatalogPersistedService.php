<?php

namespace App\Services\Templates;

use App\Models\TemplatePart;
use Illuminate\Support\Carbon;

class EnsureTemplatePartCatalogPersistedService
{
    public function __construct(
        private readonly TemplatePartCatalogService $templatePartCatalogService,
    ) {
    }

    /**
     * @return array<string, TemplatePart>
     */
    public function ensure(): array
    {
        $definitions = $this->templatePartCatalogService->all();
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

        return TemplatePart::query()
            ->whereIn(
                'type',
                array_column($definitions, 'type'),
            )
            ->get()
            ->keyBy('type')
            ->all();
    }
}
