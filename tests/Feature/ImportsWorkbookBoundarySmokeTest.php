<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsWorkbookBoundarySmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_workbook_boundary_smoke_happy_path_from_upload_to_analyze_contract(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('imports.index'))
            ->assertOk();

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

        $uploadResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $storedPath = $uploadResponse->json('data.storedPath');

        $analyzeResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.analyze-workbook'), [
                'storedPath' => $storedPath,
            ]);

        $analyzeResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('data.contract.version', '1.2-H')
            ->assertJsonPath('data.summary.detectedSheetCount', 5)
            ->assertJsonPath('data.summary.unexpectedSheetCount', 1)
            ->assertJsonPath('data.unexpectedSheets.0', 'Template Mail')
            ->assertJsonCount(4, 'data.sheets');
    }

    public function test_workbook_boundary_smoke_failure_then_recovery_path(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $brokenWorkbook = UploadedFile::fake()->create(
            'broken.xlsx',
            32,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $brokenUploadResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), [
                'file' => $brokenWorkbook,
            ]);

        $brokenStoredPath = $brokenUploadResponse->json('data.storedPath');

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.analyze-workbook'), [
                'storedPath' => $brokenStoredPath,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('toast.summary', 'Không thể đọc workbook');

        $validWorkbook = new UploadedFile(
            base_path('data/Data import chuẩn_Final.xlsx'),
            'Data import chuẩn_Final.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $validUploadResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), [
                'file' => $validWorkbook,
            ]);

        $validStoredPath = $validUploadResponse->json('data.storedPath');

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.analyze-workbook'), [
                'storedPath' => $validStoredPath,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('data.contract.stage', 'workbook-boundary')
            ->assertJsonCount(4, 'data.sheets');
    }
}
