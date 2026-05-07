<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\User;

class AppendMailTemplateSectionService
{
    public function __construct(
        private readonly TemplateSectionCatalogService $templateSectionCatalogService,
    ) {
    }

    public function append(MailTemplate $mailTemplate, User $user, string $type): MailTemplate
    {
        $structure = $mailTemplate->structure_json;
        $sections = $structure['sections'] ?? [];

        if (collect($sections)->contains(fn (mixed $section): bool => is_array($section) && ($section['type'] ?? null) === $type)) {
            return $mailTemplate;
        }

        $sections[] = $this->templateSectionCatalogService->buildSectionForTemplate($mailTemplate, $type);

        $mailTemplate->forceFill([
            'structure_json' => [
                ...$structure,
                'version' => '2.2-E',
                'sections' => $sections,
            ],
            'updated_by' => $user->id,
        ])->save();

        return $mailTemplate->refresh();
    }
}
