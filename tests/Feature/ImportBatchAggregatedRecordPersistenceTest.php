<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Services\Imports\PersistImportBatchAggregatedRecordsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportBatchAggregatedRecordPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_persist_aggregated_records_for_a_batch(): void
    {
        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-AGG-0001',
            'original_file_name' => 'Data import chuẩn_Final.xlsx',
            'stored_path' => 'imports/tmp/test-agg.xlsx',
            'status' => 'parsed_complete',
            'started_at' => now(),
        ]);

        $service = app(PersistImportBatchAggregatedRecordsService::class);

        $service->replaceForBatch($importBatch, [
            [
                'customerCode' => '90300',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp', 'Khoán NPP'],
                'tongHop' => ['customerCode' => '90300'],
                'khoanNpp' => ['customerCode' => '90300'],
                'camCa' => null,
                'keyAccount' => null,
            ],
            [
                'customerCode' => '11008',
                'customerType' => 'Key Account',
                'sourceSheets' => ['Key Account'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => ['customerCode' => '11008'],
            ],
        ]);

        $this->assertDatabaseHas('import_batch_aggregated_records', [
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90300',
            'customer_type' => 'Khách thường',
        ]);

        $this->assertDatabaseHas('import_batch_aggregated_records', [
            'import_batch_id' => $importBatch->id,
            'customer_code' => '11008',
            'customer_type' => 'Key Account',
        ]);
    }

    public function test_service_replaces_existing_aggregated_records_for_the_same_batch(): void
    {
        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-AGG-0002',
            'original_file_name' => 'Data import chuẩn_Final.xlsx',
            'stored_path' => 'imports/tmp/test-agg-2.xlsx',
            'status' => 'parsed_complete',
            'started_at' => now(),
        ]);

        $service = app(PersistImportBatchAggregatedRecordsService::class);

        $service->replaceForBatch($importBatch, [
            [
                'customerCode' => '90300',
                'customerType' => 'Khách thường',
                'sourceSheets' => ['Tổng hợp'],
                'tongHop' => ['customerCode' => '90300'],
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => null,
            ],
        ]);

        $service->replaceForBatch($importBatch, [
            [
                'customerCode' => '11008',
                'customerType' => 'Key Account',
                'sourceSheets' => ['Key Account'],
                'tongHop' => null,
                'khoanNpp' => null,
                'camCa' => null,
                'keyAccount' => ['customerCode' => '11008'],
            ],
        ]);

        $this->assertDatabaseMissing('import_batch_aggregated_records', [
            'import_batch_id' => $importBatch->id,
            'customer_code' => '90300',
        ]);

        $this->assertDatabaseHas('import_batch_aggregated_records', [
            'import_batch_id' => $importBatch->id,
            'customer_code' => '11008',
        ]);

        $this->assertSame(1, $importBatch->aggregatedRecords()->count());
    }
}
