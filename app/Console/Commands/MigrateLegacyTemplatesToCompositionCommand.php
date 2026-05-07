<?php

namespace App\Console\Commands;

use App\Services\Templates\SyncLegacyMailTemplateToCompositionService;
use Illuminate\Console\Command;

class MigrateLegacyTemplatesToCompositionCommand extends Command
{
    protected $signature = 'templates:migrate-prototype-to-composition';

    protected $description = 'Migrate legacy mail_templates prototype data into template parts, part versions, and canvas bindings.';

    public function handle(SyncLegacyMailTemplateToCompositionService $syncLegacyMailTemplateToCompositionService): int
    {
        $syncLegacyMailTemplateToCompositionService->syncAll();

        $this->info('Đã migrate prototype template sang composition model.');

        return self::SUCCESS;
    }
}
