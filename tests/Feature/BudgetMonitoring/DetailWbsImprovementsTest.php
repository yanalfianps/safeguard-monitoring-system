<?php

namespace Tests\Feature\BudgetMonitoring;

use App\Models\BudgetPo;
use App\Models\BudgetPoPayment;
use App\Models\BudgetTransaction;
use App\Models\BudgetWbs;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DetailWbsImprovementsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

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
    }

    public function test_detail_wbs_header_badge_format_and_info(): void
    {
        $uniqueCode = 'TST-' . rand(100, 999);
        $wbs = BudgetWbs::create([
            'wbs_number' => $uniqueCode,
            'wbs_name' => 'Program Keselamatan Kerja Tambang',
            'fiscal_year' => 2026,
            'initial_budget' => 500000000,
            'is_active' => true,
            'description' => 'WBS uji coba header badge',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('budget-monitoring.wbs.show', $wbs));

        $response->assertStatus(200);
        $response->assertSee('WBS: ' . $uniqueCode);
        $response->assertSee('Program Keselamatan Kerja Tambang');
        $response->assertSee('2026');
        $response->assertSee('Aktif');
    }

    public function test_detail_wbs_filter_labels_and_functionality(): void
    {
        $uniqueCode = 'WFL-' . rand(100, 999);
        $wbs = BudgetWbs::create([
            'wbs_number' => $uniqueCode,
            'wbs_name' => 'Filter Test WBS',
            'fiscal_year' => 2026,
            'initial_budget' => 100000000,
            'is_active' => true,
        ]);

        $po = BudgetPo::create([
            'budget_wbs_id' => $wbs->id,
            'po_number' => 'PO-' . rand(1000, 9999),
            'program_name' => 'Pengadaan APD',
            'vendor' => 'PT Vendor Aman',
            'total_po' => 20000000,
            'commitment_value' => 20000000,
            'status' => 'Aktif',
            'po_date' => '2026-03-01',
        ]);

        $payNum = 'PAY-' . rand(1000, 9999);
        BudgetPoPayment::create([
            'budget_wbs_id' => $wbs->id,
            'budget_po_id' => $po->id,
            'payment_number' => $payNum,
            'amount' => 10000000,
            'payment_date' => '2026-03-10',
            'description' => 'Tahap 1 APD',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('budget-monitoring.wbs.show', $wbs));

        $response->assertStatus(200);
        $response->assertSee('PO Expenses (1)');
        $response->assertSee('Non-PO Expenses (0)');

        // Test filtering by PAYMENT using filter_type=PAYMENT
        $filteredResponse = $this->actingAs($this->adminUser)
            ->get(route('budget-monitoring.wbs.show', ['wbs' => $wbs->id, 'filter_type' => 'PAYMENT']));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertSee($payNum);

        // Test filtering using alias PO Expenses
        $aliasResponse = $this->actingAs($this->adminUser)
            ->get(route('budget-monitoring.wbs.show', ['wbs' => $wbs->id, 'filter_type' => 'PO Expenses']));
        $aliasResponse->assertStatus(200);
        $aliasResponse->assertSee($payNum);
    }

    public function test_bertahap_removed_from_section_and_modal_headers(): void
    {
        $uniqueCode = 'WNB-' . rand(100, 999);
        $wbs = BudgetWbs::create([
            'wbs_number' => $uniqueCode,
            'wbs_name' => 'WBS Without Bertahap Title',
            'fiscal_year' => 2026,
            'initial_budget' => 50000000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('budget-monitoring.wbs.show', $wbs));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Pembayaran PO');
        $response->assertDontSee('Riwayat Pembayaran PO Bertahap');
        $response->assertSee('Catat Pembayaran PO');
        $response->assertDontSee('Catat Pembayaran PO Bertahap');
    }

    public function test_audit_log_row_numbering_is_strictly_sequential(): void
    {
        $uniqueCode = 'WAS-' . rand(100, 999);
        $wbs = BudgetWbs::create([
            'wbs_number' => $uniqueCode,
            'wbs_name' => 'WBS Sequential Audit Log',
            'fiscal_year' => 2026,
            'initial_budget' => 200000000,
            'is_active' => true,
        ]);

        // Insert multiple transactions with different dates
        BudgetTransaction::create([
            'budget_wbs_id' => $wbs->id,
            'transaction_number' => 'TRX-SEQ-001',
            'transaction_type' => 'BUDGET_INITIAL',
            'transaction_type_label' => 'Anggaran Awal',
            'amount' => 200000000,
            'transaction_date' => '2026-01-01',
            'description' => 'Penetapan Anggaran Awal',
            'created_by' => $this->adminUser->id,
        ]);

        BudgetTransaction::create([
            'budget_wbs_id' => $wbs->id,
            'transaction_number' => 'TRX-SEQ-002',
            'transaction_type' => 'PO_COMMITMENT',
            'transaction_type_label' => 'Komitmen PO',
            'amount' => 50000000,
            'transaction_date' => '2026-02-15',
            'description' => 'Penerbitan PO',
            'created_by' => $this->adminUser->id,
        ]);

        BudgetTransaction::create([
            'budget_wbs_id' => $wbs->id,
            'transaction_number' => 'TRX-SEQ-003',
            'transaction_type' => 'PAYMENT',
            'transaction_type_label' => 'PO Expenses',
            'amount' => 25000000,
            'transaction_date' => '2026-03-01',
            'description' => 'Pembayaran Tahap 1',
            'created_by' => $this->adminUser->id,
        ]);

        BudgetTransaction::create([
            'budget_wbs_id' => $wbs->id,
            'transaction_number' => 'TRX-SEQ-004',
            'transaction_type' => 'NON_PO',
            'transaction_type_label' => 'Non-PO Expenses',
            'amount' => 10000000,
            'transaction_date' => '2026-04-10',
            'description' => 'Pengeluaran Non-PO',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('budget-monitoring.wbs.show', $wbs));

        $response->assertStatus(200);

        $content = $response->getContent();

        // Extract the audit log table rows
        $auditTablePos = strpos($content, 'id="collapseRiwayatTransaksi"');
        $this->assertNotFalse($auditTablePos);
        $auditSection = substr($content, $auditTablePos);

        // Pattern matches rows in audit log table
        preg_match_all('/<td class="ps-3 text-center text-muted fw-semibold">\s*(\d+)\s*<\/td>/', $auditSection, $matches);

        $this->assertNotEmpty($matches[1]);
        $rowNumbers = array_map('intval', $matches[1]);

        // Assert strictly sequential 1, 2, 3, 4
        $this->assertEquals([1, 2, 3, 4], $rowNumbers);
    }
}
