<?php

namespace App\Jobs;

use App\Models\MailCampaignExport;
use App\Services\Mail\GenerateMailCampaignPdfExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateMailCampaignPdfExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $mailCampaignExportId,
    ) {
        $this->onQueue((string) config('mail_campaigns.exports.queue', 'default'));
    }

    public function handle(GenerateMailCampaignPdfExportService $generateMailCampaignPdfExportService): void
    {
        $export = MailCampaignExport::query()->find($this->mailCampaignExportId);

        if (! $export) {
            return;
        }

        if (! in_array($export->status, ['queued', 'processing'], true)) {
            return;
        }

        $generateMailCampaignPdfExportService->generate($export);
    }
}
