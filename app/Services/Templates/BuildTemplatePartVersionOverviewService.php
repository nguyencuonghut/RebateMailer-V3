<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\TemplatePart;

class BuildTemplatePartVersionOverviewService
{
    public function __construct(
        private readonly EnsureTemplatePartCatalogPersistedService $ensureTemplatePartCatalogPersistedService,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(?MailTemplate $selectedTemplate): array
    {
        $this->ensureTemplatePartCatalogPersistedService->ensure();

        $selectedCanvasId = MailTemplateCanvas::query()
            ->where('legacy_mail_template_id', $selectedTemplate?->getKey())
            ->value('id');

        return TemplatePart::query()
            ->with([
                'versions' => fn ($query) => $query
                    ->orderByDesc('is_active')
                    ->orderByDesc('version_no')
                    ->orderByDesc('id'),
                'canvasBindings' => fn ($query) => $query
                    ->when($selectedCanvasId, fn ($inner) => $inner->where('mail_template_canvas_id', $selectedCanvasId)),
            ])
            ->orderBy('id')
            ->get()
            ->map(function (TemplatePart $templatePart): array {
                $selectedBinding = $templatePart->canvasBindings->first();

                return [
                    'partType' => $templatePart->type,
                    'code' => $templatePart->code,
                    'label' => $templatePart->label,
                    'kind' => $templatePart->kind,
                    'sourceSheet' => $templatePart->source_sheet,
                    'maxActiveVersions' => $templatePart->max_active_versions,
                    'selectedVersionId' => $selectedBinding?->template_part_version_id,
                    'versionCount' => $templatePart->versions->count(),
                    'activeVersionCount' => $templatePart->versions->where('is_active', true)->count(),
                    'versions' => $templatePart->versions->map(fn ($version): array => [
                        'id' => $version->getKey(),
                        'versionNo' => $version->version_no,
                        'versionLabel' => $version->version_label,
                        'isActive' => $version->is_active,
                        'hasTextTemplate' => filled($version->text_template),
                        'rowCount' => is_array($version->structure_json['rows'] ?? null)
                            ? count($version->structure_json['rows'])
                            : 0,
                        'legacyMailTemplateId' => $version->legacy_mail_template_id,
                        'updatedAt' => optional($version->updated_at)->toIso8601String(),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
