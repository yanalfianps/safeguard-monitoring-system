<?php

namespace Tests\Feature\BudgetMonitoring;

use App\Models\BudgetAdjustment;
use App\Models\BudgetNonPo;
use App\Models\BudgetPo;
use App\Models\BudgetPoPayment;
use App\Models\BudgetTransaction;
use App\Models\BudgetTransfer;
use App\Models\BudgetWbs;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BudgetMonitoringManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_test_budget@geodipa.co.id'],
            [
                'name' => 'Admin Budget Test',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'aktif',
            ]
        );
        $this->adminUser->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'aktif']);

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user_test_budget@geodipa.co.id'],
            [
                'name' => 'Regular User Budget Test',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_USER,
                'status' => 'aktif',
            ]
        );
        $this->regularUser->update(['role' => User::ROLE_USER, 'status' => 'aktif']);
    }

    /**
     * 1. Submenu Monitoring Anggaran tepat di bawah Data, mempertahankan seluruh submenu lain.
     */
    public function test_sidebar_has_monitoring_anggaran_under_data_submenu(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/monitoring-anggaran');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Data Submenu harus memiliki Manhour, Incident, Issue & Follow Up, Gas Monitoring, dan Monitoring Anggaran
        $response->assertSee('id="dataSubmenu"', false);
        $response->assertSee('Manhour', false);
        $response->assertSee('Incident', false);
        $response->assertSee('Issue &amp; Follow Up', false);
        $response->assertSee('Gas Monitoring', false);
        $response->assertSee('Monitoring Anggaran', false);

        // Monitoring Anggaran harus berada di dalam #dataSubmenu
        $posDataSubmenu = strpos($content, 'id="dataSubmenu"');
        $posMonitoring = strpos($content, 'Monitoring Anggaran', $posDataSubmenu);
        $this->assertNotFalse($posMonitoring, 'Monitoring Anggaran harus berada di dalam submenu Data.');

        // Jangan membuat submenu sidebar terpisah untuk PO, Non-PO, atau Pengalihan
        $response->assertDontSee('id="poSubmenu"', false);
        $response->assertDontSee('id="budgetSubmenu"', false);
    }

    /**
     * 2. Tampilan berbahasa Indonesia, 6 indikator utama, 7 tab, tanpa diagram/grafik visual.
     */
    public function test_budget_monitoring_index_renders_in_indonesian_without_charts(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/monitoring-anggaran');
        $response->assertStatus(200);

        // 6 Indikator Keuangan
        $response->assertSee('Total Anggaran', false);
        $response->assertSee('Komitmen PO', false);
        $response->assertSee('Realisasi PO', false);
        $response->assertSee('Realisasi Non-PO', false);
        $response->assertSee('Anggaran Teralokasi', false);
        $response->assertSee('Sisa Anggaran', false);

        // 7 Tab Navigasi Internal
        $response->assertSee('1. Ringkasan Anggaran', false);
        $response->assertSee('2. Anggaran WBS', false);
        $response->assertSee('3. Pengadaan / PO', false);
        $response->assertSee('4. Realisasi PO', false);
        $response->assertSee('5. Realisasi Non-PO', false);
        $response->assertSee('6. Pengalihan Anggaran', false);
        $response->assertSee('7. Riwayat Transaksi', false);

        // Tombol aksi header
        $response->assertSee('Ekspor PDF', false);
        $response->assertSee('+ Tambah WBS', false);
        $response->assertDontSee('Ekspor Excel', false);
        $response->assertDontSee('Reset Data', false);
        $response->assertDontSee('modalResetDataBudget', false);

        // Tidak boleh ada elemen grafik visual pada modul monitoring anggaran
        $content = $response->getContent();
        $this->assertStringNotContainsString('<canvas id="chart', $content, 'Modul Monitoring Anggaran tidak boleh memuat grafik canvas.');
        $this->assertStringNotContainsString('new Chart(', $content, 'Modul Monitoring Anggaran tidak boleh menginisialisasi Chart.js.');
    }

    /**
     * 3. Skenario Keuangan Lengkap sesuai Spesifikasi Bagian 9 & 17:
     * - Anggaran Awal: Rp1.000.000.000
     * - Komitmen PO: Rp500.000.000
     * - Pembayaran PO: Rp200.000.000
     *   => Realisasi PO Rp200.000.000, Sisa Komitmen Rp300.000.000, Teralokasi Rp500.000.000, Sisa Anggaran Rp500.000.000
     * - Non-PO: Rp300.000.000
     *   => Teralokasi Rp800.000.000, Sisa Anggaran Rp200.000.000
     */
    public function test_complete_financial_calculation_lifecycle(): void
    {
        // 1. Catat WBS dengan Anggaran Awal Rp1.000.000.000
        $wbsNumber = 'WBS-TEST-' . uniqid();
        $resWbs = $this->actingAs($this->adminUser)->post('/monitoring-anggaran/wbs', [
            'wbs_number' => $wbsNumber,
            'wbs_name' => 'Program Uji Keselamatan Kerja',
            'initial_budget' => 1000000000,
            'fiscal_year' => 2026,
            'description' => 'WBS Pengujian',
        ]);
        $resWbs->assertSessionHasNoErrors();

        $wbs = BudgetWbs::where('wbs_number', $wbsNumber)->firstOrFail();
        $this->assertEquals(1000000000, $wbs->current_budget);
        $this->assertEquals(1000000000, $wbs->remaining_budget);
        $this->assertEquals(0, $wbs->allocated_budget);

        // 2. Buat PO dengan Komitmen Rp500.000.000
        $poNumber = 'PO-TEST-' . uniqid();
        $resPo = $this->actingAs($this->adminUser)->post('/monitoring-anggaran/po', [
            'budget_wbs_id' => $wbs->id,
            'po_number' => $poNumber,
            'program_name' => 'Pengadaan APD Mandiri',
            'vendor' => 'PT Mitra Sejati',
            'po_value' => 500000000,
            'commitment_value' => 500000000,
            'po_date' => '2026-05-10',
            'status' => 'Aktif',
            'description' => 'PO APD',
        ]);
        $resPo->assertSessionHasNoErrors();

        $po = BudgetPo::where('po_number', $poNumber)->firstOrFail();
        $this->assertEquals(500000000, $po->commitment_value);
        $this->assertEquals(500000000, $po->remaining_commitment);

        $wbs->refresh();
        $this->assertEquals(500000000, $wbs->po_commitment);
        $this->assertEquals(500000000, $wbs->allocated_budget);
        $this->assertEquals(500000000, $wbs->remaining_budget);

        // 3. Catat Pembayaran Pertama Rp200.000.000
        $resPay = $this->actingAs($this->adminUser)->post("/monitoring-anggaran/po/{$po->id}/payments", [
            'payment_date' => '2026-06-01',
            'amount' => 200000000,
            'reference_number' => 'INV-001',
            'description' => 'Termin Pertama',
        ]);
        $resPay->assertSessionHasNoErrors();

        $po->refresh();
        $wbs->refresh();

        // Pastikan: Realisasi PO = 200jt, Sisa Komitmen = 300jt, Anggaran Teralokasi tetap 500jt, Sisa Anggaran tetap 500jt
        $this->assertEquals(200000000, $po->total_payment);
        $this->assertEquals(300000000, $po->remaining_commitment);
        $this->assertEquals(200000000, $wbs->po_realization);
        $this->assertEquals(500000000, $wbs->allocated_budget, 'Pembayaran PO tidak boleh mengurangi sisa anggaran untuk kedua kalinya.');
        $this->assertEquals(500000000, $wbs->remaining_budget);

        // 4. Catat Pengeluaran Non-PO Rp300.000.000
        $resNonPo = $this->actingAs($this->adminUser)->post('/monitoring-anggaran/non-po', [
            'budget_wbs_id' => $wbs->id,
            'program_name' => 'Konsumsi Pelatihan Tanggap Darurat',
            'transaction_date' => '2026-06-15',
            'amount' => 300000000,
            'recipient_vendor' => 'Katering Dieng',
            'description' => 'Pelatihan Lapangan',
        ]);
        $resNonPo->assertSessionHasNoErrors();

        $wbs->refresh();

        // Pastikan: Realisasi Non-PO = 300jt, Anggaran Teralokasi menjadi 800jt, Sisa Anggaran menjadi 200jt
        $this->assertEquals(300000000, $wbs->non_po_realization);
        $this->assertEquals(800000000, $wbs->allocated_budget);
        $this->assertEquals(200000000, $wbs->remaining_budget);

        // Pastikan seluruh transaksi tercatat pada Riwayat Transaksi Keuangan
        $trxCount = BudgetTransaction::where('budget_wbs_id', $wbs->id)->count();
        $this->assertGreaterThanOrEqual(4, $trxCount); // Initial Adj + PO Commitment + PO Payment + Non-PO
    }

    /**
     * 4. Skenario Pengalihan Anggaran Antar-WBS:
     * - WBS A sisa Rp100.000.000
     * - WBS B sisa Rp20.000.000
     * - Transfer Rp50.000.000 dari A ke B
     * - Sisa A: Rp50.000.000, Sisa B: Rp70.000.000
     * - Total Anggaran Keseluruhan TETAP SAMA (Invariance).
     */
    public function test_budget_transfer_between_wbs_and_invariance_of_total_budget(): void
    {
        $wbsA = BudgetWbs::create([
            'wbs_number' => 'WBS-TRF-A-' . uniqid(),
            'wbs_name' => 'WBS Sumber Pengalihan',
            'initial_budget' => 100000000,
            'current_budget' => 100000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $wbsB = BudgetWbs::create([
            'wbs_number' => 'WBS-TRF-B-' . uniqid(),
            'wbs_name' => 'WBS Tujuan Pengalihan',
            'initial_budget' => 20000000,
            'current_budget' => 20000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $totalBefore = BudgetWbs::whereIn('id', [$wbsA->id, $wbsB->id])->sum('current_budget');
        $this->assertEquals(120000000, $totalBefore);

        $resTransfer = $this->actingAs($this->adminUser)->post('/monitoring-anggaran/transfers', [
            'source_wbs_id' => $wbsA->id,
            'destination_wbs_id' => $wbsB->id,
            'amount' => 50000000,
            'transfer_date' => '2026-07-01',
            'reason' => 'Percepatan Pengadaan Safeguard Mendesak',
            'description' => 'Memo Persetujuan Direksi No. 042',
        ]);
        $resTransfer->assertSessionHasNoErrors();

        $wbsA->refresh();
        $wbsB->refresh();

        $this->assertEquals(50000000, $wbsA->current_budget);
        $this->assertEquals(50000000, $wbsA->remaining_budget);

        $this->assertEquals(70000000, $wbsB->current_budget);
        $this->assertEquals(70000000, $wbsB->remaining_budget);

        $totalAfter = BudgetWbs::whereIn('id', [$wbsA->id, $wbsB->id])->sum('current_budget');
        $this->assertEquals(120000000, $totalAfter, 'Total Anggaran Keseluruhan tidak boleh berubah akibat pengalihan dana internal.');

        // Cek catatan transaksi: Dana Keluar di WBS A dan Dana Masuk di WBS B
        $trxOut = BudgetTransaction::where('budget_wbs_id', $wbsA->id)
            ->where('transaction_type', BudgetTransaction::TYPE_TRANSFER_OUT)
            ->first();
        $this->assertNotNull($trxOut);
        $this->assertEquals(50000000, $trxOut->amount);

        $trxIn = BudgetTransaction::where('budget_wbs_id', $wbsB->id)
            ->where('transaction_type', BudgetTransaction::TYPE_TRANSFER_IN)
            ->first();
        $this->assertNotNull($trxIn);
        $this->assertEquals(50000000, $trxIn->amount);
    }

    /**
     * 5. Uji Validasi Ketat:
     * - Komitmen PO melebihi sisa anggaran WBS harus ditolak.
     * - Pembayaran PO melebihi sisa komitmen PO harus ditolak.
     * - Realisasi Non-PO melebihi sisa anggaran WBS harus ditolak.
     * - Pengalihan melebihi sisa anggaran WBS Asal harus ditolak.
     * - Pengalihan ke WBS yang sama harus ditolak.
     */
    public function test_validation_prevents_overcommitment_overspending_and_invalid_transfers(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-VAL-' . uniqid(),
            'wbs_name' => 'WBS Validasi Ketat',
            'initial_budget' => 10000000,
            'current_budget' => 10000000, // Sisa Rp10.000.000
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        // A. PO Rp15.000.000 pada sisa Rp10.000.000 harus ditolak
        $resPo = $this->actingAs($this->adminUser)->postJson('/monitoring-anggaran/po', [
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-VAL-OVER-' . uniqid(),
            'program_name' => 'Overcommitment Test',
            'po_value' => 15000000,
            'commitment_value' => 15000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
        ]);
        $resPo->assertStatus(422);

        // Buat PO valid Rp6.000.000 (sisa WBS sisa Rp4.000.000)
        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-VAL-OK-' . uniqid(),
            'program_name' => 'PO Normal',
            'po_value' => 6000000,
            'commitment_value' => 6000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
        ]);

        // B. Pembayaran Rp7.000.000 pada komitmen Rp6.000.000 harus ditolak
        $resPay = $this->actingAs($this->adminUser)->postJson("/monitoring-anggaran/po/{$po->id}/payments", [
            'payment_date' => '2026-08-02',
            'amount' => 7000000,
        ]);
        $resPay->assertStatus(422);

        // C. Non-PO Rp5.000.000 pada sisa WBS Rp4.000.000 harus ditolak
        $resNonPo = $this->actingAs($this->adminUser)->postJson('/monitoring-anggaran/non-po', [
            'budget_wbs_id' => $wbs->id,
            'program_name' => 'Over Non-PO Test',
            'transaction_date' => '2026-08-03',
            'amount' => 5000000,
        ]);
        $resNonPo->assertStatus(422);

        // D. Pengalihan Rp5.000.000 dari WBS (sisa Rp4.000.000) harus ditolak
        $wbsTarget = BudgetWbs::create([
            'wbs_number' => 'WBS-TARGET-' . uniqid(),
            'wbs_name' => 'WBS Sasaran',
            'initial_budget' => 5000000,
            'current_budget' => 5000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $resTransferOver = $this->actingAs($this->adminUser)->postJson('/monitoring-anggaran/transfers', [
            'source_wbs_id' => $wbs->id,
            'destination_wbs_id' => $wbsTarget->id,
            'amount' => 5000000,
            'transfer_date' => '2026-08-04',
            'reason' => 'Pengalihan Berlebih',
        ]);
        $resTransferOver->assertStatus(422);

        // E. Pengalihan ke WBS yang sama harus ditolak (different:destination_wbs_id)
        $resTransferSame = $this->actingAs($this->adminUser)->postJson('/monitoring-anggaran/transfers', [
            'source_wbs_id' => $wbs->id,
            'destination_wbs_id' => $wbs->id,
            'amount' => 1000000,
            'transfer_date' => '2026-08-04',
            'reason' => 'Pengalihan Diri Sendiri',
        ]);
        $resTransferSame->assertStatus(422);
    }

    /**
     * 6. Uji Penyesuaian Anggaran WBS (Audit Trail):
     */
    public function test_wbs_budget_adjustment_recording(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-ADJ-' . uniqid(),
            'wbs_name' => 'WBS Uji Penyesuaian',
            'initial_budget' => 50000000,
            'current_budget' => 50000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        // Tambah anggaran Rp25.000.000
        $res = $this->actingAs($this->adminUser)->post("/monitoring-anggaran/wbs/{$wbs->id}/adjust", [
            'adjustment_type' => 'TAMBAH',
            'amount' => 25000000,
            'adjustment_date' => '2026-09-01',
            'reason' => 'Revisi RKAP Semester II',
        ]);
        $res->assertSessionHasNoErrors();

        $wbs->refresh();
        $this->assertEquals(75000000, $wbs->current_budget);

        $adj = BudgetAdjustment::where('budget_wbs_id', $wbs->id)->latest('id')->first();
        $this->assertNotNull($adj);
        $this->assertEquals('TAMBAH', $adj->adjustment_type);
        $this->assertEquals(25000000, $adj->amount);
        $this->assertEquals(50000000, $adj->old_budget);
        $this->assertEquals(75000000, $adj->new_budget);
    }

    /**
     * 7. Uji Hak Akses: User hanya dapat melihat dan ekspor, tidak boleh menambah atau mengubah.
     */
    public function test_role_permissions_for_budget_monitoring(): void
    {
        // User dapat melihat halaman
        $resView = $this->actingAs($this->regularUser)->get('/monitoring-anggaran');
        $resView->assertStatus(200);

        // User ditolak saat mencoba menambah WBS
        $resWbs = $this->actingAs($this->regularUser)->post('/monitoring-anggaran/wbs', [
            'wbs_number' => 'WBS-UNAUTHORIZED',
            'wbs_name' => 'Percobaan Ilegal',
            'initial_budget' => 10000000,
            'fiscal_year' => 2026,
        ]);
        $resWbs->assertStatus(403);

        // User ditolak saat mencoba menambah PO
        $resPo = $this->actingAs($this->regularUser)->post('/monitoring-anggaran/po', [
            'program_name' => 'Percobaan Ilegal',
        ]);
        $resPo->assertStatus(403);

        // User ditolak saat mencoba transfer dana
        $resTransfer = $this->actingAs($this->regularUser)->post('/monitoring-anggaran/transfers', [
            'amount' => 5000000,
        ]);
        $resTransfer->assertStatus(403);
    }

    /**
     * 8. Uji Ekspor Laporan PDF dan Excel/CSV
     */
    public function test_export_pdf_and_excel_endpoints(): void
    {
        $resPdf = $this->actingAs($this->adminUser)->get('/monitoring-anggaran/export-pdf');
        $resPdf->assertStatus(200);
        $resPdf->assertHeader('content-type', 'application/pdf');

        $resExcel = $this->actingAs($this->adminUser)->get('/monitoring-anggaran/export-excel');
        $resExcel->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resExcel->headers->get('content-type'));

        // Regular user dengan permission can.export juga diizinkan mengunduh
        $resUserPdf = $this->actingAs($this->regularUser)->get('/monitoring-anggaran/export-pdf');
        $resUserPdf->assertStatus(200);
    }

    /**
     * 9. Uji Halaman Rincian Detail per WBS
     */
    public function test_detail_wbs_page_renders_with_related_activities(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-DET-' . uniqid(),
            'wbs_name' => 'WBS Uji Tampilan Detail',
            'initial_budget' => 45000000,
            'current_budget' => 45000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $res = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}");
        $res->assertStatus(200);
        $res->assertSee($wbs->wbs_number);
        $res->assertSee($wbs->wbs_name);
        $res->assertSee('Total Anggaran', false);
        $res->assertSee('Komitmen PO', false);
        $res->assertSee('Realisasi PO', false);
        $res->assertSee('Sisa Anggaran', false);
    }

    /**
     * 9b. Uji Bug Fix: Tidak ada duplikasi realisasi PO pada rincian aktivitas transaksi WBS.
     * Komitmen hanya dicatat pada baris pengadaan PO.
     * Realisasi PO hanya dicatat pada baris pembayaran aktual.
     * Total agregat dan sisa komitmen dihitung akurat tanpa duplikasi.
     */
    public function test_wbs_activity_details_po_realization_not_duplicated_and_only_recorded_on_actual_payment(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-NO-DUP-' . uniqid(),
            'wbs_name' => 'WBS Pengujian Duplikasi Realisasi',
            'initial_budget' => 500000000,
            'current_budget' => 500000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-TEST-' . uniqid(),
            'program_name' => 'Pengadaan APD dan Sepatu Safety',
            'vendor' => 'PT Mitra Safety Indonesia',
            'po_value' => 200000000,
            'commitment_value' => 200000000,
            'po_date' => '2026-06-01',
            'status' => 'Aktif',
            'created_by' => $this->adminUser->id,
        ]);

        $pay1 = BudgetPoPayment::create([
            'budget_po_id' => $po->id,
            'budget_wbs_id' => $wbs->id,
            'payment_number' => 'PAY-TEST-001-' . uniqid(),
            'payment_date' => '2026-06-10',
            'amount' => 150000000,
            'reference_number' => 'INV-001',
            'description' => 'Pembayaran Termin Pertama',
            'created_by' => $this->adminUser->id,
        ]);

        $pay2 = BudgetPoPayment::create([
            'budget_po_id' => $po->id,
            'budget_wbs_id' => $wbs->id,
            'payment_number' => 'PAY-TEST-002-' . uniqid(),
            'payment_date' => '2026-06-20',
            'amount' => 50000000,
            'reference_number' => 'INV-002',
            'description' => 'Pembayaran Termin Kedua (Pelunasan)',
            'created_by' => $this->adminUser->id,
        ]);

        $nonPo = BudgetNonPo::create([
            'budget_wbs_id' => $wbs->id,
            'transaction_number' => 'NPO-TEST-' . uniqid(),
            'program_name' => 'Konsumsi Rapat HSE Bulanan',
            'transaction_date' => '2026-06-25',
            'amount' => 10000000,
            'recipient_vendor' => 'Katering Dieng',
            'description' => 'Konsumsi Tim Lapangan',
            'created_by' => $this->adminUser->id,
        ]);

        $wbs->refresh();

        // 1. Verifikasi Agregat Model
        $this->assertEquals(200000000, $wbs->po_commitment);
        $this->assertEquals(200000000, $wbs->po_realization, 'Total Realisasi PO harus tepat 200jt (bukan 400jt)');
        $this->assertEquals(0, $wbs->remaining_po_commitment, 'Sisa Komitmen PO harus tepat Rp0');
        $this->assertEquals(10000000, $wbs->non_po_realization, 'Realisasi Non-PO harus terpisah (10jt)');
        $this->assertEquals(210000000, $wbs->allocated_budget, 'Anggaran Teralokasi = Komitmen PO 200jt + Non-PO 10jt');
        $this->assertEquals(290000000, $wbs->remaining_budget, 'Sisa Anggaran WBS = 500jt - 210jt = 290jt');

        // 2. Request Halaman Detail WBS
        $res = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}");
        $res->assertStatus(200);

        /** @var \Illuminate\Support\Collection $activities */
        $activities = $res->viewData('activities');
        $this->assertCount(4, $activities); // 1 PO + 2 Payments + 1 Non-PO

        // Baris jenis Pengadaan PO: po_realization harus null (tanda —)
        $poActivity = $activities->firstWhere('type', 'PO');
        $this->assertNotNull($poActivity);
        $this->assertEquals(200000000, $poActivity->po_value);
        $this->assertEquals(0, $poActivity->commitment_value, 'Sisa komitmen pada baris PO harus 0 setelah seluruh pembayaran terealisasi.');
        $this->assertNull($poActivity->po_realization, 'Baris pengadaan PO tidak boleh mengisi kolom Realisasi PO');
        $this->assertNull($poActivity->non_po_realization);

        // Baris jenis Pembayaran PO: Realisasi PO menampilkan nominal pembayaran, komitmen null
        $payActivities = $activities->where('type', 'PAYMENT');
        $this->assertCount(2, $payActivities);
        foreach ($payActivities as $pAct) {
            $this->assertNull($pAct->po_value);
            $this->assertNull($pAct->commitment_value);
            $this->assertNull($pAct->non_po_realization);
            $this->assertGreaterThan(0, $pAct->po_realization);
        }

        // Baris jenis Realisasi Non-PO: hanya kolom non_po_realization
        $nonPoActivity = $activities->firstWhere('type', 'NON_PO');
        $this->assertNotNull($nonPoActivity);
        $this->assertNull($nonPoActivity->po_value);
        $this->assertNull($nonPoActivity->commitment_value);
        $this->assertNull($nonPoActivity->po_realization);
        $this->assertEquals(10000000, $nonPoActivity->non_po_realization);

        // Sum kolom Realisasi PO dari seluruh baris aktivitas harus tepat 200.000.000 (tidak ada duplikasi 400.000.000)
        $this->assertEquals(200000000, $activities->sum('po_realization'));
    }

    /**
     * 10. Uji Penghapusan WBS: Ditolak jika ada data turunan, diizinkan jika masih kosong.
     */
    public function test_wbs_deletion_safety_and_lifecycle(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-DEL-' . uniqid(),
            'wbs_name' => 'WBS Tes Hapus',
            'initial_budget' => 20000000,
            'current_budget' => 20000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        // Pasang PO agar ada relasi
        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-DEL-PREVENT-' . uniqid(),
            'program_name' => 'Program Terkait',
            'po_value' => 5000000,
            'commitment_value' => 5000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
        ]);

        // Coba hapus WBS yang memiliki PO -> harus ditolak
        $resForbidden = $this->actingAs($this->adminUser)->deleteJson("/monitoring-anggaran/wbs/{$wbs->id}");
        $resForbidden->assertStatus(422);
        $this->assertDatabaseHas('budget_wbs', ['id' => $wbs->id]);

        // Hapus PO terlebih dahulu
        $po->delete();

        // Sekarang hapus WBS yang sudah bersih -> harus berhasil
        $resSuccess = $this->actingAs($this->adminUser)->deleteJson("/monitoring-anggaran/wbs/{$wbs->id}");
        $resSuccess->assertStatus(200);
        $this->assertDatabaseMissing('budget_wbs', ['id' => $wbs->id]);
    }

    /**
     * 11. Uji Penghapusan PO: Ditolak jika ada pembayaran, diizinkan jika belum dan memulihkan sisa anggaran.
     */
    public function test_po_deletion_safety_and_balance_restoration(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-PO-DEL-' . uniqid(),
            'wbs_name' => 'WBS Tes PO Hapus',
            'initial_budget' => 50000000,
            'current_budget' => 50000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-DEL-' . uniqid(),
            'program_name' => 'Pengadaan APD',
            'po_value' => 20000000,
            'commitment_value' => 20000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
        ]);

        // Sisa WBS terpotong jadi 30.000.000
        $wbs->refresh();
        $this->assertEquals(30000000, $wbs->remaining_budget);

        // Catat pembayaran
        $pay = BudgetPoPayment::create([
            'budget_po_id' => $po->id,
            'budget_wbs_id' => $wbs->id,
            'payment_number' => 'PAY-DEL-' . uniqid(),
            'payment_date' => '2026-08-05',
            'amount' => 5000000,
        ]);

        // Coba hapus PO yang sudah ada pembayaran -> harus ditolak
        $resForbidden = $this->actingAs($this->adminUser)->deleteJson("/monitoring-anggaran/po/{$po->id}");
        $resForbidden->assertStatus(422);

        // Hapus pembayaran dulu
        $pay->delete();
        $po->refresh();

        // Sekarang hapus PO
        $resSuccess = $this->actingAs($this->adminUser)->deleteJson("/monitoring-anggaran/po/{$po->id}");
        $resSuccess->assertStatus(200);
        $this->assertDatabaseMissing('budget_pos', ['id' => $po->id]);

        // Sisa anggaran WBS harus pulih kembali ke 50.000.000
        $wbs->refresh();
        $this->assertEquals(50000000, $wbs->remaining_budget);
        $this->assertEquals(0, $wbs->po_commitment);
    }

    /**
     * 12. Uji Penghapusan Pembayaran PO: Memulihkan sisa komitmen PO.
     */
    public function test_po_payment_deletion_restores_po_remaining_commitment(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-PAY-DEL-' . uniqid(),
            'wbs_name' => 'WBS Pembayaran',
            'initial_budget' => 30000000,
            'current_budget' => 30000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-PAY-DEL-' . uniqid(),
            'program_name' => 'Pengadaan Pelatihan',
            'po_value' => 10000000,
            'commitment_value' => 10000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
        ]);

        $pay = BudgetPoPayment::create([
            'budget_po_id' => $po->id,
            'budget_wbs_id' => $wbs->id,
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'payment_date' => '2026-08-05',
            'amount' => 4000000,
        ]);

        $po->refresh();
        $this->assertEquals(6000000, $po->remaining_commitment);

        // Hapus pembayaran
        $res = $this->actingAs($this->adminUser)->deleteJson("/monitoring-anggaran/po-payment/{$pay->id}");
        $res->assertStatus(200);

        $this->assertDatabaseMissing('budget_po_payments', ['id' => $pay->id]);
        $po->refresh();
        $this->assertEquals(10000000, $po->remaining_commitment);
    }

    /**
     * 13. Uji Penghapusan Realisasi Non-PO: Memulihkan sisa anggaran WBS.
     */
    public function test_non_po_deletion_restores_wbs_remaining_budget(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-NPO-DEL-' . uniqid(),
            'wbs_name' => 'WBS Non-PO',
            'initial_budget' => 25000000,
            'current_budget' => 25000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $npo = BudgetNonPo::create([
            'budget_wbs_id' => $wbs->id,
            'transaction_number' => 'NPO-DEL-' . uniqid(),
            'program_name' => 'Konsumsi Audit',
            'transaction_date' => '2026-08-06',
            'amount' => 5000000,
        ]);

        $wbs->refresh();
        $this->assertEquals(20000000, $wbs->remaining_budget);

        $res = $this->actingAs($this->adminUser)->deleteJson("/monitoring-anggaran/non-po/{$npo->id}");
        $res->assertStatus(200);

        $this->assertDatabaseMissing('budget_non_pos', ['id' => $npo->id]);
        $wbs->refresh();
        $this->assertEquals(25000000, $wbs->remaining_budget);
    }

    /**
     * 14. Uji Pembatalan/Penghapusan Pengalihan Anggaran: Membalik saldo kedua WBS.
     */
    public function test_transfer_deletion_safety_and_balance_reversal(): void
    {
        $wbsA = BudgetWbs::create([
            'wbs_number' => 'WBS-TRF-REV-A-' . uniqid(),
            'wbs_name' => 'WBS Asal Reversal',
            'initial_budget' => 50000000,
            'current_budget' => 50000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $wbsB = BudgetWbs::create([
            'wbs_number' => 'WBS-TRF-REV-B-' . uniqid(),
            'wbs_name' => 'WBS Tujuan Reversal',
            'initial_budget' => 10000000,
            'current_budget' => 10000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        // Lakukan transfer 20.000.000 dari A ke B
        $this->actingAs($this->adminUser)->post('/monitoring-anggaran/transfers', [
            'source_wbs_id' => $wbsA->id,
            'destination_wbs_id' => $wbsB->id,
            'amount' => 20000000,
            'transfer_date' => '2026-08-10',
            'reason' => 'Transfer Uji Reversal',
        ]);

        $transfer = BudgetTransfer::where('source_wbs_id', $wbsA->id)->latest('id')->firstOrFail();

        $wbsA->refresh();
        $wbsB->refresh();
        $this->assertEquals(30000000, $wbsA->current_budget);
        $this->assertEquals(30000000, $wbsB->current_budget);

        // Hapus/batalkan transfer
        $resDelete = $this->actingAs($this->adminUser)->deleteJson("/monitoring-anggaran/transfers/{$transfer->id}");
        $resDelete->assertStatus(200);

        // Saldo kedua WBS harus berbalik normal (A kembali 50jt, B kembali 10jt)
        $wbsA->refresh();
        $wbsB->refresh();
        $this->assertEquals(50000000, $wbsA->current_budget);
        $this->assertEquals(10000000, $wbsB->current_budget);
        $this->assertDatabaseMissing('budget_transfers', ['id' => $transfer->id]);
    }

    /**
     * 15. Regular user tidak boleh menghapus data monitoring anggaran (403 Forbidden).
     */
    public function test_unauthorized_user_cannot_delete_budget_data(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-AUTH-' . uniqid(),
            'wbs_name' => 'WBS Unauthorized Delete',
            'initial_budget' => 10000000,
            'current_budget' => 10000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $res = $this->actingAs($this->regularUser)->deleteJson("/monitoring-anggaran/wbs/{$wbs->id}");
        $res->assertStatus(403);
    }

    /**
     * 16. Kolom Aksi Tabel WBS memiliki tombol Detail, Edit, Hapus, dan TIDAK memiliki tombol kuning/sliders.
     */
    public function test_index_table_action_column_has_detail_edit_delete_without_yellow_sliders_button(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-BTN-TEST-' . uniqid(),
            'wbs_name' => 'WBS Tes Tombol Aksi',
            'initial_budget' => 35000000,
            'current_budget' => 35000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $res = $this->actingAs($this->adminUser)->get('/monitoring-anggaran');
        $res->assertStatus(200);

        // Tombol Detail, Edit, Hapus harus ada
        $res->assertSee('Detail', false);
        $res->assertSee('bi-pencil', false);
        $res->assertSee('bi-trash', false);

        // Tombol ikon sliders/pengaturan kuning di tabel monitoring anggaran tidak boleh ada
        $content = $res->getContent();
        $this->assertStringNotContainsString('title="Penyesuaian Anggaran WBS"', $content, 'Tombol pengaturan kuning (sliders) pada tabel WBS harus dihilangkan.');
    }

    /**
     * 17. Halaman Detail WBS menampilkan Riwayat Pengalihan Anggaran dengan narasi dinamis WBS Asal & Penerima.
     */
    public function test_detail_wbs_displays_transfer_history_and_dynamic_narratives(): void
    {
        $wbsSrc = BudgetWbs::create([
            'wbs_number' => 'WBS-NARR-SRC-' . uniqid(),
            'wbs_name' => 'WBS Sumber Narasi',
            'initial_budget' => 80000000,
            'current_budget' => 40000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $wbsDest = BudgetWbs::create([
            'wbs_number' => 'WBS-NARR-DEST-' . uniqid(),
            'wbs_name' => 'WBS Penerima Narasi',
            'initial_budget' => 20000000,
            'current_budget' => 60000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $transfer = BudgetTransfer::create([
            'transfer_number' => 'TRF-NARR-' . uniqid(),
            'source_wbs_id' => $wbsSrc->id,
            'destination_wbs_id' => $wbsDest->id,
            'amount' => 40000000,
            'transfer_date' => '2026-10-21',
            'reason' => 'Pengadaan APD Tambahan',
            'status' => 'Disetujui',
            'created_by' => $this->adminUser->id,
        ]);

        // Cek halaman Detail WBS Sumber (Dana Keluar)
        $resSrc = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbsSrc->id}");
        $resSrc->assertStatus(200);
        $resSrc->assertSee('Riwayat Pengalihan Anggaran', false);
        $resSrc->assertSee('Dana Keluar', false);
        $resSrc->assertSee('dialihkan dari WBS ' . $wbsSrc->wbs_number . ' ke WBS ' . $wbsDest->wbs_number, false);
        $resSrc->assertSee('Rp40.000.000', false);

        // Cek halaman Detail WBS Penerima (Dana Masuk)
        $resDest = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbsDest->id}");
        $resDest->assertStatus(200);
        $resDest->assertSee('Riwayat Pengalihan Anggaran', false);
        $resDest->assertSee('Dana Masuk', false);
        $resDest->assertSee('Menerima pengalihan dana sebesar Rp40.000.000 dari WBS ' . $wbsSrc->wbs_number, false);
    }

    /**
     * 18. Keamanan Reset Data: User biasa tidak dapat mereset data anggaran.
     */
    public function test_unauthorized_user_cannot_reset_budget_monitoring_data(): void
    {
        $response = $this->actingAs($this->regularUser)->post('/monitoring-anggaran/reset-data', [
            'scope' => 'all',
            'confirm_text' => 'RESET',
        ]);

        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    /**
     * 19. Admin dapat mereset data modul anggaran dengan backup otomatis.
     */
    public function test_admin_can_reset_budget_monitoring_data_with_backup(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-RESET-' . uniqid(),
            'wbs_name' => 'WBS Pengujian Reset',
            'initial_budget' => 50000000,
            'current_budget' => 50000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-RESET-' . uniqid(),
            'program_name' => 'Program Reset',
            'po_value' => 20000000,
            'commitment_value' => 20000000,
            'po_date' => '2026-10-21',
            'status' => 'Aktif',
        ]);

        // Cek bahwa data ada
        $this->assertDatabaseHas('budget_wbs', ['id' => $wbs->id]);
        $this->assertDatabaseHas('budget_pos', ['id' => $po->id]);

        // Jalankan reset transactions_only
        $response = $this->actingAs($this->adminUser)->post('/monitoring-anggaran/reset-data', [
            'scope' => 'transactions_only',
            'confirm_text' => 'RESET',
        ]);

        $response->assertRedirect('/monitoring-anggaran');
        $this->assertDatabaseMissing('budget_pos', ['id' => $po->id]);
        $this->assertDatabaseHas('budget_wbs', ['id' => $wbs->id]);
    }

    /**
     * 20. Uji Persyaratan Baru:
     * - Kartu highlight Realisasi PO dihapus dari grid ringkasan atas
     * - Judul daftar berubah menjadi 'Daftar Anggaran/WBS'
     * - Label kolom konsisten: Budget, Total PO, Commitment, PO Expenses, Non-PO Expenses, Assigned, Available
     */
    public function test_user_requirements_budget_column_labels_and_po_card_removal(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/monitoring-anggaran');
        $response->assertStatus(200);

        // 1. Kartu Realisasi PO tidak boleh ada di budget-metrics-grid
        $response->assertDontSee('card-realisasi-po', false);

        // 2. Judul daftar harus 'Daftar Anggaran/WBS'
        $response->assertSee('Daftar Anggaran/WBS', false);
        $response->assertDontSee('Daftar Anggaran WBS (HSE & Safeguard)', false);

        // 3. Nama kolom konsisten pada tabel utama
        $response->assertSee('>Budget<', false);
        $response->assertSee('>Total PO<', false);
        $response->assertSee('>Commitment<', false);
        $response->assertSee('>PO Expenses<', false);
        $response->assertSee('>Non-PO Expenses<', false);
        $response->assertSee('>Assigned<', false);
        $response->assertSee('>Available<', false);

        // 4. Periksa pada halaman detail WBS
        $wbs = BudgetWbs::first();
        if ($wbs) {
            $resDetail = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}");
            $resDetail->assertStatus(200);
            $resDetail->assertSee('>Budget<', false);
            $resDetail->assertSee('>Total PO<', false);
            $resDetail->assertSee('>Commitment<', false);
            $resDetail->assertSee('>PO Expenses<', false);
            $resDetail->assertSee('>Non-PO Expenses<', false);
            $resDetail->assertSee('>Assigned<', false);
            $resDetail->assertSee('>Available<', false);
        }
    }

    /**
     * 21. Uji Validasi Rumus Keuangan Sesuai Contoh Spesifikasi User:
     * Budget = Rp1.000.000.000
     * Total PO = Rp700.000.000 (PO 1: Rp400.000.000 + PO 2: Rp300.000.000)
     * PO Expenses = Rp200.000.000 (Pembayaran bertahap: Termin 1 Rp100jt + Termin 2 Rp100jt)
     * Non-PO Expenses = Rp100.000.000
     *
     * Hasil yang Wajib:
     * - Budget = Rp1.000.000.000
     * - Total PO = Rp700.000.000
     * - Commitment = Rp500.000.000 (Total PO 700jt - PO Expenses 200jt)
     * - PO Expenses = Rp200.000.000
     * - Non-PO Expenses = Rp100.000.000
     * - Assigned = Rp800.000.000 (Total PO 700jt + Non-PO Expenses 100jt)
     * - Available = Rp200.000.000 (Budget 1M - Assigned 800jt)
     * - Pembayaran PO TIDAK mengurangi Available untuk kedua kalinya!
     */
    public function test_financial_formulas_exact_user_specification_and_no_double_deduction(): void
    {
        // 1. Buat WBS dengan Budget = Rp1.000.000.000
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-SPEC-' . uniqid(),
            'wbs_name' => 'WBS Contoh Spesifikasi Keuangan',
            'initial_budget' => 1000000000,
            'current_budget' => 1000000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $this->assertEquals(1000000000, $wbs->current_budget);
        $this->assertEquals(1000000000, $wbs->available);
        $this->assertEquals(0, $wbs->assigned);

        // 2. Terbitkan 2 PO (Total PO = Rp700.000.000)
        $po1 = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-SPEC-1-' . uniqid(),
            'program_name' => 'Pengadaan APD Utama',
            'po_value' => 400000000,
            'commitment_value' => 400000000,
            'po_date' => '2026-06-01',
            'status' => 'Aktif',
        ]);

        $po2 = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-SPEC-2-' . uniqid(),
            'program_name' => 'Pengadaan Alat Rescue',
            'po_value' => 300000000,
            'commitment_value' => 300000000,
            'po_date' => '2026-06-05',
            'status' => 'Aktif',
        ]);

        $wbs->refresh();
        $this->assertEquals(700000000, $wbs->total_po_value);
        $this->assertEquals(700000000, $wbs->commitment);
        $this->assertEquals(700000000, $wbs->assigned);
        $this->assertEquals(300000000, $wbs->available);

        // 3. Catat Pembayaran Bertahap pada PO 1 (Termin 1: Rp100jt, Termin 2: Rp100jt => Total Rp200.000.000)
        $pay1 = BudgetPoPayment::create([
            'budget_po_id' => $po1->id,
            'budget_wbs_id' => $wbs->id,
            'payment_number' => 'PAY-SPEC-1-' . uniqid(),
            'payment_date' => '2026-06-10',
            'amount' => 100000000,
        ]);

        $pay2 = BudgetPoPayment::create([
            'budget_po_id' => $po1->id,
            'budget_wbs_id' => $wbs->id,
            'payment_number' => 'PAY-SPEC-2-' . uniqid(),
            'payment_date' => '2026-06-15',
            'amount' => 100000000,
        ]);

        $wbs->refresh();
        // Setelah pembayaran Rp200jt:
        // Commitment berkurang dari 700jt menjadi 500jt
        $this->assertEquals(200000000, $wbs->po_expenses);
        $this->assertEquals(500000000, $wbs->commitment);
        // Assigned dan Available TIDAK berkurang untuk kedua kalinya!
        $this->assertEquals(700000000, $wbs->assigned, 'Assigned tidak boleh bertambah saat pembayaran PO');
        $this->assertEquals(300000000, $wbs->available, 'Available tidak boleh berkurang lagi karena pembayaran PO');

        // 4. Catat Non-PO Expenses = Rp100.000.000
        $nonPo = BudgetNonPo::create([
            'budget_wbs_id' => $wbs->id,
            'transaction_number' => 'NPO-SPEC-' . uniqid(),
            'program_name' => 'Konsumsi Pelatihan & Sertifikasi',
            'transaction_date' => '2026-06-20',
            'amount' => 100000000,
        ]);

        $wbs->refresh();

        // 5. Verifikasi Final Sesuai Contoh Persis User:
        // Budget = Rp1.000.000.000
        $this->assertEquals(1000000000, $wbs->current_budget);
        // Total PO = Rp700.000.000
        $this->assertEquals(700000000, $wbs->total_po_value);
        // Commitment = Rp500.000.000
        $this->assertEquals(500000000, $wbs->commitment);
        // PO Expenses = Rp200.000.000
        $this->assertEquals(200000000, $wbs->po_expenses);
        // Non-PO Expenses = Rp100.000.000
        $this->assertEquals(100000000, $wbs->non_po_expenses);
        // Assigned = Rp800.000.000
        $this->assertEquals(800000000, $wbs->assigned);
        // Available = Rp200.000.000
        $this->assertEquals(200000000, $wbs->available);
    }

    /**
     * Detail WBS UI & Calculation test:
     * - Baris 'TOTAL AKTIVITAS WBS' tidak ada di tabel aktivitas
     * - Riwayat pembayaran PO collapsible secara default dengan tombol toggle 'Lihat Semua Riwayat Pembayaran PO'
     * - Perhitungan contoh prompt: Budget 600M, Total PO 200M, PO Expenses 200M, Non-PO Expenses 300M => Commitment 0, Assigned 500M, Available 100M.
     */
    public function test_detail_wbs_ui_and_calculation_specs(): void
    {
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-DETAIL-' . uniqid(),
            'wbs_name' => 'WBS Pengujian Detail Tampilan',
            'initial_budget' => 600000000,
            'current_budget' => 600000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-DET-' . uniqid(),
            'program_name' => 'Pengadaan APD Lapangan',
            'po_value' => 200000000,
            'commitment_value' => 200000000,
            'po_date' => '2026-06-01',
            'status' => 'Aktif',
        ]);

        BudgetPoPayment::create([
            'budget_po_id' => $po->id,
            'budget_wbs_id' => $wbs->id,
            'payment_number' => 'PAY-DET-' . uniqid(),
            'payment_date' => '2026-06-15',
            'amount' => 200000000,
        ]);

        BudgetNonPo::create([
            'budget_wbs_id' => $wbs->id,
            'transaction_number' => 'NPO-DET-' . uniqid(),
            'program_name' => 'Konsumsi Pelatihan',
            'transaction_date' => '2026-06-20',
            'amount' => 300000000,
        ]);

        $wbs->refresh();
        $this->assertEquals(600000000, $wbs->current_budget);
        $this->assertEquals(200000000, $wbs->total_po_value);
        $this->assertEquals(0, $wbs->commitment);
        $this->assertEquals(200000000, $wbs->po_expenses);
        $this->assertEquals(300000000, $wbs->non_po_expenses);
        $this->assertEquals(500000000, $wbs->assigned);
        $this->assertEquals(100000000, $wbs->available);

        $response = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}");
        $response->assertStatus(200);

        // 1. TOTAL AKTIVITAS WBS tidak boleh ada di tabel aktivitas
        $response->assertDontSee('TOTAL AKTIVITAS WBS', false);

        // 2. Collapsible Riwayat Pembayaran PO Bertahap
        $response->assertSee('id="collapseRiwayatPembayaran"', false);
        $response->assertSee('id="btnToggleRiwayatPembayaran"', false);
        $response->assertSee('Lihat Semua Riwayat Pembayaran PO', false);

        // 3. Collapsible Riwayat Pengalihan Anggaran
        $response->assertSee('id="collapseRiwayatPengalihan"', false);
        $response->assertSee('id="btnToggleRiwayatPengalihan"', false);
        $response->assertSee('Lihat Riwayat Pengalihan Anggaran', false);

        // 4. Collapsible Riwayat Transaksi Keuangan (Audit Log WBS)
        $response->assertSee('id="collapseRiwayatTransaksi"', false);
        $response->assertSee('id="btnToggleRiwayatTransaksi"', false);
        $response->assertSee('Lihat Riwayat Transaksi Keuangan', false);

        // 5. 7 Metrik di kartu Detail WBS
        $response->assertSee('Budget', false);
        $response->assertSee('Total PO', false);
        $response->assertSee('Commitment', false);
        $response->assertSee('PO Expenses', false);
        $response->assertSee('Non-PO Expenses', false);
        $response->assertSee('Assigned', false);
        $response->assertSee('Available', false);
    }

    /**
     * Uji bertahap realisasi PO sesuai contoh spesifikasi prompt:
     * - PO Rp200.000.000
     * - Pembayaran 1: Rp150.000.000 => Total PO = 200jt, PO Expenses = 150jt, Commitment = 50jt
     * - Pembayaran 2: Rp50.000.000 => Total PO = 200jt, PO Expenses = 200jt, Commitment = 0
     * - Baris Pengadaan PO di tabel aktivitas menampilkan sisa Commitment yang menurun (bukan mengulang nilai PO awal)
     * - Pembayaran kumulatif tidak boleh melebihi sisa komitmen PO
     * - Assigned dan Available tidak dihitung ganda
     */
    public function test_staged_po_payments_reduces_po_commitment_accurately(): void
    {
        // 1. Buat WBS dengan Budget = Rp500.000.000
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-STAGE-' . uniqid(),
            'wbs_name' => 'WBS Uji Pembayaran Bertahap',
            'initial_budget' => 500000000,
            'current_budget' => 500000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        // 2. Buat PO sebesar Rp200.000.000
        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-STAGE-' . uniqid(),
            'program_name' => 'Pengadaan Turbin Safety Valve',
            'po_value' => 200000000,
            'commitment_value' => 200000000,
            'po_date' => '2026-07-01',
            'status' => 'Aktif',
        ]);

        $wbs->refresh();
        $this->assertEquals(200000000, $wbs->total_po_value);
        $this->assertEquals(200000000, $wbs->commitment);
        $this->assertEquals(0, $wbs->po_expenses);
        $this->assertEquals(200000000, $wbs->assigned);
        $this->assertEquals(300000000, $wbs->available);

        // 3. Pembayaran Pertama: Rp150.000.000
        $resPay1 = $this->actingAs($this->adminUser)->post("/monitoring-anggaran/po/{$po->id}/payments", [
            'payment_date' => '2026-07-10',
            'amount' => 150000000,
            'reference_number' => 'INV-TRM-1',
            'description' => 'Pembayaran Termin 1 75%',
        ]);
        $resPay1->assertSessionHasNoErrors();

        $po->refresh();
        $wbs->refresh();

        // Verifikasi metrik setelah pembayaran 1:
        $this->assertEquals(200000000, $wbs->total_po_value);
        $this->assertEquals(150000000, $wbs->po_expenses);
        $this->assertEquals(50000000, $wbs->commitment);
        $this->assertEquals(50000000, $po->remaining_commitment);
        // Assigned dan Available TIDAK berkurang untuk kedua kalinya
        $this->assertEquals(200000000, $wbs->assigned);
        $this->assertEquals(300000000, $wbs->available);

        // Cek halaman Detail WBS setelah pembayaran 1
        $resView1 = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}");
        $resView1->assertStatus(200);
        // Nilai komitmen pada baris PO di tabel aktivitas harus Rp50.000.000, bukan Rp200.000.000 lagi!
        $activities1 = $resView1->viewData('activities');
        $poActivity1 = $activities1->firstWhere('type', 'PO');
        $this->assertNotNull($poActivity1);
        $this->assertEquals(200000000, $poActivity1->po_value);
        $this->assertEquals(50000000, $poActivity1->commitment_value, 'Baris PO harus menampilkan sisa komitmen Rp50.000.000 setelah pembayaran termin 1');

        // Riwayat Pembayaran Bertahap setelah pembayaran 1
        $paymentsHistory1 = $resView1->viewData('paymentsHistory');
        $this->assertCount(1, $paymentsHistory1);
        $this->assertEquals(150000000, $paymentsHistory1[0]->cumulative_amount);
        $this->assertEquals(50000000, $paymentsHistory1[0]->remaining_commitment);

        // 4. Uji validasi overpayment: mencoba bayar Rp60.000.000 padahal sisa tinggal Rp50.000.000
        $resOver = $this->actingAs($this->adminUser)->post("/monitoring-anggaran/po/{$po->id}/payments", [
            'payment_date' => '2026-07-20',
            'amount' => 60000000,
            'reference_number' => 'INV-OVER',
            'description' => 'Mencoba bayar lebih dari sisa komitmen',
        ]);
        $resOver->assertSessionHas('error');

        // 5. Pembayaran Kedua yang Sah: Rp50.000.000
        $resPay2 = $this->actingAs($this->adminUser)->post("/monitoring-anggaran/po/{$po->id}/payments", [
            'payment_date' => '2026-07-25',
            'amount' => 50000000,
            'reference_number' => 'INV-TRM-2',
            'description' => 'Pelunasan PO 100%',
        ]);
        $resPay2->assertSessionHasNoErrors();

        $po->refresh();
        $wbs->refresh();

        // Verifikasi metrik setelah pembayaran kedua:
        // Total PO = 200.000.000, PO Expenses = 200.000.000, Commitment = 0
        $this->assertEquals(200000000, $wbs->total_po_value);
        $this->assertEquals(200000000, $wbs->po_expenses);
        $this->assertEquals(0, $wbs->commitment);
        $this->assertEquals(0, $po->remaining_commitment);
        // Assigned tetap 200.000.000 dan Available tetap 300.000.000
        $this->assertEquals(200000000, $wbs->assigned);
        $this->assertEquals(300000000, $wbs->available);

        // Cek halaman Detail WBS setelah pelunasan
        $resView2 = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}");
        $resView2->assertStatus(200);
        $activities2 = $resView2->viewData('activities');
        $poActivity2 = $activities2->firstWhere('type', 'PO');
        $this->assertNotNull($poActivity2);
        $this->assertEquals(200000000, $poActivity2->po_value);
        $this->assertEquals(0, $poActivity2->commitment_value, 'Baris PO harus menampilkan sisa komitmen Rp0 setelah pelunasan penuh');

        // Pastikan HTML menampilkan Rp0
        $resView2->assertSee('Rp0', false);

        // Riwayat Pembayaran Bertahap setelah pelunasan
        $paymentsHistory2 = $resView2->viewData('paymentsHistory');
        $this->assertCount(2, $paymentsHistory2);
        // Sorted desc by payment date: pembayaran ke-2 adalah yang pertama di list
        $latestPay = $paymentsHistory2->firstWhere('reference_number', 'INV-TRM-2');
        $this->assertEquals(200000000, $latestPay->cumulative_amount);
        $this->assertEquals(0, $latestPay->remaining_commitment);
    }

    /**
     * Test perbaikan form PO, PO Payment (Nomor SA/GR), Non-PO, dan multi-proof file uploads.
     */
    public function test_form_improvements_and_multi_proof_uploads(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-FORM-' . uniqid(),
            'wbs_name' => 'WBS Uji Form dan Multi-Upload',
            'initial_budget' => 500000000,
            'current_budget' => 500000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        // 1. Verifikasi UI form pada halaman index & detail WBS
        $resIndex = $this->actingAs($this->adminUser)->get('/monitoring-anggaran');
        $resIndex->assertStatus(200);
        // Form PO di index tidak ada field status_pengadaan dan nilai komitmen input
        $resIndex->assertDontSee('id="inputCommitmentValue"', false);
        // Form Non-PO di index tidak ada input transaction_number
        $resIndex->assertDontSee('name="transaction_number"', false);
        // Form Non-PO di index mendukung multiple proofs
        $resIndex->assertSee('name="proofs[]"', false);
        $resIndex->assertSee('multiple', false);

        $resDetail = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}");
        $resDetail->assertStatus(200);
        // Form PO Payment berlabel 'Nomor SA/GR'
        $resDetail->assertSee('Nomor SA/GR', false);
        $resDetail->assertDontSee('Nomor Referensi / SPK / Invoice', false);
        // Form PO Payment mendukung multiple proofs
        $resDetail->assertSee('name="proofs[]"', false);

        // 2. Simpan PO tanpa field status dan commitment_value
        $poNumber = 'PO-FORM-' . uniqid();
        $resPo = $this->actingAs($this->adminUser)->post('/monitoring-anggaran/po', [
            'budget_wbs_id' => $wbs->id,
            'po_number' => $poNumber,
            'program_name' => 'Pengadaan Peralatan Safety Baru',
            'vendor' => 'PT Multi Solusi Teknik',
            'po_value' => 150000000,
            'po_date' => '2026-07-01',
            'description' => 'PO tanpa input manual status/komitmen',
        ]);
        $resPo->assertSessionHasNoErrors();

        $po = BudgetPo::where('po_number', $poNumber)->first();
        $this->assertNotNull($po);
        $this->assertEquals('Aktif', $po->status);
        $this->assertEquals(150000000, $po->commitment_value);
        $this->assertEquals(150000000, $po->po_value);

        // 3. Catat Pembayaran PO dengan multi-proof files dan Nomor SA/GR
        $file1 = \Illuminate\Http\UploadedFile::fake()->create('bukti_transfer_1.pdf', 300, 'application/pdf');
        $file2 = \Illuminate\Http\UploadedFile::fake()->image('struk_bank.png');

        $resPay = $this->actingAs($this->adminUser)->post("/monitoring-anggaran/po/{$po->id}/payments", [
            'payment_number' => 'SAGR-2026-0001',
            'payment_date' => '2026-07-10',
            'amount' => 50000000,
            'description' => 'Pembayaran Termin 1 dengan multi-file bukti',
            'proofs' => [$file1, $file2],
        ]);
        $resPay->assertSessionHasNoErrors();

        $payment = BudgetPoPayment::where('payment_number', 'SAGR-2026-0001')->first();
        $this->assertNotNull($payment);
        $this->assertCount(2, $payment->proof_list);
        $this->assertNotNull($payment->proof_paths);
        $this->assertCount(2, $payment->proof_paths);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($payment->proof_paths[0]));
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($payment->proof_paths[1]));
        $this->assertEquals($payment->proof_paths[0], $payment->proof_path);

        // 4. Catat Realisasi Non-PO tanpa input nomor transaksi dan dengan multi-proof files
        $kwitansi1 = \Illuminate\Http\UploadedFile::fake()->create('nota_dinas.pdf', 150, 'application/pdf');
        $kwitansi2 = \Illuminate\Http\UploadedFile::fake()->image('nota_warung.jpg');

        $resNonPo = $this->actingAs($this->adminUser)->post('/monitoring-anggaran/non-po', [
            'budget_wbs_id' => $wbs->id,
            'program_name' => 'Snack dan Konsumsi Rapat Koordinasi',
            'transaction_date' => '2026-07-12',
            'amount' => 5000000,
            'recipient_vendor' => 'RM Sederhana',
            'description' => 'Realisasi non-po dengan nomor otomatis dan multi-bukti',
            'proofs' => [$kwitansi1, $kwitansi2],
        ]);
        $resNonPo->assertSessionHasNoErrors();

        $nonPo = BudgetNonPo::where('program_name', 'Snack dan Konsumsi Rapat Koordinasi')->first();
        $this->assertNotNull($nonPo);
        $this->assertNotEmpty($nonPo->transaction_number);
        $this->assertStringStartsWith('NPO-', $nonPo->transaction_number);
        $this->assertCount(2, $nonPo->proof_list);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($nonPo->proof_paths[0]));
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($nonPo->proof_paths[1]));

        // 5. Cek tampilan detail WBS menampilkan bukti transaksi dengan benar
        $resDetailAfter = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}");
        $resDetailAfter->assertStatus(200);
        $resDetailAfter->assertSee('2 Bukti Tersedia', false);
        $resDetailAfter->assertSee('Bukti #1', false);
        $resDetailAfter->assertSee('Bukti #2', false);

        // 6. Backward compatibility: record lama yang hanya memiliki kolom proof_path tunggal
        $oldPayment = BudgetPoPayment::create([
            'budget_po_id' => $po->id,
            'budget_wbs_id' => $wbs->id,
            'payment_number' => 'SAGR-LEGACY-' . uniqid(),
            'payment_date' => '2026-07-15',
            'amount' => 20000000,
            'proof_path' => 'budget-monitoring/payments/legacy_file.pdf',
            'proof_paths' => null,
        ]);
        $this->assertCount(1, $oldPayment->proof_list);
        $this->assertEquals(['budget-monitoring/payments/legacy_file.pdf'], $oldPayment->proof_list);

        // 7. Hapus payment & non-po akan membersihkan file dari storage
        $storedPayFiles = $payment->proof_paths;
        $storedNonPoFiles = $nonPo->proof_paths;

        $delPay = $this->actingAs($this->adminUser)->deleteJson("/monitoring-anggaran/payments/{$payment->id}");
        $delPay->assertStatus(200);
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('public')->exists($storedPayFiles[0]));
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('public')->exists($storedPayFiles[1]));

        $delNonPo = $this->actingAs($this->adminUser)->deleteJson("/monitoring-anggaran/non-po/{$nonPo->id}");
        $delNonPo->assertStatus(200);
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('public')->exists($storedNonPoFiles[0]));
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('public')->exists($storedNonPoFiles[1]));
    }

    /**
     * Test fitur filter transaksi pada halaman Detail WBS:
     * - Posisi filter tepat di atas tabel aktivitas
     * - Opsi Semua Transaksi, Pengadaan PO, Pembayaran PO, Realisasi Non-PO, Pengalihan Masuk, Pengalihan Keluar
     * - Filter isolasi baris tabel berdasarkan relasi database
     * - 7 Kartu ringkasan keuangan tidak terpengaruh oleh filter
     * - Badge jumlah aktivitas pada header tabel mengikuti hasil filter
     * - Pesan kosong dan tombol kembali ke Semua Transaksi
     */
    public function test_detail_wbs_transaction_filter_behavior_and_isolation(): void
    {
        // 1. Setup 2 WBS untuk relasi pengalihan
        $wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-FLT-' . uniqid(),
            'wbs_name' => 'WBS Uji Filter Transaksi',
            'initial_budget' => 1000000000,
            'current_budget' => 1000000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        $wbsTarget = BudgetWbs::create([
            'wbs_number' => 'WBS-TRG-' . uniqid(),
            'wbs_name' => 'WBS Lawan Pengalihan',
            'initial_budget' => 500000000,
            'current_budget' => 500000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);

        // Buat 1 Pengadaan PO
        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-FLT-001',
            'program_name' => 'Pengadaan Turbin Kompresor',
            'po_value' => 200000000,
            'commitment_value' => 200000000,
            'po_date' => '2026-05-01',
            'status' => 'Aktif',
        ]);

        // Buat 1 Pembayaran PO
        BudgetPoPayment::create([
            'budget_po_id' => $po->id,
            'budget_wbs_id' => $wbs->id,
            'payment_number' => 'PAY-FLT-001',
            'payment_date' => '2026-05-10',
            'amount' => 50000000,
        ]);

        // Buat 1 Realisasi Non-PO
        BudgetNonPo::create([
            'budget_wbs_id' => $wbs->id,
            'transaction_number' => 'NPO-FLT-001',
            'program_name' => 'Pelatihan First Aid Tim Reaksi Cepat',
            'transaction_date' => '2026-05-15',
            'amount' => 30000000,
        ]);

        // Buat 1 Pengalihan Keluar (WBS ini sebagai source)
        BudgetTransfer::create([
            'transfer_number' => 'TRF-OUT-001',
            'source_wbs_id' => $wbs->id,
            'destination_wbs_id' => $wbsTarget->id,
            'amount' => 20000000,
            'transfer_date' => '2026-05-20',
            'reason' => 'Bantuan Anggaran Proyek Lawan',
            'status' => 'Disetujui',
        ]);

        // Buat 1 Pengalihan Masuk (WBS ini sebagai destination)
        BudgetTransfer::create([
            'transfer_number' => 'TRF-IN-001',
            'source_wbs_id' => $wbsTarget->id,
            'destination_wbs_id' => $wbs->id,
            'amount' => 15000000,
            'transfer_date' => '2026-05-25',
            'reason' => 'Relokasi Saldo Sisa Safeguard',
            'status' => 'Disetujui',
        ]);

        $wbs->refresh();
        $expectedBudget = $wbs->current_budget;
        $expectedTotalPo = $wbs->total_po_value;
        $expectedCommitment = $wbs->remaining_po_commitment;
        $expectedPoExpenses = $wbs->po_realization;
        $expectedNonPoExpenses = $wbs->non_po_realization;
        $expectedAssigned = $wbs->allocated_budget;
        $expectedAvailable = $wbs->remaining_budget;

        // 2. Default: Semua Transaksi (5 aktivitas)
        $resAll = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}");
        $resAll->assertStatus(200);
        $resAll->assertSee('PO-FLT-001', false);
        $resAll->assertSee('PAY-FLT-001', false);
        $resAll->assertSee('NPO-FLT-001', false);
        $resAll->assertSee('TRF-OUT-001', false);
        $resAll->assertSee('TRF-IN-001', false);
        $resAll->assertSee('Total: 5 Aktivitas', false);
        // Opsi filter tersedia
        $resAll->assertSee('Semua Transaksi', false);
        $resAll->assertSee('Pengadaan PO', false);
        $resAll->assertSee('Pembayaran PO', false);
        $resAll->assertSee('Realisasi Non-PO', false);
        $resAll->assertSee('Pengalihan Anggaran Masuk', false);
        $resAll->assertSee('Pengalihan Anggaran Keluar', false);

        // 3. Filter Pengadaan PO: hanya PO-FLT-001
        $resPo = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}?filter_type=PO");
        $resPo->assertStatus(200);
        $activitiesPo = $resPo->viewData('activities');
        $this->assertCount(1, $activitiesPo);
        $this->assertEquals('PO-FLT-001', $activitiesPo->first()->identifier);
        $this->assertEquals('PO', $activitiesPo->first()->type);
        $this->assertFalse($activitiesPo->contains('identifier', 'PAY-FLT-001'));
        $this->assertFalse($activitiesPo->contains('identifier', 'NPO-FLT-001'));
        $this->assertFalse($activitiesPo->contains('identifier', 'TRF-OUT-001'));
        $this->assertFalse($activitiesPo->contains('identifier', 'TRF-IN-001'));
        $resPo->assertSee('PO-FLT-001', false);
        $resPo->assertSee('Total: 1 Aktivitas', false);

        // Pastikan nilai 7 kartu ringkasan keuangan TIDAK BERUBAH saat difilter
        $resPoWbs = $resPo->viewData('wbs');
        $this->assertEquals($expectedBudget, $resPoWbs->current_budget);
        $this->assertEquals($expectedTotalPo, $resPoWbs->total_po_value);
        $this->assertEquals($expectedCommitment, $resPoWbs->remaining_po_commitment);
        $this->assertEquals($expectedPoExpenses, $resPoWbs->po_realization);
        $this->assertEquals($expectedNonPoExpenses, $resPoWbs->non_po_realization);
        $this->assertEquals($expectedAssigned, $resPoWbs->allocated_budget);
        $this->assertEquals($expectedAvailable, $resPoWbs->remaining_budget);

        // 4. Filter Pembayaran PO: hanya PAY-FLT-001
        $resPay = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}?filter_type=PAYMENT");
        $resPay->assertStatus(200);
        $activitiesPay = $resPay->viewData('activities');
        $this->assertCount(1, $activitiesPay);
        $this->assertEquals('PAY-FLT-001', $activitiesPay->first()->identifier);
        $this->assertEquals('PAYMENT', $activitiesPay->first()->type);
        $this->assertFalse($activitiesPay->contains('identifier', 'PO-FLT-001'));
        $this->assertFalse($activitiesPay->contains('identifier', 'NPO-FLT-001'));
        $this->assertFalse($activitiesPay->contains('identifier', 'TRF-OUT-001'));
        $this->assertFalse($activitiesPay->contains('identifier', 'TRF-IN-001'));
        $resPay->assertSee('PAY-FLT-001', false);
        $resPay->assertSee('Total: 1 Aktivitas', false);

        // 5. Filter Realisasi Non-PO: hanya NPO-FLT-001
        $resNonPo = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}?filter_type=NON_PO");
        $resNonPo->assertStatus(200);
        $activitiesNonPo = $resNonPo->viewData('activities');
        $this->assertCount(1, $activitiesNonPo);
        $this->assertEquals('NPO-FLT-001', $activitiesNonPo->first()->identifier);
        $this->assertEquals('NON_PO', $activitiesNonPo->first()->type);
        $this->assertFalse($activitiesNonPo->contains('identifier', 'PO-FLT-001'));
        $this->assertFalse($activitiesNonPo->contains('identifier', 'PAY-FLT-001'));
        $this->assertFalse($activitiesNonPo->contains('identifier', 'TRF-OUT-001'));
        $this->assertFalse($activitiesNonPo->contains('identifier', 'TRF-IN-001'));
        $resNonPo->assertSee('NPO-FLT-001', false);
        $resNonPo->assertSee('Total: 1 Aktivitas', false);

        // 6. Filter Pengalihan Anggaran Masuk (TRANSFER_IN): hanya TRF-IN-001
        $resTrfIn = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}?filter_type=TRANSFER_IN");
        $resTrfIn->assertStatus(200);
        $activitiesTrfIn = $resTrfIn->viewData('activities');
        $this->assertCount(1, $activitiesTrfIn);
        $this->assertEquals('TRF-IN-001', $activitiesTrfIn->first()->identifier);
        $this->assertEquals('TRANSFER_IN', $activitiesTrfIn->first()->type);
        $this->assertFalse($activitiesTrfIn->contains('identifier', 'TRF-OUT-001'));
        $this->assertFalse($activitiesTrfIn->contains('identifier', 'PO-FLT-001'));
        $this->assertFalse($activitiesTrfIn->contains('identifier', 'PAY-FLT-001'));
        $this->assertFalse($activitiesTrfIn->contains('identifier', 'NPO-FLT-001'));
        $resTrfIn->assertSee('TRF-IN-001', false);
        $resTrfIn->assertSee('Total: 1 Aktivitas', false);

        // 7. Filter Pengalihan Anggaran Keluar (TRANSFER_OUT): hanya TRF-OUT-001
        $resTrfOut = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}?filter_type=TRANSFER_OUT");
        $resTrfOut->assertStatus(200);
        $activitiesTrfOut = $resTrfOut->viewData('activities');
        $this->assertCount(1, $activitiesTrfOut);
        $this->assertEquals('TRF-OUT-001', $activitiesTrfOut->first()->identifier);
        $this->assertEquals('TRANSFER_OUT', $activitiesTrfOut->first()->type);
        $this->assertFalse($activitiesTrfOut->contains('identifier', 'TRF-IN-001'));
        $this->assertFalse($activitiesTrfOut->contains('identifier', 'PO-FLT-001'));
        $this->assertFalse($activitiesTrfOut->contains('identifier', 'PAY-FLT-001'));
        $this->assertFalse($activitiesTrfOut->contains('identifier', 'NPO-FLT-001'));
        $resTrfOut->assertSee('TRF-OUT-001', false);
        $resTrfOut->assertSee('Total: 1 Aktivitas', false);

        // 8. Filter dengan hasil kosong (misal kata kunci pencarian yang tidak ada)
        $resEmpty = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}?filter_type=PO&search=KATA_KUNCI_TIDAK_ADA");
        $resEmpty->assertStatus(200);
        $this->assertCount(0, $resEmpty->viewData('activities'));
        $resEmpty->assertSee('Tidak Ada Transaksi yang Cocok', false);
        $resEmpty->assertSee('Kembali ke Semua Transaksi', false);

        // 9. Kembali ke Semua Transaksi (parameter all) mengembalikan seluruh 5 aktivitas
        $resReset = $this->actingAs($this->adminUser)->get("/monitoring-anggaran/wbs/{$wbs->id}?filter_type=all");
        $resReset->assertStatus(200);
        $this->assertCount(5, $resReset->viewData('activities'));
        $resReset->assertSee('Total: 5 Aktivitas', false);
    }
}

