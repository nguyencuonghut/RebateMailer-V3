<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvasPart;
use App\Models\TemplatePart;
use App\Models\TemplatePartVersion;
use App\Support\Templates\TemplatePartType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateMailTemplateCanvasPartBindingService
{
    public function __construct(
        private readonly ResolveMailTemplateCanvasService $resolveMailTemplateCanvasService,
        private readonly HydrateLegacyMailTemplateFromCanvasService $hydrateLegacyMailTemplateFromCanvasService,
        private readonly ResolveMailTemplateEditLockService $resolveMailTemplateEditLockService,
    ) {
    }

    public function update(MailTemplate $mailTemplate, string $partType, int $templatePartVersionId): void
    {
        $this->resolveMailTemplateEditLockService->assertEditable($mailTemplate);

        $canvas = $this->resolveMailTemplateCanvasService->resolve($mailTemplate);

        if (! $canvas) {
            throw ValidationException::withMessages([
                'partType' => 'Canvas hiện tại chưa được khởi tạo.',
            ]);
        }

        $binding = $canvas->partBindings()
            ->with('templatePart')
            ->whereHas('templatePart', fn ($query) => $query->where('type', $partType))
            ->first();

        $templatePart = $binding?->templatePart
            ?? TemplatePart::query()->where('type', $partType)->first();

        if (! $templatePart) {
            throw ValidationException::withMessages([
                'partType' => 'Part được chọn không tồn tại trong catalog template.',
            ]);
        }

        $version = TemplatePartVersion::query()
            ->whereKey($templatePartVersionId)
            ->where('template_part_id', $templatePart->getKey())
            ->first();

        if (! $version) {
            throw ValidationException::withMessages([
                'templatePartVersionId' => 'Version được chọn không thuộc part này.',
            ]);
        }

        DB::transaction(function () use ($binding, $version, $mailTemplate, $canvas, $templatePart, $partType): void {
            if ($binding) {
                $binding->forceFill([
                    'template_part_version_id' => $version->getKey(),
                ])->save();
            } else {
                MailTemplateCanvasPart::query()->create([
                    'mail_template_canvas_id' => $canvas->getKey(),
                    'template_part_id' => $templatePart->getKey(),
                    'template_part_version_id' => $version->getKey(),
                    'sort_order' => $this->resolveSortOrder($partType),
                ]);
            }

            $freshCanvas = $canvas->fresh(['partBindings.templatePart', 'partBindings.templatePartVersion']);

            if ($freshCanvas) {
                $this->hydrateLegacyMailTemplateFromCanvasService->hydrate($mailTemplate, $freshCanvas);
            }
        });
    }

    private function resolveSortOrder(string $partType): int
    {
        foreach (TemplatePartType::cases() as $index => $candidate) {
            if ($candidate->value === $partType) {
                return $index;
            }
        }

        return count(TemplatePartType::cases());
    }
}
