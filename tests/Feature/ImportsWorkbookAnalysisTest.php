<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsWorkbookAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_with_manage_permission_can_analyze_uploaded_workbook_and_receive_detected_sheets(): void
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

        $analysisResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.analyze-workbook'), [
                'storedPath' => $storedPath,
            ]);

        $analysisResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Đã đọc cấu trúc workbook thành công.')
            ->assertJsonPath('toast.summary', 'Đọc workbook thành công')
            ->assertJsonPath('data.contract.version', '1.2-H')
            ->assertJsonPath('data.contract.stage', 'workbook-boundary')
            ->assertJsonPath('data.summary.detectedSheetCount', 5)
            ->assertJsonPath('data.summary.missingSheetCount', 0)
            ->assertJsonPath('data.summary.unexpectedSheetCount', 1)
            ->assertJsonPath('data.expectedSheets.0', 'Tổng hợp')
            ->assertJsonPath('data.expectedSheets.1', 'Khoán NPP')
            ->assertJsonPath('data.expectedSheets.2', 'Cám cá')
            ->assertJsonPath('data.expectedSheets.3', 'Key Account')
            ->assertJsonPath('data.detectedSheets.0', 'Tổng hợp')
            ->assertJsonPath('data.detectedSheets.1', 'Khoán NPP')
            ->assertJsonPath('data.detectedSheets.2', 'Cám cá')
            ->assertJsonPath('data.detectedSheets.3', 'Key Account')
            ->assertJsonPath('data.detectedSheets.4', 'Template Mail')
            ->assertJsonPath('data.missingSheets', [])
            ->assertJsonPath('data.unexpectedSheets.0', 'Template Mail')
            ->assertJsonPath('data.sheets.0.name', 'Tổng hợp')
            ->assertJsonPath('data.sheets.0.present', true)
            ->assertJsonPath('data.sheets.0.missing', false)
            ->assertJsonPath('data.sheets.0.headerRow.0', 'STT')
            ->assertJsonPath('data.sheets.0.headerRow.1', 'Tháng')
            ->assertJsonPath('data.sheets.0.headerRow.2', 'Mã số')
            ->assertJsonPath('data.sheets.1.headerRow.9', 'Nội dung CT 1')
            ->assertJsonPath('data.sheets.2.headerRow.10', 'CT1')
            ->assertJsonPath('data.sheets.3.headerRow.16', 'Nội dung CT 1');

        $payload = $analysisResponse->json('data');

        $this->assertCount(4, $payload['sheets']);
        $this->assertGreaterThan(0, $payload['sheets'][0]['dataRowCount']);
        $this->assertGreaterThan(0, $payload['sheets'][1]['dataRowCount']);
        $this->assertGreaterThan(0, $payload['sheets'][2]['dataRowCount']);
        $this->assertGreaterThan(0, $payload['sheets'][3]['dataRowCount']);

        $this->assertFalse($payload['sheets'][0]['isEmpty']);
        $this->assertFalse($payload['sheets'][1]['isEmpty']);
        $this->assertFalse($payload['sheets'][2]['isEmpty']);
        $this->assertFalse($payload['sheets'][3]['isEmpty']);
    }

    public function test_guest_cannot_analyze_workbook_without_manage_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.analyze-workbook'), [
                'storedPath' => 'imports/tmp/fake.xlsx',
            ])
            ->assertForbidden();
    }

    public function test_analyze_workbook_rejects_missing_temporary_file_with_vietnamese_message(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.analyze-workbook'), [
                'storedPath' => 'imports/tmp/missing.xlsx',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['storedPath']);

        $this->assertSame(
            'Không tìm thấy file upload tạm để đọc workbook.',
            $response->json('errors.storedPath.0'),
        );
    }

    public function test_analyze_workbook_returns_structured_error_for_invalid_excel_content(): void
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

        $analysisResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.analyze-workbook'), [
                'storedPath' => $storedPath,
            ]);

        $analysisResponse
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('toast.summary', 'Không thể đọc workbook')
            ->assertJsonPath('errors.workbook.0', 'Không tìm thấy cấu trúc workbook trong file Excel.');
    }
}
