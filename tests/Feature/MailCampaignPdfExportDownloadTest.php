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
        Storage::fake('local');
    }

    public function test_user_can_download_completed_pdf_export_file(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $export] = $this->makeCompletedExportFixture($user);

        Storage::disk('local')->put($export->file_path, 'fake pdf content');

        $response = $this->actingAs($user)
            ->get(route('mail.campaigns.exports.pdf.download', [$campaign, $export]));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename='.$export->file_name);
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignExport}
     */
    private function makeCompletedExportFixture(User $user): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-PDF-DL-2026-05',
            'name' => 'Batch PDF tải xuống',
            'original_file_name' => 'thang-5.xlsx',
            'stored_path' => 'imports/tmp/thang-5.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Canvas PDF tải xuống',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch PDF tải xuống',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'status' => 'completed',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $export = MailCampaignExport::query()->create([
            'mail_campaign_id' => $campaign->id,
            'export_type' => 'pdf',
            'status' => 'completed',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'completed_at' => now(),
            'file_disk' => 'local',
            'file_path' => 'mail-exports/pdf/export-test.pdf',
            'file_name' => 'export-test.pdf',
            'total_recipients' => 1,
            'exported_recipients' => 1,
        ]);

        return [$campaign, $export];
    }
}
