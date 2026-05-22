<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;

class TemplateSectionCatalogService
{
    public function __construct(
        private readonly TemplatePartCatalogService $templatePartCatalogService,
        private readonly ResolveTemplateCanvasSectionService $resolveTemplateCanvasSectionService,
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
            $this->buildSectionPayload('representative-signature', ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSectionForTemplate(MailTemplate $mailTemplate, string $type): array
    {
        $resolvedSection = $this->resolveTemplateCanvasSectionService->resolve($mailTemplate, $type);

        $section = $this->buildSectionPayload(
            $type,
            match ($type) {
                'subject' => trim((string) ($resolvedSection['content'] ?? $mailTemplate->subject_template)),
                'greeting' => trim((string) ($resolvedSection['content'] ?? $this->resolveGreetingContent($mailTemplate))),
                default => '',
            },
        );

        if (($section['kind'] ?? null) === 'table') {
            $section['rows'] = [];
        }

        if (($section['kind'] ?? null) === 'composite') {
            $section['blocks'] = $this->defaultRepresentativeSignatureBlocks();
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
            'blocks' => $type === 'representative-signature' ? $this->defaultRepresentativeSignatureBlocks() : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function defaultRepresentativeSignatureBlocks(): array
    {
        return [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => null,
                'representativeRole' => '',
                'representativeName' => '',
            ],
            'keyAccountCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => null,
                'representativeRole' => '',
                'representativeName' => '',
            ],
        ];
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
