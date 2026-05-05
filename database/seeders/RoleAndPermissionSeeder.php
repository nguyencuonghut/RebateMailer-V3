<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Authorization\PermissionName;
use App\Support\Authorization\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();

        $permissions = collect(PermissionName::values())
            ->mapWithKeys(fn (string $permission): array => [
                $permission => Permission::findOrCreate($permission, 'web'),
            ]);

        $adminRole = Role::findOrCreate(RoleName::Admin->value, 'web');
        $userRole = Role::findOrCreate(RoleName::User->value, 'web');
        $guestRole = Role::findOrCreate(RoleName::Guest->value, 'web');

        $adminRole->syncPermissions($permissions->values());
        $userRole->syncPermissions($this->resolvePermissions($permissions, PermissionName::nonUserCrudValues()));
        $guestRole->syncPermissions($this->resolvePermissions($permissions, PermissionName::guestValues()));

        $this->seedDefaultUsers($adminRole, $userRole, $guestRole);

        $permissionRegistrar->forgetCachedPermissions();
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Permission>  $permissions
     * @param  list<string>  $names
     * @return list<Permission>
     */
    private function resolvePermissions($permissions, array $names): array
    {
        return array_values(array_map(
            static fn (string $name): Permission => $permissions->get($name),
            $names,
        ));
    }

    private function seedDefaultUsers(Role $adminRole, Role $userRole, Role $guestRole): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@rebatemailer.test'],
            ['name' => 'Quản trị hệ thống', 'password' => 'password']
        );
        $admin->syncRoles([$adminRole]);

        $user = User::query()->firstOrCreate(
            ['email' => 'user@rebatemailer.test'],
            ['name' => 'Người dùng nghiệp vụ', 'password' => 'password']
        );
        $user->syncRoles([$userRole]);

        $guest = User::query()->firstOrCreate(
            ['email' => 'guest@rebatemailer.test'],
            ['name' => 'Người dùng khách', 'password' => 'password']
        );
        $guest->syncRoles([$guestRole]);
    }
}
