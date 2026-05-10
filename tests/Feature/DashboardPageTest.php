<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dashboard_page_renders_real_operational_data(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-05',
            'name' => 'Batch tháng 5/2026',
            'original_file_name' => 'thang-5.xlsx',
            'stored_path' => 'imports/tmp/thang-5.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'tongHop' => ['email' => 'a@example.com'],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 5-2026',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 5',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Gửi thử',
            'status' => 'dispatching',
            'dispatch_trigger' => 'manual',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => 'Khách thường',
            'recipient_email' => 'a@example.com',
            'delivery_status' => 'failed',
            'attempts_count' => 1,
            'latest_error_message' => 'SMTP timeout',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('title', 'Bảng điều khiển vận hành')
                ->where('overviewCards.0.value', 1)
                ->where('overviewCards.1.value', 1)
                ->where('overviewCards.2.value', 1)
                ->where('overviewCards.3.value', 0)
                ->where('deliveryHealth.failedRecipients', 1)
                ->where('deliveryHealth.failureRatePercent', 100)
                ->where('quickActions.0.routeName', 'imports.index')
                ->where('quickActions.1.routeName', 'templates.index')
                ->where('quickActions.2.routeName', 'mail.index')
                ->where('recentImportBatches.0.batchCode', 'IMP-2026-05')
                ->where('recentImportBatches.0.aggregatedRecordCount', 1)
                ->where('recentCampaigns.0.name', 'Chiến dịch tháng 5')
                ->where('recentCampaigns.0.recipientCount', 1)
                ->where('recentCampaigns.0.status', 'dispatching')
            );
    }

    public function test_dashboard_quick_actions_follow_user_permissions(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('quickActions', 2)
                ->where('quickActions.0.routeName', 'imports.index')
                ->where('quickActions.1.routeName', 'mail.index')
            );
    }
}
