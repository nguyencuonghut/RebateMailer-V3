<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\TemplatePart;
use App\Models\TemplatePartVersion;
use App\Models\User;
use App\Services\Templates\SyncLegacyMailTemplateToCompositionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesPartUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_manage_permission_can_update_subject_part_through_part_route(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template subject',
            'subject_template' => 'Subject cũ',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject cũ'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                ],
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncMailTemplate($mailTemplate);

        $this->actingAs($user)
            ->put(route('templates.parts.update', $mailTemplate), [
                'partType' => 'subject',
                'content' => 'Subject mới {{tháng}}',
            ])
            ->assertRedirect(route('templates.index'));

        $subjectPart = TemplatePart::query()->where('type', 'subject')->firstOrFail();
        $subjectVersion = TemplatePartVersion::query()
            ->where('template_part_id', $subjectPart->id)
            ->where('legacy_mail_template_id', $mailTemplate->id)
            ->firstOrFail();

        $this->assertSame('Subject mới {{tháng}}', $subjectVersion->text_template);
        $this->assertSame('Subject mới {{tháng}}', $mailTemplate->fresh()->subject_template);
        $this->assertSame('Subject mới {{tháng}}', $mailTemplate->fresh()->structure_json['sections'][0]['content']);
    }

    public function test_user_with_manage_permission_can_update_table_part_through_part_route(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template table',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                    ['type' => 'tong-hop-table', 'label' => 'Table Chế độ tháng', 'kind' => 'table', 'sourceSheet' => 'Tổng hợp', 'rows' => []],
                ],
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncMailTemplate($mailTemplate);

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
                        [
                            'content' => 'Tổng sản lượng (gồm cám thủy sản)',
                            'rowType' => 'data',
                            'columnKey' => 'Tổng sản lượng (gồm cám thủy sản)',
                            'hideWhenValueZero' => false,
                            'isBold' => false,
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('templates.index'));

        $tongHopPart = TemplatePart::query()->where('type', 'tong-hop-table')->firstOrFail();
        $tongHopVersion = TemplatePartVersion::query()
            ->where('template_part_id', $tongHopPart->id)
            ->where('legacy_mail_template_id', $mailTemplate->id)
            ->firstOrFail();

        $this->assertSame('Tổng sản lượng (gồm cám thủy sản)', $tongHopVersion->structure_json['rows'][0]['content']);
        $this->assertSame('data', $tongHopVersion->structure_json['rows'][0]['rowType']);
        $this->assertSame('Tổng sản lượng (gồm cám thủy sản)', $mailTemplate->fresh()->structure_json['sections'][2]['rows'][0]['content']);
    }

    public function test_user_with_manage_permission_can_persist_khoan_npp_semantic_rows_through_part_route(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template Khoán NPP',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.3-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                    ['type' => 'khoan-npp-table', 'label' => 'Table Chương trình khoán đặc biệt', 'kind' => 'table', 'sourceSheet' => 'Khoán NPP', 'rows' => []],
                ],
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncMailTemplate($mailTemplate);

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

        $mailTemplate->refresh();

        $this->assertSame('khoan-npp-table', $mailTemplate->structure_json['sections'][2]['type']);
        $this->assertCount(3, $mailTemplate->structure_json['sections'][2]['rows']);
        $this->assertSame('program-loop', $mailTemplate->structure_json['sections'][2]['rows'][0]['rowType']);
        $this->assertSame('total', $mailTemplate->structure_json['sections'][2]['rows'][1]['rowType']);
        $this->assertSame('in-words', $mailTemplate->structure_json['sections'][2]['rows'][2]['rowType']);
    }

    public function test_part_update_returns_row_level_validation_errors_for_invalid_table_rows(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template invalid table row',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.3-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                    ['type' => 'khoan-npp-table', 'label' => 'Table Chương trình khoán đặc biệt', 'kind' => 'table', 'sourceSheet' => 'Khoán NPP', 'rows' => []],
                ],
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncMailTemplate($mailTemplate);

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->put(route('templates.parts.update', $mailTemplate), [
                'partType' => 'khoan-npp-table',
                'section' => [
                    'type' => 'khoan-npp-table',
                    'label' => 'Table Chương trình khoán đặc biệt',
                    'description' => 'Lấy dữ liệu từ sheet Khoán NPP.',
                    'kind' => 'table',
                    'sourceSheet' => 'Khoán NPP',
                    'rows' => [
                        ['content' => '', 'rowType' => 'sai-row-type'],
                    ],
                ],
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors([
                'section.rows.0.rowType',
            ]);
    }

    public function test_user_with_manage_permission_can_persist_cam_ca_semantic_rows_through_part_route(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template Cám cá',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.3-F',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                    ['type' => 'cam-ca-table', 'label' => 'Table Chiết khấu cám cá', 'kind' => 'table', 'sourceSheet' => 'Cám cá', 'rows' => []],
                ],
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncMailTemplate($mailTemplate);

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
                        ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'hideWhenValueZero' => true, 'isBold' => false],
                        ['content' => 'Tiền chiết khấu theo hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Tiền chiết khấu theo Hóa đơn', 'hideWhenValueZero' => true, 'isBold' => true],
                        ['content' => 'Thưởng sản lượng tháng 03.2026', 'rowType' => 'child-value', 'columnKey' => 'Thưởng sản lượng tháng 03.2026', 'hideWhenValueZero' => true, 'isBold' => false],
                        ['content' => '', 'rowType' => 'child-program-loop', 'hideWhenValueZero' => true, 'isBold' => false],
                        ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => true, 'isBold' => true],
                        ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'hideWhenValueZero' => true, 'isBold' => false],
                    ],
                ],
            ])
            ->assertRedirect(route('templates.index'));

        $mailTemplate->refresh();

        $this->assertSame('cam-ca-table', $mailTemplate->structure_json['sections'][2]['type']);
        $this->assertCount(6, $mailTemplate->structure_json['sections'][2]['rows']);
        $this->assertSame('value-row', $mailTemplate->structure_json['sections'][2]['rows'][0]['rowType']);
        $this->assertSame('parent', $mailTemplate->structure_json['sections'][2]['rows'][1]['rowType']);
        $this->assertSame('child-value', $mailTemplate->structure_json['sections'][2]['rows'][2]['rowType']);
        $this->assertSame('child-program-loop', $mailTemplate->structure_json['sections'][2]['rows'][3]['rowType']);
    }

    public function test_user_with_manage_permission_can_persist_key_account_semantic_rows_through_part_route(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template Key Account',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.3-G',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                    ['type' => 'key-account-table', 'label' => 'Table Chiết khấu Key Account', 'kind' => 'table', 'sourceSheet' => 'Key Account', 'rows' => []],
                ],
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncMailTemplate($mailTemplate);

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
                        ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'quantity', 'hideWhenValueZero' => true, 'isBold' => false],
                        ['content' => 'Chiết khấu theo hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Chiết khấu theo hóa đơn', 'valueColumn' => 'amount', 'hideWhenValueZero' => true, 'isBold' => true],
                        ['content' => 'Thưởng doanh thu tháng', 'rowType' => 'child-value', 'columnKey' => 'Thưởng doanh thu tháng 02.2026', 'valueColumn' => 'amount', 'hideWhenValueZero' => true, 'isBold' => false],
                        ['content' => '', 'rowType' => 'child-program-loop', 'hideWhenValueZero' => false, 'isBold' => false],
                        ['content' => 'Cộng', 'rowType' => 'total', 'hideWhenValueZero' => false, 'isBold' => true],
                        ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'hideWhenValueZero' => false, 'isBold' => false],
                    ],
                ],
            ])
            ->assertRedirect(route('templates.index'));

        $mailTemplate->refresh();

        $this->assertSame('key-account-table', $mailTemplate->structure_json['sections'][2]['type']);
        $this->assertCount(6, $mailTemplate->structure_json['sections'][2]['rows']);
        $this->assertSame('value-row', $mailTemplate->structure_json['sections'][2]['rows'][0]['rowType']);
        $this->assertSame('quantity', $mailTemplate->structure_json['sections'][2]['rows'][0]['valueColumn']);
        $this->assertTrue($mailTemplate->structure_json['sections'][2]['rows'][0]['hideWhenValueZero']);
        $this->assertSame('parent', $mailTemplate->structure_json['sections'][2]['rows'][1]['rowType']);
        $this->assertSame('amount', $mailTemplate->structure_json['sections'][2]['rows'][1]['valueColumn']);
        $this->assertTrue($mailTemplate->structure_json['sections'][2]['rows'][1]['hideWhenValueZero']);
        $this->assertSame('child-program-loop', $mailTemplate->structure_json['sections'][2]['rows'][3]['rowType']);
    }
}
