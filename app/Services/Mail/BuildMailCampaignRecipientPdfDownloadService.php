<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class BuildMailCampaignRecipientPdfDownloadService
{
    public function __construct(
        private readonly BuildMailCampaignRecipientPdfPayloadService $buildMailCampaignRecipientPdfPayloadService,
        private readonly BuildMailCampaignRecipientPdfHtmlService $buildMailCampaignRecipientPdfHtmlService,
    ) {
    }

    /**
     * @return array{binary: string, fileName: string}
     */
    public function build(MailCampaign $campaign, MailCampaignRecipient $recipient): array
    {
        $payload = $this->buildMailCampaignRecipientPdfPayloadService->build($campaign, $recipient);
        $renderedHtml = $this->buildMailCampaignRecipientPdfHtmlService->build($payload);

        return [
            'binary' => Pdf::setOption(['defaultFont' => 'DejaVu Sans'])
                ->loadHTML($renderedHtml)
                ->output(),
            'fileName' => sprintf(
                'mail-campaign-%d-recipient-%s.pdf',
                $campaign->id,
                $this->buildRecipientFileSlug($recipient),
            ),
        ];
    }

    private function buildRecipientFileSlug(MailCampaignRecipient $recipient): string
    {
        $base = trim((string) $recipient->customer_full_name);

        if ($base === '') {
            $base = (string) $recipient->customer_code;
        }

        $slug = Str::of($base)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '-')
            ->trim('-')
            ->value();

        return $slug !== '' ? $slug : (string) $recipient->customer_code;
    }
}
