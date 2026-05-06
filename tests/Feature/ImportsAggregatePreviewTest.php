<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsAggregatePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_user_with_manage_permission_can_preview_aggregated_import_data(): void
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
            ->post(route('imports.preview-aggregated'), [
                'storedPath' => $storedPath,
            ]);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Đã gom dữ liệu import theo Mã số thành công.')
            ->assertJsonPath('toast.summary', 'Preview aggregator đã sẵn sàng')
            ->assertJsonPath('data.summary.totalCustomerCount', 15)
            ->assertJsonPath('data.summary.normalCustomerCount', 12)
            ->assertJsonPath('data.summary.keyAccountCustomerCount', 3);

        $records = collect($previewResponse->json('data.records'))->keyBy('customerCode');

        $this->assertAggregatedRecord90300($records);
        $this->assertAggregatedRecord16068($records);
        $this->assertAggregatedRecord90182Ts($records);
        $this->assertAggregatedRecord11008($records);
    }

    public function test_guest_cannot_preview_aggregator_without_manage_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->withHeader('Accept', 'application/json')
            ->post(route('imports.preview-aggregated'), [
                'storedPath' => 'imports/tmp/fake.xlsx',
            ])
            ->assertForbidden();
    }

    public function test_preview_aggregator_returns_structured_error_for_invalid_excel_content(): void
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
            ->post(route('imports.preview-aggregated'), [
                'storedPath' => $storedPath,
            ]);

        $previewResponse
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('toast.summary', 'Không thể preview aggregator');
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertAggregatedRecord90300(Collection $records): void
    {
        $record = $records->get('90300');

        $this->assertNotNull($record);
        $this->assertSame('Khách thường', $record['customerType']);
        $this->assertSame(['Tổng hợp', 'Khoán NPP'], $record['sourceSheets']);
        $this->assertNotNull($record['tongHop']);
        $this->assertNotNull($record['khoanNpp']);
        $this->assertNull($record['camCa']);
        $this->assertNull($record['keyAccount']);
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertAggregatedRecord16068(Collection $records): void
    {
        $record = $records->get('16068');

        $this->assertNotNull($record);
        $this->assertSame('Khách thường', $record['customerType']);
        $this->assertSame(['Cám cá'], $record['sourceSheets']);
        $this->assertNull($record['tongHop']);
        $this->assertNull($record['khoanNpp']);
        $this->assertNotNull($record['camCa']);
        $this->assertNull($record['keyAccount']);
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertAggregatedRecord90182Ts(Collection $records): void
    {
        $record = $records->get('90182TS');

        $this->assertNotNull($record);
        $this->assertSame('Khách thường', $record['customerType']);
        $this->assertSame(['Cám cá'], $record['sourceSheets']);
        $this->assertNotNull($record['camCa']);
        $this->assertSame('48581875', $record['camCa']['grandTotal']);
    }

    /**
     * @param Collection<string, array<string, mixed>> $records
     */
    private function assertAggregatedRecord11008(Collection $records): void
    {
        $record = $records->get('11008');

        $this->assertNotNull($record);
        $this->assertSame('Key Account', $record['customerType']);
        $this->assertSame(['Key Account'], $record['sourceSheets']);
        $this->assertNull($record['tongHop']);
        $this->assertNull($record['khoanNpp']);
        $this->assertNull($record['camCa']);
        $this->assertNotNull($record['keyAccount']);
        $this->assertSame('147061500', $record['keyAccount']['grandTotal']);
    }
}
