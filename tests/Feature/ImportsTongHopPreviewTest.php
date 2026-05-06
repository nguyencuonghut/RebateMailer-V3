<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsTongHopPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_with_manage_permission_can_preview_tong_hop_sheet(): void
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
            ->post(route('imports.preview-tong-hop'), [
                'storedPath' => $storedPath,
            ]);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Đã parse sheet Tổng hợp thành công.')
            ->assertJsonPath('toast.summary', 'Preview sheet Tổng hợp đã sẵn sàng')
            ->assertJsonPath('data.sheetName', 'Tổng hợp')
            ->assertJsonPath('data.fixedHeaders.0', 'STT')
            ->assertJsonPath('data.fixedHeaders.1', 'Tháng');

        $payload = $previewResponse->json('data');

        $this->assertGreaterThan(0, $payload['recordCount']);
        $this->assertNotEmpty($payload['dynamicHeaders']);
        $this->assertNotEmpty($payload['records'][0]['month']);
        $this->assertNotEmpty($payload['records'][0]['customerCode']);
        $this->assertNotEmpty($payload['records'][0]['customerFullName']);
        $this->assertNotEmpty($payload['records'][0]['dynamicItems']);
    }

    public function test_guest_cannot_preview_tong_hop_without_manage_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-tong-hop'), [
                'storedPath' => 'imports/tmp/fake.xlsx',
            ])
            ->assertForbidden();
    }

    public function test_preview_tong_hop_returns_structured_error_for_invalid_excel_content(): void
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
            ->post(route('imports.preview-tong-hop'), [
                'storedPath' => $storedPath,
            ]);

        $previewResponse
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('toast.summary', 'Không thể preview sheet Tổng hợp');
    }
}
