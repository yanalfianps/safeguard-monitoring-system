<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Reset/Truncate seluruh tabel master barang & transaksi terkait
     * agar database siap diisi bersih dari awal oleh pengguna.
     */
    public function run(): void
    {
        if (app()->environment('production') || !filter_var(env('ALLOW_DESTRUCTIVE_SEEDING', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('InventorySeeder (reset/truncate) diblokir untuk melindungi data operasional. Set ALLOW_DESTRUCTIVE_SEEDING=true di environment non-produksi jika benar-benar diperlukan.');
            return;
        }

        Schema::disableForeignKeyConstraints();

        DB::table('inventory_transactions')->truncate();
        if (Schema::hasTable('inventory_borrowings')) {
            DB::table('inventory_borrowings')->truncate();
        }
        DB::table('inventory_items')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->command->info('Tabel Master Barang (inventory_items), Mutasi Transaksi (inventory_transactions), dan Peminjaman (inventory_borrowings) telah berhasil dikosongkan (TRUNCATE).');
    }
}
