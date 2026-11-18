<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom Inspeksi pada tabel environment_ppich_records (mempertahankan data historis PPKH/PPICH)
        if (Schema::hasTable('environment_ppich_records')) {
            Schema::table('environment_ppich_records', function (Blueprint $table) {
                if (!Schema::hasColumn('environment_ppich_records', 'inspection_type')) {
                    $table->string('inspection_type', 150)->nullable()->after('id');
                }
                if (!Schema::hasColumn('environment_ppich_records', 'inspection_type_id')) {
                    $table->foreignId('inspection_type_id')->nullable()->after('inspection_type')->constrained('environment_masters')->nullOnDelete();
                }
                if (!Schema::hasColumn('environment_ppich_records', 'condition')) {
                    $table->text('condition')->nullable()->after('location_name');
                }
                if (!Schema::hasColumn('environment_ppich_records', 'inspection_date')) {
                    $table->date('inspection_date')->nullable()->after('sampling_date');
                }
            });
        }

        // 2. Buat tabel pencatatan Cuaca tersendiri (environment_weather_records)
        if (!Schema::hasTable('environment_weather_records')) {
            Schema::create('environment_weather_records', function (Blueprint $table) {
                $table->id();
                $table->date('recording_date');
                $table->time('recording_time')->nullable();
                $table->foreignId('location_id')->nullable()->constrained('environment_masters')->nullOnDelete();
                $table->string('location_name', 150)->nullable();
                $table->decimal('wind_speed', 8, 2)->comment('Kecepatan Angin (m/s)');
                $table->decimal('rainfall', 8, 2)->comment('Curah Hujan (mm/hr)');
                $table->decimal('temperature', 8, 2)->comment('Temperatur (°C)');
                $table->decimal('humidity', 8, 2)->comment('Humidity (%)');
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. Seed Master Data baru (Inspeksi, Lokasi Inspeksi, Parameter Cuaca, dan Satuan Cuaca)
        $now = now();

        // A. Master Jenis Inspeksi
        $inspectionTypes = [
            ['name' => 'Spill Kit', 'code' => 'SPILL_KIT', 'order_index' => 1],
            ['name' => 'Tempat Sampah', 'code' => 'TEMPAT_SAMPAH', 'order_index' => 2],
            ['name' => 'TPS LB3', 'code' => 'TPS_LB3', 'order_index' => 3],
            ['name' => 'PPKH/Lahan', 'code' => 'PPKH_LAHAN', 'order_index' => 4],
        ];
        foreach ($inspectionTypes as $it) {
            $exists = DB::table('environment_masters')
                ->where('category', 'inspection_type')
                ->where('name', $it['name'])
                ->exists();
            if (!$exists) {
                DB::table('environment_masters')->insert([
                    'category' => 'inspection_type',
                    'name' => $it['name'],
                    'code' => $it['code'],
                    'order_index' => $it['order_index'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // B. Master Lokasi Inspeksi
        $inspectionLocations = [
            ['name' => 'Area Well Pad', 'code' => 'LOC_WELL_PAD', 'order_index' => 1],
            ['name' => 'Area PP', 'code' => 'LOC_PP', 'order_index' => 2],
            ['name' => 'Temporary Office', 'code' => 'LOC_TEMP_OFFICE', 'order_index' => 3],
            ['name' => 'SK 2013', 'code' => 'SK_2013', 'order_index' => 4],
            ['name' => 'SK 2024', 'code' => 'SK_2024', 'order_index' => 5],
        ];
        foreach ($inspectionLocations as $il) {
            $exists = DB::table('environment_masters')
                ->where('category', 'inspection_location')
                ->where('name', $il['name'])
                ->exists();
            if (!$exists) {
                DB::table('environment_masters')->insert([
                    'category' => 'inspection_location',
                    'name' => $il['name'],
                    'code' => $il['code'],
                    'order_index' => $il['order_index'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // C. Master Parameter Cuaca
        $weatherParameters = [
            ['name' => 'Kecepatan Angin', 'code' => 'WIND_SPEED', 'description' => 'Satuan: m/s', 'order_index' => 1],
            ['name' => 'Curah Hujan', 'code' => 'RAINFALL', 'description' => 'Satuan: mm/hr', 'order_index' => 2],
            ['name' => 'Temperatur', 'code' => 'TEMPERATURE', 'description' => 'Satuan: °C', 'order_index' => 3],
            ['name' => 'Humidity', 'code' => 'HUMIDITY', 'description' => 'Satuan: %', 'order_index' => 4],
        ];
        foreach ($weatherParameters as $wp) {
            $exists = DB::table('environment_masters')
                ->where('category', 'weather_parameter')
                ->where('name', $wp['name'])
                ->exists();
            if (!$exists) {
                DB::table('environment_masters')->insert([
                    'category' => 'weather_parameter',
                    'name' => $wp['name'],
                    'code' => $wp['code'],
                    'description' => $wp['description'],
                    'order_index' => $wp['order_index'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // D. Master Satuan Cuaca (m/s dan mm/hr)
        $weatherUnits = [
            ['name' => 'm/s', 'code' => 'M_S', 'order_index' => 20, 'submodules' => json_encode(['cuaca'])],
            ['name' => 'mm/hr', 'code' => 'MM_HR', 'order_index' => 21, 'submodules' => json_encode(['cuaca'])],
        ];
        foreach ($weatherUnits as $wu) {
            $exists = DB::table('environment_masters')
                ->where('category', 'unit')
                ->where('name', $wu['name'])
                ->exists();
            if (!$exists) {
                DB::table('environment_masters')->insert([
                    'category' => 'unit',
                    'name' => $wu['name'],
                    'code' => $wu['code'],
                    'order_index' => $wu['order_index'],
                    'submodules' => $wu['submodules'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Update satuan °C dan % agar submodules menyertakan cuaca
        $celsius = DB::table('environment_masters')->where('category', 'unit')->where('name', '°C')->first();
        if ($celsius) {
            $subs = json_decode($celsius->submodules ?? '[]', true) ?: [];
            if (!in_array('cuaca', $subs)) {
                $subs[] = 'cuaca';
                DB::table('environment_masters')->where('id', $celsius->id)->update(['submodules' => json_encode(array_values(array_unique($subs)))]);
            }
        }

        $pct = DB::table('environment_masters')->where('category', 'unit')->where('name', '%')->first();
        if ($pct) {
            $subs = json_decode($pct->submodules ?? '[]', true) ?: [];
            if (!in_array('cuaca', $subs)) {
                $subs[] = 'cuaca';
                DB::table('environment_masters')->where('id', $pct->id)->update(['submodules' => json_encode(array_values(array_unique($subs)))]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environment_weather_records');

        if (Schema::hasTable('environment_ppich_records')) {
            Schema::table('environment_ppich_records', function (Blueprint $table) {
                if (Schema::hasColumn('environment_ppich_records', 'inspection_type_id')) {
                    $table->dropForeign(['inspection_type_id']);
                    $table->dropColumn('inspection_type_id');
                }
                if (Schema::hasColumn('environment_ppich_records', 'inspection_type')) {
                    $table->dropColumn('inspection_type');
                }
                if (Schema::hasColumn('environment_ppich_records', 'condition')) {
                    $table->dropColumn('condition');
                }
                if (Schema::hasColumn('environment_ppich_records', 'inspection_date')) {
                    $table->dropColumn('inspection_date');
                }
            });
        }
    }
};

