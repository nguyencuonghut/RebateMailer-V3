<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\MailTemplateCanvasPart;
use App\Models\TemplatePartVersion;
use App\Models\User;
use App\Services\Templates\EnsureTemplatePartCatalogPersistedService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesCanvasPartBindingUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_canvas_part_binding_route_can_reuse_existing_subject_version_for_current_canvas_without_existing_binding(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $parts = app(EnsureTemplatePartCatalogPersistedService::class)->ensure();
        $subjectPart = $parts['subject'];

        $templateMarch = MailTemplate::query()->create([
            'name' => 'Mẫu gửi mail tháng 3-2026',
            'subject_template' => 'Subject tháng 3',
            'structure_json' => ['sections' => []],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $templateApril = MailTemplate::query()->create([
            'name' => 'Mẫu gửi mail tháng 4-2026',
            'subject_template' => 'Subject tháng 4',
            'structure_json' => ['sections' => []],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $subjectVersionMarch = TemplatePartVersion::query()->create([
            'template_part_id' => $subjectPart->id,
            'version_no' => 1,
            'version_label' => 'Subject v1',
            'text_template' => 'Chủ đề dùng lại từ tháng 3',
            'legacy_mail_template_id' => $templateMarch->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $subjectVersionApril = TemplatePartVersion::query()->create([
            'template_part_id' => $subjectPart->id,
            'version_no' => 2,
            'version_label' => 'Subject v2',
            'text_template' => 'Chủ đề riêng của tháng 4',
            'legacy_mail_template_id' => $templateApril->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $canvasApril = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 4-2026',
            'legacy_mail_template_id' => $templateApril->id,
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('templates.canvas-part-binding.update', $templateApril), [
                'partType' => 'subject',
                'templatePartVersionId' => $subjectVersionMarch->id,
            ])
            ->assertRedirect(route('templates.index'));

        $binding = MailTemplateCanvasPart::query()
            ->where('mail_template_canvas_id', $canvasApril->id)
            ->where('template_part_id', $subjectPart->id)
            ->firstOrFail();

        $this->assertSame($subjectVersionMarch->id, $binding->template_part_version_id);
        $this->assertSame(0, $binding->sort_order);
        $this->assertSame('Chủ đề dùng lại từ tháng 3', $templateApril->fresh()->subject_template);
    }

    public function test_canvas_part_binding_route_rejects_version_from_another_part(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $parts = app(EnsureTemplatePartCatalogPersistedService::class)->ensure();
        $subjectPart = $parts['subject'];
        $greetingPart = $parts['greeting'];

        $template = MailTemplate::query()->create([
            'name' => 'Mẫu gửi mail tháng 4-2026',
            'subject_template' => 'Subject tháng 4',
            'structure_json' => ['sections' => []],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 4-2026',
            'legacy_mail_template_id' => $template->id,
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $subjectVersion = TemplatePartVersion::query()->create([
            'template_part_id' => $subjectPart->id,
            'version_no' => 1,
            'version_label' => 'Subject v1',
            'text_template' => 'Subject hợp lệ',
            'legacy_mail_template_id' => $template->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $greetingVersion = TemplatePartVersion::query()->create([
            'template_part_id' => $greetingPart->id,
            'version_no' => 1,
            'version_label' => 'Greeting v1',
            'text_template' => 'Greeting không hợp lệ cho subject',
            'legacy_mail_template_id' => $template->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailTemplateCanvasPart::query()->create([
            'mail_template_canvas_id' => $canvas->id,
            'template_part_id' => $subjectPart->id,
            'template_part_version_id' => $subjectVersion->id,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->put(route('templates.canvas-part-binding.update', $template), [
                'partType' => 'subject',
                'templatePartVersionId' => $greetingVersion->id,
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors('templatePartVersionId');
    }
}
