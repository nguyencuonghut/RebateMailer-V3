<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailTemplate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplatesEndToEndSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_templates_module_smoke_flow_can_create_configure_activate_and_reload_preview_context(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-SMOKE-2026-03',
            'name' => 'Batch smoke tháng 03-2026',
            'original_file_name' => 'smoke.xlsx',
            'stored_path' => 'imports/tmp/smoke.xlsx',
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
                            'Email',
                            'Địa chỉ',
                            'Thức ăn chăn nuôi',
                            'Tổng sản lượng (gồm cám thủy sản)',
                            'Tổng cộng',
                            'Bằng chữ',
                        ],
                        'dynamicHeaders' => [],
                    ],
                    'Cám cá' => [
                        'fixedHeaders' => [
                            'Tổng sản lượng',
                            'Tổng cộng',
                            'Bằng chữ',
                        ],
                        'discreteHeaders' => [],
                    ],
                    'Key Account' => [
                        'fixedHeaders' => [
                            'Tổng sản lượng',
                            'Tổng cộng',
                            'Bằng chữ',
                        ],
                        'discreteHeaders' => [],
                    ],
                ],
            ],
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '11008',
            'customer_type' => 'Key Account',
            'source_sheets' => ['Tổng hợp', 'Khoán NPP', 'Cám cá', 'Key Account'],
            'aggregated_payload' => [
                'customerCode' => '11008',
                'customerFullName' => '11008 - Siêu thị smoke',
                'customerType' => 'Key Account',
                'sourceSheets' => ['Tổng hợp', 'Khoán NPP', 'Cám cá', 'Key Account'],
                'tongHop' => [
                    'month' => '03.2026',
                    'customerCode' => '11008',
                    'customerFullName' => '11008 - Siêu thị smoke',
                    'address' => '123 Đường Smoke',
                    'feedCategory' => 'Cám hỗn hợp',
                    'totalQuantity' => '123456',
                    'grandTotal' => '456789',
                    'totalInWords' => 'Bốn trăm năm mươi sáu nghìn bảy trăm tám mươi chín đồng.',
                    'dynamicItems' => [],
                ],
                'khoanNpp' => [
                    'month' => '03.2026',
                    'customerCode' => '11008',
                    'customerFullName' => '11008 - Siêu thị smoke',
                    'address' => '123 Đường Smoke',
                    'feedCategory' => 'Cám hỗn hợp',
                    'programItems' => [
                        [
                            'programIndex' => 1,
                            'content' => 'CT hỗ trợ quý I',
                            'quantity' => '200',
                            'supportRate' => '500',
                            'amount' => '100000',
                        ],
                    ],
                    'grandTotal' => '100000',
                    'totalInWords' => 'Một trăm nghìn đồng.',
                ],
                'camCa' => [
                    'month' => '3-2026',
                    'customerCode' => '11008',
                    'customerFullName' => '11008 - Siêu thị smoke',
                    'address' => '123 Đường Smoke',
                    'feedCategory' => 'Cám hỗn hợp',
                    'totalQuantity' => '888',
                    'grandTotal' => '222000',
                    'totalInWords' => 'Hai trăm hai mươi hai nghìn đồng.',
                    'programItems' => [
                        [
                            'programIndex' => 1,
                            'content' => 'CT cám cá đặc biệt',
                            'amount' => '22000',
                        ],
                    ],
                    'discreteItems' => [],
                ],
                'keyAccount' => [
                    'month' => '03.2026',
                    'customerCode' => '11008',
                    'customerFullName' => '11008 - Siêu thị smoke',
                    'address' => '123 Đường Smoke',
                    'feedCategory' => 'Cám hỗn hợp',
                    'totalQuantity' => '999',
                    'grandTotal' => '333000',
                    'totalInWords' => 'Ba trăm ba mươi ba nghìn đồng.',
                    'programItems' => [
                        [
                            'programIndex' => 1,
                            'content' => 'CT Key Account tháng 3',
                            'quantity' => '40',
                            'supportRate' => '700',
                            'amount' => '28000',
                        ],
                    ],
                    'discreteItems' => [],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->post(route('templates.store'), [
                'name' => 'Canvas smoke tháng 03-2026',
            ])
            ->assertRedirect(route('templates.index'));

        $mailTemplate = MailTemplate::query()->where('name', 'Canvas smoke tháng 03-2026')->firstOrFail();

        foreach (['subject', 'greeting', 'tong-hop-table', 'khoan-npp-table', 'cam-ca-table', 'key-account-table'] as $partType) {
            $this->actingAs($user)
                ->post(route('templates.sections.store', $mailTemplate), [
                    'type' => $partType,
                ])
                ->assertRedirect(route('templates.index'));
        }

        $this->actingAs($user)
            ->put(route('templates.parts.update', $mailTemplate), [
                'partType' => 'subject',
                'content' => 'Chế độ tháng {{tháng}} - {{mã & tên khách hàng}}',
            ])
            ->assertRedirect(route('templates.index'));

        $this->actingAs($user)
            ->put(route('templates.parts.update', $mailTemplate), [
                'partType' => 'greeting',
                'content' => "Kính gửi {{mã & tên khách hàng}},\nĐịa chỉ: {{địa chỉ}}\nNhóm thức ăn: {{thức ăn chăn nuôi}}",
            ])
            ->assertRedirect(route('templates.index'));

        $this->actingAs($user)
            ->put(route('templates.parts.update', $mailTemplate), [
                'partType' => 'tong-hop-table',
                'section' => [
                    'type' => 'tong-hop-table',
                    'label' => 'Table Chế độ tháng',
                    'description' => 'Lấy dữ liệu từ sheet Tổng hợp.',
                    'kind' => 'table',
                    'sourceSheet' => 'Tổng hợp',
                    'rows' => [
                        ['content' => 'Tổng sản lượng (gồm cám thủy sản)', 'rowType' => 'data', 'columnKey' => 'Tổng sản lượng (gồm cám thủy sản)', 'hideWhenValueZero' => false, 'isBold' => false],
                        ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => false, 'isBold' => true],
                        ['content' => 'Bằng chữ:', 'rowType' => 'text', 'columnKey' => 'Bằng chữ', 'hideWhenValueZero' => false, 'isBold' => false],
                    ],
                ],
            ])
            ->assertRedirect(route('templates.index'));

        $this->actingAs($user)
            ->put(route('templates.parts.update', $mailTemplate), [
                'partType' => 'khoan-npp-table',
                'section' => [
                    'type' => 'khoan-npp-table',
                    'label' => 'Table Chương trình khoán đặc biệt',
                    'description' => 'Lấy dữ liệu từ sheet Khoán NPP.',
                    'kind' => 'table',
                    'sourceSheet' => 'Khoán NPP',
                    'rows' => [
                        ['content' => '', 'rowType' => 'program-loop', 'hideWhenValueZero' => true, 'isBold' => false],
                        ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => true, 'isBold' => true],
                        ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'hideWhenValueZero' => true, 'isBold' => false],
                    ],
                ],
            ])
            ->assertRedirect(route('templates.index'));

        $this->actingAs($user)
            ->put(route('templates.parts.update', $mailTemplate), [
                'partType' => 'cam-ca-table',
                'section' => [
                    'type' => 'cam-ca-table',
                    'label' => 'Table Chiết khấu cám cá',
                    'description' => 'Lấy dữ liệu từ sheet Cám cá.',
                    'kind' => 'table',
                    'sourceSheet' => 'Cám cá',
                    'rows' => [
                        ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'hideWhenValueZero' => false, 'isBold' => false],
                        ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => false, 'isBold' => true],
                        ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'hideWhenValueZero' => false, 'isBold' => false],
                    ],
                ],
            ])
            ->assertRedirect(route('templates.index'));

        $this->actingAs($user)
            ->put(route('templates.parts.update', $mailTemplate), [
                'partType' => 'key-account-table',
                'section' => [
                    'type' => 'key-account-table',
                    'label' => 'Table Chiết khấu Key Account',
                    'description' => 'Lấy dữ liệu từ sheet Key Account.',
                    'kind' => 'table',
                    'sourceSheet' => 'Key Account',
                    'rows' => [
                        ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'quantity', 'hideWhenValueZero' => false, 'isBold' => false],
                        ['content' => '', 'rowType' => 'child-program-loop', 'hideWhenValueZero' => false, 'isBold' => false],
                        ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => false, 'isBold' => true],
                    ],
                ],
            ])
            ->assertRedirect(route('templates.index'));

        $this->actingAs($user)
            ->put(route('templates.activate', $mailTemplate))
            ->assertRedirect(route('templates.index'));

        $this->actingAs($user)
            ->get(route('templates.index', [
                'preview_batch' => $importBatch->id,
                'preview_record' => $record->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Templates/Index')
                ->where('activeTemplateId', $mailTemplate->id)
                ->where('builderTemplate.id', $mailTemplate->id)
                ->where('selectedPreviewBatchId', $importBatch->id)
                ->where('selectedPreviewRecordId', $record->id)
                ->where('canvasComposition.partSelections.0.isConfigured', true)
                ->where('canvasComposition.partSelections.5.isConfigured', true)
                ->where('subjectPreview.sample.recordId', $record->id)
                ->where('subjectPreview.renderedText', 'Chế độ tháng 03.2026 - 11008 - Siêu thị smoke')
                ->where('greetingPreview.sample.recordId', $record->id)
                ->where('greetingPreview.renderedText', "Kính gửi 11008 - Siêu thị smoke,\nĐịa chỉ: 123 Đường Smoke\nNhóm thức ăn: Cám hỗn hợp")
                ->where('tongHopTablePreview.sample.recordId', $record->id)
                ->where('tongHopTablePreview.rows.0.value', '123456')
                ->where('khoanNppTablePreview.sample.recordId', $record->id)
                ->where('khoanNppTablePreview.rows.0.amount', '100000')
                ->where('camCaTablePreview.sample.recordId', $record->id)
                ->where('camCaTablePreview.rows.0.value', '888')
                ->where('keyAccountTablePreview.sample.recordId', $record->id)
                ->where('keyAccountTablePreview.rows.0.quantity', '999')
                ->where('keyAccountTablePreview.rows.1.amount', '28000')
            );
    }
}
