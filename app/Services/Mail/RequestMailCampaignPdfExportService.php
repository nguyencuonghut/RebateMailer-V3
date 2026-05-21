<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;
use App\Models\MailCampaignExport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestMailCampaignPdfExportService
{
    public function request(MailCampaign $campaign, User $user): MailCampaignExport
    {
        $campaign->loadMissing(['recipients', 'exports']);

        $sentRecipientsCount = $campaign->recipients
            ->where('delivery_status', 'sent')
            ->count();

        if ($sentRecipientsCount === 0) {
            throw ValidationException::withMessages([
                'export' => 'Chiến dịch chưa có mail đã gửi để export PDF.',
            ]);
        }

        $hasInProgressExport = $campaign->exports
            ->contains(fn (MailCampaignExport $export): bool => $export->export_type === 'sent-mails-pdf'
                && in_array($export->status, ['queued', 'processing'], true));

        if ($hasInProgressExport) {
            throw ValidationException::withMessages([
                'export' => 'Đang có một yêu cầu export PDF mail đã gửi chưa hoàn tất.',
            ]);
        }

        return DB::transaction(fn (): MailCampaignExport => MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'sent-mails-pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'total_recipients' => $sentRecipientsCount,
            'exported_recipients' => 0,
        ]));
    }
}
