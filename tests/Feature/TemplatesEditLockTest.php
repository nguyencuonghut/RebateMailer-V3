<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplate;
use App\Models\TemplatePartVersion;
use App\Models\User;
use App\Services\Templates\EnsureTemplatePartCatalogPersistedService;
use App\Services\Templates\SyncLegacyMailTemplateToCompositionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplatesEditLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_templates_page_marks_selected_template_as_write_locked_after_it_has_been_used_to_send_mail(): void
    {
        [$user] = $this->createSentTemplateFixture();

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Templates/Index')
                ->where('selectedTemplateWriteLocked', true)
                ->where('selectedTemplateWriteLockReason', fn (string $value): bool => str_contains($value, 'không thể chỉnh sửa nữa'))
            );
    }

    public function test_locked_template_rejects_all_edit_routes_once_it_has_been_used_to_send_mail(): void
    {
        [$user, $mailTemplate] = $this->createSentTemplateFixture();
        $parts = app(EnsureTemplatePartCatalogPersistedService::class)->ensure();
        $subjectPart = $parts['subject'];
        $subjectVersion = TemplatePartVersion::query()
            ->where('template_part_id', $subjectPart->id)
            ->where('legacy_mail_template_id', $mailTemplate->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->post(route('templates.sections.store', $mailTemplate), [
                'type' => 'tong-hop-table',
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors('template');

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->put(route('templates.canvas.update', $mailTemplate), [
                'partTypes' => ['subject', 'greeting', 'tong-hop-table'],
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors('template');

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->put(route('templates.canvas-part-binding.update', $mailTemplate), [
                'partType' => 'subject',
                'templatePartVersionId' => $subjectVersion->id,
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors('template');

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->put(route('templates.parts.update', $mailTemplate), [
                'partType' => 'subject',
                'content' => 'Subject mới sau khi đã gửi',
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors('template');

        $this->actingAs($user)
            ->from(route('templates.index'))
            ->put(route('templates.structure.update', $mailTemplate), [
                'version' => '2.3-Z',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Subject mới'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Xin chào'],
                ],
            ])
            ->assertRedirect(route('templates.index'))
            ->assertSessionHasErrors('template');
    }

    /**
     * @return array{0: User, 1: MailTemplate}
     */
    private function createSentTemplateFixture(): array
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Template đã gửi thật',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'version' => '2.3-G',
                'sections' => [
                    ['type' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'content' => 'Chế độ tháng {{tháng}}'],
                    ['type' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'content' => 'Kính gửi quý khách'],
                ],
            ],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SyncLegacyMailTemplateToCompositionService::class)->syncMailTemplate($mailTemplate);

        $canvas = \App\Models\MailTemplateCanvas::query()
            ->where('legacy_mail_template_id', $mailTemplate->id)
            ->firstOrFail();

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TEMPLATE-LOCK',
            'original_file_name' => 'lock.xlsx',
            'stored_path' => 'imports/tmp/lock.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $aggregatedRecord = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => [
                    'month' => '03.2026',
                    'customerFullName' => '90300 - Công ty A',
                ],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch đã gửi dùng template này',
            'import_batch_id' => $importBatch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'completed',
            'dispatch_trigger' => 'manual',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $aggregatedRecord->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'a@example.com',
            'delivery_status' => 'sent',
            'attempts_count' => 1,
            'sent_at' => now(),
            'sent_subject_snapshot' => 'Chế độ tháng 03.2026',
            'sent_html_snapshot' => '<!DOCTYPE html><html lang="vi"><body><div>Đã gửi</div></body></html>',
            'sent_signature_snapshot' => [
                'title' => 'Đại diện công ty',
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
            'snapshot_version' => 1,
        ]);

        return [$user, $mailTemplate];
    }
}
