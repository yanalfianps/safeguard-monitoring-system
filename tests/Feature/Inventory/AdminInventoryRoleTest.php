<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBorrowing;
use App\Models\InventoryItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminInventoryRoleTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdminInventory(): User
    {
        return User::where('email', 'inventory@geodipa.co.id')->first()
            ?: User::factory()->create([
                'name' => 'Admin Inventory HSSE',
                'email' => 'inventory@geodipa.co.id',
                'password' => Hash::make('Invventory123'),
                'role' => User::ROLE_ADMIN_INVENTORY,
                'status' => 'Aktif',
            ]);
    }

    protected function getAdminManagement(): User
    {
        return User::where('role', User::ROLE_ADMIN_MANAGEMENT)->first()
            ?: User::factory()->create([
                'name' => 'Administrator Management',
                'email' => 'admin_test@geodipa.co.id',
                'password' => Hash::make('Admin123!'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'Aktif',
            ]);
    }

    protected function getStandardUser(): User
    {
        return User::where('role', User::ROLE_USER)->first()
            ?: User::factory()->create([
                'name' => 'Standard User',
                'email' => 'user_test@geodipa.co.id',
                'password' => Hash::make('User123!'),
                'role' => User::ROLE_USER,
                'status' => 'Aktif',
            ]);
    }

    public function test_admin_inventory_account_exists_and_can_authenticate(): void
    {
        $user = User::where('email', 'inventory@geodipa.co.id')->first();

        $this->assertNotNull($user, 'Akun inventory@geodipa.co.id wajib tersedia di database.');
        $this->assertEquals(User::ROLE_ADMIN_INVENTORY, $user->role);
        $this->assertTrue(Hash::check('Invventory123', $user->password), 'Password akun inventory harus sesuai Invventory123.');

        // Test login route redirection
        $response = $this->post(route('login'), [
            'email' => 'inventory@geodipa.co.id',
            'password' => 'Invventory123',
        ]);

        $response->assertRedirect(route('inventory.index', ['tab' => 'borrowed']));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_page_redirects_authenticated_admin_inventory_to_borrowed_tab(): void
    {
        $adminInv = $this->getAdminInventory();

        $response = $this->actingAs($adminInv)->get(route('login'));
        $response->assertRedirect(route('inventory.index', ['tab' => 'borrowed']));
    }

    public function test_admin_inventory_sidebar_only_shows_peminjaman_barang(): void
    {
        $adminInv = $this->getAdminInventory();

        $response = $this->actingAs($adminInv)->get(route('inventory.index', ['tab' => 'borrowed']));
        $response->assertStatus(200);

        // Should see Peminjaman Barang navigation in sidebar
        $response->assertSee('Peminjaman Barang');

        // Should NOT see other module links in sidebar
        $response->assertDontSee('href="' . route('daily-work.index') . '"', false);
        $response->assertDontSee('href="' . route('events.index') . '"', false);
        $response->assertDontSee('href="' . route('budget-monitoring.index') . '"', false);
        $response->assertDontSee('href="' . route('users.index') . '"', false);
    }

    public function test_admin_inventory_can_access_borrowed_tab_and_sees_borrow_actions_only(): void
    {
        $adminInv = $this->getAdminInventory();

        $response = $this->actingAs($adminInv)->get(route('inventory.index', ['tab' => 'borrowed']));
        $response->assertStatus(200);

        // Has borrowing elements
        $response->assertSee('Barang Terpinjam');
        $response->assertSee('Pinjam Barang');

        // Administrative actions/tabs are hidden
        $response->assertDontSee('class="tab-group tab-group-stock"', false);
        $response->assertDontSee('<span>Catat Transaksi</span>', false);
        $response->assertDontSee('<span>Kelola Satuan</span>', false);
        $response->assertDontSee('<span>Tambah Master Barang</span>', false);
        $response->assertDontSee('data-bs-target="#modalTambahBarang"', false);
        $response->assertDontSee('id="modalTambahBarang"', false);
        $response->assertDontSee('id="modalTransaksiTerpadu"', false);
        $response->assertDontSee('id="modalKelolaSatuan"', false);
    }

    public function test_admin_inventory_can_access_borrow_history_tab(): void
    {
        $adminInv = $this->getAdminInventory();

        $response = $this->actingAs($adminInv)->get(route('inventory.index', ['tab' => 'borrow_history']));
        $response->assertStatus(200);
        $response->assertSee('History Peminjaman');
    }

    public function test_admin_inventory_is_forbidden_from_master_and_transaction_history_tabs(): void
    {
        $adminInv = $this->getAdminInventory();

        // Direct tab access
        $this->actingAs($adminInv)->get(route('inventory.index', ['tab' => 'master']))->assertStatus(403);
        $this->actingAs($adminInv)->get(route('inventory.index', ['tab' => 'history']))->assertStatus(403);

        // Direct administrative URLs
        $this->actingAs($adminInv)->get(route('inventory.transactions'))->assertStatus(403);
        $this->actingAs($adminInv)->get(route('inventory.units.index'))->assertStatus(403);
    }

    public function test_admin_inventory_is_forbidden_from_master_barang_and_unit_crud(): void
    {
        $adminInv = $this->getAdminInventory();

        $item = InventoryItem::first() ?: InventoryItem::create([
            'item_name' => 'Test Item ' . uniqid(),
            'category' => 'APD (Alat Pelindung Diri)',
            'stock_warehouse' => 5,
            'stock_hsse' => 5,
            'unit_measure' => 'Pcs',
            'keterangan' => 'Test',
        ]);

        // Attempt Create Master Item
        $this->actingAs($adminInv)->post(route('inventory.store'), [
            'nama_barang' => 'Illegal Item Create',
            'kategori' => 'APD (Alat Pelindung Diri)',
            'stock_warehouse' => 10,
            'stock_hsse' => 5,
            'satuan' => 'Pcs',
            'keterangan' => 'Illegal attempt',
        ])->assertStatus(403);

        // Attempt Update Master Item
        $this->actingAs($adminInv)->put(route('inventory.update', $item->id), [
            'nama_barang' => 'Modified Name',
            'kategori' => $item->kategori,
            'stock_warehouse' => 20,
            'stock_hsse' => 10,
            'satuan' => $item->satuan,
            'keterangan' => 'Illegal update',
        ])->assertStatus(403);

        // Attempt Delete Master Item
        $this->actingAs($adminInv)->delete(route('inventory.destroy', $item->id))->assertStatus(403);

        // Attempt Transaction Mutation
        $this->actingAs($adminInv)->post(route('inventory.transactions.store'), [
            'jenis_transaksi' => 'masuk',
            'tanggal_transaksi' => date('Y-m-d'),
            'petugas' => 'Petugas Test',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'lokasi' => 'Warehouse',
                    'jumlah' => 5,
                ]
            ]
        ])->assertStatus(403);

        // Attempt Unit CRUD
        $this->actingAs($adminInv)->post(route('inventory.units.store'), [
            'name' => 'UnitIllegal',
        ])->assertStatus(403);
    }

    public function test_admin_inventory_can_create_and_return_borrowing(): void
    {
        $adminInv = $this->getAdminInventory();

        $item = InventoryItem::create([
            'item_name' => 'Safety Harness Dual Lanyard ' . uniqid(),
            'category' => 'Peralatan Bekerja di Ketinggian',
            'stock_warehouse' => 10,
            'stock_hsse' => 5,
            'unit_measure' => 'Set',
            'keterangan' => 'Harness safety test',
        ]);

        // 1. Store Borrowing
        $borrowResponse = $this->actingAs($adminInv)->post(route('inventory.borrowings.store'), [
            'inventory_item_id' => $item->id,
            'location' => 'Warehouse',
            'borrower_name' => 'Ahmad Teknisi',
            'department' => 'Pemeliharaan',
            'quantity_borrowed' => 2,
            'borrow_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'expected_return_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
            'purpose' => 'Pekerjaan perbaikan rig',
            'recorded_by' => $adminInv->name,
        ]);

        $borrowResponse->assertRedirect(route('inventory.index', ['tab' => 'borrowed']));
        $borrowResponse->assertSessionHas('success');

        $borrowing = InventoryBorrowing::where('inventory_item_id', $item->id)
            ->where('borrower_name', 'Ahmad Teknisi')
            ->first();

        $this->assertNotNull($borrowing);
        $this->assertEquals(2, $borrowing->quantity_borrowed);
        $this->assertEquals(InventoryBorrowing::STATUS_BORROWED, $borrowing->display_status);

        // 2. Return Borrowing (Full Return redirects to borrow_history)
        $returnResponse = $this->actingAs($adminInv)->post(route('inventory.borrowings.return', $borrowing->id), [
            'quantity_returned' => 2,
            'return_location' => 'Warehouse',
            'actual_return_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'recorded_by' => $adminInv->name,
            'notes' => 'Dikembalikan lengkap tanpa kerusakan.',
        ]);

        $returnResponse->assertRedirect(route('inventory.index', ['tab' => 'borrow_history']));
        $returnResponse->assertSessionHas('success');

        $borrowing->refresh();
        $this->assertEquals(InventoryBorrowing::STATUS_RETURNED, $borrowing->display_status);
        $this->assertEquals(2, $borrowing->quantity_returned);
    }

    public function test_admin_inventory_is_strictly_forbidden_from_all_other_modules(): void
    {
        $adminInv = $this->getAdminInventory();

        $forbiddenUrls = [
            route('dashboard'),
            route('daily-work.index'),
            route('events.index'),
            route('gas-detector.index'),
            route('gas-monitoring.index'),
            route('man-hours.index'),
            route('incident.index'),
            route('issue-follow-up.index'),
            route('budget-monitoring.index'),
            route('users.index'),
        ];

        foreach ($forbiddenUrls as $url) {
            $response = $this->actingAs($adminInv)->get($url);
            $response->assertStatus(403);
        }
    }

    public function test_admin_management_retains_full_privileges(): void
    {
        $adminMgmt = $this->getAdminManagement();

        // Can access dashboard
        $this->actingAs($adminMgmt)->get(route('dashboard'))->assertStatus(200);

        // Can access master tab
        $this->actingAs($adminMgmt)->get(route('inventory.index', ['tab' => 'master']))->assertStatus(200);

        // Can access users
        $this->actingAs($adminMgmt)->get(route('users.index'))->assertStatus(200);

        // Can access daily work
        $this->actingAs($adminMgmt)->get(route('daily-work.index'))->assertStatus(200);
    }

    public function test_standard_user_cannot_manage_inventory_borrowings(): void
    {
        $user = $this->getStandardUser();

        $item = InventoryItem::first();
        if ($item) {
            $this->actingAs($user)->post(route('inventory.borrowings.store'), [
                'inventory_item_id' => $item->id,
                'location' => 'Warehouse',
                'borrower_name' => 'Unauthorized User',
                'department' => 'Test',
                'quantity_borrowed' => 1,
                'borrow_date' => Carbon::now()->format('Y-m-d\TH:i'),
                'expected_return_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'purpose' => 'Test',
                'recorded_by' => 'Test',
            ])->assertStatus(403);
        }
    }
}
