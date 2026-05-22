<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'mail_campaign_id',
    'import_batch_aggregated_record_id',
    'customer_code',
    'customer_full_name',
    'customer_type',
    'recipient_email',
    'delivery_status',
    'attempts_count',
    'latest_error_message',
    'sent_at',
    'failed_at',
])]
class MailCampaignRecipient extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempts_count' => 'integer',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MailCampaign::class, 'mail_campaign_id');
    }

    public function aggregatedRecord(): BelongsTo
    {
        return $this->belongsTo(ImportBatchAggregatedRecord::class, 'import_batch_aggregated_record_id');
    }

    public function attemptLogs(): HasMany
    {
        return $this->hasMany(MailCampaignRecipientAttempt::class, 'mail_campaign_recipient_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
