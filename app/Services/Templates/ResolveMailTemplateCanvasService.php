<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\TemplatePart;

class ResolveMailTemplateCanvasService
{
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

        $canvas = MailTemplateCanvas::query()
            ->where('legacy_mail_template_id', $mailTemplate->getKey())
            ->with(['partBindings.templatePart', 'partBindings.templatePartVersion'])
            ->first();

        if ($canvas && $canvas->partBindings->isNotEmpty()) {
            return $canvas;
        }

        /** @var array<string, TemplatePart> $partsByType */
        $partsByType = $this->ensureTemplatePartCatalogPersistedService->ensure();

        return $this->syncLegacyMailTemplateToCompositionService
            ->syncMailTemplate($mailTemplate, $partsByType);
    }
}
