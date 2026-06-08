<?php

namespace Tests\Feature;

use App\Jobs\DispatchMailCampaignRecipientJob;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MailCampaignRecipientRetryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_retry_failed_recipient_and_queue_job_again(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeFailedRecipientFixture($user, 'retry@example.com', 'SMTP timeout');

        $response = $this->actingAs($user)->post(route('mail.campaigns.recipients.retry', [
            'mailCampaign' => $campaign->id,
            'mailCampaignRecipient' => $recipient->id,
        ]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $campaign->id,
            'status' => 'dispatching',
        ]);

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $recipient->id,
            'delivery_status' => 'queued',
            'attempts_count' => 2,
            'latest_error_message' => null,
        ]);

        $this->assertDatabaseHas('mail_campaign_recipient_attempts', [
            'mail_campaign_recipient_id' => $recipient->id,
            'event_type' => 'retry_queued',
            'status' => 'queued',
        ]);

        Queue::assertPushed(DispatchMailCampaignRecipientJob::class, function (DispatchMailCampaignRecipientJob $job) use ($recipient): bool {
            return $job->mailCampaignRecipientId === $recipient->id;
        });
    }

    public function test_retry_failed_recipient_without_email_logs_blocked_error(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeFailedRecipientFixture($user, null, 'Không có email người nhận để đưa vào hàng đợi gửi mail.');

        $response = $this->actingAs($user)->post(route('mail.campaigns.recipients.retry', [
            'mailCampaign' => $campaign->id,
            'mailCampaignRecipient' => $recipient->id,
        ]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $recipient->id,
            'delivery_status' => 'failed',
            'attempts_count' => 2,
            'latest_error_message' => 'Retry bị chặn vì người nhận chưa có email hợp lệ.',
        ]);

        $this->assertDatabaseHas('mail_campaign_recipient_attempts', [
            'mail_campaign_recipient_id' => $recipient->id,
            'event_type' => 'retry_blocked',
            'status' => 'failed',
            'message' => 'Retry bị chặn vì người nhận chưa có email hợp lệ.',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_retry_failed_email_does_not_change_other_recipient_rows_of_same_customer(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-10',
            'name' => 'Batch tháng 10/2026',
            'original_file_name' => 'thang-10.xlsx',
            'stored_path' => 'imports/tmp/thang-10.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90900',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90900',
                'customerFullName' => '90900 - Công ty retry nhiều email',
                'customerType' => 'Khách thường',
                'email' => 'failed@example.com',
                'emails' => ['failed@example.com', 'sent@example.com'],
                'tongHop' => [
                    'email' => 'failed@example.com; sent@example.com',
                    'emails' => ['failed@example.com', 'sent@example.com'],
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu retry nhiều email',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch retry nhiều email',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Retry thử nhiều email',
            'status' => 'completed_with_failures',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $failedRecipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90900',
            'customer_full_name' => '90900 - Công ty retry nhiều email',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'failed@example.com',
            'delivery_status' => 'failed',
            'attempts_count' => 1,
            'latest_error_message' => 'SMTP timeout',
            'failed_at' => now(),
        ]);

        $sentRecipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90900',
            'customer_full_name' => '90900 - Công ty retry nhiều email',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'sent@example.com',
            'delivery_status' => 'sent',
            'attempts_count' => 1,
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('mail.campaigns.recipients.retry', [
            'mailCampaign' => $campaign->id,
            'mailCampaignRecipient' => $failedRecipient->id,
        ]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $failedRecipient->id,
            'delivery_status' => 'queued',
            'attempts_count' => 2,
        ]);

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $sentRecipient->id,
            'delivery_status' => 'sent',
            'attempts_count' => 1,
        ]);

        Queue::assertPushed(DispatchMailCampaignRecipientJob::class, function (DispatchMailCampaignRecipientJob $job) use ($failedRecipient): bool {
            return $job->mailCampaignRecipientId === $failedRecipient->id;
        });
        Queue::assertNotPushed(DispatchMailCampaignRecipientJob::class, function (DispatchMailCampaignRecipientJob $job) use ($sentRecipient): bool {
            return $job->mailCampaignRecipientId === $sentRecipient->id;
        });
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignRecipient}
     */
    private function makeFailedRecipientFixture(User $user, ?string $email, string $latestErrorMessage): array
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
                    'email' => $email ?? '',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 6',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 6',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Retry thử',
            'status' => 'completed_with_failures',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $recipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => 'Khách thường',
            'recipient_email' => $email,
            'delivery_status' => 'failed',
            'attempts_count' => 1,
            'latest_error_message' => $latestErrorMessage,
            'failed_at' => now(),
        ]);

        return [$campaign, $recipient];
    }
}
