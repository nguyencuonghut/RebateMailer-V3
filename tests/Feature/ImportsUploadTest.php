<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_with_imports_manage_permission_can_upload_xlsx_and_receive_receipt(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $file = UploadedFile::fake()->create(
            'Data import chuẩn_Final.xlsx',
            256,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), [
                'file' => $file,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Tải file lên thành công.')
            ->assertJsonPath('toast.summary', 'Tải file thành công')
            ->assertJsonPath('data.originalFileName', 'Data import chuẩn_Final.xlsx')
            ->assertJsonPath('data.size', $file->getSize())
            ->assertJsonPath('data.importBatch.status', 'uploaded');

        $storedPath = $response->json('data.storedPath');
        $batchId = $response->json('data.importBatch.id');

        $this->assertNotEmpty($storedPath);
        $this->assertNotEmpty($batchId);
        Storage::disk('local')->assertExists($storedPath);

        $batch = ImportBatch::query()->findOrFail($batchId);

        $this->assertSame('Data import chuẩn_Final.xlsx', $batch->original_file_name);
        $this->assertSame($storedPath, $batch->stored_path);
        $this->assertSame($user->id, $batch->uploaded_by);
        $this->assertSame('uploaded', $batch->status);
        $this->assertNotNull($batch->started_at);
        $this->assertNotEmpty($response->json('data.importBatch.batchCode'));
    }

    public function test_guest_role_cannot_upload_import_file_without_manage_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        $file = UploadedFile::fake()->create(
            'Data import chuẩn_Final.xlsx',
            256,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), [
                'file' => $file,
            ])
            ->assertForbidden();
    }

    public function test_upload_rejects_invalid_file_type_with_vietnamese_validation_message(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        $file = UploadedFile::fake()->create('notes.txt', 32, 'text/plain');

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.upload'), [
                'file' => $file,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $this->assertSame(
            'Chỉ chấp nhận file Excel .xlsx.',
            $response->json('errors.file.0'),
        );
    }
}
