<?php

namespace App\Services\Mail;

use App\Models\MailCampaignRecipient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\Factory as ViewFactory;
use RuntimeException;

class BuildMailCampaignRecipientSnapshotPdfService
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {
    }

    public function build(MailCampaignRecipient $recipient, ?string $campaignName = null): string
    {
        $page = $this->buildPagePayload($recipient, $campaignName);

        $html = $this->viewFactory
            ->make('mail.campaign-recipient-snapshot-pdf', [
                'campaignName' => $campaignName,
                'page' => $page,
            ])
            ->render();

        return Pdf::loadHTML($html)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setPaper('a4')
            ->output();
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
