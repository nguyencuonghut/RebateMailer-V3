<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\MailTemplateCanvasPart;
use App\Models\TemplatePart;
use App\Models\TemplatePartVersion;
use App\Models\User;
use App\Services\Templates\EnsureTemplatePartCatalogPersistedService;
use App\Services\Templates\SyncLegacyMailTemplateToCompositionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateCompositionPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_template_part_catalog_is_persisted_with_part_type_active_policy(): void
    {
        $parts = app(EnsureTemplatePartCatalogPersistedService::class)->ensure();

        $this->assertCount(7, $parts);
        $this->assertSame(1, $parts['subject']->max_active_versions);
        $this->assertSame(2, $parts['greeting']->max_active_versions);
        $this->assertSame('Tổng hợp', $parts['tong-hop-table']->source_sheet);
        $this->assertSame('composite', $parts['representative-signature']->kind);

        $this->assertDatabaseHas('template_parts', [
            'type' => 'subject',
            'code' => 'subject',
            'max_active_versions' => 1,
        ]);

        $this->assertDatabaseHas('template_parts', [
            'type' => 'greeting',
            'code' => 'greeting',
            'max_active_versions' => 2,
        ]);

        $this->assertDatabaseHas('template_parts', [
            'type' => 'representative-signature',
            'code' => 'representative-signature',
            'kind' => 'composite',
            'max_active_versions' => 1,
        ]);
    }

    public function test_sync_service_can_migrate_legacy_mail_template_into_part_versions_and_canvas_bindings(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $legacyTemplate = MailTemplate::query()->create([
            'name' => 'Template legacy tháng 02',
            'subject_template' => 'Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}',
            'structure_json' => [
                'version' => '2.3-D',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi quý khách hàng {{mã & tên khách hàng}}'],
                    [
                        'type' => 'tong-hop-table',
                        'label' => 'Table Chế độ tháng',
                        'sourceSheet' => 'Tổng hợp',
                        'rows' => [
                            [
                                'rowType' => 'parent',
                                'content' => 'Tiền chiết khấu theo Hóa đơn',
                                'columnKey' => 'Tiền chiết khấu theo Hóa đơn',
                                'hideWhenValueZero' => false,
                                'isBold' => true,
                            ],
                        ],
                    ],
                    [
                        'type' => 'cam-ca-table',
                        'label' => 'Table Chiết khấu cám cá',
                        'sourceSheet' => 'Cám cá',
                        'rows' => [],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $canvas = app(SyncLegacyMailTemplateToCompositionService::class)->syncMailTemplate($legacyTemplate);

        $this->assertInstanceOf(MailTemplateCanvas::class, $canvas);
        $this->assertSame($legacyTemplate->id, $canvas->legacy_mail_template_id);
        $this->assertTrue($canvas->is_active);

        $this->assertDatabaseHas('mail_template_canvases', [
            'id' => $canvas->id,
            'name' => 'Template legacy tháng 02',
            'legacy_mail_template_id' => $legacyTemplate->id,
            'is_active' => true,
        ]);

        $this->assertSame(4, TemplatePartVersion::query()->count());
        $this->assertSame(4, MailTemplateCanvasPart::query()->where('mail_template_canvas_id', $canvas->id)->count());

        $subjectPart = TemplatePart::query()->where('type', 'subject')->firstOrFail();
        $greetingPart = TemplatePart::query()->where('type', 'greeting')->firstOrFail();
        $tongHopPart = TemplatePart::query()->where('type', 'tong-hop-table')->firstOrFail();

        $subjectVersion = TemplatePartVersion::query()
            ->where('template_part_id', $subjectPart->id)
            ->where('legacy_mail_template_id', $legacyTemplate->id)
            ->firstOrFail();

        $greetingVersion = TemplatePartVersion::query()
            ->where('template_part_id', $greetingPart->id)
            ->where('legacy_mail_template_id', $legacyTemplate->id)
            ->firstOrFail();

        $tongHopVersion = TemplatePartVersion::query()
            ->where('template_part_id', $tongHopPart->id)
            ->where('legacy_mail_template_id', $legacyTemplate->id)
            ->firstOrFail();

        $this->assertSame(1, $subjectVersion->version_no);
        $this->assertSame('Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}', $subjectVersion->text_template);
        $this->assertTrue($subjectVersion->is_active);
        $this->assertSame('Kính gửi quý khách hàng {{mã & tên khách hàng}}', $greetingVersion->text_template);
        $this->assertSame('tong-hop-table', $tongHopVersion->structure_json['type']);
        $this->assertSame('Tiền chiết khấu theo Hóa đơn', $tongHopVersion->structure_json['rows'][0]['content']);
        $this->assertStringContainsString('Kính gửi quý khách hàng', $greetingVersion->text_template ?? '');
        $this->assertStringContainsString('Tiền chiết khấu theo Hóa đơn', $tongHopVersion->getRawOriginal('structure_json'));
        $this->assertStringNotContainsString('\\u', $tongHopVersion->getRawOriginal('structure_json'));
    }
}
