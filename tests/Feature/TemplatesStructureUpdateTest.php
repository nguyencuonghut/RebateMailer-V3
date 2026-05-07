<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesStructureUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_manage_permission_can_save_table_rows_and_row_order(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template save rows',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Kính gửi quý khách,'],
                    ['type' => 'tong-hop-table', 'label' => 'Table Chế độ tháng', 'kind' => 'table', 'sourceSheet' => 'Tổng hợp', 'rows' => []],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('templates.structure.update', $mailTemplate), [
            'version' => '2.2-E',
            'sections' => [
                ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Chế độ tháng {{tháng}}'],
                ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Kính gửi quý khách,'],
                [
                    'type' => 'tong-hop-table',
                    'label' => 'Table Chế độ tháng',
                    'kind' => 'table',
                    'sourceSheet' => 'Tổng hợp',
                    'rows' => [
                        ['content' => 'Dòng 2', 'indentLevel' => 1],
                        ['content' => 'Dòng 1', 'indentLevel' => 0],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('templates.index'));

        $mailTemplate->refresh();

        $this->assertSame('2.2-E', $mailTemplate->structure_json['version']);
        $this->assertSame('Dòng 2', $mailTemplate->structure_json['sections'][2]['rows'][0]['content']);
        $this->assertSame(1, $mailTemplate->structure_json['sections'][2]['rows'][0]['indentLevel']);
        $this->assertSame('Dòng 1', $mailTemplate->structure_json['sections'][2]['rows'][1]['content']);
        $this->assertSame(0, $mailTemplate->structure_json['sections'][2]['rows'][1]['indentLevel']);
    }

    public function test_update_structure_requires_manage_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('templates.view');

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template read only',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                ],
            ],
            'is_active' => false,
            'created_by' => $viewer->id,
            'updated_by' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->put(route('templates.structure.update', $mailTemplate), [
                'version' => '2.2-E',
                'sections' => [],
            ])
            ->assertForbidden();
    }

    public function test_update_structure_validates_row_payload(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template invalid rows',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->put(route('templates.structure.update', $mailTemplate), [
                'version' => '2.2-E',
                'sections' => [
                    [
                        'type' => 'tong-hop-table',
                        'label' => 'Table Chế độ tháng',
                        'kind' => 'table',
                        'sourceSheet' => 'Tổng hợp',
                        'rows' => [
                            ['content' => '', 'indentLevel' => 1],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors(['sections.0.rows.0.content']);
    }

    public function test_update_structure_clamps_indent_level_between_zero_and_four(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template clamp indent',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                    ['type' => 'cam-ca-table', 'label' => 'Table Chiết khấu cám cá', 'kind' => 'table', 'sourceSheet' => 'Cám cá', 'rows' => []],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)->put(route('templates.structure.update', $mailTemplate), [
            'version' => '2.2-E',
            'sections' => [
                ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                [
                    'type' => 'cam-ca-table',
                    'label' => 'Table Chiết khấu cám cá',
                    'kind' => 'table',
                    'sourceSheet' => 'Cám cá',
                    'rows' => [
                        ['content' => 'Âm', 'indentLevel' => -3],
                        ['content' => 'Dương', 'indentLevel' => 9],
                    ],
                ],
            ],
        ])->assertRedirect(route('templates.index'));

        $mailTemplate->refresh();

        $this->assertSame(0, $mailTemplate->structure_json['sections'][2]['rows'][0]['indentLevel']);
        $this->assertSame(4, $mailTemplate->structure_json['sections'][2]['rows'][1]['indentLevel']);
    }

    public function test_update_structure_rejects_unknown_variables_in_subject_or_greeting_sections(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template invalid variable contract',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.3-A',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->put(route('templates.structure.update', $mailTemplate), [
                'version' => '2.3-A',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Chế độ tháng {{biến lạ}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Kính gửi {{địa chỉ}} {{biến sai}}'],
                ],
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors([
                'sections.0.content',
                'sections.1.content',
            ]);
    }

    public function test_user_with_manage_permission_can_save_semantic_rows_for_tong_hop_table(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template semantic rows',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.3-D',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                    ['type' => 'tong-hop-table', 'label' => 'Table Chế độ tháng', 'kind' => 'table', 'sourceSheet' => 'Tổng hợp', 'rows' => []],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)->put(route('templates.structure.update', $mailTemplate), [
            'version' => '2.3-D',
            'sections' => [
                ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject'],
                ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                [
                    'type' => 'tong-hop-table',
                    'label' => 'Table Chế độ tháng',
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
                        [
                            'content' => 'Tiền chiết khấu theo Hóa đơn',
                            'rowType' => 'parent',
                            'columnKey' => 'Tiền chiết khấu theo Hóa đơn',
                            'hideWhenValueZero' => false,
                            'isBold' => true,
                        ],
                    ],
                ],
            ],
        ])->assertRedirect(route('templates.index'));

        $mailTemplate->refresh();

        $this->assertSame('data', $mailTemplate->structure_json['sections'][2]['rows'][0]['rowType']);
        $this->assertSame('Tổng sản lượng (gồm cám thủy sản)', $mailTemplate->structure_json['sections'][2]['rows'][0]['columnKey']);
        $this->assertFalse($mailTemplate->structure_json['sections'][2]['rows'][0]['hideWhenValueZero']);
        $this->assertFalse($mailTemplate->structure_json['sections'][2]['rows'][0]['isBold']);
        $this->assertSame('parent', $mailTemplate->structure_json['sections'][2]['rows'][1]['rowType']);
        $this->assertSame('Tiền chiết khấu theo Hóa đơn', $mailTemplate->structure_json['sections'][2]['rows'][1]['columnKey']);
        $this->assertTrue($mailTemplate->structure_json['sections'][2]['rows'][1]['isBold']);
    }
}
