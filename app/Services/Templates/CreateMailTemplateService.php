<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\User;

class CreateMailTemplateService
{
    public function __construct(
        private readonly TemplateSectionCatalogService $templateSectionCatalogService,
    ) {
    }

    /**
     * @param  array{name: string, subject_template: string, greeting_template: string}  $payload
     */
    public function create(User $user, array $payload): MailTemplate
    {
        return MailTemplate::query()->create([
            'name' => $payload['name'],
            'subject_template' => $payload['subject_template'],
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => $this->templateSectionCatalogService->defaultSections(
                    $payload['subject_template'],
                    $payload['greeting_template'],
                ),
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
