<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;

class UpdateMailCampaignDispatchStatusService
{
    public function refresh(MailCampaign $campaign): MailCampaign
    {
        $campaign->loadMissing('recipients');

        $pendingCount = $campaign->recipients->where('delivery_status', 'pending')->count();
        $queuedCount = $campaign->recipients->where('delivery_status', 'queued')->count();
        $sendingCount = $campaign->recipients->where('delivery_status', 'sending')->count();
        $sentCount = $campaign->recipients->where('delivery_status', 'sent')->count();
        $failedCount = $campaign->recipients->where('delivery_status', 'failed')->count();
        $totalCount = $campaign->recipients->count();

        if (($pendingCount + $queuedCount + $sendingCount) > 0) {
            $campaign->forceFill([
                'status' => 'dispatching',
            ])->save();

            return $campaign->refresh();
        }

        if ($totalCount > 0 && $sentCount === $totalCount) {
            $campaign->forceFill([
                'status' => 'completed',
            ])->save();

            return $campaign->refresh();
        }

        if ($failedCount > 0 || ($sentCount > 0 && $failedCount > 0)) {
            $campaign->forceFill([
                'status' => 'completed_with_failures',
            ])->save();

            return $campaign->refresh();
        }

        return $campaign->refresh();
    }
}
