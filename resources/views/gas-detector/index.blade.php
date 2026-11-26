@extends('layouts.app', [
    'title' => 'Gas Monitoring - Pemantauan Gas Well Pad',
    'activeModule' => 'gas-detector'
])

@push('styles')
<style>
    /* =========================================================================
       1. HEADER LAYOUT & BIG DATE STRIP (Seragam dengan Man Hours & Manpower)
       ========================================================================= */
    .gas-top-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 20px;
    }

    .gas-header-brand {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .gas-header-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #059669;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
        flex-shrink: 0;
    }

    .gas-header-title {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
        margin: 0;
        letter-spacing: -0.3px;
    }

    .gas-breadcrumb-nav {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .gas-breadcrumb-nav a {
        color: inherit;
        text-decoration: none;
    }

    .gas-breadcrumb-nav a:hover {
        color: #059669;
    }

    .gas-header-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .btn-action-emerald {
        background-color: #059669;
        color: #ffffff !important;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 6px rgba(5, 150, 105, 0.2);
        transition: all 0.2s ease;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
    }
    .btn-action-emerald:hover {
        background-color: #047857;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);
    }

    .btn-action-white {
        background-color: #ffffff;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
    }
    .btn-action-white:hover {
        background-color: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a !important;
        transform: translateY(-1px);
    }

    /* BIG DATE STRIP (Persis seperti Man Hours & Manpower) */
    .gas-date-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 22px;
    }

    .gas-big-date-box {
        display: inline-flex;
        align-items: center;
        gap: 16px;
        background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        border: 1px solid #bbf7d0;
        border-radius: 16px;
        padding: 10px 22px 10px 14px;
        box-shadow: 0 3px 12px rgba(5, 150, 105, 0.08), 0 1px 3px rgba(0, 0, 0, 0.02);
        position: relative;
        overflow: hidden;
    }

    .gas-big-date-box::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 60px;
        height: 100%;
        background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.12), transparent 70%);
        pointer-events: none;
    }

    .gas-calendar-icon-box {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: #059669;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
        flex-shrink: 0;
    }

    .gas-big-date-info {
        display: flex;
        flex-direction: column;
    }

    .gas-big-date-day-badge {
        font-size: 11px;
        font-weight: 800;
        color: #059669;
        letter-spacing: 0.4px;
        line-height: 1.2;
    }

    .gas-big-date-text {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        line-height: 1.15;
    }

    /* =========================================================================
       2. FILTER BAR (Filter Periode, Parameter Periode Dinamis, Well Pad, Indikator)
       ========================================================================= */
    .gas-filter-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 11px 16px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
        margin-bottom: 16px;
    }
    .gas-filter-grid {
        display: flex;
        align-items: flex-end;
        flex-wrap: nowrap;
        gap: 10px;
    }
    .filter-col-period-type {
        flex: 0 1 155px;
        min-width: 135px;
    }
    .filter-col-period-input {
        flex: 1 1 250px;
        min-width: 210px;
    }
    .filter-dynamic-controls {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        width: 100%;
    }
    .filter-dynamic-subcol {
        flex: 1 1 0;
        min-width: 140px;
    }
    .filter-date-input-wrap {
        position: relative;
    }
    .filter-date-input-wrap i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 13px;
        pointer-events: none;
    }
    .filter-date-input-wrap input {
        padding-left: 32px !important;
    }
    .filter-col-well-pad {
        flex: 0 1 165px;
        min-width: 140px;
    }
    .filter-col-indicator {
        flex: 0 1 165px;
        min-width: 140px;
    }
    .filter-col-actions {
        flex: 0 0 auto;
        margin-left: 2px;
    }
    .filter-input-label {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        color: #64748b;
        margin-bottom: 4px;
        line-height: 1.2;
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }
    .gas-filter-panel .form-control,
    .gas-filter-panel .form-select {
        border-color: #cbd5e1;
        font-size: 12.5px;
        font-weight: 500;
        color: #1e293b;
        border-radius: 8px;
        box-shadow: none;
        height: 34px;
        min-height: 34px;
        padding: 4px 10px;
        line-height: 1.3;
        width: 100%;
        box-sizing: border-box;
    }
    .gas-filter-panel .form-select {
        padding-right: 28px;
    }
    .gas-filter-panel .form-control:focus,
    .gas-filter-panel .form-select:focus {
        border-color: #059669;
        box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.15);
    }
    .filter-col-actions .btn {
        height: 34px;
        min-height: 34px;
        padding: 0 13px;
        font-size: 12.5px;
        border-radius: 8px;
        line-height: 1;
    }
    .date-range-split {
        display: flex;
        align-items: center;
        gap: 6px;
        width: 100%;
    }
    .date-range-split .form-control {
        flex: 1 1 0;
        min-width: 0;
    }
    .date-range-split .date-separator {
        color: #94a3b8;
        font-weight: 600;
        font-size: 13px;
        flex-shrink: 0;
        user-select: none;
        padding: 0 1px;
    }
    .gas-active-period-indicator {
        margin-top: 8px;
        padding-top: 7px;
        border-top: 1px dashed #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11.5px;
        line-height: 1.3;
        color: #64748b;
        flex-wrap: wrap;
        gap: 6px;
    }
    .gas-active-period-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #ecfdf5;
        color: #059669;
        font-weight: 700;
        font-size: 11px;
        padding: 2px 10px;
        border-radius: 20px;
        border: 1px solid #a7f3d0;
    }
    @media (max-width: 1199px) {
        .gas-filter-grid {
            flex-wrap: wrap;
        }
        .filter-col-period-type,
        .filter-col-well-pad,
        .filter-col-indicator {
            flex: 1 1 160px;
        }
        .filter-col-period-input {
            flex: 2 1 260px;
        }
        .filter-col-actions {
            margin-left: 0;
        }
    }
    @media (max-width: 991px) {
        .filter-col-period-type,
        .filter-col-period-input,
        .filter-col-well-pad,
        .filter-col-indicator,
        .filter-col-actions {
            flex: 1 1 calc(50% - 10px);
        }
        .filter-col-actions {
            margin-left: 0;
        }
    }
    @media (max-width: 600px) {
        .filter-col-period-type,
        .filter-col-period-input,
        .filter-col-well-pad,
        .filter-col-indicator,
        .filter-col-actions {
            flex: 1 1 100%;
            min-width: 100%;
        }
        .filter-col-actions > .filter-input-label {
            display: none;
        }
    }

    /* =========================================================================
       3. 5 PARAMETER SUMMARY CARDS
       ========================================================================= */
    .param-card-v2 {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .param-card-v2:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.05);
    }
    .param-card-v2-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .param-v2-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
    }
    .param-v2-title {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
    }
    .param-v2-value-wrap {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 6px;
    }
    .param-v2-value {
        font-size: 26px;
        font-weight: 800;
        color: #0f2d42;
        line-height: 1;
    }
    .param-v2-threshold {
        font-size: 11px;
        color: #64748b;
        font-weight: 500;
    }

    /* Badges */
    .badge-sesuai {
        background-color: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
        font-weight: 700;
        font-size: 10.5px;
        padding: 2.5px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
    }
    .badge-tidaksesuai {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        font-weight: 700;
        font-size: 10.5px;
        padding: 2.5px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
    }

    /* =========================================================================
       4. CHART & STATISTIK SECTION
       ========================================================================= */
    .gas-chart-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .chart-tabs-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 8px 14px;
        gap: 10px;
    }
    .chart-tabs-left {
        display: flex;
        align-items: center;
        gap: 6px;
        overflow-x: auto;
    }
    .chart-tabs-summary-right {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
        flex-wrap: wrap;
    }
    .chart-summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 5px 12px;
        font-size: 12px;
        line-height: 1.2;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        white-space: nowrap;
    }
    .chart-summary-chip-label {
        color: #64748b;
        font-weight: 600;
        font-size: 11.5px;
    }
    .chart-summary-chip-val {
        color: #0f172a;
        font-weight: 800;
        font-size: 12.5px;
    }
    .chart-summary-chip-sub {
        color: #059669;
        font-weight: 600;
        font-size: 11px;
        background: #ecfdf5;
        padding: 2px 7px;
        border-radius: 5px;
    }

    /* Multi-select Well Pad Dropdown (Seragam dengan Man Hours & Manpower) */
    .wp-dropdown-wrap {
        position: relative;
        width: 100%;
    }
    .wp-dropdown-btn {
        width: 100%;
        height: 34px;
        min-height: 34px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 4px 10px;
        font-size: 12.5px;
        font-weight: 500;
        color: #1e293b;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        cursor: pointer;
        text-align: left;
        transition: border-color 0.15s ease;
    }
    .wp-dropdown-btn:hover,
    .wp-dropdown-btn:focus {
        border-color: #059669;
        outline: none;
    }
    .wp-dropdown-btn span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .wp-dropdown-menu {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        min-width: 100%;
        width: max-content;
        max-width: 260px;
        max-height: 240px;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
        padding: 6px;
        z-index: 1050;
        display: none;
    }
    .wp-dropdown-menu.show {
        display: block;
    }
    .wp-check-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 6px;
        font-size: 12.5px;
        color: #1e293b;
        cursor: pointer;
        user-select: none;
        margin: 0;
    }
    .wp-check-item:hover {
        background: #f1f5f9;
    }
    .wp-check-item input[type="checkbox"] {
        width: 15px;
        height: 15px;
        accent-color: #059669;
        cursor: pointer;
        margin: 0;
    }
    .chart-tab-pill {
        background: transparent;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 12.5px;
        font-weight: 600;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .chart-tab-pill:hover {
        background: #edf2f7;
        color: #1e293b;
    }
    .chart-tab-pill.active {
        background: #059669;
        color: #ffffff;
        box-shadow: 0 1px 3px rgba(5, 150, 105, 0.3);
    }
    .chart-tab-pill.active i, .chart-tab-pill.active span {
        color: #ffffff !important;
    }

    .gas-stat-sidebar-v2 {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .stat-row-v2 {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 9px 0;
        border-bottom: 1px dashed #f1f5f9;
    }
    .stat-row-v2:last-child {
        border-bottom: none;
    }
    .stat-icon-v2 {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    /* =========================================================================
       5. TABEL RIWAYAT & EXPORT ACTIONS
       ========================================================================= */
    .gas-table-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        padding: 20px;
        margin-bottom: 30px;
    }
    .table-gas {
        width: 100%;
        min-width: 780px;
        table-layout: auto;
    }
    .table-gas thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 11.5px;
        font-weight: 700;
        border-top: 1px solid #edf2f7;
        border-bottom: 1.5px solid #e2e8f0;
        padding: 10px 8px;
        vertical-align: middle;
    }
    .table-gas tbody td {
        font-size: 12.5px;
        padding: 10px 8px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        word-wrap: break-word;
    }
    .table-gas tbody tr:hover {
        background-color: #f8fafc;
    }

    .btn-export-pdf {
        background-color: #dc2626;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        padding: 6px 14px;
        font-weight: 600;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        text-decoration: none;
        white-space: nowrap;
    }
    .btn-export-pdf:hover {
        background-color: #b91c1c;
        color: #ffffff;
    }

    .btn-tbl-action {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: all 0.15s;
        font-size: 13px;
        cursor: pointer;
    }
    .btn-tbl-view { background-color: #e0f2fe; color: #0284c7; }
    .btn-tbl-view:hover { background-color: #bae6fd; color: #0369a1; }
    .btn-tbl-edit { background-color: #fef3c7; color: #d97706; }
    .btn-tbl-edit:hover { background-color: #fde68a; color: #b45309; }
    .btn-tbl-del { background-color: #fee2e2; color: #dc2626; }
    .btn-tbl-del:hover { background-color: #fecaca; color: #b91c1c; }
    /* =========================================================================
       PAGINATION FIX
       ========================================================================= */
    .pagination {
        margin-bottom: 0;
        display: flex;
        gap: 4px;
    }
    .pagination .page-item .page-link {
        border-radius: 8px !important;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
        padding: 5px 12px;
        transition: all 0.15s ease;
    }
    .pagination .page-item.active .page-link {
        background-color: #059669;
        border-color: #059669;
        color: #ffffff;
        box-shadow: 0 2px 5px rgba(5, 150, 105, 0.25);
    }
    .pagination .page-item .page-link:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .pagination .page-item.disabled .page-link {
        color: #cbd5e1;
        background-color: #f8fafc;
        border-color: #f1f5f9;
    }
    .pagination svg {
        width: 14px;
        height: 14px;
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<!-- ===========================================================================
     1. TOP HEADER & BREADCRUMB (Seragam dengan Man Hours & Manpower)
     =========================================================================== -->
<x-module-header
    title="Gas Monitoring"
    icon="bi bi-broadcast-pin"
    icon-bg="#059669"
    icon-shadow="rgba(5, 150, 105, 0.25)"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Gas Monitoring'],
    ]"
>
    <x-slot:actions>
        @adminManagement
            <button type="button" class="btn-action-emerald hsse-action-primary" data-bs-toggle="modal" data-bs-target="#modalBatchCreateGas">
                <i class="bi bi-plus-lg"></i> Tambah Data Gas Monitoring
            </button>
            <button type="button" class="btn-action-white" onclick="openWellPadModal()">
                <i class="bi bi-gear"></i> Kelola Well Pad
            </button>
        @endadminManagement
        @canExportPdf
            <a href="{{ route('gas-detector.measurements.export', request()->query()) }}" id="btnExportGasPdf" class="btn-export-pdf">
                <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
            </a>
        @endcanExportPdf
    </x-slot:actions>
</x-module-header>

<!-- ===========================================================================
     2. BIG DATE STRIP (Seragam dengan Man Hours & Manpower)
     =========================================================================== -->
<div class="gas-date-strip">
    <div class="gas-big-date-box">
        <div class="gas-calendar-icon-box">
            <i class="bi bi-calendar-event"></i>
        </div>
        <div class="gas-big-date-info">
            <span class="gas-big-date-day-badge" id="headerDayDisplay">
                {{ $headerDayName ?? 'Sabtu' }}
            </span>
            <span class="gas-big-date-text" id="headerPeriodLabel">
                {{ $headerDateLabel ?? $formattedPeriodLabel }}
            </span>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3 py-2 px-3 small" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3 py-2 px-3 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- ===========================================================================
     3. 5 KARTU RINGKASAN PARAMETER (HIGHLIGHT CARDS)
     =========================================================================== -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
    <!-- CO -->
    <div class="col">
        <div class="param-card-v2">
            <div class="param-card-v2-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="param-v2-icon" style="background: #f0fdf4; color: #16a34a;">
                        <i class="bi bi-cloud"></i>
                    </div>
                    <span class="param-v2-title">CO (ppm)</span>
                </div>
                <span class="{{ $summaryCards['co']['status'] === 'Sesuai' ? 'badge-sesuai' : ($summaryCards['co']['status'] === 'Tidak Sesuai' ? 'badge-tidaksesuai' : 'badge bg-light text-muted border') }}">
                    {{ $summaryCards['co']['status'] }}
                </span>
            </div>
            <div class="param-v2-value-wrap">
                <span class="param-v2-value">{{ $summaryCards['co']['val'] }}</span>
            </div>
            <div class="param-v2-threshold">{{ $summaryCards['co']['threshold'] }}</div>
        </div>
    </div>

    <!-- O2 -->
    <div class="col">
        <div class="param-card-v2">
            <div class="param-card-v2-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="param-v2-icon" style="background: #eff6ff; color: #2563eb;">
                        <span class="fw-bold fs-6">O₂</span>
                    </div>
                    <span class="param-v2-title">O₂ (%)</span>
                </div>
                <span class="{{ $summaryCards['o2']['status'] === 'Sesuai' ? 'badge-sesuai' : ($summaryCards['o2']['status'] === 'Tidak Sesuai' ? 'badge-tidaksesuai' : 'badge bg-light text-muted border') }}">
                    {{ $summaryCards['o2']['status'] }}
                </span>
            </div>
            <div class="param-v2-value-wrap">
                <span class="param-v2-value">{{ $summaryCards['o2']['val'] }}</span>
            </div>
            <div class="param-v2-threshold">{{ $summaryCards['o2']['threshold'] }}</div>
        </div>
    </div>

    <!-- H2S -->
    <div class="col">
        <div class="param-card-v2">
            <div class="param-card-v2-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="param-v2-icon" style="background: #fef2f2; color: #ef4444;">
                        <i class="bi bi-radioactive"></i>
                    </div>
                    <span class="param-v2-title">H₂S (ppm)</span>
                </div>
                <span class="{{ $summaryCards['h2s']['status'] === 'Sesuai' ? 'badge-sesuai' : ($summaryCards['h2s']['status'] === 'Tidak Sesuai' ? 'badge-tidaksesuai' : 'badge bg-light text-muted border') }}">
                    {{ $summaryCards['h2s']['status'] }}
                </span>
            </div>
            <div class="param-v2-value-wrap">
                <span class="param-v2-value">{{ $summaryCards['h2s']['val'] }}</span>
            </div>
            <div class="param-v2-threshold">{{ $summaryCards['h2s']['threshold'] }}</div>
        </div>
    </div>

    <!-- LEL -->
    <div class="col">
        <div class="param-card-v2">
            <div class="param-card-v2-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="param-v2-icon" style="background: #fffbeb; color: #d97706;">
                        <i class="bi bi-fire"></i>
                    </div>
                    <span class="param-v2-title">LEL (%)</span>
                </div>
                <span class="{{ $summaryCards['lel']['status'] === 'Sesuai' ? 'badge-sesuai' : ($summaryCards['lel']['status'] === 'Tidak Sesuai' ? 'badge-tidaksesuai' : 'badge bg-light text-muted border') }}">
                    {{ $summaryCards['lel']['status'] }}
                </span>
            </div>
            <div class="param-v2-value-wrap">
                <span class="param-v2-value">{{ $summaryCards['lel']['val'] }}</span>
            </div>
            <div class="param-v2-threshold">{{ $summaryCards['lel']['threshold'] }}</div>
        </div>
    </div>

    <!-- Pressure -->
    <div class="col">
        <div class="param-card-v2">
            <div class="param-card-v2-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="param-v2-icon" style="background: #faf5ff; color: #9333ea;">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <span class="param-v2-title">Pressure (Psi)</span>
                </div>
            </div>
            <div class="param-v2-value-wrap">
                <span class="param-v2-value">{{ $summaryCards['pressure']['val'] }}</span>
            </div>
            <div class="param-v2-threshold">&nbsp;</div>
        </div>
    </div>
</div>

<!-- ===========================================================================
     3B. NAVIGASI SECTION: HARI INI VS SEMUA RIWAYAT
     =========================================================================== -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-inline-flex p-1 bg-white border rounded-3 shadow-sm" role="tablist" aria-label="Section Gas Monitoring">
        <button type="button"
                id="btnSectionHariIni"
                class="btn btn-sm px-3 fw-semibold rounded-2 {{ ($activeSection ?? 'hari_ini') === 'hari_ini' ? 'btn-success text-white' : 'btn-light text-secondary border-0' }}"
                onclick="switchGasSection('hari_ini')"
                style="{{ ($activeSection ?? 'hari_ini') === 'hari_ini' ? 'background-color: #059669; border-color: #059669;' : '' }}">
            <i class="bi bi-calendar-check me-1"></i> Hari Ini
        </button>
        <button type="button"
                id="btnSectionSemuaRiwayat"
                class="btn btn-sm px-3 fw-semibold rounded-2 {{ ($activeSection ?? 'hari_ini') === 'semua_riwayat' ? 'btn-success text-white' : 'btn-light text-secondary border-0' }}"
                onclick="switchGasSection('semua_riwayat')"
                style="{{ ($activeSection ?? 'hari_ini') === 'semua_riwayat' ? 'background-color: #059669; border-color: #059669;' : '' }}">
            <i class="bi bi-clock-history me-1"></i> Semua Riwayat
        </button>
    </div>
    <div class="small text-muted" id="sectionModeHint">
        @if(($activeSection ?? 'hari_ini') === 'semua_riwayat')
            <i class="bi bi-graph-up me-1 text-success"></i> Analisis riwayat per Well Pad &amp; seluruh catatan pengukuran
        @else
            <i class="bi bi-activity me-1 text-success"></i> Pemantauan pengukuran hari ini ({{ $todayDisplayLabel }})
        @endif
    </div>
</div>

<!-- ===========================================================================
     4. GRAFIK PARAMETER GAS (TANPA PANEL STATISTIK)
     =========================================================================== -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="gas-chart-box mb-0">
            <!-- 5 Parameter Tabs + Ringkasan Rata-rata & Tertinggi/Terendah di Kanan -->
            <div class="chart-tabs-bar">
                <div class="chart-tabs-left">
                    <button type="button" class="chart-tab-pill active" onclick="switchChartParam('co')" id="btnTabCo">
                        <i class="bi bi-cloud text-success"></i> CO (ppm)
                    </button>
                    <button type="button" class="chart-tab-pill" onclick="switchChartParam('o2')" id="btnTabO2">
                        <span class="fw-bold text-primary">O₂</span> O₂ (%)
                    </button>
                    <button type="button" class="chart-tab-pill" onclick="switchChartParam('h2s')" id="btnTabH2s">
                        <i class="bi bi-radioactive text-danger"></i> H₂S (ppm)
                    </button>
                    <button type="button" class="chart-tab-pill" onclick="switchChartParam('lel')" id="btnTabLel">
                        <i class="bi bi-fire text-warning"></i> LEL (%)
                    </button>
                    <button type="button" class="chart-tab-pill" onclick="switchChartParam('pressure')" id="btnTabPressure">
                        <i class="bi bi-speedometer2" style="color: #9333ea;"></i> Pressure (Psi)
                    </button>
                </div>

                <!-- Ringkasan Rata-rata & Nilai Tertinggi (Khusus O2: Terendah) -->
                <div class="chart-tabs-summary-right">
                    <div class="chart-summary-chip">
                        <span class="chart-summary-chip-label">Rata-rata:</span>
                        <span class="chart-summary-chip-val" id="barTabAvgVal">
                            {{ ($statisticsData['co']['avg'] ?? '-') !== '-' ? $statisticsData['co']['avg'] . ' ppm' : '-' }}
                        </span>
                    </div>
                    <div class="chart-summary-chip">
                        <span class="chart-summary-chip-label" id="barTabExtremeLabel">Tertinggi:</span>
                        <span class="chart-summary-chip-val" id="barTabExtremeVal">
                            {{ ($statisticsData['co']['max_val'] ?? '-') !== '-' ? $statisticsData['co']['max_val'] . ' ppm' : '-' }}
                        </span>
                        <span class="chart-summary-chip-sub {{ empty($statisticsData['co']['max_pad']) || $statisticsData['co']['max_pad'] === '-' ? 'd-none' : '' }}" id="barTabExtremePad">
                            {{ $statisticsData['co']['max_pad'] ?? '' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Konten Grafik -->
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0" id="chartHeaderTitle" style="font-size: 14px;">
                        Grafik CO per Well Pad — {{ $formattedPeriodLabel }}
                    </h6>
                    <div id="chartThresholdBadge" class="text-danger fw-bold small">
                        Threshold 25 ppm
                    </div>
                </div>

                <div style="height: 300px; position: relative;">
                    <div style="position: relative; width: 100%; min-width: 0; max-width: 100%; height: 280px;">
                        <canvas id="gasBarChartCanvas"></canvas>
                    </div>
                    <div id="noDataAlert" class="d-none position-absolute top-50 start-50 translate-middle text-center text-muted">
                        <i class="bi bi-folder-x fs-1 d-block mb-1"></i>
                        Tidak ada data pengukuran pada periode yang dipilih.
                    </div>
                </div>

                <!-- Legenda Status di Bawah Grafik -->
                <div id="chartLegendFooter" class="d-flex flex-wrap justify-content-center align-items-center gap-4 mt-3 pt-2 border-top small text-muted">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                        <span id="legendSesuai">Sesuai (&le; 25 ppm)</span>
                    </div>
                    <div id="legendWrapWarning" class="d-flex align-items-center gap-2">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                        <span>Mendekati Batas</span>
                    </div>
                    <div id="legendWrapTidakSesuai" class="d-flex align-items-center gap-2">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                        <span id="legendTidakSesuai">Tidak Sesuai (&gt; 25 ppm)</span>
                    </div>
                    <div id="legendWrapThreshold" class="d-flex align-items-center gap-2">
                        <span style="width: 20px; height: 2px; border-top: 2px dashed #ef4444; display: inline-block;"></span>
                        <span id="legendThreshold">Threshold (25 ppm)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===========================================================================
     5. FILTER GRAFIK SEMUA RIWAYAT (HANYA MUNCUL SAAT SECTION SEMUA RIWAYAT AKTIF)
     =========================================================================== -->
<div id="gasHistoryFilterWrapper" class="{{ ($activeSection ?? 'hari_ini') === 'semua_riwayat' ? '' : 'd-none' }}">
    <form method="GET" action="{{ route('gas-detector.index') }}" id="gasHistoryFilterForm" onsubmit="return applyHistoryChartFilter(event)">
        <input type="hidden" name="section" value="semua_riwayat">
        <div class="gas-filter-panel">
            <div class="gas-filter-grid">
                <!-- Pilihan Tipe Periode (Bulanan, Tahunan, Rentang Tanggal) -->
                <div class="filter-col-period-type">
                    <label class="filter-input-label">
                        <i class="bi bi-funnel text-success"></i> Tipe Periode
                    </label>
                    <select name="history_period_type" id="filterHistoryPeriodType" class="form-select form-select-sm" onchange="toggleHistoryPeriodMode(this.value)">
                        <option value="monthly" {{ ($historyFilter['period_type'] ?? 'monthly') === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        <option value="yearly" {{ ($historyFilter['period_type'] ?? 'monthly') === 'yearly' ? 'selected' : '' }}>Tahunan</option>
                        <option value="custom" {{ ($historyFilter['period_type'] ?? 'monthly') === 'custom' ? 'selected' : '' }}>Rentang Tanggal</option>
                    </select>
                </div>

                <!-- Input Dinamis Sesuai Tipe Periode -->
                <div id="gasHistoryPeriodInputWrapper" class="filter-col-period-input">
                    <!-- 1. Bulanan (Dropdown Bulan & Dropdown Tahun) -->
                    <div id="gasHistoryPeriod_monthly" class="gas-period-variant filter-dynamic-controls" style="{{ ($historyFilter['period_type'] ?? 'monthly') === 'monthly' ? 'display: flex;' : 'display: none;' }}">
                        <div class="filter-dynamic-subcol">
                            <label class="filter-input-label"><i class="bi bi-calendar-event text-success"></i> Bulan</label>
                            <select name="history_month" id="filterHistoryMonth" class="form-select form-select-sm">
                                @foreach($monthNames as $mNum => $mLabel)
                                    <option value="{{ $mNum }}" {{ (int)($historyFilter['month'] ?? date('n')) === (int)$mNum ? 'selected' : '' }}>{{ $mLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-dynamic-subcol">
                            <label class="filter-input-label">Tahun</label>
                            <select name="history_year" id="filterHistoryYear" class="form-select form-select-sm">
                                @foreach($availableYears as $yrOpt)
                                    <option value="{{ $yrOpt }}" {{ (int)($historyFilter['year'] ?? date('Y')) === (int)$yrOpt ? 'selected' : '' }}>{{ $yrOpt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 2. Tahunan (Dropdown Tahun) -->
                    <div id="gasHistoryPeriod_yearly" class="gas-period-variant" style="{{ ($historyFilter['period_type'] ?? 'monthly') === 'yearly' ? '' : 'display: none;' }}">
                        <label class="filter-input-label">
                            <i class="bi bi-calendar-event text-success"></i> Pilih Tahun
                        </label>
                        <select name="history_year_only" id="filterHistoryYearOnly" class="form-select form-select-sm">
                            @foreach($availableYears as $yrOpt)
                                <option value="{{ $yrOpt }}" {{ (int)($historyFilter['year'] ?? date('Y')) === (int)$yrOpt ? 'selected' : '' }}>{{ $yrOpt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Rentang Tanggal (Tanggal Mulai & Tanggal Akhir) -->
                    <div id="gasHistoryPeriod_custom" class="gas-period-variant" style="{{ ($historyFilter['period_type'] ?? 'monthly') === 'custom' ? '' : 'display: none;' }}">
                        <label class="filter-input-label">
                            <i class="bi bi-calendar-event text-success"></i> Rentang Tanggal
                        </label>
                        <div class="date-range-split">
                            <input type="date" name="history_start_date" id="filterHistoryStartDate" class="form-control form-control-sm" value="{{ $historyFilter['start_date'] ?? date('Y-m-01') }}" title="Tanggal Mulai">
                            <span class="date-separator">–</span>
                            <input type="date" name="history_end_date" id="filterHistoryEndDate" class="form-control form-control-sm" value="{{ $historyFilter['end_date'] ?? date('Y-m-d') }}" title="Tanggal Selesai">
                        </div>
                    </div>
                </div>

                <!-- Filter Well Pad (Hanya 1 Well Pad dalam satu waktu) -->
                <div class="filter-col-well-pad">
                    <label class="filter-input-label">
                        <i class="bi bi-geo-alt text-primary"></i> Well Pad
                    </label>
                    <select name="history_well_pad" id="filterHistoryWellPad" class="form-select form-select-sm">
                        @foreach($activeWellPads as $pad)
                            <option value="{{ $pad->name }}" {{ ($historyFilter['well_pad'] ?? '') === $pad->name ? 'selected' : '' }}>
                                {{ $pad->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tombol Terapkan & Reset -->
                <div class="filter-col-actions">
                    <label class="filter-input-label" style="visibility: hidden;">Aksi</label>
                    <div class="d-flex gap-2">
                        <button type="submit" id="btnApplyHistoryFilter" class="btn btn-sm btn-success px-3 fw-semibold d-inline-flex align-items-center gap-1" style="background-color: #059669; border: none; white-space: nowrap;">
                            <i class="bi bi-funnel-fill"></i> Terapkan Filter
                        </button>
                        <button type="button" id="btnResetHistoryFilter" onclick="resetHistoryChartFilter()" class="btn btn-sm btn-light border px-3 fw-semibold text-secondary d-inline-flex align-items-center gap-1" style="white-space: nowrap;">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- ===========================================================================
     6. TABEL DATA HASIL PENGUKURAN (SECTION HARI INI & SECTION SEMUA RIWAYAT)
     =========================================================================== -->
<!-- 6A. TABEL SECTION HARI INI -->
<div id="sectionTodayTable" class="gas-table-box {{ ($activeSection ?? 'hari_ini') === 'hari_ini' ? '' : 'd-none' }}">
    <div class="row align-items-center g-2 mb-3">
        <div class="col-md-6">
            <h5 class="fw-bold text-dark mb-0" style="font-size: 16px;">
                Data Gas Monitoring Hari Ini — {{ $todayDisplayLabel }}
            </h5>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                <form method="GET" action="{{ route('gas-detector.index') }}" class="input-group input-group-sm" style="max-width: 260px;">
                    <input type="hidden" name="section" value="hari_ini">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search_today" id="liveSearchInput" class="form-control border-start-0" placeholder="Cari well pad hari ini..." value="{{ $searchToday ?? '' }}">
                </form>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-gas align-middle" id="mainGasMonitoringTable">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">No</th>
                    <th style="width: 10%;">Tanggal</th>
                    <th style="width: 14%;">Well Pad</th>
                    <th class="text-center" style="width: 10%;">CO (ppm)</th>
                    <th class="text-center" style="width: 10%;">O₂ (%)</th>
                    <th class="text-center" style="width: 10%;">H₂S (ppm)</th>
                    <th class="text-center" style="width: 10%;">LEL (%)</th>
                    <th class="text-center" style="width: 11%;">Pressure (Psi)</th>
                    <th class="text-center" style="width: 10%;">Status</th>
                    <th class="text-center" style="width: 10%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($todayRecords as $index => $row)
                    @php
                        $coExceeded = ($row->co_level !== null && $row->co_level > 25.0);
                        $o2Exceeded = ($row->o2_level !== null && ($row->o2_level < 19.5 || $row->o2_level > 23.5));
                        $h2sExceeded = ($row->h2s_level !== null && $row->h2s_level > 10.0);
                        $lelExceeded = ($row->lel_level !== null && $row->lel_level > 5.0);
                        $isRowExceeded = $coExceeded || $o2Exceeded || $h2sExceeded || $lelExceeded;
                        $hasAnyMeasured = ($row->co_level !== null || $row->o2_level !== null || $row->h2s_level !== null || $row->lel_level !== null);
                    @endphp
                    <tr style="{{ $isRowExceeded ? 'background-color: #fef2f2;' : '' }}">
                        <td class="text-center text-muted fw-semibold">{{ $todayRecords->firstItem() + $index }}</td>
                        <td class="text-nowrap text-dark">{{ $row->measurement_date ? $row->measurement_date->format('d/m/Y') : '-' }}</td>
                        <td class="fw-bold text-dark">{{ $row->well_pad }}</td>
                        <td class="text-center fw-semibold {{ $coExceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->co_level !== null ? rtrim(rtrim(number_format($row->co_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center fw-semibold {{ $o2Exceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->o2_level !== null ? rtrim(rtrim(number_format($row->o2_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center fw-semibold {{ $h2sExceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->h2s_level !== null ? rtrim(rtrim(number_format($row->h2s_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center fw-semibold {{ $lelExceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->lel_level !== null ? rtrim(rtrim(number_format($row->lel_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center fw-semibold">{{ $row->pressure !== null ? rtrim(rtrim(number_format($row->pressure, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center">
                            @if($isRowExceeded)
                                <span class="badge-tidaksesuai">Tidak Sesuai</span>
                            @elseif($hasAnyMeasured)
                                <span class="badge-sesuai">Sesuai</span>
                            @else
                                <span class="badge bg-light text-muted border">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn-tbl-action btn-tbl-view" onclick="viewMeasurementDetail({{ $row->id }})" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @adminManagement
                                    <button type="button" class="btn-tbl-action btn-tbl-edit" onclick="editSingleMeasurement({{ $row->id }})" title="Edit Data">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn-tbl-action btn-tbl-del" onclick="deleteMeasurementRecord({{ $row->id }}, '{{ $row->well_pad }}')" title="Hapus Data">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endadminManagement
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                            Belum ada data pengukuran Gas Monitoring pada hari ini ({{ $todayDisplayLabel }}).
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($todayRecords->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
            <div class="small text-muted">
                Menampilkan {{ $todayRecords->firstItem() }} sampai {{ $todayRecords->lastItem() }} dari {{ $todayRecords->total() }} data
            </div>
            <div>
                {{ $todayRecords->appends(['section' => 'hari_ini'])->links() }}
            </div>
        </div>
    @endif
</div>

<!-- 6B. TABEL SECTION SEMUA RIWAYAT (TIDAK TERPENGARUH FILTER GRAFIK) -->
<div id="sectionHistoryTable" class="gas-table-box {{ ($activeSection ?? 'hari_ini') === 'semua_riwayat' ? '' : 'd-none' }}">
    <div class="row align-items-center g-2 mb-3">
        <div class="col-md-6">
            <h5 class="fw-bold text-dark mb-0" style="font-size: 16px;">
                Semua Riwayat Pengukuran Gas Monitoring
            </h5>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                <form method="GET" action="{{ route('gas-detector.index') }}" class="input-group input-group-sm" style="max-width: 260px;">
                    <input type="hidden" name="section" value="semua_riwayat">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search_history" id="liveSearchHistoryInput" class="form-control border-start-0" placeholder="Cari well pad / tanggal..." value="{{ $searchHistory ?? '' }}">
                </form>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-gas align-middle" id="historyGasMonitoringTable">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">No</th>
                    <th style="width: 10%;">Tanggal</th>
                    <th style="width: 14%;">Well Pad</th>
                    <th class="text-center" style="width: 10%;">CO (ppm)</th>
                    <th class="text-center" style="width: 10%;">O₂ (%)</th>
                    <th class="text-center" style="width: 10%;">H₂S (ppm)</th>
                    <th class="text-center" style="width: 10%;">LEL (%)</th>
                    <th class="text-center" style="width: 11%;">Pressure (Psi)</th>
                    <th class="text-center" style="width: 10%;">Status</th>
                    <th class="text-center" style="width: 10%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($historyTableRecords as $index => $row)
                    @php
                        $coExceeded = ($row->co_level !== null && $row->co_level > 25.0);
                        $o2Exceeded = ($row->o2_level !== null && ($row->o2_level < 19.5 || $row->o2_level > 23.5));
                        $h2sExceeded = ($row->h2s_level !== null && $row->h2s_level > 10.0);
                        $lelExceeded = ($row->lel_level !== null && $row->lel_level > 5.0);
                        $isRowExceeded = $coExceeded || $o2Exceeded || $h2sExceeded || $lelExceeded;
                        $hasAnyMeasured = ($row->co_level !== null || $row->o2_level !== null || $row->h2s_level !== null || $row->lel_level !== null);
                    @endphp
                    <tr style="{{ $isRowExceeded ? 'background-color: #fef2f2;' : '' }}">
                        <td class="text-center text-muted fw-semibold">{{ $historyTableRecords->firstItem() + $index }}</td>
                        <td class="text-nowrap text-dark">{{ $row->measurement_date ? $row->measurement_date->format('d/m/Y') : '-' }}</td>
                        <td class="fw-bold text-dark">{{ $row->well_pad }}</td>
                        <td class="text-center fw-semibold {{ $coExceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->co_level !== null ? rtrim(rtrim(number_format($row->co_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center fw-semibold {{ $o2Exceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->o2_level !== null ? rtrim(rtrim(number_format($row->o2_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center fw-semibold {{ $h2sExceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->h2s_level !== null ? rtrim(rtrim(number_format($row->h2s_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center fw-semibold {{ $lelExceeded ? 'text-danger bg-danger-subtle' : '' }}">{{ $row->lel_level !== null ? rtrim(rtrim(number_format($row->lel_level, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center fw-semibold">{{ $row->pressure !== null ? rtrim(rtrim(number_format($row->pressure, 1, '.', ','), '0'), '.') : '-' }}</td>
                        <td class="text-center">
                            @if($isRowExceeded)
                                <span class="badge-tidaksesuai">Tidak Sesuai</span>
                            @elseif($hasAnyMeasured)
                                <span class="badge-sesuai">Sesuai</span>
                            @else
                                <span class="badge bg-light text-muted border">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn-tbl-action btn-tbl-view" onclick="viewMeasurementDetail({{ $row->id }})" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @adminManagement
                                    <button type="button" class="btn-tbl-action btn-tbl-edit" onclick="editSingleMeasurement({{ $row->id }})" title="Edit Data">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn-tbl-action btn-tbl-del" onclick="deleteMeasurementRecord({{ $row->id }}, '{{ $row->well_pad }}')" title="Hapus Data">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endadminManagement
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                            Belum ada data riwayat Gas Monitoring.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($historyTableRecords->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
            <div class="small text-muted">
                Menampilkan {{ $historyTableRecords->firstItem() }} sampai {{ $historyTableRecords->lastItem() }} dari {{ $historyTableRecords->total() }} data
            </div>
            <div>
                {{ $historyTableRecords->appends(['section' => 'semua_riwayat'])->links() }}
            </div>
        </div>
    @endif
</div>


<!-- ===========================================================================
     MODAL 1: FORM TAMBAH DATA GAS MONITORING MASSAL (SESUAI GAMBAR 2)
     =========================================================================== -->
<div class="modal fade" id="modalBatchCreateGas" tabindex="-1" aria-labelledby="modalBatchCreateGasLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: #059669; color: #ffffff; padding: 16px 24px; border: none;">
                <div>
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2 mb-0" id="modalBatchCreateGasLabel" style="font-size: 16px;">
                        <i class="bi bi-plus-circle"></i> Tambah Data Gas Monitoring
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>

            <form id="formBatchCreateGas" onsubmit="submitBatchGasForm(event)">
                <div class="modal-body p-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
                    <!-- Row Input Tanggal -->
                    <div class="row g-3 mb-3 pb-3 border-bottom align-items-center">
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-dark small mb-1">Tanggal Pengukuran <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar3"></i></span>
                                <input type="date" name="measurement_date" id="batchMeasurementDate" class="form-control" value="{{ now()->setTimezone('Asia/Jakarta')->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    <!-- Judul Form Tabel -->
                    <h6 class="fw-bold text-dark mb-2" style="font-size: 13.5px;">Data Parameter Gas per Well Pad</h6>

                    <!-- Tabel Input Massal Well Pad Aktif + Keterangan per Parameter -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="tableBatchInputs" style="font-size: 12px;">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th style="width: 14%;" class="align-middle">Well Pad</th>
                                    <th style="width: 17.2%;">
                                        <div class="d-flex align-items-center justify-content-center gap-1 text-success fw-bold">
                                            <i class="bi bi-cloud"></i> CO
                                        </div>
                                        <span class="text-muted" style="font-size: 10px;">(ppm)<br>NAB: &le; 25 ppm</span>
                                    </th>
                                    <th style="width: 17.2%;">
                                        <div class="d-flex align-items-center justify-content-center gap-1 text-primary fw-bold">
                                            <span>O₂</span>
                                        </div>
                                        <span class="text-muted" style="font-size: 10px;">(%)<br>NAB: 19.5 - 23.5 %</span>
                                    </th>
                                    <th style="width: 17.2%;">
                                        <div class="d-flex align-items-center justify-content-center gap-1 text-danger fw-bold">
                                            <i class="bi bi-radioactive"></i> H₂S
                                        </div>
                                        <span class="text-muted" style="font-size: 10px;">(ppm)<br>NAB: &le; 10 ppm</span>
                                    </th>
                                    <th style="width: 17.2%;">
                                        <div class="d-flex align-items-center justify-content-center gap-1 text-warning fw-bold">
                                            <i class="bi bi-fire"></i> LEL
                                        </div>
                                        <span class="text-muted" style="font-size: 10px;">(%)<br>NAB: &le; 5 %</span>
                                    </th>
                                    <th style="width: 17.2%;">
                                        <div class="d-flex align-items-center justify-content-center gap-1 text-purple fw-bold" style="color: #9333ea;">
                                            <i class="bi bi-speedometer2"></i> Pressure
                                        </div>
                                        <span class="text-muted" style="font-size: 10px;">(Psi)</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeWellPads as $idx => $pad)
                                    <tr>
                                        <td class="fw-bold text-dark bg-light-subtle">
                                            <input type="hidden" name="measurements[{{ $idx }}][well_pad_id]" value="{{ $pad->id }}">
                                            <input type="hidden" name="measurements[{{ $idx }}][well_pad_name]" value="{{ $pad->name }}">
                                            {{ $pad->name }}
                                        </td>
                                        <td>
                                            <input type="number" step="0.1" min="0" name="measurements[{{ $idx }}][co_level]" class="form-control form-control-sm text-center mb-1" placeholder="Nilai (Contoh: 12)">
                                            <input type="text" name="measurements[{{ $idx }}][co_notes]" class="form-control form-control-sm" style="font-size: 11px;" placeholder="Keterangan CO...">
                                        </td>
                                        <td>
                                            <input type="number" step="0.1" min="0" max="100" name="measurements[{{ $idx }}][o2_level]" class="form-control form-control-sm text-center mb-1" placeholder="Nilai (Contoh: 20.8)">
                                            <input type="text" name="measurements[{{ $idx }}][o2_notes]" class="form-control form-control-sm" style="font-size: 11px;" placeholder="Keterangan O₂...">
                                        </td>
                                        <td>
                                            <input type="number" step="0.1" min="0" name="measurements[{{ $idx }}][h2s_level]" class="form-control form-control-sm text-center mb-1" placeholder="Nilai (Contoh: 8)">
                                            <input type="text" name="measurements[{{ $idx }}][h2s_notes]" class="form-control form-control-sm" style="font-size: 11px;" placeholder="Keterangan H₂S...">
                                        </td>
                                        <td>
                                            <input type="number" step="0.1" min="0" max="100" name="measurements[{{ $idx }}][lel_level]" class="form-control form-control-sm text-center mb-1" placeholder="Nilai (Contoh: 3)">
                                            <input type="text" name="measurements[{{ $idx }}][lel_notes]" class="form-control form-control-sm" style="font-size: 11px;" placeholder="Keterangan LEL...">
                                        </td>
                                        <td>
                                            <input type="number" step="0.1" min="0" name="measurements[{{ $idx }}][pressure]" class="form-control form-control-sm text-center mb-1" placeholder="Nilai (Contoh: 800)">
                                            <input type="text" name="measurements[{{ $idx }}][pressure_notes]" class="form-control form-control-sm" style="font-size: 11px;" placeholder="Keterangan Pressure...">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="resetBatchForm()">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Form
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border btn-sm px-3" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-success btn-sm px-4 fw-semibold d-inline-flex align-items-center gap-1" id="btnSubmitBatchGas" style="background-color: #059669; border: none;">
                            <i class="bi bi-file-earmark-check"></i> Simpan Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ===========================================================================
     MODAL 2: MANAJEMEN KELOLA WELL PAD (SESUAI GAMBAR 3)
     =========================================================================== -->
<div class="modal fade" id="modalWellPadManagement" tabindex="-1" aria-labelledby="modalWellPadManagementLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: #059669; color: #ffffff; padding: 16px 24px; border: none;">
                <div>
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="modalWellPadManagementLabel" style="font-size: 16px;">
                        <i class="bi bi-gear-fill"></i> Manajemen Well Pad
                    </h5>
                    <div class="small text-white-50" style="font-size: 11.5px;">
                        Kelola daftar Well Pad yang digunakan pada Gas Monitoring
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <div class="alert alert-info border-0 d-flex align-items-start gap-2 py-2 px-3 mb-3" style="background-color: #eff6ff; color: #1e40af; border-radius: 10px;">
                    <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
                    <div style="font-size: 11.5px;">
                        <strong>Well Pad yang ditambahkan akan muncul pada form input Gas Monitoring, filter, grafik, dan rekapitulasi.</strong><br>
                        Jangan menghapus Well Pad yang sudah memiliki data riwayat. Gunakan status nonaktif jika Well Pad tidak digunakan lagi.
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <div class="input-group input-group-sm" style="max-width: 280px;">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchWellPadInput" class="form-control border-start-0" placeholder="Cari nama well pad..." oninput="filterWellPadList()">
                    </div>
                    <button type="button" class="btn btn-sm btn-success fw-semibold d-inline-flex align-items-center gap-1" onclick="openAddWellPadForm()" style="background-color: #059669; border: none;">
                        <i class="bi bi-plus-lg"></i> Tambah Well Pad
                    </button>
                </div>

                <div class="collapse mb-3" id="collapseWellPadForm">
                    <div class="card card-body bg-light border p-3" style="border-radius: 10px;">
                        <form id="formWellPadAction" onsubmit="submitWellPadForm(event)">
                            <input type="hidden" id="wpFormId" value="">
                            <h6 class="fw-bold text-dark mb-2" id="wpFormTitle" style="font-size: 13px;">Tambah Well Pad</h6>
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label class="form-label small mb-1 fw-semibold">Nama Well Pad <span class="text-danger">*</span></label>
                                    <input type="text" id="wpFormName" class="form-control form-control-sm" placeholder="Contoh: Well Pad 11" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1 fw-semibold">Kode Well Pad <span class="text-danger">*</span></label>
                                    <input type="text" id="wpFormCode" class="form-control form-control-sm" placeholder="Contoh: WP-11" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1 fw-semibold">Status</label>
                                    <select id="wpFormStatus" class="form-select form-select-sm">
                                        <option value="1">Aktif</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-sm btn-light border" onclick="closeWellPadForm()">Batal</button>
                                <button type="submit" class="btn btn-sm btn-success px-3 fw-semibold" id="btnSaveWp">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="tableWellPads">
                        <thead class="bg-light text-center" style="font-size: 11.5px; color: #475569;">
                            <tr>
                                <th style="width: 45px;">No</th>
                                <th>Nama Well Pad</th>
                                <th>Kode Well Pad</th>
                                <th style="width: 90px;">Status</th>
                                <th style="width: 100px;">Jumlah Data</th>
                                <th style="width: 130px;">Tanggal Input Terakhir</th>
                                <th style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyWellPads" style="font-size: 12px;">
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top gap-2 small text-muted">
                    <div class="d-flex align-items-center gap-2">
                        <span id="wpTableCounter">Menampilkan 0 dari 0 Well Pad</span>
                        <div class="d-inline-flex align-items-center gap-1 ms-2">
                            <span>Per halaman:</span>
                            <select id="wpPerPageSelect" class="form-select form-select-sm py-0 px-2" style="width: auto; height: 26px; font-size: 11px;" onchange="changeWpPerPage(this.value)">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="all">Semua</option>
                            </select>
                        </div>
                    </div>
                    <nav aria-label="Well Pad Table Navigation" id="wpPaginationNav">
                        <ul class="pagination pagination-sm mb-0" id="wpPaginationList">
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ===========================================================================
     MODAL 3: DETAIL SINGLE MEASUREMENT (PREVIEW HISTORY)
     =========================================================================== -->
<div class="modal fade" id="modalSingleDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold text-dark mb-0">Detail Pengukuran Gas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <table class="table table-sm table-borderless small mb-2">
                    <tr><td class="text-muted" style="width: 135px;">Tanggal</td><td class="fw-bold" id="dtlDate">-</td></tr>
                    <tr><td class="text-muted">Well Pad</td><td class="fw-bold text-dark" id="dtlWellPad">-</td></tr>
                    <tr><td class="text-muted">CO (ppm)</td><td id="dtlCo">-</td></tr>
                    <tr><td class="text-muted">O₂ (%)</td><td id="dtlO2">-</td></tr>
                    <tr><td class="text-muted">H₂S (ppm)</td><td id="dtlH2s">-</td></tr>
                    <tr><td class="text-muted">LEL (%)</td><td id="dtlLel">-</td></tr>
                    <tr><td class="text-muted">Pressure (Psi)</td><td id="dtlPress">-</td></tr>
                    <tr><td class="text-muted">Status</td><td id="dtlStatus">-</td></tr>
                    <tr><td class="text-muted">Petugas Input</td><td id="dtlOfficer">-</td></tr>
                </table>

                <!-- Bagian Keterangan Parameter di Paling Bawah Preview -->
                <div class="p-2.5 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 12px;">
                    <div class="fw-bold text-dark mb-1" style="font-size: 11.5px;">
                        <i class="bi bi-card-text text-success me-1"></i> Keterangan Parameter Gas
                    </div>
                    <div id="dtlParameterNotesBox" class="small text-secondary" style="font-size: 12px; line-height: 1.5;">
                        -
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ===========================================================================
     MODAL 4: EDIT SINGLE MEASUREMENT
     =========================================================================== -->
<div class="modal fade" id="modalSingleEdit" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: #059669; color: #ffffff;">
                <h6 class="modal-title fw-bold text-white mb-0">Edit Data Pengukuran Gas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            <form id="formSingleEdit" onsubmit="submitSingleEdit(event)">
                <input type="hidden" id="editMeasurementId" value="">
                <div class="modal-body p-3">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Tanggal Pengukuran</label>
                            <input type="date" id="editDate" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1">Well Pad</label>
                            <input type="text" id="editWellPad" class="form-control form-control-sm bg-light" readonly>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-5">
                            <label class="form-label small fw-semibold mb-1">CO (ppm)</label>
                            <input type="number" step="0.1" min="0" id="editCo" class="form-control form-control-sm">
                        </div>
                        <div class="col-7">
                            <label class="form-label small fw-semibold mb-1">Keterangan CO</label>
                            <input type="text" id="editCoNotes" class="form-control form-control-sm" placeholder="Keterangan CO...">
                        </div>

                        <div class="col-5">
                            <label class="form-label small fw-semibold mb-1">O₂ (%)</label>
                            <input type="number" step="0.1" min="0" max="100" id="editO2" class="form-control form-control-sm">
                        </div>
                        <div class="col-7">
                            <label class="form-label small fw-semibold mb-1">Keterangan O₂</label>
                            <input type="text" id="editO2Notes" class="form-control form-control-sm" placeholder="Keterangan O₂...">
                        </div>

                        <div class="col-5">
                            <label class="form-label small fw-semibold mb-1">H₂S (ppm)</label>
                            <input type="number" step="0.1" min="0" id="editH2s" class="form-control form-control-sm">
                        </div>
                        <div class="col-7">
                            <label class="form-label small fw-semibold mb-1">Keterangan H₂S</label>
                            <input type="text" id="editH2sNotes" class="form-control form-control-sm" placeholder="Keterangan H₂S...">
                        </div>

                        <div class="col-5">
                            <label class="form-label small fw-semibold mb-1">LEL (%)</label>
                            <input type="number" step="0.1" min="0" max="100" id="editLel" class="form-control form-control-sm">
                        </div>
                        <div class="col-7">
                            <label class="form-label small fw-semibold mb-1">Keterangan LEL</label>
                            <input type="text" id="editLelNotes" class="form-control form-control-sm" placeholder="Keterangan LEL...">
                        </div>

                        <div class="col-5">
                            <label class="form-label small fw-semibold mb-1">Pressure (Psi)</label>
                            <input type="number" step="0.1" min="0" id="editPressure" class="form-control form-control-sm">
                        </div>
                        <div class="col-7">
                            <label class="form-label small fw-semibold mb-1">Keterangan Pressure</label>
                            <input type="text" id="editPressureNotes" class="form-control form-control-sm" placeholder="Keterangan Pressure...">
                        </div>
                    </div>
                    <input type="hidden" id="editNotes" value="">
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold px-3" style="background-color: #059669; border: none;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const todayChartRawData = @json($chartData);
const todayStatsRawData = @json($statisticsData);
const todayDisplayLabel = "{{ $todayDisplayLabel }}";

let historyChartRawData = @json($historyChartData);
let historyStatsRawData = @json($historyStatisticsData);
let historyPeriodLabel = "{{ $historyPeriodLabel }}";
let historyWellPadName = "{{ $historyFilter['well_pad'] ?? '' }}";
const defaultHistoryMonth = "{{ (int) date('n') }}";
const defaultHistoryYear = "{{ (int) date('Y') }}";
const defaultHistoryStartDate = "{{ date('Y-m-01') }}";
const defaultHistoryEndDate = "{{ date('Y-m-d') }}";
const defaultHistoryWellPad = "{{ $activeWellPads->first()?->name ?? '' }}";

let activeSection = "{{ $activeSection ?? 'hari_ini' }}";
let activeParamKey = 'co';
let gasBarChart = null;
let wellPadsCache = [];

function getActiveChartRawData() {
    return activeSection === 'semua_riwayat' ? historyChartRawData : todayChartRawData;
}

function getActiveStatsRawData() {
    return activeSection === 'semua_riwayat' ? historyStatsRawData : todayStatsRawData;
}

// Switch antara Section Hari Ini & Semua Riwayat
function switchGasSection(section) {
    if (section !== 'hari_ini' && section !== 'semua_riwayat') {
        section = 'hari_ini';
    }
    activeSection = section;

    const btnToday = document.getElementById('btnSectionHariIni');
    const btnHistory = document.getElementById('btnSectionSemuaRiwayat');
    const filterWrap = document.getElementById('gasHistoryFilterWrapper');
    const todayTable = document.getElementById('sectionTodayTable');
    const historyTable = document.getElementById('sectionHistoryTable');
    const hintEl = document.getElementById('sectionModeHint');

    if (btnToday && btnHistory) {
        if (section === 'hari_ini') {
            btnToday.className = 'btn btn-sm px-3 fw-semibold rounded-2 btn-success text-white';
            btnToday.style.backgroundColor = '#059669';
            btnToday.style.borderColor = '#059669';
            btnHistory.className = 'btn btn-sm px-3 fw-semibold rounded-2 btn-light text-secondary border-0';
            btnHistory.style.backgroundColor = '';
            btnHistory.style.borderColor = '';
        } else {
            btnHistory.className = 'btn btn-sm px-3 fw-semibold rounded-2 btn-success text-white';
            btnHistory.style.backgroundColor = '#059669';
            btnHistory.style.borderColor = '#059669';
            btnToday.className = 'btn btn-sm px-3 fw-semibold rounded-2 btn-light text-secondary border-0';
            btnToday.style.backgroundColor = '';
            btnToday.style.borderColor = '';
        }
    }

    if (filterWrap) filterWrap.classList.toggle('d-none', section !== 'semua_riwayat');
    if (todayTable) todayTable.classList.toggle('d-none', section !== 'hari_ini');
    if (historyTable) historyTable.classList.toggle('d-none', section !== 'semua_riwayat');

    if (hintEl) {
        hintEl.innerHTML = section === 'semua_riwayat'
            ? '<i class="bi bi-graph-up me-1 text-success"></i> Analisis riwayat per Well Pad &amp; seluruh catatan pengukuran'
            : `<i class="bi bi-activity me-1 text-success"></i> Pemantauan pengukuran hari ini (${todayDisplayLabel})`;
    }

    initGasBarChart(activeParamKey);
}

// Toggler Input Mode Periode pada Section Semua Riwayat (Bulanan, Tahunan, Rentang Tanggal)
function toggleHistoryPeriodMode(mode) {
    const variants = ['monthly', 'yearly', 'custom'];
    variants.forEach(v => {
        const el = document.getElementById(`gasHistoryPeriod_${v}`);
        if (!el) return;
        if (v === mode) {
            el.style.display = (v === 'monthly') ? 'flex' : '';
        } else {
            el.style.display = 'none';
        }
    });
}

// Terapkan Filter Grafik Semua Riwayat via AJAX tanpa mengubah 5 kartu & tabel
async function applyHistoryChartFilter(event) {
    if (event) event.preventDefault();

    const periodType = document.getElementById('filterHistoryPeriodType')?.value || 'monthly';
    const wellPad = document.getElementById('filterHistoryWellPad')?.value || defaultHistoryWellPad;
    const month = document.getElementById('filterHistoryMonth')?.value || defaultHistoryMonth;
    const year = periodType === 'yearly'
        ? (document.getElementById('filterHistoryYearOnly')?.value || defaultHistoryYear)
        : (document.getElementById('filterHistoryYear')?.value || defaultHistoryYear);
    const startDate = document.getElementById('filterHistoryStartDate')?.value || '';
    const endDate = document.getElementById('filterHistoryEndDate')?.value || '';

    if (periodType === 'custom') {
        if (!startDate || !endDate) {
            Swal.fire('Validasi Tanggal', 'Harap lengkapi Tanggal Mulai dan Tanggal Akhir.', 'warning');
            return false;
        }
        if (startDate > endDate) {
            Swal.fire('Rentang Tidak Valid', 'Tanggal Mulai tidak boleh lebih besar daripada Tanggal Akhir.', 'error');
            return false;
        }
    }

    const params = new URLSearchParams({
        history_period_type: periodType,
        history_well_pad: wellPad,
        history_month: month,
        history_year: year,
        history_start_date: startDate,
        history_end_date: endDate,
    });

    const btn = document.getElementById('btnApplyHistoryFilter');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memuat...';
    }

    try {
        const res = await fetch(`{{ route('gas-detector.history-chart') }}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            Swal.fire('Validasi Filter', data.message || 'Gagal memuat grafik riwayat.', 'warning');
            return false;
        }

        historyChartRawData = data.chartData;
        historyStatsRawData = data.statisticsData;
        historyPeriodLabel = data.periodLabel;
        historyWellPadName = data.wellPadName;

        initGasBarChart(activeParamKey);
    } catch (err) {
        console.error('Gagal memuat grafik riwayat:', err);
        Swal.fire('Error', 'Terjadi kesalahan saat memuat data grafik riwayat.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
    return false;
}

function resetHistoryChartFilter() {
    const typeEl = document.getElementById('filterHistoryPeriodType');
    const monthEl = document.getElementById('filterHistoryMonth');
    const yearEl = document.getElementById('filterHistoryYear');
    const yearOnlyEl = document.getElementById('filterHistoryYearOnly');
    const startEl = document.getElementById('filterHistoryStartDate');
    const endEl = document.getElementById('filterHistoryEndDate');
    const wpEl = document.getElementById('filterHistoryWellPad');

    if (typeEl) typeEl.value = 'monthly';
    if (monthEl) monthEl.value = defaultHistoryMonth;
    if (yearEl) yearEl.value = defaultHistoryYear;
    if (yearOnlyEl) yearOnlyEl.value = defaultHistoryYear;
    if (startEl) startEl.value = defaultHistoryStartDate;
    if (endEl) endEl.value = defaultHistoryEndDate;
    if (wpEl && defaultHistoryWellPad) wpEl.value = defaultHistoryWellPad;

    toggleHistoryPeriodMode('monthly');
    applyHistoryChartFilter();
}

// Threshold Line Plugin untuk Chart.js
const horizontalThresholdPlugin = {
    id: 'horizontalThreshold',
    afterDraw(chart) {
        const activeKey = chart.config.options.plugins.activeParamKey;
        if (!activeKey) return;

        const ctx = chart.ctx;
        const yAxis = chart.scales.y;
        const xAxis = chart.scales.x;

        if (activeKey === 'o2') {
            [19.5, 23.5].forEach(val => {
                const yPos = yAxis.getPixelForValue(val);
                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([6, 6]);
                ctx.strokeStyle = '#ef4444';
                ctx.lineWidth = 1.5;
                ctx.moveTo(xAxis.left, yPos);
                ctx.lineTo(xAxis.right, yPos);
                ctx.stroke();
                ctx.restore();
            });
        } else if (activeKey !== 'pressure') {
            const threshMap = { 'co': { val: 25, label: 'Threshold 25 ppm' }, 'h2s': { val: 10, label: 'Threshold 10 ppm' }, 'lel': { val: 5, label: 'Threshold 5 %' } };
            const threshObj = threshMap[activeKey];
            if (threshObj !== undefined) {
                const yPos = yAxis.getPixelForValue(threshObj.val);
                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([6, 6]);
                ctx.strokeStyle = '#ef4444';
                ctx.lineWidth = 1.5;
                ctx.moveTo(xAxis.left, yPos);
                ctx.lineTo(xAxis.right, yPos);
                ctx.stroke();
                
                ctx.fillStyle = '#dc2626';
                ctx.font = 'bold 11px sans-serif';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'bottom';
                ctx.fillText(threshObj.label, xAxis.right, yPos - 4);
                ctx.restore();
            }
        }

        // Gambar Nilai di Atas Setiap Batang / Titik
        chart.data.datasets.forEach((dataset, i) => {
            const meta = chart.getDatasetMeta(i);
            meta.data.forEach((point, index) => {
                const val = dataset.data[index];
                if (val !== null && val !== undefined && !isNaN(val)) {
                    ctx.save();
                    ctx.font = 'bold 11.5px sans-serif';
                    ctx.fillStyle = '#1e293b';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(val, point.x, point.y - 6);
                    ctx.restore();
                }
            });
        });
    }
};

Chart.register(horizontalThresholdPlugin);

document.addEventListener('DOMContentLoaded', function() {
    initGasBarChart('co');
    initLiveSearch();
});

// Inisialisasi Grafik (Bar untuk Hari Ini, Line dengan jeda null untuk Semua Riwayat)
function initGasBarChart(paramKey) {
    activeParamKey = paramKey;
    const canvas = document.getElementById('gasBarChartCanvas');
    if (!canvas) return;

    const currentChartData = getActiveChartRawData();
    const isHistoryMode = (activeSection === 'semua_riwayat');
    const dataObj = currentChartData[paramKey] || { data: [], colors: [], dates: [] };
    const labels = currentChartData.labels || [];
    const noDataAlert = document.getElementById('noDataAlert');

    const numericValues = (dataObj.data || []).filter(v => v !== null && v !== undefined && !isNaN(v));
    if (labels.length === 0 || numericValues.length === 0) {
        if (noDataAlert) noDataAlert.classList.remove('d-none');
    } else {
        if (noDataAlert) noDataAlert.classList.add('d-none');
    }

    let maxVal = numericValues.length > 0 ? Math.max(...numericValues, 10) : 10;
    if (paramKey === 'co') maxVal = Math.max(maxVal, 30);
    if (paramKey === 'o2') maxVal = 25;
    if (paramKey === 'h2s') maxVal = Math.max(maxVal, 15);
    if (paramKey === 'lel') maxVal = Math.max(maxVal, 8);
    if (paramKey === 'pressure') maxVal = Math.max(maxVal, 25);

    if (gasBarChart) {
        gasBarChart.destroy();
    }

    const isGasMobile = window.innerWidth < 576;
    const ctx = canvas.getContext('2d');

    const datasetConfig = isHistoryMode
        ? {
            data: dataObj.data,
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.08)',
            borderWidth: 2.5,
            tension: 0.2,
            fill: false,
            spanGaps: false,
            pointRadius: 5,
            pointHoverRadius: 7,
            pointBackgroundColor: dataObj.colors,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 1.5,
        }
        : {
            data: dataObj.data,
            backgroundColor: dataObj.colors,
            borderRadius: 4,
            barPercentage: 0.65,
            categoryPercentage: 0.72,
            maxBarThickness: 48,
        };

    gasBarChart = new Chart(ctx, {
        type: isHistoryMode ? 'line' : 'bar',
        data: {
            labels: labels,
            datasets: [datasetConfig]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 25,
                    right: isGasMobile ? 8 : 15,
                    left: 5,
                    bottom: 0
                }
            },
            plugins: {
                legend: { display: false },
                activeParamKey: paramKey,
                tooltip: {
                    callbacks: {
                        title: function(items) {
                            if (!items || !items.length) return '';
                            const idx = items[0].dataIndex;
                            if (isHistoryMode) {
                                const dtLabel = (dataObj.dates && dataObj.dates[idx]) ? dataObj.dates[idx] : items[0].label;
                                return `${historyWellPadName || currentChartData.well_pad || ''} (${items[0].label}) — ${dtLabel}`;
                            }
                            return items[0].label;
                        },
                        label: function(context) {
                            if (context.raw === null || context.raw === undefined) {
                                return ' Tidak ada data pengukuran';
                            }
                            const prefix = isHistoryMode
                                ? (currentChartData.period_type === 'yearly' ? 'Nilai Terburuk: ' : 'Pengukuran: ')
                                : 'Nilai: ';
                            return ` ${prefix}${context.raw} ${dataObj.unit || ''}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: maxVal,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { size: isGasMobile ? 10 : 11, family: 'sans-serif' },
                        color: '#64748b'
                    },
                    title: {
                        display: !isGasMobile,
                        text: `${paramKey === 'pressure' ? 'Pressure' : paramKey.toUpperCase()} (${dataObj.unit || ''})`,
                        font: { size: 11, weight: 'bold' },
                        color: '#475569'
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        autoSkip: isGasMobile,
                        maxTicksLimit: isGasMobile ? 6 : undefined,
                        maxRotation: isGasMobile ? 30 : 0,
                        font: { size: isGasMobile ? 10 : 11, weight: 'bold' },
                        color: '#1e293b'
                    }
                }
            }
        }
    });

    updateChartLabelsAndSidebar(paramKey);
}

let gasResizeTimer = null;
window.addEventListener('resize', function () {
    clearTimeout(gasResizeTimer);
    gasResizeTimer = setTimeout(function () {
        if (!gasBarChart) return;
        const isMobile = window.innerWidth < 576;
        gasBarChart.options.scales.y.title.display = !isMobile;
        gasBarChart.options.scales.y.ticks.font.size = isMobile ? 10 : 11;
        gasBarChart.options.scales.x.ticks.autoSkip = isMobile;
        gasBarChart.options.scales.x.ticks.maxTicksLimit = isMobile ? 6 : undefined;
        gasBarChart.options.scales.x.ticks.maxRotation = isMobile ? 30 : 0;
        gasBarChart.options.scales.x.ticks.font.size = isMobile ? 10 : 11.5;
        gasBarChart.update('none');
    }, 150);
});

function switchChartParam(paramKey) {
    document.querySelectorAll('.chart-tab-pill').forEach(btn => btn.classList.remove('active'));
    const btnMap = {
        'co': 'btnTabCo',
        'o2': 'btnTabO2',
        'h2s': 'btnTabH2s',
        'lel': 'btnTabLel',
        'pressure': 'btnTabPressure',
    };
    if (btnMap[paramKey]) {
        document.getElementById(btnMap[paramKey]).classList.add('active');
    }

    initGasBarChart(paramKey);
}

function updateChartLabelsAndSidebar(paramKey) {
    const isHistoryMode = (activeSection === 'semua_riwayat');
    const titlePeriod = isHistoryMode
        ? `${historyWellPadName || 'Well Pad'} — ${historyPeriodLabel}`
        : `per Well Pad — ${todayDisplayLabel}`;
    const headerTitle = document.getElementById('chartHeaderTitle');
    const threshBadge = document.getElementById('chartThresholdBadge');

    const legSesuai = document.getElementById('legendSesuai');
    const legTidakSesuai = document.getElementById('legendTidakSesuai');
    const legThreshold = document.getElementById('legendThreshold');
    const wrapWarning = document.getElementById('legendWrapWarning');
    const wrapTidakSesuai = document.getElementById('legendWrapTidakSesuai');
    const wrapThreshold = document.getElementById('legendWrapThreshold');

    // Update kotak sederetan tab: Rata-rata & Tertinggi (Khusus O2: Terendah)
    const activeStats = getActiveStatsRawData();
    const s = activeStats[paramKey] || {};
    const unitMap = { 'co': 'ppm', 'o2': '%', 'h2s': 'ppm', 'lel': '%', 'pressure': 'Psi' };
    const uStr = unitMap[paramKey] || '';

    const tabAvgEl = document.getElementById('barTabAvgVal');
    const tabExtLabelEl = document.getElementById('barTabExtremeLabel');
    const tabExtValEl = document.getElementById('barTabExtremeVal');
    const tabExtPadEl = document.getElementById('barTabExtremePad');

    if (tabAvgEl) {
        tabAvgEl.textContent = (s.avg && s.avg !== '-') ? `${s.avg} ${uStr}` : '-';
    }

    if (paramKey === 'o2') {
        if (tabExtLabelEl) tabExtLabelEl.textContent = 'Terendah:';
        if (tabExtValEl) tabExtValEl.textContent = (s.min_val && s.min_val !== '-') ? `${s.min_val} ${uStr}` : '-';
        if (tabExtPadEl) {
            if (s.min_pad && s.min_pad !== '-') {
                tabExtPadEl.textContent = s.min_pad;
                tabExtPadEl.classList.remove('d-none');
            } else {
                tabExtPadEl.textContent = '';
                tabExtPadEl.classList.add('d-none');
            }
        }
    } else {
        if (tabExtLabelEl) tabExtLabelEl.textContent = 'Tertinggi:';
        if (tabExtValEl) tabExtValEl.textContent = (s.max_val && s.max_val !== '-') ? `${s.max_val} ${uStr}` : '-';
        if (tabExtPadEl) {
            if (s.max_pad && s.max_pad !== '-') {
                tabExtPadEl.textContent = s.max_pad;
                tabExtPadEl.classList.remove('d-none');
            } else {
                tabExtPadEl.textContent = '';
                tabExtPadEl.classList.add('d-none');
            }
        }
    }

    if (paramKey === 'pressure') {
        if (headerTitle) headerTitle.textContent = `Grafik Pressure ${titlePeriod}`;
        if (threshBadge) threshBadge.textContent = '';
        if (legSesuai) legSesuai.textContent = 'Nilai Pressure (Psi)';
        if (wrapWarning) wrapWarning.classList.add('d-none');
        if (wrapTidakSesuai) wrapTidakSesuai.classList.add('d-none');
        if (wrapThreshold) wrapThreshold.classList.add('d-none');
        return;
    }

    if (wrapWarning) wrapWarning.classList.remove('d-none');
    if (wrapTidakSesuai) wrapTidakSesuai.classList.remove('d-none');
    if (wrapThreshold) wrapThreshold.classList.remove('d-none');

    if (paramKey === 'co') {
        if (headerTitle) headerTitle.textContent = `Grafik CO ${titlePeriod}`;
        if (threshBadge) threshBadge.textContent = 'Threshold 25 ppm';
        if (legSesuai) legSesuai.textContent = 'Sesuai (≤ 25 ppm)';
        if (legTidakSesuai) legTidakSesuai.textContent = 'Tidak Sesuai (> 25 ppm)';
        if (legThreshold) legThreshold.textContent = 'Threshold (25 ppm)';
    } else if (paramKey === 'o2') {
        if (headerTitle) headerTitle.textContent = `Grafik O₂ ${titlePeriod}`;
        if (threshBadge) threshBadge.textContent = 'Threshold 19.5 – 23.5 %';
        if (legSesuai) legSesuai.textContent = 'Sesuai (19.5 - 23.5 %)';
        if (legTidakSesuai) legTidakSesuai.textContent = 'Tidak Sesuai (< 19.5 atau > 23.5 %)';
        if (legThreshold) legThreshold.textContent = 'Threshold (19.5 – 23.5 %)';
    } else if (paramKey === 'h2s') {
        if (headerTitle) headerTitle.textContent = `Grafik H₂S ${titlePeriod}`;
        if (threshBadge) threshBadge.textContent = 'Threshold 10 ppm';
        if (legSesuai) legSesuai.textContent = 'Sesuai (≤ 10 ppm)';
        if (legTidakSesuai) legTidakSesuai.textContent = 'Tidak Sesuai (> 10 ppm)';
        if (legThreshold) legThreshold.textContent = 'Threshold (10 ppm)';
    } else if (paramKey === 'lel') {
        if (headerTitle) headerTitle.textContent = `Grafik LEL ${titlePeriod}`;
        if (threshBadge) threshBadge.textContent = 'Threshold 5 %';
        if (legSesuai) legSesuai.textContent = 'Sesuai (≤ 5 %)';
        if (legTidakSesuai) legTidakSesuai.textContent = 'Tidak Sesuai (> 5 %)';
        if (legThreshold) legThreshold.textContent = 'Threshold (5 %)';
    }
}

function initLiveSearch() {
    const bindTableSearch = (inputId, tableSelector) => {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('keyup', function() {
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll(`${tableSelector} tbody tr`);
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    };

    bindTableSearch('liveSearchInput', '#mainGasMonitoringTable');
    bindTableSearch('liveSearchHistoryInput', '#historyGasMonitoringTable');
}

function resetBatchForm() {
    const form = document.getElementById('formBatchCreateGas');
    if (form) form.reset();
}

async function submitBatchGasForm(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitBatchGas');
    btn.disabled = true;

    const form = document.getElementById('formBatchCreateGas');
    const formData = new FormData(form);

    const payload = {
        measurement_date: formData.get('measurement_date'),
        measurements: []
    };

    const tableRows = document.querySelectorAll('#tableBatchInputs tbody tr');
    tableRows.forEach((tr, i) => {
        const wpId = tr.querySelector(`input[name="measurements[${i}][well_pad_id]"]`)?.value;
        const wpName = tr.querySelector(`input[name="measurements[${i}][well_pad_name]"]`)?.value;
        const co = tr.querySelector(`input[name="measurements[${i}][co_level]"]`)?.value;
        const o2 = tr.querySelector(`input[name="measurements[${i}][o2_level]"]`)?.value;
        const h2s = tr.querySelector(`input[name="measurements[${i}][h2s_level]"]`)?.value;
        const lel = tr.querySelector(`input[name="measurements[${i}][lel_level]"]`)?.value;
        const press = tr.querySelector(`input[name="measurements[${i}][pressure]"]`)?.value;
        const coNotes = tr.querySelector(`input[name="measurements[${i}][co_notes]"]`)?.value;
        const o2Notes = tr.querySelector(`input[name="measurements[${i}][o2_notes]"]`)?.value;
        const h2sNotes = tr.querySelector(`input[name="measurements[${i}][h2s_notes]"]`)?.value;
        const lelNotes = tr.querySelector(`input[name="measurements[${i}][lel_notes]"]`)?.value;
        const pressNotes = tr.querySelector(`input[name="measurements[${i}][pressure_notes]"]`)?.value;

        payload.measurements.push({
            well_pad_id: wpId,
            well_pad_name: wpName,
            co_level: co !== '' ? co : null,
            o2_level: o2 !== '' ? o2 : null,
            h2s_level: h2s !== '' ? h2s : null,
            lel_level: lel !== '' ? lel : null,
            pressure: press !== '' ? press : null,
            co_notes: coNotes || null,
            o2_notes: o2Notes || null,
            h2s_notes: h2sNotes || null,
            lel_notes: lelNotes || null,
            pressure_notes: pressNotes || null,
        });
    });

    try {
        const res = await fetch("{{ route('gas-detector.measurements.store-batch') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const body = await res.json();
        btn.disabled = false;

        if (res.ok && body.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalBatchCreateGas')).hide();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: body.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = `{{ route('gas-detector.index') }}?start_date=${body.startDate}&end_date=${body.endDate}`;
            });
        } else {
            let msg = body.message || 'Gagal menyimpan data.';
            if (body.errors) msg = Object.values(body.errors).flat().join('<br>');
            Swal.fire({ icon: 'error', title: 'Penyimpanan Gagal', html: msg });
        }
    } catch (err) {
        btn.disabled = false;
        Swal.fire('Error', 'Terjadi kesalahan sistem saat menyimpan data.', 'error');
    }
}

let wpFilteredList = [];
let wpCurrentPage = 1;
let wpPerPage = 10;

function changeWpPerPage(val) {
    wpPerPage = val === 'all' ? 'all' : parseInt(val, 10);
    wpCurrentPage = 1;
    renderWellPadsTable();
}

function goToWpPage(page) {
    wpCurrentPage = page;
    renderWellPadsTable();
}

async function openWellPadModal() {
    await loadWellPads();
    const modalEl = document.getElementById('modalWellPadManagement');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

async function loadWellPads() {
    try {
        const res = await fetch("{{ route('gas-detector.well-pads.data') }}", {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (json.success) {
            wellPadsCache = (json.data || []).sort((a, b) => 
                (a.name || '').localeCompare(b.name || '', undefined, { numeric: true, sensitivity: 'base' })
            );
            filterWellPadList();
        }
    } catch (err) {
        console.error('Failed to load well pads:', err);
    }
}

function filterWellPadList() {
    const term = (document.getElementById('searchWellPadInput')?.value || '').toLowerCase().trim();
    if (!term) {
        wpFilteredList = [...wellPadsCache];
    } else {
        wpFilteredList = wellPadsCache.filter(wp => 
            (wp.name && wp.name.toLowerCase().includes(term)) || 
            (wp.code && wp.code.toLowerCase().includes(term))
        );
    }
    wpCurrentPage = 1;
    renderWellPadsTable();
}

function renderWellPadsTable() {
    const tbody = document.getElementById('tbodyWellPads');
    if (!tbody) return;
    tbody.innerHTML = '';

    const total = wpFilteredList.length;
    if (total === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted">Tidak ada Well Pad yang cocok.</td></tr>';
        const counterEl = document.getElementById('wpTableCounter');
        if (counterEl) counterEl.textContent = 'Menampilkan 0 dari 0 Well Pad';
        const nav = document.getElementById('wpPaginationList');
        if (nav) nav.innerHTML = '';
        return;
    }

    const perPageNum = wpPerPage === 'all' ? total : wpPerPage;
    const totalPages = Math.max(1, Math.ceil(total / perPageNum));
    if (wpCurrentPage > totalPages) wpCurrentPage = totalPages;
    if (wpCurrentPage < 1) wpCurrentPage = 1;

    const startIndex = wpPerPage === 'all' ? 0 : (wpCurrentPage - 1) * perPageNum;
    const endIndex = Math.min(startIndex + perPageNum, total);
    const displayedPads = wpFilteredList.slice(startIndex, endIndex);

    displayedPads.forEach((wp, idx) => {
        const globalIndex = startIndex + idx + 1;
        const statusBadge = wp.is_active 
            ? '<span class="badge-sesuai">Aktif</span>'
            : '<span class="badge bg-light text-muted border">Nonaktif</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center text-muted fw-semibold">${globalIndex}</td>
            <td class="fw-bold text-dark">${wp.name}</td>
            <td class="text-center font-monospace">${wp.code}</td>
            <td class="text-center">${statusBadge}</td>
            <td class="text-center fw-semibold">${wp.data_count}</td>
            <td class="text-center text-muted">${wp.last_input_date}</td>
            <td class="text-center">
                <div class="d-inline-flex gap-1">
                    <button type="button" class="btn-tbl-action btn-tbl-edit" onclick="editWellPadItem(${wp.id})" title="Edit Well Pad">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn-tbl-action btn-tbl-del" onclick="deleteWellPadItem(${wp.id}, '${wp.name.replace(/'/g, "\\'")}', ${wp.data_count})" title="Hapus / Nonaktifkan">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    const counterEl = document.getElementById('wpTableCounter');
    if (counterEl) {
        counterEl.textContent = `Menampilkan ${startIndex + 1} - ${endIndex} dari ${total} Well Pad`;
    }

    renderWpPagination(totalPages);
}

function renderWpPagination(totalPages) {
    const nav = document.getElementById('wpPaginationList');
    if (!nav) return;
    nav.innerHTML = '';

    if (totalPages <= 1 || wpPerPage === 'all') {
        return;
    }

    // Tombol Sebelumnya (Previous)
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${wpCurrentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<button type="button" class="page-link" onclick="goToWpPage(${wpCurrentPage - 1})" aria-label="Previous">&laquo;</button>`;
    nav.appendChild(prevLi);

    // Nomor Halaman
    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7 && Math.abs(p - wpCurrentPage) > 2 && p !== 1 && p !== totalPages) {
            if (p === 2 || p === totalPages - 1) {
                const ellipsis = document.createElement('li');
                ellipsis.className = 'page-item disabled';
                ellipsis.innerHTML = '<span class="page-link py-1 px-2 border-0">...</span>';
                nav.appendChild(ellipsis);
            }
            continue;
        }

        const li = document.createElement('li');
        li.className = `page-item ${p === wpCurrentPage ? 'active' : ''}`;
        li.innerHTML = `<button type="button" class="page-link" onclick="goToWpPage(${p})">${p}</button>`;
        nav.appendChild(li);
    }

    // Tombol Selanjutnya (Next)
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${wpCurrentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<button type="button" class="page-link" onclick="goToWpPage(${wpCurrentPage + 1})" aria-label="Next">&raquo;</button>`;
    nav.appendChild(nextLi);
}

function openAddWellPadForm() {
    document.getElementById('wpFormId').value = '';
    document.getElementById('wpFormTitle').textContent = 'Tambah Well Pad Baru';
    document.getElementById('wpFormName').value = '';
    document.getElementById('wpFormCode').value = '';
    document.getElementById('wpFormStatus').value = '1';
    
    const collapseEl = document.getElementById('collapseWellPadForm');
    bootstrap.Collapse.getOrCreateInstance(collapseEl).show();
}

function closeWellPadForm() {
    const collapseEl = document.getElementById('collapseWellPadForm');
    bootstrap.Collapse.getOrCreateInstance(collapseEl).hide();
}

function editWellPadItem(id) {
    const wp = wellPadsCache.find(p => p.id === id);
    if (!wp) return;

    document.getElementById('wpFormId').value = wp.id;
    document.getElementById('wpFormTitle').textContent = `Edit Well Pad - ${wp.name}`;
    document.getElementById('wpFormName').value = wp.name;
    document.getElementById('wpFormCode').value = wp.code;
    document.getElementById('wpFormStatus').value = wp.is_active ? '1' : '0';

    const collapseEl = document.getElementById('collapseWellPadForm');
    bootstrap.Collapse.getOrCreateInstance(collapseEl).show();
}

async function submitWellPadForm(e) {
    e.preventDefault();
    const id = document.getElementById('wpFormId').value;
    const isEdit = Boolean(id);
    const url = isEdit ? `/gas-detector/well-pads/${id}` : '/gas-detector/well-pads';
    const method = isEdit ? 'PUT' : 'POST';

    const payload = {
        name: document.getElementById('wpFormName').value,
        code: document.getElementById('wpFormCode').value,
        is_active: document.getElementById('wpFormStatus').value === '1',
    };

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (res.ok && data.success) {
            closeWellPadForm();
            await loadWellPads();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => window.location.reload());
        } else {
            let msg = data.message || 'Gagal menyimpan data Well Pad.';
            if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
            Swal.fire({ icon: 'error', title: 'Gagal', html: msg });
        }
    } catch (err) {
        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
    }
}

function deleteWellPadItem(id, name, dataCount) {
    let warningText = `Apakah Anda yakin ingin menghapus ${name}?`;
    if (dataCount > 0) {
        warningText = `Well Pad "${name}" memiliki ${dataCount} riwayat data pengukuran gas. Menghapus akan mengubah statusnya menjadi NONAKTIF agar data riwayat masa lalu tetap aman. Lanjutkan?`;
    }

    Swal.fire({
        title: 'Hapus / Nonaktifkan Well Pad?',
        text: warningText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await fetch(`/gas-detector/well-pads/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    await loadWellPads();
                    Swal.fire({
                        icon: 'success',
                        title: data.action === 'deactivated' ? 'Dinonaktifkan!' : 'Terhapus!',
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Gagal memproses.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            }
        }
    });
}

async function viewMeasurementDetail(id) {
    try {
        const res = await fetch(`/gas-detector/measurements/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            const m = data.data;
            document.getElementById('dtlDate').textContent = m.measurement_date_formatted || m.measurement_date || '-';
            document.getElementById('dtlWellPad').textContent = m.well_pad || '-';
            document.getElementById('dtlCo').textContent = m.co_level !== null ? `${m.co_level} ppm (${m.co_status})` : '-';
            document.getElementById('dtlO2').textContent = m.o2_level !== null ? `${m.o2_level} % (${m.o2_status})` : '-';
            document.getElementById('dtlH2s').textContent = m.h2s_level !== null ? `${m.h2s_level} ppm (${m.h2s_status})` : '-';
            document.getElementById('dtlLel').textContent = m.lel_level !== null ? `${m.lel_level} % (${m.lel_status})` : '-';
            document.getElementById('dtlPress').textContent = m.pressure !== null ? `${m.pressure} Psi` : '-';
            let statusBadge = '<span class="badge bg-light text-muted border">-</span>';
            if (m.overall_status === 'Sesuai') {
                statusBadge = '<span class="badge-sesuai">Sesuai</span>';
            } else if (m.overall_status === 'Tidak Sesuai') {
                statusBadge = '<span class="badge-tidaksesuai">Tidak Sesuai</span>';
            }
            document.getElementById('dtlStatus').innerHTML = statusBadge;
            document.getElementById('dtlOfficer').textContent = m.recorded_by || '-';

            const notesItems = [
                { label: 'CO (ppm)', val: m.co_notes },
                { label: 'O₂ (%)', val: m.o2_notes },
                { label: 'H₂S (ppm)', val: m.h2s_notes },
                { label: 'LEL (%)', val: m.lel_notes },
                { label: 'Pressure (Psi)', val: m.pressure_notes },
            ];
            if (m.general_notes) {
                notesItems.push({ label: 'Catatan Umum', val: m.general_notes });
            }

            const notesBox = document.getElementById('dtlParameterNotesBox');
            if (notesBox) {
                const filledNotes = notesItems.filter(n => n.val && String(n.val).trim() !== '');
                if (filledNotes.length > 0) {
                    notesBox.innerHTML = filledNotes.map(n => `
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <span class="text-muted fw-semibold">${n.label}:</span>
                            <span class="text-dark fw-medium text-end">${n.val}</span>
                        </div>
                    `).join('');
                } else {
                    notesBox.textContent = 'Tidak ada keterangan tambahan.';
                }
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSingleDetail')).show();
        }
    } catch (err) {
        Swal.fire('Error', 'Gagal memuat detail data.', 'error');
    }
}

async function editSingleMeasurement(id) {
    try {
        const res = await fetch(`/gas-detector/measurements/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            const m = data.data;
            document.getElementById('editMeasurementId').value = m.id;
            document.getElementById('editDate').value = m.measurement_date;
            document.getElementById('editWellPad').value = m.well_pad;
            document.getElementById('editCo').value = m.co_level !== null ? m.co_level : '';
            document.getElementById('editO2').value = m.o2_level !== null ? m.o2_level : '';
            document.getElementById('editH2s').value = m.h2s_level !== null ? m.h2s_level : '';
            document.getElementById('editLel').value = m.lel_level !== null ? m.lel_level : '';
            document.getElementById('editPressure').value = m.pressure !== null ? m.pressure : '';
            document.getElementById('editCoNotes').value = m.co_notes || '';
            document.getElementById('editO2Notes').value = m.o2_notes || '';
            document.getElementById('editH2sNotes').value = m.h2s_notes || '';
            document.getElementById('editLelNotes').value = m.lel_notes || '';
            document.getElementById('editPressureNotes').value = m.pressure_notes || '';
            document.getElementById('editNotes').value = m.general_notes || '';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalSingleEdit')).show();
        }
    } catch (err) {
        Swal.fire('Error', 'Gagal memuat data pengukuran.', 'error');
    }
}

async function submitSingleEdit(e) {
    e.preventDefault();
    const id = document.getElementById('editMeasurementId').value;
    const coVal = document.getElementById('editCo').value;
    const o2Val = document.getElementById('editO2').value;
    const h2sVal = document.getElementById('editH2s').value;
    const lelVal = document.getElementById('editLel').value;
    const pressVal = document.getElementById('editPressure').value;

    const payload = {
        measurement_date: document.getElementById('editDate').value,
        well_pad: document.getElementById('editWellPad').value,
        co_level: coVal !== '' ? coVal : null,
        o2_level: o2Val !== '' ? o2Val : null,
        h2s_level: h2sVal !== '' ? h2sVal : null,
        lel_level: lelVal !== '' ? lelVal : null,
        pressure: pressVal !== '' ? pressVal : null,
        co_notes: document.getElementById('editCoNotes').value || null,
        o2_notes: document.getElementById('editO2Notes').value || null,
        h2s_notes: document.getElementById('editH2sNotes').value || null,
        lel_notes: document.getElementById('editLelNotes').value || null,
        pressure_notes: document.getElementById('editPressureNotes').value || null,
        notes: document.getElementById('editNotes').value || null,
    };

    try {
        const res = await fetch(`/gas-detector/measurements/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok && data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalSingleEdit')).hide();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => window.location.reload());
        } else {
            Swal.fire('Error', data.message || 'Gagal menyimpan pembaruan.', 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
    }
}

function deleteMeasurementRecord(id, wellPad) {
    Swal.fire({
        title: 'Hapus Rekaman Pengukuran?',
        text: `Apakah Anda yakin ingin menghapus data pengukuran pada ${wellPad}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await fetch(`/gas-detector/measurements/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: data.message,
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Gagal menghapus.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            }
        }
    });
}
// Inisialisasi Tampilan Periode Riwayat pada Form Load
document.addEventListener('DOMContentLoaded', function () {
    const periodTypeSelect = document.getElementById('filterHistoryPeriodType');
    if (periodTypeSelect) {
        toggleHistoryPeriodMode(periodTypeSelect.value);
    }
});
</script>
@endpush