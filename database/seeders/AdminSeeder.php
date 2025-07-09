<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\V1\User\Models\Admin;
use Shared\Enums\StatusEnum;
use Shared\Helpers\DateTimeHelper;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $timestamp = DateTimeHelper::timestamp();

            Admin::firstOrCreate([
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => StatusEnum::ACTIVE,
                'super_admin' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        });
    }
}
