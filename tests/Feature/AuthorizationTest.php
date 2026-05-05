<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Authorization\PermissionName;
use App\Support\Authorization\RoleName;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_has_full_permissions(): void
    {
        $admin = User::query()->where('email', 'admin@rebatemailer.test')->firstOrFail();

        $this->assertTrue($admin->hasRole(RoleName::Admin->value));

        foreach (PermissionName::values() as $permission) {
            $this->assertTrue($admin->can($permission), "Admin thiếu quyền {$permission}");
        }
    }

    public function test_user_has_all_permissions_except_user_crud(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $this->assertTrue($user->hasRole(RoleName::User->value));

        foreach (PermissionName::nonUserCrudValues() as $permission) {
            $this->assertTrue($user->can($permission), "Người dùng thiếu quyền {$permission}");
        }

        $this->assertFalse($user->can(PermissionName::UsersView->value));
        $this->assertFalse($user->can(PermissionName::UsersCreate->value));
        $this->assertFalse($user->can(PermissionName::UsersUpdate->value));
        $this->assertFalse($user->can(PermissionName::UsersDelete->value));
    }

    public function test_guest_can_only_view_import_and_mail_modules(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();

        $this->assertTrue($guest->hasRole(RoleName::Guest->value));
        $this->assertTrue($guest->can(PermissionName::ImportsView->value));
        $this->assertTrue($guest->can(PermissionName::MailView->value));

        $forbiddenPermissions = array_diff(
            PermissionName::values(),
            PermissionName::guestValues(),
        );

        foreach ($forbiddenPermissions as $permission) {
            $this->assertFalse($guest->can($permission), "Khách không được có quyền {$permission}");
        }
    }

    public function test_route_access_is_limited_by_permissions(): void
    {
        $guest = User::query()->where('email', 'guest@rebatemailer.test')->firstOrFail();
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();

        $this->actingAs($guest)->get(route('imports.index'))->assertOk();
        $this->actingAs($guest)->get(route('mail.index'))->assertOk();
        $this->actingAs($guest)->get(route('tracking.index'))->assertForbidden();

        $this->actingAs($user)->get(route('templates.index'))->assertOk();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }
}
