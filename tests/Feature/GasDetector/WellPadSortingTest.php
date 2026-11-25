<?php

namespace Tests\Feature\GasDetector;

use App\Models\GasMonitoring;
use App\Models\User;
use App\Models\WellPad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WellPadSortingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.wellpad@geodipa.co.id'],
            [
                'name' => 'Admin WellPad Dieng',
                'password' => bcrypt('password123'),
                'role' => 'Admin',
                'status' => 'Aktif',
            ]
        );
    }

    /**
     * 1. Pengurutan Alfanumerik / Natural Sesuai Spesifikasi.
     * Contoh: Well 07A, Well 07B, Well 07C, Well 07D, Well 07E, Well 09C, Well 09D, Well 29B, Well 29C, Well 29D, Well 30, Well 31.
     */
    public function test_well_pads_are_naturally_sorted_by_name(): void
    {
        $inputNames = [
            'Well 31',
            'Well 07A',
            'Well 29C',
            'Well 09D',
            'Well 07E',
            'Well 07B',
            'Well 30',
            'Well 07C',
            'Well 29D',
            'Well 09C',
            'Well 07D',
            'Well 29B',
        ];

        $expectedOrder = [
            'Well 07A',
            'Well 07B',
            'Well 07C',
            'Well 07D',
            'Well 07E',
            'Well 09C',
            'Well 09D',
            'Well 29B',
            'Well 29C',
            'Well 29D',
            'Well 30',
            'Well 31',
        ];

        $sorted = WellPad::sortNaturally($inputNames);
        $this->assertEquals($expectedOrder, $sorted->values()->all());

        // Juga uji perpaduan default "Well Pad 7" dengan "Well Pad 28"
        $defaultNames = ['Well Pad 30', 'Well Pad 7', 'Well Pad 31', 'Well Pad 28', 'Well Pad 9'];
        $sortedDefaults = WellPad::sortNaturally($defaultNames);
        $this->assertEquals(
            ['Well Pad 7', 'Well Pad 9', 'Well Pad 28', 'Well Pad 30', 'Well Pad 31'],
            $sortedDefaults->values()->all()
        );
    }

    /**
     * 2. Ketika Menambahkan Well Pad Baru:
     * Awalnya: Well 07A, Well 07C, Well 07E.
     * Admin menambahkan Well 07B.
     * Hasil otomatis pada getWellPadsData: Well 07A, Well 07B, Well 07C, Well 07E.
     */
    public function test_adding_new_well_pad_automatically_places_it_in_natural_sorted_position(): void
    {
        // Bersihkan data sementara atau buat data terisolasi
        WellPad::whereIn('name', ['Well 07A', 'Well 07B', 'Well 07C', 'Well 07E'])->delete();

        WellPad::create(['name' => 'Well 07A', 'code' => 'WP-07A', 'is_active' => true, 'sort_order' => 1]);
        WellPad::create(['name' => 'Well 07C', 'code' => 'WP-07C', 'is_active' => true, 'sort_order' => 2]);
        WellPad::create(['name' => 'Well 07E', 'code' => 'WP-07E', 'is_active' => true, 'sort_order' => 3]);

        // Admin menambahkan Well 07B melalui HTTP POST
        $response = $this->actingAs($this->admin)->postJson(route('gas-detector.well-pads.store'), [
            'name' => 'Well 07B',
            'code' => 'WP-07B',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        // Ambil data dari endpoint getWellPadsData
        $dataRes = $this->actingAs($this->admin)->getJson(route('gas-detector.well-pads.data'));
        $dataRes->assertStatus(200);

        $items = collect($dataRes->json('data'));
        $names = $items->pluck('name')->filter(fn ($n) => in_array($n, ['Well 07A', 'Well 07B', 'Well 07C', 'Well 07E']))->values()->all();

        $this->assertEquals(['Well 07A', 'Well 07B', 'Well 07C', 'Well 07E'], $names);
    }

    /**
     * 3. Ketika Mengedit Nama Well Pad:
     * Jika nama diubah, posisi pada daftar otomatis berpindah mengikuti alfabet baru.
     */
    public function test_editing_well_pad_name_updates_its_sorted_position(): void
    {
        WellPad::whereIn('name', ['Well 07A', 'Well 07B', 'Well 07AA'])->delete();

        $wp1 = WellPad::create(['name' => 'Well 07A', 'code' => 'WP-07A', 'is_active' => true, 'sort_order' => 1]);
        $wp2 = WellPad::create(['name' => 'Well 07B', 'code' => 'WP-07B', 'is_active' => true, 'sort_order' => 2]);
        $wp3 = WellPad::create(['name' => 'Well 07Z', 'code' => 'WP-07Z', 'is_active' => true, 'sort_order' => 3]);

        // Edit Well 07Z menjadi Well 07AA (sehingga posisinya harus pindah ke antara Well 07A dan Well 07B)
        $response = $this->actingAs($this->admin)->putJson(route('gas-detector.well-pads.update', $wp3), [
            'name' => 'Well 07AA',
            'code' => 'WP-07AA',
            'is_active' => true,
        ]);

        $response->assertStatus(200);

        $dataRes = $this->actingAs($this->admin)->getJson(route('gas-detector.well-pads.data'));
        $items = collect($dataRes->json('data'));
        $names = $items->pluck('name')->filter(fn ($n) => in_array($n, ['Well 07A', 'Well 07AA', 'Well 07B']))->values()->all();

        $this->assertEquals(['Well 07A', 'Well 07AA', 'Well 07B'], $names);
    }

    /**
     * 4. Proteksi Penghapusan:
     * - Jika belum ada data pengukuran, Well Pad dapat dihapus dari database.
     * - Jika sudah ada data pengukuran, Well Pad otomatis diubah menjadi nonaktif (is_active = false) dan data histori aman.
     */
    public function test_deletion_protection_for_well_pads_with_measurements(): void
    {
        $wpNoData = WellPad::create(['name' => 'Well Clean', 'code' => 'WP-CLN', 'is_active' => true, 'sort_order' => 99]);
        $wpWithData = WellPad::create(['name' => 'Well HasData', 'code' => 'WP-DATA', 'is_active' => true, 'sort_order' => 100]);

        GasMonitoring::create([
            'measurement_date' => now()->toDateString(),
            'well_pad_id' => $wpWithData->id,
            'well_pad' => $wpWithData->name,
            'co_level' => 10,
            'o2_level' => 20.9,
            'h2s_level' => 5,
            'lel_level' => 1,
            'pressure' => 500,
            'safety_status' => 'Sesuai Standar',
            'recorded_by' => 'Petugas Test',
        ]);

        // Hapus wpNoData -> harus terhapus permanen
        $res1 = $this->actingAs($this->admin)->deleteJson(route('gas-detector.well-pads.destroy', $wpNoData));
        $res1->assertStatus(200);
        $res1->assertJson(['action' => 'deleted']);
        $this->assertDatabaseMissing('well_pads', ['id' => $wpNoData->id]);

        // Hapus wpWithData -> tidak boleh dihapus, melainkan dinonaktifkan
        $res2 = $this->actingAs($this->admin)->deleteJson(route('gas-detector.well-pads.destroy', $wpWithData));
        $res2->assertStatus(200);
        $res2->assertJson(['action' => 'deactivated']);
        $this->assertDatabaseHas('well_pads', [
            'id' => $wpWithData->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('gas_monitorings', ['well_pad_id' => $wpWithData->id]);
    }

    /**
     * 5. Integrasi Form Input & Dropdown:
     * $activeWellPads yang dikirim ke view index harus selalu dalam urutan natural yang sama.
     */
    public function test_gas_monitoring_index_provides_naturally_sorted_well_pads(): void
    {
        $response = $this->actingAs($this->admin)->get(route('gas-detector.index'));
        $response->assertStatus(200);

        $activeWellPads = $response->viewData('activeWellPads');
        $this->assertNotNull($activeWellPads);

        $names = $activeWellPads->pluck('name')->all();
        $expected = WellPad::sortNaturally($names)->values()->all();

        $this->assertEquals($expected, $names);
    }
}
