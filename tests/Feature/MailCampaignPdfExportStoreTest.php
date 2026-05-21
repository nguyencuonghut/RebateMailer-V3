<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use App\Jobs\GenerateMailCampaignPdfExportJob;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MailCampaignPdfExportStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_mail_view_permission_can_queue_pdf_export_for_campaign_with_sent_recipients(): void
    {
        Queue::fake();
        $user = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        $campaign = $this->makeCampaignFixture($user, sentRecipients: 2, failedRecipients: 1);

        $response = $this->actingAs($user)
            ->post(route('mail.campaigns.pdf-exports.store', ['mailCampaign' => $campaign->id]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('mail_campaign_exports', [
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'sent-mails-pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
            'total_recipients' => 2,
            'exported_recipients' => 0,
        ]);

        Queue::assertPushed(GenerateMailCampaignPdfExportJob::class, 1);
    }

    public function test_pdf_export_request_is_rejected_when_campaign_has_no_sent_recipients(): void
    {
        $user = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        $campaign = $this->makeCampaignFixture($user, sentRecipients: 0, failedRecipients: 1);

        $response = $this->actingAs($user)
            ->from(route('mail.index', ['campaign' => $campaign->id]))
            ->post(route('mail.campaigns.pdf-exports.store', ['mailCampaign' => $campaign->id]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));
        $response->assertSessionHasErrors(['export']);

        $this->assertDatabaseCount('mail_campaign_exports', 0);
    }

    public function test_pdf_export_request_is_rejected_when_campaign_already_has_export_in_progress(): void
    {
        $user = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        $campaign = $this->makeCampaignFixture($user, sentRecipients: 1, failedRecipients: 0);

        \DB::table('mail_campaign_exports')->insert([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'sent-mails-pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'total_recipients' => 1,
            'exported_recipients' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('mail.index', ['campaign' => $campaign->id]))
            ->post(route('mail.campaigns.pdf-exports.store', ['mailCampaign' => $campaign->id]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));
        $response->assertSessionHasErrors(['export']);

        $this->assertDatabaseCount('mail_campaign_exports', 1);
    }

    public function test_pdf_export_request_can_be_created_again_after_previous_export_failed(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        $campaign = $this->makeCampaignFixture($user, sentRecipients: 1, failedRecipients: 0);

        \DB::table('mail_campaign_exports')->insert([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'sent-mails-pdf',
            'status' => 'failed',
            'requested_by' => $user->id,
            'requested_at' => now()->subMinute(),
            'failed_at' => now()->subSeconds(30),
            'total_recipients' => 1,
            'exported_recipients' => 0,
            'error_message' => 'Snapshot thiếu',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subSeconds(30),
        ]);

        $response = $this->actingAs($user)
            ->post(route('mail.campaigns.pdf-exports.store', ['mailCampaign' => $campaign->id]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('mail_campaign_exports', 2);
        $this->assertDatabaseHas('mail_campaign_exports', [
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'sent-mails-pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
        ]);

        Queue::assertPushed(GenerateMailCampaignPdfExportJob::class, 1);
    }

    private function makeCampaignFixture(User $user, int $sentRecipients, int $failedRecipients): MailCampaign
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-06',
            'name' => 'Batch tháng 6/2026',
            'original_file_name' => 'thang-6.xlsx',
            'stored_path' => 'imports/tmp/thang-6.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
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
            'notes' => 'Export PDF',
            'status' => 'completed_with_failures',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $sequence = 0;

        foreach ($sentRecipients > 0 ? range(1, $sentRecipients) : [] as $index) {
            $record = ImportBatchAggregatedRecord::query()->create([
                'import_batch_id' => $batch->id,
                'customer_code' => sprintf('903%02d', $sequence),
                'customer_type' => 'Khách thường',
                'source_sheets' => ['Tổng hợp'],
                'aggregated_payload' => [
                    'customerCode' => sprintf('903%02d', $sequence),
                    'customerFullName' => sprintf('903%02d - Công ty %s', $sequence, chr(65 + $sequence)),
                    'customerType' => 'Khách thường',
                    'tongHop' => ['email' => sprintf('sent-%d@example.com', $sequence)],
                ],
            ]);

            MailCampaignRecipient::query()->create([
                'mail_campaign_id' => $campaign->id,
                'import_batch_aggregated_record_id' => $record->id,
                'customer_code' => sprintf('903%02d', $sequence),
                'customer_full_name' => sprintf('903%02d - Công ty %s', $sequence, chr(65 + $sequence)),
                'customer_type' => 'Khách thường',
                'recipient_email' => sprintf('sent-%d@example.com', $sequence),
                'delivery_status' => 'sent',
                'attempts_count' => 1,
                'sent_at' => now(),
            ]);

            $sequence++;
        }

        foreach ($failedRecipients > 0 ? range(1, $failedRecipients) : [] as $index) {
            $record = ImportBatchAggregatedRecord::query()->create([
                'import_batch_id' => $batch->id,
                'customer_code' => sprintf('903%02d', $sequence),
                'customer_type' => 'Khách thường',
                'source_sheets' => ['Tổng hợp'],
                'aggregated_payload' => [
                    'customerCode' => sprintf('903%02d', $sequence),
                    'customerFullName' => sprintf('903%02d - Công ty %s', $sequence, chr(65 + $sequence)),
                    'customerType' => 'Khách thường',
                    'tongHop' => ['email' => sprintf('failed-%d@example.com', $sequence)],
                ],
            ]);

            MailCampaignRecipient::query()->create([
                'mail_campaign_id' => $campaign->id,
                'import_batch_aggregated_record_id' => $record->id,
                'customer_code' => sprintf('903%02d', $sequence),
                'customer_full_name' => sprintf('903%02d - Công ty %s', $sequence, chr(65 + $sequence)),
                'customer_type' => 'Khách thường',
                'recipient_email' => sprintf('failed-%d@example.com', $sequence),
                'delivery_status' => 'failed',
                'attempts_count' => 1,
                'failed_at' => now(),
                'latest_error_message' => 'SMTP timeout',
            ]);

            $sequence++;
        }

        return $campaign;
    }
}
