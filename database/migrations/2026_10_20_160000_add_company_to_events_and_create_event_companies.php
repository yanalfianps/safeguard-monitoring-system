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
        if (Schema::hasTable('events') && !Schema::hasColumn('events', 'company')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('company', 150)->nullable()->after('title');
            });

            // Backfill existing events with company from unit or default Geo Dipa
            DB::table('events')->whereNull('company')->orWhere('company', '')->update([
                'company' => DB::raw("COALESCE(NULLIF(unit, ''), 'PT Geo Dipa Energi (Persero)')"),
            ]);
        }

        if (!Schema::hasTable('event_companies')) {
            Schema::create('event_companies', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->unique();
                $table->string('category', 100)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed default initial companies
            $defaults = [
                ['name' => 'EBTKE', 'category' => 'Regulator', 'is_active' => true],
                ['name' => 'PT Geo Dipa Energi (Persero)', 'category' => 'Internal', 'is_active' => true],
                ['name' => 'PLN', 'category' => 'Mitra BUMN', 'is_active' => true],
                ['name' => 'Kementerian ESDM', 'category' => 'Kementerian', 'is_active' => true],
            ];

            foreach ($defaults as $item) {
                DB::table('event_companies')->updateOrInsert(
                    ['name' => $item['name']],
                    [
                        'category' => $item['category'],
                        'is_active' => $item['is_active'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'company')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('company');
            });
        }

        Schema::dropIfExists('event_companies');
    }
};

