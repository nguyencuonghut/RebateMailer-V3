<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsKhoanNppPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_with_manage_permission_can_preview_khoan_npp_sheet(): void
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
            ->post(route('imports.preview-khoan-npp'), [
                'storedPath' => $storedPath,
            ]);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Đã parse sheet Khoán NPP thành công.')
            ->assertJsonPath('toast.summary', 'Preview sheet Khoán NPP đã sẵn sàng')
            ->assertJsonPath('data.sheetName', 'Khoán NPP')
            ->assertJsonPath('data.fixedHeaders.0', 'STT')
            ->assertJsonPath('data.fixedHeaders.1', 'Tháng');

        $payload = $previewResponse->json('data');

        $this->assertGreaterThan(0, $payload['recordCount']);
        $this->assertSame(16, $payload['programBlockCount']);
        $this->assertNotEmpty($payload['records'][0]['customerCode']);
        $this->assertNotEmpty($payload['records'][0]['customerFullName']);
        $this->assertNotEmpty($payload['records'][0]['programItems']);
        $this->assertNotEmpty($payload['records'][0]['programItems'][0]['content']);

        $quangRecord = collect($payload['records'])->firstWhere('customerCode', '90300');

        $this->assertNotNull($quangRecord);
        $this->assertCount(4, $quangRecord['programItems']);
        $this->assertSame([1, 2, 3, 4], array_column($quangRecord['programItems'], 'programIndex'));
        $this->assertSame('59170', $quangRecord['programItems'][0]['quantity']);
        $this->assertSame('200', $quangRecord['programItems'][0]['supportRate']);
        $this->assertSame('11834000', $quangRecord['programItems'][0]['amount']);
        $this->assertSame('15375', $quangRecord['programItems'][1]['quantity']);
        $this->assertSame('100', $quangRecord['programItems'][1]['supportRate']);
        $this->assertSame('1537500', $quangRecord['programItems'][1]['amount']);
        $this->assertSame('', $quangRecord['programItems'][2]['quantity']);
        $this->assertSame('', $quangRecord['programItems'][2]['supportRate']);
        $this->assertSame('6000000', $quangRecord['programItems'][2]['amount']);
        $this->assertSame('', $quangRecord['programItems'][3]['quantity']);
        $this->assertSame('', $quangRecord['programItems'][3]['supportRate']);
        $this->assertSame('5000000', $quangRecord['programItems'][3]['amount']);
    }

    public function test_guest_cannot_preview_khoan_npp_without_manage_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-khoan-npp'), [
                'storedPath' => 'imports/tmp/fake.xlsx',
            ])
            ->assertForbidden();
    }

    public function test_preview_khoan_npp_returns_structured_error_for_invalid_excel_content(): void
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
            ->post(route('imports.preview-khoan-npp'), [
                'storedPath' => $storedPath,
            ]);

        $previewResponse
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('toast.summary', 'Không thể preview sheet Khoán NPP');
    }
}
