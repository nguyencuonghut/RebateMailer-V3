<?php

namespace App\Services\Mail;

use App\Models\MailCampaignExport;
use App\Models\MailCampaignRecipient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateMailCampaignPdfExportService
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {
    }

    public function generate(MailCampaignExport $export): MailCampaignExport
    {
        $export->loadMissing([
            'campaign.recipients' => fn ($query) => $query
                ->where('delivery_status', 'sent')
                ->orderBy('customer_code')
                ->orderBy('id'),
        ]);

        $campaign = $export->campaign;

        if (! $campaign) {
            throw new RuntimeException('Không tìm thấy chiến dịch tương ứng với yêu cầu export PDF.');
        }

        $export->forceFill([
            'status' => 'processing',
            'started_at' => $export->started_at ?? now(),
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'file_name' => null,
            'file_path' => null,
            'exported_recipients' => 0,
        ])->save();

        $recipients = $campaign->recipients;

        if ($recipients->isEmpty()) {
            throw new RuntimeException('Không tìm thấy mail đã gửi để export PDF.');
        }

        $pages = $recipients
            ->map(fn (MailCampaignRecipient $recipient): array => $this->buildPagePayload($recipient, $campaign->name))
            ->all();

        $html = $this->viewFactory
            ->make('mail.campaign-export-pdf-document', [
                'campaign' => $campaign,
                'export' => $export,
                'pages' => $pages,
            ])
            ->render();

        $pdfBinary = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->output();

        $fileName = sprintf(
            '%s_%s.pdf',
            Str::slug($campaign->name ?: 'mail-campaign-export'),
            now()->format('Ymd_His'),
        );
        $filePath = trim((string) config('mail_campaigns.exports.storage_path', 'mail-exports/pdf'), '/').'/'.$fileName;
        $disk = (string) config('mail_campaigns.exports.disk', 'local');

        Storage::disk($disk)->put($filePath, $pdfBinary);

        $export->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
            'failed_at' => null,
            'error_message' => null,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'total_recipients' => count($pages),
            'exported_recipients' => count($pages),
        ])->save();

        return $export->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPagePayload(MailCampaignRecipient $recipient, ?string $campaignName): array
    {
        $subjectLine = trim((string) $recipient->sent_subject_snapshot);
        $sentHtml = trim((string) $recipient->sent_html_snapshot);
        $signatureSnapshot = $recipient->sent_signature_snapshot;

        if ($subjectLine === '' || $sentHtml === '' || ! is_array($signatureSnapshot)) {
            throw new RuntimeException(sprintf(
                'Recipient %s thiếu snapshot cần thiết để export PDF.',
                $recipient->customer_code ?: '#'.$recipient->id,
            ));
        }

        return [
            'recipient' => [
                'id' => $recipient->id,
                'customerCode' => $recipient->customer_code,
                'customerFullName' => $recipient->customer_full_name,
                'recipientEmail' => $recipient->recipient_email,
                'customerType' => $recipient->customer_type,
            ],
            'campaignName' => $campaignName,
            'subjectLine' => $subjectLine,
            'bodyHtml' => $this->extractBodyHtml($sentHtml),
            'signatureSnapshot' => $signatureSnapshot,
        ];
    }

    private function extractBodyHtml(string $html): string
    {
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $matches) === 1) {
            return trim((string) ($matches[1] ?? ''));
        }

        return $html;
    }
}
