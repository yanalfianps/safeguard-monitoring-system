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
        if (!Schema::hasTable('inventory_units')) {
            Schema::create('inventory_units', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Daftar 12 satuan standar awal
        $defaultUnits = [
            'Meter',
            'Liter',
            'Botol',
            'Mililiter',
            'Gram',
            'Kilogram',
            'Box',
            'Batang',
            'cm',
            'Ton',
            'Drum',
            'Inch',
        ];

        // Kumpulkan juga satuan yang sudah pernah dicatat di inventory_items & transactions
        $existingItemUnits = [];
        if (Schema::hasTable('inventory_items')) {
            $existingItemUnits = DB::table('inventory_items')
                ->whereNotNull('satuan')
                ->where('satuan', '!=', '')
                ->distinct()
                ->pluck('satuan')
                ->all();
        }

        $existingTrxUnits = [];
        if (Schema::hasTable('inventory_transactions')) {
            $existingTrxUnits = DB::table('inventory_transactions')
                ->whereNotNull('satuan')
                ->where('satuan', '!=', '')
                ->distinct()
                ->pluck('satuan')
                ->all();
        }

        $allUnits = array_unique(array_merge($defaultUnits, $existingItemUnits, $existingTrxUnits));
        $now = now();

        foreach ($allUnits as $unitName) {
            $trimmed = trim((string) $unitName);
            if ($trimmed === '') {
                continue;
            }

            // Cek apakah sudah ada (case-insensitive)
            $exists = DB::table('inventory_units')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])
                ->exists();

            if (!$exists) {
                DB::table('inventory_units')->insert([
                    'name' => $trimmed,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_units');
    }
};

