<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\User;

class CreateMailTemplateService
{
    public function __construct(
        private readonly TemplateSectionCatalogService $templateSectionCatalogService,
        private readonly SyncLegacyMailTemplateToCompositionService $syncLegacyMailTemplateToCompositionService,
        private readonly HydrateLegacyMailTemplateFromCanvasService $hydrateLegacyMailTemplateFromCanvasService,
        private readonly UpdateMailTemplateCanvasCompositionService $updateMailTemplateCanvasCompositionService,
    ) {
    }

    /**
     * @param  array{name: string, subject_template?: string|null, greeting_template?: string|null}  $payload
     */
    public function create(User $user, array $payload): MailTemplate
    {
        $subjectTemplate = trim((string) ($payload['subject_template'] ?? ''));
        $greetingTemplate = trim((string) ($payload['greeting_template'] ?? ''));

        $mailTemplate = MailTemplate::query()->create([
            'name' => $payload['name'],
            'subject_template' => $subjectTemplate,
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => $this->templateSectionCatalogService->defaultSections(
                    $subjectTemplate,
                    $greetingTemplate,
                ),
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->syncLegacyMailTemplateToCompositionService->syncMailTemplate($mailTemplate);
        $canvas = $this->updateMailTemplateCanvasCompositionService->update($mailTemplate, ['subject', 'greeting']);
        $this->hydrateLegacyMailTemplateFromCanvasService->hydrate($mailTemplate, $canvas);

        return $mailTemplate->refresh();
    }
}
