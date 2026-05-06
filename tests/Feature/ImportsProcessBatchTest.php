<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_user_with_manage_permission_can_process_batch_in_one_call(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $uploadedWorkbook = new UploadedFile(
            base_path('data/Data import chuẩn_Final.xlsx'),
            'Data import chuẩn_Final.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        // Upload
        $uploadResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), ['file' => $uploadedWorkbook]);

        $importBatchId = $uploadResponse->json('data.importBatch.id');
        $this->assertNotNull($importBatchId);

        // Gọi process-batch một lần — không cần bấm từng bước
        $processResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), [
                'importBatchId' => $importBatchId,
            ]);

        $processResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('toast.summary', 'Xử lý dữ liệu hoàn tất')
            ->assertJsonPath('data.summary.totalCustomerCount', 15)
            ->assertJsonPath('data.summary.normalCustomerCount', 12)
            ->assertJsonPath('data.summary.keyAccountCustomerCount', 3);

        // Verify DB có data trong cả 3 bảng
        $this->assertDatabaseHas('import_batch_sheet_records', [
            'import_batch_id' => $importBatchId,
            'sheet_name' => 'Tổng hợp',
        ]);

        $this->assertDatabaseHas('import_batch_aggregated_records', [
            'import_batch_id' => $importBatchId,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
        ]);

        $this->assertDatabaseHas('import_batch_aggregated_records', [
            'import_batch_id' => $importBatchId,
            'customer_code' => '11008',
            'customer_type' => 'Key Account',
        ]);

        $this->assertDatabaseHas('import_batches', [
            'id' => $importBatchId,
            'status' => 'aggregated',
        ]);
    }

    public function test_process_batch_is_idempotent_and_reads_from_db_on_second_call(): void
    {
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
        $storedPath = $uploadResponse->json('data.storedPath');

        // Lần 1 — process và persist
        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), ['importBatchId' => $importBatchId])
            ->assertOk();

        // Xóa file tạm
        Storage::disk('local')->delete($storedPath);

        // Lần 2 — đọc từ DB, không cần file
        $secondResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), ['importBatchId' => $importBatchId]);

        $secondResponse
            ->assertOk()
            ->assertJsonPath('data.summary.totalCustomerCount', 15);
    }

    public function test_guest_cannot_process_batch(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), ['importBatchId' => 1])
            ->assertForbidden();
    }

    public function test_process_batch_returns_structured_error_for_invalid_excel_content(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $invalidWorkbook = UploadedFile::fake()->create(
            'broken.xlsx',
            32,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $uploadResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), [
                'file' => $invalidWorkbook,
            ]);

        $importBatchId = $uploadResponse->json('data.importBatch.id');

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.process-batch'), [
                'importBatchId' => $importBatchId,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('toast.summary', 'Xử lý dữ liệu thất bại');
    }
}
