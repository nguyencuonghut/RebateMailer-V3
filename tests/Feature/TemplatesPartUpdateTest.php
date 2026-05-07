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
}
