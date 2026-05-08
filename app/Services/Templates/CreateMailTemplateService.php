<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\User;

class CreateMailTemplateService
{
    public function __construct(
        private readonly SyncLegacyMailTemplateToCompositionService $syncLegacyMailTemplateToCompositionService,
        private readonly HydrateLegacyMailTemplateFromCanvasService $hydrateLegacyMailTemplateFromCanvasService,
    ) {
    }

    /**
     * @param  array{name: string, subject_template?: string|null, greeting_template?: string|null}  $payload
     */
    public function create(User $user, array $payload): MailTemplate
    {
        $mailTemplate = MailTemplate::query()->create([
            'name' => $payload['name'],
            'subject_template' => '',
            'structure_json' => [
                'version' => '2.0-R5',
                'sections' => [],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $canvas = $this->syncLegacyMailTemplateToCompositionService->syncMailTemplate($mailTemplate);
        $this->hydrateLegacyMailTemplateFromCanvasService->hydrate($mailTemplate, $canvas);

        return $mailTemplate->refresh();
    }
}
