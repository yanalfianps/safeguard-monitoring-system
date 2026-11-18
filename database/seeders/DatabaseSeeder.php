<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Only provision master operational data safely without overwriting user accounts or dummy datasets.
        $this->call([
            AdminUserSeeder::class,
            DailyWorkCompanySeeder::class,
        ]);
    }
}
