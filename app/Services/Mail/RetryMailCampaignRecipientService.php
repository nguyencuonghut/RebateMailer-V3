<?php

namespace App\Services\Mail;

use App\Jobs\DispatchMailCampaignRecipientJob;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RetryMailCampaignRecipientService
{
    public function __construct(
        private readonly LogMailCampaignRecipientAttemptService $logMailCampaignRecipientAttemptService,
        private readonly UpdateMailCampaignDispatchStatusService $updateMailCampaignDispatchStatusService,
    ) {
    }

    public function retry(MailCampaign $campaign, MailCampaignRecipient $recipient, ?User $actor = null): MailCampaignRecipient
    {
        if ($recipient->mail_campaign_id !== $campaign->id) {
            throw new RuntimeException('Người nhận không thuộc chiến dịch đang chọn.');
        }

        if ($campaign->status === 'cancelled') {
            throw new RuntimeException('Chiến dịch đã hủy không thể retry gửi mail.');
        }

        $shouldDispatch = false;

        DB::transaction(function () use ($campaign, $recipient, $actor, &$shouldDispatch): void {
            $campaign->refresh();
            $recipient->refresh();

            if (blank($recipient->recipient_email)) {
                $message = 'Retry bị chặn vì người nhận chưa có email hợp lệ.';

                $recipient->forceFill([
                    'delivery_status' => 'failed',
                    'attempts_count' => $recipient->attempts_count + 1,
                    'latest_error_message' => $message,
                    'failed_at' => now(),
                ])->save();

                $this->logMailCampaignRecipientAttemptService->log(
                    $recipient,
                    'retry_blocked',
                    'failed',
                    $message,
                    [
                        'actorUserId' => $actor?->id,
                        'recipientEmail' => $recipient->recipient_email,
                    ],
                );

                return;
            }

            $recipient->forceFill([
                'delivery_status' => 'queued',
                'attempts_count' => $recipient->attempts_count + 1,
                'latest_error_message' => null,
                'failed_at' => null,
            ])->save();

            $campaign->forceFill([
                'status' => 'dispatching',
                'updated_by' => $actor?->id,
            ])->save();

            $this->logMailCampaignRecipientAttemptService->log(
                $recipient,
                'retry_queued',
                'queued',
                'Đã đưa người nhận trở lại hàng đợi gửi mail.',
                [
                    'actorUserId' => $actor?->id,
                    'recipientEmail' => $recipient->recipient_email,
                ],
            );

            $shouldDispatch = true;
        });

        if ($shouldDispatch) {
            DispatchMailCampaignRecipientJob::dispatch($recipient->id);
        }

        $this->updateMailCampaignDispatchStatusService->refresh($campaign);

        return $recipient->refresh();
    }
}
