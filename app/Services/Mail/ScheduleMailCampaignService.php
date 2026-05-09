<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;
use App\Models\User;
use Carbon\CarbonImmutable;
use RuntimeException;

class ScheduleMailCampaignService
{
    public function schedule(MailCampaign $campaign, CarbonImmutable $scheduledAt, ?User $actor = null): MailCampaign
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'], true)) {
            throw new RuntimeException('Chỉ campaign ở trạng thái nháp hoặc đã lên lịch mới được cập nhật lịch gửi.');
        }

        $campaign->forceFill([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'updated_by' => $actor?->id,
        ])->save();

        return $campaign->refresh();
    }
}
