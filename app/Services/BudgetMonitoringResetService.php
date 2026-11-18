<?php

namespace App\Services;

use App\Models\BudgetAdjustment;
use App\Models\BudgetNonPo;
use App\Models\BudgetPo;
use App\Models\BudgetPoPayment;
use App\Models\BudgetTransaction;
use App\Models\BudgetTransfer;
use App\Models\BudgetWbs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BudgetMonitoringResetService
{
    /**
     * Ambil jumlah data aktual saat ini di seluruh tabel Monitoring Anggaran.
     */
    public function getAuditCounts(): array
    {
        return [
            'budget_wbs' => BudgetWbs::count(),
            'budget_pos' => BudgetPo::count(),
            'budget_po_payments' => BudgetPoPayment::count(),
            'budget_non_pos' => BudgetNonPo::count(),
            'budget_transfers' => BudgetTransfer::count(),
            'budget_adjustments' => BudgetAdjustment::count(),
            'budget_transactions' => BudgetTransaction::count(),
            'total_records' => BudgetWbs::count()
                + BudgetPo::count()
                + BudgetPoPayment::count()
                + BudgetNonPo::count()
                + BudgetTransfer::count()
                + BudgetAdjustment::count()
                + BudgetTransaction::count(),
        ];
    }

    /**
     * Buat cadangan data (backup snapshot JSON) sebelum dilakukan pengosongan data.
     */
    public function createBackup(): string
    {
        $backupDir = storage_path('app/budget-backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true, true);
        }

        $timestamp = date('Ymd_His');
        $filePath = $backupDir . DIRECTORY_SEPARATOR . "budget_backup_{$timestamp}.json";

        $data = [
            'meta' => [
                'created_at' => now()->toIso8601String(),
                'initiated_by' => Auth::check() ? Auth::user()->email : 'System/CLI',
                'database' => DB::connection()->getDatabaseName(),
                'version' => 'HSSE Laravel 12 - Monitoring Anggaran',
            ],
            'tables' => [
                'budget_wbs' => BudgetWbs::all()->toArray(),
                'budget_pos' => BudgetPo::all()->toArray(),
                'budget_po_payments' => BudgetPoPayment::all()->toArray(),
                'budget_non_pos' => BudgetNonPo::all()->toArray(),
                'budget_transfers' => BudgetTransfer::all()->toArray(),
                'budget_adjustments' => BudgetAdjustment::all()->toArray(),
                'budget_transactions' => BudgetTransaction::all()->toArray(),
            ],
        ];

        File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        Log::info("Budget Monitoring backup successfully created at: {$filePath}");

        return $filePath;
    }

    /**
     * Jalankan proses reset data secara transaksional dan aman.
     *
     * @param bool $resetWbs Apakah data master WBS Monitoring Anggaran juga ikut dikosongkan.
     * @return array Hasil eksekusi dan ringkasan data yang dihapus.
     */
    public function resetData(bool $resetWbs = true): array
    {
        // 1. Audit jumlah record sebelum penghapusan
        $countsBefore = $this->getAuditCounts();

        // 2. Buat cadangan backup JSON otomatis
        $backupFile = $this->createBackup();

        // 3. Eksekusi penghapusan dalam DB Transaction dengan urutan relasi yang benar (child first)
        $deletedCounts = [];

        DB::transaction(function () use ($resetWbs, $countsBefore, &$deletedCounts) {
            // Urutan 1: Riwayat Transaksi Audit (BudgetTransaction)
            $deletedCounts['budget_transactions'] = BudgetTransaction::query()->delete();

            // Urutan 2: Pembayaran Termin PO (BudgetPoPayment)
            $deletedCounts['budget_po_payments'] = BudgetPoPayment::query()->delete();

            // Urutan 3: Data Pengadaan / PO (BudgetPo)
            $deletedCounts['budget_pos'] = BudgetPo::query()->delete();

            // Urutan 4: Realisasi Non-PO (BudgetNonPo)
            $deletedCounts['budget_non_pos'] = BudgetNonPo::query()->delete();

            // Urutan 5: Pengalihan Anggaran Antar-WBS (BudgetTransfer)
            $deletedCounts['budget_transfers'] = BudgetTransfer::query()->delete();

            // Urutan 6: Penyesuaian Anggaran (BudgetAdjustment)
            $deletedCounts['budget_adjustments'] = BudgetAdjustment::query()->delete();

            // Urutan 7: WBS (BudgetWbs)
            if ($resetWbs) {
                $deletedCounts['budget_wbs'] = BudgetWbs::query()->delete();
            } else {
                // Jika WBS dipertahankan, kembalikan current_budget ke initial_budget
                BudgetWbs::query()->update(['current_budget' => DB::raw('initial_budget')]);
                $deletedCounts['budget_wbs'] = 0;
            }
        });

        // 4. Verifikasi kondisi database setelah reset
        $countsAfter = $this->getAuditCounts();

        Log::warning('Budget Monitoring data reset successfully executed.', [
            'reset_wbs' => $resetWbs,
            'deleted' => $deletedCounts,
            'counts_after' => $countsAfter,
            'backup_file' => $backupFile,
        ]);

        return [
            'success' => true,
            'reset_wbs' => $resetWbs,
            'backup_file' => $backupFile,
            'counts_before' => $countsBefore,
            'deleted_counts' => $deletedCounts,
            'counts_after' => $countsAfter,
            'total_deleted' => array_sum($deletedCounts),
        ];
    }
}

