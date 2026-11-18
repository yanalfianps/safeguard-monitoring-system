<?php

namespace Tests\Feature\BudgetMonitoring;

use App\Models\BudgetNonPo;
use App\Models\BudgetPo;
use App\Models\BudgetPoPayment;
use App\Models\BudgetWbs;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BudgetMonitoringMultiUploadTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected BudgetWbs $wbs;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin.upload.test@geodipa.co.id'],
            [
                'name' => 'Admin Upload Tester',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'aktif',
            ]
        );
        $this->adminUser->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'aktif']);

        $this->wbs = BudgetWbs::create([
            'wbs_number' => 'WBS-UPL-' . uniqid(),
            'wbs_name' => 'WBS Testing Multi Upload Dokumen',
            'initial_budget' => 500000000,
            'current_budget' => 500000000,
            'fiscal_year' => 2026,
            'is_active' => true,
        ]);
    }

    /**
     * Requirement 1 & 2: Audit all upload fields and verify "+ Tambah Dokumen" button exists.
     */
    public function test_ui_contains_tambah_dokumen_buttons_across_all_upload_forms(): void
    {
        // 1. Index page (Modal Tambah PO dan Modal Tambah Non-PO)
        $resIndex = $this->actingAs($this->adminUser)->get(route('budget-monitoring.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('indexPoAttachmentInputs', false);
        $resIndex->assertSee('indexNonPoProofInputs', false);
        $resIndex->assertSee('+ Tambah Dokumen', false);

        // 2. Detail WBS page (Modal Tambah PO, Modal Tambah Payment, Modal Tambah Non-PO, Modal Edit PO, Modal Edit Non-PO)
        $resDetail = $this->actingAs($this->adminUser)->get(route('budget-monitoring.wbs.show', $this->wbs->id));
        $resDetail->assertStatus(200);
        $resDetail->assertSee('showWbsPoAttachmentInputs', false);
        $resDetail->assertSee('paymentProofInputs', false);
        $resDetail->assertSee('showWbsNonPoProofInputs', false);
        $resDetail->assertSee('editPoAttachmentInputs', false);
        $resDetail->assertSee('editNonPoProofInputs', false);
        $resDetail->assertSee('editPoExistingAttachmentsContainer', false);
        $resDetail->assertSee('editNonPoExistingProofsContainer', false);
        $resDetail->assertSee('+ Tambah Dokumen', false);
    }

    /**
     * Requirement 2, 4, 5: Create PO with multiple files from different inputs/folders.
     */
    public function test_po_creation_stores_multiple_attachments_and_backward_compatible_path(): void
    {
        $poNumber = 'PO-UPL-' . uniqid();
        $file1 = UploadedFile::fake()->create('kontrak_vendor.pdf', 300, 'application/pdf');
        $file2 = UploadedFile::fake()->image('lampiran_spek.png');
        $file3 = UploadedFile::fake()->create('adendum.docx', 200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($this->adminUser)->post(route('budget-monitoring.po.store'), [
            'budget_wbs_id' => $this->wbs->id,
            'po_number' => $poNumber,
            'program_name' => 'Pengadaan APD Multi Dokumen',
            'vendor' => 'PT Mitra Sejati',
            'po_value' => 100000000,
            'po_date' => '2026-08-01',
            'attachments' => [$file1, $file2, $file3],
        ]);

        $response->assertSessionHasNoErrors();

        $po = BudgetPo::where('po_number', $poNumber)->firstOrFail();
        $this->assertCount(3, $po->attachment_list);
        $this->assertCount(3, $po->attachment_paths);
        $this->assertEquals($po->attachment_paths[0], $po->attachment_path);

        foreach ($po->attachment_paths as $fPath) {
            $this->assertTrue(Storage::disk('public')->exists($fPath), "File {$fPath} must exist on disk");
        }
    }

    /**
     * Requirement 4: Edit PO appends new files without losing existing attachments.
     */
    public function test_edit_po_appends_new_files_and_preserves_existing_files(): void
    {
        $fileOld1 = UploadedFile::fake()->create('old_kontrak.pdf', 100, 'application/pdf');
        $fileOld2 = UploadedFile::fake()->image('old_foto.jpg');

        $po = BudgetPo::create([
            'budget_wbs_id' => $this->wbs->id,
            'po_number' => 'PO-EDIT-' . uniqid(),
            'program_name' => 'PO Sebelum Diubah',
            'vendor' => 'PT Lama',
            'po_value' => 50000000,
            'commitment_value' => 50000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
            'attachment_path' => $fileOld1->store('budget/po_attachments', 'public'),
            'attachment_paths' => [
                $fileOld1->store('budget/po_attachments', 'public'),
                $fileOld2->store('budget/po_attachments', 'public'),
            ],
        ]);

        $initialPaths = $po->attachment_paths;
        $this->assertCount(2, $po->fresh()->attachment_list);

        // Edit PO: tambahkan file baru tanpa menghapus file lama
        $fileNew = UploadedFile::fake()->create('new_bap.pdf', 150, 'application/pdf');

        $resUpdate = $this->actingAs($this->adminUser)->put(route('budget-monitoring.po.update', $po->id), [
            'program_name' => 'PO Sesudah Diubah',
            'vendor' => 'PT Lama',
            'po_value' => 50000000,
            'po_date' => '2026-08-01',
            'attachments' => [$fileNew],
        ]);

        $resUpdate->assertSessionHasNoErrors();
        $freshPo = $po->fresh();

        $this->assertCount(3, $freshPo->attachment_list);
        $this->assertContains($initialPaths[0], $freshPo->attachment_list);
        $this->assertContains($initialPaths[1], $freshPo->attachment_list);

        // Pastikan seluruh file fisik ada di storage
        foreach ($freshPo->attachment_list as $fPath) {
            $this->assertTrue(Storage::disk('public')->exists($fPath));
        }
    }

    /**
     * Requirement 4: Explicitly deleting one attachment in Edit PO only deletes that file.
     */
    public function test_edit_po_explicit_deletion_removes_only_target_file(): void
    {
        $path1 = UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf')->store('budget/po_attachments', 'public');
        $path2 = UploadedFile::fake()->create('file2.pdf', 100, 'application/pdf')->store('budget/po_attachments', 'public');

        $po = BudgetPo::create([
            'budget_wbs_id' => $this->wbs->id,
            'po_number' => 'PO-DEL-' . uniqid(),
            'program_name' => 'PO Uji Hapus File Tertentu',
            'vendor' => 'Vendor Test',
            'po_value' => 40000000,
            'commitment_value' => 40000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
            'attachment_path' => $path1,
            'attachment_paths' => [$path1, $path2],
        ]);

        // Hapus path1 via deleted_attachments
        $resUpdate = $this->actingAs($this->adminUser)->put(route('budget-monitoring.po.update', $po->id), [
            'program_name' => 'PO Uji Hapus File Tertentu',
            'vendor' => 'Vendor Test',
            'po_value' => 40000000,
            'po_date' => '2026-08-01',
            'deleted_attachments' => [$path1],
        ]);

        $resUpdate->assertSessionHasNoErrors();
        $freshPo = $po->fresh();

        $this->assertCount(1, $freshPo->attachment_list);
        $this->assertNotContains($path1, $freshPo->attachment_list);
        $this->assertContains($path2, $freshPo->attachment_list);
        $this->assertEquals($path2, $freshPo->attachment_path);

        $this->assertFalse(Storage::disk('public')->exists($path1), "Deleted file must be removed from disk");
        $this->assertTrue(Storage::disk('public')->exists($path2), "Remaining file must be preserved on disk");
    }

    /**
     * Requirement 3 & 4: PO Payment with multi-proof files from different inputs/folders.
     */
    public function test_po_payment_stores_multiple_proofs(): void
    {
        $po = BudgetPo::create([
            'budget_wbs_id' => $this->wbs->id,
            'po_number' => 'PO-PAY-' . uniqid(),
            'program_name' => 'PO Induk Pembayaran',
            'vendor' => 'Vendor APD',
            'po_value' => 80000000,
            'commitment_value' => 80000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
        ]);

        $proofA = UploadedFile::fake()->create('bukti_transfer_a.pdf', 200, 'application/pdf');
        $proofB = UploadedFile::fake()->image('foto_kwitansi_b.jpg');

        $resPay = $this->actingAs($this->adminUser)->post(route('budget-monitoring.po-payment.store', $po->id), [
            'payment_number' => 'SAGR-TEST-' . uniqid(),
            'payment_date' => '2026-08-05',
            'amount' => 30000000,
            'description' => 'Pembayaran Termin 1 dengan 2 bukti',
            'proofs' => [$proofA, $proofB],
        ]);

        $resPay->assertSessionHasNoErrors();

        $payment = BudgetPoPayment::where('budget_po_id', $po->id)->firstOrFail();
        $this->assertCount(2, $payment->proof_list);
        $this->assertCount(2, $payment->proof_paths);
        $this->assertEquals($payment->proof_paths[0], $payment->proof_path);

        foreach ($payment->proof_paths as $fPath) {
            $this->assertTrue(Storage::disk('public')->exists($fPath));
        }
    }

    /**
     * Requirement 4: Non-PO creation and update with append & explicit deletion.
     */
    public function test_non_po_multi_proof_creation_and_update_with_append_and_deletion(): void
    {
        $file1 = UploadedFile::fake()->create('nota_awal_1.pdf', 150, 'application/pdf');
        $file2 = UploadedFile::fake()->image('struk_awal_2.png');

        $resStore = $this->actingAs($this->adminUser)->post(route('budget-monitoring.non-po.store'), [
            'budget_wbs_id' => $this->wbs->id,
            'program_name' => 'Konsumsi Rapat Multi Bukti',
            'transaction_date' => '2026-08-02',
            'amount' => 10000000,
            'recipient_vendor' => 'Catering Berkah',
            'description' => 'Awal simpan 2 file',
            'proofs' => [$file1, $file2],
        ]);

        $resStore->assertSessionHasNoErrors();
        $nonPo = BudgetNonPo::where('program_name', 'Konsumsi Rapat Multi Bukti')->firstOrFail();
        $this->assertCount(2, $nonPo->proof_list);

        $p1 = $nonPo->proof_paths[0];
        $p2 = $nonPo->proof_paths[1];

        // Edit Non-PO: hapus p1, pertahankan p2, dan tambahkan p3
        $file3 = UploadedFile::fake()->create('nota_tambahan_3.pdf', 120, 'application/pdf');

        $resUpdate = $this->actingAs($this->adminUser)->put(route('budget-monitoring.non-po.update', $nonPo->id), [
            'program_name' => 'Konsumsi Rapat Multi Bukti (Updated)',
            'transaction_date' => '2026-08-02',
            'amount' => 10000000,
            'recipient_vendor' => 'Catering Berkah',
            'deleted_proofs' => [$p1],
            'proofs' => [$file3],
        ]);

        $resUpdate->assertSessionHasNoErrors();
        $freshNonPo = $nonPo->fresh();

        $this->assertCount(2, $freshNonPo->proof_list);
        $this->assertNotContains($p1, $freshNonPo->proof_list);
        $this->assertContains($p2, $freshNonPo->proof_list);

        $this->assertFalse(Storage::disk('public')->exists($p1), "Deleted proof p1 must be deleted from storage");
        $this->assertTrue(Storage::disk('public')->exists($p2), "Preserved proof p2 must still exist on storage");
        $this->assertTrue(Storage::disk('public')->exists($freshNonPo->proof_list[1]), "New proof p3 must exist on storage");
    }

    /**
     * Requirement 6: File validation individual size limit & mime types.
     */
    public function test_file_validation_rejects_disallowed_types_and_oversized_files(): void
    {
        // 1. PO rejects file > 10MB
        $oversizedPoFile = UploadedFile::fake()->create('huge_contract.pdf', 11000, 'application/pdf');
        $resPoOversize = $this->actingAs($this->adminUser)->post(route('budget-monitoring.po.store'), [
            'budget_wbs_id' => $this->wbs->id,
            'po_number' => 'PO-OVER-' . uniqid(),
            'program_name' => 'PO Oversize Test',
            'po_value' => 10000000,
            'po_date' => '2026-08-01',
            'attachments' => [$oversizedPoFile],
        ]);
        $resPoOversize->assertSessionHasErrors();

        // 2. Non-PO rejects file > 5MB
        $oversizedNonPoFile = UploadedFile::fake()->create('huge_receipt.pdf', 6000, 'application/pdf');
        $resNonPoOversize = $this->actingAs($this->adminUser)->post(route('budget-monitoring.non-po.store'), [
            'budget_wbs_id' => $this->wbs->id,
            'program_name' => 'Non PO Oversize Test',
            'transaction_date' => '2026-08-01',
            'amount' => 1000000,
            'proofs' => [$oversizedNonPoFile],
        ]);
        $resNonPoOversize->assertSessionHasErrors();

        // 3. Reject forbidden extension (e.g. .exe or .sh)
        $badFile = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');
        $resBad = $this->actingAs($this->adminUser)->post(route('budget-monitoring.non-po.store'), [
            'budget_wbs_id' => $this->wbs->id,
            'program_name' => 'Non PO Bad File Test',
            'transaction_date' => '2026-08-01',
            'amount' => 1000000,
            'proofs' => [$badFile],
        ]);
        $resBad->assertSessionHasErrors();
    }

    /**
     * Requirement 7: Detail WBS displays multiple documents dropdown cleanly.
     */
    public function test_detail_wbs_renders_multiple_attachments_dropdown(): void
    {
        $file1 = UploadedFile::fake()->create('doc_1.pdf', 100, 'application/pdf')->store('budget/po_attachments', 'public');
        $file2 = UploadedFile::fake()->image('doc_2.jpg')->store('budget/po_attachments', 'public');

        $po = BudgetPo::create([
            'budget_wbs_id' => $this->wbs->id,
            'po_number' => 'PO-DROPDOWN-' . uniqid(),
            'program_name' => 'PO Dengan Dropdown Dokumen',
            'po_value' => 20000000,
            'commitment_value' => 20000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
            'attachment_path' => $file1,
            'attachment_paths' => [$file1, $file2],
        ]);

        $res = $this->actingAs($this->adminUser)->get(route('budget-monitoring.wbs.show', $this->wbs->id));
        $res->assertStatus(200);
        $res->assertSee('2 Bukti Tersedia', false);
        $res->assertSee(asset('storage/' . $file1), false);
        $res->assertSee(asset('storage/' . $file2), false);
    }

    /**
     * Requirement 9: Financial calculations remain completely invariant and accurate.
     */
    public function test_financial_calculations_remain_unaffected_by_multi_upload(): void
    {
        $initialBudget = (float) $this->wbs->current_budget;

        $po = BudgetPo::create([
            'budget_wbs_id' => $this->wbs->id,
            'po_number' => 'PO-FIN-' . uniqid(),
            'program_name' => 'PO Uji Keuangan',
            'po_value' => 50000000,
            'commitment_value' => 50000000,
            'po_date' => '2026-08-01',
            'status' => 'Aktif',
            'attachment_paths' => ['budget/po_attachments/f1.pdf', 'budget/po_attachments/f2.pdf'],
        ]);

        $pay = BudgetPoPayment::create([
            'budget_po_id' => $po->id,
            'budget_wbs_id' => $this->wbs->id,
            'payment_number' => 'SAGR-FIN-' . uniqid(),
            'payment_date' => '2026-08-02',
            'amount' => 20000000,
            'proof_paths' => ['budget/payment_proofs/p1.pdf', 'budget/payment_proofs/p2.pdf'],
        ]);

        $nonPo = BudgetNonPo::create([
            'budget_wbs_id' => $this->wbs->id,
            'transaction_number' => 'NPO-FIN-' . uniqid(),
            'program_name' => 'Non PO Uji Keuangan',
            'transaction_date' => '2026-08-03',
            'amount' => 15000000,
            'proof_paths' => ['budget/non_po_proofs/n1.pdf'],
        ]);

        $wbs = $this->wbs->fresh();

        $this->assertEquals(50000000, $wbs->total_po_value);
        $this->assertEquals(20000000, $wbs->po_realization);
        $this->assertEquals(30000000, $wbs->remaining_po_commitment);
        $this->assertEquals(15000000, $wbs->non_po_realization);
        $this->assertEquals(65000000, $wbs->allocated_budget); // Total PO (50M) + Non-PO (15M)
        $this->assertEquals(435000000, $wbs->remaining_budget); // 500M - 65M
    }
}
