<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\MailTemplateCanvasPart;
use App\Models\TemplatePart;
use App\Models\TemplatePartVersion;
use Illuminate\Support\Facades\DB;

class UpdateMailTemplateCanvasCompositionService
{
    public function __construct(
        private readonly EnsureTemplatePartCatalogPersistedService $ensureTemplatePartCatalogPersistedService,
        private readonly TemplateSectionCatalogService $templateSectionCatalogService,
        private readonly SyncLegacyMailTemplateToCompositionService $syncLegacyMailTemplateToCompositionService,
        private readonly HydrateLegacyMailTemplateFromCanvasService $hydrateLegacyMailTemplateFromCanvasService,
    ) {
    }

    /**
     * @param  array<int, string>  $partTypes
     */
    public function update(MailTemplate $mailTemplate, array $partTypes): MailTemplateCanvas
    {
        $partsByType = $this->ensureTemplatePartCatalogPersistedService->ensure();
        $canvas = $this->syncLegacyMailTemplateToCompositionService->syncMailTemplate($mailTemplate, $partsByType);

        return DB::transaction(function () use ($mailTemplate, $partTypes, $partsByType, $canvas): MailTemplateCanvas {
            $bindingsByPartType = $canvas->partBindings()
                ->with(['templatePart', 'templatePartVersion'])
                ->get()
                ->filter(fn (MailTemplateCanvasPart $binding): bool => $binding->templatePart !== null)
                ->keyBy(fn (MailTemplateCanvasPart $binding): string => $binding->templatePart->type);

            foreach ($partTypes as $sortOrder => $partType) {
                $templatePart = $partsByType[$partType] ?? null;

                if (! $templatePart instanceof TemplatePart) {
                    continue;
                }

                $binding = $bindingsByPartType->get($partType);

                if (! $binding) {
                    $version = $this->createDefaultVersion($mailTemplate, $templatePart);

                    MailTemplateCanvasPart::query()->create([
                        'mail_template_canvas_id' => $canvas->getKey(),
                        'template_part_id' => $templatePart->getKey(),
                        'template_part_version_id' => $version->getKey(),
                        'sort_order' => $sortOrder,
                    ]);

                    continue;
                }

                $binding->forceFill([
                    'sort_order' => $sortOrder,
                ])->save();
            }

            MailTemplateCanvasPart::query()
                ->where('mail_template_canvas_id', $canvas->getKey())
                ->whereHas('templatePart', fn ($query) => $query->whereNotIn('type', $partTypes))
                ->delete();

            $canvas = $canvas->fresh(['partBindings.templatePart', 'partBindings.templatePartVersion']);
            $this->hydrateLegacyMailTemplateFromCanvasService->hydrate($mailTemplate, $canvas);

            return $canvas;
        });
    }

    private function createDefaultVersion(MailTemplate $mailTemplate, TemplatePart $templatePart): TemplatePartVersion
    {
        $section = $this->templateSectionCatalogService->buildSectionForTemplate($mailTemplate, $templatePart->type);

        $nextVersionNo = (int) TemplatePartVersion::query()
            ->where('template_part_id', $templatePart->getKey())
            ->max('version_no') + 1;

        return TemplatePartVersion::query()->create([
            'template_part_id' => $templatePart->getKey(),
            'version_no' => $nextVersionNo,
            'version_label' => sprintf('Draft %s', $mailTemplate->name),
            'text_template' => $templatePart->kind === 'text' ? (string) ($section['content'] ?? '') : null,
            'structure_json' => in_array($templatePart->kind, ['table', 'composite'], true) ? $section : null,
            'legacy_mail_template_id' => $mailTemplate->getKey(),
            'created_by' => $mailTemplate->created_by,
            'updated_by' => $mailTemplate->updated_by,
        ]);
    }
}
