<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel inventory_stock_batches untuk menyimpan kelompok stok per lokasi & tanggal expired
        if (!Schema::hasTable('inventory_stock_batches')) {
            Schema::create('inventory_stock_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_item_id')
                    ->constrained('inventory_items')
                    ->cascadeOnDelete();
                $table->string('location', 100)->default('Warehouse');
                $table->date('expired_date')->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->string('notes', 500)->nullable();
                $table->timestamps();

                $table->index(['inventory_item_id', 'location', 'expired_date'], 'idx_inv_stock_batches_lookup');
            });
        }

        // 2. Tambahkan kolom expired_date & expired_batches pada tabel inventory_borrowings
        Schema::table('inventory_borrowings', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_borrowings', 'expired_date')) {
                $table->date('expired_date')->nullable()->after('expected_return_date');
            }
            if (!Schema::hasColumn('inventory_borrowings', 'expired_batches')) {
                $table->json('expired_batches')->nullable()->after('expired_date');
            }
        });

        // 3. Sinkronisasi data stok awal per lokasi & kelompok tanggal expired dari data lama tanpa menghapus/mereset data
        $items = DB::table('inventory_items')->get();
        $now = now();

        foreach ($items as $item) {
            $hasBatches = DB::table('inventory_stock_batches')
                ->where('inventory_item_id', $item->id)
                ->exists();

            if ($hasBatches) {
                continue;
            }

            foreach (['Warehouse', 'Gudang HSSE'] as $loc) {
                $targetStock = $loc === 'Gudang HSSE'
                    ? max(0, (int) ($item->stock_hsse ?? 0))
                    : max(0, (int) ($item->stock_warehouse ?? 0));

                if ($targetStock <= 0) {
                    continue;
                }

                // Ambil transaksi masuk pada lokasi ini untuk melihat riwayat kelompok tanggal expired yang pernah dicatat
                $incomingByExp = DB::table('inventory_transactions')
                    ->where('inventory_item_id', $item->id)
                    ->where('location', $loc)
                    ->whereIn('jenis_transaksi', ['masuk', 'Masuk'])
                    ->select('expired_date', DB::raw('SUM(COALESCE(jumlah, quantity, 0)) as total_qty'))
                    ->groupBy('expired_date')
                    ->orderByRaw('CASE WHEN expired_date IS NULL THEN 0 ELSE 1 END ASC')
                    ->orderBy('expired_date', 'desc')
                    ->get();

                $remainingToAllocate = $targetStock;
                $allocatedRows = [];

                foreach ($incomingByExp as $grp) {
                    if ($remainingToAllocate <= 0) {
                        break;
                    }
                    $grpQty = max(0, (int) $grp->total_qty);
                    if ($grpQty <= 0) {
                        continue;
                    }
                    $take = min($remainingToAllocate, $grpQty);
                    $expDate = $grp->expired_date ?: null;
                    $key = $expDate ?? 'UNRECORDED';

                    if (!isset($allocatedRows[$key])) {
                        $allocatedRows[$key] = [
                            'expired_date' => $expDate,
                            'quantity' => 0,
                        ];
                    }
                    $allocatedRows[$key]['quantity'] += $take;
                    $remainingToAllocate -= $take;
                }

                // Sisa stok yang belum teralokasi dimasukkan ke kelompok expired master (atau null = Belum Dicatat, jangan mengarang tanggal)
                if ($remainingToAllocate > 0) {
                    $fallbackExp = $item->expired_date ?: null;
                    $key = $fallbackExp ?? 'UNRECORDED';
                    if (!isset($allocatedRows[$key])) {
                        $allocatedRows[$key] = [
                            'expired_date' => $fallbackExp,
                            'quantity' => 0,
                        ];
                    }
                    $allocatedRows[$key]['quantity'] += $remainingToAllocate;
                }

                foreach ($allocatedRows as $row) {
                    if ($row['quantity'] > 0) {
                        DB::table('inventory_stock_batches')->insert([
                            'inventory_item_id' => $item->id,
                            'location' => $loc,
                            'expired_date' => $row['expired_date'],
                            'quantity' => $row['quantity'],
                            'notes' => 'Sinkronisasi stok lokasi & kelompok expired',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        // 4. Lengkapi referensi expired_batches pada data peminjaman lama (jika belum tercatat, gunakan expired_date item atau null = Belum Dicatat)
        if (Schema::hasTable('inventory_borrowings')) {
            $itemMap = DB::table('inventory_items')->get()->keyBy('id');
            $borrowings = DB::table('inventory_borrowings')->get();

            foreach ($borrowings as $b) {
                if (!empty($b->expired_batches)) {
                    continue;
                }

                $linkedItem = $itemMap->get($b->inventory_item_id);
                $knownExp = $b->expired_date ?: ($linkedItem->expired_date ?? null);
                $loc = $b->location ?: 'Warehouse';

                $batchesPayload = [[
                    'batch_id' => null,
                    'location' => $loc,
                    'expired_date' => $knownExp,
                    'quantity_borrowed' => (int) $b->quantity_borrowed,
                    'quantity_returned' => (int) $b->quantity_returned,
                ]];

                DB::table('inventory_borrowings')
                    ->where('id', $b->id)
                    ->update([
                        'expired_date' => $knownExp,
                        'expired_batches' => json_encode($batchesPayload),
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('inventory_borrowings', function (Blueprint $table) {
            $cols = array_filter(['expired_date', 'expired_batches'], fn ($c) => Schema::hasColumn('inventory_borrowings', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::dropIfExists('inventory_stock_batches');
    }
};

