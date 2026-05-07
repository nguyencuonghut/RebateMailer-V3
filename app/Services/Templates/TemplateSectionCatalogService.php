<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;

class TemplateSectionCatalogService
{
    public function __construct(
        private readonly TemplatePartCatalogService $templatePartCatalogService,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->templatePartCatalogService->all();
    }

    /**
     * @return array<int, string>
     */
    public function allowedTypes(): array
    {
        return array_column($this->all(), 'type');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function defaultSections(string $subjectTemplate, string $greetingTemplate): array
    {
        return [
            $this->buildSectionPayload('subject', $subjectTemplate),
            $this->buildSectionPayload('greeting', $greetingTemplate),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSectionForTemplate(MailTemplate $mailTemplate, string $type): array
    {
        $section = $this->buildSectionPayload(
            $type,
            $type === 'subject' ? $mailTemplate->subject_template : $this->resolveGreetingContent($mailTemplate),
        );

        if (($section['kind'] ?? null) === 'table') {
            $section['rows'] = [];
        }

        return $section;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSectionPayload(string $type, string $textContent): array
    {
        $definition = collect($this->all())->firstWhere('type', $type);

        return array_filter([
            'type' => $definition['type'],
            'label' => $definition['label'],
            'description' => $definition['description'],
            'kind' => $definition['kind'],
            'sourceSheet' => $definition['sourceSheet'],
            'content' => in_array($type, ['subject', 'greeting'], true) ? $textContent : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function resolveGreetingContent(MailTemplate $mailTemplate): string
    {
        $sections = $mailTemplate->structure_json['sections'] ?? [];

        $greetingSection = collect($sections)->firstWhere('type', 'greeting');

        return is_array($greetingSection) && is_string($greetingSection['content'] ?? null)
            ? $greetingSection['content']
            : '';
    }
}
