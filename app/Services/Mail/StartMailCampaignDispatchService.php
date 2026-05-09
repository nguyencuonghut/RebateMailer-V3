<?php

namespace App\Services\Mail;

use App\Jobs\DispatchMailCampaignRecipientJob;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StartMailCampaignDispatchService
{
    public function __construct(
        private readonly LogMailCampaignRecipientAttemptService $logMailCampaignRecipientAttemptService,
        private readonly UpdateMailCampaignDispatchStatusService $updateMailCampaignDispatchStatusService,
    ) {
    }

    public function start(MailCampaign $campaign, ?User $actor = null): MailCampaign
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'], true)) {
            throw new RuntimeException('Chỉ campaign ở trạng thái nháp hoặc đã lên lịch mới được mở dispatch.');
        }

        $queuedRecipientIds = [];

        DB::transaction(function () use ($campaign, $actor, &$queuedRecipientIds): void {
            $campaign->loadMissing('recipients');

            $campaign->forceFill([
                'status' => 'dispatching',
                'scheduled_at' => null,
                'updated_by' => $actor?->id,
            ])->save();

            foreach ($campaign->recipients as $recipient) {
                if (! $recipient instanceof MailCampaignRecipient) {
                    continue;
                }

                if ($recipient->delivery_status !== 'pending') {
                    continue;
                }

                if (blank($recipient->recipient_email)) {
                    $message = 'Không có email người nhận để đưa vào hàng đợi gửi mail.';

                    $recipient->forceFill([
                        'delivery_status' => 'failed',
                        'attempts_count' => $recipient->attempts_count + 1,
                        'latest_error_message' => $message,
                        'failed_at' => now(),
                    ])->save();

                    $this->logMailCampaignRecipientAttemptService->log(
                        $recipient,
                        'queue_blocked',
                        'failed',
                        $message,
                        [
                            'actorUserId' => $actor?->id,
                            'recipientEmail' => $recipient->recipient_email,
                        ],
                    );

                    continue;
                }

                $recipient->forceFill([
                    'delivery_status' => 'queued',
                    'latest_error_message' => null,
                ])->save();

                $this->logMailCampaignRecipientAttemptService->log(
                    $recipient,
                    'queued',
                    'queued',
                    'Đã đưa người nhận vào hàng đợi gửi mail.',
                    [
                        'actorUserId' => $actor?->id,
                        'recipientEmail' => $recipient->recipient_email,
                    ],
                );

                $queuedRecipientIds[] = $recipient->id;
            }
        });

        foreach ($queuedRecipientIds as $recipientId) {
            DispatchMailCampaignRecipientJob::dispatch($recipientId);
        }

        return $this->updateMailCampaignDispatchStatusService->refresh($campaign);
    }
}
