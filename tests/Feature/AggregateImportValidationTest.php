<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Services\Imports\AggregateImportPreviewService;
use App\Services\Imports\PersistImportBatchSheetRecordsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AggregateImportValidationTest extends TestCase
{
    use RefreshDatabase;

    private AggregateImportPreviewService $service;
    private PersistImportBatchSheetRecordsService $persistService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AggregateImportPreviewService::class);
        $this->persistService = app(PersistImportBatchSheetRecordsService::class);
    }

    // ─── Email validation ───────────────────────────────────────────────────

    public function test_empty_email_produces_error(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: '', grandTotal: '1000000', totalInWords: 'Một triệu');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Tổng hợp] Email: không được để trống', $record['validationErrors']);
    }

    public function test_invalid_email_format_produces_error(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: 'not-an-email', grandTotal: '1000000', totalInWords: 'Một triệu');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Tổng hợp] Email: không đúng định dạng', $record['validationErrors']);
    }

    public function test_valid_email_produces_no_email_error(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: 'khach@example.com', grandTotal: '1000000', totalInWords: 'Một triệu');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertNotContains('[Tổng hợp] Email: không được để trống', $record['validationErrors']);
        $this->assertNotContains('[Tổng hợp] Email: không đúng định dạng', $record['validationErrors']);
    }

    public function test_semicolon_delimited_emails_produce_no_email_error_when_all_are_valid(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: 'a@example.com; b@example.com ; c@example.com', grandTotal: '1000000', totalInWords: 'Một triệu');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertNotContains('[Tổng hợp] Email: không được để trống', $record['validationErrors']);
        $this->assertNotContains('[Tổng hợp] Email: không đúng định dạng', $record['validationErrors']);
    }

    public function test_invalid_email_in_semicolon_delimited_list_produces_specific_error(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: 'a@example.com; bad-email; c@example.com', grandTotal: '1000000', totalInWords: 'Một triệu');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Tổng hợp] Email: "bad-email" không đúng định dạng', $record['validationErrors']);
    }

    // ─── Tổng cộng validation ───────────────────────────────────────────────

    public function test_empty_grand_total_produces_error(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: 'khach@example.com', grandTotal: '', totalInWords: 'Một triệu');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Tổng hợp] Tổng cộng: thiếu dữ liệu', $record['validationErrors']);
    }

    public function test_non_empty_grand_total_produces_no_error(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: 'khach@example.com', grandTotal: '5000000', totalInWords: 'Năm triệu');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertNotContains('[Tổng hợp] Tổng cộng: thiếu dữ liệu', $record['validationErrors']);
    }

    // ─── Bằng chữ validation ────────────────────────────────────────────────

    public function test_empty_total_in_words_produces_error(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: 'khach@example.com', grandTotal: '1000000', totalInWords: '');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Tổng hợp] Bằng chữ: thiếu dữ liệu', $record['validationErrors']);
    }

    public function test_non_empty_total_in_words_produces_no_error(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: 'khach@example.com', grandTotal: '1000000', totalInWords: 'Một triệu');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertNotContains('[Tổng hợp] Bằng chữ: thiếu dữ liệu', $record['validationErrors']);
    }

    // ─── Multi-error per record ─────────────────────────────────────────────

    public function test_all_three_fields_invalid_produces_three_errors(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: '', grandTotal: '', totalInWords: '');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Tổng hợp] Email: không được để trống', $record['validationErrors']);
        $this->assertContains('[Tổng hợp] Tổng cộng: thiếu dữ liệu', $record['validationErrors']);
        $this->assertContains('[Tổng hợp] Bằng chữ: thiếu dữ liệu', $record['validationErrors']);
        $this->assertCount(3, $record['validationErrors']);
    }

    public function test_record_with_no_errors_has_empty_validation_errors_array(): void
    {
        $batch = $this->makeBatchWithTongHop('C001', email: 'ok@example.com', grandTotal: '1000000', totalInWords: 'Một triệu');

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertSame([], $record['validationErrors']);
    }

    // ─── Multi-sheet validation ─────────────────────────────────────────────

    public function test_errors_are_collected_from_each_sheet_independently(): void
    {
        $batch = $this->makeEmptyBatch();
        $this->persistService->replaceForSheet($batch, 'Tổng hợp', [
            $this->sheetRow('C001', email: 'bad-email', grandTotal: '1000000', totalInWords: 'Một triệu'),
        ]);
        $this->persistService->replaceForSheet($batch, 'Khoán NPP', [
            $this->sheetRow('C001', email: 'ok@example.com', grandTotal: '', totalInWords: 'Hai triệu'),
        ]);

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Tổng hợp] Email: không đúng định dạng', $record['validationErrors']);
        $this->assertContains('[Khoán NPP] Tổng cộng: thiếu dữ liệu', $record['validationErrors']);
        $this->assertCount(2, $record['validationErrors']);
    }

    // ─── Summary errorCount ─────────────────────────────────────────────────

    public function test_error_count_equals_number_of_records_with_at_least_one_error(): void
    {
        $batch = $this->makeEmptyBatch();
        $this->persistService->replaceForSheet($batch, 'Tổng hợp', [
            $this->sheetRow('C001', email: 'ok@example.com', grandTotal: '1000000', totalInWords: 'Một triệu', rowNumber: 2),
            $this->sheetRow('C002', email: '', grandTotal: '2000000', totalInWords: 'Hai triệu', rowNumber: 3),
            $this->sheetRow('C003', email: 'bad-email', grandTotal: '', totalInWords: '', rowNumber: 4),
        ]);

        $result = $this->service->aggregate($batch);

        // C001 has no errors, C002 has 1 error, C003 has 3 errors → errorCount = 2 records
        $this->assertSame(2, $result['summary']['errorCount']);
    }

    public function test_error_count_is_zero_when_all_records_are_valid(): void
    {
        $batch = $this->makeEmptyBatch();
        $this->persistService->replaceForSheet($batch, 'Tổng hợp', [
            $this->sheetRow('C001', email: 'a@example.com', grandTotal: '1000000', totalInWords: 'Một triệu', rowNumber: 2),
            $this->sheetRow('C002', email: 'b@example.com', grandTotal: '2000000', totalInWords: 'Hai triệu', rowNumber: 3),
        ]);

        $result = $this->service->aggregate($batch);

        $this->assertSame(0, $result['summary']['errorCount']);
    }

    // ─── Validation per sheet type ──────────────────────────────────────────

    public function test_khoan_npp_sheet_validation_uses_sheet_name_in_error(): void
    {
        $batch = $this->makeEmptyBatch();
        $this->persistService->replaceForSheet($batch, 'Khoán NPP', [
            $this->sheetRow('C001', email: '', grandTotal: '1000000', totalInWords: 'Một triệu'),
        ]);

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Khoán NPP] Email: không được để trống', $record['validationErrors']);
    }

    public function test_cam_ca_sheet_validation_uses_sheet_name_in_error(): void
    {
        $batch = $this->makeEmptyBatch();
        $this->persistService->replaceForSheet($batch, 'Cám cá', [
            $this->sheetRow('C001', email: 'ok@example.com', grandTotal: '', totalInWords: 'Một triệu'),
        ]);

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Cám cá] Tổng cộng: thiếu dữ liệu', $record['validationErrors']);
    }

    public function test_key_account_sheet_validation_uses_sheet_name_in_error(): void
    {
        $batch = $this->makeEmptyBatch();
        $this->persistService->replaceForSheet($batch, 'Key Account', [
            $this->sheetRow('C001', email: 'ok@example.com', grandTotal: '1000000', totalInWords: ''),
        ]);

        $result = $this->service->aggregate($batch);
        $record = collect($result['records'])->firstWhere('customerCode', 'C001');

        $this->assertContains('[Key Account] Bằng chữ: thiếu dữ liệu', $record['validationErrors']);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeEmptyBatch(): ImportBatch
    {
        return ImportBatch::query()->create([
            'batch_code' => 'IMP-VAL-TEST',
            'original_file_name' => 'test.xlsx',
            'stored_path' => 'imports/tmp/test.xlsx',
            'status' => 'workbook_analyzed',
            'started_at' => now(),
        ]);
    }

    private function makeBatchWithTongHop(
        string $customerCode,
        string $email,
        string $grandTotal,
        string $totalInWords,
    ): ImportBatch {
        $batch = $this->makeEmptyBatch();
        $this->persistService->replaceForSheet($batch, 'Tổng hợp', [
            $this->sheetRow($customerCode, $email, $grandTotal, $totalInWords),
        ]);

        return $batch;
    }

    /**
     * @return array<string, mixed>
     */
    private function sheetRow(
        string $customerCode,
        string $email,
        string $grandTotal,
        string $totalInWords,
        int $rowNumber = 2,
    ): array {
        return [
            'customerCode' => $customerCode,
            'rowNumber' => $rowNumber,
            'parsedPayload' => [
                'rowNumber' => $rowNumber,
                'customerCode' => $customerCode,
                'customerFullName' => $customerCode.' - Tên khách',
                'email' => $email,
                'emails' => $email === ''
                    ? []
                    : array_values(array_filter(array_map(static fn (string $item): string => trim($item), explode(';', $email)), static fn (string $item): bool => $item !== '')),
                'grandTotal' => $grandTotal,
                'totalInWords' => $totalInWords,
            ],
        ];
    }
}
