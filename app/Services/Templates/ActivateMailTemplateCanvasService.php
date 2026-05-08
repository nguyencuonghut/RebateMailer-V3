<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ActivateMailTemplateCanvasService
{
    public function __construct(
        private readonly SyncLegacyMailTemplateToCompositionService $syncLegacyMailTemplateToCompositionService,
    ) {
    }

    public function activate(MailTemplate $mailTemplate, User $user): MailTemplateCanvas
    {
        $canvas = $this->syncLegacyMailTemplateToCompositionService->syncMailTemplate($mailTemplate);

        return DB::transaction(function () use ($mailTemplate, $user, $canvas): MailTemplateCanvas {
            MailTemplateCanvas::query()
                ->where('is_active', true)
                ->whereKeyNot($canvas->getKey())
                ->update([
                    'is_active' => false,
                    'updated_by' => $user->getKey(),
                    'updated_at' => now(),
                ]);

            MailTemplate::query()
                ->where('is_active', true)
                ->whereKeyNot($mailTemplate->getKey())
                ->update([
                    'is_active' => false,
                    'updated_by' => $user->getKey(),
                    'updated_at' => now(),
                ]);

            $canvas->forceFill([
                'is_active' => true,
                'updated_by' => $user->getKey(),
            ])->save();

            $mailTemplate->forceFill([
                'is_active' => true,
                'updated_by' => $user->getKey(),
            ])->save();

            return $canvas->fresh();
        });
    }
}
