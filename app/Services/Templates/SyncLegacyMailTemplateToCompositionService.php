<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\MailTemplateCanvasPart;
use App\Models\TemplatePart;
use App\Models\TemplatePartVersion;
use App\Support\Templates\TemplatePartType;
use Illuminate\Support\Facades\DB;

class SyncLegacyMailTemplateToCompositionService
{
    public function __construct(
        private readonly EnsureTemplatePartCatalogPersistedService $ensureTemplatePartCatalogPersistedService,
        private readonly ActivateTemplatePartVersionService $activateTemplatePartVersionService,
    ) {
    }

    public function syncAll(): void
    {
        $partsByType = $this->ensureTemplatePartCatalogPersistedService->ensure();

        MailTemplate::query()
            ->orderBy('id')
            ->get()
            ->each(fn (MailTemplate $mailTemplate) => $this->syncMailTemplate($mailTemplate, $partsByType));
    }

    /**
     * @param  array<string, TemplatePart>|null  $partsByType
     */
    public function syncMailTemplate(MailTemplate $mailTemplate, ?array $partsByType = null): MailTemplateCanvas
    {
        $partsByType ??= $this->ensureTemplatePartCatalogPersistedService->ensure();

        return DB::transaction(function () use ($mailTemplate, $partsByType): MailTemplateCanvas {
            $canvas = MailTemplateCanvas::query()->updateOrCreate(
                [
                    'legacy_mail_template_id' => $mailTemplate->getKey(),
                ],
                [
                    'name' => $mailTemplate->name,
                    'is_active' => (bool) $mailTemplate->is_active,
                    'created_by' => $mailTemplate->created_by,
                    'updated_by' => $mailTemplate->updated_by,
                    'created_at' => $mailTemplate->created_at,
                    'updated_at' => $mailTemplate->updated_at,
                ],
            );

            foreach (TemplatePartType::cases() as $index => $partType) {
                $payload = $this->extractLegacyPayload($mailTemplate, $partType);

                if ($payload === null) {
                    continue;
                }

                $templatePart = $partsByType[$partType->value] ?? null;

                if (! $templatePart instanceof TemplatePart) {
                    continue;
                }

                $templatePartVersion = $this->upsertLegacyVersion(
                    $templatePart,
                    $mailTemplate,
                    $payload,
                );

                MailTemplateCanvasPart::query()->updateOrCreate(
                    [
                        'mail_template_canvas_id' => $canvas->getKey(),
                        'template_part_id' => $templatePart->getKey(),
                    ],
                    [
                        'template_part_version_id' => $templatePartVersion->getKey(),
                        'sort_order' => $index,
                    ],
                );

                if ((bool) $mailTemplate->is_active) {
                    $this->activateTemplatePartVersionService->activate($templatePartVersion);
                }
            }

            return $canvas->fresh(['partBindings.templatePart', 'partBindings.templatePartVersion']);
        });
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function extractLegacyPayload(MailTemplate $mailTemplate, TemplatePartType $partType): array|string|null
    {
        $sections = collect($mailTemplate->structure_json['sections'] ?? [])
            ->filter(static fn (mixed $section): bool => is_array($section))
            ->mapWithKeys(static fn (array $section): array => [
                (string) ($section['type'] ?? '') => $section,
            ]);

        if ($partType === TemplatePartType::Subject) {
            $subject = trim((string) ($sections->get('subject')['content'] ?? $mailTemplate->subject_template));

            return $subject === '' ? null : $subject;
        }

        $section = $sections->get($partType->value);

        if (! is_array($section)) {
            return null;
        }

        if ($partType->kind() === 'text') {
            $content = trim((string) ($section['content'] ?? ''));

            return $content === '' ? null : $content;
        }

        return $section;
    }

    /**
     * @param  array<string, mixed>|string  $payload
     */
    private function upsertLegacyVersion(TemplatePart $templatePart, MailTemplate $mailTemplate, array|string $payload): TemplatePartVersion
    {
        $existingVersion = TemplatePartVersion::query()
            ->where('template_part_id', $templatePart->getKey())
            ->where('legacy_mail_template_id', $mailTemplate->getKey())
            ->first();

        $attributes = [
            'version_label' => sprintf('Legacy %s', $mailTemplate->name),
            'text_template' => is_string($payload) ? $payload : null,
            'structure_json' => is_array($payload) ? $payload : null,
            'is_active' => (bool) $mailTemplate->is_active,
            'activated_at' => $mailTemplate->is_active ? ($mailTemplate->updated_at ?? now()) : null,
            'created_by' => $mailTemplate->created_by,
            'updated_by' => $mailTemplate->updated_by,
            'created_at' => $mailTemplate->created_at,
            'updated_at' => $mailTemplate->updated_at,
        ];

        if ($existingVersion) {
            $existingVersion->forceFill($attributes)->save();

            return $existingVersion->fresh();
        }

        $nextVersionNo = (int) TemplatePartVersion::query()
            ->where('template_part_id', $templatePart->getKey())
            ->max('version_no') + 1;

        return TemplatePartVersion::query()->create([
            'template_part_id' => $templatePart->getKey(),
            'version_no' => $nextVersionNo,
            'legacy_mail_template_id' => $mailTemplate->getKey(),
            ...$attributes,
        ]);
    }
}
