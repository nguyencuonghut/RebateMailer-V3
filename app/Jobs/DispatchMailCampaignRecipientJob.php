<?php

namespace App\Jobs;

use App\Mail\MailCampaignRecipientMail;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Services\Mail\BuildMailCampaignRecipientEmailHtmlService;
use App\Services\Mail\BuildMailCampaignRecipientPreviewService;
use App\Services\Mail\BuildRepresentativeSignatureSnapshotService;
use App\Services\Mail\LogMailCampaignRecipientAttemptService;
use App\Services\Mail\UpdateMailCampaignDispatchStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use RuntimeException;
use Throwable;

class DispatchMailCampaignRecipientJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $mailCampaignRecipientId,
    ) {
        $this->onQueue((string) config('mail_campaigns.dispatch.queue', 'mail-dispatch'));
    }

    public function middleware(): array
    {
        return [
            new RateLimited('mail-campaign-dispatch'),
            (new WithoutOverlapping('mail-campaign-recipient:'.$this->mailCampaignRecipientId))
                ->expireAfter((int) config('mail_campaigns.dispatch.lock_seconds', 120))
                ->releaseAfter((int) config('mail_campaigns.dispatch.release_after_seconds', 15)),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return array_values(array_map(
            static fn (mixed $seconds): int => (int) $seconds,
            config('mail_campaigns.dispatch.backoff_seconds', [60, 300, 900]),
        ));
    }

    public function tries(): int
    {
        return (int) config('mail_campaigns.dispatch.tries', 50);
    }

    public function maxExceptions(): int
    {
        return (int) config('mail_campaigns.dispatch.max_exceptions', 5);
    }

    public function handle(
        BuildMailCampaignRecipientPreviewService $buildMailCampaignRecipientPreviewService,
        BuildMailCampaignRecipientEmailHtmlService $buildMailCampaignRecipientEmailHtmlService,
        BuildRepresentativeSignatureSnapshotService $buildRepresentativeSignatureSnapshotService,
        LogMailCampaignRecipientAttemptService $logMailCampaignRecipientAttemptService,
        UpdateMailCampaignDispatchStatusService $updateMailCampaignDispatchStatusService,
    ): void
    {
        $recipient = MailCampaignRecipient::query()->find($this->mailCampaignRecipientId);

        if (! $recipient instanceof MailCampaignRecipient) {
            return;
        }

        if (! in_array($recipient->delivery_status, ['queued', 'failed'], true)) {
            return;
        }

        $campaign = $recipient->campaign()->first();

        if (! $campaign instanceof MailCampaign) {
            throw new RuntimeException('Không tìm thấy chiến dịch gửi mail tương ứng với người nhận.');
        }

        $preview = $buildMailCampaignRecipientPreviewService->build($campaign, $recipient->id);

        if (! is_array($preview)) {
            $recipient->forceFill([
                'delivery_status' => 'failed',
                'attempts_count' => $recipient->attempts_count + 1,
                'latest_error_message' => 'Không dựng được preview email để gửi thật.',
                'failed_at' => now(),
            ])->save();

            $logMailCampaignRecipientAttemptService->log(
                $recipient,
                'render_failed',
                'failed',
                'Không dựng được preview email để gửi thật.',
            );

            $updateMailCampaignDispatchStatusService->refresh($campaign);

            return;
        }

        $subjectErrors = $preview['subject']['errors'] ?? [];
        $greetingErrors = $preview['greeting']['errors'] ?? [];
        $tableErrors = collect($preview['tables'] ?? [])
            ->flatMap(static fn (array $table): array => array_values($table['errors'] ?? []))
            ->all();
        $rootErrors = array_values($preview['errors'] ?? []);
        $allErrors = array_values(array_filter([
            ...$rootErrors,
            ...$subjectErrors,
            ...$greetingErrors,
            ...$tableErrors,
        ], static fn (mixed $message): bool => is_string($message) && $message !== ''));

        if ($allErrors !== []) {
            $message = $allErrors[0];

            $recipient->forceFill([
                'delivery_status' => 'failed',
                'attempts_count' => $recipient->attempts_count + 1,
                'latest_error_message' => $message,
                'failed_at' => now(),
            ])->save();

            $logMailCampaignRecipientAttemptService->log(
                $recipient,
                'render_failed',
                'failed',
                $message,
            );

            $updateMailCampaignDispatchStatusService->refresh($campaign);

            return;
        }

        $subjectLine = $preview['subject']['renderedText'] ?? $campaign->name;
        $emailHtml = $buildMailCampaignRecipientEmailHtmlService->build($preview, isPreview: false);
        $signatureSnapshot = null;

        try {
            $signatureSnapshot = $buildRepresentativeSignatureSnapshotService->build($campaign, $recipient);
        } catch (RuntimeException $exception) {
            $recipient->forceFill([
                'delivery_status' => 'failed',
                'attempts_count' => $recipient->attempts_count + 1,
                'latest_error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ])->save();

            $logMailCampaignRecipientAttemptService->log(
                $recipient,
                'render_failed',
                'failed',
                $exception->getMessage(),
            );

            $updateMailCampaignDispatchStatusService->refresh($campaign);

            return;
        }

        try {
            Mail::to((string) $recipient->recipient_email)->send(
                new MailCampaignRecipientMail((string) $subjectLine, $emailHtml)
            );

            $recipient->forceFill([
                'delivery_status' => 'sent',
                'attempts_count' => $recipient->attempts_count + 1,
                'latest_error_message' => null,
                'sent_at' => now(),
                'failed_at' => null,
                'sent_subject_snapshot' => (string) $subjectLine,
                'sent_html_snapshot' => $emailHtml,
                'sent_signature_snapshot' => $signatureSnapshot,
                'snapshot_version' => 1,
            ])->save();

            $logMailCampaignRecipientAttemptService->log(
                $recipient,
                'sent',
                'success',
                'Mail đã được gửi qua mailer cấu hình hiện tại.',
                [
                    'recipientEmail' => $recipient->recipient_email,
                    'subject' => $subjectLine,
                ],
            );

            $updateMailCampaignDispatchStatusService->refresh($campaign);
        } catch (Throwable $throwable) {
            $recipient->forceFill([
                'delivery_status' => 'failed',
                'attempts_count' => $recipient->attempts_count + 1,
                'latest_error_message' => $throwable->getMessage(),
                'failed_at' => now(),
            ])->save();

            $logMailCampaignRecipientAttemptService->log(
                $recipient,
                'dispatch_attempt_failed',
                'failed',
                $throwable->getMessage(),
                [
                    'exception' => $throwable::class,
                ],
            );

            $updateMailCampaignDispatchStatusService->refresh($campaign);

            throw $throwable;
        }
    }

    public function failed(Throwable $throwable): void
    {
        $recipient = MailCampaignRecipient::query()->find($this->mailCampaignRecipientId);

        if (! $recipient instanceof MailCampaignRecipient) {
            return;
        }

        $recipient->forceFill([
            'delivery_status' => 'failed',
            'latest_error_message' => $throwable->getMessage(),
            'failed_at' => now(),
        ])->save();

        app(LogMailCampaignRecipientAttemptService::class)->log(
            $recipient,
            'dispatch_failed',
            'failed',
            $throwable->getMessage(),
            [
                'exception' => $throwable::class,
            ],
        );

        $campaign = $recipient->campaign()->first();

        if ($campaign instanceof MailCampaign) {
            app(UpdateMailCampaignDispatchStatusService::class)->refresh($campaign);
        }
    }
}
