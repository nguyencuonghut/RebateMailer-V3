<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\TemplatePart;

class ResolveMailTemplateCanvasService
{
    /**
     * @var array<int, MailTemplateCanvas|null>
     */
    private array $resolvedCanvases = [];

    public function __construct(
        private readonly EnsureTemplatePartCatalogPersistedService $ensureTemplatePartCatalogPersistedService,
        private readonly SyncLegacyMailTemplateToCompositionService $syncLegacyMailTemplateToCompositionService,
    ) {
    }

    public function resolve(?MailTemplate $mailTemplate): ?MailTemplateCanvas
    {
        if (! $mailTemplate) {
            return null;
        }

        $templateId = (int) $mailTemplate->getKey();

        if (array_key_exists($templateId, $this->resolvedCanvases)) {
            return $this->resolvedCanvases[$templateId];
        }

        $canvas = MailTemplateCanvas::query()
            ->where('legacy_mail_template_id', $mailTemplate->getKey())
            ->with(['partBindings.templatePart', 'partBindings.templatePartVersion'])
            ->first();

        if ($canvas && $canvas->partBindings->isNotEmpty()) {
            return $this->resolvedCanvases[$templateId] = $canvas;
        }

        /** @var array<string, TemplatePart> $partsByType */
        $partsByType = $this->ensureTemplatePartCatalogPersistedService->ensure();

        return $this->resolvedCanvases[$templateId] = $this->syncLegacyMailTemplateToCompositionService
            ->syncMailTemplate($mailTemplate, $partsByType);
    }
}
