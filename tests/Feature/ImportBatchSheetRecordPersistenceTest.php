<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Services\Imports\PersistImportBatchSheetRecordsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportBatchSheetRecordPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_persist_parsed_records_for_all_four_import_sheets(): void
    {
        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TEST-0001',
            'original_file_name' => 'Data import chuẩn_Final.xlsx',
            'stored_path' => 'imports/tmp/test.xlsx',
            'status' => 'workbook_analyzed',
            'started_at' => now(),
        ]);

        $service = app(PersistImportBatchSheetRecordsService::class);

        $service->replaceForSheet($importBatch, 'Tổng hợp', [
            [
                'customerCode' => '90300',
                'rowNumber' => 2,
                'parsedPayload' => ['customerCode' => '90300', 'grandTotal' => '20780500'],
            ],
        ]);

        $service->replaceForSheet($importBatch, 'Khoán NPP', [
            [
                'customerCode' => '90300',
                'rowNumber' => 2,
                'parsedPayload' => ['customerCode' => '90300', 'programItems' => [['programIndex' => 1]]],
            ],
        ]);

        $service->replaceForSheet($importBatch, 'Cám cá', [
            [
                'customerCode' => '16068',
                'rowNumber' => 2,
                'parsedPayload' => ['customerCode' => '16068', 'grandTotal' => '212465000'],
            ],
        ]);

        $service->replaceForSheet($importBatch, 'Key Account', [
            [
                'customerCode' => '11008',
                'rowNumber' => 2,
                'parsedPayload' => ['customerCode' => '11008', 'grandTotal' => '147061500'],
            ],
        ]);

        $this->assertDatabaseHas('import_batch_sheet_records', [
            'import_batch_id' => $importBatch->id,
            'sheet_name' => 'Tổng hợp',
            'customer_code' => '90300',
            'row_number' => 2,
            'customer_type_inferred' => 'Khách thường',
        ]);

        $this->assertDatabaseHas('import_batch_sheet_records', [
            'import_batch_id' => $importBatch->id,
            'sheet_name' => 'Khoán NPP',
            'customer_code' => '90300',
            'row_number' => 2,
            'customer_type_inferred' => 'Khách thường',
        ]);

        $this->assertDatabaseHas('import_batch_sheet_records', [
            'import_batch_id' => $importBatch->id,
            'sheet_name' => 'Cám cá',
            'customer_code' => '16068',
            'row_number' => 2,
            'customer_type_inferred' => 'Khách thường',
        ]);

        $this->assertDatabaseHas('import_batch_sheet_records', [
            'import_batch_id' => $importBatch->id,
            'sheet_name' => 'Key Account',
            'customer_code' => '11008',
            'row_number' => 2,
            'customer_type_inferred' => 'Key Account',
        ]);
    }

    public function test_service_replaces_existing_records_for_the_same_sheet_and_batch(): void
    {
        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-TEST-0002',
            'original_file_name' => 'Data import chuẩn_Final.xlsx',
            'stored_path' => 'imports/tmp/test-2.xlsx',
            'status' => 'workbook_analyzed',
            'started_at' => now(),
        ]);

        $service = app(PersistImportBatchSheetRecordsService::class);

        $service->replaceForSheet($importBatch, 'Tổng hợp', [
            [
                'customerCode' => '90300',
                'rowNumber' => 2,
                'parsedPayload' => ['customerCode' => '90300'],
            ],
            [
                'customerCode' => '90301',
                'rowNumber' => 3,
                'parsedPayload' => ['customerCode' => '90301'],
            ],
        ]);

        $service->replaceForSheet($importBatch, 'Tổng hợp', [
            [
                'customerCode' => '90302',
                'rowNumber' => 4,
                'parsedPayload' => ['customerCode' => '90302'],
            ],
        ]);

        $this->assertDatabaseMissing('import_batch_sheet_records', [
            'import_batch_id' => $importBatch->id,
            'sheet_name' => 'Tổng hợp',
            'customer_code' => '90300',
        ]);

        $this->assertDatabaseMissing('import_batch_sheet_records', [
            'import_batch_id' => $importBatch->id,
            'sheet_name' => 'Tổng hợp',
            'customer_code' => '90301',
        ]);

        $this->assertDatabaseHas('import_batch_sheet_records', [
            'import_batch_id' => $importBatch->id,
            'sheet_name' => 'Tổng hợp',
            'customer_code' => '90302',
            'row_number' => 4,
        ]);

        $this->assertSame(
            1,
            $importBatch->sheetRecords()->where('sheet_name', 'Tổng hợp')->count(),
        );
    }
}
