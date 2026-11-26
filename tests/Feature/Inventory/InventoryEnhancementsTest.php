<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBorrowing;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryEnhancementsTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdmin(): User
    {
        return User::whereIn('role', [User::ROLE_ADMIN_MANAGEMENT, 'Admin', 'Admin Management'])->first()
            ?: User::factory()->create([
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'Aktif',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REQUIREMENT 1: MASTER DATA SATUAN
    |--------------------------------------------------------------------------
    */

    public function test_units_api_returns_all_units_with_usage_count(): void
    {
        $admin = $this->getAdmin();
        InventoryUnit::ensureDefaults();

        $response = $this->actingAs($admin)->getJson(route('inventory.units.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'is_active', 'usage_count', 'can_delete'],
            ],
        ]);
        $this->assertTrue($response->json('success'));
    }

    public function test_can_add_new_satuan_and_reject_duplicates(): void
    {
        $admin = $this->getAdmin();

        $unitName = 'TestUnit_' . uniqid();

        // Add new unit
        $response = $this->actingAs($admin)->postJson(route('inventory.units.store'), [
            'name' => $unitName,
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseHas('inventory_units', [
            'name' => $unitName,
            'is_active' => true,
        ]);

        // Attempt duplicate (case-insensitive & trimmed)
        $dupResponse = $this->actingAs($admin)->postJson(route('inventory.units.store'), [
            'name' => '  ' . strtolower($unitName) . '  ',
        ]);
        $dupResponse->assertStatus(422);
        $dupResponse->assertJson([
            'success' => false,
        ]);
    }

    public function test_can_edit_satuan_and_cascades_to_master_and_transactions(): void
    {
        $admin = $this->getAdmin();

        $oldName = 'OldUnit_' . uniqid();
        $newName = 'NewUnit_' . uniqid();

        $unit = InventoryUnit::create([
            'name' => $oldName,
            'is_active' => true,
        ]);

        // Create an inventory item and transaction using this unit
        $item = InventoryItem::create([
            'kode_barang' => 'TST-' . rand(1000, 9999),
            'nama_barang' => 'Item Testing Unit',
            'kategori' => 'Alat Kerja',
            'satuan' => $oldName,
            'stock_warehouse' => 10,
            'stock_hsse' => 5,
        ]);

        $trx = InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'kode_transaksi' => 'TRX-' . rand(1000, 9999),
            'jenis_transaksi' => 'Masuk',
            'tanggal_transaksi' => now()->format('Y-m-d'),
            'lokasi' => 'Warehouse',
            'satuan' => $oldName,
            'jumlah' => 10,
            'petugas' => 'Tester',
        ]);

        // Update the unit name
        $response = $this->actingAs($admin)->putJson(route('inventory.units.update', $unit->id), [
            'name' => $newName,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify unit table updated
        $this->assertDatabaseHas('inventory_units', [
            'id' => $unit->id,
            'name' => $newName,
        ]);

        // Verify cascaded update to inventory item and transaction
        $this->assertEquals($newName, $item->fresh()->satuan);
        $this->assertEquals($newName, $trx->fresh()->satuan);
    }

    public function test_cannot_delete_satuan_in_use_but_can_deactivate(): void
    {
        $admin = $this->getAdmin();

        $usedName = 'UsedUnit_' . uniqid();
        $unit = InventoryUnit::create([
            'name' => $usedName,
            'is_active' => true,
        ]);

        InventoryItem::create([
            'kode_barang' => 'TST-' . rand(1000, 9999),
            'nama_barang' => 'Item In Use Unit',
            'kategori' => 'Alat Kerja',
            'satuan' => $usedName,
            'stock_warehouse' => 2,
            'stock_hsse' => 0,
        ]);

        // Attempt delete
        $delResponse = $this->actingAs($admin)->deleteJson(route('inventory.units.destroy', $unit->id));
        $delResponse->assertStatus(422);
        $delResponse->assertJson([
            'success' => false,
            'can_deactivate' => true,
        ]);
        $this->assertDatabaseHas('inventory_units', ['id' => $unit->id]);

        // Toggle to inactive instead
        $toggleResponse = $this->actingAs($admin)->postJson(route('inventory.units.toggle', $unit->id));
        $toggleResponse->assertStatus(200);
        $this->assertEquals(0, $unit->fresh()->is_active);
    }

    public function test_can_delete_unused_satuan(): void
    {
        $admin = $this->getAdmin();

        $cleanUnit = InventoryUnit::create([
            'name' => 'CleanUnit_' . uniqid(),
            'is_active' => true,
        ]);

        $delResponse = $this->actingAs($admin)->deleteJson(route('inventory.units.destroy', $cleanUnit->id));
        $delResponse->assertStatus(200);
        $delResponse->assertJson(['success' => true]);
        $this->assertDatabaseMissing('inventory_units', ['id' => $cleanUnit->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | REQUIREMENT 2: CATATAN TRANSAKSI PENCARIAN & AUTOCOMPLETE
    |--------------------------------------------------------------------------
    */

    public function test_search_items_endpoint_matches_name_code_and_category(): void
    {
        $admin = $this->getAdmin();

        $item = InventoryItem::create([
            'kode_barang' => 'MSK-9901',
            'nama_barang' => 'Masker N95 Respirator',
            'kategori' => 'APD (Alat Pelindung Diri)',
            'satuan' => 'Box',
            'stock_warehouse' => 25,
            'stock_hsse' => 15,
        ]);

        // Search by "mask"
        $resp = $this->actingAs($admin)->getJson(route('inventory.search-items', ['q' => 'mask']));
        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertNotEmpty($data);

        $matched = collect($data)->firstWhere('id', $item->id);
        $this->assertNotNull($matched);
        $this->assertEquals('Masker N95 Respirator', $matched['nama_barang']);
        $this->assertEquals('MSK-9901', $matched['kode_barang']);
        $this->assertEquals(25, $matched['stock_warehouse']);
        $this->assertEquals(15, $matched['stock_hsse']);

        // Search by code "9901"
        $respCode = $this->actingAs($admin)->getJson(route('inventory.search-items', ['q' => '9901']));
        $respCode->assertStatus(200);
        $this->assertNotEmpty(collect($respCode->json('data'))->where('id', $item->id));
    }

    public function test_multi_item_transaction_with_item_id_succeeds(): void
    {
        $admin = $this->getAdmin();

        $item1 = InventoryItem::create([
            'kode_barang' => 'APD-9911',
            'nama_barang' => 'Helm Proyek Kuning',
            'kategori' => 'APD (Alat Pelindung Diri)',
            'satuan' => 'Pcs',
            'stock_warehouse' => 20,
            'stock_hsse' => 10,
        ]);

        $item2 = InventoryItem::create([
            'kode_barang' => 'APD-9912',
            'nama_barang' => 'Kacamata Safety Bening',
            'kategori' => 'APD (Alat Pelindung Diri)',
            'satuan' => 'Pcs',
            'stock_warehouse' => 15,
            'stock_hsse' => 5,
        ]);

        $response = $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            '_strict_trx_form' => '1',
            'jenis_transaksi' => 'Keluar',
            'tanggal_transaksi' => now()->format('Y-m-d'),
            'petugas' => 'Petugas Lapangan HSSE',
            'keterangan' => 'Uji Transaksi Multi Item',
            'items' => [
                [
                    'inventory_id' => $item1->id,
                    'location' => 'Warehouse',
                    'satuan' => 'Pcs',
                    'jumlah' => 3,
                ],
                [
                    'inventory_id' => $item2->id,
                    'location' => 'Gudang HSSE',
                    'satuan' => 'Pcs',
                    'jumlah' => 2,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertEquals(17, $item1->fresh()->stock_warehouse);
        $this->assertEquals(3, $item2->fresh()->stock_hsse);
    }

    /*
    |--------------------------------------------------------------------------
    | REQUIREMENT 3: PEMINJAMAN BARANG — PILIHAN LOKASI MENYESUAIKAN STOK
    |--------------------------------------------------------------------------
    */

    public function test_borrowing_when_stock_only_in_single_location(): void
    {
        $admin = $this->getAdmin();

        // Item with stock ONLY in Warehouse (Gudang HSSE = 0)
        $item = InventoryItem::create([
            'kode_barang' => 'EQP-7701',
            'nama_barang' => 'Gas Detector Portable Single Location',
            'kategori' => 'Peralatan Khusus',
            'satuan' => 'Unit',
            'is_consumable' => false,
            'stock_warehouse' => 5,
            'stock_hsse' => 0,
        ]);

        // Borrowing with Warehouse location succeeds
        $resp = $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            '_strict_borrow_form' => '1',
            'inventory_item_id' => $item->id,
            'borrower_name' => 'Budi Santoso',
            'department' => 'Operasi & Pemeliharaan',
            'location' => 'Warehouse',
            'borrow_date' => now()->format('Y-m-d H:i:s'),
            'expected_return_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'purpose' => 'Pemeriksaan lapangan',
            'recorded_by' => 'Petugas HSSE',
            'borrow_batches' => [
                [
                    'expiry_key' => 'UNRECORDED',
                    'quantity' => 2,
                ],
            ],
        ]);

        $resp->assertRedirect();
        $this->assertEquals(3, $item->fresh()->stock_warehouse);

        // Attempting to borrow from Gudang HSSE (which has 0 stock) fails
        $failResp = $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            '_strict_borrow_form' => '1',
            'inventory_item_id' => $item->id,
            'borrower_name' => 'Budi Santoso',
            'department' => 'Operasi & Pemeliharaan',
            'location' => 'Gudang HSSE',
            'borrow_date' => now()->format('Y-m-d H:i:s'),
            'expected_return_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'purpose' => 'Pemeriksaan lapangan',
            'recorded_by' => 'Petugas HSSE',
            'borrow_batches' => [
                [
                    'expiry_key' => 'UNRECORDED',
                    'quantity' => 1,
                ],
            ],
        ]);

        $failResp->assertSessionHas('error');
    }

    public function test_borrowing_rejected_when_stock_is_zero_in_all_locations(): void
    {
        $admin = $this->getAdmin();

        $emptyItem = InventoryItem::create([
            'kode_barang' => 'EQP-7702',
            'nama_barang' => 'Noise Meter Habis Total',
            'kategori' => 'Peralatan Khusus',
            'satuan' => 'Unit',
            'is_consumable' => false,
            'stock_warehouse' => 0,
            'stock_hsse' => 0,
        ]);

        $resp = $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            '_strict_borrow_form' => '1',
            'inventory_item_id' => $emptyItem->id,
            'borrower_name' => 'Andi Wijaya',
            'department' => 'HSSE',
            'location' => 'Warehouse',
            'borrow_date' => now()->format('Y-m-d H:i:s'),
            'expected_return_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'purpose' => 'Pengukuran kebisingan',
            'recorded_by' => 'Petugas HSSE',
            'borrow_batches' => [
                [
                    'expiry_key' => 'UNRECORDED',
                    'quantity' => 1,
                ],
            ],
        ]);

        $resp->assertSessionHas('error');
    }

    public function test_borrowing_cannot_exceed_chosen_location_stock_when_both_have_stock(): void
    {
        $admin = $this->getAdmin();

        // Warehouse has 2, Gudang HSSE has 10
        $dualItem = InventoryItem::create([
            'kode_barang' => 'EQP-7703',
            'nama_barang' => 'Dual Stock Lux Meter',
            'kategori' => 'Peralatan Khusus',
            'satuan' => 'Unit',
            'is_consumable' => false,
            'stock_warehouse' => 2,
            'stock_hsse' => 10,
        ]);

        // Attempting to borrow 5 from Warehouse (only 2 available) must fail
        $resp = $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            '_strict_borrow_form' => '1',
            'inventory_item_id' => $dualItem->id,
            'borrower_name' => 'Citra Pratama',
            'department' => 'Pemeliharaan',
            'location' => 'Warehouse',
            'borrow_date' => now()->format('Y-m-d H:i:s'),
            'expected_return_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'purpose' => 'Pengujian lampu darurat',
            'recorded_by' => 'Petugas HSSE',
            'borrow_batches' => [
                [
                    'expiry_key' => 'UNRECORDED',
                    'quantity' => 5,
                ],
            ],
        ]);

        $resp->assertSessionHas('error');

        // Borrowing 5 from Gudang HSSE (10 available) succeeds
        $successResp = $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            '_strict_borrow_form' => '1',
            'inventory_item_id' => $dualItem->id,
            'borrower_name' => 'Citra Pratama',
            'department' => 'Pemeliharaan',
            'location' => 'Gudang HSSE',
            'borrow_date' => now()->format('Y-m-d H:i:s'),
            'expected_return_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'purpose' => 'Pengujian lampu darurat',
            'recorded_by' => 'Petugas HSSE',
            'borrow_batches' => [
                [
                    'expiry_key' => 'UNRECORDED',
                    'quantity' => 5,
                ],
            ],
        ]);

        $successResp->assertRedirect();
        $this->assertEquals(5, $dualItem->fresh()->stock_hsse);
        $this->assertEquals(2, $dualItem->fresh()->stock_warehouse);
    }

    public function test_inventory_index_renders_all_components_and_modals(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('inventory.index'));
        $response->assertStatus(200);

        // Verify Kelola Master (Kategori & Satuan) UI presence
        $response->assertSee('modalKelolaMaster', false);
        $response->assertSee('Kelola Master', false);
        $response->assertSee('tabMasterKategori', false);
        $response->assertSee('tabMasterSatuan', false);
        $response->assertSee('newKategoriNameInput', false);
        $response->assertSee('newSatuanNameInput', false);
        $response->assertSee('+ Kelola Kategori', false);
        $response->assertSee('+ Kelola Satuan', false);

        // Verify Integrated Autocomplete in Catatan Transaksi presence
        $response->assertSee('modalTransaksiTerpadu', false);
        $response->assertSee('trx-item-search-input', false);
        $response->assertSee('trx-item-id-input', false);
        $response->assertSee('trx-autocomplete-dropdown', false);

        // Verify Peminjaman Location adaptive elements
        $response->assertSee('modalPinjamBarang', false);
        $response->assertSee('borrowLocationSelect', false);
        $response->assertSee('borrowLocationHidden', false);
        $response->assertSee('borrowLocationNote', false);
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER DATA KATEGORI (KELOLA MASTER)
    |--------------------------------------------------------------------------
    */

    public function test_categories_api_returns_all_categories_with_usage_count(): void
    {
        $admin = $this->getAdmin();
        InventoryCategory::ensureDefaults();

        $response = $this->actingAs($admin)->getJson(route('inventory.categories.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'is_active', 'usage_count', 'can_delete'],
            ],
        ]);
        $this->assertTrue($response->json('success'));
    }

    public function test_can_add_new_kategori_and_reject_duplicates(): void
    {
        $admin = $this->getAdmin();

        $catName = 'TestKategori_' . uniqid();

        $response = $this->actingAs($admin)->postJson(route('inventory.categories.store'), [
            'name' => $catName,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('inventory_categories', [
            'name' => $catName,
            'is_active' => true,
        ]);

        // Attempt duplicate (case-insensitive & trimmed)
        $dupResponse = $this->actingAs($admin)->postJson(route('inventory.categories.store'), [
            'name' => '  ' . strtolower($catName) . '  ',
        ]);
        $dupResponse->assertStatus(422);
        $dupResponse->assertJson(['success' => false]);
    }

    public function test_can_edit_kategori_and_cascades_to_inventory_items(): void
    {
        $admin = $this->getAdmin();

        $oldName = 'OldKategori_' . uniqid();
        $newName = 'NewKategori_' . uniqid();

        $category = InventoryCategory::create([
            'name' => $oldName,
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'kode_barang' => 'KTG-' . rand(1000, 9999),
            'nama_barang' => 'Item Testing Kategori',
            'kategori' => $oldName,
            'satuan' => 'Box',
            'stock_warehouse' => 4,
            'stock_hsse' => 1,
        ]);

        $response = $this->actingAs($admin)->putJson(route('inventory.categories.update', $category->id), [
            'name' => $newName,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventory_categories', [
            'id' => $category->id,
            'name' => $newName,
        ]);

        $freshItem = $item->fresh();
        $this->assertEquals($newName, $freshItem->kategori);
        $this->assertEquals($newName, $freshItem->category);
    }

    public function test_cannot_delete_kategori_in_use_but_can_delete_unused(): void
    {
        $admin = $this->getAdmin();

        $usedName = 'UsedKategori_' . uniqid();
        $usedCategory = InventoryCategory::create([
            'name' => $usedName,
            'is_active' => true,
        ]);

        InventoryItem::create([
            'kode_barang' => 'KTG-' . rand(1000, 9999),
            'nama_barang' => 'Item In Use Kategori',
            'kategori' => $usedName,
            'satuan' => 'Box',
            'stock_warehouse' => 3,
            'stock_hsse' => 0,
        ]);

        $delUsed = $this->actingAs($admin)->deleteJson(route('inventory.categories.destroy', $usedCategory->id));
        $delUsed->assertStatus(422);
        $delUsed->assertJson([
            'success' => false,
            'can_deactivate' => true,
        ]);
        $this->assertDatabaseHas('inventory_categories', ['id' => $usedCategory->id]);

        $cleanCategory = InventoryCategory::create([
            'name' => 'CleanKategori_' . uniqid(),
            'is_active' => true,
        ]);

        $delClean = $this->actingAs($admin)->deleteJson(route('inventory.categories.destroy', $cleanCategory->id));
        $delClean->assertStatus(200);
        $delClean->assertJson(['success' => true]);
        $this->assertDatabaseMissing('inventory_categories', ['id' => $cleanCategory->id]);
    }
}
