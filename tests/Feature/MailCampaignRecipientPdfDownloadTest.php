<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\MailTemplateCanvasPart;
use App\Models\TemplatePartVersion;
use App\Models\User;
use App\Services\Templates\EnsureTemplatePartCatalogPersistedService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailCampaignRecipientPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_download_pdf_for_sent_recipient_from_snapshots(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeSentSnapshotRecipientFixture($user);

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.recipients.pdf.download', [$campaign, $recipient]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader(
            'content-disposition',
            sprintf('attachment; filename=%s', sprintf('mail-campaign-%d-recipient-%s.pdf', $campaign->id, '90300-Cong-ty-A')),
        );
    }

    public function test_user_can_download_pdf_for_pending_recipient_when_preview_can_be_rendered(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makePreviewableRecipientFixture($user);

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.recipients.pdf.download', [$campaign, $recipient]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader(
            'content-disposition',
            sprintf('attachment; filename=%s', sprintf('mail-campaign-%d-recipient-%s.pdf', $campaign->id, '90400-Cong-ty-B')),
        );
    }

    public function test_download_redirects_back_with_error_when_recipient_pdf_cannot_be_rendered(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeInvalidRecipientFixture($user);

        $this->actingAs($user)
            ->from(route('mail.index', ['campaign' => $campaign->id]))
            ->get(route('mail.campaigns.recipients.pdf.download', [$campaign, $recipient]))
            ->assertRedirect(route('mail.index', ['campaign' => $campaign->id]))
            ->assertSessionHas('error', 'Template canvas hiện chưa có liên kết legacy mail template để dựng preview đầy đủ.');
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignRecipient}
     */
    private function makeSentSnapshotRecipientFixture(User $user): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-RECIPIENT-PDF-SENT',
            'name' => 'Batch recipient sent PDF',
            'original_file_name' => 'recipient-sent.xlsx',
            'stored_path' => 'imports/tmp/recipient-sent.xlsx',
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
            'name' => 'Canvas recipient sent PDF',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch recipient sent PDF',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
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
            'delivery_status' => 'sent',
            'attempts_count' => 1,
            'sent_subject_snapshot' => 'Chế độ tháng 03.2026 - 90300 - Công ty A',
            'sent_html_snapshot' => '<!DOCTYPE html><html lang="vi"><body><div style="font-size:15px; color:#1e293b;">Kính gửi 90300 - Công ty A</div></body></html>',
            'sent_signature_snapshot' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => null,
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
            'snapshot_version' => 1,
            'sent_at' => now(),
        ]);

        return [$campaign, $recipient];
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignRecipient}
     */
    private function makePreviewableRecipientFixture(User $user): array
    {
        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template recipient PDF',
            'subject_template' => 'Chế độ tháng {{tháng}} - {{mã & tên khách hàng}}',
            'structure_json' => [
                'version' => '2.4-C',
                'sections' => [
                    ['type' => 'subject', 'content' => 'Chế độ tháng {{tháng}} - {{mã & tên khách hàng}}'],
                    ['type' => 'greeting', 'content' => "Kính gửi {{mã & tên khách hàng}},\nĐịa chỉ: {{địa chỉ}}"],
                    ['type' => 'tong-hop-table', 'rows' => [
                        ['content' => 'Chiết khấu theo hóa đơn', 'rowType' => 'parent', 'hideWhenValueZero' => false, 'isBold' => true],
                        ['content' => 'Tổng sản lượng (gồm cám thủy sản)', 'rowType' => 'child', 'columnKey' => 'Tổng sản lượng (gồm cám thủy sản)', 'hideWhenValueZero' => false, 'isBold' => false],
                        ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => false, 'isBold' => true],
                    ]],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas recipient PDF',
            'is_active' => true,
            'legacy_mail_template_id' => $mailTemplate->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-RECIPIENT-PDF-PREVIEW',
            'name' => 'Batch recipient preview PDF',
            'original_file_name' => 'recipient-preview.xlsx',
            'stored_path' => 'imports/tmp/recipient-preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Tổng hợp' => [
                        'fixedHeaders' => [
                            'Tổng sản lượng (gồm cám thủy sản)',
                            'Tổng cộng',
                        ],
                        'dynamicHeaders' => [],
                    ],
                ],
            ],
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
                    'month' => '03.2026',
                    'customerCode' => '90400',
                    'customerFullName' => '90400 - Công ty B',
                    'address' => 'Địa chỉ B',
                    'email' => 'b@example.com',
                    'totalQuantity' => '123456',
                    'grandTotal' => '789000',
                    'totalInWords' => 'Bảy trăm tám mươi chín nghìn đồng',
                    'dynamicItems' => [],
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch recipient preview PDF',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $recipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90400',
            'customer_full_name' => '90400 - Công ty B',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'b@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        $this->attachRepresentativeSignatureToCampaignCanvas($campaign);

        return [$campaign, $recipient];
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignRecipient}
     */
    private function makeInvalidRecipientFixture(User $user): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-RECIPIENT-PDF-INVALID',
            'name' => 'Batch recipient invalid PDF',
            'original_file_name' => 'recipient-invalid.xlsx',
            'stored_path' => 'imports/tmp/recipient-invalid.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90500',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90500',
                'customerFullName' => '90500 - Công ty lỗi',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'email' => 'invalid@example.com',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas recipient invalid PDF',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch recipient invalid PDF',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $recipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90500',
            'customer_full_name' => '90500 - Công ty lỗi',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'invalid@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        return [$campaign, $recipient];
    }

    private function attachRepresentativeSignatureToCampaignCanvas(MailCampaign $campaign): void
    {
        $parts = app(EnsureTemplatePartCatalogPersistedService::class)->ensure();
        $signaturePart = $parts['representative-signature'];
        $canvas = $campaign->templateCanvas()->firstOrFail();
        $nextVersionNo = (int) TemplatePartVersion::query()
            ->where('template_part_id', $signaturePart->id)
            ->max('version_no') + 1;

        $version = TemplatePartVersion::query()->create([
            'template_part_id' => $signaturePart->id,
            'version_no' => $nextVersionNo,
            'version_label' => 'Signature for recipient PDF test',
            'structure_json' => [
                'type' => 'representative-signature',
                'label' => 'Chữ ký đại diện',
                'description' => 'Chữ ký người đại diện theo 2 block Khách thường và Key Account.',
                'kind' => 'composite',
                'blocks' => [
                    'normalCustomer' => [
                        'title' => 'Đại diện công ty',
                        'signatureImageDataUrl' => null,
                        'representativeRole' => 'Trưởng ban tài chính',
                        'representativeName' => 'Nguyễn Văn B',
                    ],
                    'keyAccountCustomer' => [
                        'title' => 'Đại diện công ty',
                        'signatureImageDataUrl' => null,
                        'representativeRole' => 'Giám đốc Key Account',
                        'representativeName' => 'Nguyễn Văn K',
                    ],
                ],
            ],
            'legacy_mail_template_id' => null,
            'created_by' => $campaign->created_by,
            'updated_by' => $campaign->updated_by,
        ]);

        MailTemplateCanvasPart::query()->create([
            'mail_template_canvas_id' => $canvas->id,
            'template_part_id' => $signaturePart->id,
            'template_part_version_id' => $version->id,
            'sort_order' => 2,
        ]);
    }
}
