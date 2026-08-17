<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \App\Domains\CoreIdentityAndAccessKernel\Database\Seeders\RolePermissionSeeder::class,
        ]);
    }
}
