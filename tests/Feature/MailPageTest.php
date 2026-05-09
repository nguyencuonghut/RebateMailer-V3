<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MailPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_mail_page_renders_campaign_creation_form_and_options(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-03',
            'name' => 'Batch tháng 3/2026',
            'original_file_name' => 'thang-3.xlsx',
            'stored_path' => 'imports/tmp/thang-3.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'aggregated',
        ]);

        ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => 'Khách thường',
                'tongHop' => [
                    'email' => 'a@example.com',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 3-2026',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('mail.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mail/Index')
                ->where('title', 'Điều phối gửi mail')
                ->where('canManageCampaigns', true)
                ->has('batchOptions', 1)
                ->where('batchOptions.0.batchId', $batch->id)
                ->where('batchOptions.0.batchCode', 'IMP-2026-03')
                ->has('templateOptions', 1)
                ->where('templateOptions.0.canvasId', $canvas->id)
                ->where('selectedCampaign', null)
                ->where('campaignOptions', [])
                ->where('recipientList', [])
            );
    }
}
