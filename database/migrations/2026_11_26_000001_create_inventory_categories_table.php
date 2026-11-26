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
        if (!Schema::hasTable('inventory_categories')) {
            Schema::create('inventory_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Kumpulkan kategori standar awal
        $defaultCategories = InventoryItem::CATEGORIES;

        // Kumpulkan juga kategori yang sudah pernah dicatat di inventory_items agar data lama tetap terjaga
        $existingKategori = [];
        $existingCategory = [];
        if (Schema::hasTable('inventory_items')) {
            $existingKategori = DB::table('inventory_items')
                ->whereNotNull('kategori')
                ->where('kategori', '!=', '')
                ->distinct()
                ->pluck('kategori')
                ->all();

            $existingCategory = DB::table('inventory_items')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category')
                ->all();
        }

        $allCategories = array_unique(array_merge($defaultCategories, $existingKategori, $existingCategory));
        $now = now();

        foreach ($allCategories as $catName) {
            $trimmed = preg_replace('/\s+/', ' ', trim((string) $catName));
            if ($trimmed === '') {
                continue;
            }

            // Cek apakah sudah ada (case-insensitive)
            $exists = DB::table('inventory_categories')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])
                ->exists();

            if (!$exists) {
                DB::table('inventory_categories')->insert([
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
        Schema::dropIfExists('inventory_categories');
    }
};
