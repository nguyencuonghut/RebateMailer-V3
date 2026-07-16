<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignExport;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_mail_page_exposes_pdf_export_request_state_for_selected_campaign(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-05',
            'name' => 'Batch tháng 5/2026',
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
            'name' => 'Mẫu gửi mail tháng 5-2026',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 5',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Đợt gửi có export PDF',
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

        $export = MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'pdf',
            'status' => 'queued',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'total_recipients' => 1,
            'exported_recipients' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('mail.index', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('selectedCampaign.id', $campaign->id)
                ->where('selectedCampaign.canRequestPdfExport', false)
                ->where('selectedCampaign.pdfExportDisabledReason', 'Chiến dịch đang có yêu cầu export PDF chưa hoàn tất.')
                ->where('selectedCampaign.latestPdfExport.id', $export->id)
                ->where('selectedCampaign.latestPdfExport.status', 'queued')
                ->where('selectedCampaign.latestPdfExport.totalRecipients', 1)
                ->where('selectedCampaign.latestPdfExport.exportedRecipients', 0)
            );
    }

    public function test_mail_page_allows_new_pdf_export_request_after_latest_export_failed(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-06',
            'name' => 'Batch tháng 6/2026',
            'original_file_name' => 'thang-6.xlsx',
            'stored_path' => 'imports/tmp/thang-6.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90400',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90400',
                'customerFullName' => '90400 - Công ty B',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'email' => 'b@example.com',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 6-2026',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 6',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90400',
            'customer_full_name' => '90400 - Công ty B',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'b@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        $export = MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'pdf',
            'status' => 'failed',
            'requested_by' => $user->id,
            'requested_at' => now()->subMinute(),
            'started_at' => now()->subMinute(),
            'failed_at' => now(),
            'error_message' => 'PDF render failed',
            'total_recipients' => 1,
            'exported_recipients' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('mail.index', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('selectedCampaign.id', $campaign->id)
                ->where('selectedCampaign.canRequestPdfExport', true)
                ->where('selectedCampaign.pdfExportDisabledReason', null)
                ->where('selectedCampaign.latestPdfExport.id', $export->id)
                ->where('selectedCampaign.latestPdfExport.status', 'failed')
                ->where('selectedCampaign.latestPdfExport.errorMessage', 'PDF render failed')
                ->where('selectedCampaign.latestPdfExport.totalRecipients', 1)
                ->where('selectedCampaign.latestPdfExport.exportedRecipients', 0)
            );
    }

    public function test_mail_page_exposes_recipient_pdf_export_capability_in_recipient_list(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-07',
            'name' => 'Batch tháng 7/2026',
            'original_file_name' => 'thang-7.xlsx',
            'stored_path' => 'imports/tmp/thang-7.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90600',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90600',
                'customerFullName' => '90600 - Công ty PDF',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'email' => 'pdf@example.com',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas tháng 7-2026',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 7',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90600',
            'customer_full_name' => '90600 - Công ty PDF',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'pdf@example.com',
            'delivery_status' => 'sent',
            'attempts_count' => 1,
            'sent_subject_snapshot' => 'Chế độ tháng 03.2026 - 90600 - Công ty PDF',
            'sent_html_snapshot' => '<!DOCTYPE html><html lang="vi"><body><div>Kính gửi 90600 - Công ty PDF</div></body></html>',
            'sent_signature_snapshot' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => null,
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn PDF',
            ],
            'snapshot_version' => 1,
            'sent_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('mail.index', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('selectedCampaign.id', $campaign->id)
                ->where('recipientList.0.customerCode', '90600')
                ->where('recipientList.0.canExportPdf', true)
            );
    }

    public function test_mail_page_exposes_preview_issues_in_recipient_list_before_opening_preview(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Mẫu gửi mail tháng 05-2026',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.4-C',
                'sections' => [
                    ['type' => 'subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'tong-hop-table', 'rows' => [
                        [
                            'content' => 'Khuyến mại từ 25/5-30/5',
                            'rowType' => 'child',
                            'columnKey' => 'Khuyến mại từ 25/5-30/5',
                            'hideWhenValueZero' => true,
                            'isBold' => false,
                        ],
                    ]],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas tháng 05-2026',
            'is_active' => false,
            'legacy_mail_template_id' => $mailTemplate->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-06',
            'name' => 'Data import tháng 06-2026',
            'original_file_name' => 'thang-06.xlsx',
            'stored_path' => 'imports/tmp/thang-06.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Tổng hợp' => [
                        'fixedHeaders' => ['Tổng cộng', 'Bằng chữ'],
                        'dynamicHeaders' => [],
                    ],
                ],
            ],
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
                    'customerCode' => '21033',
                    'customerFullName' => '21033 - Đại lý tháng 06',
                    'email' => 'customer@example.com',
                    'grandTotal' => '1000000',
                    'totalInWords' => 'Một triệu đồng chẵn.',
                    'dynamicItems' => [],
                ],
            ],
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Gửi mail tháng 06-2026',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
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

        $this->actingAs($user)
            ->get(route('mail.index', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('selectedRecipientPreview', null)
                ->where('recipientList.0.customerCode', '21033')
                ->where('recipientList.0.previewIssueCount', 1)
                ->where('recipientList.0.previewIssues.0.section', 'Bảng chế độ tháng')
                ->where(
                    'recipientList.0.previewIssues.0.message',
                    'Dòng "Khuyến mại từ 25/5-30/5" chưa tìm thấy dữ liệu tương ứng trong sheet Tổng hợp đã aggregate.',
                )
            );
    }

    public function test_mail_page_preflight_preview_issues_use_bounded_queries_for_recipient_list(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Mẫu gửi mail query budget',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.4-C',
                'sections' => [
                    ['type' => 'subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'tong-hop-table', 'rows' => [
                        [
                            'content' => 'Chương trình chưa có trong dữ liệu aggregate',
                            'rowType' => 'child',
                            'columnKey' => 'Chương trình chưa có trong dữ liệu aggregate',
                            'hideWhenValueZero' => true,
                            'isBold' => false,
                        ],
                    ]],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas query budget',
            'is_active' => false,
            'legacy_mail_template_id' => $mailTemplate->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-QUERY-BUDGET',
            'name' => 'Data query budget',
            'original_file_name' => 'query-budget.xlsx',
            'stored_path' => 'imports/tmp/query-budget.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Tổng hợp' => [
                        'fixedHeaders' => ['Tổng cộng', 'Bằng chữ'],
                        'dynamicHeaders' => [],
                    ],
                ],
            ],
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Gửi mail query budget',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        foreach (range(1, 8) as $index) {
            $customerCode = sprintf('QB%03d', $index);
            $record = ImportBatchAggregatedRecord::query()->create([
                'import_batch_id' => $batch->id,
                'customer_code' => $customerCode,
                'customer_type' => 'Khách thường',
                'source_sheets' => ['Tổng hợp'],
                'aggregated_payload' => [
                    'customerCode' => $customerCode,
                    'customerFullName' => $customerCode.' - Đại lý query budget',
                    'customerType' => 'Khách thường',
                    'tongHop' => [
                        'month' => '06-2026',
                        'customerCode' => $customerCode,
                        'customerFullName' => $customerCode.' - Đại lý query budget',
                        'grandTotal' => '1000000',
                        'totalInWords' => 'Một triệu đồng chẵn.',
                        'dynamicItems' => [],
                    ],
                ],
            ]);

            MailCampaignRecipient::query()->create([
                'mail_campaign_id' => $campaign->id,
                'import_batch_aggregated_record_id' => $record->id,
                'customer_code' => $customerCode,
                'customer_full_name' => $customerCode.' - Đại lý query budget',
                'customer_type' => 'Khách thường',
                'recipient_email' => 'customer'.$index.'@example.com',
                'delivery_status' => 'pending',
                'attempts_count' => 0,
            ]);
        }

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->actingAs($user)
            ->get(route('mail.index', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('selectedRecipientPreview', null)
                ->has('recipientList', 8)
                ->where('recipientList.0.previewIssueCount', 1)
                ->where('recipientList.0.previewIssues.0.section', 'Bảng chế độ tháng')
            );

        $this->assertLessThan(180, $queryCount);
    }

    public function test_mail_page_explains_multiple_recipient_rows_for_same_customer(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-08',
            'name' => 'Batch tháng 8/2026',
            'original_file_name' => 'thang-8.xlsx',
            'stored_path' => 'imports/tmp/thang-8.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90700',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp', 'Khoán NPP'],
            'aggregated_payload' => [
                'customerCode' => '90700',
                'customerFullName' => '90700 - Công ty nhiều email',
                'customerType' => 'Khách thường',
                'email' => 'one@example.com',
                'emails' => ['one@example.com', 'two@example.com', 'three@example.com'],
                'tongHop' => [
                    'email' => 'one@example.com; two@example.com; three@example.com',
                    'emails' => ['one@example.com', 'two@example.com', 'three@example.com'],
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas tháng 8-2026',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 8',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            MailCampaignRecipient::query()->create([
                'mail_campaign_id' => $campaign->id,
                'import_batch_aggregated_record_id' => $record->id,
                'customer_code' => '90700',
                'customer_full_name' => '90700 - Công ty nhiều email',
                'customer_type' => 'Khách thường',
                'recipient_email' => $email,
                'delivery_status' => 'pending',
                'attempts_count' => 0,
            ]);
        }

        $this->actingAs($user)
            ->get(route('mail.index', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('recipientList.0.customerCode', '90700')
                ->where('recipientList.0.aggregatedEmails', ['one@example.com', 'two@example.com', 'three@example.com'])
                ->where('recipientList.0.relatedRecipientCount', 3)
                ->where('recipientList.0.recipientGroupLabel', '3 địa chỉ nhận cho cùng khách hàng')
                ->where('recipientList.1.relatedRecipientCount', 3)
                ->where('recipientList.2.relatedRecipientCount', 3)
            );
    }
}
