<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use App\Services\Templates\ActivateMailTemplateCanvasService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesActivateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_activation_service_keeps_only_one_active_canvas_and_syncs_legacy_template(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $firstTemplate = MailTemplate::query()->create([
            'name' => 'Canvas 1',
            'subject_template' => 'Subject 1',
            'structure_json' => ['sections' => []],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $secondTemplate = MailTemplate::query()->create([
            'name' => 'Canvas 2',
            'subject_template' => 'Subject 2',
            'structure_json' => ['sections' => []],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $firstCanvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas 1',
            'legacy_mail_template_id' => $firstTemplate->id,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $secondCanvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas 2',
            'legacy_mail_template_id' => $secondTemplate->id,
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $activatedCanvas = app(ActivateMailTemplateCanvasService::class)->activate($secondTemplate, $user);

        $this->assertSame($secondCanvas->id, $activatedCanvas->id);
        $this->assertFalse($firstCanvas->fresh()->is_active);
        $this->assertTrue($secondCanvas->fresh()->is_active);
        $this->assertFalse($firstTemplate->fresh()->is_active);
        $this->assertTrue($secondTemplate->fresh()->is_active);
        $this->assertSame(1, MailTemplateCanvas::query()->where('is_active', true)->count());
        $this->assertSame(1, MailTemplate::query()->where('is_active', true)->count());
    }

    public function test_templates_activate_route_switches_active_canvas(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $firstTemplate = MailTemplate::query()->create([
            'name' => 'Canvas 1',
            'subject_template' => 'Subject 1',
            'structure_json' => ['sections' => []],
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $secondTemplate = MailTemplate::query()->create([
            'name' => 'Canvas 2',
            'subject_template' => 'Subject 2',
            'structure_json' => ['sections' => []],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailTemplateCanvas::query()->create([
            'name' => 'Canvas 1',
            'legacy_mail_template_id' => $firstTemplate->id,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailTemplateCanvas::query()->create([
            'name' => 'Canvas 2',
            'legacy_mail_template_id' => $secondTemplate->id,
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('templates.activate', $secondTemplate))
            ->assertRedirect(route('templates.index'));

        $this->assertFalse($firstTemplate->fresh()->is_active);
        $this->assertTrue($secondTemplate->fresh()->is_active);
        $this->assertDatabaseHas('mail_template_canvases', [
            'legacy_mail_template_id' => $secondTemplate->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('mail_template_canvases', [
            'legacy_mail_template_id' => $firstTemplate->id,
            'is_active' => false,
        ]);
    }

    public function test_templates_activate_route_requires_manage_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        $owner = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $template = MailTemplate::query()->create([
            'name' => 'Canvas 1',
            'subject_template' => 'Subject 1',
            'structure_json' => ['sections' => []],
            'is_active' => false,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        MailTemplateCanvas::query()->create([
            'name' => 'Canvas 1',
            'legacy_mail_template_id' => $template->id,
            'is_active' => false,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $this->actingAs($guest)
            ->put(route('templates.activate', $template))
            ->assertForbidden();
    }
}
