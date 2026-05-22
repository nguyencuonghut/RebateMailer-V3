<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;

class BuildRepresentativeSignatureSnapshotService
{
    public function __construct(
        private readonly ResolveRepresentativeSignatureFromCanvasService $resolveRepresentativeSignatureFromCanvasService,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function build(MailCampaign $campaign, MailCampaignRecipient $recipient): array
    {
        $block = $this->resolveRepresentativeSignatureFromCanvasService->resolve(
            $campaign,
            (string) $recipient->customer_type,
        );

        return [
            'title' => isset($block['title']) ? (string) $block['title'] : 'Đại diện công ty',
            'signatureImageDataUrl' => isset($block['signatureImageDataUrl']) && is_string($block['signatureImageDataUrl'])
                ? $block['signatureImageDataUrl']
                : null,
            'representativeRole' => isset($block['representativeRole']) ? (string) $block['representativeRole'] : '',
            'representativeName' => isset($block['representativeName']) ? (string) $block['representativeName'] : '',
        ];
    }
}
