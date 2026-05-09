<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'mail_campaign_recipient_id',
    'event_type',
    'status',
    'message',
    'context',
])]
class MailCampaignRecipientAttempt extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(MailCampaignRecipient::class, 'mail_campaign_recipient_id');
    }
}
