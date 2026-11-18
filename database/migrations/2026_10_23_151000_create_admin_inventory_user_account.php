<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations to safely and idempotently provision the admin_inventory account.
     */
    public function up(): void
    {
        $existing = User::where('email', 'inventory@geodipa.co.id')->first();
        if (!$existing) {
            User::create([
                'name' => 'Admin Inventory',
                'email' => 'inventory@geodipa.co.id',
                'password' => Hash::make(env('DEFAULT_INVENTORY_PASSWORD', 'Inventory123')),
                'role' => User::ROLE_ADMIN_INVENTORY,
                'status' => 'Aktif',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep user data intact
    }
};

