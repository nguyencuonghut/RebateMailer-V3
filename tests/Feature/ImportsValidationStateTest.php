<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Services\Imports\ValidationIssue;
use App\Services\Imports\WriteValidationStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportsValidationStateTest extends TestCase
{
    use RefreshDatabase;

    private function makeImportBatch(string $code = 'IMP-VAL-0001'): ImportBatch
    {
        return ImportBatch::query()->create([
            'batch_code' => $code,
            'original_file_name' => 'Data import chuẩn_Final.xlsx',
            'stored_path' => 'imports/tmp/test-val.xlsx',
            'status' => 'aggregated',
            'started_at' => now(),
            'workbook_summary' => [
                'aggregatePreview' => [
                    'summary' => [
                        'totalCustomerCount' => 2,
                        'normalCustomerCount' => 1,
                        'keyAccountCustomerCount' => 1,
                    ],
                    'recordCount' => 2,
                ],
            ],
        ]);
    }

    private function makeAggregatedRecord(ImportBatch $importBatch, string $customerCode, string $customerType = 'Khách thường'): ImportBatchAggregatedRecord
    {
        return ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $importBatch->id,
            'customer_code' => $customerCode,
            'customer_type' => $customerType,
            'source_sheets' => ['Tổng hợp'],
            'aggregated_payload' => ['customerCode' => $customerCode, 'customerType' => $customerType],
            'validation_state' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Behavior 1: ghi validation_state vào aggregated record
    // -------------------------------------------------------------------------

    public function test_service_writes_validation_state_into_aggregated_record(): void
    {
        $importBatch = $this->makeImportBatch();
        $this->makeAggregatedRecord($importBatch, '90300');

        $service = app(WriteValidationStateService::class);

        $service->write($importBatch, [
            '90300' => [
                new ValidationIssue(code: 'MISSING_EMAIL', message: 'Khách hàng thiếu email.', isBlocking: true),
            ],
        ]);

        $record = ImportBatchAggregatedRecord::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('customer_code', '90300')
            ->firstOrFail();

        $state = $record->validation_state;

        $this->assertIsArray($state);
        $this->assertFalse($state['isValid']);
        $this->assertCount(1, $state['errors']);
        $this->assertSame('MISSING_EMAIL', $state['errors'][0]['code']);
        $this->assertSame('Khách hàng thiếu email.', $state['errors'][0]['message']);
        $this->assertCount(0, $state['warnings']);
    }

    public function test_service_marks_record_valid_when_only_warnings_present(): void
    {
        $importBatch = $this->makeImportBatch('IMP-VAL-0002');
        $this->makeAggregatedRecord($importBatch, '90300');

        $service = app(WriteValidationStateService::class);

        $service->write($importBatch, [
            '90300' => [
                new ValidationIssue(code: 'LOW_GRAND_TOTAL', message: 'Tổng cộng bằng 0.', isBlocking: false),
            ],
        ]);

        $record = ImportBatchAggregatedRecord::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('customer_code', '90300')
            ->firstOrFail();

        $state = $record->validation_state;

        $this->assertTrue($state['isValid']);
        $this->assertCount(0, $state['errors']);
        $this->assertCount(1, $state['warnings']);
        $this->assertSame('LOW_GRAND_TOTAL', $state['warnings'][0]['code']);
    }

    public function test_service_marks_record_valid_and_no_issues_when_no_issues_given(): void
    {
        $importBatch = $this->makeImportBatch('IMP-VAL-0003');
        $this->makeAggregatedRecord($importBatch, '90300');

        $service = app(WriteValidationStateService::class);

        $service->write($importBatch, []);

        $record = ImportBatchAggregatedRecord::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('customer_code', '90300')
            ->firstOrFail();

        $state = $record->validation_state;

        $this->assertTrue($state['isValid']);
        $this->assertCount(0, $state['errors']);
        $this->assertCount(0, $state['warnings']);
    }

    // -------------------------------------------------------------------------
    // Behavior 2: batch status được cập nhật đúng
    // -------------------------------------------------------------------------

    public function test_batch_status_becomes_validated_ready_when_no_blocking_errors(): void
    {
        $importBatch = $this->makeImportBatch('IMP-VAL-0004');
        $this->makeAggregatedRecord($importBatch, '90300');

        $service = app(WriteValidationStateService::class);

        $updatedBatch = $service->write($importBatch, [
            '90300' => [
                new ValidationIssue(code: 'LOW_GRAND_TOTAL', message: 'Tổng cộng bằng 0.', isBlocking: false),
            ],
        ]);

        $this->assertSame('validated_with_warnings', $updatedBatch->status);
    }

    public function test_batch_status_becomes_validated_ready_when_zero_issues(): void
    {
        $importBatch = $this->makeImportBatch('IMP-VAL-0005');
        $this->makeAggregatedRecord($importBatch, '90300');

        $service = app(WriteValidationStateService::class);

        $updatedBatch = $service->write($importBatch, []);

        $this->assertSame('validated_ready', $updatedBatch->status);
    }

    public function test_batch_status_becomes_validated_with_warnings_when_has_blocking_errors(): void
    {
        $importBatch = $this->makeImportBatch('IMP-VAL-0006');
        $this->makeAggregatedRecord($importBatch, '90300');

        $service = app(WriteValidationStateService::class);

        $updatedBatch = $service->write($importBatch, [
            '90300' => [
                new ValidationIssue(code: 'MISSING_EMAIL', message: 'Khách hàng thiếu email.', isBlocking: true),
            ],
        ]);

        // Có blocking error → không thể dispatch → vẫn là validated_with_warnings (có lỗi)
        $this->assertSame('validated_with_warnings', $updatedBatch->status);
        $summary = $updatedBatch->workbook_summary['validationSummary'] ?? [];
        $this->assertFalse($summary['isReadyToDispatch']);
    }

    // -------------------------------------------------------------------------
    // Behavior 3: workbook_summary['validationSummary'] được populate
    // -------------------------------------------------------------------------

    public function test_validation_summary_is_written_to_workbook_summary(): void
    {
        $importBatch = $this->makeImportBatch('IMP-VAL-0007');
        $this->makeAggregatedRecord($importBatch, '90300');
        $this->makeAggregatedRecord($importBatch, '11008', 'Key Account');

        $service = app(WriteValidationStateService::class);

        $updatedBatch = $service->write($importBatch, [
            '90300' => [
                new ValidationIssue(code: 'MISSING_EMAIL', message: 'Thiếu email.', isBlocking: true),
                new ValidationIssue(code: 'LOW_GRAND_TOTAL', message: 'Tổng 0.', isBlocking: false),
            ],
        ]);

        $summary = $updatedBatch->workbook_summary['validationSummary'] ?? null;

        $this->assertIsArray($summary);
        $this->assertSame(1, $summary['totalErrors']);
        $this->assertSame(1, $summary['totalWarnings']);
        $this->assertFalse($summary['isReadyToDispatch']);
        $this->assertArrayHasKey('checkedAt', $summary);
    }
}
