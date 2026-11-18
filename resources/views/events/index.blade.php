@extends('layouts.app', [
    'title' => 'Events',
    'activeModule' => 'events'
])

@push('styles')
<style>
    /* Minimalist Stat Cards Styling for Events */
    .events-stat-overview-card {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 20px;
        padding: 24px 28px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .events-stat-overview-card:hover {
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    }

    .events-chart-wrapper {
        width: 100%;
        height: 160px;
        position: relative;
        margin-top: 10px;
    }

    .events-chart-wrapper.is-empty {
        height: auto;
        min-height: 108px;
    }

    .events-empty-chart-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 18px 14px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        gap: 4px;
    }

    .events-empty-chart-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #eef2ff;
        color: #6366f1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-bottom: 2px;
    }

    .stats-subgrid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    @media (max-width: 992px) {
        .stats-subgrid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 576px) {
        .stats-subgrid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
    }

    .stats-subgrid .stat-card {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 18px;
        padding: 20px 22px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .stats-subgrid .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
    }

    .stat-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.015em;
        margin-bottom: 0;
        line-height: 1.2;
    }

    .stat-period-detail {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        line-height: 1.3;
        margin-bottom: 4px;
    }

    .stat-number {
        font-size: 40px;
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.03em;
        font-family: inherit;
    }

    .safeguard-text {
        color: #1e293b;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.4;
    }



    /* Thumbnail Tabel Dokumentasi */
    .event-doc-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: transform 0.18s, box-shadow 0.18s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    .event-doc-thumb:hover {
        transform: scale(1.08);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .event-title-cell {
        white-space: normal !important;
        min-width: 240px;
        max-width: 380px;
    }

    .table-custom th, .table-custom td {
        vertical-align: middle !important;
    }

    .form-section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11.5px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 14px;
        margin-bottom: 8px;
        padding-bottom: 4px;
        border-bottom: 1px solid #f1f5f9;
    }

    .form-section-header:first-of-type {
        margin-top: 0;
    }

    .form-section-header i {
        color: #6366f1;
        font-size: 13px;
    }

    .event-doc-placeholder {
        padding: 4px 10px;
        border-radius: 6px;
        background: #f8fafc;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 500;
        border: 1px dashed #cbd5e1;
    }

    .event-doc-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        font-size: 11.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
        cursor: pointer;
    }

    .event-doc-badge:hover {
        background: #e2e8f0;
        color: #0f172a;
        transform: translateY(-1px);
    }


    .company-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        background: #f5f3ff;
        color: #6d28d9;
        border: 1px solid #ddd6fe;
    }

    .detail-info-row {
        display: grid;
        grid-template-columns: 120px 10px 1fr;
        gap: 6px;
        margin-bottom: 8px;
        font-size: 12px;
    }

    .detail-info-label {
        color: #64748b;
        font-weight: 500;
    }

    .detail-info-sep {
        color: #94a3b8;
    }

    .detail-info-val {
        color: #0f172a;
        font-weight: 600;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .gallery-item-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        background: #ffffff;
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .gallery-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }

    .gallery-thumb-img {
        width: 100%;
        height: 85px;
        object-fit: cover;
        display: block;
        cursor: pointer;
    }

    .gallery-doc-placeholder {
        width: 100%;
        height: 85px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
    }

    .gallery-label {
        padding: 6px 8px;
        font-size: 11px;
        color: #334155;
        font-weight: 500;
        background: #ffffff;
    }


    .gallery-upload-card {
        border: 2px dashed #cbd5e1;
        border-radius: 6px;
        height: 106px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: #f8fafc;
        color: #64748b;
        transition: all 0.2s;
    }

    .gallery-upload-card:hover {
        border-color: var(--green-primary);
        color: var(--green-primary);
        background: #ecfdf5;
    }

    .upload-drag-area {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.2s;
    }

    .upload-drag-area:hover {
        border-color: var(--green-primary);
        background: #ecfdf5;
    }

    .filter-grid-events-new {
        display: grid;
        grid-template-columns: 1fr 190px 150px minmax(260px, 310px) auto;
        gap: 10px;
        align-items: center;
    }

    /* Date Range Container in Filter Events */
    .date-range-container {
        position: relative;
        display: flex;
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
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
        border: none;
        outline: none;
        background: transparent;
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
        font-weight: 600;
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

    /* Lightbox Modal Custom Styling */
    #modalImagePreview {
        z-index: 1070 !important;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(6px);
    }
    #modalImagePreview .modal-dialog {
        max-width: 92vw;
        margin: 1.5rem auto;
    }
    #modalImagePreview .modal-content {
        background: transparent;
        border: none;
        box-shadow: none;
    }
    .lightbox-card {
        background: rgba(30, 41, 59, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 16px;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        display: inline-block;
        max-width: 100%;
    }
    .lightbox-img-element {
        max-height: 80vh;
        max-width: 88vw;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
        display: none;
        margin: 0 auto;
    }
    .lightbox-close-btn {
        position: absolute;
        top: -14px;
        right: -14px;
        width: 34px;
        height: 34px;
        background: #ef4444;
        color: #ffffff;
        border: 2px solid #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        z-index: 10;
    }
    .lightbox-close-btn:hover {
        background: #dc2626;
        transform: scale(1.1);
    }

    @media (max-width: 992px) {
        .filter-grid-events-new {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 576px) {
        .filter-grid-events-new {
            grid-template-columns: 1fr;
        }
    }
    .filter-grid-events-new,
    .gallery-grid {
        min-width: 0;
        max-width: 100%;
    }
    @media (max-width: 1280px) {
        .filter-grid-events-new {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
    @media (max-width: 767.98px) {
        .filter-grid-events-new {
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
        .gallery-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
    @media (max-width: 575.98px) {
        .events-page-header {
            margin-bottom: 12px !important;
            gap: 10px !important;
        }
        .events-header-actions {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px !important;
            width: 100%;
        }
        .events-header-actions > :first-child:nth-last-child(3),
        .events-header-actions > :only-child {
            grid-column: 1 / -1;
        }
        .events-header-actions .btn {
            width: 100%;
            height: 36px !important;
            padding: 0 10px !important;
            font-size: 12.5px !important;
        }
        .events-stat-overview-card {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 10px !important;
        }
        .events-overview-header {
            padding-bottom: 10px !important;
        }
        .events-overview-title {
            font-size: 15.5px !important;
        }
        #cardTotalKegiatan {
            font-size: 28px !important;
        }
        .events-chart-wrapper {
            height: 135px;
        }
        .events-chart-wrapper.is-empty {
            height: auto;
            min-height: 96px;
        }
        .stats-subgrid .stat-card {
            padding: 12px 14px;
            border-radius: 12px;
            gap: 2px;
        }
        .stats-subgrid .stat-title {
            font-size: 13.5px;
        }
        .stats-subgrid .stat-period-detail {
            font-size: 11px;
            margin-bottom: 2px;
        }
        .stats-subgrid .stat-number {
            font-size: 28px;
        }
    }
    @media (max-width: 420px) {
        .gallery-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $hasTrendData = collect($trendCounts ?? [])->sum() > 0;
@endphp
<!-- HEADER & ACTIONS -->
<div class="page-header events-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="header-title-wrapper d-flex align-items-center gap-3">
        <div class="header-icon-box purple d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #ffffff; font-size: 20px; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);">
            <i class="bi bi-calendar-event"></i>
        </div>
        <div>
            <h1 class="page-heading mb-0" style="font-size: 22px; font-weight: 700; color: #0f172a; letter-spacing: -0.02em;">Events</h1>
            <div class="breadcrumb-nav">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="mx-1 text-muted">›</span>
                <span class="text-secondary fw-semibold">Events</span>
            </div>
        </div>
    </div>

    <div class="header-actions events-header-actions d-flex flex-wrap align-items-center gap-2">
        @adminManagement
            <button class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambahEvent" style="height: 38px; padding: 0 16px; font-size: 13px; font-weight: 600; border-radius: 8px; background: #6366f1; border-color: #6366f1; white-space: nowrap;">
                <i class="bi bi-plus-lg"></i> Tambah Event
            </button>
            <button type="button" class="btn btn-outline-success d-inline-flex align-items-center justify-content-center gap-2" onclick="openManageCompaniesModal()" title="Kelola Perusahaan / Instansi" style="height: 38px; padding: 0 16px; font-size: 13px; font-weight: 600; border-radius: 8px; white-space: nowrap;">
                <i class="bi bi-gear-fill"></i> Kelola Instansi
            </button>
        @endadminManagement
        @canExportPdf
        <button type="button" class="btn btn-outline-dark d-inline-flex align-items-center justify-content-center gap-2" id="btnExportPdf" data-bs-toggle="modal" data-bs-target="#modalExportPdf" title="Ekspor Laporan PDF" style="height: 38px; padding: 0 16px; font-size: 13px; font-weight: 600; border-radius: 8px; color: #0f172a; border-color: #cbd5e1; white-space: nowrap;">
            <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Export PDF
        </button>
        @endcanExportPdf
    </div>
</div>

<!-- AREA STATISTIK & TREN KEGIATAN (2-TIER LAYOUT) -->
<div class="mb-4">
    <!-- Baris Atas (Full Width): Card Grafik "Total Pekerjaan / Kegiatan" -->
    <div class="events-stat-overview-card mb-3">
        <div class="events-overview-header d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-3">
            <div>
                <h3 class="stat-title events-overview-title mb-1" style="font-size: 18px;">Total Kegiatan</h3>
                <div class="text-muted" style="font-size: 12px; font-weight: 500;">
                    Visualisasi tren akumulasi kegiatan dalam 6 bulan terakhir
                </div>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="text-muted small fw-semibold">Total Terdata:</span>
                <span class="stat-number text-dark" id="cardTotalKegiatan" style="font-size: 38px;">{{ $totalKegiatan }}</span>
            </div>
        </div>

        <!-- Area Grafik Tren / Informative Empty State -->
        <div class="events-chart-wrapper {{ $hasTrendData ? '' : 'is-empty' }}">
            <div id="eventsChartEmptyState" class="events-empty-chart-state {{ $hasTrendData ? 'd-none' : '' }}">
                <div class="events-empty-chart-icon">
                    <i class="bi bi-calendar2-x"></i>
                </div>
                <div class="fw-semibold text-dark" style="font-size: 13px;">Belum ada kegiatan tercatat pada 6 bulan terakhir</div>
                <div class="text-muted" style="font-size: 11.5px; max-width: 340px;">
                    Tambahkan event baru untuk menampilkan visualisasi tren akumulasi kegiatan HSSE.
                </div>
            </div>
            <canvas id="miniTrendChart" class="{{ $hasTrendData ? '' : 'd-none' }}"></canvas>
        </div>
    </div>

    <!-- Baris Bawah (4 Kolom Sejajar): Card Ringkasan Tahun Ini, Bulan Ini, Minggu Ini, Hari Ini -->
    <div class="stats-subgrid">
        <!-- Card 1: Tahun Ini -->
        <div class="stat-card">
            <div class="stat-title">Tahun Ini</div>
            <div class="stat-period-detail">{{ $periodeTahunIni }}</div>
            <div class="stat-number text-primary" id="cardTahunIni">{{ $tahunIni }}</div>
        </div>

        <!-- Card 2: Bulan Ini -->
        <div class="stat-card">
            <div class="stat-title">Bulan Ini</div>
            <div class="stat-period-detail">{{ $periodeBulanIni }}</div>
            <div class="stat-number text-warning" id="cardBulanIni">{{ $bulanIni }}</div>
        </div>

        <!-- Card 3: Minggu Ini -->
        <div class="stat-card">
            <div class="stat-title">Minggu Ini</div>
            <div class="stat-period-detail">{{ $periodeMingguIni }}</div>
            <div class="stat-number text-info" id="cardMingguIni">{{ $mingguIni }}</div>
        </div>

        <!-- Card 4: Hari Ini -->
        <div class="stat-card">
            <div class="stat-title">Hari Ini</div>
            <div class="stat-period-detail">{{ $periodeHariIni }}</div>
            <div class="stat-number text-success" id="cardHariIni">{{ $hariIni }}</div>
        </div>
    </div>
</div>

<!-- FILTER BAR SEDERHANA & COMPACT -->
<div class="filter-card mb-4" style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 16px; padding: 14px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
    <form id="filterForm" method="GET" action="{{ route('events.index') }}">
        <div class="filter-grid-events-new">
            <!-- 1. Input Pencarian Global: Judul Kegiatan & Deskripsi -->
            <div class="filter-input-wrap">
                <i class="bi bi-search filter-icon-addon"></i>
                <input type="text"
                       name="search"
                       id="filterSearch"
                       class="form-control form-control-filter"
                       placeholder="Cari judul kegiatan atau deskripsi..."
                       value="{{ request('search') }}"
                       style="height: 36px; font-size: 12.5px;">
            </div>

            <!-- 2. Dropdown Fungsi Safeguard -->
            <div>
                <select name="safeguard_function" id="filterSafeguardFunction" class="form-select form-select-filter" style="height: 36px; font-size: 12.5px;">
                    <option value="All" {{ in_array(request('safeguard_function', 'All'), ['All', 'Semua', '']) ? 'selected' : '' }}>Semua Fungsi Safeguard</option>
                    @foreach(\App\Models\Event::SAFEGUARD_FUNCTIONS as $func)
                        <option value="{{ $func }}" {{ request('safeguard_function') === $func ? 'selected' : '' }}>{{ $func }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Dropdown Status (All / Direncanakan / Berlangsung / Selesai / Dibatalkan) -->
            <div>
                <select name="status" id="filterStatus" class="form-select form-select-filter" style="height: 36px; font-size: 12.5px;">
                    <option value="All" {{ in_array(request('status', 'All'), ['All', 'Semua', '']) ? 'selected' : '' }}>Semua Status (All)</option>
                    <option value="Direncanakan" {{ request('status') == 'Direncanakan' ? 'selected' : '' }}>Direncanakan</option>
                    <option value="Berlangsung" {{ request('status') == 'Berlangsung' ? 'selected' : '' }}>Berlangsung</option>
                    <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="Dibatalkan" {{ request('status') == 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            <!-- 4. Rentang Tanggal (Date Range Container Menyatu dengan pemisah s/d) -->
            <div class="date-range-container @if(request('start_date') || request('end_date')) has-date @endif" id="dateRangeBox" style="height: 36px;">
                <i class="bi bi-calendar-event date-range-icon"></i>
                <div class="date-range-inputs">
                    <input type="date"
                           name="start_date"
                           id="filterStartDate"
                           class="date-range-input"
                           placeholder="dd/mm/yyyy"
                           value="{{ request('start_date') }}"
                           title="Tanggal Mulai"
                           onclick="try{this.showPicker()}catch(e){}">
                    <span class="date-range-separator">s/d</span>
                    <input type="date"
                           name="end_date"
                           id="filterEndDate"
                           class="date-range-input"
                           placeholder="dd/mm/yyyy"
                           value="{{ request('end_date') }}"
                           title="Tanggal Selesai"
                           onclick="try{this.showPicker()}catch(e){}">
                </div>
                <button type="button"
                        class="date-range-clear-btn"
                        id="btnClearDateRange"
                        onclick="clearDateRange()"
                        title="Hapus filter tanggal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Hidden per_page -->
            <input type="hidden" name="per_page" id="filterPerPage" value="{{ $perPage }}">

            <!-- 5. Tombol Filter & Reset -->
            <div class="d-flex align-items-center gap-1">
                <button type="submit" class="btn btn-sm btn-primary px-3 d-inline-flex align-items-center gap-1" style="height: 36px; background:#059669; border-color:#059669; font-weight: 600; border-radius: 6px;">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>
                <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-secondary px-3 d-inline-flex align-items-center" style="height: 36px; font-weight: 600; border-radius: 6px;">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- TABEL DAFTAR KEGIATAN -->
<div class="table-card">
    <div class="table-topbar">
        <div>
            <h2 class="table-section-title mb-0">
                Daftar Kegiatan
                <span class="badge bg-secondary-subtle text-secondary ms-2" style="font-size:10.5px;">{{ $events->total() }} Data</span>
            </h2>
        </div>

        <div class="table-controls">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted" style="font-size:11.5px">Tampilkan</span>
                <select id="perPageSelect" class="form-select form-select-sm" style="width: 70px; font-size:11.5px">
                    <option value="5" {{ $perPage == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span class="text-muted" style="font-size:11.5px">per halaman</span>
            </div>
        </div>
    </div>

    <div class="table-responsive-custom">
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No.</th>
                    <th style="width: 140px;">Fungsi</th>
                    <th>Judul Kegiatan</th>
                    <th style="width: 120px; text-align: center;">Tanggal</th>
                    <th style="width: 185px;">Perusahaan/Instansi</th>
                    <th style="width: 120px; text-align: center;">Status</th>
                    <th style="width: 145px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="eventsTableBody">
                @forelse($events as $index => $event)
                    @php
                        $docs = $event->documents;
                        $docCount = $docs->count();
                        $hasDocs = $docCount > 0 || !empty($event->documentation);
                        $safeguard = $event->safeguard_function ?: 'Health & Safety';
                    @endphp
                    <tr>
                        <td style="text-align: center; color: #64748b;">{{ $events->firstItem() + $index }}</td>
                        <td class="text-slate-800 text-gray-900 fw-medium safeguard-text">
                            {{ $safeguard }}
                        </td>
                        <td class="event-title-cell">
                            <div class="fw-bold text-dark fs-6">{{ $event->title }}</div>
                            @if($event->description)
                                <div class="text-muted small mt-1" style="font-size:11.5px; line-height:1.4;">
                                    {{ \Illuminate\Support\Str::limit($event->description, 110) }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div class="fw-semibold text-dark">{{ $event->event_date ? $event->event_date->format('d/m/Y') : '-' }}</div>
                        </td>
                        <td>
                            <span class="company-pill">
                                <i class="bi bi-building me-1"></i>{{ $event->company ?: 'EBTKE' }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            @php
                                $statusBadgeClass = match($event->status) {
                                    'Selesai' => 'bg-success-subtle text-success border border-success-subtle',
                                    'Berlangsung' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                    'Direncanakan' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                    'Dibatalkan' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                    default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                };
                            @endphp
                            <span class="badge {{ $statusBadgeClass }}" style="font-size:11px; padding: 5px 10px;">
                                {{ $event->status }}
                            </span>
                        </td>
                        <!-- Kolom Aksi -->
                        <td style="text-align: center;">
                            <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-nowrap">
                                <!-- 1. Lihat Detail -->
                                <button class="btn-action-icon btn-action-view" title="Lihat Detail Kegiatan" onclick="openDetailModal({{ $event->id }})">
                                     <i class="bi bi-eye"></i>
                                </button>

                                @adminManagement
                                    <!-- 2. Edit -->
                                    <button class="btn-action-icon btn-action-edit" title="Edit Kegiatan" onclick="openEditModal({{ $event->id }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <!-- 3. Hapus -->
                                    <button class="btn-action-icon btn-action-delete" title="Hapus Kegiatan" onclick="confirmDeleteEvent({{ $event->id }}, {{ Js::from($event->title) }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endadminManagement
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                            Tidak ada data kegiatan yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Table Footer & Pagination -->
    <div class="table-bottom-bar">
        <div>
            Menampilkan {{ $events->firstItem() ?? 0 }} - {{ $events->lastItem() ?? 0 }} dari {{ $events->total() }} data
        </div>
        <div>
            {{ $events->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<!-- MODAL TAMBAH EVENT -->
<div class="modal fade" id="modalTambahEvent" tabindex="-1" aria-labelledby="modalTambahEventLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="formTambahEvent" class="modal-content modal-custom-content" action="{{ route('events.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header modal-header-themed purple">
                <h5 class="modal-title" id="modalTambahEventLabel">
                    <i class="bi bi-calendar-plus me-1"></i> Tambah Event Kegiatan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Baris 1: Judul Kegiatan & Fungsi Safeguard -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="Contoh: Audit Keselamatan Kerja EBTKE" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Fungsi Safeguard <span class="text-danger">*</span></label>
                            <select name="safeguard_function" id="tambahSafeguardFunction" class="form-select form-select-sm" required>
                                <option value="Health & Safety" selected>Health &amp; Safety</option>
                                <option value="Security">Security</option>
                                <option value="Environment">Environment</option>
                                <option value="Social">Social</option>
                            </select>
                        </div>

                        <!-- Baris 2: Tanggal Kegiatan & Perusahaan/Instansi -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Tanggal Kegiatan <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" id="tambahEventDate" class="form-control form-control-sm" required value="{{ date('Y-m-d') }}" onchange="autoUpdateStatusByDate(this.value, 'tambahStatus')" oninput="autoUpdateStatusByDate(this.value, 'tambahStatus')">
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0" style="font-size:12px">Perusahaan/Instansi <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-success text-decoration-none fw-semibold" onclick="openManageCompaniesModal()" style="font-size:11.5px;">
                                    <i class="bi bi-gear-fill me-1"></i>Kelola Instansi
                                </button>
                            </div>
                            <select name="company" id="selectCompanyTambah" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Perusahaan/Instansi --</option>
                                @foreach($allCompanies as $comp)
                                    <option value="{{ $comp }}" {{ $comp === 'EBTKE' ? 'selected' : '' }}>{{ $comp }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Baris 3: Status Kegiatan -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px">Status Kegiatan <span class="text-danger">*</span></label>
                            <select name="status" id="tambahStatus" class="form-select form-select-sm" required>
                                <option value="Direncanakan" selected>Direncanakan</option>
                                <option value="Berlangsung">Berlangsung</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Dibatalkan">Dibatalkan</option>
                            </select>
                        </div>

                        <!-- Baris 4 (Full Width): Deskripsi Kegiatan -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px">Deskripsi Kegiatan</label>
                            <textarea name="description" class="form-control form-control-sm" rows="3" placeholder="Uraikan ringkasan, tujuan, atau hasil kegiatan..."></textarea>
                        </div>

                        <!-- Baris 5 (Full Width): Unggah Dokumentasi / Lampiran (Multi-File) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px">Unggah Dokumentasi / Lampiran (Multi-File)</label>
                            <div class="upload-drag-area" onclick="document.getElementById('inputDocsTambah').click()">
                                <i class="bi bi-cloud-arrow-up fs-3 text-secondary d-block mb-1"></i>
                                <span class="fw-semibold text-dark d-block" style="font-size:12px">Pilih Berkas Dokumentasi</span>
                                <span class="text-muted" style="font-size:11px">Dapat memilih banyak berkas sekaligus (JPG, PNG, WEBP, PDF, DOCX, XLSX - maks 15 MB/file)</span>
                                <input type="file" name="documentation_files[]" id="inputDocsTambah" class="d-none" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" onchange="handleFilesSelected(this, 'tambah')">
                            </div>
                            <div id="previewFilesTambahContainer" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-action-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Simpan Event
                    </button>
                </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT EVENT -->
<div class="modal fade" id="modalEditEvent" tabindex="-1" aria-labelledby="modalEditEventLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="formEditEvent" class="modal-content modal-custom-content" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-header modal-header-themed purple">
                <h5 class="modal-title" id="modalEditEventLabel">
                    <i class="bi bi-pencil-square me-1"></i> Edit Event Kegiatan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Baris 1: Judul Kegiatan & Fungsi Safeguard -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="editTitle" class="form-control form-control-sm" placeholder="Contoh: Audit Keselamatan Kerja EBTKE" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Fungsi Safeguard <span class="text-danger">*</span></label>
                            <select name="safeguard_function" id="editSafeguardFunction" class="form-select form-select-sm" required>
                                <option value="Health & Safety">Health &amp; Safety</option>
                                <option value="Security">Security</option>
                                <option value="Environment">Environment</option>
                                <option value="Social">Social</option>
                            </select>
                        </div>

                        <!-- Baris 2: Tanggal Kegiatan & Perusahaan/Instansi -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px">Tanggal Kegiatan <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" id="editEventDate" class="form-control form-control-sm" required onchange="autoUpdateStatusByDate(this.value, 'editStatus')" oninput="autoUpdateStatusByDate(this.value, 'editStatus')">
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0" style="font-size:12px">Perusahaan/Instansi <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-success text-decoration-none fw-semibold" onclick="openManageCompaniesModal()" style="font-size:11.5px;">
                                    <i class="bi bi-gear-fill me-1"></i>Kelola Instansi
                                </button>
                            </div>
                            <select name="company" id="selectCompanyEdit" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Perusahaan/Instansi --</option>
                                @foreach($allCompanies as $comp)
                                    <option value="{{ $comp }}">{{ $comp }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Baris 3: Status Kegiatan -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px">Status Kegiatan <span class="text-danger">*</span></label>
                            <select name="status" id="editStatus" class="form-select form-select-sm" required>
                                <option value="Direncanakan">Direncanakan</option>
                                <option value="Berlangsung">Berlangsung</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Dibatalkan">Dibatalkan</option>
                            </select>
                        </div>

                        <!-- Baris 4 (Full Width): Deskripsi Kegiatan -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px">Deskripsi Kegiatan</label>
                            <textarea name="description" id="editDescription" class="form-control form-control-sm" rows="3" placeholder="Uraikan ringkasan, tujuan, atau hasil kegiatan..."></textarea>
                        </div>

                        <!-- Baris 5 (Full Width): Unggah Dokumentasi / Lampiran (Multi-File) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:12px">Unggah Dokumentasi / Lampiran (Multi-File)</label>
                            <div class="upload-drag-area" onclick="document.getElementById('inputDocsEdit').click()">
                                <i class="bi bi-cloud-arrow-up fs-3 text-secondary d-block mb-1"></i>
                                <span class="fw-semibold text-dark d-block" style="font-size:12px">Pilih Berkas Dokumentasi</span>
                                <span class="text-muted" style="font-size:11px">File lama tetap tersimpan. Unggah berkas tambahan jika diperlukan (maks 15 MB/file)</span>
                                <input type="file" name="documentation_files[]" id="inputDocsEdit" class="d-none" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" onchange="handleFilesSelected(this, 'edit')">
                            </div>
                            <div id="previewFilesEditContainer" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-action-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                </div>
        </form>
    </div>
</div>

<!-- MODAL DETAIL EVENT -->
<div class="modal fade" id="modalDetailEvent" tabindex="-1" aria-labelledby="modalDetailEventLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-custom-content">
            <div class="modal-header modal-header-themed purple">
                <h5 class="modal-title" id="modalDetailEventLabel">
                    <i class="bi bi-info-circle me-1"></i> Detail Event
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-5 border-end">
                        <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2" style="font-size:13px">
                            <i class="bi bi-info-circle text-primary"></i> Rincian Informasi Kegiatan
                        </h6>

                        <div class="detail-info-row">
                            <div class="detail-info-label">Judul</div>
                            <div class="detail-info-sep">:</div>
                            <div class="detail-info-val" id="detailJudul">-</div>
                        </div>

                        <div class="detail-info-row">
                            <div class="detail-info-label">Fungsi Safeguard</div>
                            <div class="detail-info-sep">:</div>
                            <div class="detail-info-val" id="detailSafeguard">-</div>
                        </div>

                        <div class="detail-info-row">
                            <div class="detail-info-label">Perusahaan/Instansi</div>
                            <div class="detail-info-sep">:</div>
                            <div class="detail-info-val" id="detailCompany">-</div>
                        </div>

                        <div class="detail-info-row">
                            <div class="detail-info-label">Tanggal</div>
                            <div class="detail-info-sep">:</div>
                            <div class="detail-info-val" id="detailTanggal">-</div>
                        </div>

                        <div class="detail-info-row">
                            <div class="detail-info-label">PIC</div>
                            <div class="detail-info-sep">:</div>
                            <div class="detail-info-val" id="detailPic">-</div>
                        </div>

                        <div class="detail-info-row">
                            <div class="detail-info-label">Status</div>
                            <div class="detail-info-sep">:</div>
                            <div class="detail-info-val">
                                <span id="detailStatusBadge" class="badge-status status-selesai">Selesai</span>
                            </div>
                        </div>

                        <div class="detail-info-row">
                            <div class="detail-info-label">Deskripsi</div>
                            <div class="detail-info-sep">:</div>
                            <div class="detail-info-val fw-normal text-muted" id="detailDeskripsi">-</div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <div class="detail-info-row">
                                <div class="detail-info-label">Dibuat oleh</div>
                                <div class="detail-info-sep">:</div>
                                <div class="detail-info-val fw-normal text-muted" id="detailDibuat">-</div>
                            </div>
                            <div class="detail-info-row">
                                <div class="detail-info-label">Diubah oleh</div>
                                <div class="detail-info-sep">:</div>
                                <div class="detail-info-val fw-normal text-muted" id="detailDiubah">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <ul class="nav nav-tabs nav-tabs-detail mb-0 border-bottom-0" id="detailTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tabDocsBtn" data-bs-toggle="tab" data-bs-target="#tabDocs" type="button" role="tab">
                                        Dokumentasi (<span id="detailDocsCount">0</span>)
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tabLogsBtn" data-bs-toggle="tab" data-bs-target="#tabLogs" type="button" role="tab">
                                        Riwayat Kegiatan
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content pt-2 border-top" id="detailTabContent">
                            <div class="tab-pane fade show active" id="tabDocs" role="tabpanel">
                                <div class="gallery-grid" id="detailGalleryGrid"></div>
                            </div>

                            <div class="tab-pane fade" id="tabLogs" role="tabpanel">
                                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                    <table class="table table-sm table-hover" style="font-size: 11px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Waktu</th>
                                                <th>Pengguna</th>
                                                <th>Aksi</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody id="detailLogsTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PREVIEW GAMBAR BESAR (LIGHTBOX) -->
<div class="modal fade" id="modalImagePreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered justify-content-center">
        <div class="modal-content">
            <div class="lightbox-card text-center">
                <!-- Close Button (X) -->
                <button type="button" class="lightbox-close-btn" data-bs-dismiss="modal" aria-label="Close" title="Tutup Preview">
                    <i class="bi bi-x-lg"></i>
                </button>

                <!-- Loading Spinner -->
                <div id="previewLoadingSpinner" class="py-5 text-white" style="display: none; min-width: 250px;">
                    <div class="spinner-border text-light mb-2" role="status"></div>
                    <div class="small text-white-50">Memuat gambar...</div>
                </div>

                <!-- Fallback / Error Message Container -->
                <div id="previewFallbackMsg" class="py-5 px-4 text-center text-white" style="display: none; min-width: 280px;">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-1 d-block mb-2"></i>
                    <h6 class="fw-bold mb-1 text-white">Foto Tidak Tersedia</h6>
                    <p class="small text-white-50 mb-3" style="max-width: 320px;">Berkas gambar tidak ditemukan di server atau tautan tidak valid.</p>
                </div>

                <!-- Main Preview Image -->
                <img id="modalPreviewImage"
                     alt="Preview Dokumentasi"
                     class="lightbox-img-element">

                <!-- Footer Info & Actions (View-Only) -->
                <div class="d-flex align-items-center justify-content-between mt-3 px-1">
                    <div class="text-start pe-3" style="min-width: 0;">
                        <div id="modalPreviewTitle" class="text-white fw-semibold text-truncate" style="font-size: 13.5px; max-width: min(450px, 70vw);"></div>
                        <div id="modalPreviewSubtitle" class="text-white-50" style="font-size: 11px;">Pratinjau Foto Dokumentasi</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light py-1 px-3" style="font-size: 11.5px; border-radius: 6px;" data-bs-dismiss="modal">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FILTER EXPORT PDF (CHECKLIST SELEKTIF) -->
<div class="modal fade" id="modalExportPdf" tabindex="-1" aria-labelledby="modalExportPdfLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-custom-content">
            <div class="modal-header modal-header-themed purple">
                <h5 class="modal-title" id="modalExportPdfLabel">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF Kegiatan (Filter Selektif)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formExportPdf" method="GET" action="{{ route('events.export') }}">
                <!-- Bawa serta filter aktif dari halaman utama bila ada -->
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if(request('status') && request('status') !== 'All')
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('start_date'))
                    <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                @endif
                @if(request('end_date'))
                    <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                @endif

                <div class="modal-body p-4">
                    <!-- Shortcut Pilih Semua Global -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 mb-3 rounded-3 border" style="background: #f8fafc;">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 13px;">Pilih Kriteria Kegiatan untuk Diekspor</div>
                            <div class="text-muted" style="font-size: 11.5px;">Centang Kategori Fungsi Safeguard dan Perusahaan/Instansi yang ingin disertakan dalam laporan PDF.</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="checkAllExportGlobal" checked onchange="toggleAllExportCheckboxes(this.checked)" style="cursor: pointer;">
                            <label class="form-check-label fw-bold text-primary" for="checkAllExportGlobal" style="font-size: 12.5px; cursor: pointer;">
                                Pilih Semua (Select All)
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Kolom 1: Kategori / Fungsi Safeguard -->
                        <div class="col-md-5">
                            <div class="p-3 rounded-3 border h-100" style="background: #ffffff;">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-dark" style="font-size: 12.5px;">
                                        <i class="bi bi-shield-check text-primary me-1"></i> Kategori / Fungsi Safeguard
                                    </span>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="checkAllSafeguardExport" checked onchange="toggleGroupExportCheckboxes('.export-safeguard-cb', this.checked)" style="cursor: pointer;">
                                        <label class="form-check-label text-muted fw-semibold" for="checkAllSafeguardExport" style="font-size: 11px; cursor: pointer;">Semua</label>
                                    </div>
                                </div>

                                <div class="d-flex flex-column gap-2 pt-1">
                                    @foreach(\App\Models\Event::SAFEGUARD_FUNCTIONS as $idx => $func)
                                        <div class="form-check">
                                            <input class="form-check-input export-safeguard-cb" type="checkbox" name="safeguard_functions[]" value="{{ $func }}" id="expSafeguard_{{ $idx }}" checked onchange="syncExportMasterCheckboxes()" style="cursor: pointer;">
                                            <label class="form-check-label text-dark fw-medium" for="expSafeguard_{{ $idx }}" style="font-size: 12.5px; cursor: pointer;">
                                                {{ $func }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Kolom 2: Perusahaan / Instansi -->
                        <div class="col-md-7">
                            <div class="p-3 rounded-3 border h-100" style="background: #ffffff;">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-dark" style="font-size: 12.5px;">
                                        <i class="bi bi-buildings text-success me-1"></i> Perusahaan / Instansi
                                    </span>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="checkAllCompanyExport" checked onchange="toggleGroupExportCheckboxes('.export-company-cb', this.checked)" style="cursor: pointer;">
                                        <label class="form-check-label text-muted fw-semibold" for="checkAllCompanyExport" style="font-size: 11px; cursor: pointer;">Semua</label>
                                    </div>
                                </div>

                                <div class="row g-2 pt-1" id="exportCompanyChecklistContainer" style="max-height: 230px; overflow-y: auto;">
                                    @foreach($allCompanies as $cIdx => $comp)
                                        <div class="col-sm-6 export-company-item" data-export-company="{{ $comp }}">
                                            <div class="form-check">
                                                <input class="form-check-input export-company-cb" type="checkbox" name="companies[]" value="{{ $comp }}" id="expCompany_{{ $cIdx }}" checked onchange="syncExportMasterCheckboxes()" style="cursor: pointer;">
                                                <label class="form-check-label text-dark fw-medium text-truncate d-block" for="expCompany_{{ $cIdx }}" title="{{ $comp }}" style="font-size: 12px; cursor: pointer;">
                                                    {{ $comp }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger px-4 fw-semibold d-inline-flex align-items-center gap-1" id="btnSubmitDownloadPdf">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL KELOLA PERUSAHAAN / INSTANSI (TAMBAH & HAPUS) -->
<div class="modal fade" id="modalManageCompanies" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="header-icon-box green" style="width:34px; height:34px; font-size:16px;">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Kelola Perusahaan / Instansi</h5>
                        <div class="text-muted" style="font-size:11px;">Tambah atau hapus master opsi Instansi/Perusahaan</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Form Tambah Instansi Baru (Bagian Atas) --}}
                <div class="mb-3 p-3 bg-light rounded border">
                    <label class="form-label fw-bold mb-2" style="font-size:12px;">+ Tambah Perusahaan / Instansi Baru</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="inputNewCompanyName" class="form-control" placeholder="Contoh: PT Medco Power, EBTKE, dsb...">
                        <button type="button" class="btn btn-success" onclick="submitAddNewCompany()">
                            <i class="bi bi-plus-lg me-1"></i> Simpan
                        </button>
                    </div>
                </div>

                {{-- Daftar Instansi & Hapus (Bagian Bawah) --}}
                <div class="fw-bold text-dark small mb-2 d-flex justify-content-between align-items-center">
                    <span>Daftar Perusahaan / Instansi Saat Ini</span>
                    <span class="badge bg-secondary-subtle text-secondary" id="countActiveCompanies">{{ count($allCompanies) }} Opsi</span>
                </div>

                <div class="list-group" id="listManageCompanies" style="max-height: 280px; overflow-y: auto;">
                    @foreach($allCompanies as $comp)
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3" data-company-item="{{ $comp }}">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-building text-success"></i>
                                <span class="fw-semibold text-dark" style="font-size: 12.5px;">{{ $comp }}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 11px;" onclick="deleteCompanyItem({{ Js::from($comp) }})" title="Hapus instansi ini">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Compatibility Container for Standard Units -->
<div class="d-none" id="compatibilityStandardUnits">
    @foreach(\App\Models\Event::UNITS as $stdUnit)
        <span>{{ $stdUnit }}</span>
    @endforeach
</div>

<!-- Hidden Form for Delete -->
<form id="deleteEventForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let currentDetailEventId = null;

    // Mini Chart Tren 6 Bulan Terakhir di Card "Total Pekerjaan / Kegiatan" (Bar Chart)
    document.addEventListener('DOMContentLoaded', function() {
        const miniCtx = document.getElementById('miniTrendChart');
        if (miniCtx) {
            const trendLabels = {!! json_encode($trendMonths) !!};
            const trendData = {!! json_encode($trendCounts) !!};
            const totalTrendPoints = (trendData || []).reduce((acc, v) => acc + Number(v || 0), 0);

            if (totalTrendPoints === 0) {
                return;
            }

            const isMobile = window.innerWidth < 576;
            new Chart(miniCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Kegiatan',
                        data: trendData,
                        backgroundColor: '#818cf8',
                        hoverBackgroundColor: '#6366f1',
                        borderRadius: 5,
                        borderSkipped: false,
                        barPercentage: 0.65,
                        categoryPercentage: 0.85
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 4,
                            bottom: 0,
                            left: 0,
                            right: 0
                        }
                    },
                    animation: {
                        duration: 700
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#ffffff',
                            bodyColor: '#cbd5e1',
                            borderColor: '#334155',
                            borderWidth: 1,
                            padding: 8,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                title: (items) => items.length ? `Bulan ${items[0].label}` : '',
                                label: (ctx) => `${ctx.raw} kegiatan`
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                autoSkip: true,
                                maxTicksLimit: isMobile ? 6 : 12,
                                maxRotation: 0,
                                font: {
                                    size: isMobile ? 10 : 11,
                                    weight: '600'
                                },
                                color: '#64748b',
                                padding: 6
                            }
                        },
                        y: {
                            display: true,
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9',
                                drawBorder: false,
                                borderDash: [4, 4]
                            },
                            ticks: {
                                stepSize: 1,
                                precision: 0,
                                font: {
                                    size: isMobile ? 10 : 11,
                                    weight: '500'
                                },
                                color: '#94a3b8',
                                padding: 8
                            }
                        }
                    }
                }
            });
        }
    });

    // Otomatisasi status kegiatan berdasarkan tanggal aktual (Direncanakan / Berlangsung / Selesai)
    function getLocalTodayString() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function autoUpdateStatusByDate(dateVal, statusSelectId) {
        const statusSel = document.getElementById(statusSelectId);
        if (!statusSel || !dateVal) return;

        // Jangan timpa jika status sudah diatur secara manual menjadi Dibatalkan
        if (statusSel.value === 'Dibatalkan') return;

        const today = getLocalTodayString();
        if (dateVal > today) {
            statusSel.value = 'Direncanakan';
        } else if (dateVal === today) {
            statusSel.value = 'Berlangsung';
        } else {
            statusSel.value = 'Selesai';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        [
            { dateId: 'tambahEventDate', statusId: 'tambahStatus' },
            { dateId: 'editEventDate', statusId: 'editStatus' }
        ].forEach(({ dateId, statusId }) => {
            const dateInput = document.getElementById(dateId);
            if (dateInput) {
                ['change', 'input'].forEach(evtName => {
                    dateInput.addEventListener(evtName, function() {
                        autoUpdateStatusByDate(this.value, statusId);
                    });
                });
            }
        });

        const modalTambahEl = document.getElementById('modalTambahEvent');
        if (modalTambahEl) {
            modalTambahEl.addEventListener('show.bs.modal', function() {
                const tambahDateEl = document.getElementById('tambahEventDate');
                const tambahStatusEl = document.getElementById('tambahStatus');
                if (tambahDateEl && tambahStatusEl && tambahStatusEl.value !== 'Dibatalkan') {
                    autoUpdateStatusByDate(tambahDateEl.value, 'tambahStatus');
                }
            });
        }
    });

    // Otomatis Submit saat Filter Berubah
    ['filterSafeguardFunction', 'filterStatus', 'filterStartDate', 'filterEndDate'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => {
                const startEl = document.getElementById('filterStartDate');
                const endEl = document.getElementById('filterEndDate');
                const box = document.getElementById('dateRangeBox');
                if (box) {
                    if ((startEl && startEl.value) || (endEl && endEl.value)) {
                        box.classList.add('has-date');
                    } else {
                        box.classList.remove('has-date');
                    }
                }
                document.getElementById('filterForm').submit();
            });
        }
    });

    function clearDateRange() {
        const start = document.getElementById('filterStartDate');
        const end = document.getElementById('filterEndDate');
        const box = document.getElementById('dateRangeBox');
        if (start) start.value = '';
        if (end) end.value = '';
        if (box) box.classList.remove('has-date');
        document.getElementById('filterForm').submit();
    }

    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            document.getElementById('filterPerPage').value = this.value;
            document.getElementById('filterForm').submit();
        });
    }

    // Modal Manajemen Master Perusahaan / Instansi (Tambah & Hapus)
    function openManageCompaniesModal() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalManageCompanies')).show();
    }

    async function submitAddNewCompany() {
        const input = document.getElementById('inputNewCompanyName');
        const name = input ? input.value.trim() : '';

        if (!name) {
            Swal.fire('Peringatan', 'Silakan ketikkan nama perusahaan / instansi baru.', 'warning');
            return;
        }

        try {
            const res = await fetch("{{ route('events.companies.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: name })
            });

            const data = await res.json();
            if (data.success) {
                if (input) input.value = '';

                // Append ke list modal jika belum ada
                const list = document.getElementById('listManageCompanies');
                if (list) {
                    let existingItem = list.querySelector(`[data-company-item="${CSS.escape(name)}"]`);
                    if (!existingItem) {
                        const item = document.createElement('div');
                        item.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3';
                        item.setAttribute('data-company-item', name);
                        item.innerHTML = `
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-building text-success"></i>
                                <span class="fw-semibold text-dark" style="font-size: 12.5px;">${escapeHtml(name)}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 11px;" onclick="deleteCompanyItem('${escapeHtml(name)}')" title="Hapus instansi ini">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                        list.appendChild(item);

                        const countBadge = document.getElementById('countActiveCompanies');
                        if (countBadge) {
                            countBadge.textContent = `${list.children.length} Opsi`;
                        }
                    }
                }

                // Update kedua dropdown form (tambah dan edit) dan set selected
                ['selectCompanyTambah', 'selectCompanyEdit'].forEach(selId => {
                    const sel = document.getElementById(selId);
                    if (sel) {
                        let optExists = false;
                        for (let i = 0; i < sel.options.length; i++) {
                            if (sel.options[i].value.toLowerCase() === name.toLowerCase()) {
                                optExists = true;
                                sel.selectedIndex = i;
                                break;
                            }
                        }
                        if (!optExists) {
                            const newOpt = new Option(name, name, true, true);
                            sel.add(newOpt);
                            sel.value = name;
                        }
                    }
                });

                // Tambahkan juga ke checklist Modal Export PDF
                const expCompContainer = document.getElementById('exportCompanyChecklistContainer');
                if (expCompContainer && !expCompContainer.querySelector(`[data-export-company="${CSS.escape(name)}"]`)) {
                    const newIdx = Date.now();
                    const colDiv = document.createElement('div');
                    colDiv.className = 'col-sm-6 export-company-item';
                    colDiv.setAttribute('data-export-company', name);
                    colDiv.innerHTML = `
                        <div class="form-check">
                            <input class="form-check-input export-company-cb" type="checkbox" name="companies[]" value="${escapeHtml(name)}" id="expCompany_${newIdx}" checked onchange="syncExportMasterCheckboxes()" style="cursor: pointer;">
                            <label class="form-check-label text-dark fw-medium text-truncate d-block" for="expCompany_${newIdx}" title="${escapeHtml(name)}" style="font-size: 12px; cursor: pointer;">
                                ${escapeHtml(name)}
                            </label>
                        </div>
                    `;
                    expCompContainer.appendChild(colDiv);
                    syncExportMasterCheckboxes();
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Gagal', data.message || 'Tidak dapat menambahkan instansi.', 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Terjadi kesalahan sistem saat menyimpan instansi.', 'error');
        }
    }

    async function deleteCompanyItem(companyName) {
        Swal.fire({
            title: 'Hapus Instansi / Perusahaan?',
            html: `Apakah Anda yakin ingin menghapus opsi <strong>"${escapeHtml(companyName)}"</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`/events/companies/${encodeURIComponent(companyName)}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (data.success) {
                    // Hapus dari list modal
                    const list = document.getElementById('listManageCompanies');
                    if (list) {
                        const item = list.querySelector(`[data-company-item="${CSS.escape(companyName)}"]`);
                        if (item) item.remove();

                        const countBadge = document.getElementById('countActiveCompanies');
                        if (countBadge) {
                            countBadge.textContent = `${list.children.length} Opsi`;
                        }
                    }

                    // Hapus opsi dari kedua select
                    ['selectCompanyTambah', 'selectCompanyEdit'].forEach(selId => {
                        const sel = document.getElementById(selId);
                        if (sel) {
                            for (let i = 0; i < sel.options.length; i++) {
                                if (sel.options[i].value.toLowerCase() === companyName.toLowerCase()) {
                                    sel.remove(i);
                                    break;
                                }
                            }
                        }
                    });

                    // Hapus juga dari checklist Modal Export PDF
                    const expCompContainer = document.getElementById('exportCompanyChecklistContainer');
                    if (expCompContainer) {
                        const expItem = expCompContainer.querySelector(`[data-export-company="${CSS.escape(companyName)}"]`);
                        if (expItem) expItem.remove();
                        syncExportMasterCheckboxes();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal Menghapus', data.message || 'Instansi tidak dapat dihapus.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan sistem saat menghapus instansi.', 'error');
            }
        });
    }

    // Escape helper
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Modal Detail Kegiatan
    async function openDetailModal(id, activeTab = 'docs') {
        currentDetailEventId = id;
        try {
            const response = await fetch(`/events/${id}`, {
                headers: { 'Accept': 'application/json' }
            });
            const res = await response.json();

            if (res.success) {
                const e = res.event;
                document.getElementById('detailJudul').textContent = e.title;
                if (document.getElementById('detailSafeguard')) {
                    document.getElementById('detailSafeguard').textContent = e.safeguard_function || 'Health & Safety';
                }
                document.getElementById('detailCompany').textContent = e.company || '-';
                if (document.getElementById('detailPic')) document.getElementById('detailPic').textContent = e.pic || '-';

                const dateFormatted = e.event_date ? new Date(e.event_date).toLocaleDateString('id-ID', {
                    day: 'numeric', month: 'long', year: 'numeric'
                }) : '-';
                document.getElementById('detailTanggal').textContent = dateFormatted;

                const badge = document.getElementById('detailStatusBadge');
                badge.textContent = e.status;
                badge.className = 'badge-status ' + (
                    e.status === 'Selesai' ? 'status-selesai' :
                    e.status === 'Berlangsung' ? 'status-berlangsung' :
                    e.status === 'Dibatalkan' ? 'status-dibatalkan' : 'status-direncanakan'
                );

                document.getElementById('detailDeskripsi').textContent = e.description || '-';
                document.getElementById('detailDibuat').textContent = (e.created_by || 'Administrator') +
                    (e.created_at ? ' (' + new Date(e.created_at).toLocaleDateString('id-ID') + ')' : '');
                document.getElementById('detailDiubah').textContent = (e.updated_by || 'Administrator') +
                    (e.updated_at ? ' (' + new Date(e.updated_at).toLocaleDateString('id-ID') + ')' : '');

                document.getElementById('detailDocsCount').textContent = res.documents.length;

                const gallery = document.getElementById('detailGalleryGrid');
                let galleryHtml = '';

                if (res.documents && res.documents.length > 0) {
                    res.documents.forEach((doc, idx) => {
                        const fileName = doc.file_name || doc.title || `Berkas ${idx + 1}`;
                        const ext = fileName.split('.').pop().toLowerCase();
                        const isImg = (doc.mime_type && doc.mime_type.startsWith('image/')) || ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);

                        let previewContent = '';
                        let clickAttr = '';

                        if (isImg) {
                            previewContent = `<img src="${escapeHtml(doc.file_url)}" alt="${escapeHtml(fileName)}" class="gallery-thumb-img" loading="lazy">`;
                            clickAttr = `onclick="previewImage('${escapeHtml(doc.file_url)}', '${escapeHtml(fileName)}')" style="cursor: pointer;"`;
                        } else {
                            let iconClass = 'bi-file-earmark-text-fill text-secondary';
                            let iconColor = '#64748b';
                            let extLabel = ext.toUpperCase();

                            if (ext === 'pdf') {
                                iconClass = 'bi-file-earmark-pdf-fill';
                                iconColor = '#ef4444';
                            } else if (['doc', 'docx'].includes(ext)) {
                                iconClass = 'bi-file-earmark-word-fill';
                                iconColor = '#2563eb';
                            } else if (['xls', 'xlsx'].includes(ext)) {
                                iconClass = 'bi-file-earmark-excel-fill';
                                iconColor = '#16a34a';
                            }

                            previewContent = `
                                <div class="gallery-doc-placeholder" title="Pratinjau berkas: ${escapeHtml(fileName)}">
                                    <i class="bi ${iconClass} fs-1" style="color: ${iconColor};"></i>
                                    <span class="badge bg-secondary-subtle text-secondary mt-1" style="font-size: 9px; font-weight: 700;">${extLabel}</span>
                                </div>
                            `;
                            if (ext === 'pdf' && doc.file_url) {
                                clickAttr = `onclick="window.open('${escapeHtml(doc.file_url)}', '_blank')" style="cursor: pointer;"`;
                            }
                        }

                        const sizeFormatted = doc.file_size
                            ? (doc.file_size > 1024 * 1024 ? (doc.file_size / (1024 * 1024)).toFixed(1) + ' MB' : Math.round(doc.file_size / 1024) + ' KB')
                            : '';

                        galleryHtml += `
                            <div class="gallery-item-card" title="${escapeHtml(fileName)}">
                                <div ${clickAttr}>
                                    ${previewContent}
                                </div>
                                <div class="gallery-label d-flex justify-content-between align-items-center">
                                    <div class="text-truncate text-start w-100">
                                        <div class="text-truncate fw-semibold" style="font-size: 11px;">${escapeHtml(fileName)}</div>
                                        ${sizeFormatted ? `<div class="text-muted" style="font-size: 9.5px;">${sizeFormatted}</div>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    galleryHtml += `
                        <div class="col-12 text-center py-4 text-muted small" style="grid-column: 1 / -1;">
                            <i class="bi bi-folder2-open fs-2 d-block text-secondary mb-1"></i>
                            Belum ada berkas dokumentasi yang diunggah.
                        </div>
                    `;
                }

                gallery.innerHTML = galleryHtml;

                const logTable = document.getElementById('detailLogsTableBody');
                if (res.logs && res.logs.length > 0) {
                    logTable.innerHTML = res.logs.map(log => `
                        <tr>
                            <td>${log.created_at || '-'}</td>
                            <td class="fw-semibold">${log.user_name || 'Admin'}</td>
                            <td><span class="badge bg-secondary">${log.action}</span></td>
                            <td>${log.description}</td>
                        </tr>
                    `).join('');
                } else {
                    logTable.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Belum ada riwayat aktivitas.</td></tr>`;
                }

                if (activeTab === 'logs') {
                    new bootstrap.Tab(document.getElementById('tabLogsBtn')).show();
                } else {
                    new bootstrap.Tab(document.getElementById('tabDocsBtn')).show();
                }
                new bootstrap.Modal(document.getElementById('modalDetailEvent')).show();
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Gagal memuat detail kegiatan', 'error');
        }
    }

    let previewModalInstance = null;

    function getPreviewModalInstance() {
        const modalEl = document.getElementById('modalImagePreview');
        if (!previewModalInstance && modalEl) {
            previewModalInstance = new bootstrap.Modal(modalEl, {
                backdrop: true,
                keyboard: true
            });
        }
        return previewModalInstance;
    }

    function previewImage(url, title) {
        const img = document.getElementById('modalPreviewImage');
        const spinner = document.getElementById('previewLoadingSpinner');
        const fallback = document.getElementById('previewFallbackMsg');
        const titleEl = document.getElementById('modalPreviewTitle');

        titleEl.textContent = title || 'Dokumentasi Kegiatan';

        if (!url || url.trim() === '' || url === 'null' || url === 'undefined') {
            img.style.display = 'none';
            img.src = '';
            spinner.style.display = 'none';
            fallback.style.display = 'block';
            getPreviewModalInstance().show();
            return;
        }

        // Format and normalize URL if needed
        let finalUrl = url.trim();
        if (!finalUrl.startsWith('http://') && !finalUrl.startsWith('https://') && !finalUrl.startsWith('/')) {
            finalUrl = '/' + finalUrl;
        }

        // Reset state
        fallback.style.display = 'none';
        img.style.display = 'none';
        spinner.style.display = 'block';

        // Load image
        img.src = finalUrl;
        getPreviewModalInstance().show();
    }

    function onPreviewImageLoad() {
        const img = document.getElementById('modalPreviewImage');
        const spinner = document.getElementById('previewLoadingSpinner');
        const fallback = document.getElementById('previewFallbackMsg');

        spinner.style.display = 'none';
        fallback.style.display = 'none';
        img.style.display = 'block';
    }

    function onPreviewImageError() {
        const img = document.getElementById('modalPreviewImage');
        const spinner = document.getElementById('previewLoadingSpinner');
        const fallback = document.getElementById('previewFallbackMsg');

        spinner.style.display = 'none';
        img.style.display = 'none';
        fallback.style.display = 'block';
    }

    // Helper Checklist Modal Export PDF
    function toggleAllExportCheckboxes(checked) {
        document.querySelectorAll('.export-safeguard-cb, .export-company-cb').forEach(cb => {
            cb.checked = checked;
        });
        const sgAll = document.getElementById('checkAllSafeguardExport');
        const compAll = document.getElementById('checkAllCompanyExport');
        if (sgAll) sgAll.checked = checked;
        if (compAll) compAll.checked = checked;
    }

    function toggleGroupExportCheckboxes(selector, checked) {
        document.querySelectorAll(selector).forEach(cb => {
            cb.checked = checked;
        });
        syncExportMasterCheckboxes();
    }

    function syncExportMasterCheckboxes() {
        const sgCbs = Array.from(document.querySelectorAll('.export-safeguard-cb'));
        const compCbs = Array.from(document.querySelectorAll('.export-company-cb'));

        const allSgChecked = sgCbs.length > 0 && sgCbs.every(cb => cb.checked);
        const allCompChecked = compCbs.length > 0 && compCbs.every(cb => cb.checked);

        const sgAll = document.getElementById('checkAllSafeguardExport');
        const compAll = document.getElementById('checkAllCompanyExport');
        const globalAll = document.getElementById('checkAllExportGlobal');

        if (sgAll) sgAll.checked = allSgChecked;
        if (compAll) compAll.checked = allCompChecked;
        if (globalAll) globalAll.checked = allSgChecked && allCompChecked;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const previewImg = document.getElementById('modalPreviewImage');
        if (previewImg) {
            previewImg.addEventListener('load', function() {
                if (this.getAttribute('src')) {
                    onPreviewImageLoad();
                }
            });
            previewImg.addEventListener('error', function() {
                if (this.getAttribute('src')) {
                    onPreviewImageError();
                }
            });
        }

        const formExportPdf = document.getElementById('formExportPdf');
        if (formExportPdf) {
            formExportPdf.addEventListener('submit', function(e) {
                const checkedSg = document.querySelectorAll('.export-safeguard-cb:checked').length;
                const checkedComp = document.querySelectorAll('.export-company-cb:checked').length;

                if (checkedSg === 0 || checkedComp === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pilihan Belum Lengkap',
                        text: 'Silakan pilih minimal satu Kategori Fungsi Safeguard dan satu Perusahaan/Instansi untuk diekspor.'
                    });
                    return;
                }

                // Tutup modal setelah form di-submit untuk mengunduh PDF
                const modalEl = document.getElementById('modalExportPdf');
                if (modalEl) {
                    const inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) inst.hide();
                }
            });
        }
    });

    function validateSelectedFiles(input) {
        const files = input?.files;
        if (!files || files.length === 0) return false;
        const maxBytes = 10 * 1024 * 1024;
        const allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        for (let i = 0; i < files.length; i++) {
            const f = files[i];
            const ext = (f.name.split('.').pop() || '').toLowerCase();
            if (!allowedExt.includes(ext)) {
                Swal.fire({ icon: 'warning', title: 'Format File Tidak Didukung', text: `File "${f.name}" tidak diizinkan.` });
                input.value = '';
                return false;
            }
            if (f.size > maxBytes) {
                Swal.fire({ icon: 'warning', title: 'Ukuran File Terlalu Besar', text: `File "${f.name}" melebihi batas maksimal 10MB.` });
                input.value = '';
                return false;
            }
        }
        return true;
    }

    async function uploadDirectDoc(input) {
        if (!input.files || input.files.length === 0 || !currentDetailEventId) return;

        if (!validateSelectedFiles(input)) {
            return;
        }

        const formData = new FormData();
        for (let i = 0; i < input.files.length; i++) {
            formData.append('files[]', input.files[i]);
        }
        formData.append('_token', csrfToken);

        try {
            Swal.fire({
                title: 'Mengunggah...',
                text: 'Sedang menyimpan file dokumentasi',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const res = await fetch(`/events/${currentDetailEventId}/upload`, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });

            const data = await res.json();
            if (data.success) {
                Swal.fire('Berhasil', data.message, 'success');
                openDetailModal(currentDetailEventId, 'docs');
            } else {
                Swal.fire('Gagal', data.message || 'Gagal mengunggah foto', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Terjadi kesalahan sistem saat upload', 'error');
        }
    }

    async function openEditModal(id) {
        try {
            const response = await fetch(`/events/${id}`, {
                headers: { 'Accept': 'application/json' }
            });
            const res = await response.json();

            if (res.success) {
                const e = res.event;
                const form = document.getElementById('formEditEvent');
                form.action = `/events/${id}`;

                document.getElementById('editTitle').value = e.title || '';
                document.getElementById('editEventDate').value = e.event_date ? e.event_date.split('T')[0] : '';

                if (document.getElementById('editSafeguardFunction')) {
                    document.getElementById('editSafeguardFunction').value = e.safeguard_function || 'Health & Safety';
                }

                const selComp = document.getElementById('selectCompanyEdit');
                if (selComp && e.company) {
                    let found = false;
                    for (let i = 0; i < selComp.options.length; i++) {
                        if (selComp.options[i].value === e.company) {
                            selComp.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        selComp.add(new Option(e.company, e.company, true, true));
                    }
                }

                if (document.getElementById('editPic')) {
                    document.getElementById('editPic').value = e.pic || '';
                }

                const editStatusEl = document.getElementById('editStatus');
                if (editStatusEl) {
                    editStatusEl.value = e.status || 'Direncanakan';
                }
                document.getElementById('editDescription').value = e.description || '';

                new bootstrap.Modal(document.getElementById('modalEditEvent')).show();
            }
        } catch (err) {
            Swal.fire('Error', 'Gagal memuat data kegiatan untuk diedit', 'error');
        }
    }

    function confirmDeleteEvent(id, title) {
        Swal.fire({
            title: 'Hapus Kegiatan?',
            html: `Apakah Anda yakin ingin menghapus kegiatan <b>"${title}"</b>?<br><small class="text-danger">Tindakan ini tidak dapat dibatalkan dan seluruh dokumentasi terkait akan terhapus.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteEventForm');
                form.action = `/events/${id}`;
                form.submit();
            }
        });
    }

    // Validasi Ukuran File di Client-Side (Maksimal 15 MB per berkas)
    const MAX_FILE_SIZE_BYTES = 15 * 1024 * 1024; // 15 MB
    const ALLOWED_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.webp', '.gif', '.pdf', '.doc', '.docx', '.xls', '.xlsx'];

    // In-memory DataTransfer containers to support individual file removals
    const selectedFilesMap = {
        tambah: new DataTransfer(),
        edit: new DataTransfer()
    };

    function handleFilesSelected(input, type) {
        if (!input.files || input.files.length === 0) return;

        const dt = selectedFilesMap[type];

        // Validasi dan masukkan file baru ke DataTransfer jika belum ada
        for (let i = 0; i < input.files.length; i++) {
            const file = input.files[i];
            const ext = '.' + file.name.split('.').pop().toLowerCase();

            // Cek ekstensi / format
            if (!ALLOWED_EXTENSIONS.includes(ext)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Format File Tidak Didukung',
                    html: `Berkas <b>"${escapeHtml(file.name)}"</b> memiliki format yang tidak didukung.<br><small class="text-muted">Format yang diizinkan: JPG, JPEG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX</small>`,
                });
                continue;
            }

            // Cek ukuran file (> 15 MB)
            if (file.size > MAX_FILE_SIZE_BYTES) {
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                Swal.fire({
                    icon: 'error',
                    title: 'Ukuran File Terlalu Besar',
                    html: `Berkas <b>"${escapeHtml(file.name)}"</b> berukuran <b>${fileSizeMB} MB</b>.<br><span class="text-danger">Maksimal ukuran berkas adalah 15 MB per file.</span>`,
                });
                continue;
            }

            // Hindari duplikat file dengan nama dan ukuran sama
            let alreadyExists = false;
            for (let j = 0; j < dt.files.length; j++) {
                if (dt.files[j].name === file.name && dt.files[j].size === file.size) {
                    alreadyExists = true;
                    break;
                }
            }

            if (!alreadyExists) {
                dt.items.add(file);
            }
        }

        // Sinkronkan input file dengan DataTransfer
        input.files = dt.files;
        renderFilePreviewBadges(type);
    }

    function removeSelectedFile(index, type) {
        const dt = selectedFilesMap[type];
        const newDt = new DataTransfer();

        for (let i = 0; i < dt.files.length; i++) {
            if (i !== index) {
                newDt.items.add(dt.files[i]);
            }
        }

        selectedFilesMap[type] = newDt;
        const inputId = type === 'tambah' ? 'inputDocsTambah' : 'inputDocsEdit';
        const input = document.getElementById(inputId);
        if (input) {
            input.files = newDt.files;
        }

        renderFilePreviewBadges(type);
    }

    function renderFilePreviewBadges(type) {
        const containerId = type === 'tambah' ? 'previewFilesTambahContainer' : 'previewFilesEditContainer';
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        const dt = selectedFilesMap[type];

        if (dt.files.length === 0) return;

        Array.from(dt.files).forEach((file, index) => {
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

            const badge = document.createElement('div');
            badge.className = 'd-inline-flex align-items-center gap-2 py-1 px-2 rounded-2 border bg-white shadow-sm';
            badge.style.fontSize = '12px';
            badge.innerHTML = `
                <i class="bi ${iconClass}"></i>
                <span class="text-truncate fw-medium" style="max-width: 220px;" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span>
                <span class="text-muted" style="font-size: 10.5px;">(${sizeText})</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1" style="font-size: 13px; line-height: 1;" onclick="removeSelectedFile(${index}, '${type}')" title="Hapus berkas ini">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            `;
            container.appendChild(badge);
        });
    }

    // Reset DataTransfer saat modal ditutup
    document.addEventListener('DOMContentLoaded', function() {
        const modalTambah = document.getElementById('modalTambahEvent');
        if (modalTambah) {
            modalTambah.addEventListener('hidden.bs.modal', function() {
                selectedFilesMap.tambah = new DataTransfer();
                const inp = document.getElementById('inputDocsTambah');
                if (inp) inp.value = '';
                const c = document.getElementById('previewFilesTambahContainer');
                if (c) c.innerHTML = '';
            });
        }

        const modalEdit = document.getElementById('modalEditEvent');
        if (modalEdit) {
            modalEdit.addEventListener('hidden.bs.modal', function() {
                selectedFilesMap.edit = new DataTransfer();
                const inp = document.getElementById('inputDocsEdit');
                if (inp) inp.value = '';
                const c = document.getElementById('previewFilesEditContainer');
                if (c) c.innerHTML = '';
            });
        }
    });

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            timer: 3000,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: "{{ session('error') }}",
        });
    @endif
</script>
@endpush
