<?php

namespace Tests\Feature\DailyWork;

use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\Sikka;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyWorkViewAndFilterImprovementsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin.dw.improvements@geodipa.co.id'],
            [
                'name' => 'Admin DW Improvements',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'aktif',
            ]
        );
        $this->adminUser->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'aktif']);

        DailyWorkCompany::ensureRequired();
        Sikka::ensureDefaults();
    }

    /**
     * 1. Hapus badge status Selesai pada Semua Riwayat.
     *    - Tab Semua Riwayat tidak merender badge Selesai.
     *    - Nama pekerjaan dan kode pekerjaan (#DW-XXXX) tetap ada.
     *    - Status selesai tetap tersimpan di database.
     *    - Tombol centang Pekerjaan Selesai tidak muncul di Semua Riwayat.
     */
    public function test_semua_riwayat_does_not_render_selesai_status_badge(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $completedWork = DailyWork::create([
            'name' => 'Pekerjaan Selesai Riwayat Test',
            'unit' => 'ADP',
            'sika' => 'Cold Work',
            'start_date' => $today,
            'end_date' => $today,
            'status' => DailyWork::STATUS_COMPLETED,
            'location' => 'Area Turbin',
        ]);

        $this->assertDatabaseHas('daily_works', [
            'id' => $completedWork->id,
            'status' => DailyWork::STATUS_COMPLETED,
        ]);

        // Cek via endpoint JSON mode 'all'
        $response = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'all',
        ]));
        $response->assertStatus(200);

        $works = collect($response->json('works'));
        $found = $works->firstWhere('id', $completedWork->id);
        $this->assertNotNull($found);
        $this->assertEquals(DailyWork::STATUS_COMPLETED, $found['status']);

        // Cek script Blade index: statusBadge hanya di-render saat currentMode === 'today'
        $viewResponse = $this->actingAs($this->adminUser)->get(route('daily-work.index'));
        $viewResponse->assertStatus(200);

        // Pastikan view logic memeriksa mode === 'today' sebelum membuat badge selesai
        $viewResponse->assertSee("currentMode === 'today'", false);
        $viewResponse->assertSee("canManageDailyWork && (currentMode === 'today') && !isCompleted", false);
    }

    /**
     * 2. Urutkan data berdasarkan nama perusahaan A–Z, lalu nama pekerjaan A–Z pada kedua tab.
     *    - Urutan primer: unit ASC (A-Z).
     *    - Urutan sekunder: name ASC (A-Z).
     *    - Urutan tersier: start_date DESC.
     *    - Urutan kuarter: id DESC.
     */
    public function test_sorting_alphabetical_by_company_name_a_to_z_on_both_tabs(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $yesterday = Carbon::yesterday('Asia/Jakarta')->toDateString();

        // Buat beberapa pekerjaan:
        // - WCP: Pekerjaan Zebra WCP
        // - BISMO: Pekerjaan Alfa BISMO
        // - ADP: dua pekerjaan berbeda nama (Zebra dan Alfa) pada hari ini
        // - ADP: dua pekerjaan dengan nama pekerjaan sama (Alfa) namun tanggal berbeda (hari ini vs kemarin)
        $workWcp = DailyWork::create([
            'name' => 'Pekerjaan Zebra WCP',
            'unit' => 'WCP',
            'start_date' => $today,
            'end_date' => $today,
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Lokasi WCP',
        ]);

        $workBismo = DailyWork::create([
            'name' => 'Pekerjaan Alfa BISMO',
            'unit' => 'BISMO',
            'start_date' => $today,
            'end_date' => $today,
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Lokasi BISMO',
        ]);

        // ADP 1: Nama "Zebra ADP" (Hari Ini)
        $workAdpZebra = DailyWork::create([
            'name' => 'Zebra ADP',
            'unit' => 'ADP',
            'start_date' => $today,
            'end_date' => $today,
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Lokasi ADP 1',
        ]);

        // ADP 2: Nama "Alfa ADP" (Hari Ini)
        $workAdpAlfaToday = DailyWork::create([
            'name' => 'Alfa ADP',
            'unit' => 'ADP',
            'start_date' => $today,
            'end_date' => $today,
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Lokasi ADP 2',
        ]);

        // ADP 3: Nama "Alfa ADP" (Kemarin)
        $workAdpAlfaYesterday = DailyWork::create([
            'name' => 'Alfa ADP',
            'unit' => 'ADP',
            'start_date' => $yesterday,
            'end_date' => $yesterday,
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Lokasi ADP 3',
        ]);

        // --- PENGUJIAN TAB SEMUA RIWAYAT (date_mode = all) ---
        $resAll = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'all',
        ]));
        $resAll->assertStatus(200);

        $worksAll = collect($resAll->json('works'));
        $filteredWorks = $worksAll->whereIn('id', [
            $workWcp->id,
            $workBismo->id,
            $workAdpZebra->id,
            $workAdpAlfaToday->id,
            $workAdpAlfaYesterday->id,
        ])->values();

        // Urutan yang diharapkan:
        // 1. ADP -> "Alfa ADP" (today)
        // 2. ADP -> "Alfa ADP" (yesterday)
        // 3. ADP -> "Zebra ADP" (today)
        // 4. BISMO -> "Pekerjaan Alfa BISMO"
        // 5. WCP -> "Pekerjaan Zebra WCP"
        $this->assertEquals($workAdpAlfaToday->id, $filteredWorks[0]['id'], 'Urutan 1 harus ADP - Alfa ADP (today)');
        $this->assertEquals($workAdpAlfaYesterday->id, $filteredWorks[1]['id'], 'Urutan 2 harus ADP - Alfa ADP (yesterday)');
        $this->assertEquals($workAdpZebra->id, $filteredWorks[2]['id'], 'Urutan 3 harus ADP - Zebra ADP');
        $this->assertEquals($workBismo->id, $filteredWorks[3]['id'], 'Urutan 4 harus BISMO');
        $this->assertEquals($workWcp->id, $filteredWorks[4]['id'], 'Urutan 5 harus WCP');

        // --- PENGUJIAN TAB HARI INI (date_mode = today) ---
        $resToday = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'today',
        ]));
        $resToday->assertStatus(200);

        $worksToday = collect($resToday->json('works'));
        $filteredToday = $worksToday->whereIn('id', [
            $workWcp->id,
            $workBismo->id,
            $workAdpZebra->id,
            $workAdpAlfaToday->id,
        ])->values();

        // Pada Hari Ini:
        // 1. ADP -> "Alfa ADP"
        // 2. ADP -> "Zebra ADP"
        // 3. BISMO -> "Pekerjaan Alfa BISMO"
        // 4. WCP -> "Pekerjaan Zebra WCP"
        $this->assertEquals($workAdpAlfaToday->id, $filteredToday[0]['id'], 'Hari Ini: Urutan 1 harus ADP - Alfa ADP');
        $this->assertEquals($workAdpZebra->id, $filteredToday[1]['id'], 'Hari Ini: Urutan 2 harus ADP - Zebra ADP');
        $this->assertEquals($workBismo->id, $filteredToday[2]['id'], 'Hari Ini: Urutan 3 harus BISMO');
        $this->assertEquals($workWcp->id, $filteredToday[3]['id'], 'Hari Ini: Urutan 4 harus WCP');

        // Export PDF juga harus mempertahankan urutan unit ASC, name ASC
        $pdfRes = $this->actingAs($this->adminUser)->get(route('daily-work.export', [
            'date_mode' => 'all',
        ]));
        $pdfRes->assertStatus(200);
    }

    /**
     * 3. Filter Rentang Tanggal (Tanggal Mulai & Tanggal Selesai) inklusif dan beririsan.
     */
    public function test_date_range_filter_inclusive_and_overlapping(): void
    {
        $baseDate = '2026-11-10';

        // Pekerjaan 1: 10 Nov - 12 Nov
        $w1 = DailyWork::create([
            'name' => 'Pekerjaan Rentang 10-12 Nov',
            'unit' => 'ADP',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-12',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Lokasi 1',
        ]);

        // Pekerjaan 2: 13 Nov - 15 Nov
        $w2 = DailyWork::create([
            'name' => 'Pekerjaan Rentang 13-15 Nov',
            'unit' => 'BISMO',
            'start_date' => '2026-11-13',
            'end_date' => '2026-11-15',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Lokasi 2',
        ]);

        // Pekerjaan 3: 16 Nov - 20 Nov
        $w3 = DailyWork::create([
            'name' => 'Pekerjaan Rentang 16-20 Nov',
            'unit' => 'PJSE',
            'start_date' => '2026-11-16',
            'end_date' => '2026-11-20',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Lokasi 3',
        ]);

        // Filter rentang 11 Nov s/d 14 Nov:
        // W1 beririsan (end_date 12 >= 11 and start_date 10 <= 14) -> Masuk
        // W2 beririsan (end_date 15 >= 11 and start_date 13 <= 14) -> Masuk
        // W3 tidak beririsan (start_date 16 > 14) -> Tidak Masuk
        $resRange = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'all',
            'start_date' => '2026-11-11',
            'end_date' => '2026-11-14',
        ]));
        $resRange->assertStatus(200);
        $ids = collect($resRange->json('works'))->pluck('id')->all();
        $this->assertContains($w1->id, $ids);
        $this->assertContains($w2->id, $ids);
        $this->assertNotContains($w3->id, $ids);

        // Filter rentang dengan start_date > end_date (swap otomatis)
        $resSwap = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'all',
            'start_date' => '2026-11-14',
            'end_date' => '2026-11-11',
        ]));
        $resSwap->assertStatus(200);
        $idsSwap = collect($resSwap->json('works'))->pluck('id')->all();
        $this->assertContains($w1->id, $idsSwap);
        $this->assertContains($w2->id, $idsSwap);
        $this->assertNotContains($w3->id, $idsSwap);

        // Hanya Tanggal Mulai diisi: 14 Nov ke atas
        // W1 selesai 12 Nov (< 14 Nov) -> Tidak Masuk
        // W2 selesai 15 Nov (>= 14 Nov) -> Masuk
        // W3 selesai 20 Nov (>= 14 Nov) -> Masuk
        $resStartOnly = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'all',
            'start_date' => '2026-11-14',
        ]));
        $resStartOnly->assertStatus(200);
        $idsStart = collect($resStartOnly->json('works'))->pluck('id')->all();
        $this->assertNotContains($w1->id, $idsStart);
        $this->assertContains($w2->id, $idsStart);
        $this->assertContains($w3->id, $idsStart);

        // Hanya Tanggal Selesai diisi: 12 Nov ke bawah
        // W1 mulai 10 Nov (<= 12 Nov) -> Masuk
        // W2 mulai 13 Nov (> 12 Nov) -> Tidak Masuk
        // W3 mulai 16 Nov (> 12 Nov) -> Tidak Masuk
        $resEndOnly = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'all',
            'end_date' => '2026-11-12',
        ]));
        $resEndOnly->assertStatus(200);
        $idsEnd = collect($resEndOnly->json('works'))->pluck('id')->all();
        $this->assertContains($w1->id, $idsEnd);
        $this->assertNotContains($w2->id, $idsEnd);
        $this->assertNotContains($w3->id, $idsEnd);

        // Gabungan filter rentang tanggal dengan filter perusahaan
        $resCombined = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'all',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-15',
            'company' => 'ADP',
        ]));
        $resCombined->assertStatus(200);
        $idsCombined = collect($resCombined->json('works'))->pluck('id')->all();
        $this->assertContains($w1->id, $idsCombined);
        $this->assertNotContains($w2->id, $idsCombined);
    }

    /**
     * 4. Hapus keterangan kecil pada kartu ringkasan dan verifikasi elemen rentang tanggal.
     *    - Tidak ada teks 'Pekerjaan aktif sesuai tanggal'
     *    - Tidak ada teks 'Unit / mitra pelaksana hari ini'
     *    - Input startDateFilter dan endDateFilter tersedia
     *    - Tombol klik kartu tetap ada
     */
    public function test_stats_cards_have_small_captions_removed_and_retain_click_functions(): void
    {
        $resp = $this->actingAs($this->adminUser)->get(route('daily-work.index'));
        $resp->assertStatus(200);

        // Keterangan kecil pada kedua kartu sudah dihapus
        $resp->assertDontSee('Pekerjaan aktif sesuai tanggal', false);
        $resp->assertDontSee('Unit / mitra pelaksana hari ini', false);

        // Judul kartu tetap ada
        $resp->assertSee('Total Pekerjaan Hari Ini', false);
        $resp->assertSee('Perusahaan Terlibat', false);

        // Fungsi klik kartu tetap utuh
        $resp->assertSee('id="cardStatToday"', false);
        $resp->assertSee('onclick="filterByCardToday()"', false);
        $resp->assertSee('id="cardStatCompanies"', false);
        $resp->assertSee('onclick="filterByCardCompanies()"', false);

        // Input Tanggal Mulai dan Tanggal Selesai beserta labelnya tersedia
        $resp->assertSee('id="startDateFilter"', false);
        $resp->assertSee('id="endDateFilter"', false);
        $resp->assertSee('id="dateRangeFilterWrap"', false);
        $resp->assertSee('date-range-separator', false);
        $resp->assertSee('Tanggal Mulai', false);
        $resp->assertSee('Tanggal Selesai', false);
    }

    /**
     * 5. Verifikasi 3 perbaikan tambahan:
     *    1. Label Tanggal Mulai & Tanggal Selesai di atas input tanggal.
     *    2. Kotak dekoratif bi-square dihapus pada tabel Master Perusahaan (renderMasterTables).
     *    3. Tombol Tambah Perusahaan (btn-master-teal) dan Tambah SIKA (btn-master-blue) berwarna solid kontras.
     */
    public function test_date_filter_labels_decorative_box_removal_and_colored_buttons(): void
    {
        $resp = $this->actingAs($this->adminUser)->get(route('daily-work.index'));
        $resp->assertStatus(200);

        // 1. Label Tanggal Mulai dan Tanggal Selesai
        $resp->assertSee('<label for="startDateFilter" class="date-range-label">Tanggal Mulai</label>', false);
        $resp->assertSee('<label for="endDateFilter" class="date-range-label">Tanggal Selesai</label>', false);

        // 2. Kotak dekoratif bi-square tidak ada lagi di baris tabel Master Perusahaan
        // Induk rata kiri bold, subcon menjorok dengan bi-arrow-return-right
        $resp->assertDontSee('<i class="bi bi-square me-2 text-muted" style="font-size:11px;"></i>', false);

        // 3. Tombol Tambah Perusahaan (btn-master-teal) dan Tambah SIKA (btn-master-blue)
        $resp->assertSee('btn-master-teal', false);
        $resp->assertSee('btn-master-blue', false);
        $resp->assertSee('id="btnShowCompanyForm"', false);
        $resp->assertSee('submitAddSika()', false);
    }
}
