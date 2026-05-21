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
        return $this->resolveRepresentativeSignatureFromCanvasService->resolve(
            $campaign,
            (string) $recipient->customer_type,
        );
    }
}
