<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ImportsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_imports_page_renders_through_inertia_with_backend_driven_toast_message(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $response = $this->actingAs($user)->get(route('imports.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Imports/Index')
                ->where('title', 'Import dữ liệu')
                ->where('currentSlice.code', '1.7')
                ->where('canManageImports', true)
                ->where('uploadPolicy.acceptedExtension', '.xlsx')
                ->where('analysisPrep.actionLabel', 'Đọc cấu trúc workbook')
                ->where('nextSlice.code', '1.8')
                ->where('toast.summary', 'Khu vực import đã sẵn sàng')
                ->where('toast.detail', 'Bạn có thể tải file Excel lên để hệ thống tự xử lý batch và hiển thị kết quả theo từng tab dữ liệu.')
            );
    }

    public function test_guest_role_can_open_imports_page_when_it_has_imports_view_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->get(route('imports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Imports/Index')
                ->where('canManageImports', false)
            );
    }

    public function test_imports_page_can_hydrate_selected_batch_from_persisted_history(): void
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
        $batchCode = $uploadResponse->json('data.importBatch.batchCode');
        $originalFileName = $uploadResponse->json('data.originalFileName');

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-aggregated'), [
                'importBatchId' => $importBatchId,
            ])
            ->assertOk();

        $batch = ImportBatch::query()
            ->withCount(['sheetRecords', 'aggregatedRecords'])
            ->findOrFail($importBatchId);

        $response = $this->actingAs($user)->get(route('imports.index', [
            'batch' => $importBatchId,
        ]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Imports/Index')
                ->where('activeBatchId', $importBatchId)
                ->where('initialUploadReceipt.importBatch.id', $importBatchId)
                ->where('initialUploadReceipt.importBatch.batchCode', $batchCode)
                ->where('initialUploadReceipt.originalFileName', $originalFileName)
                ->where('initialWorkbookBoundary.contract.version', '1.2-H')
                ->where('initialTongHopPreview.sheetName', 'Tổng hợp')
                ->where('initialKhoanNppPreview.sheetName', 'Khoán NPP')
                ->where('initialCamCaPreview.sheetName', 'Cám cá')
                ->where('initialKeyAccountPreview.sheetName', 'Key Account')
                ->where('initialAggregatePreview.summary.totalCustomerCount', 15)
                ->has('importHistory', 1)
                ->where('importHistory.0.id', $importBatchId)
                ->where('importHistory.0.batchCode', $batchCode)
                ->where('importHistory.0.parsedRecordCount', $batch->sheet_records_count)
                ->where('importHistory.0.aggregatedRecordCount', $batch->aggregated_records_count)
            );
    }
}
