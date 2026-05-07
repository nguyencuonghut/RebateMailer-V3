<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;
use Illuminate\Support\Facades\DB;

class ActivateMailTemplateService
{
    public function activate(MailTemplate $mailTemplate): MailTemplate
    {
        return DB::transaction(function () use ($mailTemplate): MailTemplate {
            MailTemplate::query()
                ->where('is_active', true)
                ->whereKeyNot($mailTemplate->getKey())
                ->update([
                    'is_active' => false,
                ]);

            $mailTemplate->forceFill([
                'is_active' => true,
            ])->save();

            return $mailTemplate->fresh();
        });
    }
}
