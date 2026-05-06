<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatchJob;
use App\Models\ImportBatch;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsProcessBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_with_manage_permission_can_queue_batch_in_one_call(): void
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
        $this->assertNotNull($importBatchId);

        $processResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), [
                'importBatchId' => $importBatchId,
            ]);

        $processResponse
            ->assertStatus(202)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('toast.summary', 'Đã đưa vào hàng đợi xử lý')
            ->assertJsonPath('data.importBatch.status', 'queued');

        Queue::assertPushed(ProcessImportBatchJob::class, function (ProcessImportBatchJob $job) use ($importBatchId): bool {
            return $job->importBatchId === $importBatchId;
        });

        $this->assertDatabaseHas('import_batches', [
            'id' => $importBatchId,
            'status' => 'queued',
        ]);
    }

    public function test_process_batch_does_not_dispatch_duplicate_job_when_batch_is_already_processing(): void
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

        $batch = ImportBatch::query()->findOrFail($uploadResponse->json('data.importBatch.id'));
        $batch->forceFill(['status' => 'processing'])->save();

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), ['importBatchId' => $batch->id])
            ->assertStatus(202)
            ->assertJsonPath('toast.summary', 'Batch đang được xử lý')
            ->assertJsonPath('data.importBatch.status', 'processing');

        Queue::assertNothingPushed();
    }

    public function test_guest_cannot_process_batch(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), ['importBatchId' => 1])
            ->assertForbidden();
    }
}
