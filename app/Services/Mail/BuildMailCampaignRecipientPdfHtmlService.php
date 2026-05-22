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
     * @param  array{subjectLine: string, bodyHtml: string|null, preview: array<string, mixed>|null, signature: array<string, string|null>}  $payload
     */
    public function build(array $payload): string
    {
        return $this->viewFactory
            ->make('mail.campaign-recipient-pdf', [
                'subjectLine' => (string) ($payload['subjectLine'] ?? ''),
                'bodyHtml' => $payload['bodyHtml'] ?? null,
                'preview' => $payload['preview'] ?? null,
                'signature' => $payload['signature'] ?? [],
            ])
            ->render();
    }
}
