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
    }

    public function test_generate_pdf_export_job_creates_pdf_file_and_marks_export_completed(): void
    {
        Storage::fake('local');

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$export, $campaign] = $this->makeQueuedPdfExportFixture($user, withValidSnapshots: true);

        $job = new GenerateMailCampaignPdfExportJob($export->id);
        $job->handle(app(\App\Services\Mail\GenerateMailCampaignPdfExportService::class));

        $export->refresh();
        $this->assertSame('completed', $export->status);
        $this->assertSame(2, $export->total_recipients);
        $this->assertSame(2, $export->exported_recipients);
        $this->assertNotNull($export->started_at);
        $this->assertNotNull($export->completed_at);
        $this->assertNull($export->failed_at);
        $this->assertNull($export->error_message);
        $this->assertNotNull($export->file_name);
        $this->assertNotNull($export->file_path);
        Storage::disk('local')->assertExists((string) $export->file_path);

        $pdfBinary = Storage::disk('local')->get((string) $export->file_path);

        $this->assertStringStartsWith('%PDF', $pdfBinary);
        $this->assertStringEndsWith('.pdf', (string) $export->file_name);

        $campaign->refresh();
        $this->assertTrue($campaign->exports()->whereKey($export->id)->exists());
    }

    public function test_generate_pdf_export_job_handles_hundreds_of_sent_mails_without_loading_fixture_size_into_export_counts(): void
    {
        Storage::fake('local');

        config()->set('mail_campaigns.exports.chunk_size', 25);

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$export] = $this->makeQueuedPdfExportFixture($user, withValidSnapshots: true, recipientCount: 250);

        $job = new GenerateMailCampaignPdfExportJob($export->id);
        $job->handle(app(\App\Services\Mail\GenerateMailCampaignPdfExportService::class));

        $export->refresh();

        $this->assertSame('completed', $export->status);
        $this->assertSame(250, $export->total_recipients);
        $this->assertSame(250, $export->exported_recipients);
        $this->assertNotNull($export->file_path);
        Storage::disk('local')->assertExists((string) $export->file_path);
    }

    public function test_generate_pdf_export_job_marks_export_failed_when_snapshot_is_missing(): void
    {
        Storage::fake('local');

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$export] = $this->makeQueuedPdfExportFixture($user, withValidSnapshots: false);

        $job = new GenerateMailCampaignPdfExportJob($export->id);
        $job->handle(app(\App\Services\Mail\GenerateMailCampaignPdfExportService::class));

        $export->refresh();

        $this->assertSame('failed', $export->status);
        $this->assertNotNull($export->started_at);
        $this->assertNotNull($export->failed_at);
        $this->assertNull($export->completed_at);
        $this->assertNotNull($export->error_message);
        $this->assertStringContainsString('snapshot', mb_strtolower((string) $export->error_message));
        $this->assertNull($export->file_path);
    }

    public function test_generate_pdf_export_job_marks_export_failed_when_campaign_has_no_sent_recipients(): void
    {
        Storage::fake('local');

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$export] = $this->makeQueuedPdfExportFixture($user, withValidSnapshots: true);

        MailCampaignRecipient::query()->where('mail_campaign_id', $export->mail_campaign_id)->delete();

        $job = new GenerateMailCampaignPdfExportJob($export->id);
        $job->handle(app(\App\Services\Mail\GenerateMailCampaignPdfExportService::class));

        $export->refresh();

        $this->assertSame('failed', $export->status);
        $this->assertNotNull($export->error_message);
        $this->assertStringContainsString('không tìm thấy mail đã gửi', mb_strtolower((string) $export->error_message));
    }

    /**
     * @return array{0: MailCampaignExport, 1: MailCampaign}
     */
    private function makeQueuedPdfExportFixture(User $user, bool $withValidSnapshots, int $recipientCount = 2): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-07',
            'name' => 'Batch tháng 7/2026',
            'original_file_name' => 'thang-7.xlsx',
            'stored_path' => 'imports/tmp/thang-7.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu PDF tháng 7',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch PDF tháng 7',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Generate PDF',
            'status' => 'completed',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        foreach ($this->makeRecipientSeeds($recipientCount) as [$customerCode, $representativeName]) {
            $record = ImportBatchAggregatedRecord::query()->create([
                'import_batch_id' => $batch->id,
                'customer_code' => $customerCode,
                'customer_type' => 'Khách thường',
                'source_sheets' => ['Tổng hợp'],
                'aggregated_payload' => [
                    'customerCode' => $customerCode,
                    'customerFullName' => $customerCode.' - Công ty A',
                    'customerType' => 'Khách thường',
                    'tongHop' => ['email' => strtolower($customerCode).'@example.com'],
                ],
            ]);

            MailCampaignRecipient::query()->create([
                'mail_campaign_id' => $campaign->id,
                'import_batch_aggregated_record_id' => $record->id,
                'customer_code' => $customerCode,
                'customer_full_name' => $customerCode.' - Công ty A',
                'customer_type' => 'Khách thường',
                'recipient_email' => strtolower($customerCode).'@example.com',
                'delivery_status' => 'sent',
                'attempts_count' => 1,
                'sent_at' => now(),
                'sent_subject_snapshot' => 'Thư chiết khấu tháng 7',
                'sent_html_snapshot' => $withValidSnapshots
                    ? '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>Mail</title></head><body><div style="padding:16px"><h1>Mail đã gửi</h1><p>Nội dung cho '.$customerCode.'</p></div></body></html>'
                    : null,
                'sent_signature_snapshot' => $withValidSnapshots ? [
                    'partType' => 'representative-signature',
                    'customerType' => 'Khách thường',
                    'title' => 'Đại diện công ty',
                    'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFsLXNpZw==',
                    'representativeRole' => 'Giám đốc kinh doanh',
                    'representativeName' => $representativeName,
                ] : null,
                'snapshot_version' => $withValidSnapshots ? 1 : null,
            ]);
        }

        $export = MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'sent-mails-pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'total_recipients' => $recipientCount,
            'exported_recipients' => 0,
        ]);

        return [$export, $campaign];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function makeRecipientSeeds(int $count): array
    {
        $seeds = [];

        for ($index = 0; $index < $count; $index++) {
            $customerCode = str_pad((string) (90300 + $index), 5, '0', STR_PAD_LEFT);
            $seeds[] = [
                $customerCode,
                'Đại diện '.$customerCode,
            ];
        }

        return $seeds;
    }
}
