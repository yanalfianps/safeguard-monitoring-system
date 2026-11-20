<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BudgetAdjustment;
use App\Models\BudgetNonPo;
use App\Models\BudgetPo;
use App\Models\BudgetPoPayment;
use App\Models\BudgetTransaction;
use App\Models\BudgetTransfer;
use App\Models\BudgetWbs;
use App\Services\BudgetMonitoringResetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class BudgetMonitoringController extends Controller
{
    /**
     * Halaman Utama Monitoring Anggaran (HSE & Safeguard)
     */
    public function index(Request $request): View|JsonResponse
    {
        $activeTab = $request->input('tab', 'wbs');
        $validTabs = ['wbs', 'ringkasan', 'po', 'realisasi-po', 'realisasi-non-po', 'pengalihan', 'riwayat'];
        if (!in_array($activeTab, $validTabs, true)) {
            $activeTab = 'wbs';
        }

        $allWbs = BudgetWbs::where('is_active', true)->orderBy('wbs_number')->get();

        // 1. Ringkasan 6 Indikator Utama (Berdasarkan Data Keseluruhan / Filter Tahun)
        $fiscalYear = $request->filled('fiscal_year') ? (int) $request->input('fiscal_year') : null;

        $wbsQuery = BudgetWbs::query()->where('is_active', true);
        if ($fiscalYear) {
            $wbsQuery->where('fiscal_year', $fiscalYear);
        }
        $wbsListScope = $wbsQuery->get();

        $wbsIds = $wbsListScope->pluck('id')->toArray();

        // Total Anggaran Keseluruhan (Budget)
        $totalAnggaran = (float) $wbsListScope->sum('current_budget');

        // Total Nilai PO Keseluruhan (Total PO)
        $totalPo = (float) BudgetPo::whereIn('budget_wbs_id', $wbsIds)
            ->where('status', '!=', 'Dibatalkan')
            ->sum(DB::raw('CASE WHEN po_value > 0 THEN po_value ELSE commitment_value END'));

        // Realisasi Pembayaran PO (PO Expenses)
        $realisasiPo = (float) BudgetPoPayment::whereIn('budget_wbs_id', $wbsIds)->sum('amount');

        // Realisasi Non-PO (Non-PO Expenses)
        $realisasiNonPo = (float) BudgetNonPo::whereIn('budget_wbs_id', $wbsIds)->sum('amount');

        // Sisa Komitmen PO (Commitment = Total PO - PO Expenses)
        $sisaKomitmenPo = max(0, (float) ($totalPo - $realisasiPo));

        // Anggaran Teralokasi (Assigned = Total PO + Non-PO Expenses)
        // (Pembayaran PO tidak mengurangi sisa anggaran lagi karena komitmen PO sudah mengalokasikannya)
        $anggaranTeralokasi = (float) ($totalPo + $realisasiNonPo);

        // Sisa Anggaran (Available = Budget - Assigned)
        $sisaAnggaran = (float) ($totalAnggaran - $anggaranTeralokasi);

        $metrics = [
            'total_anggaran' => $totalAnggaran,
            'budget' => $totalAnggaran,
            'total_po' => $totalPo,
            'komitmen_po' => $totalPo,
            'commitment' => $sisaKomitmenPo,
            'realisasi_po' => $realisasiPo,
            'po_expenses' => $realisasiPo,
            'realisasi_non_po' => $realisasiNonPo,
            'non_po_expenses' => $realisasiNonPo,
            'anggaran_teralokasi' => $anggaranTeralokasi,
            'assigned' => $anggaranTeralokasi,
            'sisa_anggaran' => $sisaAnggaran,
            'available' => $sisaAnggaran,
            'sisa_komitmen_po' => $sisaKomitmenPo,
        ];

        // 2. Data Tabel Sesuai Tab
        // Tab Ringkasan & WBS: Daftar WBS dengan metrik per WBS
        $searchWbs = trim((string) $request->input('search_wbs', $request->input('search', '')));
        $wbsTableQuery = BudgetWbs::with(['pos', 'poPayments', 'nonPos']);
        if ($fiscalYear) {
            $wbsTableQuery->where('fiscal_year', $fiscalYear);
        }
        if ($searchWbs !== '') {
            $wbsTableQuery->where(function ($q) use ($searchWbs) {
                $q->where('wbs_number', 'like', "%{$searchWbs}%")
                  ->orWhere('wbs_name', 'like', "%{$searchWbs}%")
                  ->orWhere('description', 'like', "%{$searchWbs}%");
            });
        }
        $wbsRecords = $wbsTableQuery->orderBy('wbs_number', 'asc')->paginate(10, ['*'], 'page_wbs')->withQueryString();

        // Tab PO: Daftar PO
        $searchPo = trim((string) $request->input('search_po', ''));
        $filterPoWbs = $request->input('po_wbs_id');
        $poTableQuery = BudgetPo::with(['wbs', 'payments']);
        if ($filterPoWbs) {
            $poTableQuery->where('budget_wbs_id', $filterPoWbs);
        }
        if ($searchPo !== '') {
            $poTableQuery->where(function ($q) use ($searchPo) {
                $q->where('po_number', 'like', "%{$searchPo}%")
                  ->orWhere('program_name', 'like', "%{$searchPo}%")
                  ->orWhere('vendor', 'like', "%{$searchPo}%")
                  ->orWhereHas('wbs', function ($sub) use ($searchPo) {
                      $sub->where('wbs_number', 'like', "%{$searchPo}%")
                          ->orWhere('wbs_name', 'like', "%{$searchPo}%");
                  });
            });
        }
        $poRecords = $poTableQuery->orderBy('po_date', 'desc')->orderBy('id', 'desc')->paginate(10, ['*'], 'page_po')->withQueryString();

        // Tab Realisasi PO: Pembayaran Bertahap
        $searchPay = trim((string) $request->input('search_pay', ''));
        $filterPayWbs = $request->input('pay_wbs_id');
        $payTableQuery = BudgetPoPayment::with(['po', 'wbs', 'creator']);
        if ($filterPayWbs) {
            $payTableQuery->where('budget_wbs_id', $filterPayWbs);
        }
        if ($searchPay !== '') {
            $payTableQuery->where(function ($q) use ($searchPay) {
                $q->where('payment_number', 'like', "%{$searchPay}%")
                  ->orWhere('reference_number', 'like', "%{$searchPay}%")
                  ->orWhereHas('po', function ($sub) use ($searchPay) {
                      $sub->where('po_number', 'like', "%{$searchPay}%")
                          ->orWhere('program_name', 'like', "%{$searchPay}%");
                  })
                  ->orWhereHas('wbs', function ($sub) use ($searchPay) {
                      $sub->where('wbs_number', 'like', "%{$searchPay}%")
                          ->orWhere('wbs_name', 'like', "%{$searchPay}%");
                  });
            });
        }
        $poPaymentRecords = $payTableQuery->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->paginate(10, ['*'], 'page_pay')->withQueryString();

        // Tab Realisasi Non-PO
        $searchNonPo = trim((string) $request->input('search_non_po', ''));
        $filterNonPoWbs = $request->input('non_po_wbs_id');
        $nonPoTableQuery = BudgetNonPo::with(['wbs', 'creator']);
        if ($filterNonPoWbs) {
            $nonPoTableQuery->where('budget_wbs_id', $filterNonPoWbs);
        }
        if ($searchNonPo !== '') {
            $nonPoTableQuery->where(function ($q) use ($searchNonPo) {
                $q->where('transaction_number', 'like', "%{$searchNonPo}%")
                  ->orWhere('program_name', 'like', "%{$searchNonPo}%")
                  ->orWhere('recipient_vendor', 'like', "%{$searchNonPo}%")
                  ->orWhereHas('wbs', function ($sub) use ($searchNonPo) {
                      $sub->where('wbs_number', 'like', "%{$searchNonPo}%")
                          ->orWhere('wbs_name', 'like', "%{$searchNonPo}%");
                  });
            });
        }
        $nonPoRecords = $nonPoTableQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->paginate(10, ['*'], 'page_npo')->withQueryString();

        // Tab Pengalihan Anggaran
        $searchTransfer = trim((string) $request->input('search_transfer', ''));
        $transferTableQuery = BudgetTransfer::with(['sourceWbs', 'destinationWbs', 'creator']);
        if ($searchTransfer !== '') {
            $transferTableQuery->where(function ($q) use ($searchTransfer) {
                $q->where('transfer_number', 'like', "%{$searchTransfer}%")
                  ->orWhere('reason', 'like', "%{$searchTransfer}%")
                  ->orWhereHas('sourceWbs', function ($sub) use ($searchTransfer) {
                      $sub->where('wbs_number', 'like', "%{$searchTransfer}%")
                          ->orWhere('wbs_name', 'like', "%{$searchTransfer}%");
                  })
                  ->orWhereHas('destinationWbs', function ($sub) use ($searchTransfer) {
                      $sub->where('wbs_number', 'like', "%{$searchTransfer}%")
                          ->orWhere('wbs_name', 'like', "%{$searchTransfer}%");
                  });
            });
        }
        $transferRecords = $transferTableQuery->orderBy('transfer_date', 'desc')->orderBy('id', 'desc')->paginate(10, ['*'], 'page_trf')->withQueryString();

        // Tab Riwayat Transaksi Keuangan
        $searchTrx = trim((string) $request->input('search_trx', ''));
        $filterTrxType = $request->input('trx_type');
        $filterTrxWbs = $request->input('trx_wbs_id');
        $trxTableQuery = BudgetTransaction::with(['wbs', 'creator']);
        if ($filterTrxType) {
            $trxTableQuery->where('transaction_type', $filterTrxType);
        }
        if ($filterTrxWbs) {
            $trxTableQuery->where('budget_wbs_id', $filterTrxWbs);
        }
        if ($searchTrx !== '') {
            $trxTableQuery->where(function ($q) use ($searchTrx) {
                $q->where('transaction_number', 'like', "%{$searchTrx}%")
                  ->orWhere('reference_number', 'like', "%{$searchTrx}%")
                  ->orWhere('program_name', 'like', "%{$searchTrx}%")
                  ->orWhere('description', 'like', "%{$searchTrx}%")
                  ->orWhereHas('wbs', function ($sub) use ($searchTrx) {
                      $sub->where('wbs_number', 'like', "%{$searchTrx}%")
                          ->orWhere('wbs_name', 'like', "%{$searchTrx}%");
                  });
            });
        }
        if ($request->filled('trx_start_date') && $request->filled('trx_end_date')) {
            $trxTableQuery->whereBetween('transaction_date', [$request->input('trx_start_date'), $request->input('trx_end_date')]);
        }
        $transactionRecords = $trxTableQuery->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->paginate(15, ['*'], 'page_trx')->withQueryString();

        // Nomor otomatis untuk modal pembuatan
        $nextWbsNumber = BudgetWbs::generateNextWbsNumber();
        $nextPoNumber = BudgetPo::generateNextPoNumber();
        $nextPaymentNumber = BudgetPoPayment::generateNextPaymentNumber();
        $nextNonPoNumber = BudgetNonPo::generateNextTransactionNumber();
        $nextTransferNumber = BudgetTransfer::generateNextTransferNumber();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'activeTab' => $activeTab,
            ]);
        }

        $budgetAuditCounts = app(BudgetMonitoringResetService::class)->getAuditCounts();

        return view('data.budget-monitoring.index', compact(
            'activeTab',
            'metrics',
            'allWbs',
            'wbsRecords',
            'poRecords',
            'poPaymentRecords',
            'nonPoRecords',
            'transferRecords',
            'transactionRecords',
            'nextWbsNumber',
            'nextPoNumber',
            'nextPaymentNumber',
            'nextNonPoNumber',
            'nextTransferNumber',
            'budgetAuditCounts'
        ));
    }

    /**
     * Detail Anggaran per WBS
     */
    public function showWbs(Request $request, BudgetWbs $wbs): View|JsonResponse
    {
        $wbs->load([
            'pos.payments',
            'poPayments.po',
            'nonPos',
            'transfersOut.destinationWbs',
            'transfersIn.sourceWbs',
            'adjustments.creator',
            'transactions.creator',
        ]);

        $detailMetrics = [
            'wbs_number' => $wbs->wbs_number,
            'wbs_name' => $wbs->wbs_name,
            'initial_budget' => $wbs->initial_budget,
            'current_budget' => $wbs->current_budget,
            'budget' => $wbs->current_budget,
            'total_po_value' => $wbs->total_po_value,
            'total_po' => $wbs->total_po_value,
            'po_commitment' => $wbs->po_commitment,
            'commitment' => $wbs->remaining_po_commitment,
            'po_realization' => $wbs->po_realization,
            'po_expenses' => $wbs->po_realization,
            'non_po_realization' => $wbs->non_po_realization,
            'non_po_expenses' => $wbs->non_po_realization,
            'allocated_budget' => $wbs->allocated_budget,
            'assigned' => $wbs->allocated_budget,
            'remaining_budget' => $wbs->remaining_budget,
            'available' => $wbs->remaining_budget,
            'remaining_po_commitment' => $wbs->remaining_po_commitment,
        ];

        // Membentuk daftar aktivitas WBS gabungan (Tabel Kuning Konsep Excel)
        $activities = collect();

        // 1. Pengadaan PO
        foreach ($wbs->pos as $po) {
            $poVal = (float) ($po->po_value > 0 ? $po->po_value : $po->commitment_value);
            $poPaid = (float) $po->total_payment;
            $remCommitment = $po->status === 'Dibatalkan' ? 0.0 : max(0.0, (float) ($poVal - $poPaid));

            $activities->push((object) [
                'type' => 'PO',
                'type_label' => 'Pengadaan PO',
                'badge_class' => 'bg-primary-subtle text-primary border border-primary-subtle',
                'id' => $po->id,
                'item' => $po,
                'date' => $po->po_date,
                'program' => $po->program_name,
                'identifier' => $po->po_number,
                'vendor' => $po->vendor,
                'po_value' => $poVal,
                'commitment_value' => $remCommitment,
                'po_realization' => null,
                'non_po_realization' => null,
                'description' => ($po->vendor ? "Vendor: {$po->vendor}. " : '') . ($po->description ?? '-'),
                'status' => $po->status,
                'attachment' => $po->attachment_path,
                'proofs' => $po->attachment_list,
                'can_delete' => !$po->payments()->exists(),
                'delete_route' => route('budget-monitoring.po.destroy', $po),
                'delete_number' => $po->po_number,
                'edit_data' => [
                    'id' => $po->id,
                    'program_name' => $po->program_name,
                    'vendor' => $po->vendor,
                    'po_value' => $po->po_value,
                    'commitment_value' => $po->commitment_value,
                    'po_date' => $po->po_date?->format('Y-m-d'),
                    'status' => $po->status,
                    'description' => $po->description,
                    'attachments' => $po->attachment_list,
                    'proofs' => $po->attachment_list,
                    'file_urls' => collect($po->attachment_list)->keys()->map(fn ($idx) => route('budget-monitoring.files.show', ['type' => 'po', 'id' => $po->id, 'index' => $idx]))->all(),
                ]
            ]);
        }

        // 2. Realisasi Pembayaran PO
        foreach ($wbs->poPayments as $pay) {
            $activities->push((object) [
                'type' => 'PAYMENT',
                'type_label' => 'Pembayaran PO',
                'badge_class' => 'bg-info-subtle text-info border border-info-subtle',
                'id' => $pay->id,
                'item' => $pay,
                'date' => $pay->payment_date,
                'program' => 'Pembayaran ' . ($pay->po?->po_number ?? 'PO') . ': ' . ($pay->po?->program_name ?? 'Pengadaan'),
                'identifier' => $pay->payment_number,
                'vendor' => $pay->po?->vendor,
                'po_value' => null,
                'commitment_value' => null,
                'po_realization' => (float) $pay->amount,
                'non_po_realization' => null,
                'description' => ($pay->reference_number ? "Ref: {$pay->reference_number}. " : '') . ($pay->description ?? 'Pembayaran bertahap'),
                'status' => 'Dibayar',
                'attachment' => $pay->proof_path,
                'proofs' => $pay->proof_list,
                'can_delete' => true,
                'delete_route' => route('budget-monitoring.po-payment.destroy', $pay),
                'delete_number' => $pay->payment_number,
                'edit_data' => null
            ]);
        }

        // 3. Realisasi Non-PO
        foreach ($wbs->nonPos as $npo) {
            $activities->push((object) [
                'type' => 'NON_PO',
                'type_label' => 'Realisasi Non-PO',
                'badge_class' => 'bg-warning-subtle text-dark border border-warning-subtle',
                'id' => $npo->id,
                'item' => $npo,
                'date' => $npo->transaction_date,
                'program' => $npo->program_name,
                'identifier' => $npo->transaction_number,
                'vendor' => $npo->recipient_vendor,
                'po_value' => null,
                'commitment_value' => null,
                'po_realization' => null,
                'non_po_realization' => (float) $npo->amount,
                'description' => ($npo->recipient_vendor ? "Penerima: {$npo->recipient_vendor}. " : '') . ($npo->description ?? '-'),
                'status' => 'Selesai',
                'attachment' => $npo->proof_path,
                'proofs' => $npo->proof_list,
                'can_delete' => true,
                'delete_route' => route('budget-monitoring.non-po.destroy', $npo),
                'delete_number' => $npo->transaction_number,
                'edit_data' => [
                    'id' => $npo->id,
                    'program_name' => $npo->program_name,
                    'transaction_date' => $npo->transaction_date?->format('Y-m-d'),
                    'amount' => $npo->amount,
                    'recipient_vendor' => $npo->recipient_vendor,
                    'description' => $npo->description,
                    'proofs' => $npo->proof_list,
                    'file_urls' => collect($npo->proof_list)->keys()->map(fn ($idx) => route('budget-monitoring.files.show', ['type' => 'non-po', 'id' => $npo->id, 'index' => $idx]))->all(),
                ]
            ]);
        }

        // 4. Pengalihan Keluar
        foreach ($wbs->transfersOut as $to) {
            $activities->push((object) [
                'type' => 'TRANSFER_OUT',
                'type_label' => 'Pengalihan Keluar',
                'badge_class' => 'bg-danger-subtle text-danger border border-danger-subtle',
                'id' => $to->id,
                'item' => $to,
                'date' => $to->transfer_date,
                'program' => "Pengalihan Dana Keluar ke WBS " . ($to->destinationWbs?->wbs_number ?? '-'),
                'identifier' => $to->transfer_number,
                'vendor' => null,
                'po_value' => null,
                'commitment_value' => null,
                'po_realization' => null,
                'non_po_realization' => null,
                'transfer_amount' => -(float) $to->amount,
                'description' => "Tujuan: WBS {$to->destinationWbs?->wbs_number} ({$to->destinationWbs?->wbs_name}). Alasan: {$to->reason}",
                'status' => 'Disetujui',
                'attachment' => null,
                'can_delete' => true,
                'delete_route' => route('budget-monitoring.transfers.destroy', $to),
                'delete_number' => $to->transfer_number,
                'edit_data' => null
            ]);
        }

        // 5. Pengalihan Masuk
        foreach ($wbs->transfersIn as $ti) {
            $activities->push((object) [
                'type' => 'TRANSFER_IN',
                'type_label' => 'Pengalihan Masuk',
                'badge_class' => 'bg-success-subtle text-success border border-success-subtle',
                'id' => $ti->id,
                'item' => $ti,
                'date' => $ti->transfer_date,
                'program' => "Penerimaan Dana Masuk dari WBS " . ($ti->sourceWbs?->wbs_number ?? '-'),
                'identifier' => $ti->transfer_number,
                'vendor' => null,
                'po_value' => null,
                'commitment_value' => null,
                'po_realization' => null,
                'non_po_realization' => null,
                'transfer_amount' => (float) $ti->amount,
                'description' => "Sumber: WBS {$ti->sourceWbs?->wbs_number} ({$ti->sourceWbs?->wbs_name}). Alasan: {$ti->reason}",
                'status' => 'Disetujui',
                'attachment' => null,
                'can_delete' => true,
                'delete_route' => route('budget-monitoring.transfers.destroy', $ti),
                'delete_number' => $ti->transfer_number,
                'edit_data' => null
            ]);
        }

        // Urutkan berdasarkan tanggal terbaru
        $activities = $activities->sortByDesc(function ($act) {
            return $act->date ? $act->date->format('Y-m-d') : '1970-01-01';
        })->values();

        // Total jumlah seluruh aktivitas tanpa filter
        $allActivitiesCount = $activities->count();

        // Hitung jumlah aktivitas per jenis untuk indikator pada filter
        $typeCounts = [
            'all' => $allActivitiesCount,
            'PO' => $wbs->pos->count(),
            'PAYMENT' => $wbs->poPayments->count(),
            'NON_PO' => $wbs->nonPos->count(),
            'TRANSFER_IN' => $wbs->transfersIn->count(),
            'TRANSFER_OUT' => $wbs->transfersOut->count(),
        ];

        // Filter aktivitas berdasarkan jenis transaksi
        $rawFilterType = trim((string) $request->input('filter_type', $request->input('type', 'all')));
        $lookupKey = strtoupper($rawFilterType);

        $typeMap = [
            'ALL' => 'all',
            'SEMUA' => 'all',
            'SEMUA TRANSAKSI' => 'all',
            'PO' => 'PO',
            'PENGADAAN PO' => 'PO',
            'PENGADAAN_PO' => 'PO',
            'PAYMENT' => 'PAYMENT',
            'PO EXPENSES' => 'PAYMENT',
            'PO_EXPENSES' => 'PAYMENT',
            'PEMBAYARAN PO' => 'PAYMENT',
            'PEMBAYARAN_PO' => 'PAYMENT',
            'NON_PO' => 'NON_PO',
            'NON-PO' => 'NON_PO',
            'NON-PO EXPENSES' => 'NON_PO',
            'NON_PO_EXPENSES' => 'NON_PO',
            'NON PO EXPENSES' => 'NON_PO',
            'REALISASI NON-PO' => 'NON_PO',
            'REALISASI_NON_PO' => 'NON_PO',
            'REALISASI NON PO' => 'NON_PO',
            'TRANSFER_IN' => 'TRANSFER_IN',
            'PENGALIHAN MASUK' => 'TRANSFER_IN',
            'PENGALIHAN_MASUK' => 'TRANSFER_IN',
            'PENGALIHAN ANGGARAN MASUK' => 'TRANSFER_IN',
            'TRANSFER_OUT' => 'TRANSFER_OUT',
            'PENGALIHAN KELUAR' => 'TRANSFER_OUT',
            'PENGALIHAN_KELUAR' => 'TRANSFER_OUT',
            'PENGALIHAN ANGGARAN KELUAR' => 'TRANSFER_OUT',
        ];

        $filterType = $typeMap[$lookupKey] ?? ($rawFilterType ?: 'all');

        $typeLabels = [
            'all' => 'Semua Transaksi',
            'PO' => 'Pengadaan PO',
            'PAYMENT' => 'PO Expenses',
            'NON_PO' => 'Non-PO Expenses',
            'TRANSFER_IN' => 'Pengalihan Anggaran Masuk',
            'TRANSFER_OUT' => 'Pengalihan Anggaran Keluar',
        ];
        $filterTypeLabel = $typeLabels[$filterType] ?? 'Semua Transaksi';

        if ($filterType !== 'all') {
            $activities = $activities->filter(function ($act) use ($filterType) {
                return $act->type === $filterType;
            })->values();
        }

        // Pencarian jika ada parameter search
        $searchKeyword = trim((string) $request->input('search', ''));
        if ($searchKeyword !== '') {
            $needle = mb_strtolower($searchKeyword);
            $activities = $activities->filter(function ($act) use ($needle) {
                return str_contains(mb_strtolower($act->program ?? ''), $needle)
                    || str_contains(mb_strtolower($act->identifier ?? ''), $needle)
                    || str_contains(mb_strtolower($act->vendor ?? ''), $needle)
                    || str_contains(mb_strtolower($act->description ?? ''), $needle);
            })->values();
        }

        // Riwayat Pengalihan Anggaran (Masuk & Keluar) dengan Keterangan Naratif Dinamis
        $transfersHistory = collect();

        // 1. Pengalihan Keluar (WBS ini sebagai WBS Asal)
        foreach ($wbs->transfersOut as $to) {
            $destNum = $to->destinationWbs?->wbs_number ?? 'WBS Tujuan';
            $destName = $to->destinationWbs?->wbs_name ? " ({$to->destinationWbs->wbs_name})" : '';
            $dateFormatted = $to->transfer_date ? $to->transfer_date->translatedFormat('d F Y') : ($to->transfer_date ? $to->transfer_date->format('d/m/Y') : '-');
            $nominalFormatted = BudgetTransfer::formatRupiah($to->amount);

            $narrative = "Dana sebesar {$nominalFormatted} dialihkan dari WBS {$wbs->wbs_number} ke WBS {$destNum}{$destName} pada {$dateFormatted}" . ($to->reason ? " untuk {$to->reason}." : ".");

            $transfersHistory->push((object)[
                'id' => $to->id,
                'transfer_number' => $to->transfer_number,
                'transfer_date' => $to->transfer_date,
                'source_wbs_id' => $to->source_wbs_id,
                'source_wbs_number' => $wbs->wbs_number,
                'source_wbs_name' => $wbs->wbs_name,
                'destination_wbs_id' => $to->destination_wbs_id,
                'destination_wbs_number' => $destNum,
                'destination_wbs_name' => $to->destinationWbs?->wbs_name ?? '-',
                'amount' => (float) $to->amount,
                'type_direction' => 'KELUAR',
                'type_label' => 'Dana Keluar',
                'type_badge' => 'bg-danger-subtle text-danger border border-danger-subtle',
                'reason' => $to->reason,
                'description' => $to->description ?? '-',
                'narrative' => $narrative,
                'status' => $to->status ?? 'Disetujui',
                'delete_url' => route('budget-monitoring.transfers.destroy', $to),
            ]);
        }

        // 2. Pengalihan Masuk (WBS ini sebagai WBS Penerima)
        foreach ($wbs->transfersIn as $ti) {
            $srcNum = $ti->sourceWbs?->wbs_number ?? 'WBS Asal';
            $srcName = $ti->sourceWbs?->wbs_name ? " ({$ti->sourceWbs->wbs_name})" : '';
            $dateFormatted = $ti->transfer_date ? $ti->transfer_date->translatedFormat('d F Y') : ($ti->transfer_date ? $ti->transfer_date->format('d/m/Y') : '-');
            $nominalFormatted = BudgetTransfer::formatRupiah($ti->amount);

            $narrative = "Menerima pengalihan dana sebesar {$nominalFormatted} dari WBS {$srcNum}{$srcName} pada {$dateFormatted}" . ($ti->reason ? " (" . $ti->reason . ")." : ".");

            $transfersHistory->push((object)[
                'id' => $ti->id,
                'transfer_number' => $ti->transfer_number,
                'transfer_date' => $ti->transfer_date,
                'source_wbs_id' => $ti->source_wbs_id,
                'source_wbs_number' => $srcNum,
                'source_wbs_name' => $ti->sourceWbs?->wbs_name ?? '-',
                'destination_wbs_id' => $ti->destination_wbs_id,
                'destination_wbs_number' => $wbs->wbs_number,
                'destination_wbs_name' => $wbs->wbs_name,
                'amount' => (float) $ti->amount,
                'type_direction' => 'MASUK',
                'type_label' => 'Dana Masuk',
                'type_badge' => 'bg-success-subtle text-success border border-success-subtle',
                'reason' => $ti->reason,
                'description' => $ti->description ?? '-',
                'narrative' => $narrative,
                'status' => $ti->status ?? 'Disetujui',
                'delete_url' => route('budget-monitoring.transfers.destroy', $ti),
            ]);
        }

        $transfersHistory = $transfersHistory->sortByDesc(function ($t) {
            return $t->transfer_date ? $t->transfer_date->format('Y-m-d') : '1970-01-01';
        })->values();

        // 3. Riwayat Pembayaran PO Bertahap (Menghitung Realisasi Kumulatif & Sisa Komitmen)
        $paymentsHistory = collect();
        foreach ($wbs->pos as $po) {
            $cumulative = 0;
            $poVal = (float) ($po->po_value > 0 ? $po->po_value : $po->commitment_value);
            $sortedPayments = $po->payments->sortBy('payment_date');
            foreach ($sortedPayments as $pay) {
                $cumulative += (float) $pay->amount;
                $remCommitment = max(0, (float) ($poVal - $cumulative));
                $paymentsHistory->push((object)[
                    'id' => $pay->id,
                    'payment_number' => $pay->payment_number,
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                    'program_name' => $po->program_name,
                    'vendor' => $po->vendor ?? '-',
                    'po_commitment' => $poVal,
                    'payment_date' => $pay->payment_date,
                    'reference_number' => $pay->reference_number ?? '-',
                    'amount' => (float) $pay->amount,
                    'cumulative_amount' => $cumulative,
                    'remaining_commitment' => $remCommitment,
                    'description' => $pay->description ?? '-',
                    'proof_path' => $pay->proof_path,
                    'proofs' => $pay->proof_list,
                    'creator_name' => $pay->creator?->name ?? 'Admin',
                    'delete_url' => route('budget-monitoring.po-payment.destroy', $pay),
                ]);
            }
        }
        $paymentsHistory = $paymentsHistory->sortByDesc(function ($p) {
            return $p->payment_date ? $p->payment_date->format('Y-m-d') : '1970-01-01';
        })->values();

        $allWbs = BudgetWbs::where('is_active', true)->orderBy('wbs_number')->get();
        $nextPoNumber = BudgetPo::generateNextPoNumber();
        $nextPaymentNumber = BudgetPoPayment::generateNextPaymentNumber();
        $nextNonPoNumber = BudgetNonPo::generateNextTransactionNumber();
        $nextTransferNumber = BudgetTransfer::generateNextTransferNumber();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'wbs' => $wbs,
                'metrics' => $detailMetrics,
                'activities' => $activities,
                'payments' => $paymentsHistory,
                'transfers' => $transfersHistory,
                'filter_type' => $filterType,
                'filter_type_label' => $filterTypeLabel,
                'total_activities_count' => $allActivitiesCount,
                'filtered_activities_count' => $activities->count(),
            ]);
        }

        return view('data.budget-monitoring.show-wbs', compact(
            'wbs',
            'detailMetrics',
            'activities',
            'paymentsHistory',
            'transfersHistory',
            'allWbs',
            'nextPoNumber',
            'nextPaymentNumber',
            'nextNonPoNumber',
            'nextTransferNumber',
            'filterType',
            'filterTypeLabel',
            'typeCounts',
            'searchKeyword',
            'allActivitiesCount'
        ));
    }

    /**
     * Tambah Anggaran WBS Baru
     */
    public function storeWbs(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'wbs_number' => 'required|string|max:100|unique:budget_wbs,wbs_number',
            'wbs_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'initial_budget' => 'required|numeric|min:0',
            'fiscal_year' => 'required|integer|min:2020|max:2099',
        ]);

        $wbs = DB::transaction(function () use ($validated, $request) {
            $budget = (float) $validated['initial_budget'];

            $wbs = BudgetWbs::create([
                'wbs_number' => trim($validated['wbs_number']),
                'wbs_name' => trim($validated['wbs_name']),
                'description' => $validated['description'] ?? null,
                'initial_budget' => $budget,
                'current_budget' => $budget,
                'fiscal_year' => (int) $validated['fiscal_year'],
                'is_active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Jika ada anggaran awal, catat penyesuaian awal & transaksi
            if ($budget > 0) {
                $adj = BudgetAdjustment::create([
                    'adjustment_number' => BudgetAdjustment::generateNextAdjustmentNumber(),
                    'budget_wbs_id' => $wbs->id,
                    'adjustment_type' => 'PENETAPAN_AWAL',
                    'amount' => $budget,
                    'old_budget' => 0,
                    'new_budget' => $budget,
                    'adjustment_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                    'reason' => 'Penetapan Alokasi Anggaran Awal WBS',
                    'created_by' => Auth::id(),
                ]);

                BudgetTransaction::create([
                    'transaction_number' => BudgetTransaction::generateNextTransactionNumber(),
                    'transaction_type' => BudgetTransaction::TYPE_ADJUSTMENT,
                    'transaction_type_label' => 'Penetapan Anggaran Awal',
                    'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                    'budget_wbs_id' => $wbs->id,
                    'program_name' => $wbs->wbs_name,
                    'reference_number' => $adj->adjustment_number,
                    'amount' => $budget,
                    'description' => 'Penetapan Anggaran Awal WBS ' . $wbs->wbs_number,
                    'created_by' => Auth::id(),
                    'related_model_type' => BudgetAdjustment::class,
                    'related_model_id' => $adj->id,
                ]);
            }

            ActivityLog::record([
                'module' => 'Monitoring Anggaran',
                'action' => 'TAMBAH',
                'record_type' => BudgetWbs::class,
                'record_id' => $wbs->id,
                'description' => "Menambahkan Anggaran WBS baru: {$wbs->wbs_number} - {$wbs->wbs_name} (Nominal: Rp" . number_format($budget, 0, ',', '.') . ")",
            ]);

            return $wbs;
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Anggaran WBS berhasil ditambahkan.',
                'data' => $wbs,
            ]);
        }

        return redirect()->route('budget-monitoring.index', ['tab' => 'wbs'])
            ->with('success', "Anggaran WBS {$wbs->wbs_number} berhasil ditambahkan.");
    }

    /**
     * Ubah Informasi Dasar WBS
     */
    public function updateWbs(Request $request, BudgetWbs $wbs): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'wbs_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fiscal_year' => 'required|integer|min:2020|max:2099',
            'is_active' => 'nullable|boolean',
        ]);

        $oldName = $wbs->wbs_name;
        $wbs->update([
            'wbs_name' => trim($validated['wbs_name']),
            'description' => $validated['description'] ?? null,
            'fiscal_year' => (int) $validated['fiscal_year'],
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
            'updated_by' => Auth::id(),
        ]);

        ActivityLog::record([
            'module' => 'Monitoring Anggaran',
            'action' => 'UBAH',
            'record_type' => BudgetWbs::class,
            'record_id' => $wbs->id,
            'description' => "Memperbarui WBS {$wbs->wbs_number} ({$oldName})",
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data WBS berhasil diperbarui.',
                'data' => $wbs,
            ]);
        }

        return redirect()->route('budget-monitoring.index', ['tab' => 'wbs'])
            ->with('success', "Data WBS {$wbs->wbs_number} berhasil diperbarui.");
    }

    private function validationFailureResponse(Request $request, string $msg, int $status = 422): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $msg], $status);
        }

        return redirect()->back()->with('error', $msg);
    }

    private function deleteStoredBudgetFiles(array $paths): void
    {
        foreach ($paths as $fPath) {
            $clean = ltrim((string) $fPath, '/');
            if ($clean === '' || str_contains($clean, '..')) {
                continue;
            }
            foreach (['local', 'public'] as $diskName) {
                if (Storage::disk($diskName)->exists($clean)) {
                    Storage::disk($diskName)->delete($clean);
                }
            }
        }
    }

    /**
     * Sajikan dokumen bukti/lampiran Monitoring Anggaran dari private storage (fallback legacy public).
     */
    public function viewDocument(string $type, int $id, int $index): BinaryFileResponse
    {
        $paths = match ($type) {
            'po' => BudgetPo::findOrFail($id)->attachment_list,
            'payment' => BudgetPoPayment::findOrFail($id)->proof_list,
            'non-po' => BudgetNonPo::findOrFail($id)->proof_list,
            default => abort(404, 'Tipe dokumen tidak valid.'),
        };

        if (!isset($paths[$index])) {
            abort(404, 'Dokumen bukti tidak ditemukan.');
        }

        $filePath = ltrim((string) $paths[$index], '/');
        if ($filePath === '' || str_contains($filePath, '..')) {
            abort(404, 'Path dokumen tidak valid.');
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                $fullPath = $disk->path($filePath);
                $mime = $disk->mimeType($filePath) ?: 'application/octet-stream';
                $safeName = str_replace(['"', "\r", "\n", '\\', '/'], '_', basename($filePath));

                return response()->file($fullPath, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $safeName . '"',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404, 'File dokumen tidak ditemukan di penyimpanan.');
    }

    /**
     * Hapus WBS (Hanya jika belum memiliki transaksi keuangan terkait)
     */
    public function destroyWbs(Request $request, BudgetWbs $wbs): RedirectResponse|JsonResponse
    {
        $wbsNum = $wbs->wbs_number;

        try {
            DB::transaction(function () use ($wbs) {
                $lockedWbs = BudgetWbs::where('id', $wbs->id)->lockForUpdate()->firstOrFail();

                if (
                    $lockedWbs->pos()->exists() ||
                    $lockedWbs->nonPos()->exists() ||
                    $lockedWbs->poPayments()->exists() ||
                    $lockedWbs->transfersOut()->exists() ||
                    $lockedWbs->transfersIn()->exists() ||
                    $lockedWbs->adjustments()->exists()
                ) {
                    throw new \DomainException("WBS {$lockedWbs->wbs_number} tidak dapat dihapus karena masih memiliki data pengadaan PO, realisasi, atau riwayat transaksi terkait.");
                }

                BudgetTransaction::where('budget_wbs_id', $lockedWbs->id)->delete();
                $lockedWbs->delete();
            });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        ActivityLog::record([
            'module' => 'Monitoring Anggaran',
            'action' => 'HAPUS_WBS',
            'record_type' => BudgetWbs::class,
            'record_id' => $wbs->id,
            'description' => "Menghapus WBS {$wbsNum}",
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "WBS {$wbsNum} berhasil dihapus."]);
        }

        return redirect()->route('budget-monitoring.index')
            ->with('success', "WBS {$wbsNum} berhasil dihapus.");
    }

    /**
     * Penyesuaian Anggaran WBS (Tambah atau Kurang)
     */
    public function adjustBudget(Request $request, BudgetWbs $wbs): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:TAMBAH,KURANG',
            'amount' => 'required|numeric|min:1',
            'adjustment_date' => 'required|date',
            'reason' => 'required|string|max:500',
        ]);

        $amount = (float) $validated['amount'];
        $type = $validated['adjustment_type'];

        try {
            DB::transaction(function () use ($wbs, $type, $amount, $validated) {
                $lockedWbs = BudgetWbs::where('id', $wbs->id)->lockForUpdate()->firstOrFail();
                $oldBudget = (float) $lockedWbs->current_budget;
                $remainingBudget = (float) $lockedWbs->remaining_budget;

                // Validasi di dalam lock: pengurangan tidak boleh melebihi sisa anggaran WBS saat ini!
                if ($type === 'KURANG' && $amount > $remainingBudget) {
                    $maxReduce = BudgetWbs::formatRupiah($remainingBudget);
                    throw new \DomainException("Pengurangan anggaran sebesar " . BudgetWbs::formatRupiah($amount) . " melebihi sisa anggaran WBS yang tersedia ({$maxReduce}).");
                }

                $newBudget = ($type === 'TAMBAH') ? ($oldBudget + $amount) : ($oldBudget - $amount);

                $adj = BudgetAdjustment::create([
                    'adjustment_number' => BudgetAdjustment::generateNextAdjustmentNumber(),
                    'budget_wbs_id' => $lockedWbs->id,
                    'adjustment_type' => $type,
                    'amount' => $amount,
                    'old_budget' => $oldBudget,
                    'new_budget' => $newBudget,
                    'adjustment_date' => $validated['adjustment_date'],
                    'reason' => $validated['reason'],
                    'created_by' => Auth::id(),
                ]);

                $lockedWbs->update([
                    'current_budget' => $newBudget,
                    'updated_by' => Auth::id(),
                ]);

                BudgetTransaction::create([
                    'transaction_number' => BudgetTransaction::generateNextTransactionNumber(),
                    'transaction_type' => BudgetTransaction::TYPE_ADJUSTMENT,
                    'transaction_type_label' => ($type === 'TAMBAH' ? 'Penambahan Anggaran' : 'Pengurangan Anggaran'),
                    'transaction_date' => $validated['adjustment_date'],
                    'budget_wbs_id' => $lockedWbs->id,
                    'program_name' => $lockedWbs->wbs_name,
                    'reference_number' => $adj->adjustment_number,
                    'amount' => $amount,
                    'description' => "Penyesuaian Anggaran WBS: {$validated['reason']}",
                    'created_by' => Auth::id(),
                    'related_model_type' => BudgetAdjustment::class,
                    'related_model_id' => $adj->id,
                ]);

                ActivityLog::record([
                    'module' => 'Monitoring Anggaran',
                    'action' => 'PENYESUAIAN_ANGGARAN',
                    'record_type' => BudgetWbs::class,
                    'record_id' => $lockedWbs->id,
                    'description' => "Penyesuaian Anggaran WBS {$lockedWbs->wbs_number} ({$type}: Rp" . number_format($amount, 0, ',', '.') . "). Alasan: {$validated['reason']}",
                ]);
            });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Penyesuaian anggaran WBS berhasil disimpan.',
            ]);
        }

        return redirect()->route('budget-monitoring.index', ['tab' => 'wbs'])
            ->with('success', "Penyesuaian anggaran WBS {$wbs->wbs_number} berhasil disimpan.");
    }

    /**
     * Simpan Pengadaan / PO Baru
     */
    public function storePo(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'budget_wbs_id' => 'required|exists:budget_wbs,id',
            'po_number' => 'required|string|max:100|unique:budget_pos,po_number',
            'program_name' => 'required|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'po_value' => 'required|numeric|min:1',
            'commitment_value' => 'nullable|numeric|min:1',
            'po_date' => 'required|date',
            'status' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $poValue = (float) $validated['po_value'];
        $commitment = $poValue;
        $targetStatus = $request->input('status', 'Aktif') ?: 'Aktif';

        try {
            $po = DB::transaction(function () use ($validated, $request, $commitment, $poValue, $targetStatus) {
                $lockedWbs = BudgetWbs::where('id', $validated['budget_wbs_id'])->lockForUpdate()->firstOrFail();

                // Validasi di dalam lock: Komitmen PO aktif tidak boleh melebihi sisa anggaran WBS!
                if ($targetStatus !== 'Dibatalkan' && $commitment > $lockedWbs->remaining_budget) {
                    $sisaFmt = BudgetWbs::formatRupiah($lockedWbs->remaining_budget);
                    $poFmt = BudgetWbs::formatRupiah($poValue);
                    throw new \DomainException("Nilai Pengadaan PO ({$poFmt}) melebihi sisa anggaran WBS yang tersedia ({$sisaFmt}).");
                }

                $storedAttachments = [];
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        if ($file && $file->isValid()) {
                            $storedAttachments[] = $file->store('budget/po_attachments', 'local');
                        }
                    }
                }
                if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
                    $storedAttachments[] = $request->file('attachment')->store('budget/po_attachments', 'local');
                }

                $po = BudgetPo::create([
                    'budget_wbs_id' => $lockedWbs->id,
                    'po_number' => trim($validated['po_number']),
                    'program_name' => trim($validated['program_name']),
                    'vendor' => $validated['vendor'] ?? null,
                    'po_value' => $poValue,
                    'commitment_value' => $commitment,
                    'po_date' => $validated['po_date'],
                    'status' => $targetStatus,
                    'description' => $validated['description'] ?? null,
                    'attachment_path' => $storedAttachments[0] ?? null,
                    'attachment_paths' => !empty($storedAttachments) ? $storedAttachments : null,
                    'created_by' => Auth::id(),
                ]);

                // Catat transaksi komitmen PO
                BudgetTransaction::create([
                    'transaction_number' => BudgetTransaction::generateNextTransactionNumber(),
                    'transaction_type' => BudgetTransaction::TYPE_PO_COMMITMENT,
                    'transaction_type_label' => 'Pembuatan / Komitmen PO',
                    'transaction_date' => $validated['po_date'],
                    'budget_wbs_id' => $lockedWbs->id,
                    'program_name' => $po->program_name,
                    'reference_number' => $po->po_number,
                    'amount' => $commitment,
                    'description' => "Komitmen Pengadaan PO {$po->po_number} (Vendor: " . ($po->vendor ?? '-') . ")",
                    'created_by' => Auth::id(),
                    'related_model_type' => BudgetPo::class,
                    'related_model_id' => $po->id,
                ]);

                ActivityLog::record([
                    'module' => 'Monitoring Anggaran',
                    'action' => 'TAMBAH_PO',
                    'record_type' => BudgetPo::class,
                    'record_id' => $po->id,
                    'description' => "Mencatat Pengadaan PO Baru: {$po->po_number} - {$po->program_name} (Nilai: Rp" . number_format($poValue, 0, ',', '.') . ")",
                ]);

                return $po;
            });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data Pengadaan PO berhasil disimpan.',
                'data' => $po,
            ]);
        }

        return redirect()->route('budget-monitoring.index', ['tab' => 'po'])
            ->with('success', "Pengadaan PO {$po->po_number} berhasil dicatat.");
    }

    /**
     * Ubah Pengadaan / PO
     */
    public function updatePo(Request $request, BudgetPo $po): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'program_name' => 'required|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'po_value' => 'required|numeric|min:1',
            'commitment_value' => 'nullable|numeric|min:1',
            'po_date' => 'required|date',
            'status' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'deleted_attachments' => 'nullable|array',
            'deleted_attachments.*' => 'string',
        ]);

        $newPoValue = (float) $validated['po_value'];
        $toPhysicallyDelete = [];

        try {
            DB::transaction(function () use ($po, $validated, $request, $newPoValue, &$toPhysicallyDelete) {
                $lockedPo = BudgetPo::where('id', $po->id)->lockForUpdate()->firstOrFail();
                $lockedWbs = BudgetWbs::where('id', $lockedPo->budget_wbs_id)->lockForUpdate()->firstOrFail();

                $oldStatus = $lockedPo->status ?? 'Aktif';
                $newStatus = $request->input('status', $oldStatus) ?: 'Aktif';
                $oldEffectiveVal = $oldStatus === 'Dibatalkan'
                    ? 0.0
                    : (float) ($lockedPo->po_value > 0 ? $lockedPo->po_value : $lockedPo->commitment_value);
                $newEffectiveVal = $newStatus === 'Dibatalkan' ? 0.0 : $newPoValue;
                $commitmentDelta = $newEffectiveVal - $oldEffectiveVal;

                if ($newStatus === 'Dibatalkan' && $lockedPo->total_payment > 0) {
                    throw new \DomainException("PO {$lockedPo->po_number} tidak dapat dibatalkan karena sudah memiliki riwayat pembayaran realisasi.");
                }

                if ($commitmentDelta > 0 && $commitmentDelta > $lockedWbs->remaining_budget) {
                    throw new \DomainException("Penambahan nilai PO melebihi sisa anggaran WBS yang tersedia.");
                }

                if ($newStatus !== 'Dibatalkan' && $newPoValue < $lockedPo->total_payment) {
                    $totFmt = BudgetPo::formatRupiah($lockedPo->total_payment);
                    throw new \DomainException("Nilai pengadaan PO tidak boleh lebih kecil dari total pembayaran yang sudah direalisasikan ({$totFmt}).");
                }

                $currentAttachments = $lockedPo->attachment_list;

                // 1. Tangani penghapusan dokumen yang ditandai secara eksplisit oleh pengguna
                if ($request->filled('deleted_attachments')) {
                    $deletedList = (array) $request->input('deleted_attachments');
                    $toPhysicallyDelete = array_intersect($currentAttachments, $deletedList);
                    $currentAttachments = array_values(array_diff($currentAttachments, $deletedList));
                }

                // 2. Tambahkan dokumen baru yang diunggah ke private storage
                $newStored = [];
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        if ($file && $file->isValid()) {
                            $newStored[] = $file->store('budget/po_attachments', 'local');
                        }
                    }
                }
                if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
                    $newStored[] = $request->file('attachment')->store('budget/po_attachments', 'local');
                }

                $currentAttachments = array_values(array_merge($currentAttachments, $newStored));

                $lockedPo->update([
                    'program_name' => trim($validated['program_name']),
                    'vendor' => $validated['vendor'] ?? null,
                    'po_value' => $newPoValue,
                    'commitment_value' => $newPoValue,
                    'po_date' => $validated['po_date'],
                    'status' => $newStatus,
                    'description' => $validated['description'] ?? null,
                    'attachment_path' => $currentAttachments[0] ?? null,
                    'attachment_paths' => !empty($currentAttachments) ? $currentAttachments : null,
                ]);

                // Update catatan transaksi komitmen PO
                BudgetTransaction::where('related_model_type', BudgetPo::class)
                    ->where('related_model_id', $lockedPo->id)
                    ->where('transaction_type', BudgetTransaction::TYPE_PO_COMMITMENT)
                    ->update([
                        'amount' => $newPoValue,
                        'program_name' => $lockedPo->program_name,
                        'transaction_date' => $validated['po_date'],
                    ]);

                ActivityLog::record([
                    'module' => 'Monitoring Anggaran',
                    'action' => 'UBAH_PO',
                    'record_type' => BudgetPo::class,
                    'record_id' => $lockedPo->id,
                    'description' => "Memperbarui Pengadaan PO {$lockedPo->po_number}",
                ]);
            });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        // Hapus file fisik HANYA setelah DB transaction berhasil commit
        $this->deleteStoredBudgetFiles($toPhysicallyDelete);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'PO berhasil diperbarui.']);
        }

        return redirect()->route('budget-monitoring.index', ['tab' => 'po'])
            ->with('success', "PO {$po->po_number} berhasil diperbarui.");
    }

    /**
     * Hapus PO (Hanya jika belum ada pembayaran)
     */
    public function destroyPo(Request $request, BudgetPo $po): RedirectResponse|JsonResponse
    {
        $poNum = $po->po_number;
        $filesToDelete = [];

        try {
            DB::transaction(function () use ($po, &$filesToDelete) {
                $lockedPo = BudgetPo::where('id', $po->id)->lockForUpdate()->firstOrFail();

                if ($lockedPo->payments()->exists()) {
                    throw new \DomainException("PO {$lockedPo->po_number} tidak dapat dihapus karena sudah memiliki riwayat pembayaran realisasi.");
                }

                BudgetTransaction::where('related_model_type', BudgetPo::class)
                    ->where('related_model_id', $lockedPo->id)
                    ->delete();

                $filesToDelete = $lockedPo->attachment_list;
                $lockedPo->delete();
            });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        $this->deleteStoredBudgetFiles($filesToDelete);

        ActivityLog::record([
            'module' => 'Monitoring Anggaran',
            'action' => 'HAPUS_PO',
            'record_type' => BudgetPo::class,
            'record_id' => $po->id,
            'description' => "Menghapus Pengadaan PO {$poNum}",
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "PO {$poNum} berhasil dihapus."]);
        }

        return redirect()->back()
            ->with('success', "PO {$poNum} berhasil dihapus.");
    }

    /**
     * Simpan Realisasi Pembayaran PO Bertahap
     */
    public function storePoPayment(Request $request, BudgetPo $po): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'payment_number' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'proofs' => 'nullable|array',
            'proofs.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $amount = (float) $validated['amount'];

        try {
            $payment = DB::transaction(function () use ($validated, $request, $po, $amount) {
                $lockedPo = BudgetPo::where('id', $po->id)->lockForUpdate()->firstOrFail();

                if ($lockedPo->status === 'Dibatalkan') {
                    throw new \DomainException("Pembayaran tidak dapat dicatat karena PO {$lockedPo->po_number} berstatus Dibatalkan.");
                }

                $sisaKomitmen = (float) $lockedPo->remaining_commitment;

                // Validasi di dalam lock: Pembayaran kumulatif tidak boleh melebihi sisa komitmen PO!
                if ($amount > $sisaKomitmen) {
                    $sisaFmt = BudgetPo::formatRupiah($sisaKomitmen);
                    throw new \DomainException("Jumlah pembayaran (" . BudgetPo::formatRupiah($amount) . ") melebihi sisa komitmen PO yang tersedia ({$sisaFmt}).");
                }

                $storedProofs = [];
                if ($request->hasFile('proofs')) {
                    foreach ($request->file('proofs') as $file) {
                        if ($file && $file->isValid()) {
                            $storedProofs[] = $file->store('budget/payment_proofs', 'local');
                        }
                    }
                }
                if ($request->hasFile('proof') && $request->file('proof')->isValid()) {
                    $storedProofs[] = $request->file('proof')->store('budget/payment_proofs', 'local');
                }

                // Gunakan Nomor SA/GR yang diisi pengguna, atau fallback ke nomor sistem
                $paymentNumber = !empty($validated['payment_number'])
                    ? trim($validated['payment_number'])
                    : BudgetPoPayment::generateNextPaymentNumber();

                $payment = BudgetPoPayment::create([
                    'budget_po_id' => $lockedPo->id,
                    'budget_wbs_id' => $lockedPo->budget_wbs_id,
                    'payment_number' => $paymentNumber,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $amount,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'proof_path' => $storedProofs[0] ?? null,
                    'proof_paths' => !empty($storedProofs) ? $storedProofs : null,
                    'created_by' => Auth::id(),
                ]);

                // Catat transaksi pembayaran PO
                BudgetTransaction::create([
                    'transaction_number' => BudgetTransaction::generateNextTransactionNumber(),
                    'transaction_type' => BudgetTransaction::TYPE_PO_PAYMENT,
                    'transaction_type_label' => 'Pembayaran PO',
                    'transaction_date' => $validated['payment_date'],
                    'budget_wbs_id' => $lockedPo->budget_wbs_id,
                    'program_name' => $lockedPo->program_name,
                    'reference_number' => $paymentNumber,
                    'amount' => $amount,
                    'description' => "Pembayaran PO {$lockedPo->po_number} (SA/GR: {$paymentNumber}): " . ($validated['description'] ?? 'Pembayaran Bertahap'),
                    'created_by' => Auth::id(),
                    'related_model_type' => BudgetPoPayment::class,
                    'related_model_id' => $payment->id,
                ]);

                ActivityLog::record([
                    'module' => 'Monitoring Anggaran',
                    'action' => 'BAYAR_PO',
                    'record_type' => BudgetPoPayment::class,
                    'record_id' => $payment->id,
                    'description' => "Mencatat Pembayaran PO {$lockedPo->po_number} (SA/GR: {$paymentNumber}) sebesar Rp" . number_format($amount, 0, ',', '.'),
                ]);

                return $payment;
            });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran PO berhasil dicatat.',
                'data' => $payment,
            ]);
        }

        return redirect()->back()
            ->with('success', "Pembayaran PO {$po->po_number} sebesar " . BudgetPo::formatRupiah($amount) . " berhasil dicatat.");
    }

    /**
     * Hapus Pembayaran PO
     */
    public function destroyPoPayment(Request $request, BudgetPoPayment $payment): RedirectResponse|JsonResponse
    {
        $num = $payment->payment_number;
        $poNum = $payment->po?->po_number ?? '-';
        $filesToDelete = [];

        DB::transaction(function () use ($payment, &$filesToDelete) {
            $lockedPayment = BudgetPoPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            BudgetTransaction::where('related_model_type', BudgetPoPayment::class)
                ->where('related_model_id', $lockedPayment->id)
                ->delete();

            $filesToDelete = $lockedPayment->proof_list;
            $lockedPayment->delete();
        });

        $this->deleteStoredBudgetFiles($filesToDelete);

        ActivityLog::record([
            'module' => 'Monitoring Anggaran',
            'action' => 'HAPUS_PEMBAYARAN_PO',
            'record_type' => BudgetPoPayment::class,
            'record_id' => $payment->id,
            'description' => "Menghapus Pembayaran PO {$poNum} ({$num})",
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Pembayaran {$num} berhasil dihapus."]);
        }

        return redirect()->back()
            ->with('success', "Pembayaran {$num} berhasil dihapus.");
    }

    /**
     * Simpan Realisasi Non-PO
     */
    public function storeNonPo(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'budget_wbs_id' => 'required|exists:budget_wbs,id',
            'transaction_number' => 'nullable|string|max:100',
            'program_name' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'recipient_vendor' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'proofs' => 'nullable|array',
            'proofs.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $amount = (float) $validated['amount'];

        try {
            $nonPo = DB::transaction(function () use ($validated, $request, $amount) {
                $lockedWbs = BudgetWbs::where('id', $validated['budget_wbs_id'])->lockForUpdate()->firstOrFail();

                // Validasi di dalam lock: Realisasi Non-PO tidak boleh melebihi sisa anggaran WBS!
                if ($amount > $lockedWbs->remaining_budget) {
                    $sisaFmt = BudgetWbs::formatRupiah($lockedWbs->remaining_budget);
                    throw new \DomainException("Jumlah pengeluaran Non-PO (" . BudgetWbs::formatRupiah($amount) . ") melebihi sisa anggaran WBS yang tersedia ({$sisaFmt}).");
                }

                $storedProofs = [];
                if ($request->hasFile('proofs')) {
                    foreach ($request->file('proofs') as $file) {
                        if ($file && $file->isValid()) {
                            $storedProofs[] = $file->store('budget/non_po_proofs', 'local');
                        }
                    }
                }
                if ($request->hasFile('proof') && $request->file('proof')->isValid()) {
                    $storedProofs[] = $request->file('proof')->store('budget/non_po_proofs', 'local');
                }

                $trxNumber = !empty($validated['transaction_number'])
                    ? trim($validated['transaction_number'])
                    : BudgetNonPo::generateNextTransactionNumber();

                $nonPo = BudgetNonPo::create([
                    'budget_wbs_id' => $lockedWbs->id,
                    'transaction_number' => $trxNumber,
                    'program_name' => trim($validated['program_name']),
                    'transaction_date' => $validated['transaction_date'],
                    'amount' => $amount,
                    'recipient_vendor' => $validated['recipient_vendor'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'proof_path' => $storedProofs[0] ?? null,
                    'proof_paths' => !empty($storedProofs) ? $storedProofs : null,
                    'created_by' => Auth::id(),
                ]);

                // Catat transaksi pengeluaran Non-PO
                BudgetTransaction::create([
                    'transaction_number' => BudgetTransaction::generateNextTransactionNumber(),
                    'transaction_type' => BudgetTransaction::TYPE_NON_PO,
                    'transaction_type_label' => 'Realisasi Non-PO',
                    'transaction_date' => $validated['transaction_date'],
                    'budget_wbs_id' => $lockedWbs->id,
                    'program_name' => $nonPo->program_name,
                    'reference_number' => $trxNumber,
                    'amount' => $amount,
                    'description' => "Pengeluaran Non-PO: " . ($nonPo->description ?: $nonPo->program_name),
                    'created_by' => Auth::id(),
                    'related_model_type' => BudgetNonPo::class,
                    'related_model_id' => $nonPo->id,
                ]);

                ActivityLog::record([
                    'module' => 'Monitoring Anggaran',
                    'action' => 'TAMBAH_NON_PO',
                    'record_type' => BudgetNonPo::class,
                    'record_id' => $nonPo->id,
                    'description' => "Mencatat Realisasi Non-PO ({$trxNumber}) pada WBS {$lockedWbs->wbs_number} sebesar Rp" . number_format($amount, 0, ',', '.'),
                ]);

                return $nonPo;
            });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Realisasi Non-PO berhasil dicatat.',
                'data' => $nonPo,
            ]);
        }

        return redirect()->route('budget-monitoring.index', ['tab' => 'realisasi-non-po'])
            ->with('success', "Realisasi Non-PO {$nonPo->transaction_number} sebesar " . BudgetWbs::formatRupiah($amount) . " berhasil dicatat.");
    }

    /**
     * Ubah Realisasi Non-PO
     */
    public function updateNonPo(Request $request, BudgetNonPo $nonPo): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'program_name' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'recipient_vendor' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'proofs' => 'nullable|array',
            'proofs.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'deleted_proofs' => 'nullable|array',
            'deleted_proofs.*' => 'string',
        ]);

        $newAmount = (float) $validated['amount'];
        $toPhysicallyDelete = [];

        try {
            DB::transaction(function () use ($nonPo, $validated, $request, $newAmount, &$toPhysicallyDelete) {
                $lockedNonPo = BudgetNonPo::where('id', $nonPo->id)->lockForUpdate()->firstOrFail();
                $lockedWbs = BudgetWbs::where('id', $lockedNonPo->budget_wbs_id)->lockForUpdate()->firstOrFail();
                $diff = $newAmount - (float) $lockedNonPo->amount;

                if ($diff > 0 && $diff > $lockedWbs->remaining_budget) {
                    throw new \DomainException("Penambahan nominal pengeluaran melebihi sisa anggaran WBS yang tersedia.");
                }

                $storedProofs = $lockedNonPo->proof_list;
                $changed = false;

                // 1. Hapus bukti yang ditandai secara eksplisit oleh pengguna
                if ($request->filled('deleted_proofs')) {
                    $deletedList = (array) $request->input('deleted_proofs');
                    $toPhysicallyDelete = array_intersect($storedProofs, $deletedList);
                    $storedProofs = array_values(array_diff($storedProofs, $deletedList));
                    $changed = true;
                }

                // 2. Tambahkan bukti baru ke private storage
                if ($request->hasFile('proofs')) {
                    foreach ($request->file('proofs') as $file) {
                        if ($file && $file->isValid()) {
                            $storedProofs[] = $file->store('budget/non_po_proofs', 'local');
                            $changed = true;
                        }
                    }
                }
                if ($request->hasFile('proof') && $request->file('proof')->isValid()) {
                    $storedProofs[] = $request->file('proof')->store('budget/non_po_proofs', 'local');
                    $changed = true;
                }

                $updateData = [
                    'program_name' => trim($validated['program_name']),
                    'transaction_date' => $validated['transaction_date'],
                    'amount' => $newAmount,
                    'recipient_vendor' => $validated['recipient_vendor'] ?? null,
                    'description' => $validated['description'] ?? null,
                ];

                if ($changed) {
                    $updateData['proof_path'] = $storedProofs[0] ?? null;
                    $updateData['proof_paths'] = !empty($storedProofs) ? $storedProofs : null;
                }

                $lockedNonPo->update($updateData);

                BudgetTransaction::where('related_model_type', BudgetNonPo::class)
                    ->where('related_model_id', $lockedNonPo->id)
                    ->update([
                        'amount' => $newAmount,
                        'program_name' => $lockedNonPo->program_name,
                        'transaction_date' => $validated['transaction_date'],
                    ]);

                ActivityLog::record([
                    'module' => 'Monitoring Anggaran',
                    'action' => 'UBAH_NON_PO',
                    'record_type' => BudgetNonPo::class,
                    'record_id' => $lockedNonPo->id,
                    'description' => "Memperbarui Realisasi Non-PO {$lockedNonPo->transaction_number}",
                ]);
            });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        // Hapus file fisik HANYA setelah DB transaction berhasil commit
        $this->deleteStoredBudgetFiles($toPhysicallyDelete);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Realisasi Non-PO berhasil diperbarui.']);
        }

        return redirect()->route('budget-monitoring.index', ['tab' => 'realisasi-non-po'])
            ->with('success', "Realisasi Non-PO {$nonPo->transaction_number} berhasil diperbarui.");
    }

    /**
     * Hapus Realisasi Non-PO
     */
    public function destroyNonPo(Request $request, BudgetNonPo $nonPo): RedirectResponse|JsonResponse
    {
        $num = $nonPo->transaction_number;
        $filesToDelete = [];

        DB::transaction(function () use ($nonPo, &$filesToDelete) {
            $lockedNonPo = BudgetNonPo::where('id', $nonPo->id)->lockForUpdate()->firstOrFail();

            BudgetTransaction::where('related_model_type', BudgetNonPo::class)
                ->where('related_model_id', $lockedNonPo->id)
                ->delete();

            $filesToDelete = $lockedNonPo->proof_list;
            $lockedNonPo->delete();
        });

        $this->deleteStoredBudgetFiles($filesToDelete);

        ActivityLog::record([
            'module' => 'Monitoring Anggaran',
            'action' => 'HAPUS_NON_PO',
            'record_type' => BudgetNonPo::class,
            'record_id' => $nonPo->id,
            'description' => "Menghapus Realisasi Non-PO {$num}",
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Realisasi Non-PO {$num} berhasil dihapus."]);
        }

        return redirect()->back()
            ->with('success', "Realisasi Non-PO {$num} berhasil dihapus.");
    }

    /**
     * Hapus / Batalkan Pengalihan Anggaran Antar-WBS
     */
    public function destroyTransfer(Request $request, BudgetTransfer $transfer): RedirectResponse|JsonResponse
    {
        $amount = (float) $transfer->amount;
        $trfNum = $transfer->transfer_number;

        try {
            DB::transaction(function () use ($transfer, $amount, $trfNum) {
                $lockedTransfer = BudgetTransfer::where('id', $transfer->id)->lockForUpdate()->firstOrFail();
                $wbsIds = array_values(array_unique(array_filter([$lockedTransfer->source_wbs_id, $lockedTransfer->destination_wbs_id])));
                sort($wbsIds);
                $lockedWbsMap = BudgetWbs::whereIn('id', $wbsIds)->lockForUpdate()->get()->keyBy('id');

                $destWbs = $lockedWbsMap->get($lockedTransfer->destination_wbs_id);
                $sourceWbs = $lockedWbsMap->get($lockedTransfer->source_wbs_id);

                // Validasi di dalam lock: WBS Penerima harus memiliki sisa anggaran yang mencukupi!
                if ($destWbs && $destWbs->remaining_budget < $amount) {
                    $sisaFmt = BudgetWbs::formatRupiah($destWbs->remaining_budget);
                    $amtFmt = BudgetWbs::formatRupiah($amount);
                    throw new \DomainException("Pengalihan {$trfNum} tidak dapat dibatalkan karena WBS Penerima ({$destWbs->wbs_number}) telah menggunakan anggarannya (sisa anggaran WBS penerima: {$sisaFmt}, diperlukan: {$amtFmt}).");
                }

                // Balikkan saldo anggaran kedua WBS
                if ($sourceWbs) {
                    $sourceWbs->increment('current_budget', $amount);
                }
                if ($destWbs) {
                    $destWbs->decrement('current_budget', $amount);
                }

                // Hapus transaksi keuangan terkait pengalihan ini
                BudgetTransaction::where('related_model_type', BudgetTransfer::class)
                    ->where('related_model_id', $lockedTransfer->id)
                    ->delete();

                $lockedTransfer->delete();

                ActivityLog::record([
                    'module' => 'Monitoring Anggaran',
                    'action' => 'BATAL_PENGALIHAN',
                    'record_type' => BudgetTransfer::class,
                    'record_id' => $lockedTransfer->id,
                    'description' => "Membatalkan Pengalihan Anggaran {$trfNum} sebesar Rp" . number_format($amount, 0, ',', '.') . " dan mengembalikan dana ke WBS Asal",
                ]);
            });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Pengalihan anggaran {$trfNum} berhasil dibatalkan dan saldo dikembalikan."]);
        }

        return redirect()->back()
            ->with('success', "Pengalihan anggaran {$trfNum} berhasil dibatalkan dan saldo dikembalikan.");
    }

    /**
     * Pengalihan Anggaran Antar-WBS
     */
    public function storeTransfer(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'source_wbs_id' => 'required|exists:budget_wbs,id|different:destination_wbs_id',
            'destination_wbs_id' => 'required|exists:budget_wbs,id',
            'amount' => 'required|numeric|min:1',
            'transfer_date' => 'required|date',
            'reason' => 'required|string|max:500',
            'description' => 'nullable|string',
        ]);

        $amount = (float) $validated['amount'];

        try {
            $transfer = DB::transaction(function () use ($validated, $amount) {
                $wbsIds = [(int) $validated['source_wbs_id'], (int) $validated['destination_wbs_id']];
                sort($wbsIds);
                $lockedWbsMap = BudgetWbs::whereIn('id', $wbsIds)->lockForUpdate()->get()->keyBy('id');

                $sourceWbs = $lockedWbsMap->get((int) $validated['source_wbs_id']);
                $destWbs = $lockedWbsMap->get((int) $validated['destination_wbs_id']);

                if (!$sourceWbs || !$destWbs) {
                    throw new \DomainException('Data WBS asal atau tujuan tidak ditemukan.');
                }

                // Validasi di dalam lock: WBS Asal harus memiliki sisa anggaran yang mencukupi!
                if ($amount > $sourceWbs->remaining_budget) {
                    $sisaFmt = BudgetWbs::formatRupiah($sourceWbs->remaining_budget);
                    throw new \DomainException("Nominal pengalihan (" . BudgetWbs::formatRupiah($amount) . ") melebihi sisa anggaran WBS Asal {$sourceWbs->wbs_number} yang tersedia ({$sisaFmt}).");
                }

                $transferNumber = BudgetTransfer::generateNextTransferNumber();

                $transfer = BudgetTransfer::create([
                    'transfer_number' => $transferNumber,
                    'source_wbs_id' => $sourceWbs->id,
                    'destination_wbs_id' => $destWbs->id,
                    'amount' => $amount,
                    'transfer_date' => $validated['transfer_date'],
                    'reason' => $validated['reason'],
                    'description' => $validated['description'] ?? null,
                    'status' => 'Disetujui',
                    'created_by' => Auth::id(),
                ]);

                // Sesuaikan current_budget kedua WBS
                $sourceWbs->decrement('current_budget', $amount);
                $destWbs->increment('current_budget', $amount);

                // 1. Catat Transaksi Pengalihan Keluar pada WBS Asal
                BudgetTransaction::create([
                    'transaction_number' => BudgetTransaction::generateNextTransactionNumber(),
                    'transaction_type' => BudgetTransaction::TYPE_TRANSFER_OUT,
                    'transaction_type_label' => 'Pengalihan Anggaran (Keluar)',
                    'transaction_date' => $validated['transfer_date'],
                    'budget_wbs_id' => $sourceWbs->id,
                    'program_name' => $sourceWbs->wbs_name,
                    'reference_number' => $transferNumber,
                    'amount' => $amount,
                    'description' => "Pengalihan anggaran sebesar " . BudgetWbs::formatRupiah($amount) . " dari WBS {$sourceWbs->wbs_number} ke WBS {$destWbs->wbs_number}. Alasan: {$validated['reason']}",
                    'created_by' => Auth::id(),
                    'related_model_type' => BudgetTransfer::class,
                    'related_model_id' => $transfer->id,
                ]);

                // 2. Catat Transaksi Pengalihan Masuk pada WBS Penerima
                BudgetTransaction::create([
                    'transaction_number' => BudgetTransaction::generateNextTransactionNumber(),
                    'transaction_type' => BudgetTransaction::TYPE_TRANSFER_IN,
                    'transaction_type_label' => 'Pengalihan Anggaran (Masuk)',
                    'transaction_date' => $validated['transfer_date'],
                    'budget_wbs_id' => $destWbs->id,
                    'program_name' => $destWbs->wbs_name,
                    'reference_number' => $transferNumber,
                    'amount' => $amount,
                    'description' => "Penerimaan pengalihan anggaran sebesar " . BudgetWbs::formatRupiah($amount) . " dari WBS {$sourceWbs->wbs_number}. Alasan: {$validated['reason']}",
                    'created_by' => Auth::id(),
                    'related_model_type' => BudgetTransfer::class,
                    'related_model_id' => $transfer->id,
                ]);

                ActivityLog::record([
                    'module' => 'Monitoring Anggaran',
                    'action' => 'PENGALIHAN_ANGGARAN',
                'record_type' => BudgetTransfer::class,
                'record_id' => $transfer->id,
                'description' => "Pengalihan Anggaran {$transferNumber} sebesar Rp" . number_format($amount, 0, ',', '.') . " dari WBS {$sourceWbs->wbs_number} ke WBS {$destWbs->wbs_number}. Alasan: {$validated['reason']}",
            ]);

            return $transfer;
        });
        } catch (\DomainException $e) {
            return $this->validationFailureResponse($request, $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengalihan anggaran antar-WBS berhasil disimpan.',
                'data' => $transfer,
            ]);
        }

        $sourceWbs = BudgetWbs::find($validated['source_wbs_id']);
        $destWbs = BudgetWbs::find($validated['destination_wbs_id']);
        $sourceWbsNumber = $sourceWbs ? $sourceWbs->wbs_number : ('WBS #' . $validated['source_wbs_id']);
        $destWbsNumber = $destWbs ? $destWbs->wbs_number : ('WBS #' . $validated['destination_wbs_id']);

        return redirect()->route('budget-monitoring.index', ['tab' => 'pengalihan'])
            ->with('success', "Pengalihan anggaran {$transfer->transfer_number} sebesar " . BudgetWbs::formatRupiah($amount) . " dari {$sourceWbsNumber} ke {$destWbsNumber} berhasil disimpan.");
    }

    /**
     * Ekspor Laporan PDF
     */
    public function exportPdf(Request $request): Response
    {
        $fiscalYear = $request->filled('fiscal_year') ? (int) $request->input('fiscal_year') : date('Y');

        $wbsRecords = BudgetWbs::where('is_active', true)
            ->when($fiscalYear, fn($q) => $q->where('fiscal_year', $fiscalYear))
            ->orderBy('wbs_number')
            ->get();

        $wbsIds = $wbsRecords->pluck('id')->toArray();

        $totalAnggaran = (float) $wbsRecords->sum('current_budget');
        $totalPo = (float) BudgetPo::whereIn('budget_wbs_id', $wbsIds)->where('status', '!=', 'Dibatalkan')->sum(DB::raw('CASE WHEN po_value > 0 THEN po_value ELSE commitment_value END'));
        $realisasiPo = (float) BudgetPoPayment::whereIn('budget_wbs_id', $wbsIds)->sum('amount');
        $realisasiNonPo = (float) BudgetNonPo::whereIn('budget_wbs_id', $wbsIds)->sum('amount');
        $sisaKomitmenPo = max(0, (float) ($totalPo - $realisasiPo));
        $anggaranTeralokasi = (float) ($totalPo + $realisasiNonPo);
        $sisaAnggaran = (float) ($totalAnggaran - $anggaranTeralokasi);

        $metrics = [
            'total_anggaran' => $totalAnggaran,
            'budget' => $totalAnggaran,
            'total_po' => $totalPo,
            'komitmen_po' => $totalPo,
            'commitment' => $sisaKomitmenPo,
            'realisasi_po' => $realisasiPo,
            'po_expenses' => $realisasiPo,
            'realisasi_non_po' => $realisasiNonPo,
            'non_po_expenses' => $realisasiNonPo,
            'anggaran_teralokasi' => $anggaranTeralokasi,
            'assigned' => $anggaranTeralokasi,
            'sisa_anggaran' => $sisaAnggaran,
            'available' => $sisaAnggaran,
            'sisa_komitmen_po' => $sisaKomitmenPo,
        ];

        $pos = BudgetPo::with('wbs')->whereIn('budget_wbs_id', $wbsIds)->orderBy('po_date', 'desc')->get();
        $payments = BudgetPoPayment::with(['po', 'wbs'])->whereIn('budget_wbs_id', $wbsIds)->orderBy('payment_date', 'desc')->get();
        $nonPos = BudgetNonPo::with('wbs')->whereIn('budget_wbs_id', $wbsIds)->orderBy('transaction_date', 'desc')->get();
        $transfers = BudgetTransfer::with(['sourceWbs', 'destinationWbs'])->orderBy('transfer_date', 'desc')->get();

        ActivityLog::record([
            'module' => 'Monitoring Anggaran',
            'action' => 'EXPORT_PDF',
            'description' => "Mengekspor Laporan PDF Monitoring Anggaran Tahun {$fiscalYear}",
        ]);

        $pdf = Pdf::loadView('exports.budget-monitoring-pdf', [
            'title' => 'Laporan Monitoring Anggaran HSE & Safeguard',
            'fiscalYear' => $fiscalYear,
            'metrics' => $metrics,
            'wbsRecords' => $wbsRecords,
            'pos' => $pos,
            'payments' => $payments,
            'nonPos' => $nonPos,
            'transfers' => $transfers,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
            'generatedBy' => Auth::user()?->name ?? 'Administrator HSSE',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Monitoring_Anggaran_' . date('Ymd_His') . '.pdf');
    }

    /**
     * Ekspor Laporan Excel / CSV
     */
    public function exportExcel(Request $request): Response
    {
        $fiscalYear = $request->filled('fiscal_year') ? (int) $request->input('fiscal_year') : date('Y');

        $wbsRecords = BudgetWbs::where('is_active', true)
            ->when($fiscalYear, fn($q) => $q->where('fiscal_year', $fiscalYear))
            ->orderBy('wbs_number')
            ->get();

        $filename = 'Laporan_Monitoring_Anggaran_WBS_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        ActivityLog::record([
            'module' => 'Monitoring Anggaran',
            'action' => 'EXPORT_EXCEL',
            'description' => "Mengekspor Laporan CSV/Excel Monitoring Anggaran Tahun {$fiscalYear}",
        ]);

        $callback = function () use ($wbsRecords, $fiscalYear) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens with proper accents and character encoding
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['LAPORAN MONITORING ANGGARAN HSE & SAFEGUARD']);
            fputcsv($handle, ['Tahun Anggaran', $fiscalYear]);
            fputcsv($handle, ['Tanggal Ekspor', date('d/m/Y H:i')]);
            fputcsv($handle, []);

            // Header Tabel WBS
            fputcsv($handle, [
                'No.',
                'Nomor WBS',
                'Nama WBS / Program',
                'Tahun',
                'Budget (Rp)',
                'Total PO (Rp)',
                'Commitment (Rp)',
                'PO Expenses (Rp)',
                'Non-PO Expenses (Rp)',
                'Assigned (Rp)',
                'Available (Rp)',
                'Keterangan',
            ]);

            $index = 1;
            $totBudget = 0;
            $totTotalPo = 0;
            $totCommitment = 0;
            $totPoExp = 0;
            $totNonPoExp = 0;
            $totAssigned = 0;
            $totAvailable = 0;

            foreach ($wbsRecords as $w) {
                $wBudget = $w->current_budget;
                $wTotalPo = $w->total_po_value;
                $wPoExp = $w->po_realization;
                $wComm = max(0, $wTotalPo - $wPoExp);
                $wNonPoExp = $w->non_po_realization;
                $wAssigned = $wTotalPo + $wNonPoExp;
                $wAvailable = $wBudget - $wAssigned;

                $totBudget += $wBudget;
                $totTotalPo += $wTotalPo;
                $totCommitment += $wComm;
                $totPoExp += $wPoExp;
                $totNonPoExp += $wNonPoExp;
                $totAssigned += $wAssigned;
                $totAvailable += $wAvailable;

                fputcsv($handle, [
                    $index++,
                    $w->wbs_number,
                    $w->wbs_name,
                    $w->fiscal_year,
                    $wBudget,
                    $wTotalPo,
                    $wComm,
                    $wPoExp,
                    $wNonPoExp,
                    $wAssigned,
                    $wAvailable,
                    $w->description ?? '-',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                '',
                'TOTAL KESELURUHAN',
                '',
                '',
                $totBudget,
                $totTotalPo,
                $totCommitment,
                $totPoExp,
                $totNonPoExp,
                $totAssigned,
                $totAvailable,
                '',
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Reset / Kosongkan Data Modul Monitoring Anggaran
     */
    public function resetData(Request $request, BudgetMonitoringResetService $resetService): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdminManagement()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Tindakan ini hanya dapat dilakukan oleh Admin Management.',
                ], 403);
            }
            abort(403, 'Akses ditolak. Tindakan ini hanya dapat dilakukan oleh Admin Management.');
        }

        $request->validate([
            'scope' => 'required|in:all,transactions_only',
            'confirm_text' => 'required|in:RESET,reset',
        ], [
            'confirm_text.in' => 'Ketik kata "RESET" secara tepat untuk mengonfirmasi pengosongan data.',
        ]);

        $resetWbs = ($request->input('scope') === 'all');
        $result = $resetService->resetData($resetWbs);

        $msg = $resetWbs
            ? "Berhasil melakukan Reset Total modul Monitoring Anggaran! Seluruh data transaksi ({$result['total_deleted']} record) dan WBS pengujian telah dikosongkan. Sistem siap digunakan dari awal."
            : "Berhasil mengosongkan transaksi Monitoring Anggaran ({$result['total_deleted']} record)! Data WBS dipertahankan dan pagu dikembalikan ke nominal awal.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'details' => $result,
            ]);
        }

        return redirect()->route('budget-monitoring.index')->with('success', $msg);
    }
}

