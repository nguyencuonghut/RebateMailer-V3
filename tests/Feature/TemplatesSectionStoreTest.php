<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesSectionStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_manage_permission_can_append_missing_table_section_to_template(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template cần thêm section',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Kính gửi quý khách,'],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('templates.sections.store', $mailTemplate), [
            'type' => 'tong-hop-table',
        ]);

        $response->assertRedirect(route('templates.index'));

        $mailTemplate->refresh();

        $this->assertSame('2.0-R5', $mailTemplate->structure_json['version']);
        $this->assertCount(3, $mailTemplate->structure_json['sections']);
        $this->assertSame('tong-hop-table', $mailTemplate->structure_json['sections'][2]['type']);
        $this->assertSame('Table Chế độ tháng', $mailTemplate->structure_json['sections'][2]['label']);
        $this->assertSame('Tổng hợp', $mailTemplate->structure_json['sections'][2]['sourceSheet']);
        $this->assertSame([], $mailTemplate->structure_json['sections'][2]['rows']);
    }

    public function test_append_section_does_not_duplicate_existing_section_type(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template đã có section',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Xin chào'],
                    ['type' => 'cam-ca-table', 'label' => 'Table Chiết khấu cám cá', 'sourceSheet' => 'Cám cá'],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('templates.sections.store', $mailTemplate), [
            'type' => 'cam-ca-table',
        ])->assertRedirect(route('templates.index'));

        $mailTemplate->refresh();

        $sections = collect($mailTemplate->structure_json['sections']);

        $this->assertCount(3, $sections);
        $this->assertSame(1, $sections->where('type', 'cam-ca-table')->count());
    }

    public function test_append_section_requires_manage_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('templates.view');

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template viewer không sửa được',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Xin chào'],
                ],
            ],
            'is_active' => false,
            'created_by' => $viewer->id,
            'updated_by' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->post(route('templates.sections.store', $mailTemplate), [
                'type' => 'khoan-npp-table',
            ])
            ->assertForbidden();
    }

    public function test_append_section_validates_allowed_type(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template validate section type',
            'subject_template' => 'Subject',
            'structure_json' => [
                'version' => '2.2-E',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'content' => 'Subject'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'content' => 'Xin chào'],
                ],
            ],
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->post(route('templates.sections.store', $mailTemplate), [
                'type' => 'generic-table',
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors(['type']);
    }
}
