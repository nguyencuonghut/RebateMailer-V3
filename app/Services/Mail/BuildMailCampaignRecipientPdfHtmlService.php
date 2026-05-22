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
     * @param  array<string, string|null>  $signature
     */
    public function build(array $preview, array $signature): string
    {
        return $this->viewFactory
            ->make('mail.campaign-recipient-pdf', [
                'subjectLine' => (string) data_get($preview, 'subject.renderedText', ''),
                'bodyHtml' => null,
                'preview' => $preview,
                'signature' => $signature,
            ])
            ->render();
    }
}
