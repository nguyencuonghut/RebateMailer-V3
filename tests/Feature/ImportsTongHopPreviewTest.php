<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsTongHopPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_with_manage_permission_can_preview_tong_hop_sheet(): void
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
            ->post(route('imports.upload'), [
                'file' => $uploadedWorkbook,
            ]);

        $importBatchId = $uploadResponse->json('data.importBatch.id');

        $previewResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-tong-hop'), [
                'importBatchId' => $importBatchId,
            ]);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Đã parse sheet Tổng hợp thành công.')
            ->assertJsonPath('toast.summary', 'Preview sheet Tổng hợp đã sẵn sàng')
            ->assertJsonPath('data.sheetName', 'Tổng hợp')
            ->assertJsonPath('data.fixedHeaders.0', 'STT')
            ->assertJsonPath('data.fixedHeaders.1', 'Tháng');

        $payload = $previewResponse->json('data');

        $this->assertGreaterThan(0, $payload['recordCount']);
        $this->assertNotEmpty($payload['dynamicHeaders']);
        $this->assertNotEmpty($payload['records'][0]['month']);
        $this->assertNotEmpty($payload['records'][0]['customerCode']);
        $this->assertNotEmpty($payload['records'][0]['customerFullName']);
        $this->assertNotEmpty($payload['records'][0]['dynamicItems']);

        $this->assertDatabaseHas('import_batch_sheet_records', [
            'import_batch_id' => $importBatchId,
            'sheet_name' => 'Tổng hợp',
            'customer_code' => $payload['records'][0]['customerCode'],
            'customer_type_inferred' => 'Khách thường',
        ]);

        $batch = ImportBatch::query()->findOrFail($importBatchId);
        $this->assertSame('parsed_partial', $batch->status);
        $this->assertIsArray($batch->workbook_summary['sheetPreviews']['Tổng hợp'] ?? null);
    }

    public function test_tong_hop_preview_can_be_read_back_from_db_after_temporary_file_is_deleted(): void
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
            ->post(route('imports.upload'), [
                'file' => $uploadedWorkbook,
            ]);

        $importBatchId = $uploadResponse->json('data.importBatch.id');
        $storedPath = $uploadResponse->json('data.storedPath');

        $firstPreviewResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-tong-hop'), [
                'importBatchId' => $importBatchId,
            ]);

        $firstPreviewResponse->assertOk();
        Storage::disk('local')->delete($storedPath);

        $secondPreviewResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-tong-hop'), [
                'importBatchId' => $importBatchId,
            ]);

        $secondPreviewResponse
            ->assertOk()
            ->assertJsonPath('data.recordCount', $firstPreviewResponse->json('data.recordCount'))
            ->assertJsonPath('data.records.0.customerCode', $firstPreviewResponse->json('data.records.0.customerCode'));
    }

    public function test_guest_cannot_preview_tong_hop_without_manage_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-tong-hop'), [
                'importBatchId' => 999999,
            ])
            ->assertForbidden();
    }

    public function test_preview_tong_hop_returns_structured_error_for_invalid_excel_content(): void
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

        $previewResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-tong-hop'), [
                'importBatchId' => $importBatchId,
            ]);

        $previewResponse
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('toast.summary', 'Không thể preview sheet Tổng hợp');
    }
}
