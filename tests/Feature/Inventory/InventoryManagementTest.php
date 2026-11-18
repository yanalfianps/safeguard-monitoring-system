<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
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

    public function test_inventory_index_renders_four_tabs_and_dashboard_summary(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/inventory');
        $response->assertStatus(200);
        $response->assertSee('Manajemen Logistik & Inventaris HSSE', false);
        $response->assertSee('Jenis Barang Tersedia', false);
        $response->assertSee('Total Jumlah Barang', false);
        $response->assertSee('Stok Warehouse / Gudang HSSE', false);
        $response->assertSee('Master Barang', false);
        $response->assertSee('Riwayat Transaksi', false);
        $response->assertSee('Barang Terpinjam', false);
        $response->assertSee('History Peminjaman', false);
    }

    public function test_inventory_ajax_json_endpoint(): void
    {
        $admin = $this->getAdmin();

        InventoryItem::firstOrCreate(
            ['item_code' => 'TEST-INV-001'],
            [
                'item_name' => 'Safety Helmet Test',
                'category' => 'APD (Alat Pelindung Diri)',
                'unit_measure' => 'Box',
                'stock_warehouse' => 30,
                'stock_hsse' => 20,
                'keterangan' => 'Helm standar keselamatan',
            ]
        );

        $response = $this->actingAs($admin)->getJson('/inventory');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'items',
            'transactions',
            'borrowings',
            'borrowHistories',
            'totalBarang',
            'totalJumlahBarang',
            'jenisBarang',
        ]);
        $response->assertJson(['success' => true]);
    }

    public function test_admin_can_create_inventory_item_with_auto_code_and_dual_locations(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->post(route('inventory.store'), [
            'nama_barang' => 'Kacamata Safety Dual Lokasi ' . uniqid(),
            'kategori' => 'APD (Alat Pelindung Diri)',
            'satuan' => 'Box',
            'stock_warehouse' => 20,
            'stock_hsse' => 15,
            'keterangan' => 'Pelindung mata standar ANSI Z87.1',
            'expired_date' => '2028-12-31',
        ]);

        $response->assertRedirect(route('inventory.index', ['tab' => 'master']));

        $item = InventoryItem::latest('id')->first();
        $this->assertNotNull($item);
        $this->assertNotEmpty($item->kode_barang);
        $this->assertEquals(20, $item->stock_warehouse);
        $this->assertEquals(15, $item->stock_hsse);
        $this->assertEquals(35, $item->stock);
        $this->assertEquals('2028-12-31', $item->expired_date?->format('Y-m-d'));
    }

    public function test_admin_can_update_inventory_item_dual_location_stocks(): void
    {
        $admin = $this->getAdmin();
        $item = InventoryItem::create([
            'item_name' => 'Sarung Tangan Uji',
            'category' => 'APD (Alat Pelindung Diri)',
            'unit_measure' => 'Box',
            'stock_warehouse' => 10,
            'stock_hsse' => 10,
            'keterangan' => 'Stok awal',
        ]);

        $response = $this->actingAs($admin)->put(route('inventory.update', $item->id), [
            'nama_barang' => 'Sarung Tangan Uji Updated',
            'kategori' => 'APD (Alat Pelindung Diri)',
            'satuan' => 'Kilogram',
            'stock_warehouse' => 18,
            'stock_hsse' => 7,
            'keterangan' => 'Diperbarui dengan lokasi terpisah',
            'expired_date' => '2027-06-30',
        ]);

        $response->assertRedirect(route('inventory.index', ['tab' => 'master']));
        $item->refresh();
        $this->assertEquals('Sarung Tangan Uji Updated', $item->item_name);
        $this->assertEquals('Kilogram', $item->satuan);
        $this->assertEquals(18, $item->stock_warehouse);
        $this->assertEquals(7, $item->stock_hsse);
        $this->assertEquals(25, $item->stock);
    }

    public function test_can_record_multi_item_transactions_across_locations(): void
    {
        $admin = $this->getAdmin();

        $itemA = InventoryItem::create([
            'item_name' => 'Absorbent Pad Oil ' . uniqid(),
            'category' => 'Peralatan Emergency & Rescue',
            'unit_measure' => 'Box',
            'stock_warehouse' => 10,
            'stock_hsse' => 5,
            'keterangan' => 'Penyerap tumpahan minyak',
        ]);

        $itemB = InventoryItem::create([
            'item_name' => 'Cairan Disinfektan ' . uniqid(),
            'category' => 'Peralatan P3K & Medis',
            'unit_measure' => 'Liter',
            'stock_warehouse' => 4,
            'stock_hsse' => 8,
            'keterangan' => 'Cairan medis',
        ]);

        // 1. Multi-item Masuk
        $respMasuk = $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'jenis_transaksi' => 'masuk',
            'tanggal_transaksi' => now()->toDateString(),
            'petugas' => 'Petugas Logistik A',
            'keterangan' => 'Penerimaan batch baru',
            'items' => [
                [
                    'inventory_id' => $itemA->id,
                    'location' => 'Warehouse',
                    'satuan' => 'Box',
                    'jumlah' => 5,
                    'expired_date' => '2027-10-01',
                ],
                [
                    'inventory_id' => $itemB->id,
                    'location' => 'Gudang HSSE',
                    'satuan' => 'Liter',
                    'jumlah' => 12,
                    'expired_date' => '2026-12-31',
                ],
            ],
        ]);
        $respMasuk->assertRedirect(route('inventory.index', ['tab' => 'history']));

        $itemA->refresh();
        $itemB->refresh();
        $this->assertEquals(15, $itemA->stock_warehouse);
        $this->assertEquals(5, $itemA->stock_hsse);
        $this->assertEquals(20, $itemA->stock);

        $this->assertEquals(4, $itemB->stock_warehouse);
        $this->assertEquals(20, $itemB->stock_hsse);
        $this->assertEquals(24, $itemB->stock);

        // 2. Multi-item Keluar
        $respKeluar = $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'jenis_transaksi' => 'keluar',
            'tanggal_transaksi' => now()->toDateString(),
            'petugas' => 'Petugas Logistik B',
            'items' => [
                [
                    'inventory_id' => $itemA->id,
                    'location' => 'Warehouse',
                    'satuan' => 'Box',
                    'jumlah' => 3,
                ],
                [
                    'inventory_id' => $itemB->id,
                    'location' => 'Gudang HSSE',
                    'satuan' => 'Liter',
                    'jumlah' => 5,
                ],
            ],
        ]);
        $respKeluar->assertRedirect(route('inventory.index', ['tab' => 'history']));

        $itemA->refresh();
        $itemB->refresh();
        $this->assertEquals(12, $itemA->stock_warehouse);
        $this->assertEquals(15, $itemB->stock_hsse);
    }

    public function test_rejects_outgoing_transaction_exceeding_location_stock(): void
    {
        $admin = $this->getAdmin();
        $item = InventoryItem::create([
            'item_name' => 'Full Body Harness Limited ' . uniqid(),
            'category' => 'Perlengkapan Kerja di Ketinggian',
            'unit_measure' => 'Box',
            'stock_warehouse' => 10,
            'stock_hsse' => 2,
            'keterangan' => 'Harness ketinggian',
        ]);

        // Coba keluarkan 5 dari Gudang HSSE (yang hanya punya 2, meskipun Warehouse punya 10)
        $response = $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'jenis_transaksi' => 'keluar',
            'tanggal_transaksi' => now()->toDateString(),
            'petugas' => 'Admin Tester',
            'items' => [
                [
                    'inventory_id' => $item->id,
                    'location' => 'Gudang HSSE',
                    'satuan' => 'Box',
                    'jumlah' => 5,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['jumlah']);
        $item->refresh();
        $this->assertEquals(10, $item->stock_warehouse);
        $this->assertEquals(2, $item->stock_hsse);
    }

    public function test_cannot_delete_item_with_transaction_history_but_can_delete_clean_item(): void
    {
        $admin = $this->getAdmin();

        // 1. Barang tanpa riwayat (stok awal 0) dapat dihapus
        $cleanItem = InventoryItem::create([
            'item_name' => 'Barang Bersih Tanpa Histori',
            'category' => 'APD (Alat Pelindung Diri)',
            'unit_measure' => 'Box',
            'stock_warehouse' => 0,
            'stock_hsse' => 0,
            'keterangan' => 'Uji hapus',
        ]);

        $resDel = $this->actingAs($admin)->delete(route('inventory.destroy', $cleanItem->id));
        $resDel->assertRedirect(route('inventory.index', ['tab' => 'master']));
        $this->assertDatabaseMissing('inventory_items', ['id' => $cleanItem->id]);

        // 2. Barang dengan riwayat transaksi ditolak penghapusannya
        $usedItem = InventoryItem::create([
            'item_name' => 'Barang Sudah Ada Transaksi',
            'category' => 'APD (Alat Pelindung Diri)',
            'unit_measure' => 'Box',
            'stock_warehouse' => 5,
            'stock_hsse' => 0,
            'keterangan' => 'Memiliki transaksi',
        ]);
        InventoryTransaction::create([
            'inventory_item_id' => $usedItem->id,
            'jenis_transaksi' => 'masuk',
            'location' => 'Warehouse',
            'satuan' => 'Box',
            'jumlah' => 5,
            'tanggal' => now()->toDateString(),
            'petugas' => 'Tester',
        ]);

        $resProtected = $this->actingAs($admin)->delete(route('inventory.destroy', $usedItem->id));
        $resProtected->assertRedirect(route('inventory.index', ['tab' => 'master']));
        $resProtected->assertSessionHas('error');
        $this->assertDatabaseHas('inventory_items', ['id' => $usedItem->id]);
    }

    public function test_inventory_dashboard_and_history_filters_work_properly(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/inventory?' . http_build_query([
            'tab' => 'history',
            'filter_item' => 'Safety',
            'filter_category' => 'APD (Alat Pelindung Diri)',
            'filter_location' => 'Warehouse',
            'filter_date_start' => date('Y-01-01'),
            'filter_date_end' => date('Y-12-31'),
            'filter_pic' => 'Admin',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Transaksi', false);
    }

    public function test_inventory_rbac_permissions_for_admin_inventory_and_user(): void
    {
        $adminInventory = User::factory()->create([
            'role' => 'Admin Inventory',
            'status' => 'Aktif',
        ]);

        $regularUser = User::factory()->create([
            'role' => 'User',
            'status' => 'Aktif',
        ]);

        // Admin Inventory: TIDAK boleh tambah master barang (403), hanya boleh kelola peminjaman
        $resStore = $this->actingAs($adminInventory)->post(route('inventory.store'), [
            'nama_barang' => 'Barang Oleh Admin Inventory ' . uniqid(),
            'kategori' => 'APD (Alat Pelindung Diri)',
            'satuan' => 'Box',
            'stock_warehouse' => 5,
            'stock_hsse' => 5,
            'keterangan' => 'Input oleh Admin Inventory',
        ]);
        $resStore->assertStatus(403);

        // Admin Inventory hanya boleh export laporan peminjaman, tidak boleh master/history
        $this->actingAs($adminInventory)->get(route('inventory.export', ['tab' => 'master', 'format' => 'pdf']))->assertStatus(403);
        $this->actingAs($adminInventory)->get(route('inventory.export', ['tab' => 'history', 'format' => 'pdf']))->assertStatus(403);

        foreach ([
            'borrowed' => 'Laporan_Barang_Terpinjam.pdf',
            'borrow_history' => 'Laporan_History_Peminjaman.pdf',
        ] as $tabKey => $expectedFilename) {
            $resAdminInvPdf = $this->actingAs($adminInventory)->get(route('inventory.export', ['tab' => $tabKey, 'format' => 'pdf']));
            $resAdminInvPdf->assertStatus(200);
            $this->assertStringContainsString($expectedFilename, (string) $resAdminInvPdf->headers->get('content-disposition'));
        }

        foreach ([
            'master' => 'Laporan_Master_Barang.pdf',
            'history' => 'Laporan_Riwayat_Transaksi.pdf',
            'borrowed' => 'Laporan_Barang_Terpinjam.pdf',
            'borrow_history' => 'Laporan_History_Peminjaman.pdf',
        ] as $tabKey => $expectedFilename) {
            $resUserPdf = $this->actingAs($regularUser)->get(route('inventory.export', ['tab' => $tabKey, 'format' => 'pdf']));
            $resUserPdf->assertStatus(200);
            $this->assertStringContainsString($expectedFilename, (string) $resUserPdf->headers->get('content-disposition'));
        }

        // User: TIDAK boleh tambah master barang (403), tetapi BOLEH export PDF (200)
        $resUserStore = $this->actingAs($regularUser)->post(route('inventory.store'), [
            'nama_barang' => 'Barang Ilegal User',
            'kategori' => 'APD (Alat Pelindung Diri)',
            'satuan' => 'Box',
            'keterangan' => 'Percobaan',
        ]);
        $resUserStore->assertStatus(403);

        $resUserExport = $this->actingAs($regularUser)->get(route('inventory.export'));
        $resUserExport->assertStatus(200);
    }

    public function test_master_item_uniqueness_and_dropdown_deduplication_across_all_scenarios(): void
    {
        $admin = $this->getAdmin();
        $uniqueName = 'Spill Kit Universal Uji Unik ' . uniqid();

        // Skenario 1: Barang baru ditambahkan ke master -> muncul sekali pada dropdown dengan format ringkas
        $this->actingAs($admin)->post(route('inventory.store'), [
            'nama_barang' => $uniqueName,
            'kategori' => 'Peralatan Emergency & Rescue',
            'satuan' => 'Box',
            'stock_warehouse' => 10,
            'stock_hsse' => 5,
            'keterangan' => 'Batch awal Spill Kit',
            'expired_date' => '2027-06-30',
        ])->assertRedirect(route('inventory.index', ['tab' => 'master']));

        $this->assertEquals(1, InventoryItem::where('nama_barang', $uniqueName)->count());
        $masterItem = InventoryItem::where('nama_barang', $uniqueName)->firstOrFail();

        $indexResp = $this->actingAs($admin)->get(route('inventory.index'));
        $indexResp->assertStatus(200);
        $expectedLabel = "[{$masterItem->kode_barang}] {$uniqueName} (Warehouse: 10 Box | Gudang HSSE: 5 Box)";
        $indexResp->assertSee($expectedLabel, false);

        // Skenario 2 & 4: Barang yang sama masuk kembali (baik via form Master Barang maupun Catat Transaksi)
        // dengan tanggal expired berbeda -> TIDAK membuat master baru, stok bertambah & batch expired tercatat
        $this->actingAs($admin)->post(route('inventory.store'), [
            'barang_id' => 'EMG-9999', // simulasi kode otomatis form
            'nama_barang' => $uniqueName,
            'kategori' => 'Peralatan Emergency & Rescue',
            'satuan' => 'Box',
            'stock_warehouse' => 4,
            'stock_hsse' => 2,
            'keterangan' => 'Penambahan batch baru via form master',
            'expired_date' => '2028-12-31',
        ])->assertRedirect(route('inventory.index', ['tab' => 'master']));

        // Pastikan tetap hanya 1 baris di inventory_items
        $this->assertEquals(1, InventoryItem::where('nama_barang', $uniqueName)->count());
        $masterItem->refresh();
        $this->assertEquals(14, $masterItem->stock_warehouse);
        $this->assertEquals(7, $masterItem->stock_hsse);

        // Masuk kembali melalui transaksi dengan expired_date ketiga
        $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'jenis_transaksi' => 'masuk',
            'tanggal_transaksi' => now()->toDateString(),
            'petugas' => 'Petugas Gudang',
            'items' => [
                [
                    'inventory_id' => $masterItem->id,
                    'location' => 'Warehouse',
                    'satuan' => 'Box',
                    'jumlah' => 6,
                    'expired_date' => '2029-03-15',
                ],
            ],
        ])->assertRedirect(route('inventory.index', ['tab' => 'history']));

        $this->assertEquals(1, InventoryItem::where('nama_barang', $uniqueName)->count());
        $masterItem->refresh();
        $this->assertEquals(20, $masterItem->stock_warehouse);
        $this->assertEquals(7, $masterItem->stock_hsse);

        // Pastikan ke-3 batch expired_date tersimpan pada transaksi milik 1 master barang tersebut
        $distinctExpiryCount = InventoryTransaction::where('inventory_item_id', $masterItem->id)
            ->whereNotNull('expired_date')
            ->distinct('expired_date')
            ->count('expired_date');
        $this->assertEquals(3, $distinctExpiryCount);

        // Skenario 3: Barang yang sama keluar beberapa kali -> tetap muncul sekali dengan stok terbaru
        $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'jenis_transaksi' => 'keluar',
            'tanggal_transaksi' => now()->toDateString(),
            'petugas' => 'Petugas Lapangan 1',
            'items' => [
                [
                    'inventory_id' => $masterItem->id,
                    'location' => 'Warehouse',
                    'satuan' => 'Box',
                    'jumlah' => 3,
                ],
            ],
        ]);
        $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'jenis_transaksi' => 'keluar',
            'tanggal_transaksi' => now()->toDateString(),
            'petugas' => 'Petugas Lapangan 2',
            'items' => [
                [
                    'inventory_id' => $masterItem->id,
                    'location' => 'Gudang HSSE',
                    'satuan' => 'Box',
                    'jumlah' => 2,
                ],
            ],
        ]);

        $masterItem->refresh();
        $this->assertEquals(17, $masterItem->stock_warehouse);
        $this->assertEquals(5, $masterItem->stock_hsse);
        $this->assertEquals(1, InventoryItem::where('nama_barang', $uniqueName)->count());

        // Skenario 5: Peminjaman dan pengembalian menggunakan master barang yang sama
        $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            'inventory_item_id' => $masterItem->id,
            'borrower_name' => 'Andi Teknisi',
            'department' => 'Pemeliharaan',
            'location' => 'Warehouse',
            'quantity_borrowed' => 4,
            'borrow_date' => now()->format('Y-m-d H:i:s'),
            'expected_return_date' => now()->addDays(5)->toDateString(),
            'purpose' => 'Siaga Area Turbin',
            'recorded_by' => 'Admin HSSE',
        ])->assertRedirect(route('inventory.index', ['tab' => 'borrowed']));

        $masterItem->refresh();
        $this->assertEquals(13, $masterItem->stock_warehouse);

        $borrowing = \App\Models\InventoryBorrowing::where('inventory_item_id', $masterItem->id)->latest('id')->firstOrFail();
        $this->actingAs($admin)->post(route('inventory.borrowings.return', $borrowing->id), [
            'quantity_returned' => 4,
            'return_location' => 'Warehouse',
            'actual_return_date' => now()->format('Y-m-d H:i:s'),
            'recorded_by' => 'Admin HSSE',
        ])->assertRedirect();

        $masterItem->refresh();
        $this->assertEquals(17, $masterItem->stock_warehouse);
        $this->assertEquals(5, $masterItem->stock_hsse);
        $this->assertEquals(1, InventoryItem::where('nama_barang', $uniqueName)->count());

        // Skenario 6: Dropdown tidak menampilkan duplikasi meskipun barang sudah mengalami banyak transaksi & peminjaman
        $htmlAfter = $this->actingAs($admin)->get(route('inventory.index'))->getContent();
        $updatedLabel = "[{$masterItem->kode_barang}] {$uniqueName} (Warehouse: 17 Box | Gudang HSSE: 5 Box)";

        // Muncul di dropdown peminjaman (#borrowItemSelect) tanpa duplikasi master
        $this->assertStringContainsString($updatedLabel, $htmlAfter);
        $this->assertEquals(1, substr_count($htmlAfter, $updatedLabel));
    }

    public function test_transaction_validation_requires_petugas_location_satuan_and_jumlah(): void
    {
        $admin = $this->getAdmin();
        $item = InventoryItem::create([
            'kode_barang' => 'APD-8801',
            'nama_barang' => 'Safety Goggles Validation Test',
            'kategori' => 'APD (Alat Pelindung Diri)',
            'satuan' => 'Pcs',
            'stock_warehouse' => 10,
            'stock_hsse' => 5,
            'keterangan' => 'Kacamata safety uji validasi',
        ]);

        // Gagal jika petugas, location, satuan, atau jumlah kosong
        $responseInvalid = $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'jenis_transaksi' => 'masuk',
            'tanggal_transaksi' => now()->toDateString(),
            'petugas' => '',
            'items' => [
                [
                    'inventory_id' => $item->id,
                    'location' => '',
                    'satuan' => '',
                    'jumlah' => '',
                ],
            ],
        ]);

        $responseInvalid->assertSessionHasErrors([
            'petugas',
            'items.0.location',
            'items.0.satuan',
            'items.0.jumlah',
        ]);

        $item->refresh();
        $this->assertEquals(10, $item->stock_warehouse);
        $this->assertEquals(5, $item->stock_hsse);

        // Berhasil jika field wajib lengkap meskipun keterangan dikosongkan
        $responseValid = $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'jenis_transaksi' => 'masuk',
            'tanggal_transaksi' => now()->toDateString(),
            'petugas' => 'Petugas Uji Validasi',
            'keterangan' => '',
            'items' => [
                [
                    'inventory_id' => $item->id,
                    'location' => 'Gudang HSSE',
                    'satuan' => 'Pcs',
                    'jumlah' => 4,
                ],
            ],
        ]);

        $responseValid->assertRedirect(route('inventory.index', ['tab' => 'history']));
        $item->refresh();
        $this->assertEquals(10, $item->stock_warehouse);
        $this->assertEquals(9, $item->stock_hsse);
    }
}
