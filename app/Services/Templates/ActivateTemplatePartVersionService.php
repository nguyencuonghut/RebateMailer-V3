<?php

namespace App\Services\Templates;

use App\Models\TemplatePartVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ActivateTemplatePartVersionService
{
    public function activate(TemplatePartVersion $templatePartVersion): TemplatePartVersion
    {
        return DB::transaction(function () use ($templatePartVersion): TemplatePartVersion {
            $part = $templatePartVersion->templatePart()->firstOrFail();
            $activatedAt = Carbon::now();

            $templatePartVersion->forceFill([
                'is_active' => true,
                'activated_at' => $activatedAt,
            ])->save();

            $activeVersionIdsToKeep = TemplatePartVersion::query()
                ->where('template_part_id', $part->getKey())
                ->where('is_active', true)
                ->orderByDesc('activated_at')
                ->orderByDesc('id')
                ->limit($part->max_active_versions)
                ->pluck('id');

            TemplatePartVersion::query()
                ->where('template_part_id', $part->getKey())
                ->where('is_active', true)
                ->whereNotIn('id', $activeVersionIdsToKeep)
                ->update([
                    'is_active' => false,
                ]);

            return $templatePartVersion->fresh(['templatePart']);
        });
    }
}
