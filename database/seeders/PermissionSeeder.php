<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\V1\User\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define permission groups
        $permissions = [
            'user_management' => [
                'view users',
                'create users',
                'edit users',
                'delete users',
            ],
            'role_management' => [
                'view roles',
                'create roles',
                'edit roles',
                'delete roles',
            ],
            'permission_management' => [
                'view permissions',
                'assign permissions',
            ],
            'creator_management' => [
                'view creators',
                'approve creators',
                'suspend creators',
            ],
            'content_management' => [
                'view content',
                'approve content',
                'delete content',
            ],
        ];

        // Seed permissions
        foreach ($permissions as $group => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm,
                    'slug' => Str::slug($perm),
                ]);
            }
        }
    }
}
