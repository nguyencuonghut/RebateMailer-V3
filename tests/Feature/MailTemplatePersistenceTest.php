<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\User;
use App\Services\Templates\ActivateMailTemplateService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailTemplatePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_mail_template_schema_can_persist_minimal_metadata_and_structure_json(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template chiết khấu tháng mặc định',
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

        $this->assertDatabaseHas('mail_templates', [
            'id' => $mailTemplate->id,
            'name' => 'Template chiết khấu tháng mặc định',
            'subject_template' => 'Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertSame(
            [
                'sections' => [
                    ['type' => 'subject'],
                    ['type' => 'greeting'],
                ],
            ],
            $mailTemplate->structure_json,
        );
    }

    public function test_structure_json_is_persisted_without_escaping_vietnamese_characters(): void
    {
        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template tiếng Việt',
            'subject_template' => 'Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}',
            'structure_json' => [
                'sections' => [
                    [
                        'type' => 'greeting',
                        'content' => 'Kính gửi quý khách hàng {{mã & tên khách hàng}}',
                    ],
                    [
                        'type' => 'table',
                        'title' => 'Chiết khấu cám cá',
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('Kính gửi quý khách hàng', $mailTemplate->getRawOriginal('structure_json'));
        $this->assertStringContainsString('Chiết khấu cám cá', $mailTemplate->getRawOriginal('structure_json'));
        $this->assertStringNotContainsString('\\u', $mailTemplate->getRawOriginal('structure_json'));
    }

    public function test_activation_service_keeps_only_one_active_template(): void
    {
        $firstTemplate = MailTemplate::query()->create([
            'name' => 'Template 1',
            'subject_template' => 'Subject 1',
            'structure_json' => ['sections' => []],
            'is_active' => true,
        ]);

        $secondTemplate = MailTemplate::query()->create([
            'name' => 'Template 2',
            'subject_template' => 'Subject 2',
            'structure_json' => ['sections' => []],
            'is_active' => false,
        ]);

        $service = app(ActivateMailTemplateService::class);
        $activatedTemplate = $service->activate($secondTemplate);

        $this->assertTrue($activatedTemplate->is_active);
        $this->assertFalse($firstTemplate->fresh()->is_active);
        $this->assertTrue($secondTemplate->fresh()->is_active);
        $this->assertSame(1, MailTemplate::query()->where('is_active', true)->count());
    }
}
