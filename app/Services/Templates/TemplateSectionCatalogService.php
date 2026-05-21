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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSectionForTemplate(MailTemplate $mailTemplate, string $type): array
    {
        $resolvedSection = $this->resolveTemplateCanvasSectionService->resolve($mailTemplate, $type);

        if ($type === 'representative-signature') {
            return $this->buildRepresentativeSignatureSection($resolvedSection);
        }

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

    /**
     * @param  array<string, mixed>|null  $resolvedSection
     * @return array<string, mixed>
     */
    private function buildRepresentativeSignatureSection(?array $resolvedSection): array
    {
        $definition = collect($this->all())->firstWhere('type', 'representative-signature');
        $blocks = $this->normalizeRepresentativeSignatureBlocks($resolvedSection['blocks'] ?? null);

        return array_filter([
            'type' => $definition['type'],
            'label' => $definition['label'],
            'description' => $definition['description'],
            'kind' => $definition['kind'],
            'sourceSheet' => $definition['sourceSheet'],
            'blocks' => $blocks,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function normalizeRepresentativeSignatureBlocks(mixed $blocks): array
    {
        $normalizedBlocks = is_array($blocks) ? $blocks : [];

        return [
            'normalCustomer' => $this->normalizeRepresentativeSignatureBlock($normalizedBlocks['normalCustomer'] ?? null),
            'keyAccountCustomer' => $this->normalizeRepresentativeSignatureBlock($normalizedBlocks['keyAccountCustomer'] ?? null),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizeRepresentativeSignatureBlock(mixed $block): array
    {
        $block = is_array($block) ? $block : [];

        return [
            'title' => trim((string) ($block['title'] ?? 'Đại diện công ty')) ?: 'Đại diện công ty',
            'signatureImageDataUrl' => ($value = trim((string) ($block['signatureImageDataUrl'] ?? ''))) !== '' ? $value : null,
            'representativeRole' => ($value = trim((string) ($block['representativeRole'] ?? ''))) !== '' ? $value : null,
            'representativeName' => ($value = trim((string) ($block['representativeName'] ?? ''))) !== '' ? $value : null,
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
