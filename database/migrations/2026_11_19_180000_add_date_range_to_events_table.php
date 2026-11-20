<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                // 1. Tambah kolom start_date jika belum ada (additive & aman)
                if (!Schema::hasColumn('events', 'start_date')) {
                    $table->date('start_date')->nullable()->after('event_date');
                }

                // 2. Tambah kolom end_date jika belum ada (additive & aman)
                if (!Schema::hasColumn('events', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }

                // 3. Perlebar panjang karakter safeguard_function untuk menampung multiple values
                if (Schema::hasColumn('events', 'safeguard_function')) {
                    $table->string('safeguard_function', 150)->default('Health & Safety')->change();
                }
            });

            // 4. Backfill aman: salin nilai event_date ke start_date dan end_date untuk seluruh data lama
            DB::table('events')
                ->whereNotNull('event_date')
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhereNull('end_date');
                })
                ->update([
                    'start_date' => DB::raw('COALESCE(start_date, event_date)'),
                    'end_date' => DB::raw('COALESCE(end_date, event_date)'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Perubahan struktur tabel (kolom start_date, end_date, event_date, serta panjang safeguard_function 150)
        // sengaja dipertahankan saat rollback untuk menjaga kompatibilitas dan mencegah kehilangan data (data loss)
        // karena kolom-kolom tersebut mungkin sudah terisi data rentang tanggal kegiatan atau pilihan multiple safeguard yang baru.
    }
};
