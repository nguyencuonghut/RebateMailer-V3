<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use App\Services\Mail\CreateMailCampaignService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailCampaignStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_mail_send_permission_can_create_campaign_and_materialize_recipients(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-04',
            'name' => 'Batch tháng 4/2026',
            'original_file_name' => 'thang-4.xlsx',
            'stored_path' => 'imports/tmp/thang-4.xlsx',
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
            'customer_code' => '11008',
            'customer_type' => 'Key Account',
            'source_sheets' => ['Key Account'],
            'aggregated_payload' => [
                'customerCode' => '11008',
                'customerFullName' => '11008 - Siêu thị B',
                'customerType' => 'Key Account',
                'keyAccount' => [
                    'email' => 'b@example.com',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 4-2026',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('mail.campaigns.store'), [
            'name' => 'Chiến dịch gửi mail tháng 4/2026',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Gửi thử nội bộ',
        ]);

        $campaign = \App\Models\MailCampaign::query()->firstOrFail();

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));

        $this->assertDatabaseHas('mail_campaigns', [
            'name' => 'Chiến dịch gửi mail tháng 4/2026',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertDatabaseCount('mail_campaign_recipients', 2);

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $firstRecord->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'recipient_email' => 'a@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $secondRecord->id,
            'customer_code' => '11008',
            'customer_full_name' => '11008 - Siêu thị B',
            'recipient_email' => 'b@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);
    }

    public function test_store_mail_campaign_fans_out_multiple_recipient_rows_for_semicolon_email_list(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

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
                'email' => 'a@example.com',
                'emails' => ['a@example.com', 'b@example.com', 'A@example.com', 'c@example.com'],
                'tongHop' => [
                    'email' => 'a@example.com',
                    'emails' => ['a@example.com', 'b@example.com', 'A@example.com', 'c@example.com'],
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 5-2026',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = app(CreateMailCampaignService::class)->create($user, [
            'name' => 'Chiến dịch gửi mail tháng 5/2026',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
        ]);

        $this->assertDatabaseCount('mail_campaign_recipients', 3);

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'recipient_email' => 'a@example.com',
        ]);
        $this->assertDatabaseHas('mail_campaign_recipients', [
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'recipient_email' => 'b@example.com',
        ]);
        $this->assertDatabaseHas('mail_campaign_recipients', [
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'recipient_email' => 'c@example.com',
        ]);
    }

    public function test_store_mail_campaign_rejects_template_month_that_differs_from_import_batch_month(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-06',
            'name' => 'Data import tháng 06-2026',
            'original_file_name' => 'thang-06-2026.xlsx',
            'stored_path' => 'imports/tmp/thang-06-2026.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '21033',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp', 'Cám cá'],
            'aggregated_payload' => [
                'customerCode' => '21033',
                'customerFullName' => '21033 - Đại lý tháng 06',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'month' => '06-2026',
                    'email' => 'customer@example.com',
                ],
                'camCa' => [
                    'month' => '06-2026',
                ],
            ],
        ]);

        $mayTemplateCanvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu mail gửi tháng 05-2026',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('mail.campaigns.store'), [
            'name' => 'Gửi mail tháng 06-2026',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $mayTemplateCanvas->id,
        ]);

        $response->assertSessionHasErrors([
            'mail_template_canvas_id' => 'Template email đang là tháng 05-2026 nhưng batch nhập liệu là tháng 06-2026. Vui lòng chọn template cùng tháng hoặc tạo lại chiến dịch sau khi kích hoạt template đúng.',
        ]);

        $this->assertDatabaseCount('mail_campaigns', 0);
        $this->assertDatabaseCount('mail_campaign_recipients', 0);
    }

    public function test_store_mail_campaign_requires_send_permission(): void
    {
        $viewer = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($viewer)
            ->post(route('mail.campaigns.store'), [
                'name' => 'Không được tạo',
                'import_batch_id' => 1,
                'mail_template_canvas_id' => 1,
            ])
            ->assertForbidden();
    }
}
