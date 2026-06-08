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

class MailCampaignRecipientExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_failed_recipients_export_keeps_one_csv_row_per_failed_email(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign] = $this->makeMultiEmailCampaignFixture($user);

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.recipients.export-failed', $campaign));

        $response->assertOk();

        $rows = $this->parseCsvRows($response->streamedContent());

        $this->assertSame(
            ['STT', 'Mã số', 'Tên khách hàng', 'Email', 'Nguồn dữ liệu', 'Số lần thử', 'Lỗi gần nhất', 'Thời gian lỗi'],
            $rows[0],
        );
        $this->assertCount(3, $rows);
        $this->assertSame('90800', $rows[1][1]);
        $this->assertSame('first@example.com', $rows[1][3]);
        $this->assertSame('90800', $rows[2][1]);
        $this->assertSame('second@example.com', $rows[2][3]);
    }

    public function test_aggregated_data_export_keeps_one_csv_row_per_recipient_email(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign] = $this->makeMultiEmailCampaignFixture($user);

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.recipients.export-aggregated', $campaign));

        $response->assertOk();

        $rows = $this->parseCsvRows($response->streamedContent());

        $this->assertSame('Mã số khách hàng', $rows[1][0]);
        $this->assertSame('Email', $rows[1][1]);
        $this->assertCount(4, $rows);
        $this->assertSame('90800', $rows[2][0]);
        $this->assertSame('first@example.com', $rows[2][1]);
        $this->assertSame('90800', $rows[3][0]);
        $this->assertSame('second@example.com', $rows[3][1]);
    }

    /**
     * @return array{0: MailCampaign, 1: ImportBatchAggregatedRecord, 2: MailCampaignRecipient, 3: MailCampaignRecipient}
     */
    private function makeMultiEmailCampaignFixture(User $user): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-09',
            'name' => 'Batch tháng 9/2026',
            'original_file_name' => 'thang-9.xlsx',
            'stored_path' => 'imports/tmp/thang-9.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90800',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90800',
                'customerFullName' => '90800 - Công ty export nhiều email',
                'customerType' => 'Khách thường',
                'email' => 'first@example.com',
                'emails' => ['first@example.com', 'second@example.com'],
                'tongHop' => [
                    'email' => 'first@example.com; second@example.com',
                    'emails' => ['first@example.com', 'second@example.com'],
                    'totalQuantity' => '100',
                    'revenue' => '200',
                    'invoiceDiscount' => '10',
                    'otherDiscount' => '5',
                    'grandTotal' => '15',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas export nhiều email',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch export nhiều email',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'completed_with_failures',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $firstRecipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90800',
            'customer_full_name' => '90800 - Công ty export nhiều email',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'first@example.com',
            'delivery_status' => 'failed',
            'attempts_count' => 2,
            'latest_error_message' => 'SMTP timeout',
            'failed_at' => now(),
        ]);

        $secondRecipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90800',
            'customer_full_name' => '90800 - Công ty export nhiều email',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'second@example.com',
            'delivery_status' => 'failed',
            'attempts_count' => 1,
            'latest_error_message' => 'Mailbox full',
            'failed_at' => now(),
        ]);

        return [$campaign, $record, $firstRecipient, $secondRecipient];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsvRows(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = array_values(array_filter(
            preg_split("/\r\n|\n|\r/", trim($content)) ?: [],
            static fn (string $line): bool => $line !== '',
        ));

        return array_map(static fn (string $line): array => str_getcsv($line), $lines);
    }
}
