<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsKeyAccountPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_with_manage_permission_can_preview_key_account_sheet(): void
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

        $storedPath = $uploadResponse->json('data.storedPath');

        $previewResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-key-account'), [
                'storedPath' => $storedPath,
            ]);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Đã parse sheet Key Account thành công.')
            ->assertJsonPath('toast.summary', 'Preview sheet Key Account đã sẵn sàng')
            ->assertJsonPath('data.sheetName', 'Key Account')
            ->assertJsonPath('data.fixedHeaders.0', 'STT')
            ->assertJsonPath('data.fixedHeaders.1', 'Tháng')
            ->assertJsonPath('data.programBlockCount', 16);

        $payload = $previewResponse->json('data');

        $this->assertSame(3, $payload['recordCount']);
        $this->assertSame([
            'Thưởng doanh thu tháng',
            'Chiết khấu khác ( Không thể hiện trên hóa đơn)',
            'Chi phí tiền lãi do không đạt CKSL bảo lãnh',
            'Chiết khấu thanh toán dòng thịt',
        ], $payload['discreteHeaders']);

        $records = collect($payload['records'])->keyBy('customerCode');

        $this->assertKeyAccountRecord11008($records);
        $this->assertKeyAccountRecord38041($records);
        $this->assertKeyAccountRecord38053($records);
    }

    public function test_guest_cannot_preview_key_account_without_manage_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-key-account'), [
                'storedPath' => 'imports/tmp/fake.xlsx',
            ])
            ->assertForbidden();
    }

    public function test_preview_key_account_returns_structured_error_for_invalid_excel_content(): void
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

        $storedPath = $uploadResponse->json('data.storedPath');

        $previewResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-key-account'), [
                'storedPath' => $storedPath,
            ]);

        $previewResponse
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('toast.summary', 'Không thể preview sheet Key Account');
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertKeyAccountRecord11008(Collection $records): void
    {
        $record = $records->get('11008');

        $this->assertNotNull($record);
        $this->assertSame('147061500', $record['invoiceDiscount']);
        $this->assertSame('147061500', $record['grandTotal']);
        $this->assertSame([], $record['discreteItems']);
        $this->assertCount(13, $record['programItems']);
        $this->assertSame('Chiết khấu tháng hàng bao và silo : 200đ/kg', $record['programItems'][0]['content']);
        $this->assertSame('147300', $record['programItems'][0]['quantity']);
        $this->assertSame('200', $record['programItems'][0]['supportRate']);
        $this->assertSame('29460000', $record['programItems'][0]['amount']);
        $this->assertSame(12, $record['programItems'][11]['programIndex']);
        $this->assertSame('Hỗ trợ cám silo: 180đ/kg', $record['programItems'][11]['content']);
        $this->assertSame('147300', $record['programItems'][11]['quantity']);
        $this->assertSame('180', $record['programItems'][11]['supportRate']);
        $this->assertSame('26514000', $record['programItems'][11]['amount']);
        $this->assertSame(13, $record['programItems'][12]['programIndex']);
        $this->assertSame('Hỗ trợ vận chuyển hàng bao: 260đ/kg', $record['programItems'][12]['content']);
        $this->assertSame('', $record['programItems'][12]['quantity']);
        $this->assertSame('260', $record['programItems'][12]['supportRate']);
        $this->assertSame('', $record['programItems'][12]['amount']);
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertKeyAccountRecord38041(Collection $records): void
    {
        $record = $records->get('38041');

        $this->assertNotNull($record);
        $this->assertSame('724162810', $record['grandTotal']);
        $this->assertSame([
            ['label' => 'Thưởng doanh thu tháng', 'value' => '431855450'],
        ], $record['discreteItems']);
        $this->assertCount(15, $record['programItems']);
        $this->assertSame([1, 2, 3], array_column(array_slice($record['programItems'], 0, 3), 'programIndex'));
        $this->assertSame(15, $record['programItems'][14]['programIndex']);
        $this->assertSame('Khuyến mại SP 1130SPRIME', $record['programItems'][14]['content']);
        $this->assertSame('207000', $record['programItems'][14]['quantity']);
        $this->assertSame('400', $record['programItems'][14]['supportRate']);
        $this->assertSame('82800000', $record['programItems'][14]['amount']);
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertKeyAccountRecord38053(Collection $records): void
    {
        $record = $records->get('38053');

        $this->assertNotNull($record);
        $this->assertSame([
            ['label' => 'Thưởng doanh thu tháng', 'value' => '361787750'],
        ], $record['discreteItems']);
        $this->assertCount(15, $record['programItems']);
        $this->assertSame('Khuyến mại SP 1410SPRIME', $record['programItems'][0]['content']);
        $this->assertSame('32000', $record['programItems'][0]['quantity']);
        $this->assertSame('400', $record['programItems'][0]['supportRate']);
        $this->assertSame('12800000', $record['programItems'][0]['amount']);
        $this->assertSame(14, $record['programItems'][13]['programIndex']);
        $this->assertSame('Khuyến mại SP 1120PPRIME', $record['programItems'][13]['content']);
        $this->assertSame('41500', $record['programItems'][13]['quantity']);
        $this->assertSame('771.44', $record['programItems'][13]['supportRate']);
        $this->assertSame('32014760', $record['programItems'][13]['amount']);
        $this->assertSame(15, $record['programItems'][14]['programIndex']);
    }
}
