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
            ->assertJsonPath('toast.summary', 'Xử lý dữ liệu hoàn tất')
            ->assertJsonPath('toast.detail', 'Đợt nhập '.$uploadResponse->json('data.importBatch.batchCode').' đã xử lý xong. Hệ thống đã parse 4 sheet hợp lệ và hợp nhất 15 khách hàng. Trang sẽ nạp lại kết quả đã lưu trong hệ thống.');
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
            ->assertJsonPath('toast.summary', 'Xử lý dữ liệu thất bại')
            ->assertJsonPath('toast.detail', 'Đợt nhập '.$uploadResponse->json('data.importBatch.batchCode').' thất bại trong quá trình xử lý nền. Không tìm thấy cấu trúc workbook trong file Excel.');
    }

    public function test_batch_status_endpoint_hides_technical_error_details_from_user_facing_toast(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-ERR-0001',
            'original_file_name' => 'broken.xlsx',
            'stored_path' => 'imports/tmp/broken.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'failed',
            'workbook_summary' => [
                'processingError' => [
                    'message' => 'Hệ thống không thể xử lý file Excel của đợt nhập này. Vui lòng kiểm tra lại cấu trúc 4 sheet import và dữ liệu đầu vào trước khi thử lại.',
                    'technicalMessage' => 'foreach() argument must be of type array|object, null given',
                    'failedAt' => now()->toIso8601String(),
                ],
            ],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->get(route('imports.batch-status', $importBatch))
            ->assertOk()
            ->assertJsonPath('data.importBatch.status', 'failed')
            ->assertJsonPath('data.processingError', 'Hệ thống không thể xử lý file Excel của đợt nhập này. Vui lòng kiểm tra lại cấu trúc 4 sheet import và dữ liệu đầu vào trước khi thử lại.')
            ->assertJsonPath('toast.summary', 'Xử lý dữ liệu thất bại')
            ->assertJsonPath('toast.detail', 'Đợt nhập IMP-ERR-0001 thất bại trong quá trình xử lý nền. Hệ thống không thể xử lý file Excel của đợt nhập này. Vui lòng kiểm tra lại cấu trúc 4 sheet import và dữ liệu đầu vào trước khi thử lại.')
            ->assertJsonMissingPath('data.technicalMessage');
    }

    public function test_batch_status_endpoint_sanitizes_legacy_failed_batch_message_that_contains_technical_text(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-ERR-0002',
            'original_file_name' => 'broken.xlsx',
            'stored_path' => 'imports/tmp/broken-legacy.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'failed',
            'workbook_summary' => [
                'processingError' => [
                    'message' => 'foreach() argument must be of type array|object, null given',
                    'failedAt' => now()->toIso8601String(),
                ],
            ],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->get(route('imports.batch-status', $importBatch))
            ->assertOk()
            ->assertJsonPath('data.processingError', 'Hệ thống không thể xử lý file Excel của đợt nhập này. Vui lòng kiểm tra lại cấu trúc 4 sheet import và dữ liệu đầu vào trước khi thử lại.')
            ->assertJsonPath('toast.detail', 'Đợt nhập IMP-ERR-0002 thất bại trong quá trình xử lý nền. Hệ thống không thể xử lý file Excel của đợt nhập này. Vui lòng kiểm tra lại cấu trúc 4 sheet import và dữ liệu đầu vào trước khi thử lại.');
    }
}
