<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_manage_permission_can_create_basic_mail_template(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $response = $this->actingAs($user)->post(route('templates.store'), [
            'name' => 'Template chiết khấu tháng 02',
            'subject_template' => 'Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}',
            'greeting_template' => 'Kính gửi quý khách hàng {{mã & tên khách hàng}},',
        ]);

        $response->assertRedirect(route('templates.index'));

        $this->assertDatabaseHas('mail_templates', [
            'name' => 'Template chiết khấu tháng 02',
            'subject_template' => 'Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}',
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $mailTemplate = MailTemplate::query()->firstOrFail();

        $this->assertSame('2.2-E', $mailTemplate->structure_json['version']);
        $this->assertCount(2, $mailTemplate->structure_json['sections']);
        $this->assertSame('subject', $mailTemplate->structure_json['sections'][0]['type']);
        $this->assertSame('Subject', $mailTemplate->structure_json['sections'][0]['label']);
        $this->assertSame('Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}', $mailTemplate->structure_json['sections'][0]['content']);
        $this->assertSame('greeting', $mailTemplate->structure_json['sections'][1]['type']);
        $this->assertSame('Lời chào', $mailTemplate->structure_json['sections'][1]['label']);
        $this->assertSame('Kính gửi quý khách hàng {{mã & tên khách hàng}},', $mailTemplate->structure_json['sections'][1]['content']);
    }

    public function test_store_template_requires_manage_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('templates.view');

        $this->actingAs($viewer)
            ->post(route('templates.store'), [
                'name' => 'Không được tạo',
                'subject_template' => 'Subject',
                'greeting_template' => 'Greeting',
            ])
            ->assertForbidden();
    }

    public function test_store_template_validates_required_fields(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->post(route('templates.store'), [
                'name' => '',
                'subject_template' => '',
                'greeting_template' => '',
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors([
                'name',
                'subject_template',
                'greeting_template',
            ]);
    }

    public function test_store_template_rejects_unknown_variables_in_subject_or_greeting(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->post(route('templates.store'), [
                'name' => 'Template sai biến',
                'subject_template' => 'Chế độ tháng {{biến không hợp lệ}}',
                'greeting_template' => 'Kính gửi {{mã & tên khách hàng}} {{biến lạ}}',
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors([
                'subject_template',
                'greeting_template',
            ]);
    }
}
