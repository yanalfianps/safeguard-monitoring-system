<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds to create or update the default Admin Management and Admin Inventory accounts safely.
     */
    public function run(): void
    {
        // Only insert default accounts if they do not exist yet; never overwrite existing credentials/status.
        User::firstOrCreate(
            ['email' => 'admin@geodipa.co.id'],
            [
                'name' => 'Administrator Dieng',
                'password' => Hash::make(env('DEFAULT_ADMIN_PASSWORD', 'Admin123')),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'Aktif',
            ]
        );

        User::firstOrCreate(
            ['email' => 'inventory@geodipa.co.id'],
            [
                'name' => 'Admin Inventory',
                'password' => Hash::make(env('DEFAULT_INVENTORY_PASSWORD', 'Inventory123')),
                'role' => User::ROLE_ADMIN_INVENTORY,
                'status' => 'Aktif',
            ]
        );

        // Default User
        User::firstOrCreate(
            ['email' => 'user@geodipa.co.id'],
            [
                'name' => 'Default User',
                'password' => Hash::make(env('DEFAULT_USER_PASSWORD', 'User123')),
                'role' => User::ROLE_USER,
                'status' => 'Aktif',
            ]
        );
    }
}
