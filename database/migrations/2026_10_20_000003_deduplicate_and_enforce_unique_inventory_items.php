<?php

use App\Models\InventoryItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $items = DB::table('inventory_items')->orderBy('id', 'asc')->get();

            // 1. Bersihkan sufiks uniqid heksadesimal otomatis (contoh: "Absorbent Pad Oil 6ad6cc84d166b")
            // serta sufiks "Updated" dari pengujian otomatis
            foreach ($items as $item) {
                $rawName = trim((string) ($item->nama_barang ?: $item->item_name));
                $cleanName = preg_replace('/\s+6ad6[0-9a-f]{8,12}$/i', '', $rawName);
                if (preg_match('/^Sarung Tangan Uji Updated$/i', $cleanName)) {
                    $cleanName = 'Sarung Tangan Uji';
                }

                if ($cleanName !== $rawName) {
                    DB::table('inventory_items')
                        ->where('id', $item->id)
                        ->update([
                            'item_name' => $cleanName,
                            'nama_barang' => $cleanName,
                        ]);
                }
            }

            // Refresh daftar item setelah pembersihan nama
            $items = DB::table('inventory_items')->orderBy('id', 'asc')->get();

            // 2. Kelompokkan duplikat yang benar-benar merupakan barang yang sama:
            // Berdasarkan kombinasi ternormalisasi (nama_barang + kategori + satuan)
            // atau duplikat dengan nama_barang & kategori identik.
            $groups = [];
            foreach ($items as $item) {
                $nameKey = mb_strtolower(trim((string) ($item->nama_barang ?: $item->item_name)));
                $catKey = mb_strtolower(trim((string) ($item->kategori ?: $item->category)));
                $unitKey = mb_strtolower(trim((string) ($item->satuan ?: $item->unit_measure)));

                $groupKey = $nameKey . '||' . $catKey . '||' . $unitKey;

                // Cek apakah sudah ada grup dengan nama & kategori sama
                foreach ($groups as $existingKey => $groupItems) {
                    [$exName, $exCat] = explode('||', $existingKey);
                    if ($exName === $nameKey && $exCat === $catKey) {
                        $groupKey = $existingKey;
                        break;
                    }
                }

                $groups[$groupKey][] = $item;
            }

            // 3. Gabungkan setiap grup yang memiliki lebih dari 1 baris duplikat secara aman
            foreach ($groups as $groupItems) {
                if (count($groupItems) <= 1) {
                    continue;
                }

                // Pilih item kanonik: utamakan yang bukan kode TEST-* (misal EMG-0032, DET-0028, APD-001),
                // atau jika semuanya TEST-*, pilih ID paling awal.
                $canonical = null;
                foreach ($groupItems as $candidate) {
                    $cCode = strtoupper(trim((string) ($candidate->kode_barang ?: $candidate->item_code)));
                    if (!str_starts_with($cCode, 'TEST-')) {
                        $canonical = $candidate;
                        break;
                    }
                }
                if (!$canonical) {
                    $canonical = $groupItems[0];
                }

                // Pilih nilai stok & expired_date terbaik dari item terbaru yang aktif di grup tersebut
                $latestItem = end($groupItems);
                $bestWhStock = max(0, (int) $latestItem->stock_warehouse);
                $bestHsseStock = max(0, (int) $latestItem->stock_hsse);
                $bestExpired = $canonical->expired_date ?: $latestItem->expired_date;

                foreach ($groupItems as $dup) {
                    if ((int) $dup->id === (int) $canonical->id) {
                        continue;
                    }

                    if (!$bestExpired && !empty($dup->expired_date)) {
                        $bestExpired = $dup->expired_date;
                    }

                    // Relasikan seluruh transaksi & peminjaman dari duplikat ke item kanonik
                    DB::table('inventory_transactions')
                        ->where('inventory_item_id', $dup->id)
                        ->update(['inventory_item_id' => $canonical->id]);

                    DB::table('inventory_borrowings')
                        ->where('inventory_item_id', $dup->id)
                        ->update(['inventory_item_id' => $canonical->id]);

                    // Hapus baris master duplikat yang sudah dipindahkan seluruh relasinya
                    DB::table('inventory_items')->where('id', $dup->id)->delete();
                }

                // Perbarui stok dan expired_date pada item kanonik
                $totalStock = $bestWhStock + $bestHsseStock;
                DB::table('inventory_items')
                    ->where('id', $canonical->id)
                    ->update([
                        'stock_warehouse' => $bestWhStock,
                        'stock_hsse' => $bestHsseStock,
                        'stock' => $totalStock,
                        'stok_sekarang' => $totalStock,
                        'expired_date' => $bestExpired,
                    ]);
            }

            // Pastikan seluruh kode_barang terisi sama dengan item_code, bersih dari prefix TEST-*, dan unik
            $remainingItems = DB::table('inventory_items')->orderBy('id', 'asc')->get();
            $seenCodes = [];
            foreach ($remainingItems as $rem) {
                $code = trim((string) ($rem->kode_barang ?: $rem->item_code));
                if ($code === '' || str_starts_with(strtoupper($code), 'TEST-') || isset($seenCodes[strtoupper($code)])) {
                    $code = InventoryItem::generateUniqueItemCode($rem->kategori ?: $rem->category);
                }
                $seenCodes[strtoupper($code)] = true;

                DB::table('inventory_items')
                    ->where('id', $rem->id)
                    ->update([
                        'item_code' => $code,
                        'kode_barang' => $code,
                    ]);
            }
        });

        // 4. Tambahkan unique index pada kode_barang jika belum ada
        $indexes = collect(DB::select("SHOW INDEX FROM inventory_items"))->pluck('Key_name')->all();
        if (!in_array('inventory_items_kode_barang_unique', $indexes, true)) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->unique('kode_barang', 'inventory_items_kode_barang_unique');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM inventory_items"))->pluck('Key_name')->all();
        if (in_array('inventory_items_kode_barang_unique', $indexes, true)) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropUnique('inventory_items_kode_barang_unique');
            });
        }
    }
};

