<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Services\Templates\SyncLegacyMailTemplateToCompositionService;
use App\Services\Templates\BuildTemplateTongHopTablePreviewService;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplatesPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_templates_page_renders_through_inertia_for_user_with_manage_permission(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Templates/Index')
                ->where('title', 'Thiết kế mẫu email')
                ->where('currentSlice.code', '2.4-B')
                ->where('canManageTemplates', true)
                ->has('writeCapabilities', 4)
                ->where('writeCapabilities.0', 'Tạo template mới')
                ->has('templateParts', 6)
                ->has('partVersionGroups', 6)
                ->has('templateVariables', 4)
                ->has('tongHopBindingOptions')
                ->where('templateVariables.0.token', '{{tháng}}')
                ->where('templateVariables.1.token', '{{mã & tên khách hàng}}')
                ->where('templateVariables.2.token', '{{địa chỉ}}')
                ->where('templateVariables.3.token', '{{thức ăn chăn nuôi}}')
                ->where('builderTemplate', null)
                ->where('canvasComposition', null)
                ->where('subjectPreview', null)
                ->where('greetingPreview', null)
                ->where('tongHopTablePreview', null)
                ->where('khoanNppTablePreview', null)
                ->where('templateParts.0.code', 'subject')
                ->where('templateParts.0.type', 'subject')
                ->where('templateParts.0.maxActiveVersions', 1)
                ->where('templateParts.1.code', 'greeting')
                ->where('templateParts.1.maxActiveVersions', 2)
                ->where('templateParts.2.code', 'tong-hop')
                ->where('templateParts.2.type', 'tong-hop-table')
                ->where('templateParts.2.sourceSheet', 'Tổng hợp')
                ->where('templateParts.3.code', 'khoan-npp')
                ->where('templateParts.3.type', 'khoan-npp-table')
                ->where('templateParts.4.code', 'cam-ca')
                ->where('templateParts.4.type', 'cam-ca-table')
                ->where('templateParts.5.code', 'key-account')
                ->where('templateParts.5.type', 'key-account-table')
                ->where('templateList', [])
                ->where('activeTemplateId', null)
                ->where('nextSlice.code', '2.4-C')
            );
    }

    public function test_tong_hop_binding_options_include_all_real_parsed_fixed_fields_and_dynamic_items(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TONG-HOP-BINDING',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Tổng hợp' => [
                        'fixedHeaders' => [
                            'STT',
                            'Tháng',
                            'Mã số',
                            'Mã & tên khách hàng',
                            'Tên khách hàng',
                            'Email',
                            'Địa chỉ',
                            'Thức ăn chăn nuôi',
                            'Tổng sản lượng (gồm cám thủy sản)',
                            'Doanh thu (gồm cám thủy sản)',
                            'Tiền chiết khấu theo Hóa đơn',
                            'Thưởng cam kết tháng',
                            'Chiết khấu cám cá',
                            'Chiết khấu khác ( Không thể hiện trên hóa đơn)',
                            'Tổng cộng',
                            'Bằng chữ',
                        ],
                        'dynamicHeaders' => [
                            'Khuyến mãi sản phẩm 4420, 6430, 2430S: 200đ/kg',
                            'Chương trình chưa có giá trị ở sample hiện tại',
                        ],
                    ],
                ],
            ],
        ]);

        $firstRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'stt' => '1',
                    'month' => '02.2026',
                    'customerCode' => '90300',
                    'customerFullName' => '90300 - Công ty A',
                    'customerName' => 'Công ty A',
                    'email' => 'a@example.com',
                    'address' => 'Địa chỉ A',
                    'feedCategory' => 'Feed A',
                    'totalQuantity' => '31960',
                    'revenue' => '376429065',
                    'invoiceDiscount' => '8942750',
                    'commitmentBonus' => '1256250',
                    'fishFeedDiscount' => '1822000',
                    'otherDiscount' => '2047500',
                    'grandTotal' => '10990250',
                    'totalInWords' => 'Mười triệu chín trăm chín mươi nghìn hai trăm năm mươi đồng chẵn.',
                    'dynamicItems' => [
                        ['label' => 'Khuyến mãi sản phẩm 4420, 6430, 2430S: 200đ/kg', 'value' => '32000'],
                    ],
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $options = app(BuildTemplateTongHopTablePreviewService::class)->buildBindingOptions();
        $optionMap = collect($options)->mapWithKeys(fn (array $item): array => [$item['key'] => $item['valuePreview']])->all();

        $this->assertSame('1', $optionMap['STT'] ?? null);
        $this->assertSame('02.2026', $optionMap['Tháng'] ?? null);
        $this->assertSame('90300', $optionMap['Mã số'] ?? null);
        $this->assertSame('90300 - Công ty A', $optionMap['Mã & tên khách hàng'] ?? null);
        $this->assertSame('Công ty A', $optionMap['Tên khách hàng'] ?? null);
        $this->assertSame('a@example.com', $optionMap['Email'] ?? null);
        $this->assertSame('Địa chỉ A', $optionMap['Địa chỉ'] ?? null);
        $this->assertSame('Feed A', $optionMap['Thức ăn chăn nuôi'] ?? null);
        $this->assertSame('10990250', $optionMap['Tổng cộng'] ?? null);
        $this->assertSame('10990250', $optionMap['Cộng'] ?? null);
        $this->assertSame('32000', $optionMap['Khuyến mãi sản phẩm 4420, 6430, 2430S: 200đ/kg'] ?? null);
        $this->assertArrayHasKey('Chương trình chưa có giá trị ở sample hiện tại', $optionMap);
        $this->assertSame('', $optionMap['Chương trình chưa có giá trị ở sample hiện tại']);
    }

    public function test_templates_page_renders_in_read_only_mode_for_actor_without_manage_permission(): void
    {
        $admin = User::query()->where('email', 'admin@rebatemailer.test')->firstOrFail();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('templates.view');

        $this->actingAs($viewer)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Templates/Index')
                ->where('canManageTemplates', false)
                ->where('readOnlyNotice', 'Tài khoản hiện tại chỉ được xem cấu trúc template email. Các thao tác tạo, chỉnh sửa và kích hoạt template chỉ mở cho người dùng có quyền quản lý template.')
            );
    }

    public function test_templates_page_can_render_template_list_and_active_state_from_database(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $inactiveTemplate = MailTemplate::query()->create([
            'name' => 'Template mặc định tháng thường',
            'subject_template' => 'Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}',
            'structure_json' => [
                'sections' => [
                    ['type' => 'subject'],
                    ['type' => 'greeting'],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $activeTemplate = MailTemplate::query()->create([
            'name' => 'Template đang hoạt động',
            'subject_template' => 'Chế độ tháng {{tháng}} - Key Account {{mã & tên khách hàng}}',
            'structure_json' => [
                'version' => '2.3-A',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}} - Key Account {{mã & tên khách hàng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => "Kính gửi {{mã & tên khách hàng}},\nĐịa chỉ: {{địa chỉ}}\nNhóm thức ăn: {{thức ăn chăn nuôi}}"],
                    ['type' => 'key-account-table', 'label' => 'Table Chiết khấu Key Account', 'sourceSheet' => 'Key Account', 'rows' => [['content' => 'Row 1', 'indentLevel' => 1]]],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-SUBJECT-PREVIEW',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $firstRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '11008',
            'customer_type' => 'Key Account',
            'source_sheets' => ['Key Account'],
            'aggregated_payload' => [
                'customerCode' => '11008',
                'customerFullName' => '11008 - Siêu thị Key Account A',
                'customerType' => 'Key Account',
                'sourceSheets' => ['Key Account'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => [
                    'month' => '02.2026',
                    'address' => 'Địa chỉ mẫu',
                    'feedCategory' => 'Thức ăn mẫu',
                ],
            ],
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncAll();
        $activeCanvas = MailTemplateCanvas::query()->where('legacy_mail_template_id', $activeTemplate->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Templates/Index')
                ->where('activeTemplateId', $activeTemplate->id)
                ->where('builderTemplate.id', $activeTemplate->id)
                ->where('canvasComposition.canvasId', $activeCanvas->id)
                ->where('canvasComposition.storageModel', 'composition-db')
                ->has('canvasComposition.partSelections', 6)
                ->where('canvasComposition.partSelections.0.partType', 'subject')
                ->where('canvasComposition.partSelections.0.maxActiveVersions', 1)
                ->where('canvasComposition.partSelections.1.partType', 'greeting')
                ->where('canvasComposition.partSelections.1.maxActiveVersions', 2)
                ->where('canvasComposition.partSelections.2.partType', 'tong-hop-table')
                ->where('canvasComposition.partSelections.2.contentSummary.rowCount', 0)
                ->where('builderTemplate.structure.sections.0.type', 'subject')
                ->where('builderTemplate.structure.sections.2.sourceSheet', 'Key Account')
                ->where('builderTemplate.structure.sections.2.rows.0.indentLevel', 1)
                ->where('selectedPreviewRecordId', $firstRecord->id)
                ->where('subjectPreview.sample.batchCode', 'IMP-SUBJECT-PREVIEW')
                ->where('subjectPreview.sample.recordId', $firstRecord->id)
                ->where('subjectPreview.sample.customerCode', '11008')
                ->where('subjectPreview.sample.month', '02.2026')
                ->where('subjectPreview.renderedText', 'Chế độ tháng 02.2026 - Key Account 11008 - Siêu thị Key Account A')
                ->where('subjectPreview.errors', [])
                ->has('previewCustomerOptions', 1)
                ->where('previewCustomerOptions.0.recordId', $firstRecord->id)
                ->where('greetingPreview.sample.batchCode', 'IMP-SUBJECT-PREVIEW')
                ->where('greetingPreview.sample.recordId', $firstRecord->id)
                ->where('greetingPreview.sample.address', 'Địa chỉ mẫu')
                ->where('greetingPreview.sample.feedCategory', 'Thức ăn mẫu')
                ->where('greetingPreview.renderedText', "Kính gửi 11008 - Siêu thị Key Account A,\nĐịa chỉ: Địa chỉ mẫu\nNhóm thức ăn: Thức ăn mẫu")
                ->where('greetingPreview.errors', [])
                ->has('templateList', 2)
                ->where('templateList.0.id', $activeTemplate->id)
                ->where('templateList.0.name', 'Template đang hoạt động')
                ->where('templateList.0.isActive', true)
                ->where('templateList.0.statusLabel', 'Đang hoạt động')
                ->where('templateList.0.sectionCount', 3)
                ->where('templateList.0.createdBy', $user->name)
                ->where('templateList.1.id', $inactiveTemplate->id)
                ->where('templateList.1.isActive', false)
                ->where('templateList.1.statusLabel', 'Ngừng hoạt động')
                ->where('templateList.1.sectionCount', 1)
            );
    }

    public function test_templates_page_filters_all_preview_contexts_by_selected_preview_batch(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template preview theo batch',
            'subject_template' => 'Chế độ tháng {{tháng}} - {{mã & tên khách hàng}}',
            'structure_json' => [
                'version' => '2.0-R5',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}} - {{mã & tên khách hàng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    ['type' => 'tong-hop-table', 'label' => 'Table Chế độ tháng', 'sourceSheet' => 'Tổng hợp', 'rows' => [
                        ['content' => 'Tổng sản lượng (gồm cám thủy sản)', 'rowType' => 'data', 'columnKey' => 'Tổng sản lượng (gồm cám thủy sản)'],
                    ]],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $batchMarch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-03',
            'original_file_name' => 'march.xlsx',
            'stored_path' => 'imports/tmp/march.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $marchRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batchMarch->id,
            'customer_code' => '90301',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90301',
                'customerFullName' => '90301 - Công ty tháng 3',
                'tongHop' => [
                    'month' => '03.2026',
                    'totalQuantity' => '100',
                ],
            ],
        ]);

        $batchApril = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-04',
            'original_file_name' => 'april.xlsx',
            'stored_path' => 'imports/tmp/april.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batchApril->id,
            'customer_code' => '90302',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90302',
                'customerFullName' => '90302 - Công ty tháng 4',
                'tongHop' => [
                    'month' => '04.2026',
                    'totalQuantity' => '200',
                ],
            ],
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncAll();

        $this->actingAs($user)
            ->get(route('templates.index', ['preview_batch' => $batchMarch->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Templates/Index')
                ->where('selectedPreviewBatchId', $batchMarch->id)
                ->where('subjectPreview.sample.batchId', $batchMarch->id)
                ->where('subjectPreview.sample.recordId', $marchRecord->id)
                ->where('tongHopTablePreview.sample.batchId', $batchMarch->id)
                ->where('tongHopTablePreview.sample.recordId', $marchRecord->id)
                ->has('previewCustomerOptions', 1)
                ->where('previewCustomerOptions.0.recordId', $marchRecord->id)
            );
    }

    public function test_templates_page_can_render_tong_hop_table_preview_from_real_aggregate_payload(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template preview Tổng hợp',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-D',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'tong-hop-table',
                        'label' => 'Table Chế độ tháng',
                        'kind' => 'table',
                        'sourceSheet' => 'Tổng hợp',
                        'rows' => [
                            ['content' => 'Tổng sản lượng (gồm cám thủy sản)', 'rowType' => 'data', 'columnKey' => 'Tổng sản lượng (gồm cám thủy sản)', 'hideWhenValueZero' => false, 'isBold' => false],
                            ['content' => 'Doanh thu (gồm cám thủy sản)', 'rowType' => 'data', 'columnKey' => 'Doanh thu (gồm cám thủy sản)', 'hideWhenValueZero' => false, 'isBold' => false],
                            ['content' => 'Tiền chiết khấu theo Hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Tiền chiết khấu theo Hóa đơn', 'hideWhenValueZero' => false, 'isBold' => true],
                            ['content' => 'Thưởng cam kết tháng', 'rowType' => 'child', 'columnKey' => 'Thưởng cam kết tháng', 'hideWhenValueZero' => false, 'isBold' => false],
                            ['content' => 'Cộng', 'rowType' => 'total', 'columnKey' => 'Cộng', 'hideWhenValueZero' => false, 'isBold' => true],
                            ['content' => 'Bằng chữ:', 'rowType' => 'text', 'columnKey' => 'Bằng chữ', 'hideWhenValueZero' => false, 'isBold' => false],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TONG-HOP-PREVIEW',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $firstRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '02.2026',
                    'customerCode' => '90300',
                    'customerFullName' => '90300 - Công ty A',
                    'address' => 'Địa chỉ A',
                    'feedCategory' => 'Feed A',
                    'totalQuantity' => '31960',
                    'revenue' => '376429065',
                    'invoiceDiscount' => '8942750',
                    'commitmentBonus' => '1256250',
                    'fishFeedDiscount' => '1822000',
                    'otherDiscount' => '2047500',
                    'grandTotal' => '10990250',
                    'totalInWords' => 'Mười triệu chín trăm chín mươi nghìn hai trăm năm mươi đồng chẵn.',
                    'dynamicItems' => [
                        ['label' => 'Khuyến mãi sản phẩm 4420, 6430, 2430S: 200đ/kg', 'value' => '32000'],
                    ],
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('builderTemplate.id', $template->id)
                ->where('selectedPreviewRecordId', $firstRecord->id)
                ->where('tongHopTablePreview.sample.batchCode', 'IMP-TONG-HOP-PREVIEW')
                ->where('tongHopTablePreview.sample.recordId', $firstRecord->id)
                ->where('tongHopTablePreview.sample.customerCode', '90300')
                ->has('previewCustomerOptions', 1)
                ->where('previewCustomerOptions.0.recordId', $firstRecord->id)
                ->where('previewCustomerOptions.0.customerCode', '90300')
                ->where('tongHopTablePreview.title', 'Chế độ tháng 02.2026')
                ->where('tongHopTablePreview.rows.0.numbering', '')
                ->where('tongHopTablePreview.rows.0.content', 'Tổng sản lượng (gồm cám thủy sản)')
                ->where('tongHopTablePreview.rows.0.value', '31960')
                ->where('tongHopTablePreview.rows.1.numbering', '')
                ->where('tongHopTablePreview.rows.1.content', 'Doanh thu (gồm cám thủy sản)')
                ->where('tongHopTablePreview.rows.1.value', '376429065')
                ->where('tongHopTablePreview.rows.2.numbering', 'I')
                ->where('tongHopTablePreview.rows.2.content', 'Tiền chiết khấu theo Hóa đơn')
                ->where('tongHopTablePreview.rows.2.value', '8942750')
                ->where('tongHopTablePreview.rows.3.numbering', '1')
                ->where('tongHopTablePreview.rows.3.content', 'Thưởng cam kết tháng')
                ->where('tongHopTablePreview.rows.3.value', '1256250')
                ->where('tongHopTablePreview.rows.4.content', 'Cộng')
                ->where('tongHopTablePreview.rows.4.value', '10990250')
                ->where('tongHopTablePreview.rows.5.content', 'Bằng chữ:')
                ->where('tongHopTablePreview.rows.5.value', 'Mười triệu chín trăm chín mươi nghìn hai trăm năm mươi đồng chẵn.')
                ->where('tongHopTablePreview.errors', [])
            );
    }

    public function test_templates_page_can_switch_tong_hop_preview_to_selected_customer_record(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template preview khách Tổng hợp',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-D',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'tong-hop-table',
                        'label' => 'Table Chế độ tháng',
                        'kind' => 'table',
                        'sourceSheet' => 'Tổng hợp',
                        'rows' => [
                            ['content' => 'Tổng sản lượng (gồm cám thủy sản)', 'rowType' => 'data', 'columnKey' => 'Tổng sản lượng (gồm cám thủy sản)', 'hideWhenValueZero' => false, 'isBold' => true],
                            ['content' => 'Cộng', 'rowType' => 'total', 'columnKey' => 'Tổng cộng', 'hideWhenValueZero' => false, 'isBold' => true],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TONG-HOP-SWITCH',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => 'Công ty A',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '02.2026',
                    'customerCode' => '90300',
                    'customerFullName' => 'Công ty A',
                    'address' => 'Địa chỉ A',
                    'feedCategory' => 'Feed A',
                    'totalQuantity' => '100',
                    'grandTotal' => '200',
                    'totalInWords' => 'Hai trăm đồng chẵn.',
                    'dynamicItems' => [],
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $selectedRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '19220',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '19220',
                'customerFullName' => 'Công ty B',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '03.2026',
                    'customerCode' => '19220',
                    'customerFullName' => 'Công ty B',
                    'address' => 'Địa chỉ B',
                    'feedCategory' => 'Feed B',
                    'totalQuantity' => '555',
                    'grandTotal' => '777',
                    'totalInWords' => 'Bảy trăm bảy mươi bảy đồng chẵn.',
                    'dynamicItems' => [],
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index', ['preview_record' => $selectedRecord->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedPreviewRecordId', $selectedRecord->id)
                ->where('tongHopTablePreview.sample.recordId', $selectedRecord->id)
                ->where('tongHopTablePreview.sample.customerCode', '19220')
                ->where('tongHopTablePreview.sample.customerFullName', 'Công ty B')
                ->where('tongHopTablePreview.title', 'Chế độ tháng 03.2026')
                ->where('tongHopTablePreview.rows.0.value', '555')
                ->where('tongHopTablePreview.rows.1.value', '777')
                ->where('tongHopBindingOptions.2.key', 'Mã số')
                ->where('tongHopBindingOptions.2.valuePreview', '19220')
                ->has('previewCustomerOptions', 2)
            );
    }

    public function test_templates_page_can_render_cam_ca_table_preview_from_real_aggregate_payload(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template preview Cám cá',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-F',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'cam-ca-table',
                        'label' => 'Table Chiết khấu cám cá',
                        'kind' => 'table',
                        'sourceSheet' => 'Cám cá',
                        'rows' => [
                            ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'hideWhenValueZero' => false, 'isBold' => false],
                            ['content' => 'Tiền chiết khấu theo hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Tiền chiết khấu theo Hóa đơn', 'hideWhenValueZero' => false, 'isBold' => true],
                            ['content' => 'Thưởng sản lượng tháng 03.2026', 'rowType' => 'child-value', 'columnKey' => 'Thưởng sản lượng tháng 03.2026', 'hideWhenValueZero' => false, 'isBold' => false],
                            ['content' => '', 'rowType' => 'child-program-loop', 'hideWhenValueZero' => true, 'isBold' => false],
                            ['content' => 'Chiết khấu khác ( không thể hiện trên hóa đơn)', 'rowType' => 'parent', 'columnKey' => 'Chiết khấu khác ( Không thể hiện trên hóa đơn)', 'hideWhenValueZero' => false, 'isBold' => true],
                            ['content' => 'Chiết khấu thanh toán', 'rowType' => 'child-value', 'columnKey' => 'Chiết khấu thanh toán', 'hideWhenValueZero' => true, 'isBold' => false],
                            ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => true, 'isBold' => true],
                            ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'hideWhenValueZero' => true, 'isBold' => false],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-CAM-CA-PREVIEW',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Cám cá' => [
                        'fixedHeaders' => [
                            'STT', 'Tháng', 'Mã số', 'Mã & tên khách hàng', 'Email', 'Địa chỉ', 'Thức ăn chăn nuôi',
                            'Tổng sản lượng', 'Doanh thu', 'Tiền chiết khấu theo Hóa đơn', 'Chiết khấu khác ( Không thể hiện trên hóa đơn)', 'Tổng cộng', 'Bằng chữ',
                        ],
                        'discreteHeaders' => [
                            'Thưởng sản lượng tháng 03.2026',
                            'Thưởng đặc biệt tháng 03.2026',
                            'Thưởng ngân quỹ 9113',
                            'Hỗ trợ vận chuyển',
                            'Chương trình KM từ 25-31.03.26',
                            'Chiết khấu thanh toán',
                        ],
                    ],
                ],
            ],
        ]);

        $firstRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '16068',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Cám cá'],
            'aggregated_payload' => [
                'customerCode' => '16068',
                'customerFullName' => '16068 - Công ty TNHH TM DV Thắng Giang',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Cám cá'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => [
                    'month' => '3-2026',
                    'customerCode' => '16068',
                    'customerFullName' => '16068 - Công ty TNHH TM DV Thắng Giang',
                    'address' => 'Địa chỉ A',
                    'feedCategory' => 'Thủy sản',
                    'totalQuantity' => '128500',
                    'revenue' => '2479904400',
                    'invoiceDiscount' => '199615000',
                    'otherDiscount' => '12850000',
                    'grandTotal' => '212465000',
                    'totalInWords' => 'Hai trăm mười hai triệu, bốn trăm sáu mươi lăm nghìn đồng chẵn.',
                    'programItems' => [
                        ['programIndex' => 1, 'content' => 'Chiết khấu quý 1.2026 sản phẩm 9113 mức 250đ/kg', 'amount' => '51200000'],
                        ['programIndex' => 2, 'content' => 'Hỗ trợ đặc biệt sản phẩm cá biển', 'amount' => '6150000'],
                    ],
                    'discreteItems' => [
                        ['label' => 'Thưởng sản lượng tháng 03.2026', 'value' => '89950000'],
                        ['label' => 'Thưởng đặc biệt tháng 03.2026', 'value' => '20780000'],
                        ['label' => 'Thưởng ngân quỹ 9113', 'value' => '9600000'],
                        ['label' => 'Hỗ trợ vận chuyển', 'value' => '19275000'],
                        ['label' => 'Chương trình KM từ 25-31.03.26', 'value' => '2660000'],
                        ['label' => 'Chiết khấu thanh toán', 'value' => '12850000'],
                    ],
                ],
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('builderTemplate.id', $template->id)
                ->where('selectedPreviewRecordId', $firstRecord->id)
                ->where('camCaTablePreview.sample.recordId', $firstRecord->id)
                ->where('camCaTablePreview.sample.customerCode', '16068')
                ->has('previewCustomerOptions', 1)
                ->where('previewCustomerOptions.0.recordId', $firstRecord->id)
                ->where('camCaTablePreview.title', 'Chiết khấu cám cá tháng 3-2026')
                ->where('camCaTablePreview.rows.0.content', 'Tổng sản lượng')
                ->where('camCaTablePreview.rows.0.value', '128500')
                ->where('camCaTablePreview.rows.1.numbering', 'I')
                ->where('camCaTablePreview.rows.2.numbering', '1')
                ->where('camCaTablePreview.rows.2.value', '89950000')
                ->where('camCaTablePreview.rows.3.numbering', '2')
                ->where('camCaTablePreview.rows.3.content', 'Chiết khấu quý 1.2026 sản phẩm 9113 mức 250đ/kg')
                ->where('camCaTablePreview.rows.4.numbering', '3')
                ->where('camCaTablePreview.rows.5.numbering', 'II')
                ->where('camCaTablePreview.rows.6.content', 'Chiết khấu thanh toán')
                ->where('camCaTablePreview.rows.7.content', 'Cộng')
                ->where('camCaTablePreview.rows.7.value', '212465000')
                ->where('camCaTablePreview.errors', [])
            );
    }

    public function test_templates_page_can_switch_cam_ca_preview_to_selected_customer_record(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template switch Cám cá',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-F',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'cam-ca-table',
                        'label' => 'Table Chiết khấu cám cá',
                        'kind' => 'table',
                        'sourceSheet' => 'Cám cá',
                        'rows' => [
                            ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'hideWhenValueZero' => false, 'isBold' => false],
                            ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => true, 'isBold' => true],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-CAM-CA-SWITCH',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Cám cá' => [
                        'fixedHeaders' => ['Tổng sản lượng', 'Doanh thu', 'Tiền chiết khấu theo Hóa đơn', 'Chiết khấu khác ( Không thể hiện trên hóa đơn)', 'Tổng cộng', 'Bằng chữ'],
                        'discreteHeaders' => ['Chiết khấu thanh toán'],
                    ],
                ],
            ],
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '16068',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Cám cá'],
            'aggregated_payload' => [
                'customerCode' => '16068',
                'customerFullName' => 'Khách A',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Cám cá'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => [
                    'month' => '3-2026',
                    'totalQuantity' => '111',
                    'grandTotal' => '222',
                    'totalInWords' => 'Hai trăm hai mươi hai đồng.',
                    'programItems' => [],
                    'discreteItems' => [],
                ],
                'keyAccount' => null,
            ],
        ]);

        $selectedRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90182TS',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Cám cá'],
            'aggregated_payload' => [
                'customerCode' => '90182TS',
                'customerFullName' => 'Khách B',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Cám cá'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => [
                    'month' => '4-2026',
                    'totalQuantity' => '555',
                    'grandTotal' => '777',
                    'totalInWords' => 'Bảy trăm bảy mươi bảy đồng.',
                    'programItems' => [],
                    'discreteItems' => [],
                ],
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index', ['preview_record' => $selectedRecord->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedPreviewRecordId', $selectedRecord->id)
                ->where('camCaTablePreview.sample.recordId', $selectedRecord->id)
                ->where('camCaTablePreview.sample.customerCode', '90182TS')
                ->where('camCaTablePreview.sample.customerFullName', 'Khách B')
                ->where('camCaTablePreview.title', 'Chiết khấu cám cá tháng 4-2026')
                ->where('camCaTablePreview.rows.0.value', '555')
                ->where('camCaTablePreview.rows.1.value', '777')
                ->where('camCaBindingOptions.0.key', 'Tổng sản lượng')
                ->where('camCaBindingOptions.0.valuePreview', '555')
                ->has('previewCustomerOptions', 2)
            );
    }

    public function test_templates_page_can_render_key_account_table_preview_from_real_aggregate_payload(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template preview Key Account',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-G',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'key-account-table',
                        'label' => 'Table Chiết khấu Key Account',
                        'kind' => 'table',
                        'sourceSheet' => 'Key Account',
                        'rows' => [
                            ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'quantity', 'isBold' => false],
                            ['content' => 'Chiết khấu theo hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Chiết khấu theo hóa đơn', 'valueColumn' => 'amount', 'isBold' => true],
                            ['content' => '', 'rowType' => 'child-program-loop', 'isBold' => false],
                            ['content' => 'Cộng', 'rowType' => 'total', 'isBold' => true],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-KEY-ACCOUNT-PREVIEW',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Key Account' => [
                        'fixedHeaders' => [
                            'STT', 'Tháng', 'Mã số', 'Mã & tên khách hàng', 'Email', 'Địa chỉ', 'Thức ăn chăn nuôi',
                            'Tổng sản lượng', 'Doanh thu', 'Chiết khấu theo hóa đơn', 'Tổng cộng', 'Bằng chữ',
                        ],
                        'discreteHeaders' => [
                            'Thưởng doanh thu tháng 02.2026',
                            'Hỗ trợ vận chuyển cám SILO',
                        ],
                    ],
                ],
            ],
        ]);

        $firstRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '11008',
            'customer_type' => 'Key Account',
            'source_sheets' => ['Key Account'],
            'aggregated_payload' => [
                'customerCode' => '11008',
                'customerFullName' => '11008 - Siêu thị Key Account A',
                'customerType' => 'Key Account',
                'sourceSheets' => ['Key Account'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => [
                    'month' => '02.2026',
                    'customerCode' => '11008',
                    'customerFullName' => '11008 - Siêu thị Key Account A',
                    'address' => 'Địa chỉ A',
                    'feedCategory' => 'Feed A',
                    'totalQuantity' => '29135',
                    'revenue' => '392236675',
                    'invoiceDiscount' => '42925867',
                    'grandTotal' => '147061500',
                    'totalInWords' => 'Một trăm bốn mươi bảy triệu không trăm sáu mươi mốt nghìn năm trăm đồng chẵn.',
                    'programItems' => [
                        ['programIndex' => 1, 'content' => 'Chiết khấu tháng sản phẩm cám heo: 380đ/kg', 'quantity' => '28975', 'supportRate' => '380', 'amount' => '11010500'],
                        ['programIndex' => 2, 'content' => 'Hỗ trợ đặc biệt sản phẩm T1120', 'quantity' => '', 'supportRate' => '1270', 'amount' => ''],
                    ],
                    'discreteItems' => [
                        ['label' => 'Thưởng doanh thu tháng 02.2026', 'value' => '3922367'],
                        ['label' => 'Hỗ trợ vận chuyển cám SILO', 'value' => '12645000'],
                    ],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('builderTemplate.id', $template->id)
                ->where('selectedPreviewRecordId', $firstRecord->id)
                ->where('keyAccountTablePreview.sample.recordId', $firstRecord->id)
                ->where('keyAccountTablePreview.sample.customerCode', '11008')
                ->where('keyAccountTablePreview.title', 'Chiết khấu Key Account tháng 02.2026')
                ->where('keyAccountTablePreview.rows.0.quantity', '29135')
                ->where('keyAccountTablePreview.rows.1.numbering', 'I')
                ->where('keyAccountTablePreview.rows.1.amount', '42925867')
                ->where('keyAccountTablePreview.rows.2.numbering', '1')
                ->where('keyAccountTablePreview.rows.2.supportRate', '380')
                ->where('keyAccountTablePreview.rows.3.numbering', '2')
                ->where('keyAccountTablePreview.rows.3.supportRate', '1270')
                ->where('keyAccountTablePreview.rows.4.amount', '147061500')
                ->where('keyAccountBindingOptions.0.key', 'Tổng sản lượng')
                ->where('keyAccountBindingOptions.0.defaultValueColumn', 'quantity')
                ->has('previewCustomerOptions', 1)
            );
    }

    public function test_templates_page_can_switch_key_account_preview_to_selected_customer_record(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template switch Key Account',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-G',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'key-account-table',
                        'label' => 'Table Chiết khấu Key Account',
                        'kind' => 'table',
                        'sourceSheet' => 'Key Account',
                        'rows' => [
                            ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'quantity', 'isBold' => false],
                            ['content' => 'Cộng', 'rowType' => 'total', 'isBold' => true],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-KEY-ACCOUNT-SWITCH',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Key Account' => [
                        'fixedHeaders' => ['Tổng sản lượng', 'Doanh thu', 'Chiết khấu theo hóa đơn', 'Tổng cộng', 'Bằng chữ'],
                        'discreteHeaders' => ['Thưởng doanh thu tháng 02.2026'],
                    ],
                ],
            ],
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '11008',
            'customer_type' => 'Key Account',
            'source_sheets' => ['Key Account'],
            'aggregated_payload' => [
                'customerCode' => '11008',
                'customerFullName' => 'Khách A',
                'customerType' => 'Key Account',
                'sourceSheets' => ['Key Account'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => [
                    'month' => '02.2026',
                    'totalQuantity' => '111',
                    'grandTotal' => '222',
                    'totalInWords' => 'Hai trăm hai mươi hai đồng.',
                    'programItems' => [],
                    'discreteItems' => [],
                ],
            ],
        ]);

        $selectedRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '14799',
            'customer_type' => 'Key Account',
            'source_sheets' => ['Key Account'],
            'aggregated_payload' => [
                'customerCode' => '14799',
                'customerFullName' => 'Khách B',
                'customerType' => 'Key Account',
                'sourceSheets' => ['Key Account'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => [
                    'month' => '03.2026',
                    'totalQuantity' => '555',
                    'grandTotal' => '777',
                    'totalInWords' => 'Bảy trăm bảy mươi bảy đồng.',
                    'programItems' => [],
                    'discreteItems' => [],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index', ['preview_record' => $selectedRecord->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedPreviewRecordId', $selectedRecord->id)
                ->where('keyAccountTablePreview.sample.recordId', $selectedRecord->id)
                ->where('keyAccountTablePreview.sample.customerCode', '14799')
                ->where('keyAccountTablePreview.sample.customerFullName', 'Khách B')
                ->where('keyAccountTablePreview.title', 'Chiết khấu Key Account tháng 03.2026')
                ->where('keyAccountTablePreview.rows.0.quantity', '555')
                ->where('keyAccountTablePreview.rows.1.amount', '777')
                ->where('keyAccountBindingOptions.0.key', 'Tổng sản lượng')
                ->where('keyAccountBindingOptions.0.quantityPreview', '555')
                ->has('previewCustomerOptions', 2)
            );
    }

    public function test_templates_page_can_switch_subject_and_greeting_preview_to_selected_customer_record(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template preview text parts',
            'subject_template' => 'Chế độ tháng {{tháng}} - {{mã & tên khách hàng}}',
            'structure_json' => [
                'version' => '2.3-C',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}} - {{mã & tên khách hàng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => "Kính gửi {{mã & tên khách hàng}},\nĐịa chỉ: {{địa chỉ}}"],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TEXT-PREVIEW-SWITCH',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '11008',
            'customer_type' => 'Key Account',
            'source_sheets' => ['Key Account'],
            'aggregated_payload' => [
                'customerCode' => '11008',
                'customerFullName' => '11008 - Siêu thị Key Account A',
                'customerType' => 'Key Account',
                'sourceSheets' => ['Key Account'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => [
                    'month' => '02.2026',
                    'address' => 'Địa chỉ A',
                    'feedCategory' => 'Feed A',
                ],
            ],
        ]);

        $selectedRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90302',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90302',
                'customerFullName' => '90302 - Công ty C',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '03.2026',
                    'address' => 'Địa chỉ C',
                    'feedCategory' => 'Feed C',
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index', ['preview_record' => $selectedRecord->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedPreviewRecordId', $selectedRecord->id)
                ->where('subjectPreview.sample.recordId', $selectedRecord->id)
                ->where('subjectPreview.sample.customerCode', '90302')
                ->where('subjectPreview.renderedText', 'Chế độ tháng 03.2026 - 90302 - Công ty C')
                ->where('greetingPreview.sample.recordId', $selectedRecord->id)
                ->where('greetingPreview.sample.address', 'Địa chỉ C')
                ->where('greetingPreview.renderedText', "Kính gửi 90302 - Công ty C,\nĐịa chỉ: Địa chỉ C")
                ->has('previewCustomerOptions', 2)
            );
    }

    public function test_templates_page_can_render_khoan_npp_table_preview_from_real_aggregate_payload(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template preview Khoán NPP',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'khoan-npp-table',
                        'label' => 'Table Chương trình khoán đặc biệt',
                        'kind' => 'table',
                        'sourceSheet' => 'Khoán NPP',
                        'rows' => [
                            ['content' => '', 'rowType' => 'program-loop', 'hideWhenValueZero' => true, 'isBold' => false],
                            ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => true, 'isBold' => true],
                            ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'hideWhenValueZero' => true, 'isBold' => false],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-KHOAN-NPP-PREVIEW',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $firstRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Khoán NPP'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Khoán NPP'],
                'tongHop' => null,
                'khoanNpp' => [
                    'month' => '03.2026',
                    'customerCode' => '90300',
                    'customerFullName' => '90300 - Công ty A',
                    'address' => 'Địa chỉ A',
                    'feedCategory' => 'Feed A',
                    'grandTotal' => '24371500',
                    'totalInWords' => 'Hai mươi bốn triệu, ba trăm bảy mươi mốt nghìn, năm trăm đồng chẵn.',
                    'programItems' => [
                        ['programIndex' => 1, 'content' => 'CT 1', 'quantity' => '59170', 'supportRate' => '200', 'amount' => '11834000'],
                        ['programIndex' => 2, 'content' => 'CT 2', 'quantity' => '15375', 'supportRate' => '100', 'amount' => '1537500'],
                        ['programIndex' => 3, 'content' => 'CT 3', 'quantity' => '', 'supportRate' => '', 'amount' => '6000000'],
                        ['programIndex' => 4, 'content' => '', 'quantity' => '', 'supportRate' => '', 'amount' => '0'],
                    ],
                ],
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('builderTemplate.id', $template->id)
                ->where('khoanNppTablePreview.sample.batchCode', 'IMP-KHOAN-NPP-PREVIEW')
                ->where('khoanNppTablePreview.sample.customerCode', '90300')
                ->where('selectedPreviewRecordId', $firstRecord->id)
                ->where('khoanNppTablePreview.title', 'Chương trình khoán đặc biệt tháng 03.2026')
                ->has('previewCustomerOptions', 1)
                ->where('previewCustomerOptions.0.recordId', $firstRecord->id)
                ->where('previewCustomerOptions.0.customerCode', '90300')
                ->has('khoanNppTablePreview.rows', 5)
                ->where('khoanNppTablePreview.rows.0.numbering', '1')
                ->where('khoanNppTablePreview.rows.0.content', 'CT 1')
                ->where('khoanNppTablePreview.rows.0.quantity', '59170')
                ->where('khoanNppTablePreview.rows.1.numbering', '2')
                ->where('khoanNppTablePreview.rows.1.content', 'CT 2')
                ->where('khoanNppTablePreview.rows.2.numbering', '3')
                ->where('khoanNppTablePreview.rows.2.content', 'CT 3')
                ->where('khoanNppTablePreview.rows.3.content', 'Cộng')
                ->where('khoanNppTablePreview.rows.3.amount', '24371500')
                ->where('khoanNppTablePreview.rows.4.content', 'Bằng chữ:')
                ->where('khoanNppTablePreview.errors', [])
            );
    }

    public function test_templates_page_can_switch_khoan_npp_preview_to_selected_customer_record(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template preview khách Khoán NPP',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'khoan-npp-table',
                        'label' => 'Table Chương trình khoán đặc biệt',
                        'kind' => 'table',
                        'sourceSheet' => 'Khoán NPP',
                        'rows' => [
                            ['content' => '', 'rowType' => 'program-loop', 'hideWhenValueZero' => true, 'isBold' => false],
                            ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => true, 'isBold' => true],
                            ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'hideWhenValueZero' => true, 'isBold' => false],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-KHOAN-NPP-SWITCH',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Khoán NPP'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => 'Công ty A',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Khoán NPP'],
                'tongHop' => null,
                'khoanNpp' => [
                    'month' => '03.2026',
                    'customerCode' => '90300',
                    'customerFullName' => 'Công ty A',
                    'address' => 'Địa chỉ A',
                    'feedCategory' => 'Feed A',
                    'grandTotal' => '24371500',
                    'totalInWords' => 'Tổng A',
                    'programItems' => [
                        ['programIndex' => 1, 'content' => 'CT A1', 'quantity' => '10', 'supportRate' => '2', 'amount' => '20'],
                    ],
                ],
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $selectedRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '19236',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Khoán NPP'],
            'aggregated_payload' => [
                'customerCode' => '19236',
                'customerFullName' => 'Công ty B',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Khoán NPP'],
                'tongHop' => null,
                'khoanNpp' => [
                    'month' => '04.2026',
                    'customerCode' => '19236',
                    'customerFullName' => 'Công ty B',
                    'address' => 'Địa chỉ B',
                    'feedCategory' => 'Feed B',
                    'grandTotal' => '500',
                    'totalInWords' => 'Năm trăm đồng chẵn.',
                    'programItems' => [
                        ['programIndex' => 1, 'content' => 'CT B1', 'quantity' => '5', 'supportRate' => '100', 'amount' => '500'],
                    ],
                ],
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index', ['preview_record' => $selectedRecord->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedPreviewRecordId', $selectedRecord->id)
                ->where('khoanNppTablePreview.sample.recordId', $selectedRecord->id)
                ->where('khoanNppTablePreview.sample.customerCode', '19236')
                ->where('khoanNppTablePreview.sample.customerFullName', 'Công ty B')
                ->where('khoanNppTablePreview.title', 'Chương trình khoán đặc biệt tháng 04.2026')
                ->where('khoanNppTablePreview.rows.0.content', 'CT B1')
                ->has('previewCustomerOptions', 2)
            );
    }

    public function test_templates_page_exposes_tong_hop_table_preview_error_when_row_has_no_matching_data(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template lỗi row Tổng hợp',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-D',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'tong-hop-table',
                        'label' => 'Table Chế độ tháng',
                        'kind' => 'table',
                        'sourceSheet' => 'Tổng hợp',
                        'rows' => [
                            ['content' => 'Nội dung không tồn tại', 'rowType' => 'child', 'columnKey' => 'Nội dung không tồn tại', 'hideWhenValueZero' => false, 'isBold' => false],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TONG-HOP-ERROR',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90303',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90303',
                'customerFullName' => '90303 - Công ty D',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '02.2026',
                    'customerCode' => '90303',
                    'customerFullName' => '90303 - Công ty D',
                    'address' => 'Địa chỉ D',
                    'feedCategory' => 'Feed D',
                    'totalQuantity' => '1000',
                    'revenue' => '5000000',
                    'invoiceDiscount' => '300000',
                    'commitmentBonus' => '100000',
                    'fishFeedDiscount' => '0',
                    'otherDiscount' => '0',
                    'grandTotal' => '300000',
                    'totalInWords' => 'Ba trăm nghìn đồng chẵn.',
                    'dynamicItems' => [],
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('builderTemplate.id', $template->id)
                ->where('tongHopTablePreview.rows.0.value', '')
                ->where('tongHopTablePreview.errors.0', 'Dòng "Nội dung không tồn tại" chưa tìm thấy dữ liệu tương ứng trong sheet Tổng hợp đã aggregate.')
            );
    }

    public function test_templates_page_hides_tong_hop_row_when_flag_hide_when_value_zero_is_enabled(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template ẩn khi bằng 0',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-D',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'tong-hop-table',
                        'label' => 'Table Chế độ tháng',
                        'kind' => 'table',
                        'sourceSheet' => 'Tổng hợp',
                        'rows' => [
                            ['content' => 'Chiết khấu cám cá', 'rowType' => 'child', 'columnKey' => 'Chiết khấu cám cá', 'hideWhenValueZero' => true, 'isBold' => false],
                            ['content' => 'Cộng', 'rowType' => 'total', 'columnKey' => 'Cộng', 'hideWhenValueZero' => false, 'isBold' => true],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TONG-HOP-HIDE-ZERO',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90304',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90304',
                'customerFullName' => '90304 - Công ty E',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '02.2026',
                    'customerCode' => '90304',
                    'customerFullName' => '90304 - Công ty E',
                    'address' => 'Địa chỉ E',
                    'feedCategory' => 'Feed E',
                    'totalQuantity' => '1000',
                    'revenue' => '5000000',
                    'invoiceDiscount' => '300000',
                    'commitmentBonus' => '100000',
                    'fishFeedDiscount' => '0',
                    'otherDiscount' => '0',
                    'grandTotal' => '300000',
                    'totalInWords' => 'Ba trăm nghìn đồng chẵn.',
                    'dynamicItems' => [],
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('builderTemplate.id', $template->id)
                ->has('tongHopTablePreview.rows', 1)
                ->where('tongHopTablePreview.rows.0.content', 'Cộng')
                ->where('tongHopTablePreview.rows.0.value', '300000')
            );
    }

    public function test_templates_page_does_not_report_error_for_valid_parsed_tong_hop_headers_that_are_blank_for_current_customer(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template header hợp lệ nhưng rỗng',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-D',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    [
                        'type' => 'tong-hop-table',
                        'label' => 'Table Chế độ tháng',
                        'kind' => 'table',
                        'sourceSheet' => 'Tổng hợp',
                        'rows' => [
                            ['content' => 'Khoán tháng', 'rowType' => 'child', 'columnKey' => 'Khoán tháng', 'hideWhenValueZero' => true, 'isBold' => false],
                            ['content' => 'Cộng', 'rowType' => 'total', 'columnKey' => 'Cộng', 'hideWhenValueZero' => false, 'isBold' => true],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TONG-HOP-BLANK-HEADER',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
            'workbook_summary' => [
                'sheetPreviews' => [
                    'Tổng hợp' => [
                        'fixedHeaders' => ['Tháng', 'Mã số', 'Mã & tên khách hàng', 'Tổng cộng'],
                        'dynamicHeaders' => ['Khoán tháng'],
                    ],
                ],
            ],
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '19220',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '19220',
                'customerFullName' => '19220 - Công ty F',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '03.2026',
                    'customerCode' => '19220',
                    'customerFullName' => '19220 - Công ty F',
                    'grandTotal' => '102854250',
                    'dynamicItems' => [],
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('tongHopTablePreview.rows', 1)
                ->where('tongHopTablePreview.rows.0.content', 'Cộng')
                ->where('tongHopTablePreview.rows.0.value', '102854250')
                ->where('tongHopTablePreview.errors', [])
            );
    }

    public function test_templates_page_exposes_subject_preview_error_for_legacy_unknown_variable(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template lỗi biến cũ',
            'subject_template' => 'Subject legacy',
            'structure_json' => [
                'version' => '2.3-A',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{biến lạ}}'],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-SUBJECT-ERROR',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '02.2026',
                    'address' => 'Địa chỉ A',
                    'feedCategory' => 'Feed A',
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('builderTemplate.id', $template->id)
                ->where('subjectPreview.renderedText', 'Chế độ tháng {{biến lạ}}')
                ->where('subjectPreview.errors.0', 'Biến {{biến lạ}} không nằm trong contract template email.')
            );
    }

    public function test_templates_page_exposes_greeting_preview_error_when_allowed_variable_has_no_real_data(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template greeting thiếu dữ liệu',
            'subject_template' => 'Subject preview',
            'structure_json' => [
                'version' => '2.3-C',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}} - {{địa chỉ}}'],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-GREETING-MISSING-DATA',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90302',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90302',
                'customerFullName' => '90302 - Công ty C',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '02.2026',
                    'address' => '',
                    'feedCategory' => 'Feed C',
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('builderTemplate.id', $template->id)
                ->where('greetingPreview.renderedText', 'Kính gửi 90302 - Công ty C - {{địa chỉ}}')
                ->where('greetingPreview.errors.0', 'Biến {{địa chỉ}} chưa có dữ liệu thật để preview.')
            );
    }

    public function test_templates_page_exposes_subject_preview_error_when_allowed_variable_has_no_real_data(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Template thiếu dữ liệu thật',
            'subject_template' => 'Subject legacy',
            'structure_json' => [
                'version' => '2.3-B',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}} - {{địa chỉ}}'],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-SUBJECT-MISSING-DATA',
            'original_file_name' => 'preview.xlsx',
            'stored_path' => 'imports/tmp/preview.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90301',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90301',
                'customerFullName' => '90301 - Công ty B',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '02.2026',
                    'address' => '',
                    'feedCategory' => 'Feed B',
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('builderTemplate.id', $template->id)
                ->where('subjectPreview.renderedText', 'Chế độ tháng 02.2026 - {{địa chỉ}}')
                ->where('subjectPreview.errors.0', 'Biến {{địa chỉ}} chưa có dữ liệu thật để preview.')
            );
    }
}
