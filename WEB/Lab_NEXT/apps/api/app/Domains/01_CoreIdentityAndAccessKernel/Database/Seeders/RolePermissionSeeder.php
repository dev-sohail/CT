<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Database\Seeders;

use App\Domains\CoreIdentityAndAccessKernel\Models\Permission;
use App\Domains\CoreIdentityAndAccessKernel\Models\Role;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign_role',
            'dashboard.view', 'admin.access',
        ];

        foreach ($permissions as $slug) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => str_replace('.', ' ', ucwords($slug)), 'group' => 'identity']
            );
        }

        $owner = Role::firstOrCreate(
            ['slug' => 'owner'],
            ['name' => 'Owner', 'is_system' => true, 'description' => 'Full access to everything.']
        );
        $owner->permissions()->sync(Permission::pluck('id')->all());

        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'is_system' => true, 'description' => 'Administrative access.']
        );
        $admin->permissions()->sync(
            Permission::where('slug', '!=', 'admin.access')->pluck('id')->all()
        );

        $member = Role::firstOrCreate(
            ['slug' => 'member'],
            ['name' => 'Member', 'is_system' => true, 'description' => 'Default personal user.']
        );
        $member->permissions()->sync(
            Permission::whereIn('slug', ['users.view', 'dashboard.view'])->pluck('id')->all()
        );

        $ownerEmail = config('ctlab.owner.email', 'owner@ctlab.local');
        $ownerPassword = config('ctlab.owner.password', 'admin123');

        if (! User::where('email', $ownerEmail)->exists()) {
            $user = User::create([
                'name' => config('ctlab.owner.name', 'Platform Owner'),
                'email' => $ownerEmail,
                'password' => Hash::make($ownerPassword),
            ]);
            $user->assignRole('owner');
        }
    }
}
