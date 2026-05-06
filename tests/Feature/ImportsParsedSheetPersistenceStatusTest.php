<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsParsedSheetPersistenceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_batch_status_becomes_parsed_complete_after_all_present_sheets_are_persisted(): void
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

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.analyze-workbook'), [
                'importBatchId' => $importBatchId,
            ])
            ->assertOk();

        foreach ([
            'imports.preview-tong-hop',
            'imports.preview-khoan-npp',
            'imports.preview-cam-ca',
            'imports.preview-key-account',
        ] as $routeName) {
            $this->actingAs($user)
                ->withHeader('Accept', 'application/json')
                ->post(route($routeName), [
                    'importBatchId' => $importBatchId,
                ])
                ->assertOk();
        }

        $batch = ImportBatch::query()->findOrFail($importBatchId);
        $persistedRecordCount = $batch->sheetRecords()->count();
        $metadataRecordCount = array_sum(array_map(
            static fn (array $preview): int => (int) ($preview['recordCount'] ?? 0),
            $batch->workbook_summary['sheetPreviews'] ?? [],
        ));

        $this->assertSame('parsed_complete', $batch->status);
        $this->assertCount(4, $batch->workbook_summary['sheetPreviews'] ?? []);
        $this->assertGreaterThan(0, $persistedRecordCount);
        $this->assertSame($metadataRecordCount, $persistedRecordCount);
    }
}
