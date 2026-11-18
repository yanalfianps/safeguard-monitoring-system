<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Normalize existing roles in users table
        DB::table('users')
            ->where('role', 'Admin')
            ->orWhere('role', 'admin')
            ->update(['role' => User::ROLE_ADMIN_MANAGEMENT]);

        DB::table('users')
            ->where('role', 'User')
            ->orWhere('role', 'user')
            ->update(['role' => User::ROLE_USER]);

        // 2. Ensure Primary Admin Management account exists (never overwrite existing account credentials/status)
        $primaryAdmin = DB::table('users')->where('email', 'admin@geodipa.co.id')->first();
        if (!$primaryAdmin) {
            DB::table('users')->insert([
                'name' => 'Administrator Dieng',
                'email' => 'admin@geodipa.co.id',
                'password' => Hash::make(env('DEFAULT_ADMIN_PASSWORD', 'Admin123')),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Ensure Default Admin Inventory account exists (never overwrite existing account credentials/status)
        $inventoryAdmin = DB::table('users')->where('email', 'inventory@geodipa.co.id')->first();
        if (!$inventoryAdmin) {
            DB::table('users')->insert([
                'name' => 'Admin Inventory',
                'email' => 'inventory@geodipa.co.id',
                'password' => Hash::make(env('DEFAULT_INVENTORY_PASSWORD', 'Inventory123')),
                'role' => User::ROLE_ADMIN_INVENTORY,
                'status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('role', User::ROLE_ADMIN_MANAGEMENT)
            ->update(['role' => 'Admin']);

        DB::table('users')
            ->where('role', User::ROLE_USER)
            ->update(['role' => 'User']);
    }
};

