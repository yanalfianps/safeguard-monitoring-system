@extends('layouts.app', [
    'title' => 'HSSE Plan - KPI Kontraktor',
    'activeModule' => 'hsse-plan'
])

@push('styles')
<style>
    .contractor-tabs-wrap {
        display: flex;
        gap: 0;
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .contractor-tab {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        border: none;
        background: transparent;
        transition: all 0.22s ease;
        border-right: 1px solid var(--border-color, #e2e8f0);
        position: relative;
    }
    .contractor-tab:last-child { border-right: none; }
    .contractor-tab:hover { background: #f8fafc; color: #1e293b; }
    .contractor-tab.active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
    }
    .contractor-tab.active .tab-badge { background: rgba(255,255,255,0.25); color: #fff; }
    .tab-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 22px; padding: 0 6px; border-radius: 11px;
        background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 700;
        transition: all 0.22s ease;
    }
    .tab-icon { font-size: 15px; opacity: 0.85; }
    .filter-row-hsse {
        display: grid;
        grid-template-columns: 135px 135px 135px 145px 1fr;
        gap: 10px;
        align-items: center;
    }
    .stats-grid-hsse {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }
    .charts-row-hsse {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 14px;
        margin-bottom: 14px;
    }
    .bottom-grid-hsse {
        display: grid;
        grid-template-columns: 1.4fr 1fr 0.85fr;
        gap: 14px;
        margin-top: 14px;
    }
    .text-leading { color: #1d4ed8; font-weight: 600; }
    .text-lagging { color: #7c3aed; font-weight: 600; }
    .progress-wrap { display: flex; align-items: center; gap: 6px; min-width: 85px; }
    .progress-custom { height: 6px; flex-grow: 1; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
    .progress-bar-custom { height: 100%; border-radius: 4px; }
    .progress-green  { background: #10b981; }
    .progress-orange { background: #f59e0b; }
    .progress-red    { background: #ef4444; }
    .progress-label  { min-width: 28px; font-size: 10px; text-align: right; font-weight: 600; }
    .kpi-row-selected { background: #ecfdf5 !important; }
    .doc-link { color: #2563eb; text-decoration: none; font-weight: 600; cursor: pointer; }
    .doc-link:hover { text-decoration: underline; }
    .detail-fields-grid { display: grid; grid-template-columns: 90px 10px 1fr; gap: 5px 0; font-size: 11px; }
    .thumb-preview-box {
        width: 44px; height: 40px; display: grid; place-items: center;
        background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; color: #475569; font-size: 16px;
    }
    .history-table-custom { font-size: 10.5px; width: 100%; }
    .history-table-custom th { background: #f8fafc; padding: 6px 8px; border-bottom: 1px solid var(--border-color); }
    .history-table-custom td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; }
    .note-item-box {
        background: #f8fafc; border-left: 3px solid var(--green-primary);
        border-radius: 4px; padding: 7px 9px; margin-bottom: 6px; font-size: 11px; transition: all 0.15s ease-in-out;
    }
    .note-item-box:hover { background: #f1f5f9; }
    .kpi-type-selector { display: flex; gap: 0; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; }
    .kpi-type-btn {
        flex: 1; padding: 8px 12px; font-size: 12px; font-weight: 600;
        text-align: center; cursor: pointer; border: none; background: #f8fafc; color: #64748b; transition: all 0.18s ease;
    }
    .kpi-type-btn.selected-leading { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #fff; }
    .kpi-type-btn.selected-lagging { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: #fff; }
    .kpi-type-btn:not(.selected-leading):not(.selected-lagging):hover { background: #e2e8f0; }
    .type-hint-box {
        font-size: 10.5px; color: #64748b; background: #f8fafc;
        border: 1px dashed #cbd5e1; border-radius: 6px; padding: 6px 10px; margin-top: 6px; line-height: 1.4;
    }
    .realisasi-history-item {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-size: 11.5px;
    }
    .realisasi-history-item:last-child { border-bottom: none; }
    .realisasi-timeline-dot {
        width: 8px; height: 8px; border-radius: 50%; background: #10b981; margin-top: 4px; flex-shrink: 0;
    }
    @media (max-width: 1200px) {
        .stats-grid-hsse  { grid-template-columns: repeat(2, 1fr); }
        .charts-row-hsse  { grid-template-columns: 1fr; }
        .bottom-grid-hsse { grid-template-columns: 1fr; }
        .filter-row-hsse  { grid-template-columns: 1fr 1fr; }
        .contractor-tabs-wrap { flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="header-title-wrapper">
        <div class="header-icon-box green"><i class="bi bi-bullseye"></i></div>
        <div>
            <h1 class="page-heading">HSSE Plan</h1>
            <div class="breadcrumb-nav">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="mx-1 text-muted">&rsaquo;</span>
                <span class="text-secondary fw-semibold">HSSE Plan</span>
            </div>
        </div>
    </div>
    <div class="header-actions">
        @adminManagement
            <button class="btn-action-primary" onclick="openKPIModal()">
                <i class="bi bi-plus-lg"></i> Tambah KPI
            </button>
        @endadminManagement
        @canExportPdf
        <button class="btn-action-outline" onclick="exportPDF()">
            <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Export PDF
        </button>
        @endcanExportPdf
    </div>
</div>

<div class="contractor-tabs-wrap" id="contractorTabs">
    <button class="contractor-tab active" data-contractor="" onclick="switchContractorTab(this, '')">
        <i class="bi bi-grid-fill tab-icon"></i> Semua
        <span class="tab-badge" id="tabBadgeAll">0</span>
    </button>
    <button class="contractor-tab" data-contractor="PJSE" onclick="switchContractorTab(this, 'PJSE')">
        <i class="bi bi-building tab-icon"></i> PJSE
        <span class="tab-badge" id="tabBadgePJSE">0</span>
    </button>
    <button class="contractor-tab" data-contractor="JOTRE" onclick="switchContractorTab(this, 'JOTRE')">
        <i class="bi bi-tools tab-icon"></i> JOTRE
        <span class="tab-badge" id="tabBadgeJOTRE">0</span>
    </button>
    <button class="contractor-tab" data-contractor="Security" onclick="switchContractorTab(this, 'Security')">
        <i class="bi bi-shield-check tab-icon"></i> Security
        <span class="tab-badge" id="tabBadgeSecurity">0</span>
    </button>
</div>

<div class="filter-card">
    <div class="filter-row-hsse">
        <div>
            <select id="filterYear" class="form-select form-select-filter">
                @foreach($availableYears ?? [date('Y')] as $yr)
                    <option value="{{ $yr }}" {{ (isset($selectedYear) && $selectedYear == $yr) ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                @endforeach
                <option value="all" {{ (isset($selectedYear) && $selectedYear === null) ? 'selected' : '' }}>Semua Tahun</option>
            </select>
        </div>
        <div>
            <select id="filterMonth" class="form-select form-select-filter">
                <option value="">Semua Bulan</option>
                <option value="1">Januari</option><option value="2">Februari</option>
                <option value="3">Maret</option><option value="4">April</option>
                <option value="5">Mei</option><option value="6">Juni</option>
                <option value="7">Juli</option><option value="8">Agustus</option>
                <option value="9">September</option><option value="10">Oktober</option>
                <option value="11">November</option><option value="12">Desember</option>
            </select>
        </div>
        <div>
            <select id="filterType" class="form-select form-select-filter">
                <option value="">Semua Jenis</option>
                <option value="Leading">Leading</option>
                <option value="Lagging">Lagging</option>
            </select>
        </div>
        <div>
            <select id="filterStatus" class="form-select form-select-filter">
                <option value="">Semua Status</option>
                <option value="Tercapai">Tercapai</option>
                <option value="Belum Tercapai">Belum Tercapai</option>
            </select>
        </div>
        <div class="filter-input-wrap">
            <i class="bi bi-search filter-icon-addon"></i>
            <input type="search" id="globalSearch" class="form-control form-control-filter" placeholder="Cari nama KPI...">
        </div>
    </div>
</div>

<div class="stats-grid-hsse">
    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-blue"><i class="bi bi-bullseye"></i></div>
        <div class="stat-body">
            <div class="stat-title">Total KPI</div>
            <div class="stat-number" id="statTotal">0</div>
            <div class="stat-trend"><span class="stat-trend-neutral">Indikator kontraktor</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-completed rounded-circle"><i class="bi bi-check-lg"></i></div>
        <div class="stat-body">
            <div class="stat-title">Tercapai</div>
            <div class="stat-number text-success" id="statAchieved">0</div>
            <div class="stat-trend"><span class="stat-trend-up">KPI terpenuhi</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-cancelled rounded-circle"><i class="bi bi-x-lg"></i></div>
        <div class="stat-body">
            <div class="stat-title">Belum Tercapai</div>
            <div class="stat-number text-danger" id="statNot">0</div>
            <div class="stat-trend"><span class="stat-trend-neutral">Perlu tindak lanjut</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-total"><i class="bi bi-diagram-3-fill"></i></div>
        <div class="stat-body">
            <div class="stat-title">Leading / Lagging</div>
            <div class="stat-number" id="statTypes" style="font-size:20px">0 / 0</div>
            <div class="stat-trend"><span class="stat-trend-caption">Jumlah indikator</span></div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-topbar">
        <h2 class="table-section-title">Daftar KPI Kontraktor</h2>
        <div class="table-controls">
            <select class="form-select form-select-sm" id="tableCategoryFilter" style="width:150px; font-size:11.5px">
                <option value="">Semua Kategori</option>
                <option value="Leading">Leading</option>
                <option value="Lagging">Lagging</option>
            </select>
        </div>
    </div>
    <div class="table-responsive-custom">
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width:40px;text-align:center;">No</th>
                    <th>Nama KPI</th>
                    <th style="width:85px;">Jenis</th>
                    <th style="width:105px;text-align:center;">Frekuensi</th>
                    <th style="width:110px;text-align:center;">Target</th>
                    <th style="width:95px;text-align:center;">Realisasi</th>
                    <th style="width:125px;">Persentase</th>
                    <th style="width:115px;text-align:center;">Status</th>
                    <th>Keterangan</th>
                    <th style="width:125px;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="kpiTableBody"></tbody>
        </table>
    </div>
    <div class="table-bottom-bar">
        <div id="tableInfo">Memuat data...</div>
        <div><ul class="pagination pagination-sm m-0" id="pagination"></ul></div>
    </div>
</div>

<div class="bottom-grid-hsse">
    <div class="chart-box-card">
        <div class="chart-header">
            <h2 class="chart-title">Detail KPI Terpilih</h2>
            <p class="chart-subtitle">Klik baris tabel untuk memilih KPI</p>
        </div>
        <div id="detailPanel" class="p-2">
            <div class="text-muted text-center py-4">Pilih baris pada tabel untuk melihat detail.</div>
        </div>
    </div>
    <div class="chart-box-card">
        <div class="chart-header">
            <h2 class="chart-title">Riwayat Capaian</h2>
            <p class="chart-subtitle">Log perkembangan realisasi indikator</p>
        </div>
        <div class="table-responsive" style="max-height:180px;overflow-y:auto;">
            <table class="history-table-custom">
                <thead><tr><th>Tanggal</th><th>Realisasi</th><th>Keterangan</th><th>Dokumen</th></tr></thead>
                <tbody id="historyBody"></tbody>
            </table>
        </div>
    </div>
    <div class="chart-box-card">
        <div class="chart-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="chart-title">Catatan KPI</h2>
                <p class="chart-subtitle">Evaluasi &amp; tindak lanjut</p>
            </div>
            @adminManagement
                <button class="btn btn-action-outline btn-sm py-1 px-2" style="font-size:11px" onclick="openNoteModal()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah
                </button>
            @endadminManagement
        </div>
        <div id="notesPanel" style="max-height:170px;overflow-y:auto;">
            <div class="text-muted text-center py-3" style="font-size:11px">Pilih KPI untuk melihat catatan.</div>
        </div>
    </div>
</div>

{{-- MODAL DETAIL KPI --}}
<div class="modal fade" id="kpiDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-custom-content">
            <div class="modal-header modal-header-themed green">
                <h5 class="modal-title" id="kpiDetailModalTitle"><i class="bi bi-bullseye me-1"></i> Detail KPI Kontraktor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="kpiDetailModalBody">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                    Memuat data detail KPI dari database...
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-2 border-top d-flex justify-content-between">
                <a href="#" id="kpiDetailPermalink" class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Penuh
                </a>
                <button type="button" class="btn btn-sm btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH KPI --}}
<div class="modal fade" id="kpiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-custom-content">
            <form id="kpiForm">
                <div class="modal-header modal-header-themed green">
                    <h5 class="modal-title" id="kpiModalTitle"><i class="bi bi-bullseye me-1"></i> Tambah KPI Kontraktor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="kpiId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px">Nama KPI <span class="text-danger">*</span></label>
                            <input type="text" id="kpiName" class="form-control form-control-sm" placeholder="Contoh: Inspeksi Harian, Zero Accident Rate..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Kontraktor Pelaksana <span class="text-danger">*</span></label>
                            <select id="kpiContractorTab" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Kontraktor --</option>
                                <option value="PJSE">PJSE</option>
                                <option value="JOTRE">JOTRE</option>
                                <option value="Security">Security</option>
                            </select>
                            <div style="font-size:10.5px;color:#64748b;margin-top:3px;"><i class="bi bi-info-circle me-1"></i>Data akan otomatis tampil di tab kontraktor terkait.</div>
                        </div>
                        <input type="hidden" id="kpiContractorId">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Frekuensi Target <span class="text-danger">*</span></label>
                            <select id="kpiTargetFrequency" class="form-select form-select-sm" onchange="onFrequencyChange(this.value)" required>
                                <option value="Bulanan">Bulanan</option>
                                <option value="Tahunan" selected>Tahunan</option>
                                <option value="Per Project">Per Project</option>
                            </select>
                            <div id="freqHintBox" style="font-size:10.5px;color:#64748b;margin-top:3px;line-height:1.35;">
                                <i class="bi bi-info-circle me-1"></i><strong>Tahunan</strong>: Target dan realisasi berjalan kumulatif sepanjang tahun berjalan.
                            </div>
                        </div>
                        <div class="col-12" id="projectDateRangeRow" style="display: none;">
                            <div class="p-2 rounded bg-light border">
                                <div class="d-flex align-items-center mb-2 text-primary" style="font-size:11.5px; font-weight:600;">
                                    <i class="bi bi-calendar-range me-1"></i> Rentang Tanggal Project
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size:11.5px">Tanggal Mulai Project <span class="text-danger">*</span></label>
                                        <input type="date" id="kpiProjectStartDate" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size:11.5px">Tanggal Berakhir Project <span class="text-danger">*</span></label>
                                        <input type="date" id="kpiProjectEndDate" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div style="font-size:10.5px;color:#64748b;margin-top:4px;">
                                    <i class="bi bi-info-circle me-1"></i>KPI Per Project hanya akan muncul di daftar/tabel jika tanggal aktif berada di dalam rentang tanggal tersebut.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Nilai Target <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="kpiTargetValue" class="form-control form-control-sm" placeholder="Angka target" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Satuan Pengukuran</label>
                            <input type="text" id="kpiUnitMeasure" class="form-control form-control-sm" placeholder="Contoh: %, kali/tahun, index">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px">Jenis KPI <span class="text-danger">*</span></label>
                            <div class="kpi-type-selector">
                                <button type="button" class="kpi-type-btn selected-leading" id="btnLeading" onclick="selectKpiType('Leading')">
                                    <i class="bi bi-graph-up-arrow me-1"></i> Leading
                                </button>
                                <button type="button" class="kpi-type-btn" id="btnLagging" onclick="selectKpiType('Lagging')">
                                    <i class="bi bi-graph-down-arrow me-1"></i> Lagging
                                </button>
                            </div>
                            <input type="hidden" id="kpiType" value="Leading">
                            <div class="type-hint-box" id="typeHintBox">
                                <strong class="text-primary">Leading</strong> — Indikator proaktif. Semakin banyak semakin bagus. Progres dihitung bertahap (persentase terhadap target).
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px">Keterangan Target</label>
                            <input type="text" id="kpiDescription" class="form-control form-control-sm" placeholder="Deskripsi singkat target KPI...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-action-primary px-4"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT REALISASI --}}
<div class="modal fade" id="editRealisasiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content modal-custom-content">
            <form id="editRealisasiForm">
                <div class="modal-header modal-header-themed green">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Update Realisasi KPI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="editRealisasiKpiId">
                    <div class="p-2 rounded bg-light border mb-3" style="font-size:12px">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="fw-bold text-dark" id="editRealisasiKpiName">-</div>
                            <span class="badge bg-secondary-subtle text-secondary border" id="editRealisasiFreqBadge" style="font-size:10.5px">-</span>
                        </div>
                        <div class="d-flex gap-3 text-muted">
                            <span>Target: <strong id="editRealisasiTarget" class="text-primary">-</strong></span>
                            <span>Saat ini: <strong id="editRealisasiCurrent" class="text-success">-</strong></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px">Nilai Realisasi <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="editRealisasiValue" class="form-control form-control-sm" placeholder="Nilai realisasi terkini..." required>
                        <div id="editRealisasiHelpText" style="font-size:10.5px;color:#64748b;margin-top:4px;"><i class="bi bi-info-circle me-1"></i>Input akumulatif realisasi.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px">Tanggal Update <span class="text-danger">*</span></label>
                        <input type="date" id="editRealisasiDate" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px"><i class="bi bi-paperclip me-1 text-primary"></i>Dokumen Bukti (Opsional)</label>
                        <input type="file" id="editRealisasiDoc" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div style="font-size:10.5px;color:#64748b;margin-top:3px;">Format: PDF, JPG, PNG, DOC. Maks 5MB.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px">Keterangan / Catatan (Opsional)</label>
                        <textarea id="editRealisasiNote" class="form-control form-control-sm" rows="2" placeholder="Catatan perubahan realisasi..."></textarea>
                    </div>
                    <div id="editRealisasiHistoryWrap">
                        <div class="fw-semibold text-muted mb-2" style="font-size:11px"><i class="bi bi-clock-history me-1"></i>Riwayat Update Realisasi</div>
                        <div id="editRealisasiHistoryList" style="max-height:140px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;">
                            <div class="text-muted text-center py-3" style="font-size:11px">Belum ada riwayat.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveRealisasi" class="btn btn-sm btn-action-primary px-4"><i class="bi bi-check-lg me-1"></i> Simpan Realisasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH CATATAN --}}
<div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom-content">
            <form id="noteForm">
                <div class="modal-header modal-header-themed green">
                    <h5 class="modal-title"><i class="bi bi-chat-left-text me-1"></i> Tambah Catatan KPI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px">KPI Terpilih</label>
                        <input type="text" id="noteKpiName" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px">Catatan Tindak Lanjut <span class="text-danger">*</span></label>
                        <textarea id="noteText" class="form-control form-control-sm" rows="3" placeholder="Tuliskan catatan evaluasi atau tindak lanjut..." required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size:12px">Penulis / PIC (Opsional)</label>
                        <input type="text" id="noteAuthor" class="form-control form-control-sm" placeholder="Nama pembuat catatan">
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-action-primary px-4"><i class="bi bi-check-lg me-1"></i> Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT CATATAN --}}
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom-content">
            <form id="editNoteForm">
                <div class="modal-header modal-header-themed green">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Catatan KPI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="editNoteId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px">Catatan Tindak Lanjut <span class="text-danger">*</span></label>
                        <textarea id="editNoteText" class="form-control form-control-sm" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-action-primary px-4"><i class="bi bi-check-lg me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const canManageKPI = {{ Auth::user()?->isAdminManagement() ? 'true' : 'false' }};
    let kpiData = @json($kpis ?? []);
    let selectedId = kpiData.length > 0 ? kpiData[0].id : null;
    let currentPage = 1;
    const pageSize = 10;
    let activeContractor = '';
    const $ = id => document.getElementById(id);
    const kpiModal = new bootstrap.Modal($('kpiModal'));
    const kpiDetailModal = new bootstrap.Modal($('kpiDetailModal'));
    const noteModal = new bootstrap.Modal($('noteModal'));
    const editNoteModal = new bootstrap.Modal($('editNoteModal'));
    const editRealisasiModal = new bootstrap.Modal($('editRealisasiModal'));

    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[ch]);
    }

    function frequencyBadgeHTML(freq) {
        const f = freq || 'Tahunan';
        if (f === 'Bulanan') {
            return `<span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:10px;font-weight:600">Bulanan</span>`;
        }
        if (f === 'Per Project') {
            return `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:10px;font-weight:600">Per Project</span>`;
        }
        return `<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10px;font-weight:600">Tahunan</span>`;
    }

    function onFrequencyChange(val) {
        const box = $('freqHintBox');
        const dateRow = $('projectDateRangeRow');
        const isProject = (val === 'Per Project');

        if (dateRow) {
            dateRow.style.display = isProject ? 'block' : 'none';
        }
        if ($('kpiProjectStartDate')) {
            $('kpiProjectStartDate').required = isProject;
        }
        if ($('kpiProjectEndDate')) {
            $('kpiProjectEndDate').required = isProject;
        }

        if (!box) return;
        if (val === 'Bulanan') {
            box.innerHTML = '<i class="bi bi-info-circle me-1"></i><strong>Bulanan</strong>: Realisasi di-reset setiap bulan baru. Rekam jejak historis tersimpan per bulan dan tahun.';
        } else if (val === 'Per Project') {
            box.innerHTML = '<i class="bi bi-info-circle me-1"></i><strong>Per Project</strong>: Realisasi dihitung kumulatif sepanjang rentang tanggal project yang diatur, tanpa terpengaruh filter bulan.';
        } else {
            box.innerHTML = '<i class="bi bi-info-circle me-1"></i><strong>Tahunan</strong>: Target dan realisasi berjalan kumulatif sepanjang tahun berjalan, otomatis mengarsip saat tahun berganti.';
        }
    }

    /**
     * Helper mengambil nilai realisasi bulanan dari struktur data
     */
    function getMonthlyVal(item, m, yr) {
        const monthly = item.monthly_realizations || {};
        const pad = String(m).padStart(2, '0');
        const ym = `${yr}-${pad}`;
        const ymShort = `${yr}-${m}`;

        if (monthly[ym] !== undefined) return Number(monthly[ym]);
        if (monthly[ymShort] !== undefined) return Number(monthly[ymShort]);
        if (monthly[yr] && monthly[yr][m] !== undefined) return Number(monthly[yr][m]);
        if (monthly[m] !== undefined && (item.year == yr || !yr)) return Number(monthly[m]);
        if (monthly[String(m)] !== undefined && (item.year == yr || !yr)) return Number(monthly[String(m)]);
        return 0;
    }

    /**
     * Hitung realisasi, persentase, dan status berdasarkan frekuensi, bulan, dan tahun evaluasi:
     * 1. Bulanan: Realisasi dan persentase dihitung terisolasi per bulan (reset setiap bulan, histori tersimpan).
     * 2. Tahunan: Realisasi dihitung secara kumulatif (YTD) dari Januari hingga bulan terpilih pada tahun berjalan.
     * 3. Per Project: Realisasi dihitung secara kumulatif sepanjang rentang tanggal project, tanpa terpengaruh filter bulan.
     */
    function getKPIProgress(item, month, year) {
        const freq = item.target_frequency || 'Tahunan';
        const target = Number(item.targetValue ?? item.target ?? 0);
        const category = item.category || item.type || 'Leading';
        const isLagging = category.toLowerCase() === 'lagging';
        const monthly = item.monthly_realizations || {};
        const evalYear = year ? parseInt(year) : (item.year || new Date().getFullYear());

        let actual = 0;

        if (freq === 'Bulanan') {
            if (month) {
                const mInt = parseInt(month);
                actual = getMonthlyVal(item, mInt, evalYear);
            } else {
                const curM = new Date().getMonth() + 1;
                const curY = new Date().getFullYear();
                const val = getMonthlyVal(item, curM, evalYear || curY);
                actual = val !== 0 ? val : Number(item.realization ?? item.actual ?? 0);
            }
        } else if (freq === 'Tahunan') {
            const endM = month ? parseInt(month) : (new Date().getMonth() + 1);
            const hasMonthly = Object.keys(monthly).length > 0;
            if (hasMonthly) {
                actual = 0;
                for (let m = 1; m <= endM; m++) {
                    actual += getMonthlyVal(item, m, evalYear);
                }
            } else {
                actual = Number(item.realization ?? item.actual ?? 0);
            }
        } else {
            // Per Project: kumulatif sepanjang rentang tanggal project
            actual = Number(item.realization ?? item.actual ?? 0);
        }

        let pct = 0;
        if (isLagging) {
            if (actual <= 0) {
                // Jika realisasi 0, persentase = 100% (Tercapai)
                pct = 100;
            } else if (target <= 0 || actual >= target) {
                // Jika realisasi mencapai atau melebihi target, persentase turun menjadi 0% (Belum Tercapai)
                pct = 0;
            } else {
                // Jika realisasi di antara 0 dan target (misal target 2, realisasi 1): (1 - (realisasi / target)) * 100%
                pct = Math.max(0, Math.min(100, Math.round((1 - (actual / target)) * 100)));
            }
        } else {
            if (target <= 0) {
                pct = actual <= 0 ? 100 : 0;
            } else {
                pct = Math.max(0, Math.min(100, Math.round((actual / target) * 100)));
            }
        }

        const status = pct >= 100 ? 'Tercapai' : 'Belum Tercapai';
        return { actual, pct, status };
    }

    function percentage(item, month = null, year = null) {
        const m = month || $('filterMonth')?.value;
        const y = year || ($('filterYear')?.value !== 'all' ? $('filterYear')?.value : null);
        return getKPIProgress(item, m, y).pct;
    }

    function formatNumber(v) { return Number(v||0).toLocaleString('id-ID',{maximumFractionDigits:2}); }
    function formatDate(v) {
        if (!v) return '-';
        const s = String(v), d = new Date(s.includes('T') ? s : s+'T00:00:00');
        if (isNaN(d.getTime())) return s;
        return d.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
    }
    function formatDateTime(v) {
        if (!v) return '-';
        const d = new Date(v); if (isNaN(d.getTime())) return String(v);
        return d.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'})+', '+d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
    }
    function statusBadgeClass(s) {
        if (s === 'Tercapai') return 'status-achieved';
        if (s === 'Belum Tercapai' || s === 'Tidak Tercapai' || s === 'Belum Mulai') return 'status-not';
        return 'status-progress';
    }
    function progressColorClass(item) {
        const st = item.status || '';
        if (st === 'Tercapai') return 'progress-green';
        if (st === 'Belum Tercapai' || st === 'Tidak Tercapai' || st === 'Belum Mulai') return 'progress-red';
        return 'progress-orange';
    }
    function getContractorName(item) {
        return (item.contractor ? item.contractor.name : (item.contractor_name || '')).trim();
    }

    // ── TAB NAVIGASI ──────────────────────────────────────────
    function switchContractorTab(el, name) {
        document.querySelectorAll('.contractor-tab').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
        activeContractor = name;
        currentPage = 1;
        applyAll();
    }
    function updateTabBadges() {
        $('tabBadgeAll').textContent      = kpiData.length;
        $('tabBadgePJSE').textContent     = kpiData.filter(x => getContractorName(x).toLowerCase() === 'pjse').length;
        $('tabBadgeJOTRE').textContent    = kpiData.filter(x => getContractorName(x).toLowerCase() === 'jotre').length;
        $('tabBadgeSecurity').textContent = kpiData.filter(x => getContractorName(x).toLowerCase() === 'security').length;
    }

    // ── FILTER ────────────────────────────────────────────────
    function getFilteredData() {
        const filterYear   = $('filterYear') ? $('filterYear').value : '';
        const filterMonth  = $('filterMonth').value;
        const categoryFilter = ($('tableCategoryFilter')?.value || $('filterType')?.value || '').trim();
        const filterStatus = $('filterStatus').value;
        const search = ($('globalSearch').value || '').trim().toLowerCase();

        let rows = kpiData.filter(item => {
            const title   = (item.title || item.name || '').toLowerCase();
            const desc    = (item.description || '').toLowerCase();
            const kpiType = item.category || item.type || '';
            const cName   = getContractorName(item).toLowerCase();
            const freq    = item.target_frequency || 'Tahunan';

            if (activeContractor && cName !== activeContractor.toLowerCase()) return false;
            if (search && !(title+' '+desc+' '+kpiType.toLowerCase()+' '+cName+' '+freq.toLowerCase()).includes(search)) return false;
            if (categoryFilter && kpiType !== categoryFilter) return false;

            // Filter tahun untuk KPI Tahunan / arsip tahun
            if (filterYear && filterYear !== 'all') {
                const y = parseInt(filterYear);
                if (freq === 'Tahunan' && item.year && item.year !== y) return false;
            }

            // Logika tampilan untuk Per Project:
            // KPI Per Project hanya akan muncul di daftar/tabel jika tanggal aktif berada di dalam rentang (start date s.d. end date) tersebut.
            if (freq === 'Per Project') {
                const pStart = item.project_start_date || item.project_start || item.start_date || item.start;
                const pEnd   = item.project_end_date || item.project_end || item.end_date || item.end;
                if (pStart && pEnd) {
                    const sDate = new Date(pStart.includes('T') ? pStart : pStart + 'T00:00:00');
                    const eDate = new Date(pEnd.includes('T') ? pEnd : pEnd + 'T23:59:59');

                    if (filterMonth) {
                        const curY = (filterYear && filterYear !== 'all') ? parseInt(filterYear) : (item.year || new Date().getFullYear());
                        const m = parseInt(filterMonth);
                        const mStart = new Date(curY, m - 1, 1);
                        const mEnd = new Date(curY, m, 0, 23, 59, 59);
                        if (sDate > mEnd || eDate < mStart) return false;
                    } else if (filterYear && filterYear !== 'all') {
                        const curY = parseInt(filterYear);
                        const yStart = new Date(curY, 0, 1);
                        const yEnd = new Date(curY, 11, 31, 23, 59, 59);
                        if (sDate > yEnd || eDate < yStart) return false;
                    } else {
                        // Periksa tanggal hari ini (tanggal aktif sistem)
                        const today = new Date();
                        if (today < sDate || today > eDate) return false;
                    }
                }
            } else if (filterMonth && freq === 'Bulanan') {
                const sDate = item.start_date || item.start, eDate = item.end_date || item.end;
                if (sDate && eDate) {
                    const m = parseInt(filterMonth);
                    const mS = new Date(sDate.includes('T')?sDate:sDate+'T00:00:00').getMonth()+1;
                    const mE = new Date(eDate.includes('T')?eDate:eDate+'T00:00:00').getMonth()+1;
                    if (m < mS || m > mE) return false;
                }
            }

            const evalYr = (filterYear && filterYear !== 'all') ? parseInt(filterYear) : null;
            const prog = getKPIProgress(item, filterMonth, evalYr);

            if (filterStatus) {
                if (filterStatus === 'Tercapai' && prog.status !== 'Tercapai') return false;
                if (filterStatus === 'Belum Tercapai' && prog.status !== 'Belum Tercapai') return false;
            }

            return true;
        });

        rows.sort((a,b) => (a.title||a.name||'').localeCompare(b.title||b.name||''));
        return rows;
    }

    // ── TABEL ────────────────────────────────────────────────
    function renderTable() {
        const rows = getFilteredData();
        const totalPages = Math.max(1, Math.ceil(rows.length/pageSize));
        currentPage = Math.min(currentPage, totalPages);
        const si = (currentPage-1)*pageSize;
        const visible = rows.slice(si, si+pageSize);
        const selMonth = $('filterMonth').value;
        const selYear = ($('filterYear') && $('filterYear').value !== 'all') ? $('filterYear').value : null;

        $('kpiTableBody').innerHTML = visible.map((item, idx) => {
            const prog = getKPIProgress(item, selMonth, selYear);
            const pct = prog.pct;
            const aVal = prog.actual;
            const st = prog.status;
            const kpiType = item.category || item.type || 'Leading';
            const tClass = kpiType === 'Leading' ? 'text-leading' : 'text-lagging';
            const tLbl = item.target_label || (item.target != null ? `${item.target} ${item.unit_measure||''}` : '-');
            const freqBadge = frequencyBadgeHTML(item.target_frequency || 'Tahunan');
            return `<tr class="${item.id===selectedId?'kpi-row-selected':''}" onclick="selectKPI(${item.id})">
                <td class="text-center">${si+idx+1}</td>
                <td><div class="fw-semibold text-dark">${escapeHTML(item.title||item.name)}</div></td>
                <td><span class="${tClass}">${escapeHTML(kpiType)}</span></td>
                <td class="text-center">${freqBadge}</td>
                <td class="text-center">${escapeHTML(tLbl)}</td>
                <td class="text-center">${escapeHTML(formatNumber(aVal))}</td>
                <td><div class="progress-wrap"><div class="progress-custom"><div class="progress-bar-custom ${progressColorClass({status:st})}" style="width:${pct}%"></div></div><span class="progress-label">${pct}%</span></div></td>
                <td class="text-center"><span class="badge-status ${statusBadgeClass(st)}">${escapeHTML(st)}</span></td>
                <td>${escapeHTML(item.description||'-')}</td>
                <td class="text-center">
                    <button class="btn-action-icon btn-action-view" title="Detail KPI" onclick="event.stopPropagation();viewKPIDetail(${item.id})"><i class="bi bi-eye"></i></button>
                    ${canManageKPI ? `
                    <button class="btn-action-icon btn-action-edit" title="Edit Realisasi" onclick="event.stopPropagation();openEditRealisasi(${item.id})"><i class="bi bi-pencil"></i></button>
                    <button class="btn-action-icon btn-action-delete" title="Hapus KPI" onclick="event.stopPropagation();deleteKPI(${item.id})"><i class="bi bi-trash"></i></button>` : ''}
                </td></tr>`;
        }).join('');

        if (!visible.length) $('kpiTableBody').innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">Tidak ada data KPI yang sesuai filter.</td></tr>';
        $('tableInfo').textContent = rows.length ? `Menampilkan ${si+1} - ${Math.min(si+pageSize,rows.length)} dari ${rows.length} data` : 'Tidak ada data';
        let pg = `<li class="page-item ${currentPage===1?'disabled':''}"><button class="page-link" onclick="goPage(${currentPage-1})">&lsaquo;</button></li>`;
        for (let p=1; p<=totalPages; p++) pg += `<li class="page-item ${p===currentPage?'active':''}"><button class="page-link" onclick="goPage(${p})">${p}</button></li>`;
        pg += `<li class="page-item ${currentPage===totalPages?'disabled':''}"><button class="page-link" onclick="goPage(${currentPage+1})">&rsaquo;</button></li>`;
        $('pagination').innerHTML = pg;
    }
    function goPage(p) {
        const max = Math.max(1, Math.ceil(getFilteredData().length/pageSize));
        if (p<1||p>max) return; currentPage=p; renderTable();
    }

    // ── STATS ────────────────────────────────────────────────
    function updateStats() {
        const rows = getFilteredData();
        const m = $('filterMonth').value;
        const y = ($('filterYear') && $('filterYear').value !== 'all') ? $('filterYear').value : null;
        const achieved    = rows.filter(x => getKPIProgress(x, m, y).status === 'Tercapai').length;
        const notAchieved = rows.length - achieved;
        const leading = rows.filter(x => (x.category||x.type)==='Leading').length;
        const lagging = rows.filter(x => (x.category||x.type)==='Lagging').length;
        $('statTotal').textContent    = rows.length;
        $('statAchieved').textContent = achieved;
        $('statNot').textContent      = notAchieved;
        $('statTypes').textContent    = `${leading} / ${lagging}`;
    }

    // ── DETAIL PANEL ─────────────────────────────────────────
    function selectKPI(id) { selectedId=id; renderDetail(); renderTable(); }
    function renderDetail() {
        const item = kpiData.find(x=>x.id===selectedId)||kpiData[0];
        if (!item) {
            $('detailPanel').innerHTML='<div class="text-muted text-center py-4">Pilih KPI untuk melihat detail.</div>';
            $('historyBody').innerHTML=''; $('notesPanel').innerHTML='<div class="text-muted text-center py-3" style="font-size:11px">Belum ada catatan evaluasi.</div>'; return;
        }
        const selYear = ($('filterYear') && $('filterYear').value !== 'all') ? $('filterYear').value : null;
        const prog = getKPIProgress(item, $('filterMonth').value, selYear);
        const pct = prog.pct;
        const aVal = prog.actual;
        const st = prog.status;
        const docs = item.documents||[];
        const kT = item.category||item.type||'Leading';
        const freq = item.target_frequency || 'Tahunan';
        const tLbl = item.target_label||(item.target!=null?`${item.target} ${item.unit_measure||''}`:'-');
        const pStart = item.project_start_date || item.project_start;
        const pEnd = item.project_end_date || item.project_end;

        $('detailPanel').innerHTML=`
            <div class="mb-2 d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge-status ${statusBadgeClass(st)}">${escapeHTML(st)}</span>
                    <span class="ms-2 fw-semibold ${kT==='Leading'?'text-leading':'text-lagging'}">${escapeHTML(kT)}</span>
                    <span class="ms-1">${frequencyBadgeHTML(freq)}</span>
                </div>
                <button class="btn btn-action-outline btn-sm py-0 px-2" style="font-size:11px" onclick="viewKPIDetail(${item.id})"><i class="bi bi-arrows-fullscreen me-1"></i>Perbesar</button>
            </div>
            <div class="detail-fields-grid mb-2">
                <span class="text-muted">Nama KPI</span><span>:</span><strong class="text-dark">${escapeHTML(item.title||item.name)}</strong>
                <span class="text-muted">Frekuensi</span><span>:</span><span>${escapeHTML(freq)}</span>
                ${(freq === 'Per Project' && pStart && pEnd) ? `<span class="text-muted">Periode Proyek</span><span>:</span><span>${formatDate(pStart)} s.d. ${formatDate(pEnd)}</span>` : ''}
                <span class="text-muted">Target</span><span>:</span><span>${escapeHTML(tLbl)}</span>
                <span class="text-muted">Realisasi</span><span>:</span><span>${escapeHTML(formatNumber(aVal))} ${escapeHTML(item.unit_measure||'')}</span>
                <span class="text-muted">Keterangan</span><span>:</span><span>${escapeHTML(item.description||'-')}</span>
            </div>
            <div class="border-top pt-2">
                <div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-semibold text-muted" style="font-size:11px">Persentase Pemenuhan</span><span class="fw-bold text-dark" style="font-size:11px">${pct}%</span></div>
                <div class="progress-custom" style="height:8px"><div class="progress-bar-custom ${progressColorClass({status:st})}" style="width:${pct}%"></div></div>
            </div>
            <div class="mt-2 pt-2 border-top">
                <span class="fw-semibold text-muted d-block mb-1" style="font-size:10.5px">Dokumen (${docs.length})</span>
                <div class="d-flex gap-1 flex-wrap">${docs.map(d=>`<span class="thumb-preview-box" title="${escapeHTML(d)}"><i class="bi bi-file-earmark-pdf"></i></span>`).join('')||'<span class="text-muted" style="font-size:10px">Belum ada dokumen.</span>'}</div>
            </div>`;
        const hist = item.history || item.realization_history || [];
        $('historyBody').innerHTML=hist.length?hist.map(h=>`<tr><td>${formatDate(h.date)}</td><td>${formatNumber(h.actual)}</td><td>${escapeHTML(h.note)}</td><td>${h.doc?`<a href="#" class="doc-link">${escapeHTML(h.doc)}</a>`:'-'}</td></tr>`).join(''):'<tr><td colspan="4" class="text-center text-muted py-3">Belum ada riwayat capaian.</td></tr>';
        renderNotesList(item);
    }
    function renderNotesList(item) {
        const notes=item.notes||[];
        if (!notes.length) { $('notesPanel').innerHTML='<div class="text-muted text-center py-3" style="font-size:11px"><i class="bi bi-chat-left-dots d-block mb-1 opacity-50"></i>Belum ada catatan evaluasi.</div>'; return; }
        $('notesPanel').innerHTML=notes.map(n=>{
            const id=n.id||0, txt=typeof n==='string'?n:(n.note||''), auth=typeof n==='object'&&n.author?n.author:'Admin HSSE', ds=typeof n==='object'&&n.created_at?formatDateTime(n.created_at):'';
            return `<div class="note-item-box d-flex justify-content-between align-items-start gap-2 mb-2 p-2"><div class="flex-grow-1" style="min-width:0"><div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-semibold text-dark" style="font-size:10.5px"><i class="bi bi-person-fill text-muted me-1"></i>${escapeHTML(auth)}</span>${ds?`<span class="text-muted" style="font-size:9.5px">${ds}</span>`:''}</div><div class="note-text text-secondary" style="font-size:11px;line-height:1.35;word-break:break-word;">${escapeHTML(txt)}</div></div><div class="note-actions d-flex gap-1 flex-shrink-0">${id?`<button type="button" class="btn btn-link text-primary p-0" onclick="openEditNoteModal(${id},${escapeHTML(JSON.stringify(txt))})"><i class="bi bi-pencil" style="font-size:11px"></i></button><button type="button" class="btn btn-link text-danger p-0" onclick="deleteNote(${id})"><i class="bi bi-trash" style="font-size:11px"></i></button>`:''}</div></div>`;
        }).join('');
    }

    // ── MODAL DETAIL ─────────────────────────────────────────
    function viewKPIDetail(id) {
        selectedId=id; renderDetail(); renderTable();
        $('kpiDetailModalTitle').innerHTML='<i class="bi bi-bullseye me-1"></i> Memuat Detail KPI...';
        $('kpiDetailModalBody').innerHTML='<div class="text-center py-5"><div class="spinner-border text-success" role="status"></div><div class="mt-2 text-muted fw-semibold" style="font-size:12px">Mengambil data KPI dari database...</div></div>';
        kpiDetailModal.show();
        fetch(`/hsse-plan/${id}`,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
        .then(r=>{if(!r.ok)throw new Error(r.status===404?'Data KPI tidak ditemukan.':'Gagal memuat detail KPI.');return r.json();})
        .then(data=>{if(!data.success||!data.kpi)throw new Error(data.message||'Data KPI tidak ditemukan.');const k=data.kpi,idx=kpiData.findIndex(x=>x.id===k.id);if(idx!==-1)kpiData[idx]=k;renderKPIDetailModal(k);})
        .catch(err=>{kpiDetailModal.hide();Swal.fire({icon:'error',title:'Data Tidak Ditemukan',text:err.message||'Data KPI tidak ditemukan pada sistem.'});});
    }
    function renderKPIDetailModal(item) {
        const selYear = ($('filterYear') && $('filterYear').value !== 'all') ? $('filterYear').value : null;
        const prog = getKPIProgress(item, $('filterMonth').value, selYear);
        const pct = prog.pct;
        const aVal = prog.actual;
        const st = prog.status;
        const notes = item.notes||[];
        const kT = item.category||item.type||'Leading';
        const freq = item.target_frequency || 'Tahunan';
        const tLbl = item.target_label||(item.target!=null?`${item.target} ${item.unit_measure||''}`:'-');
        const pStart = item.project_start_date || item.project_start;
        const pEnd = item.project_end_date || item.project_end;

        $('kpiDetailPermalink').href=`/hsse-plan/${item.id}`;
        $('kpiDetailModalTitle').innerHTML=`<i class="bi bi-bullseye me-1"></i> Detail: ${escapeHTML(item.title||item.name)}`;
        $('kpiDetailModalBody').innerHTML=`
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <span class="badge-status ${statusBadgeClass(st)} px-3 py-1 fs-6">${escapeHTML(st)}</span>
                    <span class="badge ms-2 ${kT==='Leading'?'bg-primary-subtle text-primary border border-primary-subtle':'bg-purple-subtle text-purple border'}" style="font-size:12px"><i class="bi bi-tag-fill me-1"></i>${escapeHTML(kT)} Indicator</span>
                    <span class="ms-1">${frequencyBadgeHTML(freq)}</span>
                </div>
                <span class="text-muted" style="font-size:11.5px">Kontraktor: <strong>${escapeHTML(getContractorName(item)||'-')}</strong></span>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-5"><div class="p-2 border rounded bg-light"><div class="text-muted" style="font-size:11px">Nama Indikator KPI</div><div class="fw-bold text-dark mt-1" style="font-size:13px">${escapeHTML(item.title||item.name)}</div></div></div>
                <div class="col-md-2 col-6"><div class="p-2 border rounded bg-light"><div class="text-muted" style="font-size:11px">Frekuensi</div><div class="fw-bold text-dark mt-1" style="font-size:13px">${escapeHTML(freq)}</div></div></div>
                <div class="col-md-2 col-6"><div class="p-2 border rounded bg-light"><div class="text-muted" style="font-size:11px">Target Kinerja</div><div class="fw-bold text-primary mt-1" style="font-size:13px">${escapeHTML(tLbl)}</div></div></div>
                <div class="col-md-3 col-12"><div class="p-2 border rounded bg-light"><div class="text-muted" style="font-size:11px">Realisasi Aktual</div><div class="fw-bold text-success mt-1" style="font-size:13px">${escapeHTML(formatNumber(aVal))} ${escapeHTML(item.unit_measure||'')}</div></div></div>
            </div>
            ${(freq === 'Per Project' && pStart && pEnd) ? `
            <div class="p-2 border rounded bg-light mb-3">
                <div class="text-muted" style="font-size:11px"><i class="bi bi-calendar-range me-1 text-primary"></i>Rentang Durasi Proyek</div>
                <div class="fw-semibold text-dark mt-1" style="font-size:12px">${formatDate(pStart)} s.d. ${formatDate(pEnd)}</div>
            </div>` : ''}
            <div class="p-3 border rounded mb-3 bg-white"><div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-semibold text-dark" style="font-size:11.5px">Persentase Pemenuhan Target</span><span class="fw-bold text-dark" style="font-size:13px">${pct}%</span></div><div class="progress-custom" style="height:10px"><div class="progress-bar-custom ${progressColorClass({status:st})}" style="width:${pct}%"></div></div></div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><div class="p-2 border rounded bg-light h-100"><div class="text-muted" style="font-size:11px">Keterangan Target</div><div class="text-dark mt-1" style="font-size:12px">${escapeHTML(item.description||'-')}</div></div></div>
                <div class="col-md-6"><div class="p-2 border rounded bg-light h-100"><div class="text-muted" style="font-size:11px">Satuan / Periode</div><div class="text-dark mt-1" style="font-size:12px">${escapeHTML(item.unit_measure||'-')} &nbsp;|&nbsp; Tahun ${item.year||item.period||'-'}</div></div></div>
            </div>
            <div class="border-top pt-3">
                <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold text-dark mb-0" style="font-size:12.5px"><i class="bi bi-chat-left-text me-1 text-success"></i> Catatan &amp; Tindak Lanjut (${notes.length})</h6><button type="button" class="btn btn-action-outline btn-sm py-1 px-2" style="font-size:11px" onclick="openNoteModal();"><i class="bi bi-plus-lg me-1"></i> Tambah Catatan</button></div>
                <div class="notes-modal-list" style="max-height:160px;overflow-y:auto;">${notes.length?notes.map(n=>{const nId=n.id||0,nTxt=typeof n==='string'?n:(n.note||''),nAuth=typeof n==='object'&&n.author?n.author:'Admin HSSE',nDs=typeof n==='object'&&n.created_at?formatDateTime(n.created_at):'';return `<div class="note-item-box p-2 mb-2 bg-light border-start border-3 border-success rounded d-flex justify-content-between align-items-start gap-2"><div class="flex-grow-1" style="min-width:0"><div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-semibold text-dark" style="font-size:11px"><i class="bi bi-person-fill text-muted me-1"></i>${escapeHTML(nAuth)}</span>${nDs?`<span class="text-muted" style="font-size:10px">${nDs}</span>`:''}</div><div class="text-secondary" style="font-size:11.5px;line-height:1.35;word-break:break-word;">${escapeHTML(nTxt)}</div></div><div class="d-flex gap-1 flex-shrink-0">${nId?`<button type="button" class="btn btn-link text-primary p-0" onclick="openEditNoteModal(${nId},${escapeHTML(JSON.stringify(nTxt))})"><i class="bi bi-pencil" style="font-size:11px"></i></button><button type="button" class="btn btn-link text-danger p-0" onclick="deleteNote(${nId})"><i class="bi bi-trash" style="font-size:11px"></i></button>`:''}</div></div>`;}).join(''):'<div class="text-muted text-center py-3 bg-light rounded" style="font-size:11px">Belum ada catatan evaluasi untuk KPI ini.</div>'}</div>
            </div>`;
    }

    // ── FORM TAMBAH KPI ──────────────────────────────────────
    function selectKpiType(type) {
        $('kpiType').value = type;
        $('btnLeading').className = 'kpi-type-btn' + (type==='Leading'?' selected-leading':'');
        $('btnLagging').className = 'kpi-type-btn' + (type==='Lagging'?' selected-lagging':'');
        $('typeHintBox').innerHTML = type==='Leading'
            ? '<strong class="text-primary">Leading</strong> &mdash; Indikator proaktif. Semakin banyak semakin bagus. Progres dihitung bertahap (persentase terhadap target).'
            : '<strong style="color:#7c3aed">Lagging</strong> &mdash; Indikator reaktif. Semakin sedikit semakin bagus. Target idealnya 0 (contoh: insiden, kecelakaan).';
    }
    function openKPIModal(data = null) {
        $('kpiForm').reset();
        selectKpiType('Leading');
        if (data) {
            $('kpiModalTitle').innerHTML = '<i class="bi bi-pencil-square me-1"></i> Edit KPI Kontraktor';
            $('kpiId').value = data.id;
            $('kpiName').value = data.title||data.name||'';
            $('kpiContractorTab').value = getContractorName(data)||'';
            $('kpiTargetFrequency').value = data.target_frequency || 'Tahunan';
            const pStart = data.project_start_date || data.project_start || data.start_date || data.start || '';
            const pEnd = data.project_end_date || data.project_end || data.end_date || data.end || '';
            $('kpiProjectStartDate').value = pStart ? pStart.split('T')[0] : '';
            $('kpiProjectEndDate').value = pEnd ? pEnd.split('T')[0] : '';
            onFrequencyChange(data.target_frequency || 'Tahunan');
            $('kpiTargetValue').value = data.target??data.targetValue??0;
            $('kpiUnitMeasure').value = data.unit_measure||'';
            $('kpiDescription').value = data.description||'';
            selectKpiType(data.category||data.type||'Leading');
        } else {
            $('kpiModalTitle').innerHTML = '<i class="bi bi-bullseye me-1"></i> Tambah KPI Kontraktor';
            $('kpiId').value = '';
            $('kpiTargetFrequency').value = 'Tahunan';
            const todayStr = new Date().toISOString().split('T')[0];
            const threeMo = new Date(Date.now() + 90*24*60*60*1000).toISOString().split('T')[0];
            $('kpiProjectStartDate').value = todayStr;
            $('kpiProjectEndDate').value = threeMo;
            onFrequencyChange('Tahunan');
            if (activeContractor) $('kpiContractorTab').value = activeContractor;
        }
        kpiModal.show();
    }

    let isSubmittingRealisasi = false;
    function openEditRealisasi(id) {
        const item = kpiData.find(x=>x.id===id); if (!item) return;
        selectedId = id;
        const freq = item.target_frequency || 'Tahunan';
        const selYear = ($('filterYear') && $('filterYear').value !== 'all') ? $('filterYear').value : null;
        const prog = getKPIProgress(item, new Date().getMonth() + 1, selYear);

        $('editRealisasiKpiId').value = id;
        $('editRealisasiKpiName').textContent = item.title||item.name||'-';
        $('editRealisasiFreqBadge').textContent = freq;
        $('editRealisasiTarget').textContent = `${item.target!=null?item.target:'-'} ${item.unit_measure||''}`;
        $('editRealisasiCurrent').textContent = `${formatNumber(prog.actual)} ${item.unit_measure||''}`;
        $('editRealisasiValue').value = prog.actual;
        $('editRealisasiDate').value = new Date().toISOString().split('T')[0];
        $('editRealisasiNote').value = ''; $('editRealisasiDoc').value = '';

        // Reset submit button state
        isSubmittingRealisasi = false;
        const sb = $('btnSaveRealisasi') || $('editRealisasiForm')?.querySelector('button[type="submit"]');
        if (sb) {
            sb.disabled = false;
            sb.innerHTML = '<i class="bi bi-check-lg me-1"></i> Simpan Realisasi';
        }

        const helpBox = $('editRealisasiHelpText');
        if (freq === 'Bulanan') {
            helpBox.innerHTML = '<i class="bi bi-info-circle me-1 text-primary"></i><strong>KPI Bulanan</strong>: Input nilai realisasi khusus bulan berjalan (terisolasi/reset tiap bulan, rekam jejak historis tersimpan).';
        } else if (freq === 'Per Project') {
            helpBox.innerHTML = '<i class="bi bi-info-circle me-1 text-primary"></i><strong>KPI Per Project</strong>: Input nilai akumulatif sepanjang rentang tanggal proyek yang diatur.';
        } else {
            helpBox.innerHTML = '<i class="bi bi-info-circle me-1 text-primary"></i><strong>KPI Tahunan</strong>: Input akumulatif (YTD) dari Januari hingga bulan saat ini pada tahun berjalan.';
        }

        let rawHist = item.realization_history || item.history || [];
        // Bersihkan duplikat berurutan untuk tampilan
        const hist = rawHist.filter((h, i, arr) => {
            if (i === 0) return true;
            const prev = arr[i - 1];
            return !(prev.date === h.date && Number(prev.actual) === Number(h.actual) && (prev.note || '') === (h.note || ''));
        });

        $('editRealisasiHistoryList').innerHTML = hist.length
            ? hist.map(h=>`<div class="realisasi-history-item"><div class="realisasi-timeline-dot"></div><div class="flex-grow-1"><div class="d-flex justify-content-between"><span class="fw-semibold text-dark">${formatNumber(h.actual)} ${escapeHTML(item.unit_measure||'')}</span><span class="text-muted" style="font-size:10px">${formatDate(h.date)}</span></div>${h.note?`<div class="text-muted" style="font-size:10.5px">${escapeHTML(h.note)}</div>`:''}</div></div>`).join('')
            : '<div class="text-muted text-center py-3" style="font-size:11px">Belum ada riwayat update.</div>';
        editRealisasiModal.show();
    }
    $('editRealisasiForm').addEventListener('submit', e => {
        e.preventDefault();
        if (isSubmittingRealisasi) return;

        const id = $('editRealisasiKpiId').value;
        const nVal = Number($('editRealisasiValue').value);
        const date = $('editRealisasiDate').value;
        const note = $('editRealisasiNote').value.trim();
        const docFn = $('editRealisasiDoc').files.length ? $('editRealisasiDoc').files[0].name : null;
        if (!id) return;

        const sb = $('btnSaveRealisasi') || e.target.querySelector('button[type="submit"]');
        const originalHtml = sb ? sb.innerHTML : '<i class="bi bi-check-lg me-1"></i> Simpan Realisasi';
        if (sb) {
            sb.disabled = true;
            sb.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...';
        }
        isSubmittingRealisasi = true;

        fetch(`/hsse-plan/${id}`, {
            method:'PUT', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({realization:nVal, history_date:date, history_note:note, history_doc:docFn})
        }).then(r=>r.json()).then(data => {
            isSubmittingRealisasi = false;
            if (sb) {
                sb.disabled = false;
                sb.innerHTML = originalHtml;
            }
            if (data.success && data.kpi) {
                const k=data.kpi, idx=kpiData.findIndex(x=>x.id==id);
                if (idx!==-1) {
                    kpiData[idx]=k;
                    kpiData[idx].history = k.realization_history || [];
                }
                editRealisasiModal.hide(); applyAll();
                Swal.fire({icon:'success',title:'Realisasi Diperbarui!',text:data.message||'Realisasi KPI berhasil diperbarui.',timer:2000,showConfirmButton:false});
            } else Swal.fire('Gagal', data.message||'Gagal memperbarui realisasi KPI.', 'error');
        }).catch(()=>{
            isSubmittingRealisasi = false;
            if (sb) {
                sb.disabled = false;
                sb.innerHTML = originalHtml;
            }
            Swal.fire('Error','Terjadi kesalahan sistem saat menyimpan realisasi.','error');
        });
    });

    // ── CATATAN ───────────────────────────────────────────────
    function openNoteModal() {
        if (!selectedId) { Swal.fire('Perhatian','Pilih salah satu KPI terlebih dahulu.','info'); return; }
        const item = kpiData.find(x=>x.id===selectedId);
        $('noteForm').reset(); $('noteKpiName').value = item?(item.title||item.name):'-'; noteModal.show();
    }
    $('noteForm').addEventListener('submit', e => {
        e.preventDefault(); if (!selectedId) return;
        const noteText=$('noteText').value.trim(), noteAuthor=$('noteAuthor').value.trim(); if (!noteText) return;
        const sb=e.target.querySelector('button[type="submit"]'); if(sb)sb.disabled=true;
        fetch(`/hsse-plan/${selectedId}/notes`,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({note:noteText,author:noteAuthor||null})})
        .then(r=>r.json()).then(data=>{
            if(sb)sb.disabled=false;
            if(data.success&&data.note){const item=kpiData.find(x=>x.id===selectedId);if(item){if(!item.notes)item.notes=[];item.notes.unshift(data.note);}noteModal.hide();$('noteText').value='';$('noteAuthor').value='';renderDetail();if($('kpiDetailModal').classList.contains('show')&&item)renderKPIDetailModal(item);Swal.fire({icon:'success',title:'Berhasil!',text:data.message||'Catatan berhasil ditambahkan.',timer:1800,showConfirmButton:false});}
            else Swal.fire('Gagal',data.message||'Gagal menyimpan catatan.','error');
        }).catch(()=>{if(sb)sb.disabled=false;Swal.fire('Error','Terjadi kesalahan sistem saat menyimpan catatan.','error');});
    });
    function openEditNoteModal(noteId, currentText) { $('editNoteId').value=noteId; $('editNoteText').value=currentText; editNoteModal.show(); }
    $('editNoteForm').addEventListener('submit', e => {
        e.preventDefault(); if(!selectedId)return;
        const noteId=$('editNoteId').value, noteText=$('editNoteText').value.trim(); if(!noteId||!noteText)return;
        const sb=e.target.querySelector('button[type="submit"]'); if(sb)sb.disabled=true;
        fetch(`/hsse-plan/${selectedId}/notes/${noteId}`,{method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({note:noteText})})
        .then(r=>r.json()).then(data=>{
            if(sb)sb.disabled=false;
            if(data.success&&data.note){const item=kpiData.find(x=>x.id===selectedId);if(item&&item.notes){const idx=item.notes.findIndex(n=>(n.id||n)==noteId);if(idx!==-1)item.notes[idx]=data.note;}editNoteModal.hide();renderDetail();if($('kpiDetailModal').classList.contains('show')&&item)renderKPIDetailModal(item);Swal.fire({icon:'success',title:'Berhasil!',text:data.message||'Catatan berhasil diperbarui.',timer:1800,showConfirmButton:false});}
            else Swal.fire('Gagal',data.message||'Gagal memperbarui catatan.','error');
        }).catch(()=>{if(sb)sb.disabled=false;Swal.fire('Error','Terjadi kesalahan sistem saat memperbarui catatan.','error');});
    });
    function deleteNote(noteId) {
        if(!selectedId||!noteId)return;
        Swal.fire({title:'Hapus Catatan?',text:'Apakah Anda yakin ingin menghapus catatan evaluasi ini?',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',cancelButtonColor:'#64748b',confirmButtonText:'Ya, Hapus!',cancelButtonText:'Batal'})
        .then(r=>{if(r.isConfirmed)fetch(`/hsse-plan/${selectedId}/notes/${noteId}`,{method:'DELETE',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'}}).then(res=>res.json()).then(data=>{if(data.success){const item=kpiData.find(x=>x.id===selectedId);if(item&&item.notes)item.notes=item.notes.filter(n=>(n.id||n)!=noteId);renderDetail();if($('kpiDetailModal').classList.contains('show')&&item)renderKPIDetailModal(item);Swal.fire({icon:'success',title:'Terhapus!',text:data.message||'Catatan berhasil dihapus.',timer:1800,showConfirmButton:false});}else Swal.fire('Gagal',data.message||'Tidak dapat menghapus catatan.','error');}).catch(()=>Swal.fire('Error','Terjadi kesalahan saat menghapus catatan.','error'));});
    }

    // ── HAPUS KPI ─────────────────────────────────────────────
    function deleteKPI(id) {
        const item=kpiData.find(x=>x.id===id);
        Swal.fire({title:'Hapus KPI?',html:`Apakah Anda yakin ingin menghapus indikator <b>"${escapeHTML(item?(item.title||item.name):'')}"</b>?`,icon:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',cancelButtonColor:'#64748b',confirmButtonText:'Ya, Hapus!',cancelButtonText:'Batal'})
        .then(r=>{if(r.isConfirmed)fetch(`/hsse-plan/${id}`,{method:'DELETE',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'}}).then(res=>res.json()).then(data=>{if(data.success){kpiData=kpiData.filter(x=>x.id!==id);if(selectedId===id)selectedId=kpiData[0]?kpiData[0].id:null;applyAll();Swal.fire('Terhapus!','Indikator KPI berhasil dihapus.','success');}else Swal.fire('Gagal',data.message||'Gagal menghapus KPI.','error');}).catch(()=>Swal.fire('Error','Terjadi kesalahan sistem saat menghapus KPI.','error'));});
    }

    // ── SIMPAN KPI ────────────────────────────────────────────
    $('kpiForm').addEventListener('submit', e => {
        e.preventDefault();
        const id = $('kpiId').value, cTab = $('kpiContractorTab').value;
        let cId = null;
        if (cTab) { const found=kpiData.find(x=>getContractorName(x).toLowerCase()===cTab.toLowerCase()); if(found)cId=found.contractor_id; }

        const freq = $('kpiTargetFrequency').value;
        const curYear = $('filterYear') && $('filterYear').value !== 'all' ? parseInt($('filterYear').value) : new Date().getFullYear();

        const payload = {
            title: $('kpiName').value,
            contractor_name: cTab || null,
            contractor_id: cId || null,
            category: $('kpiType').value,
            target_frequency: freq,
            unit: 'Dieng Unit 1',
            target: Number($('kpiTargetValue').value),
            unit_measure: $('kpiUnitMeasure').value,
            realization: 0,
            year: curYear,
            start_date: (freq === 'Per Project' && $('kpiProjectStartDate').value) ? $('kpiProjectStartDate').value : (curYear + '-01-01'),
            end_date: (freq === 'Per Project' && $('kpiProjectEndDate').value) ? $('kpiProjectEndDate').value : (curYear + '-12-31'),
            project_start_date: freq === 'Per Project' ? ($('kpiProjectStartDate').value || null) : null,
            project_end_date: freq === 'Per Project' ? ($('kpiProjectEndDate').value || null) : null,
            status: 'Berlangsung',
            description: $('kpiDescription').value
        };

        const isUpd=!!id, url=isUpd?`/hsse-plan/${id}`:'/hsse-plan', method=isUpd?'PUT':'POST';
        fetch(url,{method,headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(payload)})
        .then(r=>r.json()).then(data=>{if(data.success&&data.kpi){const k=data.kpi;if(isUpd){const idx=kpiData.findIndex(x=>x.id==id);if(idx!==-1)kpiData[idx]=k;}else{kpiData.unshift(k);selectedId=k.id;}kpiModal.hide();applyAll();Swal.fire('Berhasil!',data.message||'Data KPI berhasil disimpan.','success');}else Swal.fire('Gagal',data.message||'Gagal menyimpan KPI.','error');})
        .catch(()=>Swal.fire('Error','Terjadi kesalahan sistem saat menyimpan KPI.','error'));
    });

    // ── EXPORT PDF ────────────────────────────────────────────
    function exportPDF() {
        const p=new URLSearchParams();
        if($('filterYear') && $('filterYear').value) p.set('year', $('filterYear').value);
        const catVal = $('tableCategoryFilter')?.value || $('filterType')?.value;
        if(catVal) p.set('type', catVal);
        if($('filterStatus').value)p.set('status',$('filterStatus').value);
        if($('filterMonth').value)p.set('month',$('filterMonth').value);
        if($('globalSearch').value)p.set('search',$('globalSearch').value);
        if(activeContractor)p.set('contractor',activeContractor);
        window.location.href='{{ route("hsse-plan.export") }}?'+p.toString();
    }

    function applyAll() { updateTabBadges(); renderTable(); updateStats(); renderDetail(); }

    ['filterYear','filterMonth','filterStatus'].forEach(id => {
        if($(id)) $(id).addEventListener('change', () => { currentPage = 1; applyAll(); });
    });

    if ($('tableCategoryFilter')) {
        $('tableCategoryFilter').addEventListener('change', function() {
            if ($('filterType')) $('filterType').value = this.value;
            currentPage = 1;
            applyAll();
        });
    }

    if ($('filterType')) {
        $('filterType').addEventListener('change', function() {
            if ($('tableCategoryFilter')) $('tableCategoryFilter').value = this.value;
            currentPage = 1;
            applyAll();
        });
    }

    $('globalSearch').addEventListener('input',()=>{currentPage=1;applyAll();});

    applyAll();
</script>
@endpush
