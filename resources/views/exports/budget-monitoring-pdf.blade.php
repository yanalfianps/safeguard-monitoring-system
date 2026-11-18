@extends('exports.layout')

@section('content')
<div style="margin-bottom: 14px;">
    <h2 style="margin: 0 0 4px; font-size: 13pt; color: #087c68; text-transform: uppercase;">
        LAPORAN MONITORING ANGGARAN HSE &amp; SAFEGUARD
    </h2>
    <div style="font-size: 8.5pt; color: #64748b;">
        Tahun Anggaran: <strong>{{ $fiscalYear }}</strong> &nbsp;|&nbsp;
        Tanggal Laporan: <strong>{{ $generatedAt }}</strong> &nbsp;|&nbsp;
        Dicetak Oleh: <strong>{{ $generatedBy }}</strong>
    </div>
</div>

<!-- 7 INDIKATOR UTAMA KEUANGAN -->
<table style="width: 100%; border-collapse: collapse; margin-bottom: 14px; background-color: #f8fafc; border: 1px solid #cbd5e1;">
    <tr>
        <td style="padding: 6px 6px; width: 14.28%; border: 1px solid #cbd5e1; text-align: center;">
            <div style="font-size: 6.5pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Budget</div>
            <div style="font-size: 8.5pt; font-weight: bold; color: #0f766e; margin-top: 2px;">{{ \App\Models\BudgetWbs::formatRupiah($metrics['budget'] ?? $metrics['total_anggaran']) }}</div>
        </td>
        <td style="padding: 6px 6px; width: 14.28%; border: 1px solid #cbd5e1; text-align: center;">
            <div style="font-size: 6.5pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Total PO</div>
            <div style="font-size: 8.5pt; font-weight: bold; color: #0f172a; margin-top: 2px;">{{ \App\Models\BudgetWbs::formatRupiah($metrics['total_po'] ?? $metrics['komitmen_po']) }}</div>
        </td>
        <td style="padding: 6px 6px; width: 14.28%; border: 1px solid #cbd5e1; text-align: center;">
            <div style="font-size: 6.5pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Commitment</div>
            <div style="font-size: 8.5pt; font-weight: bold; color: #2563eb; margin-top: 2px;">{{ \App\Models\BudgetWbs::formatRupiah($metrics['commitment'] ?? $metrics['sisa_komitmen_po']) }}</div>
        </td>
        <td style="padding: 6px 6px; width: 14.28%; border: 1px solid #cbd5e1; text-align: center;">
            <div style="font-size: 6.5pt; color: #64748b; font-weight: bold; text-transform: uppercase;">PO Expenses</div>
            <div style="font-size: 8.5pt; font-weight: bold; color: #0891b2; margin-top: 2px;">{{ \App\Models\BudgetWbs::formatRupiah($metrics['po_expenses'] ?? $metrics['realisasi_po']) }}</div>
        </td>
        <td style="padding: 6px 6px; width: 14.28%; border: 1px solid #cbd5e1; text-align: center;">
            <div style="font-size: 6.5pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Non-PO Expenses</div>
            <div style="font-size: 8.5pt; font-weight: bold; color: #d97706; margin-top: 2px;">{{ \App\Models\BudgetWbs::formatRupiah($metrics['non_po_expenses'] ?? $metrics['realisasi_non_po']) }}</div>
        </td>
        <td style="padding: 6px 6px; width: 14.28%; border: 1px solid #cbd5e1; text-align: center;">
            <div style="font-size: 6.5pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Assigned</div>
            <div style="font-size: 8.5pt; font-weight: bold; color: #4f46e5; margin-top: 2px;">{{ \App\Models\BudgetWbs::formatRupiah($metrics['assigned'] ?? $metrics['anggaran_teralokasi']) }}</div>
        </td>
        <td style="padding: 6px 6px; width: 14.28%; border: 1px solid #cbd5e1; text-align: center;">
            <div style="font-size: 6.5pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Available</div>
            <div style="font-size: 8.5pt; font-weight: bold; color: #059669; margin-top: 2px;">{{ \App\Models\BudgetWbs::formatRupiah($metrics['available'] ?? $metrics['sisa_anggaran']) }}</div>
        </td>
    </tr>
</table>

<!-- BAGIAN 1: REKAPITULASI WBS -->
<h3 style="font-size: 9.5pt; margin: 10px 0 6px; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">
    1. Rekapitulasi Daftar Anggaran/WBS
