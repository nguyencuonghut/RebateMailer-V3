<?php

namespace App\Services\Mail;

use App\Models\MailCampaignExport;
use App\Models\MailCampaignRecipient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use RuntimeException;

class GenerateMailCampaignPdfExportService
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {
    }

    public function generate(MailCampaignExport $export): MailCampaignExport
    {
        $export->loadMissing('campaign');

        $campaign = $export->campaign;

        if (! $campaign) {
            throw new RuntimeException('Không tìm thấy chiến dịch tương ứng với yêu cầu export PDF.');
        }

        $recipientsQuery = MailCampaignRecipient::query()
            ->where('mail_campaign_id', $campaign->id)
            ->where('delivery_status', 'sent')
            ->select([
                'id',
                'mail_campaign_id',
                'customer_code',
                'customer_full_name',
                'customer_type',
                'recipient_email',
                'sent_subject_snapshot',
                'sent_html_snapshot',
                'sent_signature_snapshot',
            ])
            ->orderBy('customer_code')
            ->orderBy('id');

        $totalRecipients = (clone $recipientsQuery)->count();

        if ($totalRecipients === 0) {
            throw new RuntimeException('Không tìm thấy mail đã gửi để export PDF.');
        }

        $export->forceFill([
            'status' => 'processing',
            'started_at' => $export->started_at ?? now(),
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'file_name' => null,
            'file_path' => null,
            'total_recipients' => $totalRecipients,
            'exported_recipients' => 0,
        ])->save();

        $chunkSize = max(1, (int) config('mail_campaigns.exports.chunk_size', 100));
        $tempHtmlPath = tempnam(sys_get_temp_dir(), 'mail-campaign-export-');

        if ($tempHtmlPath === false) {
            throw new RuntimeException('Không tạo được file tạm để export PDF.');
        }

        $processedRecipients = 0;

        try {
            $this->writeDocumentStart($tempHtmlPath, $campaign->name);

            foreach ($recipientsQuery->cursor() as $recipient) {
                $page = $this->buildPagePayload($recipient, $campaign->name);
                $pageHtml = $this->viewFactory
                    ->make('mail.partials.campaign-export-pdf-page', ['page' => $page])
                    ->render();

                file_put_contents($tempHtmlPath, $pageHtml.PHP_EOL, FILE_APPEND);
                $processedRecipients++;

                if ($processedRecipients % $chunkSize === 0) {
                    $export->forceFill([
                        'exported_recipients' => $processedRecipients,
                    ])->save();
                }
            }

            $this->writeDocumentEnd($tempHtmlPath);

            $html = file_get_contents($tempHtmlPath);

            if ($html === false) {
                throw new RuntimeException('Không đọc được file HTML tạm để export PDF.');
            }

            $pdfBinary = Pdf::loadHTML($html)
                ->setOption('defaultFont', 'DejaVu Sans')
                ->setPaper('a4')
                ->output();
        } catch (Throwable $exception) {
            @unlink($tempHtmlPath);

            throw $exception;
        }

        @unlink($tempHtmlPath);

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
            'total_recipients' => $totalRecipients,
            'exported_recipients' => $processedRecipients,
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

    private function writeDocumentStart(string $path, ?string $campaignName): void
    {
        $title = e($campaignName ?: 'Export PDF mail chiến dịch');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$title}</title>
        <style>
            @page {
                margin: 16mm 12mm;
            }

            body {
                margin: 0;
                font-family: 'DejaVu Sans', sans-serif;
                color: #0f172a;
            }

            .pdf-page {
                page-break-after: always;
            }

            .pdf-page:last-child {
                page-break-after: auto;
            }
        </style>
    </head>
    <body>

HTML;

        file_put_contents($path, $html);
    }

    private function writeDocumentEnd(string $path): void
    {
        file_put_contents($path, '    </body>'.PHP_EOL.'</html>'.PHP_EOL, FILE_APPEND);
    }
}
