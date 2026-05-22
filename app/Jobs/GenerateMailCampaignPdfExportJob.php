<?php

namespace App\Jobs;

use App\Models\MailCampaignExport;
use App\Services\Mail\GenerateMailCampaignPdfExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateMailCampaignPdfExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $mailCampaignExportId,
    ) {
        $this->onQueue((string) config('mail_campaigns.exports.queue', 'mail-export-pdf'));
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('mail-campaign-export:'.$this->mailCampaignExportId))
                ->expireAfter((int) config('mail_campaigns.exports.lock_seconds', 300)),
        ];
    }

    public function tries(): int
    {
        return (int) config('mail_campaigns.exports.tries', 5);
    }

    public function handle(GenerateMailCampaignPdfExportService $generateMailCampaignPdfExportService): void
    {
        $export = MailCampaignExport::query()->find($this->mailCampaignExportId);

        if (! $export instanceof MailCampaignExport) {
            Log::warning('mail_campaign_pdf_export.job_missing_export', [
                'export_id' => $this->mailCampaignExportId,
                'queue' => $this->queue,
            ]);

            return;
        }

        Log::info('mail_campaign_pdf_export.job_started', [
            'export_id' => $export->id,
            'campaign_id' => $export->mail_campaign_id,
            'queue' => $this->queue,
        ]);

        try {
            $generateMailCampaignPdfExportService->generate($export);
        } catch (Throwable $throwable) {
            $export->forceFill([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $throwable->getMessage(),
            ])->save();

            Log::error('mail_campaign_pdf_export.job_failed', [
                'export_id' => $export->id,
                'campaign_id' => $export->mail_campaign_id,
                'queue' => $this->queue,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $throwable): void
    {
        $export = MailCampaignExport::query()->find($this->mailCampaignExportId);

        if (! $export instanceof MailCampaignExport) {
            return;
        }

        $export->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $throwable->getMessage(),
        ])->save();

        Log::error('mail_campaign_pdf_export.job_failed_callback', [
            'export_id' => $export->id,
            'campaign_id' => $export->mail_campaign_id,
            'queue' => $this->queue,
            'error' => $throwable->getMessage(),
        ]);
    }
}
