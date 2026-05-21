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
use Tests\TestCase;

class MailCampaignRecipientPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_mail_view_permission_can_download_recipient_pdf_from_sent_snapshot(): void
    {
        $user = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeRecipientFixture($user, withValidSnapshots: true);

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.recipients.download-pdf', [
                'mailCampaign' => $campaign->id,
                'mailCampaignRecipient' => $recipient->id,
            ]));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }

    public function test_recipient_pdf_download_is_rejected_when_snapshot_is_missing(): void
    {
        $user = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeRecipientFixture($user, withValidSnapshots: false);

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.recipients.download-pdf', [
                'mailCampaign' => $campaign->id,
                'mailCampaignRecipient' => $recipient->id,
            ]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));
        $response->assertSessionHasErrors(['download']);
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignRecipient}
     */
    private function makeRecipientFixture(User $user, bool $withValidSnapshots): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-10',
            'name' => 'Batch tháng 10/2026',
            'original_file_name' => 'thang-10.xlsx',
            'stored_path' => 'imports/tmp/thang-10.xlsx',
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
                'tongHop' => ['email' => 'a@example.com'],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu PDF recipient',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch recipient PDF',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Recipient download',
            'status' => 'completed',
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
            'sent_at' => now(),
            'sent_subject_snapshot' => $withValidSnapshots ? 'Thư chiết khấu tháng 10' : null,
            'sent_html_snapshot' => $withValidSnapshots
                ? '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>Mail</title></head><body><div style="padding:16px"><h1>Mail đã gửi</h1><p>Nội dung cho 90300</p></div></body></html>'
                : null,
            'sent_signature_snapshot' => $withValidSnapshots ? [
                'partType' => 'representative-signature',
                'customerType' => 'Khách thường',
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFsLXNpZw==',
                'representativeRole' => 'Giám đốc kinh doanh',
                'representativeName' => 'Nguyễn Văn A',
            ] : null,
            'snapshot_version' => $withValidSnapshots ? 1 : null,
        ]);

        return [$campaign, $recipient];
    }
}
