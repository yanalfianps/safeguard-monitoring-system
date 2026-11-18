@extends('layouts.app', [
    'title' => 'Issue & Follow Up - Pemantauan Temuan & Tindak Lanjut',
    'activeModule' => 'issue-follow-up'
])

@push('styles')
<style>
    /* 1. KARTU RINGKASAN ATAS (3 KARTU: All / Fungsi, Open, Closed) */
    .summary-cards-top-ifu {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 14px;
    }

    .summary-card-ifu {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        text-decoration: none !important;
        color: #0f172a;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: all 0.16s ease;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
        cursor: pointer;
    }

    .summary-card-ifu:hover {
        border-color: #94a3b8;
        transform: translateY(-1px);
        text-decoration: none !important;
    }

    .summary-card-ifu.active-card-all {
        border-color: #334155;
        background: #f8fafc;
    }

    .summary-card-ifu.active-card-open {
        border-color: #dc2626;
        background: #fef2f2;
    }

    .summary-card-ifu.active-card-closed {
        border-color: #059669;
        background: #ecfdf5;
    }

    .summary-card-label {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
        text-decoration: none !important;
    }

    .summary-card-value {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.3px;
        text-decoration: none !important;
    }

    .summary-card-value.val-all {
        color: #0f172a;
    }

    .summary-card-value.val-open {
        color: #dc2626;
    }

    .summary-card-value.val-closed {
        color: #059669;
    }

    /* 2. EMPAT GRAFIK RING FUNGSI SAFEGUARD (1 BARIS HORIZONTAL) */
    .safeguard-rings-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 14px;
    }

    .safeguard-ring-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        transition: all 0.18s ease;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
        position: relative;
    }

    .safeguard-ring-card:hover {
        border-color: #94a3b8;
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
    }

    .safeguard-ring-card.active-safeguard {
        border-color: #059669;
        background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
        box-shadow: 0 6px 16px rgba(5, 150, 105, 0.14);
    }

    .ring-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }

    .ring-card-title {
        font-size: 13.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .ring-card-total-badge {
        font-size: 11px;
        font-weight: 700;
        color: #334155;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        padding: 2px 9px;
        border-radius: 999px;
    }

    .ring-card-stats-bar {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 6px 10px;
        text-align: center;
        font-size: 11.5px;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .ring-canvas-wrap {
        height: 130px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
    }

    .ring-card-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .btn-ring-export {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        color: #334155;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 4px 10px;
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .btn-ring-export:hover {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #dc2626;
    }

    /* 3. BARIS FILTER PENCARIAN */
    .filter-grid-ifu {
        display: grid;
        grid-template-columns: minmax(265px, 315px) 185px 125px 185px 1fr auto;
        gap: 8px;
        align-items: center;
    }

    /* Date Range Container Menyatu */
    .date-range-container {
        position: relative;
        display: flex;
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0 10px;
        height: 36px;
        width: 100%;
        box-sizing: border-box;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .date-range-container:hover {
        border-color: #94a3b8;
    }

    .date-range-container:focus-within {
        border-color: #059669;
        box-shadow: 0 0 0 0.25rem rgba(5, 150, 105, 0.15);
    }

    .date-range-icon {
        color: #94a3b8;
        font-size: 13px;
        margin-right: 8px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
    }

    .date-range-inputs {
        display: flex;
        align-items: center;
        flex: 1;
        gap: 4px;
        min-width: 0;
    }

    .date-range-input {
        width: 100%;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        font-family: inherit;
        font-size: 12px;
        color: #334155;
        font-weight: 400;
        padding: 0;
        line-height: normal;
        cursor: pointer;
    }

    .date-range-input::-webkit-calendar-picker-indicator {
        opacity: 0.6;
        cursor: pointer;
        font-size: 13px;
        margin-left: 2px;
        margin-right: 2px;
        transition: opacity 0.15s;
    }

    .date-range-input::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
    }

    .date-range-separator {
        color: #64748b;
        font-size: 12px;
        font-weight: 500;
        padding: 0 6px;
        display: flex;
        align-items: center;
        user-select: none;
        flex-shrink: 0;
    }

    .date-range-clear-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        padding: 0;
        margin-left: 6px;
        font-size: 12px;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        transition: color 0.15s;
    }

    .date-range-clear-btn:hover {
        color: #ef4444;
    }

    .date-range-container.has-date .date-range-clear-btn {
        display: flex;
    }

    /* 4. STATUS BADGE (Open = Merah, Closed = Hijau) */
    .status-pill-ifu {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 3.5px 11px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }

    .status-open {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .status-in-progress {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .status-closed {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    /* Teks Fungsi Polos di Tabel */
    .safeguard-plain-text {
        color: #1e293b;
        font-weight: 500;
        font-size: 12.5px;
    }

    /* Upload Multi-file Box (Persis seperti modul Event) */
    .upload-drag-area {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.2s;
    }

    .upload-drag-area:hover,
    .upload-drag-area.drag-over {
        border-color: var(--green-primary, #059669);
        background: #ecfdf5;
    }

    @media (max-width: 1280px) {
        .safeguard-rings-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .filter-grid-ifu {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .summary-cards-top-ifu {
            grid-template-columns: 1fr;
        }
        .safeguard-rings-grid {
            grid-template-columns: 1fr;
        }
        .filter-grid-ifu {
            grid-template-columns: 1fr;
        }
    }
    .safeguard-ring-card,
    .filter-grid-ifu,
    .date-range-container {
        min-width: 0;
        max-width: 100%;
    }
    @media (max-width: 1280px) {
        .filter-grid-ifu {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }
    @media (max-width: 991.98px) {
        .filter-grid-ifu {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
    @media (max-width: 767.98px) {
        .filter-grid-ifu {
            grid-template-columns: minmax(0, 1fr) !important;
        }
        .date-range-container {
            flex-wrap: wrap;
            height: auto !important;
            min-height: 36px;
            padding: 8px 10px;
            gap: 6px;
        }
        .date-range-inputs {
            flex-direction: column;
            align-items: stretch;
            gap: 6px;
            width: 100%;
        }
        .date-range-separator {
            display: none;
        }
    }
</style>
@endpush

@section('content')
@php
    $currDiv = strtoupper(trim((string) ($activeDivision ?? request('hsse_division', ''))));
    if ($currDiv !== '') {
        $currDiv = \App\Models\IssueFollowUp::normalizeDivisionCode($currDiv) ?? $currDiv;
    }
    $currStatus = trim((string) request('status', ''));
    $divSum = $divisionSummary ?? [];

    // Urutan baku 4 Fungsi Safeguard: 1. Health & Safety, 2. Security, 3. Environment, 4. Social
    $safeguardOrder = [
        'H' => 'Health & Safety',
        'S_SECURITY' => 'Security',
        'E' => 'Environment',
        'S_SOCIAL' => 'Social',
    ];

    $activeSafeguardLabel = $safeguardOrder[$currDiv] ?? 'All';

    // Hitung total, open, closed & persentase untuk 3 Kartu Ringkasan Atas
    $topTotal = (int) ($stats['total_issues'] ?? 0);
    $topOpen = (int) ($stats['open_issues'] ?? 0);
    $topClosed = (int) ($stats['closed_issues'] ?? 0);
    $topOpenPct = $topTotal > 0 ? (int) round(($topOpen / $topTotal) * 100) : 0;
    $topClosedPct = $topTotal > 0 ? (int) round(($topClosed / $topTotal) * 100) : 0;

    $queryWithoutStatus = collect(request()->query())->except(['status', 'page'])->all();
    $queryWithoutDivAndStatus = collect(request()->query())->except(['hsse_division', 'division', 'status', 'page'])->all();
    $allSourcesList = $sources ?? \App\Models\IssueFollowUp::DEFAULT_SOURCES;
@endphp

<!-- 1. HEADER HALAMAN -->
<x-module-header
    title="Issue & Follow Up"
    icon="bi bi-clipboard-check-fill"
    icon-bg="#059669"
    icon-shadow="rgba(5, 150, 105, 0.25)"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Issue & Follow Up'],
    ]"
>
    <x-slot:actions>
        @adminManagement
            <button type="button" class="btn-action-primary hsse-action-primary" id="btnTambahTemuan" onclick="openCreateIssueModal()">
                <i class="bi bi-plus-lg"></i> Tambah Temuan
            </button>
            <button type="button" class="btn-action-outline" id="btnManageSources" onclick="openManageSourcesModal()">
                <i class="bi bi-tags-fill text-success"></i> Kelola Sumber Temuan
            </button>
        @endadminManagement
        @canExportPdf
            <button type="button" class="btn-action-outline" id="btnExportIssue" onclick="openExportPdfModal()">
                <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Export PDF
            </button>
        @endcanExportPdf
    </x-slot:actions>
</x-module-header>

@if(session('success'))
    <div class="alert alert-success py-2 px-3 small mb-3 d-flex align-items-center justify-content-between">
        <div><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger py-2 px-3 small mb-3 d-flex align-items-center justify-content-between">
        <div><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}</div>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger py-2 px-3 small mb-3">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Mohon periksa kembali input Anda:</div>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- 2. KARTU RINGKASAN ATAS (3 KARTU: All / Fungsi Terpilih, Open, Closed) -->
<div class="summary-cards-top-ifu" id="topSummaryCards">
    <!-- Kartu 1: All (atau Nama Fungsi Safeguard jika sedang difilter) -->
    <a href="{{ route('issue-follow-up.index', $queryWithoutStatus) }}"
       class="summary-card-ifu {{ $currStatus === '' || strcasecmp($currStatus, 'All') === 0 ? 'active-card-all' : '' }}"
       id="cardSummaryAll">
        <div class="summary-card-label" id="statAllLabel">{{ $activeSafeguardLabel }}</div>
        <div class="summary-card-value val-all" id="statTotalIssues">{{ number_format($topTotal, 0, ',', '.') }}</div>
    </a>

    <!-- Kartu 2: Open (Angka & Persentase berdampingan, dapat diklik untuk filter) -->
    <a href="{{ route('issue-follow-up.index', array_merge($queryWithoutStatus, ['status' => 'Open'])) }}"
       class="summary-card-ifu {{ strcasecmp($currStatus, 'Open') === 0 ? 'active-card-open' : '' }}"
       id="cardSummaryOpen">
        <div class="summary-card-label">Open</div>
        <div class="summary-card-value val-open" id="statOpenIssues">
            {{ number_format($topOpen, 0, ',', '.') }} ({{ $topOpenPct }}%)
        </div>
    </a>

    <!-- Kartu 3: Closed (Angka & Persentase berdampingan, dapat diklik untuk filter) -->
    <a href="{{ route('issue-follow-up.index', array_merge($queryWithoutStatus, ['status' => 'Closed'])) }}"
       class="summary-card-ifu {{ in_array(strtolower($currStatus), ['closed', 'close'], true) ? 'active-card-closed' : '' }}"
       id="cardSummaryClosed">
        <div class="summary-card-label">Closed</div>
        <div class="summary-card-value val-closed" id="statClosedIssues">
            {{ number_format($topClosed, 0, ',', '.') }} ({{ $topClosedPct }}%)
        </div>
    </a>
</div>

<!-- 3. EMPAT GRAFIK RING FUNGSI SAFEGUARD (1 BARIS HORIZONTAL) -->
<div class="safeguard-rings-grid" id="safeguardRingsRow">
    @foreach($safeguardOrder as $divCode => $divLabel)
        @php
            $dStat = $divSum[$divCode] ?? ['total' => 0, 'open' => 0, 'closed' => 0, 'open_pct' => 0, 'closed_pct' => 0];
            $dTotal = (int) ($dStat['total'] ?? 0);
            $dOpen = (int) ($dStat['open'] ?? 0);
            $dClosed = (int) ($dStat['closed'] ?? 0);
            $dOpenPct = $dTotal > 0 ? (int) round(($dOpen / $dTotal) * 100) : 0;
            $dClosedPct = $dTotal > 0 ? (int) round(($dClosed / $dTotal) * 100) : 0;
            $isActiveDiv = ($currDiv === $divCode);

            // Jika sedang aktif dan diklik kembali -> reset filter fungsi ke All
            $targetUrl = $isActiveDiv
                ? route('issue-follow-up.index', $queryWithoutDivAndStatus)
                : route('issue-follow-up.index', array_merge($queryWithoutDivAndStatus, ['hsse_division' => $divCode]));

            $exportDivUrl = route('issue-follow-up.export', array_merge(request()->except(['hsse_division', 'division', 'hsse_divisions', 'page']), ['hsse_division' => $divCode]));
        @endphp
        <div class="safeguard-ring-card {{ $isActiveDiv ? 'active-safeguard' : '' }}"
             data-division="{{ $divCode }}"
             data-label="{{ $divLabel }}"
             onclick="window.location.href='{{ $targetUrl }}'">
            <div>
                <div class="ring-card-header">
                    <h3 class="ring-card-title">{{ $divLabel }}</h3>
                    <span class="ring-card-total-badge">Total: {{ $dTotal }}</span>
                </div>
                <div class="ring-card-stats-bar">
                    <span class="text-danger">Open: {{ $dOpen }} ({{ $dOpenPct }}%)</span>,
                    <span class="text-success ms-1">Closed: {{ $dClosed }} ({{ $dClosedPct }}%)</span>
                </div>
            </div>

            <div class="ring-canvas-wrap">
                <canvas id="ringCanvas_{{ $divCode }}"
                        data-open="{{ $dOpen }}"
                        data-closed="{{ $dClosed }}"></canvas>
            </div>

            <div class="ring-card-footer">
                @canExportPdf
                    <a href="{{ $exportDivUrl }}"
                       class="btn-ring-export"
                       onclick="event.stopPropagation();"
                       title="Export PDF khusus fungsi {{ $divLabel }}">
                        <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Export PDF
                    </a>
                @endcanExportPdf
            </div>
        </div>
    @endforeach
</div>

<!-- 4. BARIS PENCARIAN & FILTER -->
<div class="filter-card">
    <form method="GET" action="{{ route('issue-follow-up.index') }}" id="ifuFilterForm">
        <div class="filter-grid-ifu">
            <!-- Rentang Tanggal (Date Range Container Menyatu dengan pemisah s/d) -->
            <div class="date-range-container @if(request('start_date') || request('end_date')) has-date @endif" id="ifuDateRangeBox">
                <i class="bi bi-calendar-event date-range-icon"></i>
                <div class="date-range-inputs">
                    <input type="date"
                           name="start_date"
                           id="filterStartDate"
                           class="date-range-input"
                           placeholder="dd/mm/yyyy"
                           value="{{ request('start_date') }}"
                           title="Dari Tanggal Temuan"
                           onclick="try{this.showPicker()}catch(e){}"
                           onchange="syncIfuDateRangeState()">
                    <span class="date-range-separator">s/d</span>
                    <input type="date"
                           name="end_date"
                           id="filterEndDate"
                           class="date-range-input"
                           placeholder="dd/mm/yyyy"
                           value="{{ request('end_date') }}"
                           title="Sampai Tanggal Temuan"
                           onclick="try{this.showPicker()}catch(e){}"
                           onchange="syncIfuDateRangeState()">
                </div>
                <button type="button"
                        class="date-range-clear-btn"
                        id="btnClearIfuDateRange"
                        onclick="clearIfuDateRange()"
                        title="Hapus filter tanggal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Fungsi Safeguard -->
            <div>
                <select name="hsse_division" id="filterHsseDivision" class="form-select form-select-filter">
                    <option value="">Semua Fungsi Safeguard</option>
                    @foreach($safeguardOrder as $divCode => $divLabel)
                        <option value="{{ $divCode }}" {{ $currDiv === $divCode ? 'selected' : '' }}>{{ $divLabel }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <select name="status" id="filterStatus" class="form-select form-select-filter">
                    <option value="">All</option>
                    <option value="Open" {{ strcasecmp($currStatus, 'Open') === 0 ? 'selected' : '' }}>Open</option>
                    <option value="Closed" {{ in_array(strtolower($currStatus), ['closed', 'close'], true) ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <!-- Sumber Temuan -->
            <div>
                <select name="category" id="filterCategory" class="form-select form-select-filter">
                    <option value="">Semua Sumber Temuan</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Pencarian Judul / Lokasi / Deskripsi -->
            <div class="filter-input-wrap">
                <i class="bi bi-search filter-icon-addon"></i>
                <input type="search" name="search" id="filterSearch" class="form-control form-control-filter" value="{{ request('search') }}" placeholder="Cari judul temuan, lokasi, deskripsi...">
            </div>

            <!-- Aksi Filter & Reset -->
            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-success px-3">Filter</button>
                <a href="{{ route('issue-follow-up.index') }}" class="btn btn-sm btn-outline-secondary px-2">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- 5. TABEL DAFTAR TEMUAN & TINDAK LANJUT -->
<div class="table-card">
    <div class="table-topbar">
        <div>
            <h2 class="table-section-title mb-0">
                Daftar Temuan &amp; Tindak Lanjut
                <span class="badge bg-secondary-subtle text-secondary ms-2" style="font-size:10.5px;">{{ $issues->total() }} Data</span>
            </h2>
        </div>

        <div class="table-controls">
            <form method="GET" action="{{ route('issue-follow-up.index') }}" class="d-flex align-items-center gap-2">
                @foreach(request()->except(['sort', 'per_page', 'page']) as $k => $v)
                    @if(!is_array($v))
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <select name="sort" class="form-select form-select-sm" style="width:165px; font-size:11.5px" onchange="this.form.submit()">
                    <option value="date-desc" {{ request('sort', 'date-desc') === 'date-desc' ? 'selected' : '' }}>Terbaru (Default)</option>
                    <option value="date-asc" {{ request('sort') === 'date-asc' ? 'selected' : '' }}>Terlama</option>
                    <option value="status" {{ request('sort') === 'status' ? 'selected' : '' }}>Status</option>
                </select>
            </form>
        </div>
    </div>

    <div class="table-responsive-custom">
        <table class="table-custom" id="issueFollowUpTable">
            <thead>
                <tr>
                    <th style="width: 48px; text-align: center;">No.</th>
                    <th style="width: 125px;">Fungsi</th>
                    <th style="width: 220px;">Judul Temuan</th>
                    <th style="width: 155px;">Lokasi</th>
                    <th style="width: 95px; text-align: center;">Tanggal</th>
                    <th>Deskripsi</th>
                    <th style="width: 130px;">Sumber</th>
                    <th style="width: 105px; text-align: center;">Status</th>
                    <th style="width: 115px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="issueTableBody">
                @forelse($issues as $index => $issue)
                    @php
                        $isClosed = in_array(strtolower((string) $issue->status), ['closed', 'close'], true);
                        $statusBadge = $isClosed ? 'status-closed' : 'status-open';
                        $displayStatus = $isClosed ? 'Closed' : 'Open';
                    @endphp
                    <tr data-id="{{ $issue->id }}">
                        <td style="text-align: center; color: #64748b;">{{ $issues->firstItem() + $index }}</td>
                        <td>
                            <span class="safeguard-plain-text">{{ $issue->division_label }}</span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $issue->title }}</div>
                        </td>
                        <td>
                            <span class="text-dark">{{ $issue->location ?: '-' }}</span>
                        </td>
                        <td style="text-align: center;">
                            <span class="text-dark">{{ $issue->reported_date ? $issue->reported_date->format('d/m/Y') : '-' }}</span>
                        </td>
                        <td>
                            <div class="text-secondary" style="font-size: 11.5px; line-height: 1.4;">
                                {{ \Illuminate\Support\Str::limit($issue->description, 105) }}
                            </div>
                        </td>
                        <td>
                            <span class="text-dark fw-medium">{{ $issue->category ?: '-' }}</span>
                        </td>
                        <td style="text-align: center;">
                            <span class="status-pill-ifu {{ $statusBadge }}">{{ $displayStatus }}</span>
                            @if($isClosed && $issue->closed_date)
                                <div class="text-muted mt-1" style="font-size: 10.5px;">
                                    {{ $issue->closed_date->format('d/m/Y') }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div class="d-inline-flex align-items-center gap-1 justify-content-center">
                                <!-- Lihat Detail -->
                                <button type="button" class="btn-action-icon btn-action-view" onclick="openIssueDetailModal({{ $issue->id }})" title="Lihat Detail Temuan">
                                    <i class="bi bi-eye"></i>
                                </button>

                                @adminManagement
                                    <!-- Edit -->
                                    <button type="button" class="btn-action-icon btn-action-edit" onclick="openEditIssueModal({{ $issue->id }})" title="Edit Temuan">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Hapus -->
                                    <button type="button" class="btn-action-icon btn-action-delete" onclick="deleteIssuePrompt({{ $issue->id }}, {{ Js::from($issue->title) }}, {{ Js::from($issue->issue_code) }})" title="Hapus Temuan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endadminManagement
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-4 text-center">
                            <div class="py-4">
                                <div class="mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle bg-light text-secondary" style="width:56px;height:56px;font-size:24px;">
                                    <i class="bi bi-clipboard-check"></i>
                                </div>
                                <h6 class="fw-bold text-secondary mb-1">Belum ada data temuan pada filter ini</h6>
                                <p class="text-muted small mb-3">Klik tombol <strong>+ Tambah Temuan</strong> untuk mencatat temuan baru ke database.</p>
                                <button type="button" class="btn-action-primary mx-auto" onclick="openCreateIssueModal()">
                                    <i class="bi bi-plus-lg"></i> Tambah Temuan
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top gap-2">
        <div class="text-muted small">
            @if($issues->total() > 0)
                Menampilkan {{ $issues->firstItem() }}–{{ $issues->lastItem() }} dari {{ $issues->total() }} temuan
            @else
                Menampilkan 0 temuan
            @endif
        </div>
        @if($issues->hasPages())
            <div>
                {{ $issues->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<!-- MODAL 1: TAMBAH / EDIT TEMUAN -->
<div class="modal fade" id="issueFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="issueForm" method="POST" action="{{ route('issue-follow-up.store') }}" enctype="multipart/form-data" onsubmit="saveIssueForm(event)">
                @csrf
                <input type="hidden" id="formIssueId" name="id" value="">
                <div class="modal-header border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="header-icon-box green" style="width:34px; height:34px; font-size:16px;">
                            <i class="bi bi-clipboard-plus"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold" id="issueFormModalTitle">Tambah Temuan Baru</h5>
                            <div class="text-muted" style="font-size:11px;">Lengkapi data temuan, fungsi safeguard, sumber temuan, status, dan dokumentasi</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Baris 1: Judul Temuan & Fungsi Safeguard -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Judul Temuan <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="formTitle" class="form-control form-control-sm" placeholder="Contoh: Isolasi kabel power panel lampu sorot terkelupas" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Fungsi Safeguard <span class="text-danger">*</span></label>
                            <select name="hsse_division" id="formHsseDivision" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Fungsi Safeguard --</option>
                                @foreach($safeguardOrder as $divCode => $divLabel)
                                    <option value="{{ $divCode }}">{{ $divLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Baris 2: Tanggal Temuan & Lokasi -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Temuan <span class="text-danger">*</span></label>
                            <input type="date" name="reported_date" id="formReportedDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Lokasi</label>
                            <input type="text" name="location" id="formLocation" class="form-control form-control-sm" placeholder="Contoh: Wellpad HCE-7A / Ruang Genset">
                        </div>

                        <!-- Baris 3: Sumber Temuan & Status Temuan -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0" style="font-size:12px;">Sumber Temuan <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-success text-decoration-none fw-semibold" style="font-size:11px;" onclick="openManageSourcesModal()">
                                    <i class="bi bi-gear-fill me-1"></i>Kelola Sumber
                                </button>
                            </div>
                            <select name="category" id="formCategory" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Sumber Temuan --</option>
                                @foreach($allSourcesList as $src)
                                    <option value="{{ $src }}">{{ $src }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Status Temuan <span class="text-danger">*</span></label>
                            <select name="status" id="formStatus" class="form-select form-select-sm" onchange="syncClosedDateInput()" required>
                                <option value="Open">Open</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>

                        <!-- Baris 4: Tanggal Closed -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">
                                Tanggal Closed <span class="text-danger" id="closedDateRequiredStar" style="display:none;">*</span>
                            </label>
                            <input type="date" name="closed_date" id="formClosedDate" class="form-control form-control-sm" disabled>
                            <div class="form-text text-muted" style="font-size:10.5px;" id="closedDateHelpText">
                                Aktif dan wajib diisi apabila status temuan diatur <strong>Closed</strong>.
                            </div>
                        </div>

                        <!-- Baris 5: Deskripsi Temuan -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Deskripsi Temuan <span class="text-danger">*</span></label>
                            <textarea name="description" id="formDescription" class="form-control form-control-sm" rows="3" placeholder="Jelaskan rincian temuan dan tindak lanjut perbaikan..." required></textarea>
                        </div>

                        <!-- Baris 6: Unggah Dokumentasi / Lampiran (Multi-File) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px;">Dokumentasi / Lampiran (Multi-File)</label>

                            <!-- Kontainer Dokumen Lama yang Ditandai Dihapus (Hidden Inputs) -->
                            <div id="modalDeletedDocsContainer"></div>

                            <!-- Kontainer Dokumen Tersimpan (Mode Edit - Vertikal) -->
                            <div id="modalExistingDocsWrapper" class="mb-3 d-none">
                                <label class="form-label fw-semibold small text-muted d-block mb-2" style="font-size:11.5px;">
                                    <i class="bi bi-folder2-open me-1 text-primary"></i> Dokumen Tersimpan Saat Ini (<span id="modalExistingDocsCount">0</span>)
                                </label>
                                <div id="modalExistingDocsList" class="d-flex flex-column gap-2"></div>
                            </div>

                            <!-- Area Unggah Berkas Baru -->
                            <div class="upload-drag-area" id="ifuUploadDropzone"
                                 onclick="document.getElementById('formDocumentations').click()"
                                 ondragover="handleIfuDragOver(event)"
                                 ondragleave="handleIfuDragLeave(event)"
                                 ondrop="handleIfuDrop(event)">
                                <i class="bi bi-cloud-arrow-up fs-3 text-secondary d-block mb-1"></i>
                                <span class="fw-semibold text-dark d-block" style="font-size:12px;">Pilih Berkas Dokumentasi</span>
                                <span class="text-muted" style="font-size:11px;">Dapat memilih banyak berkas sekaligus (JPG, PNG, WEBP, PDF, DOCX, XLSX - maks 15 MB/file)</span>
                                <input type="file" name="documentations[]" id="formDocumentations" class="d-none" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" onchange="handleIfuFilesSelected(this)">
                            </div>
                            <div id="previewFilesIfuContainer" class="d-flex flex-column gap-2 mt-2"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-action-primary btn-sm px-4" id="btnSubmitIssue">
                        <i class="bi bi-save me-1"></i> Simpan Temuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: KELOLA SUMBER TEMUAN (TAMBAH & HAPUS MASTER SUMBER TEMUAN) -->
<div class="modal fade" id="manageSourcesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="header-icon-box green" style="width:34px; height:34px; font-size:16px;">
                        <i class="bi bi-tags-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold">Kelola Sumber Temuan</h5>
                        <div class="text-muted" style="font-size:11px;">Tambah atau hapus daftar pilihan Sumber Temuan</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addSourceForm" onsubmit="submitNewSource(event)" class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">Tambah Sumber Temuan Baru</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="newSourceNameInput" class="form-control" placeholder="Contoh: Audit Internal / Inspeksi K3" required>
                        <button type="submit" class="btn btn-success px-3" id="btnAddSourceSubmit">
                            <i class="bi bi-plus-lg me-1"></i> Tambah
                        </button>
                    </div>
                </form>

                <div class="fw-semibold text-secondary mb-2" style="font-size:11.5px;">Daftar Sumber Temuan Tersedia:</div>
                <div class="list-group list-group-flush border rounded" id="sourcesMasterList" style="max-height: 260px; overflow-y: auto;">
                    @foreach($allSourcesList as $srcItem)
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3" data-source-name="{{ $srcItem }}">
                            <span class="fw-medium text-dark" style="font-size:12.5px;">{{ $srcItem }}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteFindingSource({{ Js::from($srcItem) }})" title="Hapus Sumber Temuan">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: EXPORT PDF DENGAN CHECKLIST MULTI-SELECT FUNGSI SAFEGUARD -->
<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ route('issue-follow-up.export') }}" id="exportPdfForm">
                <!-- Pertahankan filter pencarian/tanggal/status/sumber saat ini -->
                @if(request('start_date'))
                    <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                @endif
                @if(request('end_date'))
                    <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                @endif
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                <div class="modal-header border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="header-icon-box" style="width:34px; height:34px; font-size:16px; background:#fef2f2; color:#dc2626;">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold">Export PDF Temuan &amp; Tindak Lanjut</h5>
                            <div class="text-muted" style="font-size:11px;">Pilih Fungsi Safeguard yang ingin diexport ke dokumen PDF</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold text-dark mb-0" style="font-size:12.5px;">Pilih Fungsi Safeguard:</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size:11.5px;" onclick="toggleAllExportDivisions(true)">Pilih Semua</button>
                            <span class="text-muted">|</span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-secondary text-decoration-none" style="font-size:11.5px;" onclick="toggleAllExportDivisions(false)">Reset</button>
                        </div>
                    </div>

                    <div class="border rounded p-3 bg-light mb-3">
                        @foreach($safeguardOrder as $divCode => $divLabel)
                            <div class="form-check mb-2">
                                <input class="form-check-input export-division-checkbox"
                                       type="checkbox"
                                       name="hsse_divisions[]"
                                       value="{{ $divCode }}"
                                       id="expDiv_{{ $divCode }}"
                                       {{ ($currDiv === '' || $currDiv === $divCode) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium text-dark" for="expDiv_{{ $divCode }}" style="font-size:13px;">
                                    {{ $divLabel }}
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-grid">
                        <a href="{{ route('issue-follow-up.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-check2-all me-1"></i> Export Seluruh Fungsi (Sesuai Filter Saat Ini)
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 4: DETAIL TEMUAN -->
<div class="modal fade" id="issueDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="header-icon-box green" style="width:34px; height:34px; font-size:16px;">
                        <i class="bi bi-clipboard-check-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold" id="detailModalTitle">Detail Temuan</h5>
                        <div class="text-muted" style="font-size:11px;" id="detailModalSubtitle">Informasi lengkap dan dokumentasi temuan</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="issueDetailModalBody">
                <div class="text-center py-4 text-muted">Memuat detail temuan...</div>
            </div>
            <div class="modal-footer border-top bg-light justify-content-between">
                <a href="#" id="detailFullPageLink" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Penuh
                </a>
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const IFU_BASE_URL = "{{ url('/issue-follow-up') }}";
    const SOURCES_STORE_URL = "{{ route('issue-follow-up.sources.store') }}";

    document.addEventListener('DOMContentLoaded', function () {
        initSafeguardRingCharts();
        syncClosedDateInput();
    });

    /**
     * Render 4 Grafik Ring (Donut) untuk 4 Fungsi Safeguard:
     * 1. Health & Safety, 2. Security, 3. Environment, 4. Social
     */
    function initSafeguardRingCharts() {
        if (typeof Chart === 'undefined') return;

        const codes = ['H', 'S_SECURITY', 'E', 'S_SOCIAL'];
        codes.forEach(code => {
            const canvas = document.getElementById(`ringCanvas_${code}`);
            if (!canvas) return;

            const openCount = parseInt(canvas.dataset.open || '0', 10);
            const closedCount = parseInt(canvas.dataset.closed || '0', 10);
            const total = openCount + closedCount;

            new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Closed', 'Open'],
                    datasets: [{
                        data: total > 0 ? [closedCount, openCount] : [1],
                        backgroundColor: total > 0 ? ['#10b981', '#ef4444'] : ['#e2e8f0'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '66%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: total > 0 }
                    }
                }
            });
        });
    }

    /**
     * Sinkronisasi input Tanggal Closed berdasarkan pilihan Status Temuan (Open / Closed).
     * Saat status 'Open': field Tanggal Closed disabled & dikosongkan.
     * Saat status 'Closed': field Tanggal Closed aktif & wajib diisi.
     */
    function syncClosedDateInput() {
        const statusSelect = document.getElementById('formStatus');
        const closedDateInput = document.getElementById('formClosedDate');
        const starEl = document.getElementById('closedDateRequiredStar');
        if (!statusSelect || !closedDateInput) return;

        const isClosed = statusSelect.value === 'Closed';
        if (isClosed) {
            closedDateInput.disabled = false;
            closedDateInput.required = true;
            if (!closedDateInput.value) {
                closedDateInput.value = "{{ date('Y-m-d') }}";
            }
            if (starEl) starEl.style.display = 'inline';
        } else {
            closedDateInput.value = '';
            closedDateInput.disabled = true;
            closedDateInput.required = false;
            if (starEl) starEl.style.display = 'none';
        }
    }

    function openCreateIssueModal() {
        const form = document.getElementById('issueForm');
        if (form) form.reset();

        document.getElementById('formIssueId').value = '';
        document.getElementById('formReportedDate').value = "{{ date('Y-m-d') }}";

        const activeFilter = "{{ $currDiv }}";
        if (document.getElementById('formHsseDivision')) {
            document.getElementById('formHsseDivision').value = ['H', 'S_SECURITY', 'E', 'S_SOCIAL'].includes(activeFilter) ? activeFilter : '';
        }

        document.getElementById('formStatus').value = 'Open';
        document.getElementById('formClosedDate').value = '';
        syncClosedDateInput();
        resetIfuSelectedFiles();

        const existingWrapper = document.getElementById('modalExistingDocsWrapper');
        if (existingWrapper) existingWrapper.classList.add('d-none');
        const existingList = document.getElementById('modalExistingDocsList');
        if (existingList) existingList.innerHTML = '';
        const deletedContainer = document.getElementById('modalDeletedDocsContainer');
        if (deletedContainer) deletedContainer.innerHTML = '';

        document.getElementById('issueFormModalTitle').textContent = 'Tambah Temuan Baru';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('issueFormModal')).show();
    }

    function markModalDeleteDoc(docId) {
        const row = document.getElementById('modal-existing-doc-' + docId);
        if (row) {
            row.remove();
        }
        const container = document.getElementById('modalDeletedDocsContainer');
        if (container) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'deleted_documentations[]';
            input.value = docId;
            container.appendChild(input);
        }
        const list = document.getElementById('modalExistingDocsList');
        const remaining = list ? list.querySelectorAll('.modal-existing-doc-row').length : 0;
        const countEl = document.getElementById('modalExistingDocsCount');
        if (countEl) countEl.textContent = remaining;
        if (list && remaining === 0) {
            list.innerHTML = '<div class="text-muted small py-2 px-1 fst-italic">Seluruh dokumen lama telah ditandai untuk dihapus.</div>';
        }
    }

    async function openEditIssueModal(id) {
        try {
            const res = await fetch(`${IFU_BASE_URL}/${id}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (!data.success) {
                Swal.fire('Error', data.message || 'Data tidak ditemukan.', 'error');
                return;
            }

            const i = data.issue;
            document.getElementById('formIssueId').value = i.id;
            document.getElementById('formReportedDate').value = i.reported_date ? String(i.reported_date).substring(0, 10) : '';

            if (document.getElementById('formHsseDivision')) {
                let divCode = String(i.hsse_division || '').toUpperCase().trim();
                if (divCode === 'S' || divCode === 'SOCIAL' || divCode === 'SOSIAL') divCode = 'S_SOCIAL';
                if (divCode === 'SECURITY' || divCode === 'SCURITY') divCode = 'S_SECURITY';
                document.getElementById('formHsseDivision').value = divCode;
            }

            document.getElementById('formTitle').value = i.title || '';

            const catSelect = document.getElementById('formCategory');
            if (catSelect) {
                const catVal = i.category || '';
                if (catVal && !Array.from(catSelect.options).some(o => o.value === catVal)) {
                    const newOpt = new Option(catVal, catVal, true, true);
                    catSelect.add(newOpt);
                }
                catSelect.value = catVal;
            }

            document.getElementById('formLocation').value = i.location || '';
            document.getElementById('formDescription').value = i.description || '';

            const isClosed = String(i.status || '').toLowerCase() === 'closed' || String(i.status || '').toLowerCase() === 'close';
            document.getElementById('formStatus').value = isClosed ? 'Closed' : 'Open';
            document.getElementById('formClosedDate').value = (isClosed && i.closed_date) ? String(i.closed_date).substring(0, 10) : '';
            syncClosedDateInput();
            resetIfuSelectedFiles();

            // Tampilkan Dokumen Lama (Vertikal)
            const docs = data.documentations || [];
            const existingWrapper = document.getElementById('modalExistingDocsWrapper');
            const existingList = document.getElementById('modalExistingDocsList');
            const existingCount = document.getElementById('modalExistingDocsCount');
            const deletedContainer = document.getElementById('modalDeletedDocsContainer');
            if (deletedContainer) deletedContainer.innerHTML = '';

            if (docs.length > 0) {
                if (existingWrapper) existingWrapper.classList.remove('d-none');
                if (existingCount) existingCount.textContent = docs.length;
                if (existingList) {
                    existingList.innerHTML = docs.map(d => {
                        const ext = String(d.file_name || d.original_name || '').split('.').pop().toLowerCase();
                        let icon = 'bi-file-earmark-text text-secondary';
                        if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(ext)) icon = 'bi-file-earmark-image text-success';
                        else if (ext === 'pdf') icon = 'bi-file-earmark-pdf text-danger';
                        else if (['doc', 'docx'].includes(ext)) icon = 'bi-file-earmark-word text-primary';
                        else if (['xls', 'xlsx'].includes(ext)) icon = 'bi-file-earmark-excel text-success';

                        const size = d.formatted_size || (d.file_size ? (d.file_size > 1048576 ? (d.file_size / 1048576).toFixed(1) + ' MB' : Math.round(d.file_size / 1024) + ' KB') : '-');
                        return `
                            <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-2 border bg-white shadow-sm w-100 modal-existing-doc-row" id="modal-existing-doc-${d.id}">
                                <div class="d-flex align-items-center gap-2 overflow-hidden me-3" style="min-width: 0;">
                                    <i class="bi ${icon} fs-5 flex-shrink-0"></i>
                                    <div class="text-truncate">
                                        <span class="fw-semibold text-dark" style="font-size: 12.5px;">${escapeHtml(d.original_name || d.file_name)}</span>
                                        <span class="text-muted ms-1" style="font-size: 11.5px;">(${size})</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                    <a href="${IFU_BASE_URL}/documentations/${encodeURIComponent(d.id)}/view" target="_blank" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 11.5px;" title="Lihat Berkas">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size: 11.5px;" onclick="markModalDeleteDoc(${d.id})" title="Hapus dokumen ini">
                                        <i class="bi bi-trash3"></i>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            } else {
                if (existingWrapper) existingWrapper.classList.add('d-none');
                if (existingList) existingList.innerHTML = '';
                if (existingCount) existingCount.textContent = '0';
            }

            document.getElementById('issueFormModalTitle').textContent = `Edit Temuan [${i.issue_code}]`;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('issueFormModal')).show();
        } catch (err) {
            Swal.fire('Error', 'Gagal memuat data temuan.', 'error');
        }
    }

    async function saveIssueForm(e) {
        e.preventDefault();
        const id = document.getElementById('formIssueId').value;
        const isEdit = Boolean(id);
        const url = isEdit ? `${IFU_BASE_URL}/${id}` : IFU_BASE_URL;

        const form = document.getElementById('issueForm');
        const formData = new FormData(form);
        if (isEdit) {
            formData.append('_method', 'PUT');
        }

        const btn = document.getElementById('btnSubmitIssue');
        btn.disabled = true;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            const body = await res.json();
            btn.disabled = false;

            if (!res.ok || !body.success) {
                let msg = body.message || 'Gagal menyimpan data temuan.';
                if (body.errors) {
                    msg = Object.values(body.errors).flat().join('<br>');
                }
                Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: msg });
                return;
            }

            bootstrap.Modal.getInstance(document.getElementById('issueFormModal'))?.hide();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: body.message,
                timer: 1300,
                showConfirmButton: false
            }).then(() => window.location.reload());
        } catch (err) {
            btn.disabled = false;
            Swal.fire('Error', 'Terjadi kesalahan saat menyimpan temuan.', 'error');
        }
    }

    function openManageSourcesModal() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('manageSourcesModal')).show();
    }

    async function submitNewSource(e) {
        e.preventDefault();
        const input = document.getElementById('newSourceNameInput');
        const name = input ? input.value.trim() : '';
        if (!name) return;

        const btn = document.getElementById('btnAddSourceSubmit');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(SOURCES_STORE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name })
            });
            const body = await res.json();
            if (btn) btn.disabled = false;

            if (!res.ok || !body.success) {
                Swal.fire('Gagal', body.message || 'Gagal menambahkan sumber temuan.', 'error');
                return;
            }

            input.value = '';
            refreshSourcesOptions(body.sources || []);
            Swal.fire({ icon: 'success', title: 'Ditambahkan', text: body.message, timer: 1200, showConfirmButton: false });
        } catch (err) {
            if (btn) btn.disabled = false;
            Swal.fire('Error', 'Gagal menambahkan sumber temuan.', 'error');
        }
    }

    async function deleteFindingSource(sourceName) {
        Swal.fire({
            title: 'Hapus Sumber Temuan?',
            html: `Hapus <strong>${escapeHtml(sourceName)}</strong> dari daftar pilihan Sumber Temuan?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(async result => {
            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`${IFU_BASE_URL}/sources/${encodeURIComponent(sourceName)}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const body = await res.json();
                if (res.ok && body.success) {
                    refreshSourcesOptions(body.sources || []);
                    Swal.fire({ icon: 'success', title: 'Terhapus', text: body.message, timer: 1200, showConfirmButton: false });
                } else {
                    Swal.fire('Error', body.message || 'Gagal menghapus sumber temuan.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal menghapus sumber temuan.', 'error');
            }
        });
    }

    function refreshSourcesOptions(sources) {
        const listEl = document.getElementById('sourcesMasterList');
        if (listEl) {
            listEl.innerHTML = sources.map(src => `
                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3" data-source-name="${escapeHtml(src)}">
                    <span class="fw-medium text-dark" style="font-size:12.5px;">${escapeHtml(src)}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteFindingSource(this.closest('[data-source-name]').dataset.sourceName)" title="Hapus Sumber Temuan">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `).join('');
        }

        const formCat = document.getElementById('formCategory');
        if (formCat) {
            const currentVal = formCat.value;
            formCat.innerHTML = `<option value="">-- Pilih Sumber Temuan --</option>` +
                sources.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
            if (sources.includes(currentVal)) formCat.value = currentVal;
        }

        const filterCat = document.getElementById('filterCategory');
        if (filterCat) {
            const currentFilter = filterCat.value;
            filterCat.innerHTML = `<option value="">Semua Sumber Temuan</option>` +
                sources.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
            if (sources.includes(currentFilter)) filterCat.value = currentFilter;
        }
    }

    function syncIfuDateRangeState() {
        const start = document.getElementById('filterStartDate');
        const end = document.getElementById('filterEndDate');
        const box = document.getElementById('ifuDateRangeBox');
        if (!box) return;
        if ((start && start.value) || (end && end.value)) {
            box.classList.add('has-date');
        } else {
            box.classList.remove('has-date');
        }
    }

    function clearIfuDateRange() {
        const start = document.getElementById('filterStartDate');
        const end = document.getElementById('filterEndDate');
        const box = document.getElementById('ifuDateRangeBox');
        if (start) start.value = '';
        if (end) end.value = '';
        if (box) box.classList.remove('has-date');
        const form = document.getElementById('ifuFilterForm');
        if (form) form.submit();
    }

    function openExportPdfModal() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('exportPdfModal')).show();
    }

    function toggleAllExportDivisions(checked) {
        document.querySelectorAll('.export-division-checkbox').forEach(cb => {
            cb.checked = checked;
        });
    }

    function deleteIssuePrompt(id, title, code) {
        Swal.fire({
            title: 'Hapus Temuan?',
            html: `Apakah Anda yakin ingin menghapus temuan <strong>[${escapeHtml(code)}] ${escapeHtml(title)}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-trash me-1"></i> Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(async result => {
            if (!result.isConfirmed) return;

            const res = await fetch(`${IFU_BASE_URL}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const body = await res.json();
            if (res.ok && body.success) {
                Swal.fire({ icon: 'success', title: 'Terhapus', text: body.message, timer: 1200, showConfirmButton: false })
                    .then(() => window.location.reload());
            } else {
                Swal.fire('Error', body.message || 'Gagal menghapus temuan.', 'error');
            }
        });
    }

    async function openIssueDetailModal(id) {
        const modalEl = document.getElementById('issueDetailModal');
        const bodyEl = document.getElementById('issueDetailModalBody');
        document.getElementById('detailFullPageLink').href = `${IFU_BASE_URL}/${id}`;
        bodyEl.innerHTML = `<div class="text-center py-4 text-muted"><i class="bi bi-arrow-repeat me-1"></i> Memuat rincian temuan...</div>`;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        try {
            const res = await fetch(`${IFU_BASE_URL}/${id}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (!data.success) {
                bodyEl.innerHTML = `<div class="text-danger text-center py-3">${escapeHtml(data.message)}</div>`;
                return;
            }

            const i = data.issue;
            const docs = data.documentations || [];
            const isClosed = String(i.status || '').toLowerCase() === 'closed' || String(i.status || '').toLowerCase() === 'close';
            const statusClass = isClosed ? 'status-closed' : 'status-open';
            const statusText = isClosed ? 'Closed' : 'Open';

            document.getElementById('detailModalTitle').textContent = `[${i.issue_code}] ${i.title}`;
            document.getElementById('detailModalSubtitle').textContent = `${i.division_label || '-'} • Sumber: ${i.category || '-'}`;

            const docsHtml = docs.length > 0 ? docs.map(d => `
                <div class="d-flex justify-content-between align-items-center p-2 bg-white border rounded mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text text-success fs-5"></i>
                        <div>
                            <div class="fw-semibold text-dark" style="font-size:12px;">${escapeHtml(d.original_name)}</div>
                            <div class="text-muted" style="font-size:10.5px;">Diupload oleh ${escapeHtml(d.uploaded_by || 'Sistem')}</div>
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="${IFU_BASE_URL}/documentations/${encodeURIComponent(d.id)}/view" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" title="Lihat File">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="${IFU_BASE_URL}/documentations/${encodeURIComponent(d.id)}/download" class="btn btn-sm btn-outline-success py-0 px-2" title="Unduh File">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                </div>
            `).join('') : `<div class="text-muted small">Belum ada file dokumentasi terlampir.</div>`;

            bodyEl.innerHTML = `
                <div class="row g-3 mb-3" style="font-size:12.5px;">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="mb-2"><span class="text-muted">Fungsi Safeguard:</span> <strong class="text-dark">${escapeHtml(i.division_label || '-')}</strong></div>
                            <div class="mb-2"><span class="text-muted">Tanggal Temuan:</span> <strong class="text-dark">${formatDate(i.reported_date)}</strong></div>
                            <div class="mb-0"><span class="text-muted">Lokasi:</span> <strong class="text-dark">${escapeHtml(i.location || '-')}</strong></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border h-100">
                            <div class="mb-2"><span class="text-muted">Status Temuan:</span> <span class="status-pill-ifu ${statusClass}">${statusText}</span></div>
                            ${isClosed && i.closed_date ? `<div class="mb-2"><span class="text-muted">Tanggal Closed:</span> <strong class="text-success">${formatDate(i.closed_date)}</strong></div>` : ''}
                            <div class="mb-0"><span class="text-muted">Sumber Temuan:</span> <strong class="text-dark">${escapeHtml(i.category || '-')}</strong></div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-white rounded border mb-3" style="font-size:12.5px;">
                    <div class="fw-bold text-dark mb-1">Deskripsi Temuan</div>
                    <div class="text-secondary" style="white-space: pre-line;">${escapeHtml(i.description)}</div>
                </div>

                <div class="p-3 bg-light rounded border">
                    <h6 class="fw-bold text-dark mb-2" style="font-size:13px;">
                        <i class="bi bi-folder2-open text-success me-1"></i> Dokumentasi Temuan (${docs.length} File)
                    </h6>
                    ${docsHtml}
                </div>
            `;
        } catch (err) {
            bodyEl.innerHTML = `<div class="text-danger text-center py-3">Gagal memuat detail temuan.</div>`;
        }
    }

    // Validasi & Multi-Upload Drag-and-Drop Dokumentasi (Persis dengan modul Event)
    const IFU_MAX_FILE_SIZE_BYTES = 15 * 1024 * 1024; // 15 MB
    const IFU_ALLOWED_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.webp', '.gif', '.pdf', '.doc', '.docx', '.xls', '.xlsx'];
    let ifuSelectedFilesDt = new DataTransfer();

    function resetIfuSelectedFiles() {
        ifuSelectedFilesDt = new DataTransfer();
        const input = document.getElementById('formDocumentations');
        if (input) input.value = '';
        const container = document.getElementById('previewFilesIfuContainer');
        if (container) container.innerHTML = '';
    }

    function handleIfuDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropzone = document.getElementById('ifuUploadDropzone');
        if (dropzone) dropzone.classList.add('drag-over');
    }

    function handleIfuDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropzone = document.getElementById('ifuUploadDropzone');
        if (dropzone) dropzone.classList.remove('drag-over');
    }

    function handleIfuDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropzone = document.getElementById('ifuUploadDropzone');
        if (dropzone) dropzone.classList.remove('drag-over');

        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            processIfuFiles(e.dataTransfer.files);
        }
    }

    function handleIfuFilesSelected(input) {
        if (!input.files || input.files.length === 0) return;
        processIfuFiles(input.files);
    }

    function processIfuFiles(fileList) {
        for (let i = 0; i < fileList.length; i++) {
            const file = fileList[i];
            const ext = '.' + file.name.split('.').pop().toLowerCase();

            if (!IFU_ALLOWED_EXTENSIONS.includes(ext)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Format File Tidak Didukung',
                    html: `Berkas <b>"${escapeHtml(file.name)}"</b> memiliki format yang tidak didukung.<br><small class="text-muted">Format yang diizinkan: JPG, JPEG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX</small>`,
                });
                continue;
            }

            if (file.size > IFU_MAX_FILE_SIZE_BYTES) {
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                Swal.fire({
                    icon: 'error',
                    title: 'Ukuran File Terlalu Besar',
                    html: `Berkas <b>"${escapeHtml(file.name)}"</b> berukuran <b>${fileSizeMB} MB</b>.<br><span class="text-danger">Maksimal ukuran berkas adalah 15 MB per file.</span>`,
                });
                continue;
            }

            let alreadyExists = false;
            for (let j = 0; j < ifuSelectedFilesDt.files.length; j++) {
                if (ifuSelectedFilesDt.files[j].name === file.name && ifuSelectedFilesDt.files[j].size === file.size) {
                    alreadyExists = true;
                    break;
                }
            }

            if (!alreadyExists) {
                ifuSelectedFilesDt.items.add(file);
            }
        }

        const input = document.getElementById('formDocumentations');
        if (input) {
            input.files = ifuSelectedFilesDt.files;
        }
        renderIfuFilePreviewBadges();
    }

    function removeIfuSelectedFile(index) {
        const newDt = new DataTransfer();
        for (let i = 0; i < ifuSelectedFilesDt.files.length; i++) {
            if (i !== index) {
                newDt.items.add(ifuSelectedFilesDt.files[i]);
            }
        }
        ifuSelectedFilesDt = newDt;

        const input = document.getElementById('formDocumentations');
        if (input) {
            input.files = ifuSelectedFilesDt.files;
        }
        renderIfuFilePreviewBadges();
    }

    function renderIfuFilePreviewBadges() {
        const container = document.getElementById('previewFilesIfuContainer');
        if (!container) return;

        container.innerHTML = '';
        if (ifuSelectedFilesDt.files.length === 0) return;

        Array.from(ifuSelectedFilesDt.files).forEach((file, index) => {
            const ext = file.name.split('.').pop().toLowerCase();
            let iconClass = 'bi-file-earmark text-primary';
            if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
                iconClass = 'bi-file-earmark-image text-success';
            } else if (ext === 'pdf') {
                iconClass = 'bi-file-earmark-pdf text-danger';
            } else if (['doc', 'docx'].includes(ext)) {
                iconClass = 'bi-file-earmark-word text-primary';
            } else if (['xls', 'xlsx'].includes(ext)) {
                iconClass = 'bi-file-earmark-excel text-success';
            }

            const sizeText = file.size > 1024 * 1024
                ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
                : Math.round(file.size / 1024) + ' KB';

            const row = document.createElement('div');
            row.className = 'd-flex align-items-center justify-content-between p-2 px-3 rounded-2 border bg-white shadow-sm w-100';
            row.innerHTML = `
                <div class="d-flex align-items-center gap-2 overflow-hidden me-3" style="min-width: 0;">
                    <i class="bi ${iconClass} fs-5 flex-shrink-0"></i>
                    <div class="text-truncate">
                        <span class="fw-semibold text-dark" style="font-size: 12.5px;">${escapeHtml(file.name)}</span>
                        <span class="text-muted ms-1" style="font-size: 11.5px;">(${sizeText})</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2 flex-shrink-0 d-inline-flex align-items-center gap-1" style="font-size: 11.5px;" onclick="removeIfuSelectedFile(${index})" title="Hapus berkas ini">
                    <i class="bi bi-trash3"></i>
                    <span>Hapus</span>
                </button>
            `;
            container.appendChild(row);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('issueFormModal');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                resetIfuSelectedFiles();
            });
        }
    });

    function formatDate(dStr) {
        if (!dStr) return '-';
        const s = String(dStr).substring(0, 10);
        const parts = s.split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : s;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endpush
