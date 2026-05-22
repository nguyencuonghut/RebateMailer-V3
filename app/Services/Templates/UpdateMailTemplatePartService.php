<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\TemplatePart;
use App\Models\TemplatePartVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateMailTemplatePartService
{
    public function __construct(
        private readonly EnsureTemplatePartCatalogPersistedService $ensureTemplatePartCatalogPersistedService,
        private readonly SyncLegacyMailTemplateToCompositionService $syncLegacyMailTemplateToCompositionService,
        private readonly HydrateLegacyMailTemplateFromCanvasService $hydrateLegacyMailTemplateFromCanvasService,
        private readonly UpdateMailTemplateCanvasCompositionService $updateMailTemplateCanvasCompositionService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(MailTemplate $mailTemplate, User $user, string $partType, array $payload): MailTemplateCanvas
    {
        $partsByType = $this->ensureTemplatePartCatalogPersistedService->ensure();
        $canvas = $this->syncLegacyMailTemplateToCompositionService->syncMailTemplate($mailTemplate, $partsByType);
        $templatePart = $partsByType[$partType] ?? null;

        if (! $templatePart instanceof TemplatePart) {
            return $canvas;
        }

        return DB::transaction(function () use ($mailTemplate, $user, $partType, $payload, $canvas, $templatePart): MailTemplateCanvas {
            $binding = $canvas->partBindings()
                ->where('template_part_id', $templatePart->getKey())
                ->with(['templatePartVersion', 'templatePart'])
                ->first();

            if (! $binding) {
                $canvas = $this->updateMailTemplateCanvasCompositionService->update(
                    $mailTemplate,
                    array_values(array_unique([
                        ...$canvas->partBindings()->with('templatePart')->orderBy('sort_order')->get()->map(fn ($item) => $item->templatePart?->type)->filter()->all(),
                        $partType,
                    ])),
                );

                $binding = $canvas->partBindings()
                    ->where('template_part_id', $templatePart->getKey())
                    ->with(['templatePartVersion', 'templatePart'])
                    ->firstOrFail();
            }

            $version = $binding->templatePartVersion;

            if (! $version) {
                return $canvas;
            }

            $version->forceFill([
                'text_template' => $templatePart->kind === 'text' ? (string) ($payload['content'] ?? '') : null,
                'structure_json' => in_array($templatePart->kind, ['table', 'composite'], true) ? ($payload['section'] ?? null) : null,
                'updated_by' => $user->id,
            ])->save();

            $canvas = $canvas->fresh(['partBindings.templatePart', 'partBindings.templatePartVersion']);
            $this->hydrateLegacyMailTemplateFromCanvasService->hydrate($mailTemplate, $canvas);

            return $canvas;
        });
    }
}
