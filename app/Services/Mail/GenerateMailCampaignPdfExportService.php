<?php

namespace App\Services\Mail;

use App\Models\MailCampaignExport;
use App\Models\MailCampaignRecipient;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateMailCampaignPdfExportService
{
    public function __construct(
        private readonly BuildMailCampaignRecipientPdfPayloadService $buildMailCampaignRecipientPdfPayloadService,
    ) {
    }

    public function generate(MailCampaignExport $export): MailCampaignExport
    {
        $export->loadMissing(['campaign.recipients']);

        $campaign = $export->campaign;

        if (! $campaign) {
            throw new RuntimeException('Không tìm thấy chiến dịch để tạo export PDF.');
        }

        $export->forceFill([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
            'failed_at' => null,
        ])->save();

        try {
            $pages = [];
            $recipients = $campaign->recipients()->orderBy('customer_code')->get();

            foreach ($recipients as $recipient) {
                $pages[] = $this->buildRecipientPagePayload($campaign->fresh(), $recipient->fresh());
            }

            if ($pages === []) {
                throw new RuntimeException('Chiến dịch không có người nhận để tạo file PDF.');
            }

            $documentTitle = sprintf('mail-campaign-%d-export', $campaign->id);
            $renderedHtml = view('mail.campaign-export-pdf-document', [
                'documentTitle' => $documentTitle,
                'pages' => $pages,
            ])->render();

            $pdfBinary = \Barryvdh\DomPDF\Facade\Pdf::setOption(['defaultFont' => 'DejaVu Sans'])
                ->loadHTML($renderedHtml)
                ->output();

            $disk = (string) config('mail_campaigns.exports.disk', 'local');
            $directory = trim((string) config('mail_campaigns.exports.directory', 'mail-exports/pdf'), '/');
            $fileName = sprintf('mail-campaign-%d-export-%d.pdf', $campaign->id, $export->id);
            $filePath = $directory.'/'.$fileName;

            Storage::disk($disk)->put($filePath, $pdfBinary);

            $export->forceFill([
                'status' => 'completed',
                'file_disk' => $disk,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'exported_recipients' => count($pages),
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $throwable) {
            $export->forceFill([
                'status' => 'failed',
                'error_message' => $throwable->getMessage(),
                'failed_at' => now(),
                'completed_at' => null,
                'file_disk' => null,
                'file_path' => null,
                'file_name' => null,
                'exported_recipients' => 0,
            ])->save();

            throw $throwable;
        }

        return $export->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRecipientPagePayload($campaign, MailCampaignRecipient $recipient): array
    {
        return $this->buildMailCampaignRecipientPdfPayloadService->build($campaign, $recipient);
    }
}
