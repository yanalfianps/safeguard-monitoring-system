<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBorrowing;
use App\Models\InventoryItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryBorrowingTest extends TestCase
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

    public function test_inventory_index_displays_four_tabs_and_updated_borrowing_labels(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('inventory.index', ['tab' => 'borrowed']));
        $response->assertStatus(200);
        $response->assertSee('Master Barang', false);
        $response->assertSee('Riwayat Transaksi', false);
        $response->assertSee('Barang Terpinjam', false);
        $response->assertSee('History Peminjaman', false);
        $response->assertSee('Jumlah Peminjaman', false);
        $response->assertSee('Jumlah Barang Dipinjam', false);
        $response->assertSee('Melewati Tenggat', false);
        $response->assertSee('Masih Dipinjam', false);
        $response->assertDontSee('Total Jumlah Barang', false);
        $response->assertDontSee('Formulir Sirkulasi', false);
    }

    public function test_can_borrow_item_and_deduct_selected_location_stock(): void
    {
        $admin = $this->getAdmin();

        $item = InventoryItem::create([
            'item_name' => 'Gas Detector Portable Multi ' . uniqid(),
            'category' => 'Peralatan Emergency & Rescue',
            'stock_warehouse' => 10,
            'stock_hsse' => 4,
            'unit_measure' => 'Box',
            'keterangan' => 'Detektor multi gas',
        ]);

        $response = $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            'inventory_item_id' => $item->id,
            'location' => 'Warehouse',
            'borrower_name' => 'Budi Santoso',
            'department' => 'Operasi Dieng 2',
            'quantity_borrowed' => 3,
            'borrow_date' => Carbon::now()->toDateString(),
            'expected_return_date' => Carbon::now()->addDays(5)->toDateString(),
            'purpose' => 'Pemeriksaan sumur H2S',
            'recorded_by' => 'Petugas HSSE',
        ]);

        $response->assertRedirect(route('inventory.index', ['tab' => 'borrowed']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_borrowings', [
            'inventory_item_id' => $item->id,
            'location' => 'Warehouse',
            'borrower_name' => 'Budi Santoso',
            'department' => 'Operasi Dieng 2',
            'quantity_borrowed' => 3,
            'quantity_returned' => 0,
            'status' => InventoryBorrowing::STATUS_BORROWED,
        ]);

        // Stok Warehouse berkurang dari 10 menjadi 7, stok Gudang HSSE tetap 4, total stok tersedia = 11
        $freshItem = $item->fresh();
        $this->assertEquals(7, $freshItem->stock_warehouse);
        $this->assertEquals(4, $freshItem->stock_hsse);
        $this->assertEquals(11, $freshItem->stock);
        $this->assertEquals(3, $freshItem->borrowed_stock);
    }

    public function test_cannot_borrow_more_than_location_available_stock(): void
    {
        $admin = $this->getAdmin();

        $item = InventoryItem::create([
            'item_name' => 'Handy Talkie Motorola ' . uniqid(),
            'category' => 'Detektor & Instrumen Lingkungan',
            'stock_warehouse' => 5,
            'stock_hsse' => 1,
            'unit_measure' => 'Box',
            'keterangan' => 'Radio HT',
        ]);

        // Pinjam 4 unit dari Warehouse (tersisa 1 di Warehouse)
        $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            'inventory_item_id' => $item->id,
            'location' => 'Warehouse',
            'borrower_name' => 'Operator A',
            'department' => 'Security',
            'quantity_borrowed' => 4,
            'borrow_date' => Carbon::now()->toDateString(),
            'expected_return_date' => Carbon::now()->addDays(2)->toDateString(),
            'purpose' => 'Patroli pagi',
            'recorded_by' => 'Admin',
        ]);

        // Coba pinjam 2 unit lagi dari Warehouse (padahal tersisa hanya 1 di Warehouse)
        $response = $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            'inventory_item_id' => $item->id,
            'location' => 'Warehouse',
            'borrower_name' => 'Operator B',
            'department' => 'Security',
            'quantity_borrowed' => 2,
            'borrow_date' => Carbon::now()->toDateString(),
            'expected_return_date' => Carbon::now()->addDays(1)->toDateString(),
            'purpose' => 'Patroli malam',
            'recorded_by' => 'Admin',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, $item->fresh()->stock_warehouse);
    }

    public function test_multi_step_partial_returns_and_move_to_borrow_history_with_chronological_timeline(): void
    {
        $admin = $this->getAdmin();

        $item = InventoryItem::create([
            'item_name' => 'Harness Keselamatan Full Body ' . uniqid(),
            'category' => 'Perlengkapan Kerja di Ketinggian',
            'stock_warehouse' => 10,
            'stock_hsse' => 0,
            'unit_measure' => 'Box',
            'keterangan' => 'Full body harness',
        ]);

        // Pinjam 5 unit dari Warehouse -> stok Warehouse jadi 5
        $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            'inventory_item_id' => $item->id,
            'location' => 'Warehouse',
            'borrower_name' => 'Teknisi Proyek',
            'department' => 'Konstruksi',
            'quantity_borrowed' => 5,
            'borrow_date' => Carbon::now()->subDays(3)->toDateString(),
            'expected_return_date' => Carbon::now()->addDays(3)->toDateString(),
            'purpose' => 'Pekerjaan scaffolding',
            'recorded_by' => 'Petugas 1',
        ]);

        $borrowing = InventoryBorrowing::where('inventory_item_id', $item->id)->firstOrFail();
        $this->assertEquals(5, $item->fresh()->stock_warehouse);

        // 1. Pengembalian Pertama (2 dari 5 unit) ke Warehouse
        $respPartial1 = $this->actingAs($admin)->post(route('inventory.borrowings.return', $borrowing), [
            'quantity_returned' => 2,
            'actual_return_date' => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
            'return_location' => 'Warehouse',
            'recorded_by' => 'Petugas 1',
            'notes' => 'Pengembalian tahap 1',
        ]);
        $respPartial1->assertRedirect(route('inventory.index', ['tab' => 'borrowed']));

        $borrowing->refresh();
        $this->assertEquals(2, $borrowing->quantity_returned);
        $this->assertEquals(3, $borrowing->remaining_quantity);
        $this->assertEquals(InventoryBorrowing::STATUS_BORROWED, $borrowing->display_status);
        $this->assertEquals(7, $item->fresh()->stock_warehouse);

        // 2. Pengembalian Kedua (1 dari sisa 3 unit) ke Gudang HSSE
        $respPartial2 = $this->actingAs($admin)->post(route('inventory.borrowings.return', $borrowing), [
            'quantity_returned' => 1,
            'actual_return_date' => Carbon::now()->subHours(5)->format('Y-m-d H:i:s'),
            'return_location' => 'Gudang HSSE',
            'recorded_by' => 'Petugas 2',
            'notes' => 'Pengembalian tahap 2',
        ]);
        $respPartial2->assertRedirect(route('inventory.index', ['tab' => 'borrowed']));

        $borrowing->refresh();
        $this->assertEquals(3, $borrowing->quantity_returned);
        $this->assertEquals(2, $borrowing->remaining_quantity);
        $this->assertEquals(7, $item->fresh()->stock_warehouse);
        $this->assertEquals(1, $item->fresh()->stock_hsse);

        // 3. Pengembalian Ketiga / Pelunasan (2 unit terakhir) ke Warehouse
        $respFull = $this->actingAs($admin)->post(route('inventory.borrowings.return', $borrowing), [
            'quantity_returned' => 2,
            'actual_return_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'return_location' => 'Warehouse',
            'recorded_by' => 'Petugas 3',
            'notes' => 'Pengembalian tahap 3 lunas',
        ]);
        $respFull->assertRedirect(route('inventory.index', ['tab' => 'borrow_history']));

        $borrowing->refresh();
        $this->assertEquals(5, $borrowing->quantity_returned);
        $this->assertEquals(0, $borrowing->remaining_quantity);
        $this->assertEquals(InventoryBorrowing::STATUS_RETURNED, $borrowing->display_status);

        // Pastikan ke-3 record pengembalian tersimpan utuh tanpa menimpa satu sama lain
        $this->assertCount(3, $borrowing->returns);
        $timeline = $borrowing->chronological_timeline;
        $this->assertCount(4, $timeline);
        $this->assertEquals('Peminjaman', $timeline[0]['keterangan']);
        $this->assertEquals(5, $timeline[0]['jumlah']);
        $this->assertEquals('Pengembalian 1', $timeline[1]['keterangan']);
        $this->assertEquals(2, $timeline[1]['jumlah']);
        $this->assertEquals('Pengembalian 2', $timeline[2]['keterangan']);
        $this->assertEquals(1, $timeline[2]['jumlah']);
        $this->assertEquals('Pengembalian 3', $timeline[3]['keterangan']);
        $this->assertEquals(2, $timeline[3]['jumlah']);

        // Stok total kembali utuh 10 (9 di Warehouse + 1 di Gudang HSSE)
        $freshItem = $item->fresh();
        $this->assertEquals(9, $freshItem->stock_warehouse);
        $this->assertEquals(1, $freshItem->stock_hsse);
        $this->assertEquals(10, $freshItem->stock);

        // Pastikan tampil di tab History Peminjaman
        $resHistoryTab = $this->actingAs($admin)->get(route('inventory.index', ['tab' => 'borrow_history']));
        $resHistoryTab->assertStatus(200);
        $resHistoryTab->assertSee($borrowing->borrow_code, false);
        $resHistoryTab->assertSee('Sudah Dikembalikan', false);
    }

    public function test_can_extend_borrowing_due_date_and_detect_overdue_status(): void
    {
        $admin = $this->getAdmin();

        $item = InventoryItem::create([
            'item_name' => 'Alat Ukur Kebisingan ' . uniqid(),
            'category' => 'Detektor & Instrumen Lingkungan',
            'stock_warehouse' => 5,
            'stock_hsse' => 0,
            'unit_measure' => 'Box',
            'keterangan' => 'Sound level meter',
        ]);

        $borrowing = InventoryBorrowing::create([
            'inventory_item_id' => $item->id,
            'location' => 'Warehouse',
            'borrower_name' => 'Rudi Hartono',
            'department' => 'Lingkungan',
            'quantity_borrowed' => 1,
            'quantity_returned' => 0,
            'borrow_date' => Carbon::now()->subDays(5)->toDateString(),
            'expected_return_date' => Carbon::now()->subDay()->toDateString(),
            'status' => InventoryBorrowing::STATUS_BORROWED,
        ]);

        // Karena expected_return_date kemarin, harus terdeteksi overdue (Melewati Tenggat)
        $this->assertTrue($borrowing->is_overdue);
        $this->assertEquals(InventoryBorrowing::STATUS_OVERDUE, $borrowing->display_status);

        // Perpanjang rencana pengembalian ke 5 hari ke depan
        $newDueDate = Carbon::now()->addDays(5)->toDateString();
        $resExtend = $this->actingAs($admin)->post(route('inventory.borrowings.due-date', $borrowing), [
            'expected_return_date' => $newDueDate,
            'reason' => 'Pengukuran area sumur diperpanjang',
        ]);

        $resExtend->assertRedirect(route('inventory.index', ['tab' => 'borrowed']));
        $borrowing->refresh();
        $this->assertEquals($newDueDate, $borrowing->expected_return_date->format('Y-m-d'));
        $this->assertFalse($borrowing->is_overdue);
        $this->assertEquals(InventoryBorrowing::STATUS_BORROWED, $borrowing->display_status);
        $this->assertNotEmpty($borrowing->due_date_history);
    }

    public function test_stock_expiry_batches_and_multi_batch_borrowing_and_return(): void
    {
        $admin = $this->getAdmin();

        $expDate1 = Carbon::now()->addDays(15)->format('Y-m-d'); // Mendekati Expired
        $expDate2 = Carbon::now()->addDays(120)->format('Y-m-d'); // Aman / Berlaku

        // 1. Buat master barang dengan stok awal di Warehouse (tanggal expired pertama)
        $item = InventoryItem::create([
            'item_name' => 'Filter Masker N95 ' . uniqid(),
            'category' => 'Alat Pelindung Diri (APD)',
            'stock_warehouse' => 4,
            'stock_hsse' => 0,
            'unit_measure' => 'Box',
            'expired_date' => $expDate1,
            'keterangan' => 'Filter respirator',
        ]);
        $item->ensureBatchesSynced();

        // 2. Barang yang sama masuk lagi ke Warehouse dengan tanggal expired berbeda ($expDate2)
        $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'inventory_id' => $item->id,
            'jenis_transaksi' => 'masuk',
            'location' => 'Warehouse',
            'jumlah' => 6,
            'expired_date' => $expDate2,
            'tanggal_transaksi' => Carbon::now()->toDateString(),
            'keterangan' => 'Stok masuk batch ke-2',
            'petugas' => 'Petugas Gudang',
        ])->assertSessionHas('success');

        // 3. Barang yang sama masuk ke Gudang HSSE tanpa mengisi tanggal expired (Belum Dicatat)
        $this->actingAs($admin)->post(route('inventory.transactions.store'), [
            'inventory_id' => $item->id,
            'jenis_transaksi' => 'masuk',
            'location' => 'Gudang HSSE',
            'jumlah' => 3,
            'expired_date' => null,
            'tanggal_transaksi' => Carbon::now()->toDateString(),
            'keterangan' => 'Stok masuk tanpa tanggal expired',
            'petugas' => 'Petugas Gudang',
        ])->assertSessionHas('success');

        // Master barang tetap 1 baris, stok Warehouse = 10, Gudang HSSE = 3
        $item->refresh();
        $this->assertEquals(10, $item->stock_warehouse);
        $this->assertEquals(3, $item->stock_hsse);

        $groups = $item->getStockExpiryGroups();
        $this->assertCount(3, $groups);

        // Pastikan halaman Master Barang memiliki tombol Lihat Stok & Expired
        $resMaster = $this->actingAs($admin)->get(route('inventory.index', ['tab' => 'master']));
        $resMaster->assertStatus(200);
        $resMaster->assertSee('Lihat Stok & Expired', false);

        // 4. Coba pinjam melebihi stok kelompok tanggal expired pertama (stok batch 1 hanya 4, coba pinjam 5)
        $resExceedBatch = $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            'inventory_item_id' => $item->id,
            'location' => 'Warehouse',
            'borrower_name' => 'Andi Wijaya',
            'department' => 'Pemeliharaan',
            'borrow_batches' => [
                ['expiry_key' => $expDate1, 'quantity' => 5],
            ],
            'borrow_date' => Carbon::now()->toDateString(),
            'expected_return_date' => Carbon::now()->addDays(4)->toDateString(),
            'purpose' => 'Inspeksi area berdebu',
            'recorded_by' => 'Admin HSSE',
        ]);
        $resExceedBatch->assertSessionHas('error');
        $this->assertEquals(10, $item->fresh()->stock_warehouse);

        // 5. Pinjam dari 2 kelompok tanggal expired berbeda sekaligus di Warehouse (2 dari batch 1 + 3 dari batch 2 = 5)
        $resBorrowMulti = $this->actingAs($admin)->post(route('inventory.borrowings.store'), [
            'inventory_item_id' => $item->id,
            'location' => 'Warehouse',
            'borrower_name' => 'Andi Wijaya',
            'department' => 'Pemeliharaan',
            'borrow_batches' => [
                ['expiry_key' => $expDate1, 'quantity' => 2],
                ['expiry_key' => $expDate2, 'quantity' => 3],
            ],
            'borrow_date' => Carbon::now()->toDateString(),
            'expected_return_date' => Carbon::now()->addDays(4)->toDateString(),
            'purpose' => 'Inspeksi area berdebu',
            'recorded_by' => 'Admin HSSE',
        ]);
        $resBorrowMulti->assertRedirect(route('inventory.index', ['tab' => 'borrowed']));
        $resBorrowMulti->assertSessionHas('success');

        $item->refresh();
        $this->assertEquals(5, $item->stock_warehouse);

        $borrowing = InventoryBorrowing::where('inventory_item_id', $item->id)->firstOrFail();
        $this->assertEquals(5, $borrowing->quantity_borrowed);
        $this->assertCount(2, $borrowing->expiry_details);

        // Pastikan Tanggal Expired tampil di tab Barang Terpinjam
        $resBorrowedTab = $this->actingAs($admin)->get(route('inventory.index', ['tab' => 'borrowed']));
        $resBorrowedTab->assertStatus(200);
        $resBorrowedTab->assertSee(Carbon::parse($expDate1)->format('d/m/Y'), false);
        $resBorrowedTab->assertSee(Carbon::parse($expDate2)->format('d/m/Y'), false);
        $resBorrowedTab->assertSee('Mendekati Expired', false);

        // 6. Kembalikan seluruh barang (5 Box) ke Warehouse dan pastikan stok per batch kembali utuh
        $this->actingAs($admin)->post(route('inventory.borrowings.return', $borrowing), [
            'quantity_returned' => 5,
            'actual_return_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'return_location' => 'Warehouse',
            'recorded_by' => 'Admin HSSE',
            'notes' => 'Kembali lengkap',
        ])->assertRedirect(route('inventory.index', ['tab' => 'borrow_history']));

        $item->refresh();
        $this->assertEquals(10, $item->stock_warehouse);

        // Pastikan tampil juga di tab History Peminjaman beserta tanggal expired-nya
        $resHistory = $this->actingAs($admin)->get(route('inventory.index', ['tab' => 'borrow_history']));
        $resHistory->assertStatus(200);
        $resHistory->assertSee(Carbon::parse($expDate1)->format('d/m/Y'), false);
        $resHistory->assertSee(Carbon::parse($expDate2)->format('d/m/Y'), false);
    }
}
