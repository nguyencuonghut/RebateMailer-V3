<?php

namespace Tests\Feature;

use App\Jobs\DispatchMailCampaignRecipientJob;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MailCampaignDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_schedule_campaign_for_future_dispatch(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign] = $this->makeCampaignFixture($user);
        $scheduledAt = CarbonImmutable::parse('2026-05-10 09:30:00', 'Asia/Ho_Chi_Minh');

        $response = $this->actingAs($user)->post(route('mail.campaigns.schedule', $campaign), [
            'scheduled_at' => $scheduledAt->toIso8601String(),
        ]);

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $campaign->id,
            'status' => 'scheduled',
        ]);

        $campaign->refresh();
        $this->assertNotNull($campaign->scheduled_at);
        $this->assertSame($scheduledAt->utc()->format('Y-m-d H:i:s'), $campaign->scheduled_at?->utc()->format('Y-m-d H:i:s'));
    }

    public function test_user_can_start_dispatch_and_queue_each_recipient_job(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $firstRecipient, $secondRecipient] = $this->makeCampaignFixture($user);

        $response = $this->actingAs($user)->post(route('mail.campaigns.dispatch', $campaign));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $campaign->id,
            'status' => 'dispatching',
        ]);

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $firstRecipient->id,
            'delivery_status' => 'queued',
            'latest_error_message' => null,
        ]);

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $secondRecipient->id,
            'delivery_status' => 'failed',
            'latest_error_message' => 'Không có email người nhận để đưa vào hàng đợi gửi mail.',
        ]);

        $this->assertDatabaseHas('mail_campaign_recipient_attempts', [
            'mail_campaign_recipient_id' => $firstRecipient->id,
            'event_type' => 'queued',
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('mail_campaign_recipient_attempts', [
            'mail_campaign_recipient_id' => $secondRecipient->id,
            'event_type' => 'queue_blocked',
            'status' => 'failed',
            'message' => 'Không có email người nhận để đưa vào hàng đợi gửi mail.',
        ]);

        Queue::assertPushed(DispatchMailCampaignRecipientJob::class, 1);
        Queue::assertPushed(DispatchMailCampaignRecipientJob::class, function (DispatchMailCampaignRecipientJob $job) use ($firstRecipient): bool {
            return $job->mailCampaignRecipientId === $firstRecipient->id;
        });
    }

    public function test_dispatch_scheduled_campaigns_command_starts_due_campaigns_only(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$dueCampaign, $dueRecipient] = $this->makeCampaignFixture($user);
        [$futureCampaign] = $this->makeCampaignFixture($user, 'IMP-2026-04', 'Chiến dịch tương lai');

        $dueCampaign->update([
            'status' => 'scheduled',
            'scheduled_at' => CarbonImmutable::now()->subMinutes(5),
        ]);

        $futureCampaign->update([
            'status' => 'scheduled',
            'scheduled_at' => CarbonImmutable::now()->addHour(),
        ]);

        $this->artisan('mail:dispatch-scheduled-campaigns')
            ->expectsOutputToContain('Đã mở dispatch cho 1 campaign đến giờ gửi.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $dueCampaign->id,
            'status' => 'dispatching',
        ]);

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $futureCampaign->id,
            'status' => 'scheduled',
        ]);

        Queue::assertPushed(DispatchMailCampaignRecipientJob::class, function (DispatchMailCampaignRecipientJob $job) use ($dueRecipient): bool {
            return $job->mailCampaignRecipientId === $dueRecipient->id;
        });
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignRecipient, 2: MailCampaignRecipient}
     */
    private function makeCampaignFixture(User $user, string $batchCode = 'IMP-2026-03', string $campaignName = 'Chiến dịch tháng 3'): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => $batchCode,
            'name' => 'Batch '.strtolower($batchCode),
            'original_file_name' => strtolower($batchCode).'.xlsx',
            'stored_path' => 'imports/tmp/'.strtolower($batchCode).'.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        $firstRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'email' => 'a@example.com',
                ],
            ],
        ]);

        $secondRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
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
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => $campaignName,
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Gửi thử',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $firstRecipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $firstRecord->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'a@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        $secondRecipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $secondRecord->id,
            'customer_code' => '90301',
            'customer_full_name' => '90301 - Công ty B',
            'customer_type' => 'Khách thường',
            'recipient_email' => null,
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        return [$campaign, $firstRecipient, $secondRecipient];
    }
}
