<?php

namespace App\Services\Mail;

use App\Models\MailCampaignRecipient;
use App\Models\MailCampaignRecipientAttempt;

class LogMailCampaignRecipientAttemptService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(
        MailCampaignRecipient $recipient,
        string $eventType,
        string $status,
        ?string $message = null,
        array $context = [],
    ): MailCampaignRecipientAttempt {
        return MailCampaignRecipientAttempt::query()->create([
            'mail_campaign_recipient_id' => $recipient->id,
            'event_type' => $eventType,
            'status' => $status,
            'message' => $message,
            'context' => $context === [] ? null : $context,
        ]);
    }
}
