<?php

namespace Tests\Feature;

use App\Jobs\GenerateMailCampaignPdfExportJob;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignExport;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MailCampaignPdfExportJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
        config()->set('mail_campaigns.exports.disk', 'local');
        config()->set('mail_campaigns.exports.directory', 'mail-exports/pdf');
    }

    public function test_job_can_generate_pdf_file_and_mark_export_completed(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $export] = $this->makeExportFixture($user);

        $job = new GenerateMailCampaignPdfExportJob($export->id);
        $job->handle(app(\App\Services\Mail\GenerateMailCampaignPdfExportService::class));

        $export->refresh();

        $this->assertSame('completed', $export->status);
        $this->assertSame(1, $export->exported_recipients);
        $this->assertNotNull($export->completed_at);
        $this->assertSame('local', $export->file_disk);
        $this->assertNotNull($export->file_path);
        $this->assertNotNull($export->file_name);

        Storage::disk('local')->assertExists($export->file_path);
        $this->assertNotSame('', Storage::disk('local')->get($export->file_path));
    }

    public function test_job_marks_export_failed_when_recipient_cannot_be_rendered(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $export] = $this->makeFailedExportFixture($user);

        try {
            $job = new GenerateMailCampaignPdfExportJob($export->id);
            $job->handle(app(\App\Services\Mail\GenerateMailCampaignPdfExportService::class));
            $this->fail('Expected export job to throw when recipient cannot be rendered.');
        } catch (\Throwable) {
            // expected
        }

        $export->refresh();

        $this->assertSame('failed', $export->status);
        $this->assertNotNull($export->failed_at);
        $this->assertSame(0, $export->exported_recipients);
        $this->assertNotNull($export->error_message);
        $this->assertStringContainsString('Template canvas hiện chưa có liên kết legacy mail template', $export->error_message);
    }

    public function test_job_marks_export_failed_when_campaign_no_longer_has_recipients(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $export] = $this->makeExportFixture($user);

        $campaign->recipients()->delete();

        try {
            $job = new GenerateMailCampaignPdfExportJob($export->id);
            $job->handle(app(\App\Services\Mail\GenerateMailCampaignPdfExportService::class));
            $this->fail('Expected export job to throw when campaign has no recipients.');
        } catch (\Throwable) {
            // expected
        }

        $export->refresh();

        $this->assertSame('failed', $export->status);
        $this->assertSame(0, $export->exported_recipients);
        $this->assertNotNull($export->failed_at);
        $this->assertSame('Chiến dịch không có người nhận để tạo file PDF.', $export->error_message);
        $this->assertNull($export->completed_at);
        $this->assertNull($export->file_disk);
        $this->assertNull($export->file_path);
        $this->assertNull($export->file_name);
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignExport}
     */
    private function makeExportFixture(User $user): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-PDF-2026-05',
            'name' => 'Batch PDF tháng 5/2026',
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
            'name' => 'Canvas PDF tháng 5',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch PDF tháng 5',
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
            'delivery_status' => 'sent',
            'attempts_count' => 1,
            'sent_subject_snapshot' => 'Chế độ tháng 03.2026 - 90300 - Công ty A',
            'sent_html_snapshot' => '<!DOCTYPE html><html lang="vi"><body><div style="font-size:15px; white-space:pre-line; color:#1e293b;">Kính gửi 90300 - Công ty A</div></body></html>',
            'sent_signature_snapshot' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => null,
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
            'snapshot_version' => 1,
            'sent_at' => now(),
        ]);

        $export = MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'total_recipients' => 1,
            'exported_recipients' => 0,
        ]);

        return [$campaign, $export];
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignExport}
     */
    private function makeFailedExportFixture(User $user): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-PDF-FAIL-2026-05',
            'name' => 'Batch PDF lỗi',
            'original_file_name' => 'thang-5.xlsx',
            'stored_path' => 'imports/tmp/thang-5.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90399',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90399',
                'customerFullName' => '90399 - Công ty lỗi',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'email' => 'fail@example.com',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas PDF lỗi',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch PDF lỗi',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90399',
            'customer_full_name' => '90399 - Công ty lỗi',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'fail@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        $export = MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'total_recipients' => 1,
            'exported_recipients' => 0,
        ]);

        return [$campaign, $export];
    }
}
