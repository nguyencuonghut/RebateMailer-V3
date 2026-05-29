<?php

namespace App\Jobs;

use App\Models\MailCampaignExport;
use App\Services\Mail\GenerateMailCampaignPdfExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateMailCampaignPdfExportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

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

    public function failed(Throwable $exception): void
    {
        $export = MailCampaignExport::query()->find($this->mailCampaignExportId);

        if (! $export || $export->status === 'completed') {
            return;
        }

        $export->forceFill([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'failed_at' => now(),
            'completed_at' => null,
        ])->save();
    }
}
