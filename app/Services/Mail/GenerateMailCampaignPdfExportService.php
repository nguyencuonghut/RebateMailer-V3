<?php

namespace App\Services\Mail;

use App\Models\MailCampaignExport;
use App\Models\MailCampaignRecipient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateMailCampaignPdfExportService
{
    public function __construct(
        private readonly BuildMailCampaignRecipientPreviewService $buildMailCampaignRecipientPreviewService,
        private readonly BuildMailCampaignRecipientPdfHtmlService $buildMailCampaignRecipientPdfHtmlService,
        private readonly BuildRepresentativeSignatureSnapshotService $buildRepresentativeSignatureSnapshotService,
        private readonly ExtractMailCampaignRecipientBodyHtmlService $extractMailCampaignRecipientBodyHtmlService,
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

            $pdfBinary = Pdf::setOption(['defaultFont' => 'DejaVu Sans'])
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
        $hasSnapshot = filled($recipient->sent_subject_snapshot)
            && filled($recipient->sent_html_snapshot)
            && is_array($recipient->sent_signature_snapshot);

        if ($hasSnapshot) {
            return [
                'subjectLine' => (string) $recipient->sent_subject_snapshot,
                'bodyHtml' => $this->extractMailCampaignRecipientBodyHtmlService->extract((string) $recipient->sent_html_snapshot),
                'preview' => null,
                'signature' => $recipient->sent_signature_snapshot,
            ];
        }

        $preview = $this->buildMailCampaignRecipientPreviewService->build($campaign, $recipient->id);

        if (! is_array($preview)) {
            throw new RuntimeException(sprintf(
                'Không dựng được preview để export PDF cho khách hàng %s.',
                $recipient->customer_code,
            ));
        }

        $errors = array_values(array_filter([
            ...($preview['errors'] ?? []),
            ...($preview['subject']['errors'] ?? []),
            ...($preview['greeting']['errors'] ?? []),
            ...collect($preview['tables'] ?? [])->flatMap(fn (array $table): array => array_values($table['errors'] ?? []))->all(),
        ], static fn (mixed $message): bool => is_string($message) && $message !== ''));

        if ($errors !== []) {
            throw new RuntimeException($errors[0]);
        }

        return [
            'subjectLine' => (string) data_get($preview, 'subject.renderedText', $campaign->name),
            'bodyHtml' => null,
            'preview' => $preview,
            'signature' => $this->buildRepresentativeSignatureSnapshotService->build($campaign, $recipient),
        ];
    }
}
