<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('well_pads')) {
            Schema::create('well_pads', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('code', 50)->unique();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('gas_monitorings')) {
            Schema::table('gas_monitorings', function (Blueprint $table) {
                if (!Schema::hasColumn('gas_monitorings', 'well_pad_id')) {
                    $table->foreignId('well_pad_id')->nullable()->after('measurement_date')->index();
                }
                if (!Schema::hasColumn('gas_monitorings', 'o2_level')) {
                    $table->decimal('o2_level', 8, 2)->nullable()->default(20.9)->after('co_level');
                }
                if (!Schema::hasColumn('gas_monitorings', 'lel_level')) {
                    $table->decimal('lel_level', 8, 2)->nullable()->default(0)->after('h2s_level');
                }
                if (!Schema::hasColumn('gas_monitorings', 'o2_status')) {
                    $table->string('o2_status', 20)->default('Sesuai')->after('co_status');
                }
                if (!Schema::hasColumn('gas_monitorings', 'lel_status')) {
                    $table->string('lel_status', 20)->default('Sesuai')->after('h2s_status');
                }
                if (!Schema::hasColumn('gas_monitorings', 'overall_status')) {
                    $table->string('overall_status', 20)->default('Sesuai')->after('pressure_status');
                }
            });
        }

        // Seed default well pads safely
        $defaults = [
            ['name' => 'Well Pad 7', 'code' => 'WP-07', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Well Pad 9', 'code' => 'WP-09', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Well Pad 28', 'code' => 'WP-28', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Well Pad 30', 'code' => 'WP-30', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Well Pad 31', 'code' => 'WP-31', 'is_active' => true, 'sort_order' => 5],
        ];

        $now = now();
        foreach ($defaults as $wp) {
            $exists = DB::table('well_pads')->where('name', $wp['name'])->first();
            if (!$exists) {
                DB::table('well_pads')->insert([
                    'name' => $wp['name'],
                    'code' => $wp['code'],
                    'is_active' => $wp['is_active'],
                    'sort_order' => $wp['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Backfill any other distinct well_pad names from gas_monitorings and link well_pad_id
        if (Schema::hasTable('gas_monitorings')) {
            $distinctPads = DB::table('gas_monitorings')
                ->select('well_pad')
                ->whereNotNull('well_pad')
                ->distinct()
                ->pluck('well_pad');

            foreach ($distinctPads as $padName) {
                $padNameTrimmed = trim((string) $padName);
                if ($padNameTrimmed === '') {
                    continue;
                }
                $wpRow = DB::table('well_pads')->where('name', $padNameTrimmed)->first();
                if (!$wpRow) {
                    $maxSort = (int) DB::table('well_pads')->max('sort_order') + 1;
                    $numPart = preg_replace('/[^0-9]/', '', $padNameTrimmed);
                    $code = $numPart !== '' ? 'WP-' . str_pad($numPart, 2, '0', STR_PAD_LEFT) : 'WP-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $padNameTrimmed), 0, 4));
                    if (DB::table('well_pads')->where('code', $code)->exists()) {
                        $code .= '-' . $maxSort;
                    }
                    $wpId = DB::table('well_pads')->insertGetId([
                        'name' => $padNameTrimmed,
                        'code' => $code,
                        'is_active' => true,
                        'sort_order' => $maxSort,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $wpId = $wpRow->id;
                }

                DB::table('gas_monitorings')
                    ->where('well_pad', $padName)
                    ->whereNull('well_pad_id')
                    ->update(['well_pad_id' => $wpId]);
            }

            // Backfill overall_status, o2_level, lel_level on existing rows
            $rows = DB::table('gas_monitorings')->get();
            foreach ($rows as $r) {
                $o2 = $r->o2_level ?? 20.9;
                $lel = $r->lel_level ?? round(min(4.5, max(0.5, ((float) $r->h2s_level) * 0.4)), 1);
                $coStatus = ($r->co_level !== null && (float) $r->co_level > 25.0) ? 'Tidak Sesuai' : 'Sesuai';
                $o2Status = ($o2 !== null && ((float) $o2 < 19.5 || (float) $o2 > 23.5)) ? 'Tidak Sesuai' : 'Sesuai';
                $h2sStatus = ($r->h2s_level !== null && (float) $r->h2s_level > 10.0) ? 'Tidak Sesuai' : 'Sesuai';
                $lelStatus = ($lel !== null && (float) $lel > 10.0) ? 'Tidak Sesuai' : 'Sesuai';
                $pressStatus = ($r->pressure !== null && (float) $r->pressure > 20.0) ? 'Tidak Sesuai' : 'Sesuai';
                $overall = ($coStatus === 'Sesuai' && $o2Status === 'Sesuai' && $h2sStatus === 'Sesuai' && $lelStatus === 'Sesuai' && $pressStatus === 'Sesuai')
                    ? 'Sesuai'
                    : 'Tidak Sesuai';

                DB::table('gas_monitorings')
                    ->where('id', $r->id)
                    ->update([
                        'o2_level' => $o2,
                        'lel_level' => $lel,
                        'co_status' => $coStatus,
                        'o2_status' => $o2Status,
                        'h2s_status' => $h2sStatus,
                        'lel_status' => $lelStatus,
                        'pressure_status' => $pressStatus,
                        'overall_status' => $overall,
                    ]);
            }
        }

        // Normalize legacy unit names in events table to new standard units
        if (Schema::hasTable('events')) {
            $unitMap = [
                'PMU' => 'GDE (Internal)',
                'Dieng Unit 1' => 'GDE (Internal)',
                'Dieng Unit 2' => 'GDE (Internal)',
                'Utility' => 'PJSE (Utility)',
                'BISMO' => 'BUJP (Security)',
                'Kontraktor' => 'PMC',
            ];
            foreach ($unitMap as $oldUnit => $newUnit) {
                DB::table('events')->where('unit', $oldUnit)->update(['unit' => $newUnit]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gas_monitorings')) {
            Schema::table('gas_monitorings', function (Blueprint $table) {
                $cols = [];
                foreach (['well_pad_id', 'o2_level', 'lel_level', 'o2_status', 'lel_status', 'overall_status'] as $c) {
                    if (Schema::hasColumn('gas_monitorings', $c)) {
                        $cols[] = $c;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::dropIfExists('well_pads');
    }
};

