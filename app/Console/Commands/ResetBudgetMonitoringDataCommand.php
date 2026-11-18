<?php

namespace App\Console\Commands;

use App\Services\BudgetMonitoringResetService;
use Illuminate\Console\Command;

class ResetBudgetMonitoringDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'budget:reset 
                            {--all : Kosongkan seluruh data Monitoring Anggaran termasuk WBS} 
                            {--transactions-only : Kosongkan transaksi keuangan saja (PO, Pembayaran, Non-PO, Pengalihan) dan pertahankan WBS} 
                            {--force : Lewati konfirmasi interaktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kosongkan data pada modul Monitoring Anggaran secara aman dengan backup otomatis';

    /**
     * Execute the console command.
     */
    public function handle(BudgetMonitoringResetService $resetService): int
    {
        $this->info('================================================================');
        $this->info('   RESET DATA MODUL MONITORING ANGGARAN (HSE & SAFEGUARD)       ');
        $this->info('================================================================');

        $counts = $resetService->getAuditCounts();

        $this->warn('Perhatian: Tindakan ini hanya memengaruhi modul Monitoring Anggaran.');
        $this->line('Modul lain (HSSE Plan, Incident, Events, Manhour, Users, dll.) 100% AMAN.');
        $this->newLine();

        $this->table(
            ['Tabel Database', 'Deskripsi Data', 'Jumlah Record Saat Ini'],
            [
                ['budget_wbs', 'Master Data WBS / Program', $counts['budget_wbs']],
                ['budget_pos', 'Pengadaan / Purchase Order (PO)', $counts['budget_pos']],
                ['budget_po_payments', 'Realisasi Pembayaran Termin PO', $counts['budget_po_payments']],
                ['budget_non_pos', 'Realisasi Non-PO Langsung', $counts['budget_non_pos']],
                ['budget_transfers', 'Pengalihan Anggaran Antar-WBS', $counts['budget_transfers']],
                ['budget_adjustments', 'Riwayat Penyesuaian Anggaran WBS', $counts['budget_adjustments']],
                ['budget_transactions', 'Log Riwayat Transaksi Keuangan', $counts['budget_transactions']],
                ['TOTAL', 'Total Record Keseluruhan', $counts['total_records']],
            ]
        );

        if ($counts['total_records'] === 0) {
            $this->info('Data Monitoring Anggaran sudah dalam keadaan kosong.');
            return Command::SUCCESS;
        }

        // Tentukan mode reset
        $resetWbs = true;
        if ($this->option('transactions-only')) {
            $resetWbs = false;
        } elseif ($this->option('all')) {
            $resetWbs = true;
        } else {
            $choice = $this->choice(
                'Pilih cakupan data yang ingin direset:',
                [
                    'all' => 'Reset Total dari Awal (Kosongkan seluruh transaksi & WBS pengujian)',
                    'transactions_only' => 'Kosongkan Transaksi Saja (Pertahankan WBS, kembalikan pagu ke Pagu Awal)',
                ],
                'all'
            );
            $resetWbs = ($choice === 'all');
        }

        if (!$this->option('force')) {
            $confirmation = $this->confirm(
                'Data akan dihapus permanen (sistem otomatis membuat backup JSON terlebih dahulu). Lanjutkan?',
                false
            );

            if (!$confirmation) {
                $this->warn('Proses reset data dibatalkan oleh pengguna.');
                return Command::INVALID;
            }
        }

        $this->info('Membuat backup snapshot data...');
        $result = $resetService->resetData($resetWbs);

        $this->newLine();
        $this->info('✓ Backup snapshot data tersimpan di:');
        $this->line('  ' . $result['backup_file']);
        $this->newLine();

        $this->info('✓ Reset data Monitoring Anggaran BERHASIL dijalankan.');
        $this->table(
            ['Tabel Database', 'Jumlah Record Dihapus', 'Sisa Record'],
            [
                ['budget_transactions', $result['deleted_counts']['budget_transactions'], $result['counts_after']['budget_transactions']],
                ['budget_po_payments', $result['deleted_counts']['budget_po_payments'], $result['counts_after']['budget_po_payments']],
                ['budget_pos', $result['deleted_counts']['budget_pos'], $result['counts_after']['budget_pos']],
                ['budget_non_pos', $result['deleted_counts']['budget_non_pos'], $result['counts_after']['budget_non_pos']],
                ['budget_transfers', $result['deleted_counts']['budget_transfers'], $result['counts_after']['budget_transfers']],
                ['budget_adjustments', $result['deleted_counts']['budget_adjustments'], $result['counts_after']['budget_adjustments']],
                ['budget_wbs', $result['deleted_counts']['budget_wbs'], $result['counts_after']['budget_wbs']],
            ]
        );

        $this->info("Total record yang dibersihkan: {$result['total_deleted']} record.");
        $this->info('Modul Monitoring Anggaran siap digunakan untuk pencatatan baru.');

        return Command::SUCCESS;
    }
}
