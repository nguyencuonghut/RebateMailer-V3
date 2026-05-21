<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\MailCampaign;
use App\Models\MailCampaignExport;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MailCampaignPdfExportDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_with_mail_view_permission_can_download_completed_pdf_export(): void
    {
        Storage::fake('local');

        $user = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        [$campaign, $export] = $this->makeCompletedExportFixture($user);

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.pdf-exports.download', [
                'mailCampaign' => $campaign->id,
                'mailCampaignExport' => $export->id,
            ]));

        $response->assertOk();
        $response->assertDownload('mail-campaign-'.$campaign->id.'.pdf');
    }

    public function test_download_is_rejected_when_pdf_export_is_not_completed(): void
    {
        Storage::fake('local');

        $user = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        [$campaign, $export] = $this->makeCompletedExportFixture($user, status: 'processing');

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.pdf-exports.download', [
                'mailCampaign' => $campaign->id,
                'mailCampaignExport' => $export->id,
            ]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));
        $response->assertSessionHasErrors(['download']);
    }

    public function test_download_is_rejected_when_completed_export_file_is_missing_from_storage(): void
    {
        Storage::fake('local');

        $user = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        [$campaign, $export] = $this->makeCompletedExportFixture($user, status: 'completed', writeFile: false);

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.pdf-exports.download', [
                'mailCampaign' => $campaign->id,
                'mailCampaignExport' => $export->id,
            ]));

        $response->assertRedirect(route('mail.index', ['campaign' => $campaign->id]));
        $response->assertSessionHasErrors(['download']);
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignExport}
     */
    private function makeCompletedExportFixture(User $user, string $status = 'completed', bool $writeFile = true): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-08',
            'name' => 'Batch tháng 8/2026',
            'original_file_name' => 'thang-8.xlsx',
            'stored_path' => 'imports/tmp/thang-8.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu PDF tháng 8',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch PDF tháng 8',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Download PDF',
            'status' => 'completed',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if ($writeFile) {
            Storage::disk('local')->put('mail-exports/pdf/mail-campaign-'.$campaign->id.'.pdf', '%PDF-1.7 fake');
        }

        $export = MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'sent-mails-pdf',
            'status' => $status,
            'requested_by' => $user->id,
            'requested_at' => now()->subMinute(),
            'completed_at' => $status === 'completed' ? now() : null,
            'file_name' => 'mail-campaign-'.$campaign->id.'.pdf',
            'file_path' => 'mail-exports/pdf/mail-campaign-'.$campaign->id.'.pdf',
            'total_recipients' => 2,
            'exported_recipients' => $status === 'completed' ? 2 : 1,
        ]);

        return [$campaign, $export];
    }
}
