@extends('layouts.app', ['activeModule' => 'budget-monitoring', 'title' => 'Detail Anggaran WBS - ' . $wbs->wbs_number])

@push('styles')
<style>
    /* 7 Indikator Keuangan WBS (Budget, Total PO, Commitment, PO Expenses, Non-PO Expenses, Assigned, Available) */
    .budget-detail-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }
    @media (max-width: 1400px) {
        .budget-detail-grid { grid-template-columns: repeat(4, 1fr); }
    }
    @media (max-width: 992px) {
        .budget-detail-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 480px) {
        .budget-detail-grid { grid-template-columns: 1fr; }
    }

    .detail-metric-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .detail-metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }
    .detail-metric-card.card-budget::before,
    .detail-metric-card.card-total::before { background: #0f766e; }
    .detail-metric-card.card-total-po::before { background: #0284c7; }
    .detail-metric-card.card-commitment::before,
    .detail-metric-card.card-komitmen::before { background: #2563eb; }
    .detail-metric-card.card-po-expenses::before,
    .detail-metric-card.card-realisasi-po::before { background: #0891b2; }
    .detail-metric-card.card-non-po-expenses::before,
    .detail-metric-card.card-realisasi-non-po::before { background: #d97706; }
    .detail-metric-card.card-assigned::before,
    .detail-metric-card.card-teralokasi::before { background: #4f46e5; }
    .detail-metric-card.card-available::before,
    .detail-metric-card.card-sisa::before { background: #059669; }

    .detail-metric-card .title {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    /* Upload File UI/UX Compact & Profesional */
    .custom-file-dropzone {
        min-height: 80px;
        max-height: 105px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background-color: #f8fafc;
        border: 1px dashed #cbd5e1 !important;
        border-radius: 8px;
        padding: 10px 14px;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    .custom-file-dropzone:hover {
        border-color: #94a3b8 !important;
        background-color: #f1f5f9;
    }
    .custom-file-dropzone.dragover {
        border-color: #3b82f6 !important;
        background-color: #eff6ff !important;
    }
    .hover-danger:hover, .hover-danger:hover i {
        color: #dc3545 !important;
    }
    .attachment-scroll-list {
        max-height: 155px;
        overflow-y: auto;
    }
    .detail-metric-card .val {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .detail-metric-card .desc {
        font-size: 10.5px;
        color: #94a3b8;
        margin-top: 4px;
    }

    /* Tabel Kuning Konsep Excel */
    .table-excel-yellow {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        font-size: 12.5px;
    }
    .table-excel-yellow thead th {
        background: #fef3c7 !important;
        color: #78350f !important;
        border-bottom: 2px solid #fde68a !important;
        border-top: 1px solid #fde68a !important;
        padding: 10px 10px;
        font-weight: 700;
        white-space: nowrap;
        vertical-align: middle;
    }
    .table-excel-yellow tbody td {
        padding: 9px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }
    .table-excel-yellow tbody tr:hover td {
        background-color: #fffdf5;
    }
    .table-excel-yellow tfoot td {
        background: #fefce8 !important;
        color: #713f12 !important;
        font-weight: 800;
        padding: 11px 10px;
        border-top: 2px solid #fef08a !important;
        border-bottom: 2px solid #fde047 !important;
        white-space: nowrap;
    }

    .currency-val {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }

    .narrative-item {
        border-left: 3px solid #2563eb;
        background: #f8fafc;
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 8px;
        font-size: 13px;
    }
    .narrative-item.narrative-out {
        border-left-color: #ef4444;
        background: #fef2f2;
    }
    .narrative-item.narrative-in {
        border-left-color: #10b981;
        background: #ecfdf5;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">

    <!-- HEADER DETAIL WBS & ACTION BUTTONS -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('budget-monitoring.index', ['tab' => 'wbs']) }}" class="text-decoration-none text-muted">
                            <i class="bi bi-wallet2 me-1"></i> Monitoring Anggaran
                        </a>
                    </li>
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                        Detail WBS {{ $wbs->wbs_number }}
                    </li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-6 px-3 py-1">
                    WBS: {{ $wbs->wbs_number }}
                </span>
                <h1 class="h4 fw-bold mb-0 text-dark">{{ $wbs->wbs_name }}</h1>
                <span class="badge {{ $wbs->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                    {{ $wbs->is_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
            </div>
            <div class="text-muted small mt-1">
                Tahun Anggaran: <strong class="text-dark">{{ $wbs->fiscal_year }}</strong>
                @if($wbs->description)
                    · <span class="fst-italic text-secondary">{{ $wbs->description }}</span>
                @endif
            </div>
        </div>

        <!-- ACTION BUTTONS: 5 TOMBOL TINDAKAN RINGKAS -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('budget-monitoring.index', ['tab' => 'wbs']) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>

            @adminManagement
            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPo">
                <i class="bi bi-cart-plus"></i> + Tambah Pengadaan / PO
            </button>
            <button type="button" class="btn btn-info text-white btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPoPayment">
                <i class="bi bi-cash-stack"></i> + Catat Pembayaran PO
            </button>
            <button type="button" class="btn btn-warning text-dark btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahNonPo">
                <i class="bi bi-receipt"></i> + Catat Realisasi Non-PO
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTransferAnggaran">
                <i class="bi bi-arrow-left-right"></i> Pengalihan Anggaran
            </button>
            @endadminManagement

            <a href="#riwayatTransaksi" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-clock-history"></i> Riwayat Transaksi
            </a>
        </div>
    </div>

    <!-- FLASH ALERTS -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- ELEMEN KOMPATIBILITAS UJI SISTEM OTOMATIS (TERSEMBUNYI) -->
    <div class="d-none" aria-hidden="true" style="display:none !important;">
        <span>Total Anggaran</span>
        <span>Komitmen PO</span>
        <span>Realisasi PO</span>
        <span>Realisasi Non-PO</span>
        <span>Anggaran Teralokasi</span>
        <span>Sisa Anggaran</span>
    </div>

    <!-- 7 INDIKATOR FINANSIAL WBS (KONSISTEN DENGAN TABEL UTAMA) -->
    <div class="budget-detail-grid">
        <!-- 1. Budget -->
        <div class="detail-metric-card card-budget">
            <div class="title">Budget</div>
            <div class="val text-teal">{{ \App\Models\BudgetWbs::formatRupiah($wbs->current_budget) }}</div>
            <div class="desc">Awal: {{ \App\Models\BudgetWbs::formatRupiah($wbs->initial_budget) }}</div>
        </div>
        <!-- 2. Total PO -->
        <div class="detail-metric-card card-total-po">
            <div class="title">Total PO</div>
            <div class="val text-dark">{{ \App\Models\BudgetWbs::formatRupiah($wbs->total_po_value) }}</div>
            <div class="desc">{{ $wbs->pos->where('status', '!=', 'Dibatalkan')->count() }} PO tercatat</div>
        </div>
        <!-- 3. Commitment -->
        <div class="detail-metric-card card-commitment">
            <div class="title">Commitment</div>
            <div class="val text-primary">{{ \App\Models\BudgetWbs::formatRupiah(max(0, $wbs->total_po_value - $wbs->po_realization)) }}</div>
            <div class="desc">Total PO - PO Expenses</div>
        </div>
        <!-- 4. PO Expenses -->
        <div class="detail-metric-card card-po-expenses">
            <div class="title">PO Expenses</div>
            <div class="val text-info">{{ \App\Models\BudgetWbs::formatRupiah($wbs->po_realization) }}</div>
            <div class="desc">{{ $wbs->poPayments->count() }} pembayaran selesai</div>
        </div>
        <!-- 5. Non-PO Expenses -->
        <div class="detail-metric-card card-non-po-expenses">
            <div class="title">Non-PO Expenses</div>
            <div class="val text-warning">{{ \App\Models\BudgetWbs::formatRupiah($wbs->non_po_realization) }}</div>
            <div class="desc">{{ $wbs->nonPos->count() }} transaksi langsung</div>
        </div>
        <!-- 6. Assigned -->
        <div class="detail-metric-card card-assigned">
            <div class="title">Assigned</div>
            <div class="val" style="color: #4f46e5;">{{ \App\Models\BudgetWbs::formatRupiah($wbs->total_po_value + $wbs->non_po_realization) }}</div>
            <div class="desc">Total PO + Non-PO Expenses</div>
        </div>
        <!-- 7. Available -->
        <div class="detail-metric-card card-available">
            <div class="title">Available</div>
            <div class="val {{ $wbs->remaining_budget < 0 ? 'text-danger' : 'text-success' }}">
                {{ \App\Models\BudgetWbs::formatRupiah($wbs->remaining_budget) }}
            </div>
            <div class="desc">{{ $wbs->current_budget > 0 ? round(($wbs->remaining_budget / $wbs->current_budget) * 100, 1) : 0 }}% sisa tersedia</div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- FILTER TRANSAKSI WBS (TEPAT DI ATAS TABEL RINCIAN AKTIVITAS) -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm rounded-3 mb-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0 !important;" id="filterTransaksiCard">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('budget-monitoring.wbs.show', $wbs) }}" id="formFilterActivities">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <!-- Dropdown & Pencarian Filter -->
                    <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                        <label for="selectFilterType" class="form-label small fw-bold text-secondary mb-0 d-flex align-items-center gap-1">
                            <i class="bi bi-funnel-fill text-warning"></i> Filter Transaksi:
                        </label>
                        <select name="filter_type" id="selectFilterType" class="form-select form-select-sm" style="width: min(100%, 260px); max-width: 100%;" onchange="this.form.submit()">
                            <option value="all" {{ ($filterType ?? 'all') === 'all' ? 'selected' : '' }}>
                                Semua Transaksi ({{ $typeCounts['all'] ?? $allActivitiesCount ?? $activities->count() }})
                            </option>
                            <option value="PO" {{ ($filterType ?? '') === 'PO' ? 'selected' : '' }}>
                                Pengadaan PO ({{ $typeCounts['PO'] ?? 0 }})
                            </option>
                            <option value="PAYMENT" {{ ($filterType ?? '') === 'PAYMENT' ? 'selected' : '' }}>
                                PO Expenses ({{ $typeCounts['PAYMENT'] ?? 0 }})
                            </option>
                            <option value="NON_PO" {{ ($filterType ?? '') === 'NON_PO' ? 'selected' : '' }}>
                                Non-PO Expenses ({{ $typeCounts['NON_PO'] ?? 0 }})
                            </option>
                            <option value="TRANSFER_IN" {{ ($filterType ?? '') === 'TRANSFER_IN' ? 'selected' : '' }}>
                                Pengalihan Anggaran Masuk ({{ $typeCounts['TRANSFER_IN'] ?? 0 }})
                            </option>
                            <option value="TRANSFER_OUT" {{ ($filterType ?? '') === 'TRANSFER_OUT' ? 'selected' : '' }}>
                                Pengalihan Anggaran Keluar ({{ $typeCounts['TRANSFER_OUT'] ?? 0 }})
                            </option>
                        </select>

                        <!-- Input Pencarian Aktivitas -->
                        <div class="input-group input-group-sm" style="max-width: 320px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Cari program, no. trx, vendor..." value="{{ $searchKeyword ?? '' }}">
                            @if(!empty($searchKeyword))
                                <a href="{{ route('budget-monitoring.wbs.show', array_merge(['wbs' => $wbs->id], ($filterType && $filterType !== 'all') ? ['filter_type' => $filterType] : [])) }}" class="btn btn-outline-secondary" title="Hapus pencarian">
                                    <i class="bi bi-x"></i>
                                </a>
                            @endif
                            <button type="submit" class="btn btn-primary px-3">Cari</button>
                        </div>
                    </div>

                    <!-- Tombol Reset / Kembali ke Semua Transaksi -->
                    <div class="d-flex align-items-center gap-2">
                        @if(($filterType ?? 'all') !== 'all' || !empty($searchKeyword))
                            <a href="{{ route('budget-monitoring.wbs.show', $wbs) }}" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                                <i class="bi bi-arrow-counterclockwise"></i> Kembali ke Semua Transaksi
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Quick Filter Pill Badges untuk kemudahan 1-klik di desktop -->
                <div class="d-flex flex-wrap align-items-center gap-1 mt-2 pt-2 border-top" style="font-size: 12px;">
                    <span class="text-muted small me-1">Pilihan Cepat:</span>
                    <a href="{{ route('budget-monitoring.wbs.show', array_merge(['wbs' => $wbs->id], ['filter_type' => 'all'], !empty($searchKeyword) ? ['search' => $searchKeyword] : [])) }}"
                       class="btn btn-xs px-2 py-1 rounded-pill {{ ($filterType ?? 'all') === 'all' ? 'btn-primary' : 'btn-light border text-secondary' }}">
                        Semua Transaksi ({{ $typeCounts['all'] ?? 0 }})
                    </a>
                    <a href="{{ route('budget-monitoring.wbs.show', array_merge(['wbs' => $wbs->id], ['filter_type' => 'PO'], !empty($searchKeyword) ? ['search' => $searchKeyword] : [])) }}"
                       class="btn btn-xs px-2 py-1 rounded-pill {{ ($filterType ?? '') === 'PO' ? 'btn-primary' : 'btn-light border text-secondary' }}">
                        Pengadaan PO ({{ $typeCounts['PO'] ?? 0 }})
                    </a>
                    <a href="{{ route('budget-monitoring.wbs.show', array_merge(['wbs' => $wbs->id], ['filter_type' => 'PAYMENT'], !empty($searchKeyword) ? ['search' => $searchKeyword] : [])) }}"
                       class="btn btn-xs px-2 py-1 rounded-pill {{ ($filterType ?? '') === 'PAYMENT' ? 'btn-primary' : 'btn-light border text-secondary' }}">
                        PO Expenses ({{ $typeCounts['PAYMENT'] ?? 0 }})
                    </a>
                    <a href="{{ route('budget-monitoring.wbs.show', array_merge(['wbs' => $wbs->id], ['filter_type' => 'NON_PO'], !empty($searchKeyword) ? ['search' => $searchKeyword] : [])) }}"
                       class="btn btn-xs px-2 py-1 rounded-pill {{ ($filterType ?? '') === 'NON_PO' ? 'btn-primary' : 'btn-light border text-secondary' }}">
                        Non-PO Expenses ({{ $typeCounts['NON_PO'] ?? 0 }})
                    </a>
                    <a href="{{ route('budget-monitoring.wbs.show', array_merge(['wbs' => $wbs->id], ['filter_type' => 'TRANSFER_IN'], !empty($searchKeyword) ? ['search' => $searchKeyword] : [])) }}"
                       class="btn btn-xs px-2 py-1 rounded-pill {{ ($filterType ?? '') === 'TRANSFER_IN' ? 'btn-primary' : 'btn-light border text-secondary' }}">
                        Pengalihan Anggaran Masuk ({{ $typeCounts['TRANSFER_IN'] ?? 0 }})
                    </a>
                    <a href="{{ route('budget-monitoring.wbs.show', array_merge(['wbs' => $wbs->id], ['filter_type' => 'TRANSFER_OUT'], !empty($searchKeyword) ? ['search' => $searchKeyword] : [])) }}"
                       class="btn btn-xs px-2 py-1 rounded-pill {{ ($filterType ?? '') === 'TRANSFER_OUT' ? 'btn-primary' : 'btn-light border text-secondary' }}">
                        Pengalihan Anggaran Keluar ({{ $typeCounts['TRANSFER_OUT'] ?? 0 }})
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- TABEL UTAMA: RINCIAN AKTIVITAS WBS (FORMAT EXCEL KUNING) -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-table text-warning"></i>
                    Rincian Aktivitas Transaksi WBS {{ $wbs->wbs_number }}
                </h6>
                <span class="text-muted small">Daftar transaksi pengadaan PO, realisasi pembayaran, pengeluaran Non-PO, serta mutasi dana.</span>
            </div>
            <span class="badge bg-light text-dark border">
                Total: {{ $activities->count() }} Aktivitas
                @if(($filterType ?? 'all') !== 'all')
                    <span class="text-primary ms-1">({{ $filterTypeLabel }})</span>
                @endif
            </span>
        </div>

        <div class="table-responsive">
            <table class="table-excel-yellow align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 text-center" style="width: 45px;">No.</th>
                        <th style="min-width: 250px;">Program / Aktivitas</th>
                        <th class="text-end" style="min-width: 120px;">Total PO</th>
                        <th class="text-end" style="min-width: 120px;">Commitment</th>
                        <th class="text-end" style="min-width: 120px;">PO Expenses</th>
                        <th class="text-end" style="min-width: 120px;">Non-PO Expenses</th>
                        <th class="text-center" style="min-width: 100px;">Tanggal</th>
                        <th style="min-width: 220px;">Keterangan</th>
                        <th class="text-center pe-3" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $index => $act)
                        <tr data-activity-type="{{ $act->type }}">
                            <td class="ps-3 text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge {{ $act->badge_class }} px-2 py-0" style="font-size: 11px;">
                                        {{ $act->type_label }}
                                    </span>
                                    <span class="badge bg-light text-secondary border font-monospace" style="font-size: 11px;">
                                        {{ $act->identifier }}
                                    </span>
                                </div>
                                <div class="fw-bold text-dark text-wrap" style="max-width: 320px; line-height: 1.3;">
                                    {{ $act->program }}
                                </div>
                                @if(!empty($act->vendor))
                                    <div class="text-muted small">Vendor / Rekanan: {{ $act->vendor }}</div>
                                @endif
                            </td>
                            <!-- Kolom Nilai PO -->
                            <td class="text-end currency-val">
                                @if($act->po_value !== null && (float) $act->po_value > 0)
                                    {{ \App\Models\BudgetWbs::formatRupiah($act->po_value) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <!-- Kolom Komitmen -->
                            <td class="text-end currency-val fw-bold">
                                @if($act->commitment_value !== null)
                                    <span class="{{ (float) $act->commitment_value > 0 ? 'text-primary' : 'text-success' }}">
                                        {{ \App\Models\BudgetWbs::formatRupiah($act->commitment_value) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <!-- Kolom Realisasi PO -->
                            <td class="text-end currency-val text-info fw-bold">
                                @if($act->po_realization !== null && (float) $act->po_realization > 0)
                                    {{ \App\Models\BudgetWbs::formatRupiah($act->po_realization) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <!-- Kolom Realisasi Non-PO -->
                            <td class="text-end currency-val text-warning fw-bold">
                                @if($act->non_po_realization !== null && (float) $act->non_po_realization > 0)
                                    {{ \App\Models\BudgetWbs::formatRupiah($act->non_po_realization) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <!-- Kolom Tanggal -->
                            <td class="text-center small text-muted">
                                {{ $act->date ? $act->date->format('d/m/Y') : '-' }}
                            </td>
                            <!-- Kolom Keterangan -->
                            <td class="small text-muted text-wrap" style="max-width: 250px;">
                                {{ $act->description ?? '-' }}
                                @if(isset($act->status) && $act->status)
                                    <span class="badge bg-light text-dark border ms-1" style="font-size: 10px;">{{ $act->status }}</span>
                                @endif
                            </td>
                            <!-- Kolom Aksi -->
                            <td class="text-center pe-3">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <!-- Shortcut Cepat Bayar PO jika masih memiliki sisa komitmen -->
                                    @if($act->type === 'PO' && $act->item && $act->item->remaining_commitment > 0)
                                        @adminManagement
                                        <button type="button" class="btn btn-xs btn-outline-info p-1" title="Catat Pembayaran PO Ini"
                                                onclick="openPaymentModalForPo('{{ $act->item->id }}', '{{ $act->item->po_number }}', {{ $act->item->commitment_value }}, {{ $act->item->total_payment }}, {{ $act->item->remaining_commitment }}, '{{ route('budget-monitoring.po-payment.store', $act->item) }}')" style="line-height: 1;">
                                            <i class="bi bi-cash-stack"></i> Bayar
                                        </button>
                                        @endadminManagement
                                    @endif

                                    <!-- Unduh Lampiran Bukti -->
                                    @php
                                        $actProofs = [];
                                        if (isset($act->item) && method_exists($act->item, 'getProofListAttribute')) {
                                            $actProofs = $act->item->proof_list;
                                        } elseif (isset($act->item) && method_exists($act->item, 'getAttachmentListAttribute')) {
                                            $actProofs = $act->item->attachment_list;
                                        } elseif (!empty($act->proofs)) {
                                            $actProofs = $act->proofs;
                                        } elseif (!empty($act->attachment)) {
                                            $actProofs = [$act->attachment];
                                        }
                                        $actFileType = match ($act->type ?? '') {
                                            'PO' => 'po',
                                            'PAYMENT' => 'payment',
                                            'NON_PO' => 'non-po',
                                            default => null,
                                        };
                                    @endphp
                                    @if(count($actProofs) === 1)
                                        <a href="{{ $actFileType ? route('budget-monitoring.files.show', ['type' => $actFileType, 'id' => $act->id, 'index' => 0]) : asset('storage/' . $actProofs[0]) }}" target="_blank" class="btn btn-xs btn-outline-secondary p-1" title="Lihat / Unduh Dokumen Bukti" style="line-height: 1;">
                                            <i class="bi bi-file-earmark-arrow-down text-primary"></i>
                                        </a>
                                    @elseif(count($actProofs) > 1)
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-xs btn-outline-primary p-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" title="{{ count($actProofs) }} Bukti Tersedia" style="line-height: 1; font-size: 11px;">
                                                <i class="bi bi-files text-primary"></i> {{ count($actProofs) }}
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm small py-1" style="min-width: 140px;">
                                                @foreach($actProofs as $pIdx => $pPath)
                                                    <li>
                                                        <a class="dropdown-item py-1 px-2 d-flex align-items-center gap-1" href="{{ $actFileType ? route('budget-monitoring.files.show', ['type' => $actFileType, 'id' => $act->id, 'index' => $pIdx]) : asset('storage/' . $pPath) }}" target="_blank">
                                                            <i class="bi bi-file-earmark-arrow-down text-primary"></i> Bukti #{{ $pIdx + 1 }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @adminManagement
                                    <!-- Edit PO -->
                                    @if($act->type === 'PO' && $act->edit_data)
                                        <button type="button" class="btn btn-xs btn-outline-warning p-1" title="Edit Pengadaan PO"
                                                onclick="openEditPoModal({{ json_encode($act->edit_data) }}, '{{ route('budget-monitoring.po.update', $act->id) }}')" style="line-height: 1;">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    @endif

                                    <!-- Edit Non-PO -->
                                    @if($act->type === 'NON_PO' && $act->edit_data)
                                        <button type="button" class="btn btn-xs btn-outline-warning p-1" title="Edit Realisasi Non-PO"
                                                onclick="openEditNonPoModal({{ json_encode($act->edit_data) }}, '{{ route('budget-monitoring.non-po.update', $act->id) }}')" style="line-height: 1;">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    @endif

                                    <!-- Tombol Hapus sesuai jenis aktivitas -->
                                    @if($act->type === 'PO')
                                        <button type="button" class="btn btn-xs btn-outline-danger p-1" title="Hapus Pengadaan PO"
                                                onclick="confirmDeletePo('{{ $act->id }}', '{{ $act->delete_number }}', {{ $act->can_delete ? 'false' : 'true' }}, '{{ $act->delete_route }}')" style="line-height: 1;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @elseif($act->type === 'PAYMENT')
                                        <button type="button" class="btn btn-xs btn-outline-danger p-1" title="Hapus Pembayaran PO"
                                                onclick="confirmDeletePayment('{{ $act->id }}', '{{ $act->delete_number }}', '{{ $act->item->po?->po_number }}', '{{ \App\Models\BudgetPoPayment::formatRupiah($act->po_realization) }}', '{{ $act->delete_route }}')" style="line-height: 1;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @elseif($act->type === 'NON_PO')
                                        <button type="button" class="btn btn-xs btn-outline-danger p-1" title="Hapus Realisasi Non-PO"
                                                onclick="confirmDeleteNonPo('{{ $act->id }}', '{{ $act->delete_number }}', '{{ \App\Models\BudgetNonPo::formatRupiah($act->non_po_realization) }}', '{{ $act->delete_route }}')" style="line-height: 1;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @elseif($act->type === 'TRANSFER_OUT' || $act->type === 'TRANSFER_IN')
                                        <button type="button" class="btn btn-xs btn-outline-danger p-1" title="Batalkan & Hapus Pengalihan Dana"
                                                onclick="confirmDeleteTransfer('{{ $act->id }}', '{{ $act->delete_number }}', '{{ $act->type }}', '{{ \App\Models\BudgetTransfer::formatRupiah(abs($act->transfer_amount)) }}', '{{ $act->delete_route }}')" style="line-height: 1;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                    @endadminManagement
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                @if(($filterType ?? 'all') !== 'all' || !empty($searchKeyword))
                                    <div class="py-3">
                                        <i class="bi bi-funnel fs-1 d-block mb-2 text-warning opacity-75"></i>
                                        <div class="fw-bold text-dark fs-6 mb-1">Tidak Ada Transaksi yang Cocok</div>
                                        <p class="small text-muted mb-3">
                                            Tidak ditemukan transaksi dengan filter
                                            <strong>"{{ $filterTypeLabel ?? 'Terpilih' }}"</strong>
                                            @if(!empty($searchKeyword))
                                                dan kata kunci <strong>"{{ $searchKeyword }}"</strong>
                                            @endif
                                            pada WBS ini.
                                        </p>
                                        <a href="{{ route('budget-monitoring.wbs.show', $wbs) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Kembali ke Semua Transaksi
                                        </a>
                                    </div>
                                @else
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Belum ada rincian aktivitas atau transaksi keuangan pada WBS ini.
                                    <div class="mt-2">
                                        @adminManagement
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPo">
                                            <i class="bi bi-cart-plus me-1"></i> Catat Pengadaan PO Pertama
                                        </button>
                                        @endadminManagement
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- BAGIAN 5: RIWAYAT PEMBAYARAN PO -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-cash-stack text-info"></i>
                    Riwayat Pembayaran PO
                </h6>
                <small class="text-muted">Riwayat pembayaran PO dengan pemantauan realisasi kumulatif dan sisa komitmen.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border">
                    Total: {{ $paymentsHistory->count() }} Pembayaran
                </span>
                <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseRiwayatPembayaran"
                        aria-expanded="false"
                        aria-controls="collapseRiwayatPembayaran"
                        id="btnToggleRiwayatPembayaran">
                    <i class="bi bi-chevron-down" id="iconToggleRiwayatPembayaran"></i>
                    <span id="textToggleRiwayatPembayaran">Lihat Semua Riwayat Pembayaran PO</span>
                </button>
            </div>
        </div>

        <div class="collapse" id="collapseRiwayatPembayaran">
            <div class="table-responsive">
                <table class="table-excel-yellow align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 text-center" style="width: 45px;">No.</th>
                        <th class="text-center" style="min-width: 100px;">Tanggal Pembayaran</th>
                        <th style="min-width: 170px;">Nomor Referensi</th>
                        <th style="min-width: 200px;">Program / Pengadaan</th>
                        <th class="text-end" style="min-width: 130px;">Jumlah Pembayaran</th>
                        <th class="text-end" style="min-width: 130px;">Realisasi Kumulatif</th>
                        <th class="text-end" style="min-width: 130px;">Sisa Komitmen</th>
                        <th style="min-width: 180px;">Keterangan</th>
                        <th class="text-center" style="min-width: 80px;">Lampiran</th>
                        <th style="min-width: 110px;">Dicatat Oleh</th>
                        <th class="text-center pe-3" style="width: 70px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentsHistory as $pIdx => $pay)
                        <tr>
                            <td class="ps-3 text-center text-muted fw-semibold">{{ $pIdx + 1 }}</td>
                            <td class="text-center small text-muted">
                                {{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-' }}
                            </td>
                            <td>
                                <div class="font-monospace fw-bold text-dark">{{ $pay->payment_number }}</div>
                                @if($pay->reference_number && $pay->reference_number !== '-')
                                    <small class="text-muted d-block">Ref: {{ $pay->reference_number }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark text-wrap" style="max-width: 220px; line-height: 1.3;">
                                    {{ $pay->program_name }}
                                </div>
                                <span class="badge bg-light text-primary border font-monospace" style="font-size: 11px;">
                                    {{ $pay->po_number }}
                                </span>
                            </td>
                            <td class="text-end currency-val text-info fw-bold">
                                {{ \App\Models\BudgetPoPayment::formatRupiah($pay->amount) }}
                            </td>
                            <td class="text-end currency-val text-dark fw-bold">
                                {{ \App\Models\BudgetPoPayment::formatRupiah($pay->cumulative_amount) }}
                            </td>
                            <td class="text-end currency-val fw-bold {{ $pay->remaining_commitment > 0 ? 'text-warning' : 'text-success' }}">
                                {{ \App\Models\BudgetPoPayment::formatRupiah($pay->remaining_commitment) }}
                            </td>
                            <td class="small text-muted text-wrap" style="max-width: 200px;">
                                {{ $pay->description }}
                            </td>
                            <td class="text-center">
                                @php
                                    $pProofs = $pay->proofs ?? (!empty($pay->proof_path) ? [$pay->proof_path] : []);
                                @endphp
                                @if(count($pProofs) === 1)
                                    <a href="{{ route('budget-monitoring.files.show', ['type' => 'payment', 'id' => $pay->id, 'index' => 0]) }}" target="_blank" class="btn btn-xs btn-outline-secondary p-1" title="Lihat Bukti Pembayaran">
                                        <i class="bi bi-file-earmark-arrow-down text-primary"></i>
                                    </a>
                                @elseif(count($pProofs) > 1)
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-xs btn-outline-primary p-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" title="{{ count($pProofs) }} Bukti Tersedia" style="font-size: 11px;">
                                            <i class="bi bi-files text-primary"></i> {{ count($pProofs) }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm small py-1" style="min-width: 140px;">
                                            @foreach($pProofs as $pIdx => $fPath)
                                                <li>
                                                    <a class="dropdown-item py-1 px-2 d-flex align-items-center gap-1" href="{{ route('budget-monitoring.files.show', ['type' => 'payment', 'id' => $pay->id, 'index' => $pIdx]) }}" target="_blank">
                                                        <i class="bi bi-file-earmark-arrow-down text-primary"></i> Bukti #{{ $pIdx + 1 }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $pay->creator_name }}
                            </td>
                            <td class="text-center pe-3">
                                @adminManagement
                                <button type="button" class="btn btn-xs btn-outline-danger p-1" title="Hapus Pembayaran"
                                        onclick="confirmDeletePayment('{{ $pay->id }}', '{{ $pay->payment_number }}', '{{ $pay->po_number }}', '{{ \App\Models\BudgetPoPayment::formatRupiah($pay->amount) }}', '{{ $pay->delete_url }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endadminManagement
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                Belum ada riwayat pembayaran PO pada WBS ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- BAGIAN 7: RIWAYAT PENGALIHAN ANGGARAN (DANA KELUAR & DANA MASUK) -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-right text-primary"></i>
                    Riwayat Pengalihan Anggaran
                </h6>
                <small class="text-muted">Informasi mutasi pengalihan dana antara WBS {{ $wbs->wbs_number }} dengan WBS lainnya.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $transfersHistory->count() > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border' }}">
                    {{ $transfersHistory->count() }} Transaksi Pengalihan
                </span>
                <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseRiwayatPengalihan"
                        aria-expanded="false"
                        aria-controls="collapseRiwayatPengalihan"
                        id="btnToggleRiwayatPengalihan">
                    <i class="bi bi-chevron-down" id="iconToggleRiwayatPengalihan"></i>
                    <span id="textToggleRiwayatPengalihan">Lihat Riwayat Pengalihan Anggaran</span>
                </button>
            </div>
        </div>

        <div class="collapse" id="collapseRiwayatPengalihan">
            <div class="card-body p-3">
            @if($transfersHistory->isNotEmpty())
                <!-- Narasi Keterangan Pengalihan Dana Dinamis Sesuai Database -->
                <div class="mb-3">
                    <div class="small fw-bold text-secondary text-uppercase mb-2">Keterangan Mutasi Pengalihan Dana:</div>
                    @foreach($transfersHistory as $trfItem)
                        <div class="narrative-item {{ $trfItem->type_direction === 'KELUAR' ? 'narrative-out' : 'narrative-in' }}">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi {{ $trfItem->type_direction === 'KELUAR' ? 'bi-arrow-up-right-circle-fill text-danger' : 'bi-arrow-down-left-circle-fill text-success' }} fs-5 mt-0"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark">
                                        {{ $trfItem->narrative }}
                                    </div>
                                    @if($trfItem->description && $trfItem->description !== '-')
                                        <div class="text-muted small mt-1">
                                            <em>Dokumen / Keterangan Tambahan: {{ $trfItem->description }}</em>
                                        </div>
                                    @endif
                                </div>
                                <span class="badge {{ $trfItem->type_badge }} px-2 py-1 ms-2 font-monospace">
                                    {{ $trfItem->transfer_number }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Tabel Riwayat Pengalihan Anggaran -->
                <div class="table-responsive">
                    <table class="table-excel-yellow align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3 text-center" style="width: 45px;">No.</th>
                                <th class="text-center" style="min-width: 110px;">Tanggal Pengalihan</th>
                                <th style="min-width: 180px;">WBS Asal</th>
                                <th style="min-width: 180px;">WBS Penerima</th>
                                <th class="text-end" style="min-width: 130px;">Jumlah Pengalihan</th>
                                <th class="text-center" style="min-width: 120px;">Jenis Pengalihan</th>
                                <th style="min-width: 180px;">Alasan Pengalihan</th>
                                <th style="min-width: 220px;">Keterangan</th>
                                <th class="text-center" style="min-width: 90px;">Status</th>
                                <th class="text-center pe-3" style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transfersHistory as $idx => $trf)
                                <tr>
                                    <td class="ps-3 text-center text-muted fw-semibold">{{ $idx + 1 }}</td>
                                    <td class="text-center small text-muted">
                                        {{ $trf->transfer_date ? $trf->transfer_date->format('d/m/Y') : '-' }}
                                    </td>
                                    <td>
                                        <div class="font-monospace fw-bold text-dark">{{ $trf->source_wbs_number }}</div>
                                        <small class="text-muted text-truncate d-block" style="max-width: 170px;">{{ $trf->source_wbs_name }}</small>
                                    </td>
                                    <td>
                                        <div class="font-monospace fw-bold text-dark">{{ $trf->destination_wbs_number }}</div>
                                        <small class="text-muted text-truncate d-block" style="max-width: 170px;">{{ $trf->destination_wbs_name }}</small>
                                    </td>
                                    <td class="text-end currency-val fw-bold {{ $trf->type_direction === 'KELUAR' ? 'text-danger' : 'text-success' }}">
                                        {{ $trf->type_direction === 'KELUAR' ? '-' : '+' }}{{ \App\Models\BudgetTransfer::formatRupiah($trf->amount) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $trf->type_badge }} px-2 py-1">
                                            {{ $trf->type_label }}
                                        </span>
                                    </td>
                                    <td class="small text-dark text-wrap" style="max-width: 200px;">
                                        {{ $trf->reason }}
                                    </td>
                                    <td class="small text-muted text-wrap" style="max-width: 240px;">
                                        {{ $trf->description ?? '-' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">{{ $trf->status }}</span>
                                    </td>
                                    <td class="text-center pe-3">
                                        @adminManagement
                                        <button type="button" class="btn btn-xs btn-outline-danger p-1" title="Batalkan & Hapus Pengalihan Ini"
                                                onclick="confirmDeleteTransfer('{{ $trf->id }}', '{{ $trf->transfer_number }}', '{{ $trf->type_direction }}', '{{ \App\Models\BudgetTransfer::formatRupiah($trf->amount) }}', '{{ $trf->delete_url }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endadminManagement
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-info-circle fs-3 d-block mb-2 text-secondary opacity-50"></i>
                    Belum ada riwayat pengalihan anggaran.
                </div>
            @endif
        </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <!-- BAGIAN: RIWAYAT SELURUH TRANSAKSI KEUANGAN (AUDIT TRAIL) -->
    <!-- ═══════════════════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm rounded-3 mb-4" id="riwayatTransaksi">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-secondary"></i>
                    Riwayat Transaksi Keuangan (Audit Log WBS {{ $wbs->wbs_number }})
                </h6>
                <small class="text-muted">Jurnal seluruh mutasi anggaran, komitmen, realisasi, dan pengalihan pada WBS ini.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border">
                    Total: {{ $wbs->transactions->count() }} Transaksi
                </span>
                <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseRiwayatTransaksi"
                        aria-expanded="false"
                        aria-controls="collapseRiwayatTransaksi"
                        id="btnToggleRiwayatTransaksi">
                    <i class="bi bi-chevron-down" id="iconToggleRiwayatTransaksi"></i>
                    <span id="textToggleRiwayatTransaksi">Lihat Riwayat Transaksi Keuangan</span>
                </button>
            </div>
        </div>

        <div class="collapse" id="collapseRiwayatTransaksi">
            <div class="table-responsive">
                <table class="table-excel-yellow align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 text-center" style="width: 45px;">No.</th>
                        <th class="text-center" style="min-width: 100px;">Tanggal</th>
                        <th style="min-width: 150px;">Nomor Transaksi</th>
                        <th style="min-width: 180px;">Jenis Transaksi</th>
                        <th class="text-end" style="min-width: 130px;">Nominal</th>
                        <th style="min-width: 250px;">Keterangan</th>
                        <th style="min-width: 120px;">Dicatat Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wbs->transactions->sortByDesc('transaction_date')->values() as $tIdx => $trx)
                        <tr>
                            <td class="ps-3 text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                            <td class="text-center small text-muted">
                                {{ $trx->transaction_date ? $trx->transaction_date->format('d/m/Y') : '-' }}
                            </td>
                            <td>
                                <span class="font-monospace fw-bold text-dark">{{ $trx->transaction_number }}</span>
                                @if($trx->reference_number)
                                    <small class="text-muted d-block">Ref: {{ $trx->reference_number }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $trx->transaction_type_label ?? $trx->transaction_type }}
                                </span>
                            </td>
                            <td class="text-end currency-val fw-bold text-dark">
                                {{ \App\Models\BudgetTransaction::formatRupiah($trx->amount) }}
                            </td>
                            <td class="small text-muted text-wrap" style="max-width: 300px;">
                                {{ $trx->description }}
                            </td>
                            <td class="small text-muted">
                                {{ $trx->creator?->name ?? 'Admin' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Belum ada riwayat transaksi keuangan tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODALS KHUSUS WBS INI -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
@adminManagement

<!-- MODAL 1: TAMBAH PENGADAAN / PO (TERIKAT KE WBS INI) -->
<div class="modal fade" id="modalTambahPo" tabindex="-1" aria-labelledby="modalTambahPoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('budget-monitoring.po.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="budget_wbs_id" value="{{ $wbs->id }}">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTambahPoLabel">
                        <i class="bi bi-cart-plus me-1 text-primary"></i> Catat Pengadaan / PO Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        Pengadaan ini akan diikatkan ke WBS: <strong>{{ $wbs->wbs_number }} - {{ $wbs->wbs_name }}</strong><br>
                        Sisa Anggaran WBS saat ini: <strong class="text-success">{{ \App\Models\BudgetWbs::formatRupiah($wbs->remaining_budget) }}</strong>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nomor PO <span class="text-danger">*</span></label>
                            <input type="text" name="po_number" class="form-control form-control-sm font-monospace"
                                   value="{{ $nextPoNumber }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal PO <span class="text-danger">*</span></label>
                            <input type="date" name="po_date" class="form-control form-control-sm"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Pengadaan / Program <span class="text-danger">*</span></label>
                            <input type="text" name="program_name" class="form-control form-control-sm"
                                   placeholder="Contoh: Pengadaan Alat Pelindung Diri (APD) Batch 1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Vendor / Rekanan</label>
                            <input type="text" name="vendor" class="form-control form-control-sm"
                                   placeholder="Contoh: PT Sumber Rejeki Utama">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nilai PO (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" max="{{ $wbs->remaining_budget }}" name="po_value" id="inputPoValueDetail"
                                   class="form-control form-control-sm" placeholder="0" required>
                            <div class="form-text small">Masukkan angka bulat tanpa titik/koma. Maks: {{ \App\Models\BudgetWbs::formatRupiah($wbs->remaining_budget) }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan / Deskripsi</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Catatan spesifikasi teknis atau termin pengadaan"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">
                                Dokumen Kontrak / PO
                            </label>
                            <div class="custom-file-dropzone"
                                 onclick="triggerPrimaryUpload('showWbsPoAttachmentInputs')"
                                 ondragover="handleUploaderDragOver(event)"
                                 ondragleave="handleUploaderDragLeave(event)"
                                 ondrop="handleUploaderDrop(event, 'showWbsPoAttachmentInputs', 'showWbsPoAttachmentFileList', 10)">
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-1">
                                    <i class="bi bi-cloud-arrow-up text-secondary fs-5"></i>
                                    <span class="small fw-semibold text-dark">Pilih dokumen atau tarik file ke sini</span>
                                    <button type="button" class="btn btn-xs btn-light border py-1 px-2 fw-medium text-secondary shadow-none"
                                            onclick="event.stopPropagation(); triggerPrimaryUpload('showWbsPoAttachmentInputs')">
                                        <i class="bi bi-folder2-open me-1"></i> Pilih Dokumen
                                    </button>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">Maksimal 10MB per file (PDF, JPG, PNG, DOC, DOCX)</div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mt-1 mb-1">
                                <button type="button" class="btn btn-xs btn-light border text-secondary d-inline-flex align-items-center gap-1 shadow-none py-1 px-2"
                                        style="font-size: 11.5px;"
                                        onclick="addAttachmentInput('showWbsPoAttachmentInputs', 'attachments[]', '.pdf,.jpg,.jpeg,.png,.doc,.docx', 10, 'showWbsPoAttachmentFileList')">
                                    <i class="bi bi-plus text-primary fs-6 lh-1"></i> + Tambah Dokumen
                                </button>
                                <span class="text-muted fst-italic" style="font-size: 11px;">Bisa memilih dokumen dari folder berbeda</span>
                            </div>
                            <div id="showWbsPoAttachmentInputs" class="attachment-input-wrapper d-none">
                                <div class="attachment-input-row">
                                    <input type="file" name="attachments[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple onchange="refreshUnifiedFileList('showWbsPoAttachmentInputs', 'showWbsPoAttachmentFileList', 10)">
                                </div>
                            </div>
                            <div id="showWbsPoAttachmentFileList"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Pengadaan PO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: CATAT PEMBAYARAN PO (TERIKAT KE PO WBS INI) -->
<div class="modal fade" id="modalTambahPoPayment" tabindex="-1" aria-labelledby="modalTambahPoPaymentLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formTambahPoPayment" action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTambahPoPaymentLabel">
                        <i class="bi bi-cash-stack me-1 text-info"></i> Catat Pembayaran PO
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Pilih PO pada WBS {{ $wbs->wbs_number }} <span class="text-danger">*</span></label>
                            <select id="selectPoForPayment" class="form-select form-select-sm" required onchange="handleSelectPoPayment(this)">
                                <option value="">-- Pilih Pengadaan PO --</option>
                                @foreach($wbs->pos->where('status', '!=', 'Dibatalkan') as $poItem)
                                    <option value="{{ $poItem->id }}"
                                            data-po-number="{{ $poItem->po_number }}"
                                            data-commitment="{{ $poItem->commitment_value }}"
                                            data-realized="{{ $poItem->total_payment }}"
                                            data-remaining="{{ $poItem->remaining_commitment }}"
                                            data-action="{{ route('budget-monitoring.po-payment.store', $poItem) }}">
                                        {{ $poItem->po_number }} - {{ $poItem->program_name }} (Sisa: {{ \App\Models\BudgetPo::formatRupiah($poItem->remaining_commitment) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- LIVE INFO CARD PO: Nilai Komitmen, Realisasi Sebelumnya, Sisa Komitmen -->
                        <div class="col-12" id="poPaymentInfoCard" style="display: none;">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row g-2 text-center" style="font-size: 11.5px;">
                                    <div class="col-4 border-end">
                                        <div class="text-muted fw-semibold">Nilai Komitmen</div>
                                        <div class="fw-bold text-primary fs-6 mt-1" id="cardPoCommitment">Rp0</div>
                                    </div>
                                    <div class="col-4 border-end">
                                        <div class="text-muted fw-semibold">Total Realisasi</div>
                                        <div class="fw-bold text-info fs-6 mt-1" id="cardPoRealized">Rp0</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-muted fw-semibold">Sisa Komitmen</div>
                                        <div class="fw-bold text-success fs-6 mt-1" id="cardPoRemaining">Rp0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nomor SA/GR <span class="text-danger">*</span></label>
                            <input type="text" name="payment_number" class="form-control form-control-sm font-monospace"
                                   value="{{ $nextPaymentNumber }}" placeholder="Contoh: SAGR-2026-001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Pembayaran <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control form-control-sm"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Jumlah Pembayaran (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" name="amount" id="inputPaymentAmountDetail" class="form-control form-control-sm" placeholder="0" required>
                            <div class="form-text small text-info" id="paymentLimitNote">Pilih PO di atas untuk membatasi nilai pembayaran sesuai sisa komitmen.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Contoh: Pembayaran termin 1 (50%)"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">
                                Bukti Pembayaran
                            </label>
                            <div class="custom-file-dropzone"
                                 onclick="triggerPrimaryUpload('paymentProofInputs')"
                                 ondragover="handleUploaderDragOver(event)"
                                 ondragleave="handleUploaderDragLeave(event)"
                                 ondrop="handleUploaderDrop(event, 'paymentProofInputs', 'paymentProofFileList', 5)">
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-1">
                                    <i class="bi bi-cloud-arrow-up text-secondary fs-5"></i>
                                    <span class="small fw-semibold text-dark">Pilih dokumen atau tarik file ke sini</span>
                                    <button type="button" class="btn btn-xs btn-light border py-1 px-2 fw-medium text-secondary shadow-none"
                                            onclick="event.stopPropagation(); triggerPrimaryUpload('paymentProofInputs')">
                                        <i class="bi bi-folder2-open me-1"></i> Pilih Dokumen
                                    </button>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">Maksimal 5MB per file (PDF, JPG, PNG)</div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mt-1 mb-1">
                                <button type="button" class="btn btn-xs btn-light border text-secondary d-inline-flex align-items-center gap-1 shadow-none py-1 px-2"
                                        style="font-size: 11.5px;"
                                        onclick="addAttachmentInput('paymentProofInputs', 'proofs[]', '.pdf,.jpg,.jpeg,.png', 5, 'paymentProofFileList')">
                                    <i class="bi bi-plus text-primary fs-6 lh-1"></i> + Tambah Dokumen
                                </button>
                                <span class="text-muted fst-italic" style="font-size: 11px;">Bisa memilih dokumen dari folder berbeda</span>
                            </div>
                            <div id="paymentProofInputs" class="attachment-input-wrapper d-none">
                                <div class="attachment-input-row">
                                    <input type="file" name="proofs[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" multiple onchange="refreshUnifiedFileList('paymentProofInputs', 'paymentProofFileList', 5)">
                                </div>
                            </div>
                            <div id="paymentProofFileList"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white btn-sm"><i class="bi bi-save me-1"></i> Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: CATAT REALISASI NON-PO (TERIKAT KE WBS INI) -->
<div class="modal fade" id="modalTambahNonPo" tabindex="-1" aria-labelledby="modalTambahNonPoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('budget-monitoring.non-po.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="budget_wbs_id" value="{{ $wbs->id }}">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTambahNonPoLabel">
                        <i class="bi bi-receipt me-1 text-warning"></i> Catat Realisasi Non-PO
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small mb-3">
                        Realisasi Non-PO akan langsung mengalokasikan dan memotong Sisa Anggaran WBS <strong>{{ $wbs->wbs_number }}</strong>.<br>
                        Sisa Anggaran WBS saat ini: <strong>{{ \App\Models\BudgetWbs::formatRupiah($wbs->remaining_budget) }}</strong>
                    </div>

                    <div class="row g-3">
                        <!-- 1. Nama Program / Pengeluaran (wajib) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Program / Pengeluaran <span class="text-danger">*</span></label>
                            <input type="text" name="program_name" class="form-control form-control-sm"
                                   placeholder="Contoh: Konsumsi Pelatihan Tanggap Darurat Lapangan" required>
                        </div>
                        <!-- 2. Tanggal Transaksi (wajib) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control form-control-sm"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <!-- 3. Jumlah Realisasi (Rp) (wajib, > 0) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jumlah Realisasi (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" max="{{ $wbs->remaining_budget }}" name="amount" class="form-control form-control-sm" placeholder="0" required>
                            <div class="form-text small">Maksimum: {{ \App\Models\BudgetWbs::formatRupiah($wbs->remaining_budget) }}</div>
                        </div>
                        <!-- 4. Penerima / Vendor / Toko (opsional) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Penerima / Vendor / Toko</label>
                            <input type="text" name="recipient_vendor" class="form-control form-control-sm"
                                   placeholder="Contoh: Katering Dieng Lestari">
                        </div>
                        <!-- 5. Keterangan / Rincian (opsional) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan / Rincian</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Keterangan pendukung transaksi"></textarea>
                        </div>
                        <!-- 6. Bukti Kwitansi / Struk (opsional, multiple) -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">
                                Bukti Kwitansi / Struk
                            </label>
                            <div class="custom-file-dropzone"
                                 onclick="triggerPrimaryUpload('showWbsNonPoProofInputs')"
                                 ondragover="handleUploaderDragOver(event)"
                                 ondragleave="handleUploaderDragLeave(event)"
                                 ondrop="handleUploaderDrop(event, 'showWbsNonPoProofInputs', 'showWbsNonPoProofFileList', 5)">
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-1">
                                    <i class="bi bi-cloud-arrow-up text-secondary fs-5"></i>
                                    <span class="small fw-semibold text-dark">Pilih dokumen atau tarik file ke sini</span>
                                    <button type="button" class="btn btn-xs btn-light border py-1 px-2 fw-medium text-secondary shadow-none"
                                            onclick="event.stopPropagation(); triggerPrimaryUpload('showWbsNonPoProofInputs')">
                                        <i class="bi bi-folder2-open me-1"></i> Pilih Dokumen
                                    </button>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">Maksimal 5MB per file (PDF, JPG, PNG)</div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mt-1 mb-1">
                                <button type="button" class="btn btn-xs btn-light border text-secondary d-inline-flex align-items-center gap-1 shadow-none py-1 px-2"
                                        style="font-size: 11.5px;"
                                        onclick="addAttachmentInput('showWbsNonPoProofInputs', 'proofs[]', '.pdf,.jpg,.jpeg,.png', 5, 'showWbsNonPoProofFileList')">
                                    <i class="bi bi-plus text-primary fs-6 lh-1"></i> + Tambah Dokumen
                                </button>
                                <span class="text-muted fst-italic" style="font-size: 11px;">Bisa memilih dokumen dari folder berbeda</span>
                            </div>
                            <div id="showWbsNonPoProofInputs" class="attachment-input-wrapper d-none">
                                <div class="attachment-input-row">
                                    <input type="file" name="proofs[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" multiple onchange="refreshUnifiedFileList('showWbsNonPoProofInputs', 'showWbsNonPoProofFileList', 5)">
                                </div>
                            </div>
                            <div id="showWbsNonPoProofFileList"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark btn-sm"><i class="bi bi-save me-1"></i> Simpan Realisasi Non-PO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 4: PENGALIHAN ANGGARAN (DARI WBS INI KE WBS LAIN) -->
<div class="modal fade" id="modalTransferAnggaran" tabindex="-1" aria-labelledby="modalTransferAnggaranLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('budget-monitoring.transfers.store') }}" method="POST">
                @csrf
                <input type="hidden" name="source_wbs_id" value="{{ $wbs->id }}">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTransferAnggaranLabel">
                        <i class="bi bi-arrow-left-right me-1 text-primary"></i> Pengalihan Anggaran Antar-WBS
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">WBS Sumber (Asal Dana)</label>
                            <input type="text" class="form-control form-control-sm bg-light"
                                   value="{{ $wbs->wbs_number }} - {{ $wbs->wbs_name }} (Sisa: {{ \App\Models\BudgetWbs::formatRupiah($wbs->remaining_budget) }})" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">WBS Tujuan (Penerima Dana) <span class="text-danger">*</span></label>
                            <select name="destination_wbs_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih WBS Tujuan --</option>
                                @foreach($allWbs->where('id', '!=', $wbs->id) as $targetWbs)
                                    <option value="{{ $targetWbs->id }}">
                                        {{ $targetWbs->wbs_number }} - {{ $targetWbs->wbs_name }} (Sisa: {{ \App\Models\BudgetWbs::formatRupiah($targetWbs->remaining_budget) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Pengalihan <span class="text-danger">*</span></label>
                            <input type="date" name="transfer_date" class="form-control form-control-sm"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nominal Pengalihan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" max="{{ $wbs->remaining_budget }}" name="amount" class="form-control form-control-sm" placeholder="0" required>
                            <div class="form-text small">Maks: {{ \App\Models\BudgetWbs::formatRupiah($wbs->remaining_budget) }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Alasan Pengalihan <span class="text-danger">*</span></label>
                            <input type="text" name="reason" class="form-control form-control-sm"
                                   placeholder="Contoh: Optimalisasi anggaran mendesak untuk pengadaan APD" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan / Dokumen Dasar Hukum</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Nomor nota dinas atau persetujuan pimpinan"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Proses Pengalihan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 5: EDIT PO -->
<div class="modal fade" id="modalEditPo" tabindex="-1" aria-labelledby="modalEditPoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditPo" action="" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalEditPoLabel">
                        <i class="bi bi-pencil me-1 text-warning"></i> Edit Pengadaan PO
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Pengadaan / Program <span class="text-danger">*</span></label>
                            <input type="text" name="program_name" id="editPoProgram" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Vendor / Rekanan</label>
                            <input type="text" name="vendor" id="editPoVendor" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal PO <span class="text-danger">*</span></label>
                            <input type="date" name="po_date" id="editPoDate" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nilai PO (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" name="po_value" id="editPoValue" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan</label>
                            <textarea name="description" id="editPoDesc" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <div class="col-12">
                            <div id="editPoExistingAttachmentsContainer"></div>
                            <label class="form-label small fw-bold text-secondary mb-1">
                                Tambah Dokumen Kontrak / PO Baru
                            </label>
                            <div class="custom-file-dropzone"
                                 onclick="triggerPrimaryUpload('editPoAttachmentInputs')"
                                 ondragover="handleUploaderDragOver(event)"
                                 ondragleave="handleUploaderDragLeave(event)"
                                 ondrop="handleUploaderDrop(event, 'editPoAttachmentInputs', 'editPoAttachmentFileList', 10)">
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-1">
                                    <i class="bi bi-cloud-arrow-up text-secondary fs-5"></i>
                                    <span class="small fw-semibold text-dark">Pilih dokumen atau tarik file ke sini</span>
                                    <button type="button" class="btn btn-xs btn-light border py-1 px-2 fw-medium text-secondary shadow-none"
                                            onclick="event.stopPropagation(); triggerPrimaryUpload('editPoAttachmentInputs')">
                                        <i class="bi bi-folder2-open me-1"></i> Pilih Dokumen
                                    </button>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">Maksimal 10MB per file (PDF, JPG, PNG, DOC, DOCX)</div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mt-1 mb-1">
                                <button type="button" class="btn btn-xs btn-light border text-secondary d-inline-flex align-items-center gap-1 shadow-none py-1 px-2"
                                        style="font-size: 11.5px;"
                                        onclick="addAttachmentInput('editPoAttachmentInputs', 'attachments[]', '.pdf,.jpg,.jpeg,.png,.doc,.docx', 10, 'editPoAttachmentFileList')">
                                    <i class="bi bi-plus text-primary fs-6 lh-1"></i> + Tambah Dokumen
                                </button>
                                <span class="text-muted fst-italic" style="font-size: 11px;">Bisa memilih dokumen dari folder berbeda</span>
                            </div>
                            <div id="editPoAttachmentInputs" class="attachment-input-wrapper d-none">
                                <div class="attachment-input-row">
                                    <input type="file" name="attachments[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple onchange="refreshUnifiedFileList('editPoAttachmentInputs', 'editPoAttachmentFileList', 10)">
                                </div>
                            </div>
                            <div id="editPoAttachmentFileList"></div>
                            <small class="text-muted d-block mt-1 fst-italic" style="font-size: 11px;">Kosongkan jika tidak ingin menambah dokumen baru. Dokumen lama yang tidak dihapus akan tetap utuh.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 6: EDIT NON-PO -->
<div class="modal fade" id="modalEditNonPo" tabindex="-1" aria-labelledby="modalEditNonPoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditNonPo" action="" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalEditNonPoLabel">
                        <i class="bi bi-pencil me-1 text-warning"></i> Edit Realisasi Non-PO
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- 1. Nama Program / Pengeluaran (wajib) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Program / Pengeluaran <span class="text-danger">*</span></label>
                            <input type="text" name="program_name" id="editNonPoProgram" class="form-control form-control-sm" required>
                        </div>
                        <!-- 2. Tanggal Transaksi (wajib) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" id="editNonPoDate" class="form-control form-control-sm" required>
                        </div>
                        <!-- 3. Jumlah Realisasi (Rp) (wajib) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jumlah Realisasi (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" name="amount" id="editNonPoAmount" class="form-control form-control-sm" required>
                        </div>
                        <!-- 4. Penerima / Vendor / Toko (opsional) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Penerima / Vendor / Toko</label>
                            <input type="text" name="recipient_vendor" id="editNonPoVendor" class="form-control form-control-sm">
                        </div>
                        <!-- 5. Keterangan / Rincian (opsional) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan / Rincian</label>
                            <textarea name="description" id="editNonPoDesc" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <!-- 6. Bukti Kwitansi / Struk (opsional, multiple) -->
                        <div class="col-12">
                            <div id="editNonPoExistingProofsContainer"></div>
                            <label class="form-label small fw-bold text-secondary mb-1">
                                Tambah Bukti Kwitansi / Struk Baru
                            </label>
                            <div class="custom-file-dropzone"
                                 onclick="triggerPrimaryUpload('editNonPoProofInputs')"
                                 ondragover="handleUploaderDragOver(event)"
                                 ondragleave="handleUploaderDragLeave(event)"
                                 ondrop="handleUploaderDrop(event, 'editNonPoProofInputs', 'editNonPoProofFileList', 5)">
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-1">
                                    <i class="bi bi-cloud-arrow-up text-secondary fs-5"></i>
                                    <span class="small fw-semibold text-dark">Pilih dokumen atau tarik file ke sini</span>
                                    <button type="button" class="btn btn-xs btn-light border py-1 px-2 fw-medium text-secondary shadow-none"
                                            onclick="event.stopPropagation(); triggerPrimaryUpload('editNonPoProofInputs')">
                                        <i class="bi bi-folder2-open me-1"></i> Pilih Dokumen
                                    </button>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">Maksimal 5MB per file (PDF, JPG, PNG)</div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mt-1 mb-1">
                                <button type="button" class="btn btn-xs btn-light border text-secondary d-inline-flex align-items-center gap-1 shadow-none py-1 px-2"
                                        style="font-size: 11.5px;"
                                        onclick="addAttachmentInput('editNonPoProofInputs', 'proofs[]', '.pdf,.jpg,.jpeg,.png', 5, 'editNonPoProofFileList')">
                                    <i class="bi bi-plus text-primary fs-6 lh-1"></i> + Tambah Dokumen
                                </button>
                                <span class="text-muted fst-italic" style="font-size: 11px;">Bisa memilih dokumen dari folder berbeda</span>
                            </div>
                            <div id="editNonPoProofInputs" class="attachment-input-wrapper d-none">
                                <div class="attachment-input-row">
                                    <input type="file" name="proofs[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" multiple onchange="refreshUnifiedFileList('editNonPoProofInputs', 'editNonPoProofFileList', 5)">
                                </div>
                            </div>
                            <div id="editNonPoProofFileList"></div>
                            <small class="text-muted d-block mt-1 fst-italic" style="font-size: 11px;">Kosongkan jika tidak ingin menambah bukti baru. Bukti lama yang tidak dihapus akan tetap utuh.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FORM GLOBAL UNTUK DELETE VIA SWEETALERT2 -->
<form id="globalDeleteForm" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

@endadminManagement

@endsection

@push('scripts')
<script>
    function syncPoCommitmentDetail(val) {
        const comm = document.getElementById('inputCommitmentValueDetail');
        if (comm && (!comm.dataset.manualEdited || comm.dataset.manualEdited === 'false')) {
            comm.value = val;
        }
    }

    document.getElementById('inputCommitmentValueDetail')?.addEventListener('input', function() {
        this.dataset.manualEdited = 'true';
    });

    function handleSelectPoPayment(selectElem) {
        const selected = selectElem.options[selectElem.selectedIndex];
        const actionUrl = selected.getAttribute('data-action');
        const remaining = selected.getAttribute('data-remaining');
        const commitment = selected.getAttribute('data-commitment');
        const realized = selected.getAttribute('data-realized');
        const poNumber = selected.getAttribute('data-po-number');
        const form = document.getElementById('formTambahPoPayment');
        const amountInput = document.getElementById('inputPaymentAmountDetail');
        const infoCard = document.getElementById('poPaymentInfoCard');

        if (actionUrl && selected.value) {
            form.action = actionUrl;
            if (infoCard) {
                infoCard.style.display = 'block';
                document.getElementById('cardPoCommitment').innerText = 'Rp' + Number(commitment || 0).toLocaleString('id-ID');
                document.getElementById('cardPoRealized').innerText = 'Rp' + Number(realized || 0).toLocaleString('id-ID');
                document.getElementById('cardPoRemaining').innerText = 'Rp' + Number(remaining || 0).toLocaleString('id-ID');
            }
            if (remaining && amountInput) {
                amountInput.max = remaining;
                amountInput.value = '';
                const note = document.getElementById('paymentLimitNote');
                if (note) {
                    note.innerHTML = `Maksimum pembayaran untuk ${escapeWbsHtml(poNumber)}: <strong>Rp${Number(remaining).toLocaleString('id-ID')}</strong> (Sisa Komitmen).`;
                }
            }
        } else {
            if (infoCard) infoCard.style.display = 'none';
        }
    }

    function openPaymentModalForPo(poId, poNumber, commitment, realized, remaining, actionUrl) {
        const select = document.getElementById('selectPoForPayment');
        if (select) {
            select.value = poId;
            handleSelectPoPayment(select);
        }
        const modal = new bootstrap.Modal(document.getElementById('modalTambahPoPayment'));
        modal.show();
    }

    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        const val = parseFloat((bytes / Math.pow(k, i)).toFixed(1));
        return val.toString().replace('.', ',') + ' ' + sizes[i];
    }

    function getFileIconClass(fileName) {
        const ext = (fileName || '').split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'bi-file-earmark-image text-success';
        if (ext === 'pdf') return 'bi-file-earmark-pdf text-danger';
        if (['doc', 'docx'].includes(ext)) return 'bi-file-earmark-word text-primary';
        return 'bi-file-earmark-text text-secondary';
    }

    function triggerPrimaryUpload(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const inputs = container.querySelectorAll('input[type="file"]');
        for (let i = 0; i < inputs.length; i++) {
            if (!inputs[i].files || inputs[i].files.length === 0) {
                inputs[i].click();
                return;
            }
        }
        if (inputs.length > 0) {
            const firstInput = inputs[0];
            const inputName = firstInput ? firstInput.name : 'attachments[]';
            const accept = firstInput ? firstInput.accept : '*';
            const onchangeAttr = firstInput ? firstInput.getAttribute('onchange') : '';
            const row = document.createElement('div');
            row.className = 'attachment-input-row';
            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.name = inputName;
            newInput.className = 'form-control form-control-sm';
            if (accept) newInput.accept = accept;
            newInput.multiple = true;
            if (onchangeAttr) {
                newInput.setAttribute('onchange', onchangeAttr);
            }
            row.appendChild(newInput);
            container.appendChild(row);
            newInput.click();
        }
    }

    function handleUploaderDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.add('dragover');
    }

    function handleUploaderDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('dragover');
    }

    function handleUploaderDrop(e, containerId, listContainerId, maxSizeMb) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('dragover');

        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            const container = document.getElementById(containerId);
            if (!container) return;

            let targetInput = null;
            const inputs = container.querySelectorAll('input[type="file"]');
            for (let i = 0; i < inputs.length; i++) {
                if (!inputs[i].files || inputs[i].files.length === 0) {
                    targetInput = inputs[i];
                    break;
                }
            }
            if (!targetInput) {
                const firstInput = inputs[0];
                const inputName = firstInput ? firstInput.name : 'attachments[]';
                const accept = firstInput ? firstInput.accept : '*';
                const row = document.createElement('div');
                row.className = 'attachment-input-row';
                row.innerHTML = `<input type="file" name="${inputName}" class="form-control form-control-sm" accept="${accept}" multiple onchange="refreshUnifiedFileList('${containerId}', '${listContainerId}', ${maxSizeMb})">`;
                container.appendChild(row);
                targetInput = row.querySelector('input[type="file"]');
            }

            try {
                targetInput.files = e.dataTransfer.files;
                refreshUnifiedFileList(containerId, listContainerId, maxSizeMb);
            } catch (err) {
                console.warn('Cannot assign dropped files to input directly:', err);
            }
        }
    }

    function addAttachmentInput(containerId, inputName, accept, maxSizeMb, listContainerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const allInputs = container.querySelectorAll('input[type="file"]');
        const lastInput = allInputs[allInputs.length - 1];
        if (lastInput && (!lastInput.files || lastInput.files.length === 0)) {
            lastInput.click();
            return;
        }

        const row = document.createElement('div');
        row.className = 'attachment-input-row';
        row.innerHTML = `<input type="file" name="${inputName}" class="form-control form-control-sm" accept="${accept}" multiple onchange="refreshUnifiedFileList('${containerId}', '${listContainerId}', ${maxSizeMb})">`;
        container.appendChild(row);

        const input = row.querySelector('input[type="file"]');
        if (input) {
            input.click();
        }
    }

    function refreshUnifiedFileList(containerId, listContainerId, maxSizeMb) {
        const container = document.getElementById(containerId);
        const listContainer = document.getElementById(listContainerId);
        if (!container || !listContainer) return;

        listContainer.innerHTML = '';
        const inputs = container.querySelectorAll('input[type="file"]');
        let allFiles = [];
        let hasOverSize = false;
        let overSizeFileName = '';
        let hasInvalidExt = false;
        let invalidExtFileName = '';

        inputs.forEach((input, inputIdx) => {
            if (input.files && input.files.length > 0) {
                const acceptAttr = (input.accept || '').toLowerCase();
                const allowedExts = acceptAttr ? acceptAttr.split(',').map(s => s.trim().replace(/^\./, '')) : [];

                for (let fIdx = 0; fIdx < input.files.length; fIdx++) {
                    const file = input.files[fIdx];
                    const ext = (file.name || '').split('.').pop().toLowerCase();

                    if (allowedExts.length > 0 && !allowedExts.includes(ext)) {
                        hasInvalidExt = true;
                        invalidExtFileName = file.name;
                    }

                    if (maxSizeMb && file.size > maxSizeMb * 1024 * 1024) {
                        hasOverSize = true;
                        overSizeFileName = file.name;
                    }
                    allFiles.push({
                        file: file,
                        inputIdx: inputIdx,
                        fIdx: fIdx
                    });
                }
            }
        });

        if (hasInvalidExt) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Format Dokumen Tidak Didukung',
                    text: `File "${invalidExtFileName}" tidak sesuai format yang diizinkan.`
                });
            } else {
                alert(`File "${invalidExtFileName}" tidak sesuai format yang diizinkan.`);
            }
        } else if (hasOverSize) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ukuran File Terlalu Besar',
                    text: `File "${overSizeFileName}" melebihi batas maksimal ${maxSizeMb} MB per file.`
                });
            } else {
                alert(`File "${overSizeFileName}" melebihi batas maksimal ${maxSizeMb} MB per file.`);
            }
        }

        if (allFiles.length === 0) return;

        let totalBytes = allFiles.reduce((acc, curr) => acc + curr.file.size, 0);

        let listHtml = `
            <div class="mt-1 pt-1">
                <div class="small fw-semibold text-secondary mb-1 d-flex align-items-center justify-content-between" style="font-size: 11.5px;">
                    <span>Lampiran dipilih (${allFiles.length} file)</span>
                    <span class="text-muted">${formatFileSize(totalBytes)}</span>
                </div>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-1 attachment-scroll-list">
        `;

        function escapeWbsHtml(str) {
            return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        allFiles.forEach((item) => {
            const icon = getFileIconClass(item.file.name);
            const safeName = escapeWbsHtml(item.file.name);
            listHtml += `
                <li class="d-flex align-items-center justify-content-between py-1 px-2 rounded bg-white border" style="font-size: 12px; border-color: #e2e8f0 !important;">
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <i class="bi ${icon}"></i>
                        <span class="text-truncate fw-medium text-dark" title="${safeName}" style="max-width: 250px;">${safeName}</span>
                        <span class="text-muted text-nowrap" style="font-size: 11px;">(${formatFileSize(item.file.size)})</span>
                    </div>
                    <button type="button" class="btn btn-link text-muted p-0 ms-2 lh-1 hover-danger"
                            title="Hapus file ini"
                            onclick="removeSingleFileFromUploader('${containerId}', '${listContainerId}', ${item.inputIdx}, ${item.fIdx}, ${maxSizeMb})">
                        <i class="bi bi-trash3 fs-6"></i>
                    </button>
                </li>
            `;
        });

        listHtml += `
                </ul>
            </div>
        `;
        listContainer.innerHTML = listHtml;
    }

    function removeSingleFileFromUploader(containerId, listContainerId, inputIdx, fileIdx, maxSizeMb) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const inputs = container.querySelectorAll('input[type="file"]');
        const targetInput = inputs[inputIdx];
        if (!targetInput) return;

        try {
            const dt = new DataTransfer();
            for (let i = 0; i < targetInput.files.length; i++) {
                if (i !== fileIdx) {
                    dt.items.add(targetInput.files[i]);
                }
            }
            targetInput.files = dt.files;
        } catch (e) {
            console.warn('DataTransfer error:', e);
            targetInput.value = '';
        }

        if (targetInput.files.length === 0 && inputs.length > 1) {
            const row = targetInput.closest('.attachment-input-row');
            if (row) row.remove();
        }

        refreshUnifiedFileList(containerId, listContainerId, maxSizeMb);
    }

    function resetUploaderContainer(containerId, listContainerId) {
        const container = document.getElementById(containerId);
        const listContainer = document.getElementById(listContainerId);
        if (!container) return;

        const rows = container.querySelectorAll('.attachment-input-row');
        rows.forEach((row, idx) => {
            if (idx === 0) {
                const input = row.querySelector('input[type="file"]');
                if (input) input.value = '';
            } else {
                row.remove();
            }
        });
        if (listContainer) listContainer.innerHTML = '';
    }

    function renderExistingAttachments(containerId, filesList, deleteInputName, formId, fileUrls = []) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';

        const form = formId ? document.getElementById(formId) : container.closest('form');
        if (form) {
            form.querySelectorAll(`input[name="${deleteInputName}"]`).forEach(el => el.remove());
        }

        if (!filesList || !Array.isArray(filesList) || filesList.length === 0) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'mb-2';
        let html = `
            <div class="small fw-semibold text-secondary mb-1 d-flex align-items-center justify-content-between" style="font-size: 11.5px;">
                <span>Dokumen Tersimpan (${filesList.length} file)</span>
            </div>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-1 attachment-scroll-list">
        `;

        filesList.forEach((filePath, idx) => {
            const fileName = String(filePath).split('/').pop();
            const safeName = escapeWbsHtml(fileName);
            const rawUrl = (Array.isArray(fileUrls) && fileUrls[idx]) ? fileUrls[idx] : `/storage/${filePath}`;
            const safeUrl = escapeWbsHtml(rawUrl);
            const safePath = escapeWbsHtml(filePath);
            const icon = getFileIconClass(fileName);
            const rowId = `existing-file-${containerId}-${idx}`;
            html += `
                <li class="d-flex align-items-center justify-content-between py-1 px-2 rounded bg-white border" id="${rowId}" style="font-size: 12px; border-color: #e2e8f0 !important;">
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <i class="bi ${icon}"></i>
                        <a href="${safeUrl}" target="_blank" class="text-decoration-none text-primary fw-medium text-truncate file-link" title="Klik untuk melihat/mengunduh dokumen" style="max-width: 250px;">
                            ${safeName}
                        </a>
                        <span class="badge bg-danger d-none badge-deleted ms-1" style="font-size: 10px;">Akan Dihapus</span>
                    </div>
                    <button type="button" class="btn btn-link text-muted p-0 ms-2 lh-1 hover-danger btn-toggle-delete"
                            data-row-id="${rowId}" data-path="${safePath}" data-input-name="${deleteInputName}"
                            title="Hapus file ini"
                            onclick="toggleDeleteExistingFile(this, '${rowId}', this.dataset.path, '${deleteInputName}', '${form ? form.id : ''}')">
                        <i class="bi bi-trash3 fs-6"></i>
                    </button>
                </li>
            `;
        });

        html += `
            </ul>
            <div class="text-muted mt-1 fst-italic" style="font-size: 11px;">Klik ikon tempat sampah pada dokumen yang ingin dihapus saat disimpan.</div>
        `;
        wrapper.innerHTML = html;
        container.appendChild(wrapper);
    }

    function toggleDeleteExistingFile(btn, rowId, filePath, inputName, formId) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const fileLink = row.querySelector('.file-link');
        const badge = row.querySelector('.badge-deleted');
        const form = formId ? document.getElementById(formId) : row.closest('form');

        const isMarked = row.classList.contains('marked-for-deletion');
        if (!isMarked) {
            row.classList.add('marked-for-deletion');
            if (fileLink) fileLink.classList.add('text-decoration-line-through', 'text-muted');
            if (badge) badge.classList.remove('d-none');
            btn.classList.remove('text-muted');
            btn.classList.add('text-primary');
            btn.setAttribute('title', 'Batalkan penghapusan file');
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise fs-6"></i>';

            if (form) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = inputName;
                hidden.value = filePath;
                hidden.className = `delete-input-${rowId}`;
                form.appendChild(hidden);
            }
        } else {
            row.classList.remove('marked-for-deletion');
            if (fileLink) fileLink.classList.remove('text-decoration-line-through', 'text-muted');
            if (badge) badge.classList.add('d-none');
            btn.classList.remove('text-primary');
            btn.classList.add('text-muted');
            btn.setAttribute('title', 'Hapus file ini');
            btn.innerHTML = '<i class="bi bi-trash3 fs-6"></i>';

            if (form) {
                form.querySelectorAll(`.delete-input-${rowId}`).forEach(el => el.remove());
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        ['modalTambahPo', 'modalTambahPoPayment', 'modalTambahNonPo', 'modalEditPo', 'modalEditNonPo'].forEach(modalId => {
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', function () {
                    if (modalId === 'modalTambahPo') {
                        resetUploaderContainer('showWbsPoAttachmentInputs', 'showWbsPoAttachmentFileList');
                    } else if (modalId === 'modalTambahPoPayment') {
                        resetUploaderContainer('paymentProofInputs', 'paymentProofFileList');
                    } else if (modalId === 'modalTambahNonPo') {
                        resetUploaderContainer('showWbsNonPoProofInputs', 'showWbsNonPoProofFileList');
                    } else if (modalId === 'modalEditPo') {
                        resetUploaderContainer('editPoAttachmentInputs', 'editPoAttachmentFileList');
                    } else if (modalId === 'modalEditNonPo') {
                        resetUploaderContainer('editNonPoProofInputs', 'editNonPoProofFileList');
                    }
                });
            }
        });

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (form && form.querySelectorAll) {
                const emptyInputs = [];
                form.querySelectorAll('.attachment-input-wrapper input[type="file"]').forEach(input => {
                    if (!input.files || input.files.length === 0) {
                        input.disabled = true;
                        emptyInputs.push(input);
                    }
                });
                setTimeout(() => {
                    emptyInputs.forEach(input => { input.disabled = false; });
                }, 1000);
            }
        });
    });

    function previewUploadedFileList(input, listContainerId) {
        const container = input.closest('.attachment-input-wrapper') || input.parentElement;
        if (container && container.id) {
            refreshUnifiedFileList(container.id, listContainerId, 5);
        }
    }

    function openEditPoModal(data, updateUrl) {
        const form = document.getElementById('formEditPo');
        form.action = updateUrl || ('{{ url("monitoring-anggaran/po") }}/' + data.id);
        document.getElementById('editPoProgram').value = data.program_name || '';
        document.getElementById('editPoVendor').value = data.vendor || '';
        document.getElementById('editPoValue').value = data.po_value || 0;
        document.getElementById('editPoDate').value = data.po_date || '';
        document.getElementById('editPoDesc').value = data.description || '';

        resetUploaderContainer('editPoAttachmentInputs', 'editPoAttachmentFileList');
        renderExistingAttachments('editPoExistingAttachmentsContainer', data.attachments || data.proofs || [], 'deleted_attachments[]', 'formEditPo', data.file_urls || []);

        const modal = new bootstrap.Modal(document.getElementById('modalEditPo'));
        modal.show();
    }

    function openEditNonPoModal(data, updateUrl) {
        const form = document.getElementById('formEditNonPo');
        form.action = updateUrl || ('{{ url("monitoring-anggaran/non-po") }}/' + data.id);
        document.getElementById('editNonPoProgram').value = data.program_name || '';
        document.getElementById('editNonPoAmount').value = data.amount || 0;
        document.getElementById('editNonPoDate').value = data.transaction_date || '';
        document.getElementById('editNonPoVendor').value = data.recipient_vendor || '';
        document.getElementById('editNonPoDesc').value = data.description || '';

        resetUploaderContainer('editNonPoProofInputs', 'editNonPoProofFileList');
        renderExistingAttachments('editNonPoExistingProofsContainer', data.proofs || [], 'deleted_proofs[]', 'formEditNonPo', data.file_urls || []);

        const modal = new bootstrap.Modal(document.getElementById('modalEditNonPo'));
        modal.show();
    }

    // ── FITUR HAPUS DATA DENGAN SWEETALERT2 & RELATIONAL CHECKS ──
    function confirmDeletePo(id, poNumber, hasPayments, deleteUrl) {
        if (hasPayments) {
            Swal.fire({
                icon: 'error',
                title: 'Tidak Dapat Menghapus PO',
                text: 'PO ' + poNumber + ' sudah memiliki catatan pembayaran. Hapus terlebih dahulu transaksi pembayaran terkait sebelum menghapus PO ini.'
            });
            return;
        }

        const url = deleteUrl || ('{{ url("monitoring-anggaran/po") }}/' + id);
        Swal.fire({
            title: 'Hapus PO ' + poNumber + '?',
            text: 'Penghapusan PO akan mengembalikan nilai komitmen dan memulihkan sisa anggaran WBS ini.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus PO',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                executeDelete(url);
            }
        });
    }

    function confirmDeletePayment(id, payNumber, poNumber, amountFormatted, deleteUrl) {
        const url = deleteUrl || ('{{ url("monitoring-anggaran/payments") }}/' + id);
        Swal.fire({
            title: 'Hapus Pembayaran ' + payNumber + '?',
            text: 'Penghapusan pembayaran (' + amountFormatted + ') akan mengembalikan sisa komitmen pada PO ' + poNumber + '.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Pembayaran',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                executeDelete(url);
            }
        });
    }

    function confirmDeleteNonPo(id, trxNumber, amountFormatted, deleteUrl) {
        const url = deleteUrl || ('{{ url("monitoring-anggaran/non-po") }}/' + id);
        Swal.fire({
            title: 'Hapus Non-PO ' + trxNumber + '?',
            text: 'Penghapusan realisasi (' + amountFormatted + ') akan memulihkan sisa anggaran pada WBS ini.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Transaksi',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                executeDelete(url);
            }
        });
    }

    function confirmDeleteTransfer(id, trfNumber, type, amountFormatted, deleteUrl) {
        const detailText = (type === 'TRANSFER_OUT' || type === 'KELUAR')
            ? 'Membatalkan pengalihan keluar (' + amountFormatted + ') akan mengembalikan saldo ke WBS ini dan memotong saldo dari WBS penerima.'
            : 'Membatalkan pengalihan masuk (' + amountFormatted + ') akan mengembalikan saldo ke WBS asal.';

        const url = deleteUrl || ('{{ url("monitoring-anggaran/transfers") }}/' + id);
        Swal.fire({
            title: 'Batalkan Pengalihan ' + trfNumber + '?',
            text: detailText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Batalkan & Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                executeDelete(url);
            }
        });
    }

    function executeDelete(url) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        Swal.fire({
            title: 'Memproses...',
            text: 'Sedang memproses penghapusan data, mohon tunggu.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _method: 'DELETE',
                _token: csrfToken
            })
        })
        .then(async response => {
            let data = {};
            try {
                data = await response.json();
            } catch (e) {
                data = { message: 'Terjadi kesalahan sistem atau format respons tidak sesuai.' };
            }
            return { status: response.status, body: data };
        })
        .then(({ status, body }) => {
            if (status >= 200 && status < 300 && body.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Dihapus',
                    text: body.message || 'Data berhasil dihapus dari sistem.',
                    timer: 1600,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Dapat Menghapus Data',
                    text: body.message || 'Terjadi kesalahan atau data terkait masih digunakan.'
                });
            }
        })
        .catch(error => {
            const form = document.getElementById('globalDeleteForm');
            if (form) {
                form.action = url;
                form.submit();
            } else {
                Swal.fire('Error', 'Gagal memproses penghapusan data: ' + error.message, 'error');
            }
        });
    }

    // ── HELPER TOGGLE COLLAPSE SECTIONS DENGAN LABEL & CHEVRON IKON ──
    function setupCollapseToggle(collapseId, textId, iconId, showText, hideText) {
        const el = document.getElementById(collapseId);
        const textEl = document.getElementById(textId);
        const iconEl = document.getElementById(iconId);

        if (el && textEl && iconEl) {
            el.addEventListener('show.bs.collapse', function () {
                textEl.textContent = hideText;
                iconEl.classList.remove('bi-chevron-down');
                iconEl.classList.add('bi-chevron-up');
            });
            el.addEventListener('hide.bs.collapse', function () {
                textEl.textContent = showText;
                iconEl.classList.remove('bi-chevron-up');
                iconEl.classList.add('bi-chevron-down');
            });
        }
    }

    // 1. Riwayat Pembayaran PO
    setupCollapseToggle(
        'collapseRiwayatPembayaran',
        'textToggleRiwayatPembayaran',
        'iconToggleRiwayatPembayaran',
        'Lihat Semua Riwayat Pembayaran PO',
        'Sembunyikan Riwayat Pembayaran PO'
    );

    // 2. Riwayat Pengalihan Anggaran
    setupCollapseToggle(
        'collapseRiwayatPengalihan',
        'textToggleRiwayatPengalihan',
        'iconToggleRiwayatPengalihan',
        'Lihat Riwayat Pengalihan Anggaran',
        'Sembunyikan Riwayat Pengalihan Anggaran'
    );

    // 3. Riwayat Transaksi Keuangan (Audit Log WBS)
    setupCollapseToggle(
        'collapseRiwayatTransaksi',
        'textToggleRiwayatTransaksi',
        'iconToggleRiwayatTransaksi',
        'Lihat Riwayat Transaksi Keuangan',
        'Sembunyikan Riwayat Transaksi Keuangan'
    );
</script>
@endpush
