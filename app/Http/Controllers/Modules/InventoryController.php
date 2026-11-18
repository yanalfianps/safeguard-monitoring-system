<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\InventoryBorrowing;
use App\Models\InventoryBorrowingReturn;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryUnit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public const KATEGORI_LIST = InventoryItem::CATEGORIES;
    public const SATUAN_LIST = InventoryItem::SATUAN_LIST;
    public const LOCATION_LIST = InventoryItem::LOCATIONS;

    /**
     * Halaman Utama Inventory (4 Tab: Master Barang, Riwayat Transaksi, Barang Terpinjam, History Peminjaman).
     */
    public function index(Request $request)
    {
        // Pembatasan akses tab untuk role admin_inventory
        if (Auth::check() && Auth::user()->isAdminInventory()) {
            if ($request->has('tab') && in_array($request->query('tab'), ['master', 'history'], true)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akses ditolak. Akun Admin Inventory hanya memiliki izin untuk mengakses fitur Peminjaman Barang.',
                    ], 403);
                }
                abort(403, 'Akses ditolak. Akun Admin Inventory hanya memiliki izin untuk mengakses fitur Peminjaman Barang.');
            }

            $activeTab = $request->query('tab', 'borrowed');
            if (!in_array($activeTab, ['borrowed', 'borrow_history'], true)) {
                $activeTab = 'borrowed';
            }
        } else {
            $activeTab = $request->query('tab', 'master');
            if (!in_array($activeTab, ['master', 'history', 'borrowed', 'borrow_history'], true)) {
                $activeTab = 'master';
            }
        }

        // Global / Dashboard Filter Parameters (Requirements B.5 & B.6)
        $filterItem = trim((string) $request->query('filter_item', $request->query('search', $request->query('trx_search', ''))));
        $filterCategory = trim((string) $request->query('filter_category', $request->query('kategori', $request->query('trx_kategori', ''))));
        $filterLocation = trim((string) $request->query('filter_location', $request->query('lokasi', $request->query('trx_lokasi', ''))));
        $filterDateStart = $request->query('filter_date_start', $request->query('trx_start'));
        $filterDateEnd = $request->query('filter_date_end', $request->query('trx_end'));
        $filterPic = trim((string) $request->query('filter_pic', $request->query('trx_pic', '')));

        // 1. Query untuk Ringkasan Dashboard Inventory (B.5 & B.6)
        $summaryItemsQuery = InventoryItem::query()->where('is_active', true);

        if ($filterItem !== '') {
            $summaryItemsQuery->where(function ($q) use ($filterItem) {
                $q->where('nama_barang', 'like', "%{$filterItem}%")
                    ->orWhere('item_name', 'like', "%{$filterItem}%")
                    ->orWhere('kode_barang', 'like', "%{$filterItem}%")
                    ->orWhere('item_code', 'like', "%{$filterItem}%");
            });
        }

        if ($filterCategory !== '') {
            $summaryItemsQuery->where(function ($q) use ($filterCategory) {
                $q->where('kategori', $filterCategory)
                    ->orWhere('category', $filterCategory);
            });
        }

        if ($filterDateStart || $filterDateEnd || $filterPic !== '') {
            $summaryItemsQuery->whereHas('transactions', function ($tq) use ($filterDateStart, $filterDateEnd, $filterPic, $filterLocation) {
                if ($filterDateStart) {
                    $tq->whereDate('tanggal', '>=', $filterDateStart);
                }
                if ($filterDateEnd) {
                    $tq->whereDate('tanggal', '<=', $filterDateEnd);
                }
                if ($filterPic !== '') {
                    $tq->where(function ($pq) use ($filterPic) {
                        $pq->where('petugas', 'like', "%{$filterPic}%")
                            ->orWhere('person_in_charge', 'like', "%{$filterPic}%");
                    });
                }
                if ($filterLocation !== '') {
                    $tq->where('location', $filterLocation);
                }
            });
        }

        $summaryItems = $summaryItemsQuery->get();

        $getRelevantStock = function (InventoryItem $item) use ($filterLocation): int {
            if ($filterLocation === InventoryItem::LOCATION_WAREHOUSE) {
                return (int) $item->stock_warehouse;
            }
            if ($filterLocation === InventoryItem::LOCATION_HSSE) {
                return (int) $item->stock_hsse;
            }
            return (int) $item->stock_warehouse + (int) $item->stock_hsse;
        };

        // Ringkasan Utama Dashboard Inventory (Requirement B.5):
        // - Total Barang: jumlah jenis barang yang memiliki stok tersedia > 0
        // - Total Jumlah Barang: akumulasi jumlah stok seluruh barang di Warehouse dan Gudang HSSE
        // - Jenis Barang: jumlah jenis barang yang memiliki stok tersedia
        $itemsWithAvailableStock = $summaryItems->filter(fn (InventoryItem $i) => $getRelevantStock($i) > 0);
        $totalBarang = (int) $itemsWithAvailableStock->count();
        $totalJumlahBarang = (int) $summaryItems->sum(fn (InventoryItem $i) => $getRelevantStock($i));
        $jenisBarang = (int) $itemsWithAvailableStock->count();

        $totalStokWarehouse = (int) $summaryItems->sum('stock_warehouse');
        $totalStokHsse = (int) $summaryItems->sum('stock_hsse');

        // Daftar barang master unik untuk seluruh dropdown (hanya 1 entri per master barang)
        $allItems = InventoryItem::where('is_active', true)
            ->orderBy('nama_barang', 'asc')
            ->orderBy('kode_barang', 'asc')
            ->get()
            ->unique('id')
            ->values();
        $totalMasuk = (int) InventoryTransaction::whereIn('jenis_transaksi', ['masuk', 'Masuk'])->sum('jumlah');
        $totalKeluar = (int) InventoryTransaction::whereIn('jenis_transaksi', ['keluar', 'Keluar'])->sum('jumlah');
        $totalStokTersedia = $totalJumlahBarang;
        $totalStokBersihTersedia = $totalJumlahBarang;
        $totalItemJenis = $jenisBarang;
        $stokAmanCount = $totalBarang;
        $perluPengadaanCount = (int) $summaryItems->filter(fn (InventoryItem $i) => $getRelevantStock($i) <= 0)->count();

        // Statistik Peminjaman Dasar (dan akan disesuaikan dengan filter aktif pada tab Barang Terpinjam)
        $activeBorrowingsQuery = InventoryBorrowing::whereColumn('quantity_borrowed', '>', 'quantity_returned');
        if ($request->filled('borrow_search')) {
            $bkwStat = trim((string) $request->borrow_search);
            $activeBorrowingsQuery->where(function ($q) use ($bkwStat) {
                $q->where('borrow_code', 'like', "%{$bkwStat}%")
                    ->orWhere('borrower_name', 'like', "%{$bkwStat}%")
                    ->orWhere('department', 'like', "%{$bkwStat}%")
                    ->orWhere('purpose', 'like', "%{$bkwStat}%")
                    ->orWhereHas('item', function ($sub) use ($bkwStat) {
                        $sub->where('item_name', 'like', "%{$bkwStat}%")
                            ->orWhere('nama_barang', 'like', "%{$bkwStat}%")
                            ->orWhere('item_code', 'like', "%{$bkwStat}%")
                            ->orWhere('kode_barang', 'like', "%{$bkwStat}%");
                    });
            });
        }
        if ($request->filled('borrow_location')) {
            $activeBorrowingsQuery->where('location', $request->borrow_location);
        }
        if ($request->filled('borrow_start')) {
            $activeBorrowingsQuery->whereDate('borrow_date', '>=', $request->borrow_start);
        }
        if ($request->filled('borrow_end')) {
            $activeBorrowingsQuery->whereDate('borrow_date', '<=', $request->borrow_end);
        }
        if ($request->filled('expected_start')) {
            $activeBorrowingsQuery->whereDate('expected_return_date', '>=', $request->expected_start);
        }
        if ($request->filled('expected_end')) {
            $activeBorrowingsQuery->whereDate('expected_return_date', '<=', $request->expected_end);
        }

        $activeBorrowingsFiltered = $activeBorrowingsQuery->get();
        $totalPeminjamanAktif = $activeBorrowingsFiltered->count(); // Jumlah Peminjaman
        $totalUnitTerpinjam = (int) $activeBorrowingsFiltered->sum(fn ($b) => max(0, (int) $b->quantity_borrowed - (int) $b->quantity_returned)); // Jumlah Barang Dipinjam
        $totalJenisBarangTerpinjam = $activeBorrowingsFiltered->pluck('inventory_item_id')->unique()->count();
        $totalPeminjamanOverdue = $activeBorrowingsFiltered->filter(fn ($b) => $b->is_overdue)->count(); // Melewati Tenggat
        $totalHistoryPeminjaman = InventoryBorrowing::whereColumn('quantity_borrowed', '<=', 'quantity_returned')->count();

        // 2. Query Tab 1: Master Barang
        $itemsQuery = InventoryItem::with([
            'transactions' => function ($q) {
                $q->whereIn('jenis_transaksi', ['masuk', 'Masuk'])
                    ->whereNotNull('expired_date')
                    ->orderBy('expired_date', 'asc');
            },
        ]);

        if ($filterItem !== '') {
            $itemsQuery->where(function ($q) use ($filterItem) {
                $q->where('kode_barang', 'like', "%{$filterItem}%")
                    ->orWhere('item_code', 'like', "%{$filterItem}%")
                    ->orWhere('nama_barang', 'like', "%{$filterItem}%")
                    ->orWhere('item_name', 'like', "%{$filterItem}%")
                    ->orWhere('keterangan', 'like', "%{$filterItem}%");
            });
        }

        if ($filterCategory !== '') {
            $itemsQuery->where(function ($q) use ($filterCategory) {
                $q->where('kategori', $filterCategory)
                    ->orWhere('category', $filterCategory);
            });
        }

        if ($filterLocation === InventoryItem::LOCATION_WAREHOUSE) {
            $itemsQuery->where('stock_warehouse', '>', 0);
        } elseif ($filterLocation === InventoryItem::LOCATION_HSSE) {
            $itemsQuery->where('stock_hsse', '>', 0);
        }

        if ($filterDateStart || $filterDateEnd || $filterPic !== '') {
            $itemsQuery->whereHas('transactions', function ($tq) use ($filterDateStart, $filterDateEnd, $filterPic) {
                if ($filterDateStart) {
                    $tq->whereDate('tanggal', '>=', $filterDateStart);
                }
                if ($filterDateEnd) {
                    $tq->whereDate('tanggal', '<=', $filterDateEnd);
                }
                if ($filterPic !== '') {
                    $tq->where(function ($pq) use ($filterPic) {
                        $pq->where('petugas', 'like', "%{$filterPic}%")
                            ->orWhere('person_in_charge', 'like', "%{$filterPic}%");
                    });
                }
            });
        }

        $items = $itemsQuery->orderBy('nama_barang', 'asc')
            ->paginate(15, ['*'], 'items_page')
            ->withQueryString();

        // 3. Query Tab 2: Riwayat Transaksi (Requirements D.12 - D.14)
        $trxQuery = InventoryTransaction::with('inventory');

        if ($request->filled('trx_jenis')) {
            $jenis = strtolower((string) $request->trx_jenis);
            $trxQuery->where(function ($q) use ($jenis) {
                $q->where('jenis_transaksi', $jenis)
                    ->orWhere('transaction_type', ucfirst($jenis));
            });
        }

        if ($filterCategory !== '') {
            $trxQuery->whereHas('inventory', function ($q) use ($filterCategory) {
                $q->where('kategori', $filterCategory)
                    ->orWhere('category', $filterCategory);
            });
        }

        if ($filterLocation !== '') {
            $trxQuery->where('location', $filterLocation);
        }

        if ($filterDateStart) {
            $trxQuery->whereDate('tanggal', '>=', $filterDateStart);
        }

        if ($filterDateEnd) {
            $trxQuery->whereDate('tanggal', '<=', $filterDateEnd);
        }

        if ($filterPic !== '') {
            $trxQuery->where(function ($q) use ($filterPic) {
                $q->where('petugas', 'like', "%{$filterPic}%")
                    ->orWhere('person_in_charge', 'like', "%{$filterPic}%");
            });
        }

        if ($filterItem !== '') {
            $trxQuery->where(function ($q) use ($filterItem) {
                $q->where('transaction_code', 'like', "%{$filterItem}%")
                    ->orWhere('petugas', 'like', "%{$filterItem}%")
                    ->orWhere('keterangan', 'like', "%{$filterItem}%")
                    ->orWhereHas('inventory', function ($sub) use ($filterItem) {
                        $sub->where('nama_barang', 'like', "%{$filterItem}%")
                            ->orWhere('item_name', 'like', "%{$filterItem}%")
                            ->orWhere('kode_barang', 'like', "%{$filterItem}%")
                            ->orWhere('item_code', 'like', "%{$filterItem}%");
                    });
            });
        }

        $transactions = $trxQuery->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'trx_page')
            ->withQueryString();

        // 4. Query Tab 3: Barang Terpinjam (Requirements F.18, F.19, F.20)
        // Tabel Barang Terpinjam menampilkan peminjaman yang masih memiliki barang belum dikembalikan seluruhnya,
        // atau menyesuaikan filter status peminjaman yang dipilih pengguna.
        $borrowQuery = InventoryBorrowing::with(['item', 'returns']);

        $borrowStatus = $request->query('borrow_status', 'Semua Status');
        if ($borrowStatus === 'Sedang Dipinjam') {
            $borrowQuery->whereColumn('quantity_borrowed', '>', 'quantity_returned')
                ->where(function ($q) {
                    $q->whereNull('expected_return_date')
                        ->orWhereDate('expected_return_date', '>=', Carbon::today());
                });
        } elseif ($borrowStatus === 'Melewati Tenggat' || $borrowStatus === 'overdue') {
            $borrowQuery->whereColumn('quantity_borrowed', '>', 'quantity_returned')
                ->whereNotNull('expected_return_date')
                ->whereDate('expected_return_date', '<', Carbon::today());
        } elseif ($borrowStatus === 'Sudah Dikembalikan' || $borrowStatus === 'returned') {
            $borrowQuery->whereColumn('quantity_borrowed', '<=', 'quantity_returned');
        } else {
            // Default ('Semua Status' / 'active' pada tab Barang Terpinjam): tampilkan hanya peminjaman yang belum selesai seluruhnya
            $borrowQuery->whereColumn('quantity_borrowed', '>', 'quantity_returned');
        }

        if ($request->filled('borrow_search')) {
            $bkw = trim((string) $request->borrow_search);
            $borrowQuery->where(function ($q) use ($bkw) {
                $q->where('borrow_code', 'like', "%{$bkw}%")
                    ->orWhere('borrower_name', 'like', "%{$bkw}%")
                    ->orWhere('department', 'like', "%{$bkw}%")
                    ->orWhere('purpose', 'like', "%{$bkw}%")
                    ->orWhereHas('item', function ($sub) use ($bkw) {
                        $sub->where('item_name', 'like', "%{$bkw}%")
                            ->orWhere('nama_barang', 'like', "%{$bkw}%")
                            ->orWhere('item_code', 'like', "%{$bkw}%")
                            ->orWhere('kode_barang', 'like', "%{$bkw}%");
                    });
            });
        } elseif ($filterItem !== '' && $activeTab === 'borrowed') {
            $borrowQuery->where(function ($q) use ($filterItem) {
                $q->where('borrow_code', 'like', "%{$filterItem}%")
                    ->orWhere('borrower_name', 'like', "%{$filterItem}%")
                    ->orWhereHas('item', function ($sub) use ($filterItem) {
                        $sub->where('nama_barang', 'like', "%{$filterItem}%")
                            ->orWhere('kode_barang', 'like', "%{$filterItem}%");
                    });
            });
        }

        $borrowLocation = $request->query('borrow_location', $activeTab === 'borrowed' ? $filterLocation : '');
        if (!empty($borrowLocation)) {
            $borrowQuery->where('location', $borrowLocation);
        }

        if ($request->filled('borrow_start')) {
            $borrowQuery->whereDate('borrow_date', '>=', $request->borrow_start);
        }
        if ($request->filled('borrow_end')) {
            $borrowQuery->whereDate('borrow_date', '<=', $request->borrow_end);
        }
        if ($request->filled('expected_start')) {
            $borrowQuery->whereDate('expected_return_date', '>=', $request->expected_start);
        }
        if ($request->filled('expected_end')) {
            $borrowQuery->whereDate('expected_return_date', '<=', $request->expected_end);
        }

        $borrowings = $borrowQuery->orderBy('borrow_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15, ['*'], 'borrow_page')
            ->withQueryString();

        // 5. Query Tab 4: History Peminjaman (Requirements G.28 - G.31)
        // Hanya menampilkan transaksi peminjaman yang sudah selesai sepenuhnya (remaining_quantity == 0)
        $borrowHistoryQuery = InventoryBorrowing::with(['item', 'returns'])
            ->whereColumn('quantity_borrowed', '<=', 'quantity_returned');

        if ($request->filled('history_search')) {
            $hkw = trim((string) $request->history_search);
            $borrowHistoryQuery->where(function ($q) use ($hkw) {
                $q->where('borrow_code', 'like', "%{$hkw}%")
                    ->orWhere('borrower_name', 'like', "%{$hkw}%")
                    ->orWhere('department', 'like', "%{$hkw}%")
                    ->orWhereHas('item', function ($sub) use ($hkw) {
                        $sub->where('nama_barang', 'like', "%{$hkw}%")
                            ->orWhere('item_name', 'like', "%{$hkw}%")
                            ->orWhere('kode_barang', 'like', "%{$hkw}%")
                            ->orWhere('item_code', 'like', "%{$hkw}%");
                    });
            });
        }

        if ($request->filled('history_department')) {
            $dept = trim((string) $request->history_department);
            $borrowHistoryQuery->where('department', 'like', "%{$dept}%");
        }

        if ($request->filled('history_item_id')) {
            $borrowHistoryQuery->where('inventory_item_id', $request->history_item_id);
        }

        if ($request->filled('history_location')) {
            $borrowHistoryQuery->where('location', $request->history_location);
        }

        if ($request->filled('history_borrow_start')) {
            $borrowHistoryQuery->whereDate('borrow_date', '>=', $request->history_borrow_start);
        }
        if ($request->filled('history_borrow_end')) {
            $borrowHistoryQuery->whereDate('borrow_date', '<=', $request->history_borrow_end);
        }
        if ($request->filled('history_return_start')) {
            $borrowHistoryQuery->whereDate('actual_return_date', '>=', $request->history_return_start);
        }
        if ($request->filled('history_return_end')) {
            $borrowHistoryQuery->whereDate('actual_return_date', '<=', $request->history_return_end);
        }

        $borrowHistories = $borrowHistoryQuery->orderBy('actual_return_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15, ['*'], 'history_borrow_page')
            ->withQueryString();

        // Daftar Kategori, Satuan, Lokasi, PIC, dan Departemen untuk Dropdown Filter
        $dbCategories = InventoryItem::select('kategori')
            ->whereNotNull('kategori')
            ->distinct()
            ->pluck('kategori')
            ->toArray();
        $kategoriList = array_values(array_unique(array_merge(self::KATEGORI_LIST, $dbCategories)));
        $satuanList = InventoryUnit::getActiveNames();
        $allUnits = InventoryUnit::orderBy('name')->get();
        $locationList = self::LOCATION_LIST;

        $picList = InventoryTransaction::select('petugas')
            ->whereNotNull('petugas')
            ->where('petugas', '!=', '')
            ->distinct()
            ->orderBy('petugas')
            ->pluck('petugas');

        $departmentList = InventoryBorrowing::select('department')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $nextItemCode = InventoryItem::generateUniqueItemCode();
        $nextBorrowCode = InventoryBorrowing::generateBorrowCode();

        // Modal rincian kompatibilitas
        $modalMasukList = [];
        $modalKeluarList = [];
        $modalStokAmanList = [];
        $modalPerluPengadaanList = [];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'items' => $items,
                'transactions' => $transactions,
                'borrowings' => $borrowings,
                'borrowHistories' => $borrowHistories,
                'totalBarang' => $totalBarang,
                'totalJumlahBarang' => $totalJumlahBarang,
                'jenisBarang' => $jenisBarang,
                'totalMasuk' => $totalMasuk,
                'totalKeluar' => $totalKeluar,
                'stokAmanCount' => $stokAmanCount,
                'perluPengadaanCount' => $perluPengadaanCount,
                'totalPeminjamanAktif' => $totalPeminjamanAktif,
                'totalUnitTerpinjam' => $totalUnitTerpinjam,
            ]);
        }

        return view('inventory.index', [
            'activeTab' => $activeTab,
            'items' => $items,
            'inventories' => $allItems,
            'transactions' => $transactions,
            'borrowings' => $borrowings,
            'borrowHistories' => $borrowHistories,
            'totalBarang' => $totalBarang,
            'totalJumlahBarang' => $totalJumlahBarang,
            'jenisBarang' => $jenisBarang,
            'totalStokWarehouse' => $totalStokWarehouse,
            'totalStokHsse' => $totalStokHsse,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'totalStokTersedia' => $totalStokTersedia,
            'totalStokBersihTersedia' => $totalStokBersihTersedia,
            'totalItemJenis' => $totalItemJenis,
            'stokAmanCount' => $stokAmanCount,
            'perluPengadaanCount' => $perluPengadaanCount,
            'totalPeminjamanAktif' => $totalPeminjamanAktif,
            'totalUnitTerpinjam' => $totalUnitTerpinjam,
            'totalJenisBarangTerpinjam' => $totalJenisBarangTerpinjam,
            'totalPeminjamanOverdue' => $totalPeminjamanOverdue,
            'totalHistoryPeminjaman' => $totalHistoryPeminjaman,
            'kategoriList' => $kategoriList,
            'satuanList' => $satuanList,
            'units' => $allUnits,
            'locationList' => $locationList,
            'picList' => $picList,
            'departmentList' => $departmentList,
            'nextItemCode' => $nextItemCode,
            'nextBorrowCode' => $nextBorrowCode,
            'filterItem' => $filterItem,
            'filterCategory' => $filterCategory,
            'filterLocation' => $filterLocation,
            'filterDateStart' => $filterDateStart,
            'filterDateEnd' => $filterDateEnd,
            'filterPic' => $filterPic,
            'modalMasukList' => $modalMasukList,
            'modalKeluarList' => $modalKeluarList,
            'modalStokAmanList' => $modalStokAmanList,
            'modalPerluPengadaanList' => $modalPerluPengadaanList,
        ]);
    }

    /**
     * Endpoint JSON untuk riwayat transaksi.
     */
    public function transactions(Request $request)
    {
        $query = InventoryTransaction::with('inventory')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('inventory_id')) {
            $query->where('inventory_item_id', $request->inventory_id);
        }

        if ($request->filled('jenis_transaksi')) {
            $query->where('jenis_transaksi', strtolower((string) $request->jenis_transaksi));
        }

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }

    /**
     * Endpoint API pencarian barang untuk autocomplete / live search.
     * Mendukung pencarian berdasarkan kata / bagian kata pada nama_barang, kode_barang, dan kategori.
     */
    public function searchItems(Request $request): JsonResponse
    {
        $rawQuery = trim((string) $request->query('q', ''));
        if ($rawQuery === '') {
            return response()->json([
                'success' => true,
                'items' => [],
            ]);
        }

        $keywords = array_values(array_unique(array_filter(explode(' ', $rawQuery))));

        $query = InventoryItem::query()
            ->where('is_active', true)
            ->where(function ($q) use ($rawQuery, $keywords) {
                // Pencarian substring menyeluruh
                $q->where('nama_barang', 'like', "%{$rawQuery}%")
                    ->orWhere('kode_barang', 'like', "%{$rawQuery}%")
                    ->orWhere('kategori', 'like', "%{$rawQuery}%");

                // Pencarian per kata
                foreach ($keywords as $kw) {
                    $q->orWhere('nama_barang', 'like', "%{$kw}%")
                      ->orWhere('kode_barang', 'like', "%{$kw}%")
                      ->orWhere('kategori', 'like', "%{$kw}%");
                }
            });

        $items = $query->orderBy('nama_barang')
            ->limit(40)
            ->get([
                'id',
                'kode_barang',
                'nama_barang',
                'kategori',
                'satuan',
                'stock_warehouse',
                'stock_hsse',
            ]);

        return response()->json([
            'success' => true,
            'items' => $items,
            'data' => $items,
        ]);
    }

    /**
     * Ambil seluruh master satuan beserta status dan jumlah pemakaian.
     */
    public function getUnits(): JsonResponse
    {
        InventoryUnit::ensureDefaults();
        $units = $this->formatUnitsList();

        return response()->json([
            'success' => true,
            'data' => $units,
            'units' => $units,
            'satuan_list' => InventoryUnit::getActiveNames(),
        ]);
    }

    /**
     * Tambah Master Satuan Baru.
     */
    public function storeUnit(Request $request): JsonResponse
    {
        $name = preg_replace('/\s+/', ' ', trim((string) $request->input('name')));
        $request->merge(['name' => $name]);

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama satuan wajib diisi.',
            'name.max' => 'Nama satuan maksimal 50 karakter.',
        ]);

        // Cek duplikasi case-insensitive
        $existing = InventoryUnit::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Satuan '{$existing->name}' sudah terdaftar dalam sistem.",
            ], 422);
        }

        $unit = InventoryUnit::create([
            'name' => $validated['name'],
            'is_active' => array_key_exists('is_active', $validated) && $validated['is_active'] !== null
                ? (bool) $validated['is_active']
                : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Satuan '{$unit->name}' berhasil ditambahkan.",
            'data' => $unit,
            'units' => $this->formatUnitsList(),
            'satuan_list' => InventoryUnit::getActiveNames(),
        ]);
    }

    /**
     * Edit / Update Nama Satuan.
     */
    public function updateUnit(Request $request, $id): JsonResponse
    {
        $unit = InventoryUnit::findOrFail($id);
        $name = preg_replace('/\s+/', ' ', trim((string) $request->input('name')));
        $request->merge(['name' => $name]);

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama satuan wajib diisi.',
            'name.max' => 'Nama satuan maksimal 50 karakter.',
        ]);

        // Cek duplikasi case-insensitive kecuali dirinya sendiri
        $existing = InventoryUnit::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where('id', '!=', $unit->id)
            ->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Satuan '{$existing->name}' sudah terdaftar dalam sistem.",
            ], 422);
        }

        $oldName = $unit->name;
        $unit->name = $validated['name'];
        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
            $unit->is_active = (bool) $validated['is_active'];
        }
        $unit->save();

        // Jika nama berubah, perbarui referensi nama di inventory_items dan inventory_transactions
        if ($oldName !== $unit->name) {
            DB::table('inventory_items')
                ->where('satuan', $oldName)
                ->update(['satuan' => $unit->name]);

            DB::table('inventory_items')
                ->where('unit_measure', $oldName)
                ->update(['unit_measure' => $unit->name]);

            DB::table('inventory_transactions')
                ->where('satuan', $oldName)
                ->update(['satuan' => $unit->name]);
        }

        return response()->json([
            'success' => true,
            'message' => "Satuan berhasil diperbarui menjadi '{$unit->name}'.",
            'data' => $unit,
            'units' => $this->formatUnitsList(),
            'satuan_list' => InventoryUnit::getActiveNames(),
        ]);
    }

    /**
     * Hapus Satuan (hanya jika belum pernah digunakan).
     */
    public function destroyUnit(Request $request, $id): JsonResponse
    {
        $unit = InventoryUnit::findOrFail($id);
        $usageCount = $unit->usage_count;

        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'can_deactivate' => true,
                'message' => "Satuan '{$unit->name}' tidak dapat dihapus karena sudah digunakan pada {$usageCount} data barang/transaksi. Anda dapat menonaktifkannya.",
            ], 422);
        }

        $name = $unit->name;
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => "Satuan '{$name}' berhasil dihapus.",
            'units' => $this->formatUnitsList(),
            'satuan_list' => InventoryUnit::getActiveNames(),
        ]);
    }

    /**
     * Toggle status aktif / nonaktif Satuan.
     */
    public function toggleUnit(Request $request, $id): JsonResponse
    {
        $unit = InventoryUnit::findOrFail($id);
        $target = $request->has('is_active')
            ? $request->boolean('is_active')
            : !$unit->is_active;

        $unit->is_active = $target;
        $unit->save();

        $statusText = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Satuan '{$unit->name}' berhasil {$statusText}.",
            'is_active' => $unit->is_active,
            'units' => $this->formatUnitsList(),
            'satuan_list' => InventoryUnit::getActiveNames(),
        ]);
    }

    /**
     * Format daftar satuan untuk respons JSON.
     */
    protected function formatUnitsList(): array
    {
        return InventoryUnit::orderBy('name')->get()->map(function (InventoryUnit $unit) {
            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'is_active' => $unit->is_active,
                'usage_count' => $unit->usage_count,
                'can_delete' => $unit->can_delete,
            ];
        })->all();
    }

    /**
     * Simpan Master Barang Baru (Requirements A.1 - A.4 & Anti-Duplikasi Master Barang).
     * Jika barang yang sudah terdaftar masuk kembali, tambahkan stok ke master barang yang sudah ada
     * dan catat sebagai transaksi masuk (batch), bukan membuat master barang baru.
     */
    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'barang_id' => 'nullable|string|max:50',
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'satuan' => 'required|string|max:50',
            'stock_warehouse' => 'nullable|integer|min:0',
            'stock_hsse' => 'nullable|integer|min:0',
            'stok_awal' => 'nullable|integer|min:0',
            'stok_sekarang' => 'nullable|integer|min:0',
            'lokasi_default' => 'nullable|string|max:150',
            'keterangan' => 'required|string',
            'expired_date' => 'nullable|date',
        ], [
            'nama_barang.required' => 'Nama Barang wajib diisi.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'satuan.required' => 'Satuan wajib dipilih.',
            'keterangan.required' => 'Keterangan wajib diisi.',
        ]);

        // Hitung stok Warehouse & Gudang HSSE
        $hasDualInput = $request->has('stock_warehouse') || $request->has('stock_hsse');
        if ($hasDualInput) {
            $stockWarehouse = max(0, (int) $request->input('stock_warehouse', 0));
            $stockHsse = max(0, (int) $request->input('stock_hsse', 0));
        } else {
            $legacyStock = max(0, (int) ($request->input('stok_awal', $request->input('stok_sekarang', 0))));
            $locNorm = InventoryItem::normalizeLocation($request->input('lokasi_default'));
            if ($locNorm === InventoryItem::LOCATION_HSSE) {
                $stockWarehouse = 0;
                $stockHsse = $legacyStock;
            } else {
                $stockWarehouse = $legacyStock;
                $stockHsse = 0;
            }
        }

        $totalStock = $stockWarehouse + $stockHsse;
        $requestedCode = !empty($validated['barang_id']) ? trim($validated['barang_id']) : '';
        $normalizedName = mb_strtolower(trim($validated['nama_barang']));
        $normalizedCategory = mb_strtolower(trim($validated['kategori']));
        $normalizedUnit = mb_strtolower(trim($validated['satuan']));

        // 1. Periksa apakah kode barang sudah terdaftar di Master Barang
        $existingByCode = null;
        if ($requestedCode !== '') {
            $existingByCode = InventoryItem::whereRaw('LOWER(TRIM(kode_barang)) = ?', [mb_strtolower($requestedCode)])
                ->orWhereRaw('LOWER(TRIM(item_code)) = ?', [mb_strtolower($requestedCode)])
                ->first();
        }

        if ($existingByCode) {
            $existingCodeName = mb_strtolower(trim($existingByCode->nama_barang ?: $existingByCode->item_name));
            if ($existingCodeName !== $normalizedName) {
                throw ValidationException::withMessages([
                    'barang_id' => "Kode barang \"{$requestedCode}\" sudah digunakan oleh barang lain ({$existingByCode->nama_barang}). Gunakan kode barang unik.",
                ]);
            }
        }

        // 2. Periksa apakah barang dengan nama, kategori, dan satuan yang sama sudah terdaftar di Master Barang
        $existingItem = $existingByCode;
        if (!$existingItem) {
            $candidatesByName = InventoryItem::where(function ($q) use ($normalizedName) {
                $q->whereRaw('LOWER(TRIM(nama_barang)) = ?', [$normalizedName])
                    ->orWhereRaw('LOWER(TRIM(item_name)) = ?', [$normalizedName]);
            })->get();

            foreach ($candidatesByName as $candidate) {
                $candCat = mb_strtolower(trim((string) ($candidate->kategori ?: $candidate->category)));
                $candUnit = mb_strtolower(trim((string) ($candidate->satuan ?: $candidate->unit_measure)));
                $candCode = strtoupper(trim((string) ($candidate->kode_barang ?: $candidate->item_code)));

                // Jika pengguna secara eksplisit memasukkan kode khusus yang berbeda (bukan kode otomatis form)
                // dan mengonfirmasi barang berbeda (confirm_distinct_code), jangan gabungkan otomatis tanpa pemeriksaan.
                $isExplicitDifferentCode = $requestedCode !== ''
                    && strtoupper($requestedCode) !== $candCode
                    && $request->boolean('confirm_distinct_code');

                if (!$isExplicitDifferentCode && $candCat === $normalizedCategory && $candUnit === $normalizedUnit) {
                    $existingItem = $candidate;
                    break;
                }
            }
        }

        // 3. Jika barang sudah terdaftar di Master Barang:
        //    - Jika ada stok masuk (> 0), tambahkan stok pada master barang yang sudah ada & catat sebagai transaksi masuk (batch).
        //    - Jika stok 0, tolak pembuatan master duplikat dengan pesan validasi yang jelas.
        if ($existingItem) {
            if ($totalStock <= 0) {
                throw ValidationException::withMessages([
                    'nama_barang' => "Barang \"{$existingItem->nama_barang}\" ([{$existingItem->kode_barang}]) sudah terdaftar di Master Barang. Setiap jenis barang hanya boleh memiliki satu data master.",
                ]);
            }

            $item = DB::transaction(function () use ($existingItem, $validated, $stockWarehouse, $stockHsse) {
                /** @var InventoryItem $lockedItem */
                $lockedItem = InventoryItem::where('id', $existingItem->id)->lockForUpdate()->firstOrFail();

                $pic = Auth::user()->name ?? 'Administrator HSSE';
                $notes = !empty($validated['keterangan'])
                    ? $validated['keterangan']
                    : 'Penambahan stok masuk pada Master Barang';
                $expInput = !empty($validated['expired_date']) ? $validated['expired_date'] : null;

                if ($stockWarehouse > 0) {
                    $lockedItem->addBatchStock(InventoryItem::LOCATION_WAREHOUSE, $stockWarehouse, $expInput, $notes);

                    InventoryTransaction::create([
                        'inventory_item_id' => $lockedItem->id,
                        'transaction_type' => 'Masuk',
                        'jenis_transaksi' => 'masuk',
                        'location' => InventoryItem::LOCATION_WAREHOUSE,
                        'satuan' => $lockedItem->satuan,
                        'quantity' => $stockWarehouse,
                        'jumlah' => $stockWarehouse,
                        'transaction_date' => now()->toDateString(),
                        'tanggal' => now()->toDateString(),
                        'person_in_charge' => $pic,
                        'petugas' => $pic,
                        'notes' => $notes,
                        'keterangan' => $notes,
                        'expired_date' => $expInput,
                    ]);
                }

                if ($stockHsse > 0) {
                    $lockedItem->addBatchStock(InventoryItem::LOCATION_HSSE, $stockHsse, $expInput, $notes);

                    InventoryTransaction::create([
                        'inventory_item_id' => $lockedItem->id,
                        'transaction_type' => 'Masuk',
                        'jenis_transaksi' => 'masuk',
                        'location' => InventoryItem::LOCATION_HSSE,
                        'satuan' => $lockedItem->satuan,
                        'quantity' => $stockHsse,
                        'jumlah' => $stockHsse,
                        'transaction_date' => now()->toDateString(),
                        'tanggal' => now()->toDateString(),
                        'person_in_charge' => $pic,
                        'petugas' => $pic,
                        'notes' => $notes,
                        'keterangan' => $notes,
                        'expired_date' => $expInput,
                    ]);
                }

                return $lockedItem->fresh();
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'merged_existing' => true,
                    'data' => $item,
                ], 200);
            }

            return redirect()->route('inventory.index', ['tab' => 'master'])
                ->with('success', "Barang \"{$item->nama_barang}\" ([{$item->kode_barang}]) sudah terdaftar. Stok masuk berhasil ditambahkan ke master barang yang ada.");
        }

        $autoCode = $requestedCode !== ''
            ? $requestedCode
            : InventoryItem::generateUniqueItemCode($validated['kategori']);

        $item = DB::transaction(function () use ($validated, $autoCode, $stockWarehouse, $stockHsse, $totalStock) {
            $item = InventoryItem::create([
                'item_code' => $autoCode,
                'kode_barang' => $autoCode,
                'item_name' => $validated['nama_barang'],
                'nama_barang' => $validated['nama_barang'],
                'category' => $validated['kategori'],
                'kategori' => $validated['kategori'],
                'unit_measure' => $validated['satuan'],
                'satuan' => $validated['satuan'],
                'stock_warehouse' => $stockWarehouse,
                'stock_hsse' => $stockHsse,
                'stock' => $totalStock,
                'stok_sekarang' => $totalStock,
                'minimum_stock' => 0,
                'stok_minimum' => 0,
                'description' => $validated['keterangan'],
                'keterangan' => $validated['keterangan'],
                'expired_date' => $validated['expired_date'] ?? null,
                'is_active' => true,
            ]);

            $pic = Auth::user()->name ?? 'Administrator HSSE';

            // Catat batch stok awal ke riwayat transaksi per lokasi jika > 0
            if ($stockWarehouse > 0) {
                InventoryTransaction::create([
                    'inventory_item_id' => $item->id,
                    'transaction_type' => 'Masuk',
                    'jenis_transaksi' => 'masuk',
                    'location' => InventoryItem::LOCATION_WAREHOUSE,
                    'satuan' => $item->satuan,
                    'quantity' => $stockWarehouse,
                    'jumlah' => $stockWarehouse,
                    'transaction_date' => now()->toDateString(),
                    'tanggal' => now()->toDateString(),
                    'person_in_charge' => $pic,
                    'petugas' => $pic,
                    'notes' => 'Stok awal registrasi Master Barang (Warehouse)',
                    'keterangan' => 'Stok awal registrasi Master Barang (Warehouse)',
                    'expired_date' => $validated['expired_date'] ?? null,
                ]);
            }

            if ($stockHsse > 0) {
                InventoryTransaction::create([
                    'inventory_item_id' => $item->id,
                    'transaction_type' => 'Masuk',
                    'jenis_transaksi' => 'masuk',
                    'location' => InventoryItem::LOCATION_HSSE,
                    'satuan' => $item->satuan,
                    'quantity' => $stockHsse,
                    'jumlah' => $stockHsse,
                    'transaction_date' => now()->toDateString(),
                    'tanggal' => now()->toDateString(),
                    'person_in_charge' => $pic,
                    'petugas' => $pic,
                    'notes' => 'Stok awal registrasi Master Barang (Gudang HSSE)',
                    'keterangan' => 'Stok awal registrasi Master Barang (Gudang HSSE)',
                    'expired_date' => $validated['expired_date'] ?? null,
                ]);
            }

            $item->ensureBatchesSynced();

            return $item;
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $item], 201);
        }

        return redirect()->route('inventory.index', ['tab' => 'master'])
            ->with('success', "Master barang \"{$item->nama_barang}\" ({$item->kode_barang}) berhasil ditambahkan.");
    }

    /**
     * Perbarui Master Barang (Requirements A.1 - A.4).
     */
    public function updateItem(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'barang_id' => 'nullable|string|max:50|unique:inventory_items,item_code,' . $item->id,
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'satuan' => 'required|string|max:50',
            'stock_warehouse' => 'nullable|integer|min:0',
            'stock_hsse' => 'nullable|integer|min:0',
            'stok_sekarang' => 'nullable|integer|min:0',
            'lokasi_default' => 'nullable|string|max:150',
            'keterangan' => 'required|string',
            'expired_date' => 'nullable|date',
        ], [
            'nama_barang.required' => 'Nama Barang wajib diisi.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'satuan.required' => 'Satuan wajib dipilih.',
            'keterangan.required' => 'Keterangan wajib diisi.',
        ]);

        $item = DB::transaction(function () use ($request, $validated, $id) {
            /** @var InventoryItem $lockedItem */
            $lockedItem = InventoryItem::where('id', $id)->lockForUpdate()->firstOrFail();

            $hasDualInput = $request->has('stock_warehouse') || $request->has('stock_hsse');
            if ($hasDualInput) {
                $stockWarehouse = max(0, (int) $request->input('stock_warehouse', $lockedItem->stock_warehouse));
                $stockHsse = max(0, (int) $request->input('stock_hsse', $lockedItem->stock_hsse));
            } elseif ($request->has('stok_sekarang')) {
                $legacyStock = max(0, (int) $request->input('stok_sekarang'));
                $locNorm = InventoryItem::normalizeLocation($request->input('lokasi_default', $lockedItem->location));
                if ($locNorm === InventoryItem::LOCATION_HSSE) {
                    $stockWarehouse = 0;
                    $stockHsse = $legacyStock;
                } else {
                    $stockWarehouse = $legacyStock;
                    $stockHsse = 0;
                }
            } else {
                $stockWarehouse = (int) $lockedItem->stock_warehouse;
                $stockHsse = (int) $lockedItem->stock_hsse;
            }

            $code = !empty($validated['barang_id']) ? trim($validated['barang_id']) : $lockedItem->item_code;

            $lockedItem->update([
                'item_code' => $code,
                'kode_barang' => $code,
                'item_name' => $validated['nama_barang'],
                'nama_barang' => $validated['nama_barang'],
                'category' => $validated['kategori'],
                'kategori' => $validated['kategori'],
                'unit_measure' => $validated['satuan'],
                'satuan' => $validated['satuan'],
                'stock_warehouse' => $stockWarehouse,
                'stock_hsse' => $stockHsse,
                'stock' => $stockWarehouse + $stockHsse,
                'stok_sekarang' => $stockWarehouse + $stockHsse,
                'description' => $validated['keterangan'],
                'keterangan' => $validated['keterangan'],
                'expired_date' => array_key_exists('expired_date', $validated) ? $validated['expired_date'] : $lockedItem->expired_date,
            ]);

            $lockedItem->ensureBatchesSynced();

            return $lockedItem->fresh();
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $item]);
        }

        return redirect()->route('inventory.index', ['tab' => 'master'])
            ->with('success', "Master barang \"{$item->nama_barang}\" berhasil diperbarui.");
    }

    /**
     * Hapus Master Barang dengan Proteksi Relasi Transaksi & Peminjaman (Requirement A.3).
     */
    public function destroyItem(Request $request, $id)
    {
        $result = DB::transaction(function () use ($id) {
            /** @var InventoryItem $item */
            $item = InventoryItem::where('id', $id)->lockForUpdate()->firstOrFail();

            if (!$item->canBeDeleted()) {
                return [
                    'deleted' => false,
                    'message' => "Master barang \"{$item->nama_barang}\" ({$item->kode_barang}) tidak dapat dihapus karena sudah memiliki riwayat transaksi atau peminjaman.",
                ];
            }

            $nama = $item->nama_barang;
            $item->delete();

            return ['deleted' => true, 'nama' => $nama];
        });

        if (!$result['deleted']) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $result['message']], 422);
            }
            return redirect()->route('inventory.index', ['tab' => 'master'])
                ->with('error', $result['message']);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('inventory.index', ['tab' => 'master'])
            ->with('success', "Master barang \"{$result['nama']}\" berhasil dihapus.");
    }

    /**
     * Catat Transaksi Mutasi Barang — Mendukung Multi-Barang Sekaligus & Single Item (Requirements C.7 - C.11).
     */
    public function storeTransaction(Request $request)
    {
        // Normalisasi input: apakah multi-baris (`items`) atau satu barang (`inventory_id`)
        $isMultiItem = is_array($request->input('items')) && count($request->input('items')) > 0;
        $isStrictForm = $request->boolean('_strict_trx_form');

        if ($isMultiItem) {
            $validated = $request->validate([
                'jenis_transaksi' => 'required|in:masuk,keluar,Masuk,Keluar,penyesuaian,Penyesuaian',
                'tanggal_transaksi' => 'required|date',
                'petugas' => 'required|string|max:150',
                'keterangan' => 'nullable|string|max:1000',
                'items' => 'required|array|min:1',
                'items.*.inventory_id' => 'required|exists:inventory_items,id',
                'items.*.location' => 'required|string|in:Warehouse,Gudang HSSE',
                'items.*.satuan' => 'required|string|max:50',
                'items.*.jumlah' => 'required|integer|min:0',
                'items.*.expired_date' => 'nullable|date',
            ], [
                'jenis_transaksi.required' => 'Jenis transaksi wajib dipilih.',
                'tanggal_transaksi.required' => 'Tanggal transaksi wajib diisi.',
                'petugas.required' => 'Petugas pencatat wajib diisi.',
                'items.required' => 'Minimal satu baris barang harus diisi.',
                'items.*.inventory_id.required' => 'Barang wajib dipilih pada setiap baris.',
                'items.*.location.required' => 'Lokasi penyimpanan wajib dipilih pada setiap baris.',
                'items.*.satuan.required' => 'Satuan wajib dipilih pada setiap baris.',
                'items.*.jumlah.required' => 'Kuantitas wajib diisi pada setiap baris.',
                'items.*.jumlah.min' => 'Kuantitas tidak boleh negatif.',
            ]);

            $rows = $validated['items'];
        } else {
            if (!$request->filled('petugas') && $request->filled('nama_pengisi')) {
                $request->merge(['petugas' => $request->input('nama_pengisi')]);
            }

            $validated = $request->validate([
                'inventory_id' => 'required|exists:inventory_items,id',
                'jenis_transaksi' => 'required|in:masuk,keluar,Masuk,Keluar,penyesuaian,Penyesuaian',
                'location' => ($isStrictForm ? 'required' : 'nullable') . '|string|in:Warehouse,Gudang HSSE',
                'satuan' => ($isStrictForm ? 'required' : 'nullable') . '|string|max:50',
                'jumlah' => 'required|integer|min:0',
                'tanggal_transaksi' => ($isStrictForm ? 'required' : 'nullable') . '|date',
                'petugas' => ($isStrictForm ? 'required' : 'nullable') . '|string|max:150',
                'keterangan' => 'nullable|string|max:1000',
                'expired_date' => 'nullable|date',
            ], [
                'inventory_id.required' => 'Barang wajib dipilih.',
                'location.required' => 'Lokasi wajib dipilih.',
                'satuan.required' => 'Satuan wajib dipilih.',
                'jumlah.required' => 'Kuantitas wajib diisi.',
                'jumlah.min' => 'Kuantitas tidak boleh negatif.',
                'petugas.required' => 'Petugas pencatat wajib diisi.',
                'tanggal_transaksi.required' => 'Tanggal transaksi wajib diisi.',
            ]);

            $rows = [[
                'inventory_id' => $validated['inventory_id'],
                'location' => $validated['location'] ?? null,
                'satuan' => $validated['satuan'] ?? null,
                'jumlah' => (int) $validated['jumlah'],
                'expired_date' => $validated['expired_date'] ?? null,
            ]];
        }

        $jenisLower = strtolower((string) $validated['jenis_transaksi']);
        if ($jenisLower !== 'penyesuaian') {
            foreach ($rows as $idx => $row) {
                if ((int) $row['jumlah'] < 1) {
                    throw ValidationException::withMessages([
                        'jumlah' => 'Kuantitas minimal adalah 1.',
                        "items.{$idx}.jumlah" => 'Kuantitas minimal adalah 1.',
                    ]);
                }
            }
        }

        $tanggalTrx = $validated['tanggal_transaksi'] ?? now()->toDateString();
        $picName = !empty($validated['petugas'])
            ? trim($validated['petugas'])
            : (Auth::user()->name ?? 'Administrator HSSE');
        $notes = $validated['keterangan'] ?? null;

        $createdTransactions = [];

        DB::transaction(function () use ($rows, $jenisLower, $tanggalTrx, $picName, $notes, &$createdTransactions) {
            foreach ($rows as $idx => $row) {
                /** @var InventoryItem $item */
                $item = InventoryItem::lockForUpdate()->findOrFail($row['inventory_id']);
                $qty = (int) $row['jumlah'];

                // Tentukan lokasi
                if (!empty($row['location'])) {
                    $location = InventoryItem::normalizeLocation($row['location']);
                } else {
                    // Fallback jika lokasi tidak dikirim: pilih lokasi yang memiliki stok untuk 'keluar'
                    if ($jenisLower === 'keluar' && $item->stock_warehouse < $qty && $item->stock_hsse >= $qty) {
                        $location = InventoryItem::LOCATION_HSSE;
                    } else {
                        $location = InventoryItem::LOCATION_WAREHOUSE;
                    }
                }

                $satuan = !empty($row['satuan']) ? $row['satuan'] : $item->satuan;
                $rowExpiredDate = !empty($row['expired_date']) ? $row['expired_date'] : null;
                $rowNotes = $notes;

                if ($jenisLower === 'keluar') {
                    $availableAtLoc = $item->getStockAtLocation($location);
                    if ($qty > $availableAtLoc) {
                        throw ValidationException::withMessages([
                            'jumlah' => "Stok \"{$item->nama_barang}\" di {$location} tidak mencukupi! (Tersedia: {$availableAtLoc} {$item->satuan}, Diminta: {$qty} {$satuan}).",
                            "items.{$idx}.jumlah" => "Stok \"{$item->nama_barang}\" di {$location} tidak mencukupi (Tersedia: {$availableAtLoc} {$item->satuan}).",
                        ]);
                    }
                    // Kurangi dari kelompok stok yang tersedia secara otomatis (FEFO atau sesuai tanggal jika diisi)
                    $item->deductBatchStock($location, $qty, $rowExpiredDate, false);
                    $trxTypeEnum = 'Keluar';
                } elseif ($jenisLower === 'penyesuaian') {
                    // Penyesuaian Stock Opname: hitung selisih antara stok fisik aktual ($qty) dan stok sistem di lokasi
                    $currentAtLoc = $item->getStockAtLocation($location);
                    $delta = $qty - $currentAtLoc;

                    if ($delta > 0) {
                        $item->addBatchStock($location, $delta, $rowExpiredDate, $rowNotes ?: "Penyesuaian Stock Opname (+{$delta})");
                    } elseif ($delta < 0) {
                        $item->deductBatchStock($location, abs($delta), $rowExpiredDate, false);
                    } else {
                        $item->ensureBatchesSynced();
                    }

                    $trxTypeEnum = $delta < 0 ? 'Keluar' : 'Masuk';
                    $deltaText = $delta >= 0 ? ('+' . $delta) : (string) $delta;
                    $opnameInfo = "[Stock Opname {$location}: {$currentAtLoc} -> {$qty} ({$deltaText})]";
                    $rowNotes = $rowNotes ? ($opnameInfo . ' ' . $rowNotes) : $opnameInfo;
                } else {
                    // Barang Masuk: tambahkan ke lokasi & kelompok tanggal expired ini
                    $item->addBatchStock($location, $qty, $rowExpiredDate, $notes);
                    $trxTypeEnum = 'Masuk';
                }

                $createdTransactions[] = InventoryTransaction::create([
                    'inventory_item_id' => $item->id,
                    'transaction_type' => $trxTypeEnum,
                    'jenis_transaksi' => $jenisLower,
                    'location' => $location,
                    'satuan' => $satuan,
                    'quantity' => $qty,
                    'jumlah' => $qty,
                    'transaction_date' => $tanggalTrx,
                    'tanggal' => $tanggalTrx,
                    'unit' => $location,
                    'person_in_charge' => $picName,
                    'petugas' => $picName,
                    'notes' => $rowNotes,
                    'keterangan' => $rowNotes,
                    'expired_date' => $rowExpiredDate,
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'transactions' => $createdTransactions,
            ], 201);
        }

        $countItems = count($createdTransactions);
        $labelJenis = match ($jenisLower) {
            'keluar' => 'Barang Keluar',
            'penyesuaian' => 'Penyesuaian Stok',
            default => 'Barang Masuk',
        };

        return redirect()->route('inventory.index', ['tab' => 'history'])
            ->with('success', "Transaksi {$labelJenis} ({$countItems} item) berhasil dicatat dan stok lokasi telah diperbarui.");
    }

    /**
     * Catat Peminjaman Barang — Mendukung Pemilihan Satu atau Beberapa Kelompok Expired (Requirements E.16 - E.21 & Revisi Data Expired).
     */
    public function storeBorrowing(Request $request)
    {
        if (!$request->filled('inventory_item_id') && $request->filled('item_id')) {
            $request->merge(['inventory_item_id' => $request->input('item_id')]);
        }

        if (!$request->filled('department') && $request->filled('borrower_division')) {
            $request->merge(['department' => $request->input('borrower_division')]);
        }

        $isStrictForm = $request->boolean('_strict_borrow_form', false);
        $rawBatches = $request->input('borrow_batches');
        $hasMultiBatchRows = is_array($rawBatches) && count($rawBatches) > 0;

        if ($hasMultiBatchRows) {
            $sumBatchQty = collect($rawBatches)->sum(fn ($r) => max(0, (int) ($r['quantity'] ?? 0)));
            if ($sumBatchQty > 0) {
                $request->merge(['quantity_borrowed' => $sumBatchQty]);
            }
        }

        $rules = [
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'location' => 'nullable|string|in:Warehouse,Gudang HSSE',
            'quantity_borrowed' => 'required|integer|min:1',
            'expired_date' => 'nullable|string|max:50',
            'borrow_batches' => 'nullable|array',
            'borrow_batches.*.expiry_key' => 'required_with:borrow_batches|string|max:50',
            'borrow_batches.*.quantity' => 'required_with:borrow_batches|integer|min:1',
            'borrower_name' => 'required|string|max:150',
            'department' => $isStrictForm ? 'required|string|max:150' : 'nullable|string|max:150',
            'borrow_date' => 'required|date',
            'expected_return_date' => $isStrictForm ? 'required|date|after_or_equal:borrow_date' : 'nullable|date|after_or_equal:borrow_date',
            'purpose' => $isStrictForm ? 'required|string|max:1000' : 'nullable|string|max:1000',
            'recorded_by' => $isStrictForm ? 'required|string|max:150' : 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ];

        $validated = $request->validate($rules, [
            'inventory_item_id.required' => 'Nama Barang wajib dipilih.',
            'quantity_borrowed.required' => 'Kuantitas peminjaman wajib diisi.',
            'quantity_borrowed.min' => 'Kuantitas peminjaman minimal 1.',
            'borrow_batches.*.expiry_key.required_with' => 'Kelompok Tanggal Expired wajib dipilih pada setiap baris.',
            'borrow_batches.*.quantity.required_with' => 'Jumlah dipinjam wajib diisi pada setiap baris kelompok expired.',
            'borrow_batches.*.quantity.min' => 'Jumlah dipinjam minimal 1 pada setiap baris.',
            'borrower_name.required' => 'Nama Peminjam wajib diisi.',
            'department.required' => 'Unit/Divisi/Departemen wajib diisi.',
            'expected_return_date.required' => 'Rencana Pengembalian wajib diisi.',
            'purpose.required' => 'Keperluan Pemakaian wajib diisi.',
            'recorded_by.required' => 'Petugas Pencatat wajib diisi.',
        ]);

        try {
            $borrowing = DB::transaction(function () use ($validated, $hasMultiBatchRows) {
                /** @var InventoryItem $item */
                $item = InventoryItem::lockForUpdate()->findOrFail($validated['inventory_item_id']);

                if ($item->is_consumable) {
                    throw new \RuntimeException(
                        "Barang \"{$item->nama_barang}\" termasuk kategori habis pakai ({$item->kategori}) dan tidak dapat dipinjam. Gunakan menu Catat Transaksi Keluar."
                    );
                }

                $qtyRequested = (int) $validated['quantity_borrowed'];

                $item->ensureBatchesSynced();

                // Hitung stok tersedia per lokasi yang benar-benar digunakan sistem
                $whStock = max(0, (int) $item->stock_warehouse);
                $hsseStock = max(0, (int) $item->stock_hsse);
                $availableLocations = [];
                if ($whStock > 0) {
                    $availableLocations[InventoryItem::LOCATION_WAREHOUSE] = $whStock;
                }
                if ($hsseStock > 0) {
                    $availableLocations[InventoryItem::LOCATION_HSSE] = $hsseStock;
                }

                // 1. Jika barang tidak memiliki stok tersedia di lokasi mana pun, tolak transaksi
                if (count($availableLocations) === 0) {
                    throw new \RuntimeException(
                        "Barang \"{$item->nama_barang}\" tidak memiliki stok tersedia di lokasi mana pun (Warehouse: 0, Gudang HSSE: 0). Peminjaman tidak dapat diproses."
                    );
                }

                $submittedLoc = !empty($validated['location'])
                    ? InventoryItem::normalizeLocation($validated['location'])
                    : null;

                // 2. Jika hanya tersedia di 1 lokasi, otomatis pilih lokasi tersebut dan validasi kecocokan lokasi
                if (count($availableLocations) === 1) {
                    $singleLoc = array_key_first($availableLocations);
                    if ($submittedLoc && $submittedLoc !== $singleLoc) {
                        throw new \RuntimeException(
                            "Barang \"{$item->nama_barang}\" hanya tersedia di {$singleLoc}. Lokasi {$submittedLoc} tidak valid."
                        );
                    }
                    $location = $singleLoc;
                } else {
                    // 3. Jika tersedia di 2 lokasi atau lebih, pengguna harus memilih lokasi yang benar-benar memiliki stok
                    if (!$submittedLoc || !isset($availableLocations[$submittedLoc])) {
                        throw new \RuntimeException(
                            "Silakan pilih lokasi penyimpanan yang memiliki stok tersedia untuk barang \"{$item->nama_barang}\"."
                        );
                    }
                    $location = $submittedLoc;
                }

                $availableAtLoc = $availableLocations[$location] ?? 0;

                // 6 & 8. Jumlah maksimum peminjaman mengikuti stok di lokasi terpilih
                if ($qtyRequested > $availableAtLoc) {
                    throw new \RuntimeException(
                        "Stok \"{$item->nama_barang}\" di {$location} tidak mencukupi untuk dipinjam! (Tersedia di {$location}: {$availableAtLoc} {$item->satuan}, Diminta: {$qtyRequested} {$item->satuan})."
                    );
                }

                $allAllocations = [];

                if ($hasMultiBatchRows && !empty($validated['borrow_batches'])) {
                    // Gabungkan baris yang memilih kelompok expired yang sama agar validasi stok kelompok akurat
                    $groupedRequests = [];
                    foreach ($validated['borrow_batches'] as $bRow) {
                        $expKey = trim((string) ($bRow['expiry_key'] ?? 'UNRECORDED'));
                        if ($expKey === '' || $expKey === 'Belum Dicatat') {
                            $expKey = 'UNRECORDED';
                        }
                        $rowQty = max(0, (int) ($bRow['quantity'] ?? 0));
                        if ($rowQty <= 0) {
                            continue;
                        }
                        $groupedRequests[$expKey] = ($groupedRequests[$expKey] ?? 0) + $rowQty;
                    }

                    foreach ($groupedRequests as $expKey => $grpQty) {
                        $allocs = $item->deductBatchStock($location, $grpQty, $expKey, true);
                        foreach ($allocs as $a) {
                            $allAllocations[] = $a;
                        }
                    }
                } else {
                    $singleExp = $validated['expired_date'] ?? null;
                    $strictSingle = !empty($singleExp);
                    $allAllocations = $item->deductBatchStock($location, $qtyRequested, $singleExp, $strictSingle);
                }

                $expiredBatchesPayload = [];
                $earliestExpDate = null;

                foreach ($allAllocations as $alloc) {
                    $expDateVal = !empty($alloc['expired_date']) ? $alloc['expired_date'] : null;
                    if ($expDateVal !== null) {
                        if ($earliestExpDate === null || Carbon::parse($expDateVal)->lt(Carbon::parse($earliestExpDate))) {
                            $earliestExpDate = $expDateVal;
                        }
                    }
                    $expiredBatchesPayload[] = [
                        'batch_id' => $alloc['batch_id'] ?? null,
                        'location' => $location,
                        'expired_date' => $expDateVal,
                        'quantity_borrowed' => (int) ($alloc['quantity'] ?? 0),
                        'quantity_returned' => 0,
                    ];
                }

                $recordedBy = !empty($validated['recorded_by'])
                    ? trim($validated['recorded_by'])
                    : (Auth::user()->name ?? 'Petugas HSSE');

                $borrowing = new InventoryBorrowing([
                    'borrow_code' => InventoryBorrowing::generateBorrowCode(),
                    'inventory_item_id' => $item->id,
                    'location' => $location,
                    'quantity_borrowed' => $qtyRequested,
                    'quantity_returned' => 0,
                    'borrower_name' => $validated['borrower_name'],
                    'department' => $validated['department'] ?? 'Operasional',
                    'borrow_date' => $validated['borrow_date'],
                    'expected_return_date' => $validated['expected_return_date'] ?? null,
                    'expired_date' => $earliestExpDate,
                    'expired_batches' => $expiredBatchesPayload,
                    'status' => InventoryBorrowing::STATUS_BORROWED,
                    'purpose' => $validated['purpose'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'recorded_by' => $recordedBy,
                    'user_id' => Auth::id(),
                ]);
                $borrowing->stockAlreadyAdjusted = true;
                $borrowing->save();

                return $borrowing;
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('inventory.index', ['tab' => 'borrowed'])
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.index', ['tab' => 'borrowed'])
            ->with('success', "Peminjaman barang ({$borrowing->borrow_code}) atas nama {$borrowing->borrower_name} dari {$borrowing->location} berhasil dicatat.");
    }

    /**
     * Catat Pengembalian Barang — Mendukung Pengembalian Sebagian (Bertahap) & Penuh serta Mengembalikan ke Kelompok Expired Asal.
     */
    public function returnBorrowing(Request $request, InventoryBorrowing $borrowing)
    {
        if (!$request->filled('return_condition') && $request->filled('item_condition')) {
            $request->merge(['return_condition' => $request->input('item_condition')]);
        }

        $validated = $request->validate([
            'quantity_returned' => 'required|integer|min:1',
            'actual_return_date' => 'required|date',
            'return_location' => 'nullable|string|in:Warehouse,Gudang HSSE',
            'return_condition' => 'nullable|string|max:100',
            'recorded_by' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ], [
            'quantity_returned.required' => 'Jumlah barang yang dikembalikan wajib diisi.',
            'quantity_returned.min' => 'Jumlah pengembalian minimal 1.',
            'actual_return_date.required' => 'Tanggal pengembalian aktual wajib diisi.',
        ]);

        try {
            $result = DB::transaction(function () use ($borrowing, $validated) {
                /** @var InventoryBorrowing $lockedBorrowing */
                $lockedBorrowing = InventoryBorrowing::lockForUpdate()->findOrFail($borrowing->id);
                /** @var InventoryItem $item */
                $item = InventoryItem::lockForUpdate()->findOrFail($lockedBorrowing->inventory_item_id);

                $remaining = max(0, (int) $lockedBorrowing->quantity_borrowed - (int) $lockedBorrowing->quantity_returned);
                if ($remaining <= 0) {
                    throw new \RuntimeException('Transaksi peminjaman ini sudah dikembalikan sepenuhnya.');
                }

                $qtyReturnNow = (int) $validated['quantity_returned'];
                if ($qtyReturnNow > $remaining) {
                    throw new \RuntimeException("Jumlah pengembalian ({$qtyReturnNow}) melebihi jumlah yang masih dipinjam ({$remaining})!");
                }

                $returnLocation = InventoryItem::normalizeLocation(
                    $validated['return_location'] ?? $lockedBorrowing->location ?? InventoryItem::LOCATION_WAREHOUSE
                );

                $recordedBy = !empty($validated['recorded_by'])
                    ? trim($validated['recorded_by'])
                    : (Auth::user()->name ?? 'Petugas HSSE');

                $returnNumber = $lockedBorrowing->returns()->count() + 1;

                // Simpan riwayat pengembalian ke tabel child tanpa menimpa pengembalian sebelumnya
                InventoryBorrowingReturn::create([
                    'inventory_borrowing_id' => $lockedBorrowing->id,
                    'return_number' => $returnNumber,
                    'quantity_returned' => $qtyReturnNow,
                    'return_date' => $validated['actual_return_date'],
                    'return_location' => $returnLocation,
                    'condition' => $validated['return_condition'] ?? 'Baik',
                    'recorded_by' => $recordedBy,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Kembalikan stok ke kelompok expired (batch) dan lokasi pengembalian secara proporsional
                $batches = is_array($lockedBorrowing->expired_batches) ? $lockedBorrowing->expired_batches : [];
                $toRestore = $qtyReturnNow;

                if (!empty($batches)) {
                    foreach ($batches as $idx => &$bRow) {
                        if ($toRestore <= 0) {
                            break;
                        }
                        $bBorrowed = max(0, (int) ($bRow['quantity_borrowed'] ?? 0));
                        $bReturned = max(0, (int) ($bRow['quantity_returned'] ?? 0));
                        $bRemaining = max(0, $bBorrowed - $bReturned);

                        if ($bRemaining <= 0) {
                            continue;
                        }

                        $restoreHere = min($toRestore, $bRemaining);
                        $bRow['quantity_returned'] = $bReturned + $restoreHere;
                        $toRestore -= $restoreHere;

                        $expDate = !empty($bRow['expired_date']) ? $bRow['expired_date'] : null;
                        $item->addBatchStock(
                            $returnLocation,
                            $restoreHere,
                            $expDate,
                            "Pengembalian peminjaman {$lockedBorrowing->borrow_code}"
                        );
                    }
                    unset($bRow);
                }

                if ($toRestore > 0) {
                    $fallbackExp = $lockedBorrowing->expired_date ? $lockedBorrowing->expired_date->format('Y-m-d') : null;
                    $item->addBatchStock(
                        $returnLocation,
                        $toRestore,
                        $fallbackExp,
                        "Pengembalian peminjaman {$lockedBorrowing->borrow_code}"
                    );
                }

                $newTotalReturned = (int) $lockedBorrowing->quantity_returned + $qtyReturnNow;
                $isFullyReturned = $newTotalReturned >= (int) $lockedBorrowing->quantity_borrowed;

                $lockedBorrowing->quantity_returned = $newTotalReturned;
                $lockedBorrowing->actual_return_date = $validated['actual_return_date'];
                $lockedBorrowing->expired_batches = $batches;
                $lockedBorrowing->status = $isFullyReturned
                    ? InventoryBorrowing::STATUS_RETURNED
                    : InventoryBorrowing::STATUS_BORROWED;
                $lockedBorrowing->save();

                return [
                    'borrowing' => $lockedBorrowing,
                    'return_number' => $returnNumber,
                    'return_location' => $returnLocation,
                    'is_fully_returned' => $isFullyReturned,
                ];
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('inventory.index', ['tab' => 'borrowed'])
                ->with('error', $e->getMessage());
        }

        $targetTab = $result['is_fully_returned'] ? 'borrow_history' : 'borrowed';
        $statusMsg = $result['is_fully_returned']
            ? "Seluruh barang pada peminjaman {$borrowing->borrow_code} telah selesai dikembalikan ke {$result['return_location']} dan masuk ke History Peminjaman."
            : "Pengembalian ke-{$result['return_number']} ({$validated['quantity_returned']} unit) berhasil dicatat ke {$result['return_location']}. Sisa masih dipinjam: {$result['borrowing']->remaining_quantity} unit.";

        return redirect()->route('inventory.index', ['tab' => $targetTab])
            ->with('success', $statusMsg);
    }

    /**
     * Edit / Perpanjang Rencana Tanggal Pengembalian (Requirement F.25).
     */
    public function updateBorrowingDueDate(Request $request, InventoryBorrowing $borrowing)
    {
        $validated = $request->validate([
            'expected_return_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
        ], [
            'expected_return_date.required' => 'Rencana tanggal pengembalian baru wajib diisi.',
        ]);

        DB::transaction(function () use ($borrowing, $validated) {
            /** @var InventoryBorrowing $lockedBorrowing */
            $lockedBorrowing = InventoryBorrowing::where('id', $borrowing->id)->lockForUpdate()->firstOrFail();

            $history = is_array($lockedBorrowing->due_date_history) ? $lockedBorrowing->due_date_history : [];
            $history[] = [
                'previous_date' => $lockedBorrowing->expected_return_date ? $lockedBorrowing->expected_return_date->format('Y-m-d') : null,
                'new_date' => $validated['expected_return_date'],
                'reason' => $validated['reason'] ?? 'Perpanjangan masa peminjaman',
                'updated_by' => Auth::user()->name ?? 'Petugas HSSE',
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            $lockedBorrowing->expected_return_date = $validated['expected_return_date'];
            $lockedBorrowing->due_date_history = $history;
            $lockedBorrowing->save();
        });

        return redirect()->route('inventory.index', ['tab' => 'borrowed'])
            ->with('success', "Rencana pengembalian untuk {$borrowing->borrow_code} berhasil diperbarui menjadi " . Carbon::parse($validated['expected_return_date'])->format('d/m/Y') . '.');
    }

    /**
     * Export PDF / CSV Per Tab (Master Barang, Riwayat Transaksi, Barang Terpinjam, History Peminjaman).
     * Mengikuti filter aktif pada tab yang sedang dibuka (Requirements J.27 - J.29).
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $tab = $request->query('tab', 'master');
        if (!in_array($tab, ['master', 'history', 'borrowed', 'borrow_history'], true)) {
            $tab = 'master';
        }

        // Akun admin_inventory hanya diizinkan mengekspor tab peminjaman
        if (Auth::check() && Auth::user()->isAdminInventory() && in_array($tab, ['master', 'history'], true)) {
            abort(403, 'Akses ditolak. Akun Admin Inventory hanya memiliki izin untuk mengakses fitur Peminjaman Barang.');
        }

        $filterItem = trim((string) $request->query('filter_item', $request->query('trx_search', $request->query('search', ''))));
        $filterCategory = trim((string) $request->query('filter_category', $request->query('trx_kategori', $request->query('kategori', ''))));
        $filterLocation = trim((string) $request->query('filter_location', $request->query('trx_lokasi', $request->query('lokasi', ''))));
        $filterDateStart = $request->query('filter_date_start', $request->query('trx_start'));
        $filterDateEnd = $request->query('filter_date_end', $request->query('trx_end'));
        $filterPic = trim((string) $request->query('filter_pic', $request->query('trx_pic', '')));

        $userName = Auth::user()->name ?? 'Auditor HSSE';
        $generatedAt = now()->translatedFormat('d F Y, H:i') . ' WIB';

        // =========================================================================
        // TAB 1: MASTER BARANG -> Laporan_Master_Barang.pdf
        // =========================================================================
        if ($tab === 'master') {
            $itemsQuery = InventoryItem::with([
                'transactions' => function ($q) {
                    $q->whereIn('jenis_transaksi', ['masuk', 'Masuk'])
                        ->whereNotNull('expired_date')
                        ->orderBy('expired_date', 'asc');
                },
            ]);

            if ($filterItem !== '') {
                $itemsQuery->where(function ($q) use ($filterItem) {
                    $q->where('kode_barang', 'like', "%{$filterItem}%")
                        ->orWhere('item_code', 'like', "%{$filterItem}%")
                        ->orWhere('nama_barang', 'like', "%{$filterItem}%")
                        ->orWhere('item_name', 'like', "%{$filterItem}%")
                        ->orWhere('keterangan', 'like', "%{$filterItem}%");
                });
            }

            if ($filterCategory !== '') {
                $itemsQuery->where(function ($q) use ($filterCategory) {
                    $q->where('kategori', $filterCategory)
                        ->orWhere('category', $filterCategory);
                });
            }

            if ($filterLocation === InventoryItem::LOCATION_WAREHOUSE) {
                $itemsQuery->where('stock_warehouse', '>', 0);
            } elseif ($filterLocation === InventoryItem::LOCATION_HSSE) {
                $itemsQuery->where('stock_hsse', '>', 0);
            }

            if ($filterDateStart || $filterDateEnd || $filterPic !== '') {
                $itemsQuery->whereHas('transactions', function ($tq) use ($filterDateStart, $filterDateEnd, $filterPic) {
                    if ($filterDateStart) {
                        $tq->whereDate('tanggal', '>=', $filterDateStart);
                    }
                    if ($filterDateEnd) {
                        $tq->whereDate('tanggal', '<=', $filterDateEnd);
                    }
                    if ($filterPic !== '') {
                        $tq->where(function ($pq) use ($filterPic) {
                            $pq->where('petugas', 'like', "%{$filterPic}%")
                                ->orWhere('person_in_charge', 'like', "%{$filterPic}%");
                        });
                    }
                });
            }

            $records = $itemsQuery->orderBy('nama_barang', 'asc')->get();

            if ($format === 'csv') {
                $filename = 'Laporan_Master_Barang.csv';
                $headers = [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ];

                $callback = function () use ($records) {
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Stok Warehouse', 'Stok Gudang HSSE', 'Total Stok', 'Expired Date', 'Keterangan']);
                    foreach ($records as $it) {
                        fputcsv($out, [
                            $it->kode_barang,
                            $it->nama_barang,
                            $it->kategori,
                            $it->satuan,
                            $it->stock_warehouse,
                            $it->stock_hsse,
                            $it->current_stock,
                            $it->expired_date ? $it->expired_date->format('Y-m-d') : '-',
                            $it->keterangan ?? '-',
                        ]);
                    }
                    fclose($out);
                };

                return response()->stream($callback, 200, $headers);
            }

            $pdf = Pdf::loadView('exports.inventory-master-pdf', [
                'title' => 'Laporan Master Barang Inventory',
                'reportTitle' => 'LAPORAN MASTER BARANG INVENTORY',
                'moduleName' => 'Inventory — Master Barang',
                'filterPeriod' => ($filterDateStart || $filterDateEnd)
                    ? (($filterDateStart ?: 'Awal') . ' s/d ' . ($filterDateEnd ?: 'Sekarang'))
                    : 'Seluruh Data Master Barang',
                'filterScope' => ($filterLocation ?: 'Warehouse & Gudang HSSE') . ' • ' . ($filterCategory ?: 'Semua Kategori'),
                'totalRecords' => $records->count(),
                'records' => $records,
                'userName' => $userName,
                'generatedAt' => $generatedAt,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('Laporan_Master_Barang.pdf');
        }

        // =========================================================================
        // TAB 2: RIWAYAT TRANSAKSI -> Laporan_Riwayat_Transaksi.pdf
        // =========================================================================
        if ($tab === 'history') {
            $trxQuery = InventoryTransaction::with('inventory');

            if ($request->filled('trx_jenis')) {
                $jenis = strtolower((string) $request->trx_jenis);
                $trxQuery->where(function ($q) use ($jenis) {
                    $q->where('jenis_transaksi', $jenis)
                        ->orWhere('transaction_type', ucfirst($jenis));
                });
            }

            if ($filterCategory !== '') {
                $trxQuery->whereHas('inventory', function ($q) use ($filterCategory) {
                    $q->where('kategori', $filterCategory)
                        ->orWhere('category', $filterCategory);
                });
            }

            if ($filterLocation !== '') {
                $trxQuery->where('location', $filterLocation);
            }

            if ($filterDateStart) {
                $trxQuery->whereDate('tanggal', '>=', $filterDateStart);
            }

            if ($filterDateEnd) {
                $trxQuery->whereDate('tanggal', '<=', $filterDateEnd);
            }

            if ($filterPic !== '') {
                $trxQuery->where(function ($q) use ($filterPic) {
                    $q->where('petugas', 'like', "%{$filterPic}%")
                        ->orWhere('person_in_charge', 'like', "%{$filterPic}%");
                });
            }

            if ($filterItem !== '') {
                $trxQuery->where(function ($q) use ($filterItem) {
                    $q->where('transaction_code', 'like', "%{$filterItem}%")
                        ->orWhere('petugas', 'like', "%{$filterItem}%")
                        ->orWhere('keterangan', 'like', "%{$filterItem}%")
                        ->orWhereHas('inventory', function ($sub) use ($filterItem) {
                            $sub->where('nama_barang', 'like', "%{$filterItem}%")
                                ->orWhere('item_name', 'like', "%{$filterItem}%")
                                ->orWhere('kode_barang', 'like', "%{$filterItem}%")
                                ->orWhere('item_code', 'like', "%{$filterItem}%");
                        });
                });
            }

            $records = $trxQuery->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->get();

            if ($format === 'csv') {
                $filename = 'Laporan_Riwayat_Transaksi.csv';
                $headers = [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ];

                $callback = function () use ($records) {
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['Kode Transaksi', 'Tanggal Transaksi', 'Nama Barang', 'Kode Barang', 'Jenis Transaksi', 'Lokasi', 'Satuan', 'Kuantitas', 'PIC / Petugas Pencatat', 'Expired Date', 'Keterangan']);
                    foreach ($records as $trx) {
                        $inv = $trx->inventory;
                        fputcsv($out, [
                            $trx->transaction_code ?? ('TRX-' . $trx->id),
                            $trx->tanggal ? $trx->tanggal->format('Y-m-d') : '-',
                            $inv?->nama_barang ?? '-',
                            $inv?->kode_barang ?? '-',
                            ucfirst((string) $trx->jenis_transaksi),
                            $trx->location ?? 'Warehouse',
                            $trx->satuan ?? ($inv?->satuan ?? '-'),
                            $trx->jumlah,
                            $trx->petugas ?? '-',
                            $trx->expired_date ? $trx->expired_date->format('Y-m-d') : '-',
                            $trx->keterangan ?? '-',
                        ]);
                    }
                    fclose($out);
                };

                return response()->stream($callback, 200, $headers);
            }

            $pdf = Pdf::loadView('exports.inventory-history-pdf', [
                'title' => 'Laporan Riwayat Transaksi Inventory',
                'reportTitle' => 'LAPORAN RIWAYAT TRANSAKSI INVENTORY',
                'moduleName' => 'Inventory — Riwayat Transaksi',
                'filterPeriod' => ($filterDateStart || $filterDateEnd)
                    ? (($filterDateStart ?: 'Awal') . ' s/d ' . ($filterDateEnd ?: 'Sekarang'))
                    : 'Seluruh Riwayat Transaksi',
                'filterScope' => ($filterLocation ?: 'Warehouse & Gudang HSSE') . ' • ' . ($filterCategory ?: 'Semua Kategori'),
                'totalRecords' => $records->count(),
                'records' => $records,
                'userName' => $userName,
                'generatedAt' => $generatedAt,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('Laporan_Riwayat_Transaksi.pdf');
        }

        // =========================================================================
        // TAB 3: BARANG TERPINJAM -> Laporan_Barang_Terpinjam.pdf
        // Hanya memuat peminjaman yang belum selesai seluruhnya (quantity_borrowed > quantity_returned)
        // =========================================================================
        if ($tab === 'borrowed') {
            $borrowQuery = InventoryBorrowing::with(['item', 'returns'])
                ->whereColumn('quantity_borrowed', '>', 'quantity_returned');

            $borrowStatus = $request->query('borrow_status', 'Semua Status');
            if ($borrowStatus === 'Sedang Dipinjam') {
                $borrowQuery->where(function ($q) {
                    $q->whereNull('expected_return_date')
                        ->orWhereDate('expected_return_date', '>=', Carbon::today());
                });
            } elseif ($borrowStatus === 'Melewati Tenggat' || $borrowStatus === 'overdue') {
                $borrowQuery->whereNotNull('expected_return_date')
                    ->whereDate('expected_return_date', '<', Carbon::today());
            }

            if ($request->filled('borrow_search')) {
                $bkw = trim((string) $request->borrow_search);
                $borrowQuery->where(function ($q) use ($bkw) {
                    $q->where('borrow_code', 'like', "%{$bkw}%")
                        ->orWhere('borrower_name', 'like', "%{$bkw}%")
                        ->orWhere('department', 'like', "%{$bkw}%")
                        ->orWhere('purpose', 'like', "%{$bkw}%")
                        ->orWhereHas('item', function ($sub) use ($bkw) {
                            $sub->where('item_name', 'like', "%{$bkw}%")
                                ->orWhere('nama_barang', 'like', "%{$bkw}%")
                                ->orWhere('item_code', 'like', "%{$bkw}%")
                                ->orWhere('kode_barang', 'like', "%{$bkw}%");
                        });
                });
            }

            $borrowLocation = $request->query('borrow_location', $filterLocation);
            if (!empty($borrowLocation)) {
                $borrowQuery->where('location', $borrowLocation);
            }

            if ($request->filled('borrow_start')) {
                $borrowQuery->whereDate('borrow_date', '>=', $request->borrow_start);
            }
            if ($request->filled('borrow_end')) {
                $borrowQuery->whereDate('borrow_date', '<=', $request->borrow_end);
            }
            if ($request->filled('expected_start')) {
                $borrowQuery->whereDate('expected_return_date', '>=', $request->expected_start);
            }
            if ($request->filled('expected_end')) {
                $borrowQuery->whereDate('expected_return_date', '<=', $request->expected_end);
            }

            $records = $borrowQuery->orderBy('borrow_date', 'desc')->orderBy('id', 'desc')->get();

            $pdf = Pdf::loadView('exports.inventory-borrowed-pdf', [
                'title' => 'Laporan Barang Terpinjam Inventory',
                'reportTitle' => 'LAPORAN BARANG TERPINJAM INVENTORY',
                'moduleName' => 'Inventory — Barang Terpinjam (Belum Selesai)',
                'filterPeriod' => ($request->borrow_start || $request->borrow_end)
                    ? (($request->borrow_start ?: 'Awal') . ' s/d ' . ($request->borrow_end ?: 'Sekarang'))
                    : 'Seluruh Peminjaman Aktif',
                'filterScope' => ($borrowLocation ?: 'Warehouse & Gudang HSSE') . ' • Status: ' . $borrowStatus,
                'totalRecords' => $records->count(),
                'records' => $records,
                'userName' => $userName,
                'generatedAt' => $generatedAt,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('Laporan_Barang_Terpinjam.pdf');
        }

        // =========================================================================
        // TAB 4: HISTORY PEMINJAMAN -> Laporan_History_Peminjaman.pdf
        // Hanya memuat peminjaman yang sudah dikembalikan seluruhnya (quantity_borrowed <= quantity_returned)
        // =========================================================================
        $borrowHistoryQuery = InventoryBorrowing::with(['item', 'returns'])
            ->whereColumn('quantity_borrowed', '<=', 'quantity_returned');

        if ($request->filled('history_search')) {
            $hkw = trim((string) $request->history_search);
            $borrowHistoryQuery->where(function ($q) use ($hkw) {
                $q->where('borrow_code', 'like', "%{$hkw}%")
                    ->orWhere('borrower_name', 'like', "%{$hkw}%")
                    ->orWhere('department', 'like', "%{$hkw}%")
                    ->orWhereHas('item', function ($sub) use ($hkw) {
                        $sub->where('nama_barang', 'like', "%{$hkw}%")
                            ->orWhere('item_name', 'like', "%{$hkw}%")
                            ->orWhere('kode_barang', 'like', "%{$hkw}%")
                            ->orWhere('item_code', 'like', "%{$hkw}%");
                    });
            });
        }

        if ($request->filled('history_department')) {
            $dept = trim((string) $request->history_department);
            $borrowHistoryQuery->where('department', 'like', "%{$dept}%");
        }

        if ($request->filled('history_item_id')) {
            $borrowHistoryQuery->where('inventory_item_id', $request->history_item_id);
        }

        if ($request->filled('history_location')) {
            $borrowHistoryQuery->where('location', $request->history_location);
        }

        if ($request->filled('history_borrow_start')) {
            $borrowHistoryQuery->whereDate('borrow_date', '>=', $request->history_borrow_start);
        }
        if ($request->filled('history_borrow_end')) {
            $borrowHistoryQuery->whereDate('borrow_date', '<=', $request->history_borrow_end);
        }
        if ($request->filled('history_return_start')) {
            $borrowHistoryQuery->whereDate('actual_return_date', '>=', $request->history_return_start);
        }
        if ($request->filled('history_return_end')) {
            $borrowHistoryQuery->whereDate('actual_return_date', '<=', $request->history_return_end);
        }

        $records = $borrowHistoryQuery->orderBy('actual_return_date', 'desc')->orderBy('id', 'desc')->get();

        $pdf = Pdf::loadView('exports.inventory-borrow-history-pdf', [
            'title' => 'Laporan History Peminjaman Inventory',
            'reportTitle' => 'LAPORAN HISTORY PEMINJAMAN INVENTORY',
            'moduleName' => 'Inventory — History Peminjaman Selesai',
            'filterPeriod' => ($request->history_borrow_start || $request->history_borrow_end)
                ? (($request->history_borrow_start ?: 'Awal') . ' s/d ' . ($request->history_borrow_end ?: 'Sekarang'))
                : 'Seluruh History Peminjaman Selesai',
            'filterScope' => ($request->history_location ?: 'Warehouse & Gudang HSSE') . ($request->history_department ? ' • Divisi: ' . $request->history_department : ''),
            'totalRecords' => $records->count(),
            'records' => $records,
            'userName' => $userName,
            'generatedAt' => $generatedAt,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_History_Peminjaman.pdf');
    }
}
