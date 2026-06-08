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
                'email' => 'a@example.com',
                'emails' => ['a@example.com', 'b@example.com'],
                'sourceSheets' => ['Tổng hợp', 'Khoán NPP'],
                'tongHop' => ['customerCode' => '90300', 'email' => 'a@example.com', 'emails' => ['a@example.com', 'b@example.com']],
                'khoanNpp' => ['customerCode' => '90300', 'email' => 'a@example.com', 'emails' => ['a@example.com', 'b@example.com']],
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

        $record = $importBatch->fresh()->aggregatedRecords()->where('customer_code', '90300')->firstOrFail();
        $this->assertSame(['a@example.com', 'b@example.com'], $record->aggregated_payload['emails']);
        $this->assertSame(['a@example.com', 'b@example.com'], $record->aggregated_payload['tongHop']['emails']);

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

    public function test_service_persists_unicode_json_without_escaping_vietnamese_characters(): void
    {
        $importBatch = ImportBatch::query()->create([
            'batch_code' => 'IMP-AGG-0003',
            'original_file_name' => 'Data import chuẩn_Final.xlsx',
            'stored_path' => 'imports/tmp/test-agg-3.xlsx',
            'status' => 'parsed_complete',
            'started_at' => now(),
        ]);

        $service = app(PersistImportBatchAggregatedRecordsService::class);

        $service->replaceForBatch($importBatch, [
            [
                'customerCode' => '90300',
                'customerType' => 'Khách thường',
                'customerFullName' => 'Công ty Cám cá miền Tây',
                'sourceSheets' => ['Tổng hợp', 'Cám cá'],
                'tongHop' => ['sheetName' => 'Tổng hợp'],
                'khoanNpp' => null,
                'camCa' => ['sheetName' => 'Cám cá'],
                'keyAccount' => null,
            ],
        ]);

        $record = $importBatch->aggregatedRecords()->firstOrFail();

        $this->assertStringContainsString('Tổng hợp', $record->getRawOriginal('source_sheets'));
        $this->assertStringContainsString('Cám cá', $record->getRawOriginal('source_sheets'));
        $this->assertStringNotContainsString('\\u', $record->getRawOriginal('source_sheets'));

        $this->assertStringContainsString('Khách thường', $record->getRawOriginal('aggregated_payload'));
        $this->assertStringContainsString('Công ty Cám cá miền Tây', $record->getRawOriginal('aggregated_payload'));
        $this->assertStringNotContainsString('\\u', $record->getRawOriginal('aggregated_payload'));
    }
}
