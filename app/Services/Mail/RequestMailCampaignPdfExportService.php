<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;
use App\Models\MailCampaignExport;
use App\Models\User;
use RuntimeException;

class RequestMailCampaignPdfExportService
{
    public function request(User $user, MailCampaign $campaign): MailCampaignExport
    {
        $campaign->loadMissing('recipients');

        $recipientCount = $campaign->recipients->count();

        if ($recipientCount === 0) {
            throw new RuntimeException('Chiến dịch chưa có người nhận để tạo export PDF.');
        }

        $hasInProgressExport = MailCampaignExport::query()
            ->where('mail_campaign_id', $campaign->id)
            ->where('export_type', 'pdf')
            ->whereIn('status', ['queued', 'processing'])
            ->exists();

        if ($hasInProgressExport) {
            throw new RuntimeException('Chiến dịch đang có yêu cầu export PDF chưa hoàn tất.');
        }

        return MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'total_recipients' => $recipientCount,
            'exported_recipients' => 0,
        ]);
    }
}
