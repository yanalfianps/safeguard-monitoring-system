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
        // 1. Master Data Environment
        Schema::create('environment_masters', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index(); // 'unit', 'lb3_characteristic', 'non_lb3_source', 'rkl_rpl_parameter', 'rkl_rpl_location', 'ppich_location', 'ppich_activity', 'ppich_reference', 'ews_location'
            $table->string('name', 150);
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seed initial Environment Masters
        $initialMasters = [
            // Units (Satuan)
            ['category' => 'unit', 'name' => 'Ton', 'code' => 'TON', 'order_index' => 1],
            ['category' => 'unit', 'name' => 'Kilogram (kg)', 'code' => 'KG', 'order_index' => 2],
            ['category' => 'unit', 'name' => 'Liter', 'code' => 'LTR', 'order_index' => 3],
            ['category' => 'unit', 'name' => 'Drum', 'code' => 'DRM', 'order_index' => 4],
            ['category' => 'unit', 'name' => 'Sak/karung', 'code' => 'SAK', 'order_index' => 5],
            ['category' => 'unit', 'name' => 'kWh', 'code' => 'KWH', 'order_index' => 6],

            // LB3 Characteristics
            ['category' => 'lb3_characteristic', 'name' => 'Mudah meledak', 'code' => 'EXPLOSIVE', 'order_index' => 1],
            ['category' => 'lb3_characteristic', 'name' => 'Mudah menyala', 'code' => 'FLAMMABLE', 'order_index' => 2],
            ['category' => 'lb3_characteristic', 'name' => 'Reaktif', 'code' => 'REACTIVE', 'order_index' => 3],
            ['category' => 'lb3_characteristic', 'name' => 'Infeksius', 'code' => 'INFECTIOUS', 'order_index' => 4],
            ['category' => 'lb3_characteristic', 'name' => 'Korosif', 'code' => 'CORROSIVE', 'order_index' => 5],
            ['category' => 'lb3_characteristic', 'name' => 'Beracun', 'code' => 'TOXIC', 'order_index' => 6],

            // Non-LB3 Sources
            ['category' => 'non_lb3_source', 'name' => 'Kantor', 'code' => 'KANTOR', 'order_index' => 1],
            ['category' => 'non_lb3_source', 'name' => 'Workshop', 'code' => 'WORKSHOP', 'order_index' => 2],
            ['category' => 'non_lb3_source', 'name' => 'Mess Karyawan', 'code' => 'MESS', 'order_index' => 3],
            ['category' => 'non_lb3_source', 'name' => 'Kantin', 'code' => 'KANTIN', 'order_index' => 4],
            ['category' => 'non_lb3_source', 'name' => 'Area Proyek', 'code' => 'PROYEK', 'order_index' => 5],

            // RKL-RPL Parameters
            ['category' => 'rkl_rpl_parameter', 'name' => 'Kebisingan', 'code' => 'NOISE', 'order_index' => 1],
            ['category' => 'rkl_rpl_parameter', 'name' => 'Udara ambient', 'code' => 'AIR_AMBIENT', 'order_index' => 2],
            ['category' => 'rkl_rpl_parameter', 'name' => 'Kebauan', 'code' => 'ODOR', 'order_index' => 3],
            ['category' => 'rkl_rpl_parameter', 'name' => 'Air bersih', 'code' => 'CLEAN_WATER', 'order_index' => 4],
            ['category' => 'rkl_rpl_parameter', 'name' => 'Drainase', 'code' => 'DRAINAGE', 'order_index' => 5],
            ['category' => 'rkl_rpl_parameter', 'name' => 'Limbah domestik', 'code' => 'DOMESTIC_WASTE', 'order_index' => 6],
            ['category' => 'rkl_rpl_parameter', 'name' => 'Biodiversitas', 'code' => 'BIODIVERSITY', 'order_index' => 7],
            ['category' => 'rkl_rpl_parameter', 'name' => 'Air sungai', 'code' => 'RIVER_WATER', 'order_index' => 8],

            // RKL-RPL Locations
            ['category' => 'rkl_rpl_location', 'name' => 'Area Well PAD', 'code' => 'LOC_PAD', 'order_index' => 1],
            ['category' => 'rkl_rpl_location', 'name' => 'Area Power Plant', 'code' => 'LOC_PP', 'order_index' => 2],
            ['category' => 'rkl_rpl_location', 'name' => 'Reseptor Pemukiman', 'code' => 'LOC_RECEPTOR', 'order_index' => 3],
            ['category' => 'rkl_rpl_location', 'name' => 'Hulu Sungai', 'code' => 'LOC_HULU', 'order_index' => 4],
            ['category' => 'rkl_rpl_location', 'name' => 'Hilir Sungai', 'code' => 'LOC_HILIR', 'order_index' => 5],

            // PPICH Locations
            ['category' => 'ppich_location', 'name' => 'Well PAD', 'code' => 'PPICH_PAD', 'order_index' => 1],
            ['category' => 'ppich_location', 'name' => 'Temporary Office', 'code' => 'PPICH_OFFICE', 'order_index' => 2],
            ['category' => 'ppich_location', 'name' => 'Power Plant', 'code' => 'PPICH_PP', 'order_index' => 3],
            ['category' => 'ppich_location', 'name' => 'Reseptor', 'code' => 'PPICH_RECEPTOR', 'order_index' => 4],

            // PPICH Activities
            ['category' => 'ppich_activity', 'name' => 'ketika', 'code' => 'KETIKA', 'order_index' => 1],
            ['category' => 'ppich_activity', 'name' => 'Operasional Rutin', 'code' => 'OPERASIONAL', 'order_index' => 2],
            ['category' => 'ppich_activity', 'name' => 'Pengeboran', 'code' => 'PENGEBORAN', 'order_index' => 3],
            ['category' => 'ppich_activity', 'name' => 'Pemeliharaan Fasilitas', 'code' => 'MAINTENANCE', 'order_index' => 4],

            // PPICH References
            ['category' => 'ppich_reference', 'name' => 'SK 2013', 'code' => 'SK_2013', 'order_index' => 1],
            ['category' => 'ppich_reference', 'name' => 'SK 2024', 'code' => 'SK_2024', 'order_index' => 2],

            // EWS Locations
            ['category' => 'ews_location', 'name' => 'PAD9', 'code' => 'EWS_PAD9', 'order_index' => 1],
            ['category' => 'ews_location', 'name' => 'PAD31', 'code' => 'EWS_PAD31', 'order_index' => 2],
            ['category' => 'ews_location', 'name' => 'PAD7', 'code' => 'EWS_PAD7', 'order_index' => 3],
            ['category' => 'ews_location', 'name' => 'PAD30', 'code' => 'EWS_PAD30', 'order_index' => 4],
        ];

        $now = now();
        foreach ($initialMasters as $m) {
            DB::table('environment_masters')->insert([
                'category' => $m['category'],
                'name' => $m['name'],
                'code' => $m['code'] ?? null,
                'order_index' => $m['order_index'] ?? 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. LB3 Records (Limbah Bahan Berbahaya dan Beracun)
        Schema::create('environment_lb3_records', function (Blueprint $table) {
            $table->id();
            $table->string('waste_name');
            $table->decimal('amount', 15, 2);
            $table->foreignId('unit_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->string('unit_name', 50)->nullable();
            $table->date('entry_date');
            $table->json('characteristics')->nullable(); // Array of characteristics
            $table->text('description')->nullable();
            $table->json('attachment_paths')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Non-LB3 Records (Limbah Non-B3)
        Schema::create('environment_non_lb3_records', function (Blueprint $table) {
            $table->id();
            $table->string('waste_name');
            $table->string('source');
            $table->foreignId('source_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->foreignId('unit_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->string('unit_name', 50)->nullable();
            $table->date('entry_date');
            $table->date('transport_date')->nullable(); // Boleh kosong jika belum diangkut
            $table->text('description')->nullable();
            $table->json('attachment_paths')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. RKL-RPL Records (Pemantauan Lingkungan)
        Schema::create('environment_rkl_rpl_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->string('parameter_name')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->string('location_name')->nullable();
            $table->date('sampling_date');
            $table->string('measurement_value'); // Hasil atau nilai pengamatan
            $table->foreignId('unit_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->string('unit_name', 50)->nullable();
            $table->text('description')->nullable();
            $table->json('attachment_paths')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. PPICH Records (Pemantauan Lokasi)
        Schema::create('environment_ppich_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->string('location_name');
            $table->date('sampling_date');
            $table->foreignId('reference_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->string('reference_name'); // SK 2013 atau SK 2024
            $table->foreignId('activity_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->string('activity_name'); // ketika, Operasional, dsb
            $table->text('description')->nullable();
            $table->json('attachment_paths')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 6. Energy Records (BBM & Listrik)
        Schema::create('environment_energy_records', function (Blueprint $table) {
            $table->id();
            $table->string('energy_type', 20)->index(); // 'bbm' atau 'listrik'
            $table->unsignedTinyInteger('month')->index(); // 1-12
            $table->unsignedSmallInteger('year')->index(); // e.g. 2026
            $table->decimal('usage_amount', 15, 2);
            $table->string('unit', 20); // 'Liter' atau 'kWh'
            $table->text('description')->nullable();
            $table->json('attachment_paths')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Cegah duplikasi periode untuk kategori energi yang sama
            $table->unique(['energy_type', 'month', 'year'], 'unique_energy_record_period');
        });

        // 7. EWS Records (Early Warning System)
        Schema::create('environment_ews_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained('environment_masters')->nullOnDelete();
            $table->string('location_name'); // PAD9, PAD31, PAD7, PAD30
            $table->date('recording_date');
            $table->text('condition'); // Free text kondisi aktual
            $table->text('description')->nullable();
            $table->json('attachment_paths')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environment_ews_records');
        Schema::dropIfExists('environment_energy_records');
        Schema::dropIfExists('environment_ppich_records');
        Schema::dropIfExists('environment_rkl_rpl_records');
        Schema::dropIfExists('environment_non_lb3_records');
        Schema::dropIfExists('environment_lb3_records');
        Schema::dropIfExists('environment_masters');
    }
};

