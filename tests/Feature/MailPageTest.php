<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MailPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_mail_page_renders_campaign_creation_form_and_options(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-03',
            'name' => 'Batch tháng 3/2026',
            'original_file_name' => 'thang-3.xlsx',
            'stored_path' => 'imports/tmp/thang-3.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
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
            'name' => 'Mẫu gửi mail tháng 3-2026',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('mail.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('title', 'Điều phối gửi mail')
                ->where('canManageCampaigns', true)
                ->has('batchOptions', 1)
                ->where('batchOptions.0.batchId', $batch->id)
                ->where('batchOptions.0.batchCode', 'IMP-2026-03')
                ->has('templateOptions', 1)
                ->where('templateOptions.0.canvasId', $canvas->id)
                ->where('selectedCampaign', null)
                ->where('campaignOptions', [])
                ->where('recipientList', [])
            );
    }

    public function test_mail_page_exposes_campaign_progress_payload(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-04',
            'name' => 'Batch tháng 4/2026',
            'original_file_name' => 'thang-4.xlsx',
            'stored_path' => 'imports/tmp/thang-4.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
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
                    'email' => 'b@example.com',
                ],
            ],
        ]);

        $thirdRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90302',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90302',
                'customerFullName' => '90302 - Công ty C',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'email' => 'c@example.com',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 4-2026',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 4',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Đợt gửi kiểm thử',
            'status' => 'dispatching',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $firstRecord->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'a@example.com',
            'delivery_status' => 'queued',
            'attempts_count' => 0,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $secondRecord->id,
            'customer_code' => '90301',
            'customer_full_name' => '90301 - Công ty B',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'b@example.com',
            'delivery_status' => 'sent',
            'attempts_count' => 1,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $thirdRecord->id,
            'customer_code' => '90302',
            'customer_full_name' => '90302 - Công ty C',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'c@example.com',
            'delivery_status' => 'failed',
            'attempts_count' => 2,
            'latest_error_message' => 'SMTP timeout',
        ]);

        $this->actingAs($user)
            ->get(route('mail.index', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('selectedCampaign.id', $campaign->id)
                ->where('selectedCampaign.createdBy', $user->name)
                ->where('campaignOptions.0.createdBy', $user->name)
                ->where('selectedCampaign.createdAt', $campaign->created_at?->toIso8601String())
                ->where('campaignOptions.0.createdAt', $campaign->created_at?->toIso8601String())
                ->where('selectedCampaign.recipientSummary.total', 3)
                ->where('selectedCampaign.recipientSummary.queued', 1)
                ->where('selectedCampaign.recipientSummary.sent', 1)
                ->where('selectedCampaign.recipientSummary.failed', 1)
                ->where('selectedCampaign.progress.batchId', $batch->id)
                ->where('selectedCampaign.progress.batchCode', 'IMP-2026-04')
                ->where('selectedCampaign.progress.totalRecipients', 3)
                ->where('selectedCampaign.progress.processedRecipients', 2)
                ->where('selectedCampaign.progress.queuedRecipients', 1)
                ->where('selectedCampaign.progress.pendingRecipients', 0)
                ->where('selectedCampaign.progress.sentRecipients', 1)
                ->where('selectedCampaign.progress.failedRecipients', 1)
                ->where('selectedCampaign.progress.completionPercent', 67)
                ->where('selectedCampaign.progress.queuedPercent', 33)
                ->where('selectedCampaign.progress.sentPercent', 33)
                ->where('selectedCampaign.progress.failedPercent', 33)
                ->where('recipientList.0.sourceSheetsLabel', 'Tổng hợp')
                ->where('recipientList.1.sourceSheetsLabel', 'Tổng hợp')
                ->where('recipientList.2.sourceSheetsLabel', 'Tổng hợp')
            );
    }
}
