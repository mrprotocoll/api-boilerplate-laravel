<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\V1\User\Enums\RoleEnum;
use Modules\V1\User\Models\Role;

final class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = RoleEnum::names();
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'slug' => Str::slug($role)]);
        }
    }
}
