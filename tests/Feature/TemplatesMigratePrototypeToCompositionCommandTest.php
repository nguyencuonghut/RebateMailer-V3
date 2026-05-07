<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\TemplatePartVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesMigratePrototypeToCompositionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_migrate_legacy_prototype_templates_into_composition_tables(): void
    {
        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template prototype cũ',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi {{mã & tên khách hàng}}'],
                    ['type' => 'tong-hop-table', 'label' => 'Table Chế độ tháng', 'sourceSheet' => 'Tổng hợp', 'rows' => [
                        ['content' => 'Tổng sản lượng', 'rowType' => 'data'],
                    ]],
                ],
            ],
        ]);

        $this->artisan('templates:migrate-prototype-to-composition')
            ->expectsOutput('Đã migrate prototype template sang composition model.')
            ->assertSuccessful();

        $canvas = MailTemplateCanvas::query()->where('legacy_mail_template_id', $mailTemplate->id)->firstOrFail();

        $this->assertSame('Template prototype cũ', $canvas->name);
        $this->assertSame(3, $canvas->partBindings()->count());
        $this->assertSame(3, TemplatePartVersion::query()->where('legacy_mail_template_id', $mailTemplate->id)->count());
    }
}
