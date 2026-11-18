<?php

namespace Database\Seeders;

use App\Models\DailyWorkCompany;
use Illuminate\Database\Seeder;

/**
 * Inisialisasi 16 perusahaan wajib Master Data Daily Work.
 * Idempotent: aman dijalankan berulang kali tanpa membuat duplikasi
 * dan tanpa menimpa status/kategori/induk yang sudah diatur pengguna.
 *
 * php artisan db:seed --class=DailyWorkCompanySeeder
 */
class DailyWorkCompanySeeder extends Seeder
{
    public function run(): void
    {
        DailyWorkCompany::syncRequired();
    }
}

