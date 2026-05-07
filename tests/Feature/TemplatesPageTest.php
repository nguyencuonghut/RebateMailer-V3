<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
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
                ->where('currentSlice.code', '2.3-C')
                ->where('canManageTemplates', true)
                ->has('writeCapabilities', 4)
                ->where('writeCapabilities.0', 'Tạo template mới')
                ->has('templateParts', 6)
                ->has('templateVariables', 4)
                ->where('templateVariables.0.token', '{{tháng}}')
                ->where('templateVariables.1.token', '{{mã & tên khách hàng}}')
                ->where('templateVariables.2.token', '{{địa chỉ}}')
                ->where('templateVariables.3.token', '{{thức ăn chăn nuôi}}')
                ->where('builderTemplate', null)
                ->where('subjectPreview', null)
                ->where('greetingPreview', null)
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
                ->where('nextSlice.code', '2.3-D')
            );
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