</h3>
<table class="data-table" style="margin-bottom: 16px;">
    <thead>
        <tr>
            <th style="width: 3%;" class="text-center">No</th>
            <th style="width: 11%;">Nomor WBS</th>
            <th style="width: 18%;">Nama WBS / Program</th>
            <th style="width: 10%;" class="text-right">Budget</th>
            <th style="width: 10%;" class="text-right">Total PO</th>
            <th style="width: 10%;" class="text-right">Commitment</th>
            <th style="width: 10%;" class="text-right">PO Expenses</th>
            <th style="width: 10%;" class="text-right">Non-PO Expenses</th>
            <th style="width: 9%;" class="text-right">Assigned</th>
            <th style="width: 9%;" class="text-right">Available</th>
        </tr>
    </thead>
    <tbody>
        @php
            $pdfSumBudget = 0;
            $pdfSumTotalPo = 0;
            $pdfSumCommitment = 0;
            $pdfSumPoExp = 0;
            $pdfSumNonPoExp = 0;
            $pdfSumAssigned = 0;
            $pdfSumAvailable = 0;
        @endphp
        @forelse($wbsRecords as $idx => $w)
            @php
                $wBudget = $w->current_budget;
                $wTotalPo = $w->total_po_value;
                $wPoExp = $w->po_realization;
                $wComm = max(0, $wTotalPo - $wPoExp);
                $wNonPoExp = $w->non_po_realization;
                $wAssigned = $wTotalPo + $wNonPoExp;
                $wAvailable = $wBudget - $wAssigned;

                $pdfSumBudget += $wBudget;
                $pdfSumTotalPo += $wTotalPo;
                $pdfSumCommitment += $wComm;
                $pdfSumPoExp += $wPoExp;
                $pdfSumNonPoExp += $wNonPoExp;
                $pdfSumAssigned += $wAssigned;
                $pdfSumAvailable += $wAvailable;
            @endphp
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ $w->wbs_number }}</td>
                <td><strong>{{ $w->wbs_name }}</strong></td>
                <td class="text-right">{{ \App\Models\BudgetWbs::formatRupiah($wBudget) }}</td>
                <td class="text-right">{{ \App\Models\BudgetWbs::formatRupiah($wTotalPo) }}</td>
                <td class="text-right" style="color: #2563eb;">{{ \App\Models\BudgetWbs::formatRupiah($wComm) }}</td>
                <td class="text-right" style="color: #0891b2;">{{ \App\Models\BudgetWbs::formatRupiah($wPoExp) }}</td>
                <td class="text-right" style="color: #d97706;">{{ \App\Models\BudgetWbs::formatRupiah($wNonPoExp) }}</td>
                <td class="text-right" style="color: #4f46e5; font-weight: bold;">{{ \App\Models\BudgetWbs::formatRupiah($wAssigned) }}</td>
                <td class="text-right" style="color: {{ $wAvailable < 0 ? '#dc2626' : '#059669' }}; font-weight: bold;">
                    {{ \App\Models\BudgetWbs::formatRupiah($wAvailable) }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 12px; color: #94a3b8;">Belum ada data WBS terdaftar.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="3" class="text-center">TOTAL KESELURUHAN</td>
            <td class="text-right">{{ \App\Models\BudgetWbs::formatRupiah($pdfSumBudget) }}</td>
            <td class="text-right">{{ \App\Models\BudgetWbs::formatRupiah($pdfSumTotalPo) }}</td>
            <td class="text-right" style="color: #2563eb;">{{ \App\Models\BudgetWbs::formatRupiah($pdfSumCommitment) }}</td>
            <td class="text-right" style="color: #0891b2;">{{ \App\Models\BudgetWbs::formatRupiah($pdfSumPoExp) }}</td>
            <td class="text-right" style="color: #d97706;">{{ \App\Models\BudgetWbs::formatRupiah($pdfSumNonPoExp) }}</td>
            <td class="text-right" style="color: #4f46e5;">{{ \App\Models\BudgetWbs::formatRupiah($pdfSumAssigned) }}</td>
            <td class="text-right" style="color: {{ $pdfSumAvailable < 0 ? '#dc2626' : '#059669' }};">{{ \App\Models\BudgetWbs::formatRupiah($pdfSumAvailable) }}</td>
        </tr>
    </tfoot>
</table>

<!-- BAGIAN 2: DAFTAR PENGADAAN PO -->
<h3 style="font-size: 9.5pt; margin: 14px 0 6px; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">
    2. Rincian Pengadaan / Purchase Order (PO)
</h3>
<table class="data-table" style="margin-bottom: 16px;">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 12%;">Nomor PO</th>
            <th style="width: 12%;">Nomor WBS</th>
            <th style="width: 22%;">Nama Pengadaan / Program</th>
            <th style="width: 14%;">Vendor</th>
            <th style="width: 12%;" class="text-right">Komitmen PO</th>
            <th style="width: 12%;" class="text-right">Realisasi Bayar</th>
            <th style="width: 12%;" class="text-right">Sisa Komitmen</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pos as $idx => $p)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td style="font-family: monospace;">{{ $p->po_number }}</td>
                <td style="font-family: monospace;">{{ $p->wbs?->wbs_number }}</td>
                <td>{{ $p->program_name }}</td>
                <td>{{ $p->vendor ?? '-' }}</td>
                <td class="text-right">{{ \App\Models\BudgetPo::formatRupiah($p->commitment_value) }}</td>
                <td class="text-right" style="color: #0891b2;">{{ \App\Models\BudgetPo::formatRupiah($p->total_payment) }}</td>
                <td class="text-right" style="font-weight: bold;">{{ \App\Models\BudgetPo::formatRupiah($p->remaining_commitment) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 12px; color: #94a3b8;">Belum ada pengadaan PO.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- BAGIAN 3: REALISASI NON-PO -->
<h3 style="font-size: 9.5pt; margin: 14px 0 6px; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">
    3. Rincian Pengeluaran Realisasi Non-PO
</h3>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 14%;">No. Transaksi</th>
            <th style="width: 12%;">Nomor WBS</th>
            <th style="width: 10%;" class="text-center">Tanggal</th>
            <th style="width: 28%;">Nama Pengadaan / Program</th>
            <th style="width: 18%;">Penerima / Vendor</th>
            <th style="width: 14%;" class="text-right">Jumlah Realisasi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($nonPos as $idx => $np)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td style="font-family: monospace;">{{ $np->transaction_number }}</td>
                <td style="font-family: monospace;">{{ $np->wbs?->wbs_number }}</td>
                <td class="text-center">{{ $np->transaction_date ? $np->transaction_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $np->program_name }}</td>
                <td>{{ $np->recipient_vendor ?? '-' }}</td>
                <td class="text-right" style="color: #d97706; font-weight: bold;">{{ \App\Models\BudgetNonPo::formatRupiah($np->amount) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 12px; color: #94a3b8;">Belum ada pengeluaran Non-PO.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

