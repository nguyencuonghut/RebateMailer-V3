<?php

namespace App\Services\Mail;

use Illuminate\Contracts\View\Factory as ViewFactory;

class BuildMailCampaignRecipientPdfHtmlService
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {
    }

    /**
     * @param  array<string, mixed>  $preview
     * @param  array<string, mixed>|null  $signatureSnapshot
     */
    public function build(array $preview, ?array $signatureSnapshot = null, ?string $campaignName = null): string
    {
        return $this->viewFactory
            ->make('mail.campaign-recipient-pdf', [
                'preview' => $preview,
                'campaignName' => $campaignName,
                'signatureSnapshot' => $signatureSnapshot,
            ])
            ->render();
    }
}
