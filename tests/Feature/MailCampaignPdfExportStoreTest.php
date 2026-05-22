<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignExport;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailCampaignPdfExportStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_mail_send_permission_can_request_pdf_export_for_campaign(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $campaign = $this->makeCampaignFixture($user);

        $response = $this->actingAs($user)
            ->post(route('mail.campaigns.exports.pdf.store', $campaign));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));

        $this->assertDatabaseHas('mail_campaign_exports', [
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
            'total_recipients' => 1,
            'exported_recipients' => 0,
        ]);
    }

    public function test_request_pdf_export_requires_mail_send_permission(): void
    {
        $viewer = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        $owner = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $campaign = $this->makeCampaignFixture($owner);

        $this->actingAs($viewer)
            ->post(route('mail.campaigns.exports.pdf.store', $campaign))
            ->assertForbidden();
    }

    public function test_request_pdf_export_is_blocked_when_campaign_has_no_recipients(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-EXP-EMPTY',
            'name' => 'Batch rỗng',
            'original_file_name' => 'empty.xlsx',
            'stored_path' => 'imports/tmp/empty.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas export rỗng',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Campaign rỗng',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('mail.index', ['campaign' => $campaign->id]))
            ->post(route('mail.campaigns.exports.pdf.store', $campaign))
            ->assertRedirect(route('mail.index', ['campaign' => $campaign->id]))
            ->assertSessionHas('error', 'Chiến dịch chưa có người nhận để tạo export PDF.');

        $this->assertDatabaseCount('mail_campaign_exports', 0);
    }

    public function test_request_pdf_export_is_blocked_when_another_pdf_export_is_in_progress(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $campaign = $this->makeCampaignFixture($user);

        MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'pdf',
            'status' => 'processing',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'started_at' => now(),
            'total_recipients' => 1,
            'exported_recipients' => 0,
        ]);

        $this->actingAs($user)
            ->from(route('mail.index', ['campaign' => $campaign->id]))
            ->post(route('mail.campaigns.exports.pdf.store', $campaign))
            ->assertRedirect(route('mail.index', ['campaign' => $campaign->id]))
            ->assertSessionHas('error', 'Chiến dịch đang có yêu cầu export PDF chưa hoàn tất.');

        $this->assertDatabaseCount('mail_campaign_exports', 1);
    }

    private function makeCampaignFixture(User $user): MailCampaign
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-EXP-2026-05',
            'name' => 'Batch export tháng 5/2026',
            'original_file_name' => 'thang-5.xlsx',
            'stored_path' => 'imports/tmp/thang-5.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
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
            'name' => 'Canvas export tháng 5',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch export tháng 5',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'a@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        return $campaign;
    }
}
