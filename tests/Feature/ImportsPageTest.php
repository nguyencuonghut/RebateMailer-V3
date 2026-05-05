<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ImportsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_imports_page_renders_through_inertia_with_backend_driven_toast_message(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $response = $this->actingAs($user)->get(route('imports.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Imports/Index')
                ->where('title', 'Import dữ liệu')
                ->where('uploadPolicy.acceptedExtension', '.xlsx')
                ->where('toast.summary', 'Khu vực import đã sẵn sàng')
                ->where('toast.detail', 'Bạn có thể bắt đầu với bước chọn file Excel chiết khấu tháng.')
            );
    }

    public function test_guest_role_can_open_imports_page_when_it_has_imports_view_permission(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)
            ->get(route('imports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Imports/Index'));
    }
}
