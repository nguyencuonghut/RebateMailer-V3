<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\TemplatePart;
use App\Models\User;
use App\Services\Templates\SyncLegacyMailTemplateToCompositionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesCanvasCompositionUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_manage_permission_can_save_canvas_composition_by_part_types(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template composition',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Kính gửi quý khách'],
                ],
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncMailTemplate($mailTemplate);

        $this->actingAs($user)
            ->put(route('templates.canvas.update', $mailTemplate), [
                'partTypes' => ['subject', 'greeting', 'tong-hop-table'],
            ])
            ->assertRedirect(route('templates.index'));

        $canvas = MailTemplateCanvas::query()->where('legacy_mail_template_id', $mailTemplate->id)->firstOrFail();
        $partTypes = $canvas->partBindings()
            ->with('templatePart')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($binding) => $binding->templatePart?->type)
            ->all();

        $this->assertSame(['subject', 'greeting', 'tong-hop-table'], $partTypes);
        $this->assertSame('tong-hop-table', $mailTemplate->fresh()->structure_json['sections'][2]['type']);
        $this->assertSame([], $mailTemplate->fresh()->structure_json['sections'][2]['rows']);
    }

    public function test_canvas_composition_update_requires_manage_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('templates.view');

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template read only',
            'subject_template' => 'Subject',
            'structure_json' => ['version' => '2.2-E', 'sections' => []],
        ]);

        $this->actingAs($viewer)
            ->put(route('templates.canvas.update', $mailTemplate), [
                'partTypes' => ['subject', 'greeting'],
            ])
            ->assertForbidden();
    }
}
