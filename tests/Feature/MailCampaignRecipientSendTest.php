<?php

namespace Tests\Feature;

use App\Jobs\DispatchMailCampaignRecipientJob;
use App\Mail\MailCampaignRecipientMail;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use App\Services\Mail\BuildMailCampaignRecipientEmailHtmlService;
use App\Services\Mail\BuildMailCampaignRecipientPreviewService;
use App\Services\Mail\LogMailCampaignRecipientAttemptService;
use App\Services\Mail\UpdateMailCampaignDispatchStatusService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class MailCampaignRecipientSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dispatch_job_sends_mail_and_marks_campaign_completed(): void
    {
        Mail::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);

        $previewService = Mockery::mock(BuildMailCampaignRecipientPreviewService::class);
        $capturedHtmlBody = null;
        $previewService->shouldReceive('build')
            ->once()
            ->withArgs(fn (MailCampaign $resolvedCampaign, int $recipientId): bool => $resolvedCampaign->id === $campaign->id && $recipientId === $recipient->id)
            ->andReturn([
                'recipient' => [
                    'id' => $recipient->id,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'recipientEmail' => $recipient->recipient_email,
                    'customerType' => $recipient->customer_type,
                ],
                'subject' => [
                    'renderedText' => 'Thư chiết khấu tháng 6',
                    'errors' => [],
                ],
                'greeting' => [
                    'renderedText' => 'Kính gửi Quý khách',
                    'errors' => [],
                ],
                'tables' => [],
                'errors' => [],
                'html' => '<html><body><h1>Preview mail</h1></body></html>',
            ]);
        app()->instance(BuildMailCampaignRecipientPreviewService::class, $previewService);

        Mail::assertNothingSent();

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
        );

        Mail::assertSent(MailCampaignRecipientMail::class, function (MailCampaignRecipientMail $mail) use ($recipient): bool {
            $GLOBALS['__mail_campaign_snapshot_html'] = $mail->htmlBody;

            return $mail->hasTo((string) $recipient->recipient_email)
                && $mail->subjectLine === 'Thư chiết khấu tháng 6';
        });

        $capturedHtmlBody = $GLOBALS['__mail_campaign_snapshot_html'] ?? null;
        unset($GLOBALS['__mail_campaign_snapshot_html']);

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $recipient->id,
            'delivery_status' => 'sent',
            'attempts_count' => 1,
            'latest_error_message' => null,
            'sent_subject_snapshot' => 'Thư chiết khấu tháng 6',
            'snapshot_version' => 1,
        ]);

        $recipient->refresh();

        $this->assertSame($capturedHtmlBody, $recipient->sent_html_snapshot);
        $this->assertNull($recipient->sent_signature_snapshot);

        $this->assertDatabaseHas('mail_campaign_recipient_attempts', [
            'mail_campaign_recipient_id' => $recipient->id,
            'event_type' => 'sent',
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $campaign->id,
            'status' => 'completed',
        ]);
    }

    public function test_dispatch_job_marks_campaign_completed_with_failures_when_other_recipient_failed(): void
    {
        Mail::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => ImportBatchAggregatedRecord::query()->create([
                'import_batch_id' => $campaign->import_batch_id,
                'customer_code' => '90301',
                'customer_type' => 'Khách thường',
                'source_sheets' => ['Tổng hợp'],
                'aggregated_payload' => [
                    'customerCode' => '90301',
                    'customerFullName' => '90301 - Công ty B',
                    'customerType' => 'Khách thường',
                    'tongHop' => [
                        'email' => '',
                    ],
                ],
            ])->id,
            'customer_code' => '90301',
            'customer_full_name' => '90301 - Công ty B',
            'customer_type' => 'Khách thường',
            'recipient_email' => null,
            'delivery_status' => 'failed',
            'attempts_count' => 1,
            'latest_error_message' => 'Không có email',
            'failed_at' => now(),
        ]);

        $previewService = Mockery::mock(BuildMailCampaignRecipientPreviewService::class);
        $previewService->shouldReceive('build')
            ->once()
            ->andReturn([
                'recipient' => [
                    'id' => $recipient->id,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'recipientEmail' => $recipient->recipient_email,
                    'customerType' => $recipient->customer_type,
                ],
                'subject' => [
                    'renderedText' => 'Thư chiết khấu tháng 6',
                    'errors' => [],
                ],
                'greeting' => [
                    'renderedText' => 'Kính gửi Quý khách',
                    'errors' => [],
                ],
                'tables' => [],
                'errors' => [],
                'html' => '<html><body><h1>Preview mail</h1></body></html>',
            ]);
        app()->instance(BuildMailCampaignRecipientPreviewService::class, $previewService);

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
        );

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $campaign->id,
            'status' => 'completed_with_failures',
        ]);
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignRecipient}
     */
    private function makeQueuedRecipientFixture(User $user): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-06',
            'name' => 'Batch tháng 6/2026',
            'original_file_name' => 'thang-6.xlsx',
            'stored_path' => 'imports/tmp/thang-6.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'email' => 'send@example.com',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 6',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 6',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Gửi thật',
            'status' => 'dispatching',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $recipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'send@example.com',
            'delivery_status' => 'queued',
            'attempts_count' => 0,
        ]);

        return [$campaign, $recipient];
    }
}
