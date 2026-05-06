<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatchJob;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsBatchStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_batch_status_endpoint_reports_queued_state(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $uploadedWorkbook = new UploadedFile(
            base_path('data/Data import chuẩn_Final.xlsx'),
            'Data import chuẩn_Final.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $uploadResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), ['file' => $uploadedWorkbook]);

        $importBatchId = $uploadResponse->json('data.importBatch.id');

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), ['importBatchId' => $importBatchId])
            ->assertStatus(202);

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->get(route('imports.batch-status', $importBatchId))
            ->assertOk()
            ->assertJsonPath('data.importBatch.status', 'queued')
            ->assertJsonPath('data.lifecycle.isQueued', true)
            ->assertJsonPath('data.lifecycle.isTerminal', false)
            ->assertJsonPath('data.aggregateReady', false)
            ->assertJsonPath('toast', null);
    }

    public function test_batch_status_endpoint_reports_completed_state_after_background_job_finishes(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $uploadedWorkbook = new UploadedFile(
            base_path('data/Data import chuẩn_Final.xlsx'),
            'Data import chuẩn_Final.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $uploadResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), ['file' => $uploadedWorkbook]);

        $importBatchId = $uploadResponse->json('data.importBatch.id');

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), ['importBatchId' => $importBatchId])
            ->assertStatus(202);

        app()->call([new ProcessImportBatchJob($importBatchId), 'handle']);

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->get(route('imports.batch-status', $importBatchId))
            ->assertOk()
            ->assertJsonPath('data.importBatch.status', 'aggregated')
            ->assertJsonPath('data.lifecycle.isCompleted', true)
            ->assertJsonPath('data.lifecycle.isTerminal', true)
            ->assertJsonPath('data.aggregateReady', true)
            ->assertJsonPath('toast.summary', 'Xử lý dữ liệu hoàn tất');
    }

    public function test_batch_status_endpoint_reports_failed_state_after_background_job_fails(): void
    {
        Queue::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $invalidWorkbook = UploadedFile::fake()->create(
            'broken.xlsx',
            32,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $uploadResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), ['file' => $invalidWorkbook]);

        $importBatchId = $uploadResponse->json('data.importBatch.id');

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), ['importBatchId' => $importBatchId])
            ->assertStatus(202);

        try {
            app()->call([new ProcessImportBatchJob($importBatchId), 'handle']);
            $this->fail('Expected queued import batch job to fail for invalid workbook.');
        } catch (\Throwable) {
        }

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->get(route('imports.batch-status', $importBatchId))
            ->assertOk()
            ->assertJsonPath('data.importBatch.status', 'failed')
            ->assertJsonPath('data.lifecycle.isFailed', true)
            ->assertJsonPath('data.lifecycle.isTerminal', true)
            ->assertJsonPath('toast.summary', 'Xử lý dữ liệu thất bại');
    }
}
