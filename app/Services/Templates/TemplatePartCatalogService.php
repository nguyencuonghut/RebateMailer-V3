<?php

namespace App\Services\Templates;

use App\Support\Templates\TemplatePartType;

class TemplatePartCatalogService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return array_map(
            fn (TemplatePartType $type): array => $this->definition($type),
            TemplatePartType::cases(),
        );
    }

    /**
     * @return array<int, string>
     */
    public function allowedTypes(): array
    {
        return array_map(
            static fn (TemplatePartType $type): string => $type->value,
            TemplatePartType::cases(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(TemplatePartType $type): array
    {
        return [
            'type' => $type->value,
            'code' => $type->code(),
            'label' => $type->label(),
            'description' => $type->description(),
            'kind' => $type->kind(),
            'sourceSheet' => $type->sourceSheet(),
            'maxActiveVersions' => $type->maxActiveVersions(),
        ];
    }
}
