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
            ->assertJsonPath('data.sheetCount', 5)
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
            ->assertJsonPath('data.unexpectedSheets.0', 'Template Mail');
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
}
