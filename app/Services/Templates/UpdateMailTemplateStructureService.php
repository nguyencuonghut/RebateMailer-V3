<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\User;

class UpdateMailTemplateStructureService
{
    public function __construct(
        private readonly SyncLegacyMailTemplateToCompositionService $syncLegacyMailTemplateToCompositionService,
        private readonly ResolveMailTemplateEditLockService $resolveMailTemplateEditLockService,
    ) {
    }

    /**
     * @param  array{version: string, sections: array<int, array<string, mixed>>}  $payload
     */
    public function update(MailTemplate $mailTemplate, User $user, array $payload): MailTemplate
    {
        $this->resolveMailTemplateEditLockService->assertEditable($mailTemplate);

        $mailTemplate->forceFill([
            'structure_json' => [
                'version' => $payload['version'],
                'sections' => array_map(
                    fn (array $section): array => array_filter([
                        'type' => $section['type'],
                        'label' => $section['label'] ?? null,
                        'description' => $section['description'] ?? null,
                        'kind' => $section['kind'] ?? null,
                        'sourceSheet' => $section['sourceSheet'] ?? null,
                        'content' => $section['content'] ?? null,
                        'rows' => isset($section['rows'])
                            ? array_values(array_map(
                                fn (array $row): array => [
                                    'content' => $row['content'],
                                    'indentLevel' => max(0, min(4, (int) ($row['indentLevel'] ?? 0))),
                                    'rowType' => $row['rowType'] ?? null,
                                    'columnKey' => $row['columnKey'] ?? null,
                                    'valueColumn' => $row['valueColumn'] ?? null,
                                    'hideWhenValueZero' => (bool) ($row['hideWhenValueZero'] ?? false),
                                    'isBold' => array_key_exists('isBold', $row)
                                        ? (bool) $row['isBold']
                                        : null,
                                ],
                                $section['rows'],
                            ))
                            : null,
                    ], static fn (mixed $value): bool => $value !== null),
                    $payload['sections'],
                ),
            ],
            'updated_by' => $user->id,
        ])->save();

        $this->syncLegacyMailTemplateToCompositionService->syncMailTemplate($mailTemplate);

        return $mailTemplate->refresh();
    }
}
