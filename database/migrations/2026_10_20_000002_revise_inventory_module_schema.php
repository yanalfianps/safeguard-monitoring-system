<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update inventory_items table
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'stock_warehouse')) {
                $table->unsignedInteger('stock_warehouse')->default(0)->after('stock');
            }
            if (!Schema::hasColumn('inventory_items', 'stock_hsse')) {
                $table->unsignedInteger('stock_hsse')->default(0)->after('stock_warehouse');
            }
            if (!Schema::hasColumn('inventory_items', 'expired_date')) {
                $table->date('expired_date')->nullable()->after('keterangan');
            }
            if (!Schema::hasColumn('inventory_items', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('expired_date');
            }
        });

        // 2. Update inventory_transactions table
        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_transactions', 'transaction_code')) {
                $table->string('transaction_code', 60)->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('inventory_transactions', 'location')) {
                $table->string('location', 100)->default('Warehouse')->after('jenis_transaksi');
            }
            if (!Schema::hasColumn('inventory_transactions', 'satuan')) {
                $table->string('satuan', 50)->nullable()->after('location');
            }
            if (!Schema::hasColumn('inventory_transactions', 'expired_date')) {
                $table->date('expired_date')->nullable()->after('keterangan');
            }
        });

        // 3. Update inventory_borrowings table
        Schema::table('inventory_borrowings', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_borrowings', 'location')) {
                $table->string('location', 100)->default('Warehouse')->after('inventory_item_id');
            }
            if (!Schema::hasColumn('inventory_borrowings', 'due_date_history')) {
                $table->json('due_date_history')->nullable()->after('expected_return_date');
            }
        });

        // 4. Create inventory_borrowing_returns table for multi-step partial/full return history
        if (!Schema::hasTable('inventory_borrowing_returns')) {
            Schema::create('inventory_borrowing_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_borrowing_id')
                    ->constrained('inventory_borrowings')
                    ->cascadeOnDelete();
                $table->unsignedInteger('return_number')->default(1);
                $table->unsignedInteger('quantity_returned');
                $table->dateTime('return_date');
                $table->string('return_location', 100)->default('Warehouse');
                $table->string('condition', 100)->nullable();
                $table->string('recorded_by', 150)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['inventory_borrowing_id', 'return_number'], 'idx_borrowing_return_num');
            });
        }

        // 5. Safely migrate & normalize existing data without deleting anything
        $items = DB::table('inventory_items')->get();
        foreach ($items as $item) {
            $currentWarehouse = (int) ($item->stock_warehouse ?? 0);
            $currentHsse = (int) ($item->stock_hsse ?? 0);

            if ($currentWarehouse === 0 && $currentHsse === 0) {
                $rawStock = (int) ($item->stock ?? $item->stok_sekarang ?? 0);

                // Subtract active borrowed quantity if previous model didn't deduct stock on borrow
                $activeBorrowed = 0;
                if (Schema::hasTable('inventory_borrowings')) {
                    $activeBorrowed = (int) DB::table('inventory_borrowings')
                        ->where('inventory_item_id', $item->id)
                        ->whereRaw('quantity_borrowed > quantity_returned')
                        ->selectRaw('COALESCE(SUM(quantity_borrowed - quantity_returned), 0) as total')
                        ->value('total');
                }

                $availableStock = max(0, $rawStock - $activeBorrowed);
                $locRaw = strtolower(trim((string) ($item->location ?? $item->lokasi_default ?? '')));

                if (str_contains($locRaw, 'hsse')) {
                    $currentHsse = $availableStock;
                } else {
                    $currentWarehouse = $availableStock;
                }

                $totalAvailable = $currentWarehouse + $currentHsse;

                DB::table('inventory_items')
                    ->where('id', $item->id)
                    ->update([
                        'stock_warehouse' => $currentWarehouse,
                        'stock_hsse' => $currentHsse,
                        'stock' => $totalAvailable,
                        'stok_sekarang' => $totalAvailable,
                    ]);
            }
        }

        // Backfill transaction_code, location, and satuan on inventory_transactions
        $itemMap = DB::table('inventory_items')->get()->keyBy('id');
        $transactions = DB::table('inventory_transactions')->get();
        foreach ($transactions as $trx) {
            $updates = [];
            if (empty($trx->transaction_code)) {
                $datePart = $trx->created_at ? date('Ymd', strtotime((string) $trx->created_at)) : date('Ymd');
                $updates['transaction_code'] = 'TRX-' . $datePart . '-' . str_pad((string) $trx->id, 4, '0', STR_PAD_LEFT);
            }

            $locVal = trim((string) ($trx->location ?? ''));
            if (!in_array($locVal, ['Warehouse', 'Gudang HSSE'], true)) {
                $unitRaw = strtolower(trim((string) ($trx->unit ?? '')));
                $linkedItem = $itemMap->get($trx->inventory_item_id);
                $itemLocRaw = strtolower(trim((string) ($linkedItem->location ?? $linkedItem->lokasi_default ?? '')));
                if (str_contains($unitRaw, 'hsse') || str_contains($itemLocRaw, 'hsse')) {
                    $updates['location'] = 'Gudang HSSE';
                } else {
                    $updates['location'] = 'Warehouse';
                }
            }

            if (empty($trx->satuan)) {
                $linkedItem = $itemMap->get($trx->inventory_item_id);
                $updates['satuan'] = $linkedItem->satuan ?? $linkedItem->unit_measure ?? 'Box';
            }

            if (!empty($updates)) {
                DB::table('inventory_transactions')->where('id', $trx->id)->update($updates);
            }
        }

        // Backfill borrowings location & initial return records
        if (Schema::hasTable('inventory_borrowings')) {
            $borrowings = DB::table('inventory_borrowings')->get();
            foreach ($borrowings as $b) {
                $locVal = trim((string) ($b->location ?? ''));
                if (!in_array($locVal, ['Warehouse', 'Gudang HSSE'], true)) {
                    $linkedItem = $itemMap->get($b->inventory_item_id);
                    $itemLocRaw = strtolower(trim((string) ($linkedItem->location ?? $linkedItem->lokasi_default ?? '')));
                    $locVal = str_contains($itemLocRaw, 'hsse') ? 'Gudang HSSE' : 'Warehouse';
                    DB::table('inventory_borrowings')->where('id', $b->id)->update(['location' => $locVal]);
                }

                if ((int) $b->quantity_returned > 0) {
                    $hasReturnRows = DB::table('inventory_borrowing_returns')
                        ->where('inventory_borrowing_id', $b->id)
                        ->exists();

                    if (!$hasReturnRows) {
                        DB::table('inventory_borrowing_returns')->insert([
                            'inventory_borrowing_id' => $b->id,
                            'return_number' => 1,
                            'quantity_returned' => (int) $b->quantity_returned,
                            'return_date' => $b->actual_return_date ?? $b->updated_at ?? now(),
                            'return_location' => $locVal,
                            'condition' => 'Baik',
                            'recorded_by' => $b->recorded_by ?? 'Petugas HSSE',
                            'notes' => 'Pengembalian tercatat sebelumnya',
                            'created_at' => $b->actual_return_date ?? $b->updated_at ?? now(),
                            'updated_at' => $b->actual_return_date ?? $b->updated_at ?? now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_borrowing_returns');

        Schema::table('inventory_borrowings', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_borrowings', 'due_date_history')) {
                $table->dropColumn('due_date_history');
            }
            if (Schema::hasColumn('inventory_borrowings', 'location')) {
                $table->dropColumn('location');
            }
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $cols = array_filter(['transaction_code', 'location', 'satuan', 'expired_date'], fn ($c) => Schema::hasColumn('inventory_transactions', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $cols = array_filter(['stock_warehouse', 'stock_hsse', 'expired_date', 'is_active'], fn ($c) => Schema::hasColumn('inventory_items', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

