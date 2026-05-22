<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\User;

class AppendMailTemplateSectionService
{
    public function __construct(
        private readonly UpdateMailTemplateCanvasCompositionService $updateMailTemplateCanvasCompositionService,
        private readonly ResolveMailTemplateCanvasService $resolveMailTemplateCanvasService,
        private readonly ResolveMailTemplateEditLockService $resolveMailTemplateEditLockService,
    ) {
    }

    public function append(MailTemplate $mailTemplate, User $user, string $type): MailTemplate
    {
        $this->resolveMailTemplateEditLockService->assertEditable($mailTemplate);

        $canvas = $this->resolveMailTemplateCanvasService->resolve($mailTemplate);

        $sections = $canvas?->partBindings
            ?->filter(fn ($binding): bool => is_string($binding->templatePart?->type))
            ->sortBy('sort_order')
            ->map(fn ($binding): string => $binding->templatePart->type)
            ->values()
            ->all() ?? [];

        if (! in_array($type, $sections, true)) {
            $sections[] = $type;
        }

        $this->updateMailTemplateCanvasCompositionService->update($mailTemplate, $sections);

        return $mailTemplate->refresh();
    }
}
