<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_view_user_management_screen(): void
    {
        $admin = User::query()->where('email', 'admin@rebatemailer.test')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
    }

    public function test_non_admin_user_cannot_view_user_management_screen(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_user_with_a_role(): void
    {
        $admin = User::query()->where('email', 'admin@rebatemailer.test')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Điều phối viên',
            'email' => 'ops@rebatemailer.test',
            'role' => 'Người dùng',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('users.index', absolute: false));

        $createdUser = User::query()->where('email', 'ops@rebatemailer.test')->firstOrFail();

        $this->assertTrue($createdUser->hasRole('Người dùng'));
    }
}
