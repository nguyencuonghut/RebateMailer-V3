<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsCamCaPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_with_manage_permission_can_preview_cam_ca_sheet(): void
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
            ->post(route('imports.preview-cam-ca'), [
                'importBatchId' => $importBatchId,
            ]);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Đã parse sheet Cám cá thành công.')
            ->assertJsonPath('toast.summary', 'Preview sheet Cám cá đã sẵn sàng')
            ->assertJsonPath('data.sheetName', 'Cám cá')
            ->assertJsonPath('data.fixedHeaders.0', 'STT')
            ->assertJsonPath('data.fixedHeaders.1', 'Tháng')
            ->assertJsonPath('data.programPairCount', 3);

        $payload = $previewResponse->json('data');

        $this->assertSame(3, $payload['recordCount']);
        $this->assertSame([
            'Thưởng sản lượng tháng 03.2026',
            'Thưởng đặc biệt tháng 03.2026',
            'Thưởng ngân quỹ 9113',
            'Thưởng ngân quỹ 9103',
            'Hỗ trợ vận chuyển',
            'Chương trình KM từ 25-31.03.26',
            'Chiết khấu thanh toán',
            'Chi phí tiền lãi do không đạt CKSL bảo lãnh',
        ], $payload['discreteHeaders']);
        $this->assertSame([$payload['records'][0]['email']], $payload['records'][0]['emails']);

        $records = collect($payload['records'])->keyBy('customerCode');

        $this->assertCamCaRecord16068($records);
        $this->assertCamCaRecord90006($records);
        $this->assertCamCaRecord90182Ts($records);

        $this->assertDatabaseHas('import_batch_sheet_records', [
            'import_batch_id' => $importBatchId,
            'sheet_name' => 'Cám cá',
            'customer_code' => '16068',
            'row_number' => 2,
            'customer_type_inferred' => 'Khách thường',
        ]);

        $batch = ImportBatch::query()->findOrFail($importBatchId);
        $this->assertIsArray($batch->workbook_summary['sheetPreviews']['Cám cá'] ?? null);
    }

    public function test_guest_cannot_preview_cam_ca_without_manage_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-cam-ca'), [
                'importBatchId' => 999999,
            ])
            ->assertForbidden();
    }

    public function test_preview_cam_ca_returns_structured_error_for_invalid_excel_content(): void
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
            ->post(route('imports.preview-cam-ca'), [
                'importBatchId' => $importBatchId,
            ]);

        $previewResponse
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('toast.summary', 'Không thể preview sheet Cám cá');
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertCamCaRecord16068(Collection $records): void
    {
        $record = $records->get('16068');

        $this->assertNotNull($record);
        $this->assertSame(2, $record['rowNumber']);
        $this->assertSame('16068 - Công ty TNHH TM DV Thắng Giang', $record['customerFullName']);
        $this->assertSame('128500', $record['totalQuantity']);
        $this->assertSame('2479904400', $record['revenue']);
        $this->assertSame('199615000', $record['invoiceDiscount']);
        $this->assertSame('12850000', $record['otherDiscount']);
        $this->assertSame('212465000', $record['grandTotal']);
        $this->assertCount(2, $record['programItems']);
        $this->assertSame('Chiết khấu quý 1.2026 sản phẩm 9113 mức 250đ/kg', $record['programItems'][0]['content']);
        $this->assertSame('51200000', $record['programItems'][0]['amount']);
        $this->assertSame('Hỗ trợ đặc biệt sản phẩm cá biển', $record['programItems'][1]['content']);
        $this->assertSame('6150000', $record['programItems'][1]['amount']);
        $this->assertSame([
            ['label' => 'Thưởng sản lượng tháng 03.2026', 'value' => '89950000'],
            ['label' => 'Thưởng đặc biệt tháng 03.2026', 'value' => '20780000'],
            ['label' => 'Thưởng ngân quỹ 9113', 'value' => '9600000'],
            ['label' => 'Hỗ trợ vận chuyển', 'value' => '19275000'],
            ['label' => 'Chương trình KM từ 25-31.03.26', 'value' => '2660000'],
            ['label' => 'Chiết khấu thanh toán', 'value' => '12850000'],
        ], $record['discreteItems']);
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertCamCaRecord90006(Collection $records): void
    {
        $record = $records->get('90006');

        $this->assertNotNull($record);
        $this->assertSame(3, $record['rowNumber']);
        $this->assertSame([], $record['programItems']);
        $this->assertSame([
            ['label' => 'Thưởng sản lượng tháng 03.2026', 'value' => '1870000'],
            ['label' => 'Chương trình KM từ 25-31.03.26', 'value' => '525000'],
            ['label' => 'Chiết khấu thanh toán', 'value' => '467500'],
        ], $record['discreteItems']);
        $this->assertSame('2862500', $record['grandTotal']);
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertCamCaRecord90182Ts(Collection $records): void
    {
        $record = $records->get('90182TS');

        $this->assertNotNull($record);
        $this->assertSame(4, $record['rowNumber']);
        $this->assertCount(3, $record['programItems']);
        $this->assertSame([1, 2, 3], array_column($record['programItems'], 'programIndex'));
        $this->assertSame('Giảm trừ chiết khấu 8 tấn hàng gửi không lấy đúng hạn', $record['programItems'][0]['content']);
        $this->assertSame('-2400000', $record['programItems'][0]['amount']);
        $this->assertSame('Chiết khấu tháng bổ sung sản phẩm 9102 đặt riêng', $record['programItems'][1]['content']);
        $this->assertSame('4269375', $record['programItems'][1]['amount']);
        $this->assertSame('Hỗ trợ 800đ/kg sản phẩm 9102 hàng đặt riêng', $record['programItems'][2]['content']);
        $this->assertSame('25300000', $record['programItems'][2]['amount']);
        $this->assertSame([
            ['label' => 'Thưởng sản lượng tháng 03.2026', 'value' => '19812500'],
            ['label' => 'Thưởng đặc biệt tháng 03.2026', 'value' => '1600000'],
        ], $record['discreteItems']);
        $this->assertSame('48581875', $record['grandTotal']);
    }
}
