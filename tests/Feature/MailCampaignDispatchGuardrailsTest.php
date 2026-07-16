<?php

namespace Tests\Feature;

use App\Jobs\DispatchMailCampaignRecipientJob;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use App\Services\Mail\StartMailCampaignDispatchService;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class MailCampaignDispatchGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dispatch_recipient_job_uses_mail_dispatch_queue_and_guardrail_middleware(): void
    {
        config()->set('mail_campaigns.dispatch.queue', 'mail-dispatch-test');
        config()->set('mail_campaigns.dispatch.lock_seconds', 180);
        config()->set('mail_campaigns.dispatch.release_after_seconds', 25);
        config()->set('mail_campaigns.dispatch.tries', 7);
        config()->set('mail_campaigns.dispatch.max_exceptions', 8);
        config()->set('mail_campaigns.dispatch.retry_until_hours', 4);
        config()->set('mail_campaigns.dispatch.backoff_jitter_seconds', 30);
        config()->set('mail_campaigns.dispatch.backoff_seconds', [30, 120, 600]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-16 08:00:00'));

        $job = new DispatchMailCampaignRecipientJob(99);
        $middleware = $job->middleware();

        $this->assertSame('mail-dispatch-test', $job->queue);
        $this->assertCount(2, $middleware);
        $this->assertInstanceOf(RateLimited::class, $middleware[0]);
        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[1]);
        $this->assertSame([36, 126, 606], $job->backoff());
        $this->assertSame(7, $job->tries());
        $this->assertSame(8, $job->maxExceptions());
        $this->assertSame(CarbonImmutable::parse('2026-07-16 12:00:00')->timestamp, $job->retryUntil()->getTimestamp());

        CarbonImmutable::setTestNow();
    }

    public function test_start_dispatch_service_queues_jobs_on_mail_dispatch_queue(): void
    {
        Queue::fake();
        config()->set('mail_campaigns.dispatch.queue', 'mail-dispatch-test');

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeCampaignFixture($user);

        app(StartMailCampaignDispatchService::class)->start($campaign, $user);

        Queue::assertPushed(DispatchMailCampaignRecipientJob::class, function (DispatchMailCampaignRecipientJob $job) use ($recipient): bool {
            return $job->mailCampaignRecipientId === $recipient->id
                && $job->queue === 'mail-dispatch-test';
        });
    }

    public function test_start_dispatch_service_rejects_campaign_when_template_month_differs_from_batch_month(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-06',
            'name' => 'Data import tháng 06-2026',
            'original_file_name' => 'thang-06-2026.xlsx',
            'stored_path' => 'imports/tmp/thang-06-2026.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '21033',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '21033',
                'customerFullName' => '21033 - Đại lý tháng 06',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'month' => '06-2026',
                    'email' => 'customer@example.com',
                ],
            ],
        ]);

        $templateCanvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu mail gửi tháng 05-2026',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Gửi mail tháng 06-2026',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $templateCanvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '21033',
            'customer_full_name' => '21033 - Đại lý tháng 06',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'customer@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        try {
            app(StartMailCampaignDispatchService::class)->start($campaign, $user);
            $this->fail('Expected dispatch to reject a campaign whose template month differs from the batch month.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'Template email đang là tháng 05-2026 nhưng batch nhập liệu là tháng 06-2026.',
                $exception->getMessage(),
            );
        }

        Queue::assertNothingPushed();
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignRecipient}
     */
    private function makeCampaignFixture(User $user): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-05',
            'name' => 'Batch tháng 5/2026',
            'original_file_name' => 'thang-5.xlsx',
            'stored_path' => 'imports/tmp/thang-5.xlsx',
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
                    'email' => 'a@example.com',
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
            'name' => 'Chiến dịch tháng 5',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Gửi thử',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $recipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'a@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        return [$campaign, $recipient];
    }
}
