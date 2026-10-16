<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\UserRole;
use App\Models\User;

class SystemUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            [
                'email' => 'system@wave-clone.local',
            ],
            [
                'first_name' => 'System',
                'last_name' => 'Wallet',
                'phone' => '770000000',
                'role' => UserRole::SYSTEM,
                'password' => 'system-secret-password',
            ]
        );
    }
}
