<?php

namespace App\Services\Mail;

use Illuminate\Contracts\View\Factory as ViewFactory;

class BuildMailCampaignRecipientEmailHtmlService
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return string
     */
    public function build(array $preview, ?string $campaignName = null, bool $isPreview = true): string
    {
        return $this->viewFactory
            ->make('mail.campaign-recipient-preview', [
                'preview' => $preview,
                'campaignName' => $campaignName,
                'isPreview' => $isPreview,
            ])
            ->render();
    }
}
