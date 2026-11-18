<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IncidentResetSeeder extends Seeder
{
    /**
     * Kosongkan tabel incidents agar bersih dari data dummy
     * dan siap digunakan untuk uji coba input manual dari awal.
     */
    public function run(): void
    {
        if (app()->environment('production') || !filter_var(env('ALLOW_DESTRUCTIVE_SEEDING', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('IncidentResetSeeder diblokir untuk melindungi data operasional. Set ALLOW_DESTRUCTIVE_SEEDING=true di environment non-produksi jika benar-benar diperlukan.');
            return;
        }

        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('incidents')) {
            DB::table('incidents')->truncate();
        }

        Schema::enableForeignKeyConstraints();

        $this->command?->info('Tabel incidents berhasil di-reset dan dikosongkan.');
    }
}
