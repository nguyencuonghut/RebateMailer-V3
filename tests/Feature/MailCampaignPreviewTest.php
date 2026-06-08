<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MailCampaignPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_mail_page_can_render_full_preview_for_selected_recipient(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template tháng 3',
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
                        ['content' => 'Bằng chữ:', 'rowType' => 'text', 'columnKey' => 'Bằng chữ', 'hideWhenValueZero' => false, 'isBold' => false],
                    ]],
                    ['type' => 'khoan-npp-table', 'rows' => [
                        ['rowType' => 'program-loop'],
                        ['content' => 'Cộng', 'rowType' => 'total', 'isBold' => true],
                    ]],
                    ['type' => 'cam-ca-table', 'rows' => [
                        ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'hideWhenValueZero' => false, 'isBold' => false],
                        ['content' => 'Cộng', 'rowType' => 'total', 'isBold' => true],
                    ]],
                    ['type' => 'key-account-table', 'rows' => [
                        ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'quantity', 'hideWhenValueZero' => false, 'isBold' => false],
                        ['content' => 'Cộng', 'rowType' => 'total', 'isBold' => true],
                        ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'isBold' => false],
                    ]],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas tháng 3',
            'is_active' => false,
            'legacy_mail_template_id' => $mailTemplate->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-03',
            'name' => 'Batch tháng 3/2026',
            'original_file_name' => 'thang-3.xlsx',
            'stored_path' => 'imports/tmp/thang-3.xlsx',
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

        $aggregatedRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'month' => '03.2026',
                    'customerCode' => '90300',
                    'customerFullName' => '90300 - Công ty A',
                    'address' => 'Địa chỉ A',
                    'email' => 'a@example.com',
                    'totalQuantity' => '123456',
                    'grandTotal' => '789000',
                    'totalInWords' => 'Bảy trăm tám mươi chín nghìn đồng',
                    'dynamicItems' => [],
                ],
                'khoanNpp' => null,
                'khoanNpp' => [
                    'month' => '03.2026',
                    'customerCode' => '90300',
                    'customerFullName' => '90300 - Công ty A',
                    'programItems' => [
                        ['content' => 'Chương trình khoán A', 'quantity' => '120', 'supportRate' => '500', 'amount' => '60000'],
                    ],
                    'grandTotal' => '60000',
                    'totalInWords' => 'Sáu mươi nghìn đồng',
                ],
                'camCa' => [
                    'month' => '03.2026',
                    'customerCode' => '90300',
                    'customerFullName' => '90300 - Công ty A',
                    'totalQuantity' => '450',
                    'grandTotal' => '33000',
                    'totalInWords' => 'Ba mươi ba nghìn đồng',
                    'programItems' => [],
                    'discreteItems' => [],
                ],
                'keyAccount' => [
                    'month' => '03.2026',
                    'customerCode' => '90300',
                    'customerFullName' => '90300 - Công ty A',
                    'totalQuantity' => '987',
                    'grandTotal' => '123000',
                    'totalInWords' => 'Một trăm hai mươi ba nghìn đồng',
                    'programItems' => [],
                    'discreteItems' => [],
                ],
            ],
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 3',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Gửi thử',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $recipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $aggregatedRecord->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'a@example.com',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('mail.index', [
                'campaign' => $campaign->id,
                'recipient' => $recipient->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('selectedCampaign.id', $campaign->id)
                ->where('selectedRecipientId', $recipient->id)
                ->where('selectedRecipientPreview.recipient.customerCode', '90300')
                ->where('selectedRecipientPreview.subject.renderedText', 'Chế độ tháng 03.2026 - 90300 - Công ty A')
                ->where('selectedRecipientPreview.greeting.renderedText', "Kính gửi 90300 - Công ty A,\nĐịa chỉ: Địa chỉ A")
                ->where('selectedRecipientPreview.tables.0.type', 'tong-hop-table')
                ->where('selectedRecipientPreview.tables.0.rows.0.numbering', 'I')
                ->where('selectedRecipientPreview.tables.0.rows.0.fontWeight', 'bold')
                ->where('selectedRecipientPreview.tables.0.rows.1.value', '123456')
                ->where('selectedRecipientPreview.tables.0.rows.3.value', 'Bảy trăm tám mươi chín nghìn đồng')
                ->where('selectedRecipientPreview.tables.1.type', 'khoan-npp-table')
                ->where('selectedRecipientPreview.tables.1.rows.0.content', 'Chương trình khoán A')
                ->where('selectedRecipientPreview.tables.2.type', 'cam-ca-table')
                ->where('selectedRecipientPreview.tables.2.rows.0.value', '450')
                ->where('selectedRecipientPreview.tables.3.type', 'key-account-table')
                ->where('selectedRecipientPreview.tables.3.rows.0.quantity', '987')
                ->where('selectedRecipientPreview.html', fn (string $html): bool => str_contains($html, '<!DOCTYPE html>')
                    && str_contains($html, 'Kính gửi 90300 - Công ty A')
                    && str_contains($html, 'Chế độ tháng 03.2026')
                    && str_contains($html, 'Bảy trăm tám mươi chín nghìn đồng')
                    && str_contains($html, 'Chương trình khoán A')
                    && str_contains($html, 'Chiết khấu cám cá tháng 03.2026')
                    && str_contains($html, 'Chiết khấu tháng 03.2026')
                    && ! str_contains($html, 'Nguồn dữ liệu:')
                    && ! str_contains($html, 'Email này được render từ dữ liệu aggregate thật của khách hàng đã chọn trong chiến dịch.')
                    && ! str_contains($html, 'Rebate Mailer')
                    && ! str_contains($html, '<h1')
                    && str_contains($html, 'Bằng chữ:')
                    && str_contains($html, 'colspan="2"')
                    && str_contains($html, 'colspan="3"')
                    && preg_match('/Bằng chữ:\s+Bảy trăm tám mươi chín nghìn đồng/u', $html) === 1
                    && str_contains($html, 'font-weight:700; border-top:1px solid #e2e8f0;">I</td>'))
            );
    }

    public function test_mail_page_only_renders_key_account_table_for_key_account_recipient(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template tháng 3',
            'subject_template' => 'Chiết khấu tháng {{tháng}} - {{mã & tên khách hàng}}',
            'structure_json' => [
                'version' => '2.4-C',
                'sections' => [
                    ['type' => 'subject', 'content' => 'Chiết khấu tháng {{tháng}} - {{mã & tên khách hàng}}'],
                    ['type' => 'greeting', 'content' => "Kính gửi {{mã & tên khách hàng}},\nĐịa chỉ: {{địa chỉ}}"],
                    ['type' => 'tong-hop-table', 'rows' => [
                        ['content' => 'Tổng sản lượng (gồm cám thủy sản)', 'rowType' => 'data', 'columnKey' => 'Tổng sản lượng (gồm cám thủy sản)', 'hideWhenValueZero' => false, 'isBold' => false],
                    ]],
                    ['type' => 'khoan-npp-table', 'rows' => [
                        ['rowType' => 'program-loop'],
                    ]],
                    ['type' => 'cam-ca-table', 'rows' => [
                        ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'hideWhenValueZero' => false, 'isBold' => false],
                    ]],
                    ['type' => 'key-account-table', 'rows' => [
                        ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'quantity', 'hideWhenValueZero' => false, 'isBold' => false],
                    ]],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas tháng 3',
            'is_active' => false,
            'legacy_mail_template_id' => $mailTemplate->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-03',
            'name' => 'Batch tháng 3/2026',
            'original_file_name' => 'thang-3.xlsx',
            'stored_path' => 'imports/tmp/thang-3.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Tổng hợp' => ['fixedHeaders' => ['Tổng sản lượng (gồm cám thủy sản)'], 'dynamicHeaders' => []],
                    'Cám cá' => ['fixedHeaders' => ['Tổng sản lượng'], 'discreteHeaders' => []],
                    'Key Account' => ['fixedHeaders' => ['Tổng sản lượng'], 'discreteHeaders' => []],
                ],
            ],
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp', 'Cám cá'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'month' => '03.2026',
                    'customerCode' => '90300',
                    'customerFullName' => '90300 - Công ty A',
                    'address' => 'Địa chỉ A',
                    'email' => 'a@example.com',
                    'totalQuantity' => '123456',
                    'grandTotal' => '789000',
                    'totalInWords' => 'Bảy trăm tám mươi chín nghìn đồng',
                    'dynamicItems' => [],
                ],
                'camCa' => [
                    'month' => '03.2026',
                    'customerCode' => '90300',
                    'customerFullName' => '90300 - Công ty A',
                    'totalQuantity' => '450',
                    'grandTotal' => '33000',
                    'totalInWords' => 'Ba mươi ba nghìn đồng',
                    'programItems' => [],
                    'discreteItems' => [],
                ],
                'khoanNpp' => null,
                'keyAccount' => null,
            ],
        ]);

        $keyAccountRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '11008',
            'customer_type' => 'Key Account',
            'source_sheets' => ['Key Account'],
            'aggregated_payload' => [
                'customerCode' => '11008',
                'customerFullName' => '11008 - Công ty cổ phần xuất nhập khẩu Cao Bằng',
                'customerType' => 'Key Account',
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => [
                    'month' => '03.2026',
                    'customerCode' => '11008',
                    'customerFullName' => '11008 - Công ty cổ phần xuất nhập khẩu Cao Bằng',
                    'address' => 'Địa chỉ KA',
                    'email' => '11008@honghafeed.com.vn',
                    'totalQuantity' => '147300',
                    'revenue' => '2188261300',
                    'invoiceDiscount' => '147061500',
                    'grandTotal' => '147061500',
                    'totalInWords' => 'Một trăm bốn mươi bảy triệu đồng',
                    'programItems' => [],
                    'discreteItems' => [],
                ],
            ],
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 3',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Gửi thử',
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $recipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $keyAccountRecord->id,
            'customer_code' => '11008',
            'customer_full_name' => '11008 - Công ty cổ phần xuất nhập khẩu Cao Bằng',
            'customer_type' => 'Key Account',
            'recipient_email' => '11008@honghafeed.com.vn',
            'delivery_status' => 'pending',
            'attempts_count' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('mail.index', [
                'campaign' => $campaign->id,
                'recipient' => $recipient->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('selectedRecipientPreview.recipient.customerCode', '11008')
                ->where('selectedRecipientPreview.tables', fn ($tables): bool => count($tables) === 1
                    && (($tables[0]['type'] ?? null) === 'key-account-table'))
            );
    }
}
