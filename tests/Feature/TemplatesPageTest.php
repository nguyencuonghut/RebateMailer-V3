<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
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
                ->where('currentSlice.code', '2.3-D')
                ->where('canManageTemplates', true)
                ->has('writeCapabilities', 4)
                ->where('writeCapabilities.0', 'Tạo template mới')
                ->has('templateParts', 6)
                ->has('templateVariables', 4)
                ->has('tongHopBindingOptions')
                ->where('templateVariables.0.token', '{{tháng}}')
                ->where('templateVariables.1.token', '{{mã & tên khách hàng}}')
                ->where('templateVariables.2.token', '{{địa chỉ}}')
                ->where('templateVariables.3.token', '{{thức ăn chăn nuôi}}')
                ->where('builderTemplate', null)
                ->where('subjectPreview', null)
                ->where('greetingPreview', null)
                ->where('tongHopTablePreview', null)
                ->where('templateParts.0.code', 'subject')
                ->where('templateParts.0.type', 'subject')
                ->where('templateParts.1.code', 'greeting')
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
                ->where('nextSlice.code', '2.3-E')
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
                    'address' => 'Địa chỉ mẫu',
                    'feedCategory' => 'Thức ăn mẫu',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Templates/Index')
                ->where('activeTemplateId', $activeTemplate->id)
                ->where('builderTemplate.id', $activeTemplate->id)
                ->where('builderTemplate.structure.sections.0.type', 'subject')
                ->where('builderTemplate.structure.sections.2.sourceSheet', 'Key Account')
                ->where('builderTemplate.structure.sections.2.rows.0.indentLevel', 1)
                ->where('subjectPreview.sample.batchCode', 'IMP-SUBJECT-PREVIEW')
                ->where('subjectPreview.sample.customerCode', '11008')
                ->where('subjectPreview.sample.month', '02.2026')
                ->where('subjectPreview.renderedText', 'Chế độ tháng 02.2026 - Key Account 11008 - Siêu thị Key Account A')
                ->where('subjectPreview.errors', [])
                ->where('greetingPreview.sample.batchCode', 'IMP-SUBJECT-PREVIEW')
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
                ->where('templateList.1.sectionCount', 2)
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
                ->where('tongHopTablePreview.sample.batchCode', 'IMP-TONG-HOP-PREVIEW')
                ->where('tongHopTablePreview.sample.customerCode', '90300')
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
