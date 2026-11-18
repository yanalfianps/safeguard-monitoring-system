@extends('layouts.app', [
    'title' => 'Incident',
    'activeModule' => 'incident'
])

@push('styles')
<style>
    .header-icon-box.red {
        background: #dc2626;
        color: #ffffff;
    }
    :root {
        --navy: #102b3f;
        --navy-dark: #071e2e;
        --green: #07845c;
        --red-incident: #dc2626;
        --red-soft: #fef2f2;
        --page-bg: #f8fafc;
        --border: #e2e8f0;
        --muted: #64748b;
    }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 4px;
        margin-bottom: 20px;
        padding-top: 2px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .header-title-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-heading {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }

    .breadcrumb-nav {
        font-size: 11px;
        color: var(--muted);
        margin-top: 3px;
        line-height: 1.3;
    }

    .breadcrumb-text {
        color: var(--muted);
        font-size: 11px;
    }

    /* SECTION LINE CHART TREN KECELAKAAN KERJA & HIGHLIGHT CARD TOTAL KEJADIAN */
    .incident-trend-highlight-section {
        display: grid;
        grid-template-columns: minmax(0, 7.25fr) minmax(255px, 2.75fr);
        gap: 18px;
        margin-bottom: 20px;
        align-items: stretch;
    }

    .incident-trend-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        height: 100%;
    }

    .incident-trend-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: nowrap;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
        margin-bottom: 12px;
    }

    .incident-trend-title-wrap {
        flex: 1 1 0%;
        min-width: 0;
    }

    .incident-trend-title-wrap h6 {
        margin: 0;
        font-size: 14.5px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
        letter-spacing: -0.2px;
    }

    .incident-trend-subtitle {
        font-size: 11.5px;
        color: #64748b;
        margin: 3px 0 0 0;
        line-height: 1.4;
    }

    .incident-trend-controls {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: nowrap;
        flex-shrink: 0;
        margin-left: auto;
    }

    .incident-trend-controls .form-select-sm {
        width: auto;
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
        border-color: #cbd5e1;
        border-radius: 8px;
        padding: 4px 28px 4px 10px;
        background-color: #f8fafc;
        cursor: pointer;
        flex-shrink: 0;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .incident-trend-controls .form-select-sm:focus {
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        background-color: #ffffff;
    }

    .incident-trend-legend-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }

    .incident-trend-legend-items {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .incident-trend-legend-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
    }

    .incident-trend-legend-line {
        width: 18px;
        height: 3px;
        border-radius: 2px;
        position: relative;
        display: inline-block;
    }

    .incident-trend-legend-line::after {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: inherit;
        border: 1.5px solid #ffffff;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .incident-trend-canvas-wrap {
        position: relative;
        width: 100%;
        height: 225px;
        min-height: 225px;
    }

    /* HIGHLIGHT CARD TOTAL KEJADIAN YANG DILAPORKAN (KHUSUS PANAS BUMI) */
    .incident-highlight-card {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 55%, #881337 100%);
        color: #ffffff;
        border-radius: 12px;
        padding: 20px 22px;
        box-shadow: 0 10px 22px -6px rgba(185, 28, 28, 0.38), 0 4px 10px -4px rgba(153, 27, 27, 0.25);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        height: 100%;
        border: 1px solid rgba(255, 255, 255, 0.14);
    }

    .incident-highlight-card::before {
        content: '';
        position: absolute;
        width: 170px;
        height: 170px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.16) 0%, rgba(255, 255, 255, 0) 70%);
        top: -45px;
        right: -45px;
        pointer-events: none;
    }

    .incident-highlight-card::after {
        content: '';
        position: absolute;
        width: 130px;
        height: 130px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(254, 202, 202, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
        bottom: -40px;
        left: -30px;
        pointer-events: none;
    }

    .incident-highlight-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        position: relative;
        z-index: 1;
    }

    .incident-highlight-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.35;
        margin: 0;
        letter-spacing: -0.1px;
    }

    .incident-highlight-badge-scope {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 6px;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 10.5px;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.16);
        color: #fff1f2;
        border: 1px solid rgba(255, 255, 255, 0.22);
    }

    .incident-highlight-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 11px;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 21px;
        color: #ffffff;
        flex-shrink: 0;
    }

    .incident-highlight-center {
        margin: 18px 0;
        position: relative;
        z-index: 1;
    }

    .incident-highlight-number {
        font-size: 52px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1;
        letter-spacing: -1.5px;
        margin-bottom: 4px;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.16);
    }

    .incident-highlight-unit {
        font-size: 15px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.94);
        letter-spacing: 0.2px;
    }

    .incident-highlight-footer {
        position: relative;
        z-index: 1;
        padding-top: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.18);
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .incident-highlight-period {
        font-size: 11.5px;
        font-weight: 600;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .incident-highlight-note {
        font-size: 10.5px;
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.35;
    }

    .incident-trend-card,
    .incident-highlight-card,
    .incident-trend-highlight-section {
        min-width: 0;
        max-width: 100%;
    }

    @media (max-width: 1100px) {
        .incident-trend-highlight-section {
            grid-template-columns: minmax(0, 6.6fr) minmax(220px, 3.4fr);
        }
        .incident-trend-header {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 991.98px) {
        .incident-trend-highlight-section {
            grid-template-columns: minmax(0, 1fr);
        }
        .incident-trend-header {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 820px) {
        .incident-trend-canvas-wrap {
            height: 210px;
            min-height: 210px;
        }
        .incident-highlight-number {
            font-size: 42px;
        }
    }

    @media (max-width: 640px) {
        .incident-trend-header {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .incident-trend-controls {
            width: 100%;
            margin-left: 0;
            justify-content: flex-start;
            flex-wrap: wrap;
        }
        .incident-trend-controls .form-select-sm {
            min-width: 0 !important;
            flex: 1 1 110px;
            max-width: 100%;
        }
    }

    /* 3 DONUT CHARTS PANEL - KORPORAT, PROPORSI SERAGAM & ELEGAN */
    .incident-charts-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 22px;
        align-items: stretch;
    }

    .incident-chart-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        position: relative;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        height: 100%;
    }

    .incident-chart-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(16, 43, 63, 0.08);
        border-color: #cbd5e1;
    }

    .incident-chart-header {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }

    .incident-chart-header h6 {
        margin: 0;
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
        letter-spacing: -0.2px;
    }

    /* Layout Seragam: Pie Chart di Kiri (Diameter Tetap), Legenda di Kanan (Vertically Centered) */
    .incident-chart-body {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 16px;
        flex: 1;
        min-height: 158px;
    }

    .incident-chart-canvas-wrap {
        position: relative;
        width: 148px;
        height: 148px;
        min-width: 148px;
        min-height: 148px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: visible;
    }

    /* External HTML Tooltip untuk Pie Chart agar tidak pernah terpotong batas canvas / card */
    .incident-chart-external-tooltip {
        position: fixed;
        z-index: 1090;
        pointer-events: none;
        background: rgba(15, 23, 42, 0.95);
        color: #ffffff;
        border-radius: 8px;
        padding: 7px 11px;
        font-size: 11.5px;
        font-weight: 500;
        line-height: 1.4;
        box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.25), 0 4px 6px -2px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.14);
        white-space: nowrap;
        max-width: calc(100vw - 24px);
        opacity: 0;
        transition: opacity 0.12s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .incident-chart-external-tooltip .tooltip-color-swatch {
        width: 10px;
        height: 10px;
        border-radius: 2px;
        border: 1px solid #ffffff;
        flex-shrink: 0;
        display: inline-block;
    }

    @media (max-width: 576px) {
        .incident-chart-external-tooltip {
            white-space: normal;
        }
    }

    .incident-chart-legend {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 5px;
        max-height: 164px;
        overflow-y: auto;
        padding-right: 2px;
    }

    .incident-legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        line-height: 1.3;
        color: #334155;
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 4px;
        transition: background-color 0.15s ease, color 0.15s ease;
        user-select: none;
    }

    .incident-legend-item:hover {
        background-color: #f8fafc;
        color: #0f172a;
    }

    .incident-legend-item.legend-empty {
        cursor: default;
        color: #94a3b8;
    }

    .incident-legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 2px;
        flex-shrink: 0;
        display: inline-block;
    }

    .incident-legend-label {
        font-weight: 500;
        color: inherit;
        word-break: break-word;
    }

    .incident-legend-count {
        font-weight: 700;
        color: #0f172a;
    }

    @media (max-width: 1200px) {
        .incident-charts-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .incident-charts-grid {
            grid-template-columns: 1fr;
        }
        .incident-chart-canvas-wrap {
            width: 138px;
            height: 138px;
            min-width: 138px;
            min-height: 138px;
        }
    }

    @media (max-width: 420px) {
        .incident-chart-body {
            flex-direction: column;
            align-items: center;
            text-align: left;
            gap: 12px;
        }
        .incident-chart-legend {
            width: 100%;
            max-height: none;
        }
    }

    .panel {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 18px;
    }

    /* BADGES */
    .badge-ebtke { background: #e0e7ff; color: #3730a3; font-weight: 600; border-radius: 4px; padding: 3px 6px; }
    .badge-umum { background: #f1f5f9; color: #475569; font-weight: 600; border-radius: 4px; padding: 3px 6px; }
    .badge-panas-bumi { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; font-weight: 600; border-radius: 6px; padding: 4px 8px; display: inline-flex; align-items: center; font-size: 11px; }
    .badge-non-panas { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; font-weight: 600; border-radius: 6px; padding: 4px 8px; display: inline-flex; align-items: center; font-size: 11px; }
    .modal-badge-counter {
        background: #e2e8f0;
        color: #0f172a;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        letter-spacing: 0.2px;
        border: 1px solid #cbd5e1;
    }

    /* Tag Styling Spesifik Sesuai Palet Blueprint */
    .tag-pill-sm {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10.5px;
        font-weight: 600;
        margin: 1px;
        line-height: 1.4;
    }
    .tag-pill-umum { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .tag-pill-ebtke { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .tag-pill-fatality { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; font-weight: 700; }
    .tag-pill-mati { background: #7f1d1d; color: #ffffff; border: 1px solid #991b1b; font-weight: 700; }

    /* Filter Row Grid (7 Komponen Sejajar & Responsif) */
    .filter-grid-row {
        display: grid;
        grid-template-columns: 1.55fr 1.15fr 1.15fr 1.15fr 1.05fr 2.15fr auto;
        gap: 10px;
        align-items: flex-end;
    }

    /* Modal styling */
    .modal-content {
        border: 0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }
    .modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f6;
        background: #f8fafc;
    }
    .modal-body.scrollable-modal-body {
        max-height: calc(85vh - 130px);
        overflow-y: auto;
        padding: 22px;
    }
    .modal-footer {
        padding: 14px 20px;
        border-top: 1px solid #eef2f6;
        background: #f8fafc;
    }

    /* ==================== UI/UX EXPERT STYLES ==================== */
    /* 1. Toggle Biner Kanan-Kiri */
    .binary-toggle-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .binary-toggle-card {
        border: 2px solid #e2e8f0;
        background: #ffffff;
        border-radius: 8px;
        padding: 12px 14px;
        cursor: pointer;
        transition: all .2s ease-in-out;
        display: flex;
        align-items: center;
        gap: 10px;
        user-select: none;
        position: relative;
    }

    .binary-toggle-card:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        transform: translateY(-1px);
    }

    .binary-toggle-card.active-panas {
        border-color: #ea580c;
        background: #fff7ed;
        color: #9a3412;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.12);
    }

    .binary-toggle-card.active-non-panas {
        border-color: #0284c7;
        background: #f0f9ff;
        color: #0369a1;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.12);
    }

    .binary-radio-indicator {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all .2s;
    }

    .binary-toggle-card.active-panas .binary-radio-indicator {
        border-color: #ea580c;
        background: #ea580c;
    }
    .binary-toggle-card.active-non-panas .binary-radio-indicator {
        border-color: #0284c7;
        background: #0284c7;
    }
    .binary-radio-indicator::after {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: white;
        display: block;
    }

    /* 2. Tagging System Ala "Add Skill" LinkedIn */
    .linkedin-tag-container {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 8px 10px;
        background: #ffffff;
        min-height: 44px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
        transition: border-color .15s, box-shadow .15s;
    }

    .linkedin-tag-container:focus-within {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }

    .linkedin-selected-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #0a66c2;
        color: #ffffff;
        border-radius: 16px;
        padding: 4px 10px 4px 12px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.2px;
        box-shadow: 0 1px 3px rgba(10, 102, 194, 0.25);
        animation: badgePop .18s ease-out;
    }

    .linkedin-selected-badge.badge-ebtke-style {
        background: #0f766e;
        box-shadow: 0 1px 3px rgba(15, 118, 110, 0.25);
    }

    @keyframes badgePop {
        0% { transform: scale(0.85); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    .linkedin-remove-btn {
        background: transparent;
        border: 0;
        color: rgba(255, 255, 255, 0.85);
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        font-size: 13px;
        line-height: 1;
        transition: color .15s;
    }
    .linkedin-remove-btn:hover {
        color: #ffffff;
    }

    /* Suggested Badges Pills (Klik untuk Add) */
    .tag-suggestions-box {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .tag-suggestion-pill {
        background: #f1f5f9;
        color: #334155;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 3px 10px;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: all .15s ease;
        user-select: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .tag-suggestion-pill:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
        color: #0f172a;
        transform: translateY(-1px);
    }

    .tag-suggestion-pill.selected {
        opacity: 0.45;
        pointer-events: none;
        text-decoration: line-through;
        border-style: solid;
    }

    .filter-grid-row,
    .incident-charts-grid {
        min-width: 0;
        max-width: 100%;
    }

    @media (max-width: 1200px) {
        .filter-grid-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .incident-charts-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 991.98px) {
        .filter-grid-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .incident-charts-grid { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 768px) {
        .filter-grid-row { grid-template-columns: minmax(0, 1fr); }
        .incident-charts-grid { grid-template-columns: minmax(0, 1fr); }
        .binary-toggle-container { grid-template-columns: minmax(0, 1fr); }
    }
</style>
@endpush

@section('content')
@php
    $umumPaletteMap = \App\Models\Incident::KATEGORI_UMUM_PALETTE;
    $ebtkePaletteMap = \App\Models\Incident::KATEGORI_EBTKE_PALETTE;
@endphp

<!-- HEADER & ACTIONS GLOBAL INCIDENT (TEMA MERAH #dc2626) -->
<x-module-header
    title="Incident"
    icon="bi bi-exclamation-octagon"
    icon-bg="#dc2626"
    icon-shadow="rgba(220, 38, 38, 0.25)"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Incident'],
    ]"
>
    <x-slot:actions>
        <!-- Tombol Lapor Insiden Baru -->
        @adminManagement
            <button type="button" class="btn-action-primary bg-danger border-danger hsse-action-primary" data-bs-toggle="modal" data-bs-target="#modalTambahIncident">
                <i class="bi bi-plus-lg"></i> Lapor Insiden Baru
            </button>
        @endadminManagement
        <!-- Tombol Export PDF -->
        @canExportPdf
            <a href="{{ route('incident.export', request()->query()) }}" class="btn-action-outline text-decoration-none">
                <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Export PDF
            </a>
        @endcanExportPdf
    </x-slot:actions>
</x-module-header>

<!-- Flash Alert -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $trendInit = $trendKlasifikasi ?? [
        'mode' => 'bulanan',
        'year' => (int) now('Asia/Jakarta')->year,
        'month' => (int) now('Asia/Jakarta')->month,
        'currentYear' => (int) now('Asia/Jakarta')->year,
        'labels' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        'tooltipLabels' => [],
        'panasBumi' => array_fill(0, 12, 0),
        'nonPanasBumi' => array_fill(0, 12, 0),
        'highlightTotal' => 0,
        'totalPanasBumi' => 0,
        'totalNonPanasBumi' => 0,
        'periodSubtitle' => 'Periode: Tahun ' . now('Asia/Jakarta')->year,
        'colors' => [
            'panasBumi' => '#ea580c',
            'nonPanasBumi' => '#0284c7',
        ],
    ];
    $trendCurrentYear = (int) ($trendInit['currentYear'] ?? now('Asia/Jakarta')->year);
    $trendSelectedMode = $trendInit['mode'] ?? 'bulanan';
    $trendSelectedYear = (int) ($trendInit['year'] ?? $trendCurrentYear);
    $trendSelectedMonth = (int) ($trendInit['month'] ?? now('Asia/Jakarta')->month);
    $daftarBulanIndo = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];
@endphp

<!-- ==================== SECTION LINE CHART TREN KECELAKAAN KERJA & HIGHLIGHT CARD ==================== -->
<div class="incident-trend-highlight-section" id="incidentTrendHighlightSection">
    <!-- 1. Line Chart Tren Kecelakaan Kerja (72.5% Lebar Desktop) -->
    <div class="incident-trend-card">
        <div class="incident-trend-header">
            <div class="incident-trend-title-wrap">
                <h6>
                    <i class="bi bi-graph-up-arrow text-danger"></i>
                    Tren Kecelakaan Kerja
                </h6>
            </div>

            <!-- Filter Periode Grafik (Harian / Bulanan / Tahunan + Bulan + Tahun) -->
            <div class="incident-trend-controls">
                <select id="trendModeSelect" class="form-select form-select-sm" style="width: auto; min-width: 112px;" aria-label="Mode Periode Grafik" onchange="onTrendPeriodControlChange()">
                    <option value="harian" {{ $trendSelectedMode === 'harian' ? 'selected' : '' }}>Harian</option>
                    <option value="bulanan" {{ $trendSelectedMode === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                    <option value="tahunan" {{ $trendSelectedMode === 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                </select>

                <select id="trendMonthSelect" class="form-select form-select-sm {{ $trendSelectedMode === 'harian' ? '' : 'd-none' }}" style="width: auto; min-width: 122px;" aria-label="Pilih Bulan Grafik" onchange="onTrendPeriodControlChange()">
                    @foreach($daftarBulanIndo as $mNum => $mLabel)
                        <option value="{{ $mNum }}" {{ $trendSelectedMonth === $mNum ? 'selected' : '' }}>{{ $mLabel }}</option>
                    @endforeach
                </select>

                <select id="trendYearSelect" class="form-select form-select-sm {{ $trendSelectedMode === 'tahunan' ? 'd-none' : '' }}" style="width: auto; min-width: 92px;" aria-label="Pilih Tahun Grafik" onchange="onTrendPeriodControlChange()">
                    @for($yOpt = $trendCurrentYear - 5; $yOpt <= $trendCurrentYear + 5; $yOpt++)
                        <option value="{{ $yOpt }}" {{ $trendSelectedYear === $yOpt ? 'selected' : '' }}>{{ $yOpt }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <!-- Bar Legenda Dua Garis Klasifikasi Area -->
        <div class="incident-trend-legend-bar">
            <div class="incident-trend-legend-items">
                <span class="incident-trend-legend-pill">
                    <span class="incident-trend-legend-line" style="background-color: #ea580c;"></span>
                    <span>Panas Bumi <strong id="trendLegendPanasCount" class="text-dark">({{ $trendInit['totalPanasBumi'] ?? 0 }})</strong></span>
                </span>
                <span class="incident-trend-legend-pill">
                    <span class="incident-trend-legend-line" style="background-color: #0284c7;"></span>
                    <span>Non-Panas Bumi <strong id="trendLegendNonPanasCount" class="text-dark">({{ $trendInit['totalNonPanasBumi'] ?? 0 }})</strong></span>
                </span>
            </div>
            <span id="trendActivePeriodBadge" class="badge bg-light text-secondary border" style="font-size: 11px; font-weight: 600;">
                {{ $trendInit['periodSubtitle'] ?? ('Periode: Tahun ' . $trendSelectedYear) }}
            </span>
        </div>

        <!-- Canvas Line Chart -->
        <div class="incident-trend-canvas-wrap">
            <canvas id="chartTrenKecelakaanCanvas"></canvas>
        </div>
    </div>

    <!-- 2. Highlight Card Total Kejadian (Khusus Kecelakaan Panas Bumi - 27.5% Lebar Desktop) -->
    <div class="incident-highlight-card" id="incidentHighlightPanasBumiCard">
        <div class="incident-highlight-top">
            <div>
                <h6 class="incident-highlight-title">Total Kejadian</h6>
                <span class="incident-highlight-badge-scope">
                    <i class="bi bi-fire"></i> Kecelakaan Panas Bumi
                </span>
            </div>
            <div class="incident-highlight-icon-box" title="Total Kejadian Kecelakaan Panas Bumi">
                <i class="bi bi-shield-exclamation"></i>
            </div>
        </div>

        <div class="incident-highlight-center">
            <div class="incident-highlight-number" id="highlightTotalPanasBumiValue">{{ $trendInit['highlightTotal'] ?? 0 }}</div>
            <div class="incident-highlight-unit">Kejadian</div>
        </div>

        <div class="incident-highlight-footer">
            <div class="incident-highlight-period">
                <i class="bi bi-calendar3"></i>
                <span id="highlightPeriodText">{{ $trendInit['periodSubtitle'] ?? ('Periode: Tahun ' . $trendSelectedYear) }}</span>
            </div>
            <div class="incident-highlight-note">
                Akumulasi laporan klasifikasi area Panas Bumi pada periode terpilih
            </div>
        </div>
    </div>
</div>

<!-- ==================== 3 GRAFIK DISTRIBUSI KLASIFIKASI INCIDENT (SERAGAM: GRAFIK KIRI & LEGENDA KANAN) ==================== -->
<div class="incident-charts-grid">
    <!-- Grafik 1: Kategori Umum (Multiple Tags Sebaran) -->
    <div class="incident-chart-card">
        <div class="incident-chart-header">
            <h6>
                <i class="bi bi-tags-fill text-danger"></i> 1. Kategori Umum
            </h6>
        </div>
        <div class="incident-chart-body">
            <div class="incident-chart-canvas-wrap">
                <canvas id="chartUmumCanvas"></canvas>
            </div>
            <div class="incident-chart-legend" id="legendUmumContainer">
                @php $hasUmumLegend = false; @endphp
                @foreach($chartUmum['labels'] as $idx => $lbl)
                    @php
                        $cnt = (int) ($chartUmum['data'][$idx] ?? 0);
                        $clr = $chartUmum['color_map'][$lbl] ?? ($chartUmum['colors'][$idx] ?? '#2563eb');
                    @endphp
                    @if($cnt > 0)
                        @php $hasUmumLegend = true; @endphp
                        <div class="incident-legend-item" onclick="openChartDetailModal('umum', @js($lbl))" title="Klik untuk melihat rincian {{ $lbl }}">
                            <span class="incident-legend-dot" style="background-color: {{ $clr }};"></span>
                            <span class="incident-legend-label">{{ $lbl }} <span class="incident-legend-count">({{ $cnt }})</span></span>
                        </div>
                    @endif
                @endforeach
                @if(!$hasUmumLegend)
                    <div class="incident-legend-item legend-empty">
                        <span class="incident-legend-dot" style="background-color: #e2e8f0;"></span>
                        <span class="incident-legend-label">Belum ada tag (0)</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Grafik 2: Kategori EBTKE (Standar ESDM) -->
    <div class="incident-chart-card">
        <div class="incident-chart-header">
            <h6>
                <i class="bi bi-award-fill text-danger"></i> 2. Kategori EBTKE
            </h6>
        </div>
        <div class="incident-chart-body">
            <div class="incident-chart-canvas-wrap">
                <canvas id="chartEbtkeCanvas"></canvas>
            </div>
            <div class="incident-chart-legend" id="legendEbtkeContainer">
                @php $hasEbtkeLegend = false; @endphp
                @foreach($chartEbtke['labels'] as $idx => $lbl)
                    @php
                        $cnt = (int) ($chartEbtke['data'][$idx] ?? 0);
                        $clr = $chartEbtke['color_map'][$lbl] ?? ($chartEbtke['colors'][$idx] ?? '#dc2626');
                    @endphp
                    @if($cnt > 0)
                        @php $hasEbtkeLegend = true; @endphp
                        <div class="incident-legend-item" onclick="openChartDetailModal('ebtke', @js($lbl))" title="Klik untuk melihat rincian {{ $lbl }}">
                            <span class="incident-legend-dot" style="background-color: {{ $clr }};"></span>
                            <span class="incident-legend-label">{{ $lbl }} <span class="incident-legend-count">({{ $cnt }})</span></span>
                        </div>
                    @endif
                @endforeach
                @if(!$hasEbtkeLegend)
                    <div class="incident-legend-item legend-empty">
                        <span class="incident-legend-dot" style="background-color: #e2e8f0;"></span>
                        <span class="incident-legend-label">Belum ada tag (0)</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Grafik 3: Kategori Panas Bumi (Area Kejadian) -->
    <div class="incident-chart-card">
        <div class="incident-chart-header">
            <h6>
                <i class="bi bi-pie-chart-fill text-danger"></i> 3. Kategori Panas Bumi
            </h6>
        </div>
        <div class="incident-chart-body">
            <div class="incident-chart-canvas-wrap">
                <canvas id="chartAreaCanvas"></canvas>
            </div>
            <div class="incident-chart-legend" id="legendAreaContainer">
                <div class="incident-legend-item" onclick="openChartDetailModal('area', 'Kecelakaan Panas Bumi')" title="Klik untuk melihat insiden Panas Bumi">
                    <span class="incident-legend-dot" style="background-color: #ea580c;"></span>
                    <span class="incident-legend-label">Panas Bumi <span class="incident-legend-count">({{ $chartArea['data'][0] ?? 0 }})</span></span>
                </div>
                <div class="incident-legend-item" onclick="openChartDetailModal('area', 'Kecelakaan Non-Panas Bumi')" title="Klik untuk melihat insiden Non-Panas Bumi">
                    <span class="incident-legend-dot" style="background-color: #0284c7;"></span>
                    <span class="incident-legend-label">Non-Panas Bumi <span class="incident-legend-count">({{ $chartArea['data'][1] ?? 0 }})</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER KOMPREHENSIF INSIDEN (7 KOMPONEN SEJAJAR: SEARCH -> UMUM -> EBTKE -> AREA -> STATUS LAPORAN -> TANGGAL -> AKSI) -->
<div class="panel mb-4">
    <form method="GET" action="{{ route('incident.index') }}" class="filter-grid-row">
        <!-- 1. Kata Kunci -->
        <div>
            <label class="form-label small fw-bold mb-1 text-secondary">Cari Insiden</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="No. Insiden, Lokasi, Tag...">
        </div>

        <!-- 2. Kategori Umum -->
        <div>
            <label class="form-label small fw-bold mb-1 text-secondary">Kategori Umum</label>
            <select name="kategori_umum" class="form-select form-select-sm">
                <option value="">Semua Tag Umum</option>
                @foreach($kategoriUmumList as $ku)
                    <option value="{{ $ku }}" {{ request('kategori_umum') == $ku ? 'selected' : '' }}>{{ $ku }}</option>
                @endforeach
            </select>
        </div>

        <!-- 3. Kategori EBTKE -->
        <div>
            <label class="form-label small fw-bold mb-1 text-secondary">Kategori EBTKE</label>
            <select name="kategori_ebtke" class="form-select form-select-sm">
                <option value="">Semua Tag EBTKE</option>
                @foreach($kategoriEbtkeList as $ke)
                    <option value="{{ $ke }}" {{ request('kategori_ebtke') == $ke ? 'selected' : '' }}>{{ $ke }}</option>
                @endforeach
            </select>
        </div>

        <!-- 4. Klasifikasi Area -->
        <div>
            <label class="form-label small fw-bold mb-1 text-secondary">Klasifikasi Area</label>
            <select name="klasifikasi_area" class="form-select form-select-sm">
                <option value="">Semua Area</option>
                @foreach($areaList as $area)
                    <option value="{{ $area }}" {{ request('klasifikasi_area') == $area ? 'selected' : '' }}>{{ $area }}</option>
                @endforeach
            </select>
        </div>

        <!-- 5. Status Laporan -->
        <div>
            <label class="form-label small fw-bold mb-1 text-secondary">Status Laporan</label>
            <select name="status_laporan" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="Preliminary" {{ request('status_laporan') === 'Preliminary' ? 'selected' : '' }}>Preliminary</option>
                <option value="Final" {{ request('status_laporan') === 'Final' ? 'selected' : '' }}>Final</option>
            </select>
        </div>

        <!-- 6. Rentang Tanggal -->
        <div>
            <label class="form-label small fw-bold mb-1 text-secondary">Rentang Tanggal</label>
            <div class="input-group input-group-sm">
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control" title="Mulai">
                <span class="input-group-text bg-light text-muted px-2">s/d</span>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control" title="Sampai">
            </div>
        </div>

        <!-- 7. Tombol Aksi -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-1 px-3">
                <i class="bi bi-filter"></i> Filter
            </button>
            <a href="{{ route('incident.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center" title="Reset">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- TABEL REKAPITULASI INSIDEN (7 KOLOM SESUAI BLUEPRINT) -->
<div class="panel p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="min-width: 220px;">No. Insiden | Judul</th>
                    <th>Kategori Umum</th>
                    <th>Kategori EBTKE</th>
                    <th>Kategori Insiden</th>
                    <th>Lokasi</th>
                    <th class="text-center">Status</th>
                    <th class="text-center pe-3" style="width: 130px; min-width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incidents as $inc)
                    @php
                        $uTags = is_array($inc->kategori_umum) ? $inc->kategori_umum : (json_decode($inc->kategori_umum, true) ?: []);
                        $eTags = is_array($inc->kategori_ebtke) ? $inc->kategori_ebtke : (json_decode($inc->kategori_ebtke, true) ?: []);
                        $isPanasBumi = !str_contains((string) $inc->klasifikasi_area, 'Non') && str_contains((string) $inc->klasifikasi_area, 'Panas');
                        $isFinal = in_array($inc->status_laporan, ['Final', 'Selesai']);
                    @endphp
                    <tr>
                        <!-- Kolom 1: No. Insiden | Judul + Hari, Tanggal, Jam WIB -->
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <span class="badge bg-danger-subtle text-danger font-monospace border border-danger-subtle">{{ $inc->nomor_insiden }}</span>
                                @if(($inc->jumlah_hari_hilang ?? 0) > 0)
                                    <span class="badge bg-danger text-white" style="font-size: 10px;" title="Lost Workdays (LWD)">
                                        <i class="bi bi-calendar-x me-1"></i>{{ $inc->jumlah_hari_hilang }} LWD
                                    </span>
                                @endif
                            </div>
                            <div class="fw-bold text-dark" style="font-size: 13px; line-height: 1.3;">
                                {{ $inc->title ?: ($inc->nomor_insiden . ' - ' . $inc->lokasi_kejadian) }}
                            </div>
                            <div class="text-muted mt-1" style="font-size: 11px;">
                                <i class="bi bi-calendar-event me-1"></i>{{ $inc->hari }}, {{ $inc->tanggal_waktu ? $inc->tanggal_waktu->format('d/m/Y') : '-' }}
                                <span class="mx-1">•</span>
                                <i class="bi bi-clock me-1"></i>{{ $inc->tanggal_waktu ? $inc->tanggal_waktu->format('H:i') : '-' }} WIB
                            </div>
                        </td>

                        <!-- Kolom 2: Kategori Umum -->
                        <td>
                            <div class="d-flex flex-wrap gap-1" style="max-width: 220px;">
                                @forelse($uTags as $tag)
                                    @php
                                        $tagColor = $umumPaletteMap[$tag] ?? null;
                                    @endphp
                                    @if($tag === 'Fatality')
                                        <span class="tag-pill-sm tag-pill-fatality">
                                            <i class="bi bi-exclamation-octagon-fill me-1"></i>{{ $tag }}
                                        </span>
                                    @elseif($tagColor)
                                        <span class="tag-pill-sm" style="background: {{ $tagColor }}18; color: {{ $tagColor }}; border: 1px solid {{ $tagColor }}45;">
                                            {{ $tag }}
                                        </span>
                                    @else
                                        <span class="tag-pill-sm tag-pill-umum">{{ $tag }}</span>
                                    @endif
                                @empty
                                    <span class="text-muted small">-</span>
                                @endforelse
                            </div>
                        </td>

                        <!-- Kolom 3: Kategori EBTKE -->
                        <td>
                            <div class="d-flex flex-wrap gap-1" style="max-width: 200px;">
                                @forelse($eTags as $tag)
                                    @php
                                        $ebtkeColor = $ebtkePaletteMap[$tag] ?? null;
                                    @endphp
                                    @if($tag === 'Kecelakaan Mati')
                                        <span class="tag-pill-sm tag-pill-mati">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $tag }}
                                        </span>
                                    @elseif($ebtkeColor)
                                        <span class="tag-pill-sm" style="background: {{ $ebtkeColor }}18; color: {{ $ebtkeColor }}; border: 1px solid {{ $ebtkeColor }}45;">
                                            {{ $tag }}
                                        </span>
                                    @else
                                        <span class="tag-pill-sm tag-pill-ebtke">{{ $tag }}</span>
                                    @endif
                                @empty
                                    <span class="text-muted small">-</span>
                                @endforelse
                            </div>
                        </td>

                        <!-- Kolom 4: Kategori Insiden (Panas Bumi vs Non-Panas Bumi) -->
                        <td>
                            @if($isPanasBumi)
                                <span class="badge-panas-bumi">
                                    <i class="bi bi-fire me-1"></i>Kec. Panas Bumi
                                </span>
                            @else
                                <span class="badge-non-panas">
                                    <i class="bi bi-building me-1"></i>Kec. Non-Panas Bumi
                                </span>
                            @endif
                        </td>

                        <!-- Kolom 5: Lokasi & Komponen/Unit -->
                        <td>
                            <div class="fw-bold text-dark"><i class="bi bi-geo-alt text-danger me-1"></i>{{ $inc->lokasi_kejadian }}</div>
                            <small class="text-muted d-block">Unit: {{ $inc->komponen_terkait }}</small>
                            @if(!empty($inc->penanggung_jawab))
                                <small class="text-secondary" style="font-size: 10.5px;"><i class="bi bi-person-badge me-1"></i>{{ $inc->penanggung_jawab }}</small>
                            @endif
                        </td>

                        <!-- Kolom 6: Status -->
                        <td class="text-center">
                            @if($isFinal)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Final</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Preliminary</span>
                            @endif
                        </td>

                        <!-- Kolom 7: Aksi -->
                        <td class="text-center pe-3 text-nowrap" style="white-space: nowrap;">
                            <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                <!-- Tombol Detail / Investigasi -->
                                <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick="openDetailModal({{ json_encode($inc) }})"
                                        title="Lihat Detail Investigasi">
                                    <i class="bi bi-eye"></i>
                                </button>

                                @adminManagement
                                    <!-- Tombol Edit -->
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="openEditModal({{ json_encode($inc) }})"
                                            title="Edit Laporan Insiden">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Tombol Hapus Pop-up -->
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="openDeleteConfirmation({{ $inc->id }}, {{ Js::from($inc->nomor_insiden) }}, {{ Js::from($inc->lokasi_kejadian) }})"
                                            title="Hapus Laporan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endadminManagement
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i>
                            Tidak ada catatan insiden kecelakaan kerja yang terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 border-top">
        {{ $incidents->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- ==================== 1. MODAL TAMBAH INSIDEN (ALPINE.JS INTERACTIVE) ==================== -->
<div class="modal fade" id="modalTambahIncident" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="formTambahIncident" action="{{ route('incident.store') }}" method="POST" enctype="multipart/form-data"
              class="modal-content"
              x-data="incidentFormHandler({
                  area: 'Kecelakaan Panas Bumi',
                  umum: [],
                  ebtke: []
              })">
            @csrf
            <div class="modal-header bg-light">
                <div>
                    <h6 class="modal-title fw-bold text-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Lapor Insiden Kecelakaan Kerja Baru
                    </h6>
                    <small class="text-muted">Pencatatan klasifikasi area biner & multiple tags kategori</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body scrollable-modal-body">
                <div class="row g-3">
                    <!-- Nomor Insiden & Judul Insiden -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nomor Insiden (Auto-Generated)</label>
                        <input type="text" name="nomor_insiden" class="form-control form-control-sm font-monospace bg-light" value="{{ $nextIncidentNumber }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Judul Insiden <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="tambahJudulInsiden" class="form-control form-control-sm" placeholder="Contoh: Terpeleset saat inspeksi pipa..." required>
                    </div>

                    <!-- KLASIFIKASI (URUTAN: KATEGORI UMUM -> KATEGORI EBTKE -> KLASIFIKASI AREA) -->
                    <div class="col-12">
                        <label class="form-label small fw-bold text-danger mb-0">
                            <i class="bi bi-diagram-3-fill me-1"></i>Klasifikasi Insiden
                        </label>
                    </div>

                    <!-- 3a. Kategori Umum (Multiple Tags Ala LinkedIn) -->
                    <div class="col-12">
                        <label class="form-label small fw-bold d-flex justify-content-between align-items-center mb-1">
                            <span>
                                <i class="bi bi-tags text-primary me-1"></i> Kategori Umum
                            </span>
                            <span class="badge bg-light text-primary border" x-text="selectedUmum.length + ' tag terpilih'"></span>
                        </label>

                        <div class="linkedin-tag-container">
                            <template x-for="(tag, idx) in selectedUmum" :key="tag">
                                <div class="linkedin-selected-badge">
                                    <span x-text="tag"></span>
                                    <button type="button" class="linkedin-remove-btn" @click="removeUmum(tag)" title="Hapus tag">
                                        <i class="bi bi-x-circle-fill"></i>
                                    </button>
                                    <input type="hidden" name="kategori_umum[]" :value="tag">
                                </div>
                            </template>
                            <span x-show="selectedUmum.length === 0" class="text-muted small ps-1" style="font-size: 11px;">
                                Belum ada tag dipilih. Klik pilihan di bawah untuk menambahkan:
                            </span>
                        </div>

                        <div class="tag-suggestions-box">
                            @foreach($kategoriUmumList as $itemUmum)
                                <span class="tag-suggestion-pill"
                                      :class="{ 'selected': selectedUmum.includes('{{ $itemUmum }}') }"
                                      @click="addUmum('{{ $itemUmum }}')">
                                    <i class="bi bi-plus text-muted" x-show="!selectedUmum.includes('{{ $itemUmum }}')"></i>
                                    <i class="bi bi-check text-success" x-show="selectedUmum.includes('{{ $itemUmum }}')"></i>
                                    {{ $itemUmum }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- 3b. Kategori EBTKE (Standar ESDM) -->
                    <div class="col-12">
                        <label class="form-label small fw-bold d-flex justify-content-between align-items-center mb-1">
                            <span>
                                <i class="bi bi-award text-success me-1"></i> Kategori EBTKE
                            </span>
                            <span class="badge bg-light text-success border" x-text="selectedEbtke.length + ' tag terpilih'"></span>
                        </label>

                        <div class="linkedin-tag-container" style="background: #fbfdfc;">
                            <template x-for="(tag, idx) in selectedEbtke" :key="tag">
                                <div class="linkedin-selected-badge badge-ebtke-style">
                                    <span x-text="tag"></span>
                                    <button type="button" class="linkedin-remove-btn" @click="removeEbtke(tag)" title="Hapus tag">
                                        <i class="bi bi-x-circle-fill"></i>
                                    </button>
                                    <input type="hidden" name="kategori_ebtke[]" :value="tag">
                                </div>
                            </template>
                            <span x-show="selectedEbtke.length === 0" class="text-muted small ps-1" style="font-size: 11px;">
                                Opsional: Klik tag kategori EBTKE di bawah untuk pelaporan ESDM/EBTKE:
                            </span>
                        </div>

                        <div class="tag-suggestions-box">
                            @foreach($kategoriEbtkeList as $itemEbtke)
                                <span class="tag-suggestion-pill"
                                      :class="{ 'selected': selectedEbtke.includes('{{ $itemEbtke }}') }"
                                      @click="addEbtke('{{ $itemEbtke }}')">
                                    <i class="bi bi-plus text-muted" x-show="!selectedEbtke.includes('{{ $itemEbtke }}')"></i>
                                    <i class="bi bi-check text-success" x-show="selectedEbtke.includes('{{ $itemEbtke }}')"></i>
                                    {{ $itemEbtke }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- 3c. Klasifikasi Area Kejadian (Biner Kanan-Kiri) -->
                    <div class="col-12">
                        <label class="form-label small fw-bold d-block mb-1">
                            Klasifikasi Area Kejadian <span class="text-danger">*</span>
                            <small class="text-muted fw-normal">(Pilih salah satu)</small>
                        </label>

                        <input type="hidden" name="klasifikasi_area" :value="area">

                        <div class="binary-toggle-container">
                            <!-- Opsi Kiri: Kecelakaan Panas Bumi -->
                            <div class="binary-toggle-card"
                                 :class="{ 'active-panas': area === 'Kecelakaan Panas Bumi' }"
                                 @click="area = 'Kecelakaan Panas Bumi'">
                                <div class="binary-radio-indicator"></div>
                                <div>
                                    <div class="fw-bold" style="font-size: 13px;">
                                        <i class="bi bi-fire text-danger me-1"></i> Kecelakaan Panas Bumi
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Area operasi, wellpad, power plant, atau fasilitas geotermal</small>
                                </div>
                            </div>

                            <!-- Opsi Kanan: Kecelakaan Non-Panas Bumi -->
                            <div class="binary-toggle-card"
                                 :class="{ 'active-non-panas': area === 'Kecelakaan Non-Panas Bumi' }"
                                 @click="area = 'Kecelakaan Non-Panas Bumi'">
                                <div class="binary-radio-indicator"></div>
                                <div>
                                    <div class="fw-bold" style="font-size: 13px;">
                                        <i class="bi bi-building text-primary me-1"></i> Kecelakaan Non-Panas Bumi
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Kantor administrasi, mess, jalan raya luar, atau fasilitas umum</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hari Kejadian & Tanggal Kejadian (Tersinkronisasi Otomatis) -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Hari Kejadian <span class="text-danger">*</span></label>
                        @php
                            $currentHari = \App\Models\Incident::resolveDayName(now('Asia/Jakarta'));
                        @endphp
                        <input type="text" id="tambahHariSelect" name="hari" class="form-control form-control-sm bg-light" value="{{ $currentHari }}" readonly tabindex="-1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tanggal & Waktu Kejadian <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="tambahTanggalWaktu" name="tanggal_waktu" class="form-control form-control-sm" value="{{ now('Asia/Jakarta')->format('Y-m-d\TH:i') }}" required onchange="syncHariFromDate(this, 'tambahHariSelect')" oninput="syncHariFromDate(this, 'tambahHariSelect')">
                    </div>

                    <!-- Lokasi & Komponen -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Lokasi Kejadian <span class="text-danger">*</span></label>
                        <input type="text" name="lokasi_kejadian" class="form-control form-control-sm" placeholder="Contoh: Wellpad 28 / Power Plant Unit 1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Komponen / Unit Terkait <span class="text-danger">*</span></label>
                        <input type="text" name="komponen_terkait" class="form-control form-control-sm" placeholder="Contoh: Pipa Uap, Turbin, Utility..." required>
                    </div>

                    <!-- Deskripsi -->
                    <div class="col-12">
                        <label class="form-label small fw-bold">Deskripsi Kejadian <span class="text-danger">*</span></label>
                        <textarea name="deskripsi_kejadian" class="form-control form-control-sm" rows="3" placeholder="Jelaskan apa yang terjadi, kronologi singkat, dan kondisi peralatan/korban..." required></textarea>
                    </div>

                    <!-- Tindakan Perbaikan -->
                    <div class="col-12">
                        <label class="form-label small fw-bold">Tindakan Perbaikan / Korektif (CAPA)</label>
                        <textarea name="tindakan_perbaikan" class="form-control form-control-sm" rows="2" placeholder="Tindakan segera yang telah diambil untuk penanganan & mitigasi..."></textarea>
                    </div>

                    <!-- Status Laporan & Jumlah Hari Hilang (Conditional LTI) -->
                    <div :class="selectedUmum.includes('LTI (Lost Time Injury)') ? 'col-md-6' : 'col-12'">
                        <label class="form-label small fw-bold">Status Laporan <span class="text-danger">*</span></label>
                        <select name="status_laporan" id="tambahStatusLaporan" class="form-select form-select-sm" required>
                            <option value="Preliminary" selected>Preliminary (Laporan Awal / Investigasi)</option>
                            <option value="Final">Final (Selesai / Closed)</option>
                        </select>
                    </div>
                    <div class="col-md-6" x-show="selectedUmum.includes('LTI (Lost Time Injury)')" x-transition x-cloak>
                        <label class="form-label small fw-bold">Jumlah Hari Hilang (LWD)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="jumlah_hari_hilang" id="tambahJumlahHariHilang" class="form-control form-control-sm" min="0" x-model="lwd" :disabled="!selectedUmum.includes('LTI (Lost Time Injury)')" placeholder="0">
                            <span class="input-group-text bg-light text-muted" style="font-size: 11px;">Hari</span>
                        </div>
                        <div class="form-text text-muted" style="font-size: 10px;">Hari kerja hilang karena LTI (Lost Time Injury)</div>
                    </div>

                    <!-- Dokumentasi (Multiple & Incremental Upload) -->
                    <div class="col-12">
                        <label class="form-label small fw-bold">Dokumentasi (Upload File Investigasi / Bukti)</label>
                        <input type="file" id="tambahDokumenInput" name="dokumen_investigasi[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" multiple onchange="handleIncrementalFiles(this, 'tambah')">
                        <div class="form-text text-muted" style="font-size: 11px;">Dapat memilih beberapa file sekaligus atau menambahkan file secara bertahap (PDF, JPG, PNG, WEBP, DOC/DOCX, XLS/XLSX — Maks. 15MB/file)</div>
                        <div id="tambahFileListContainer" class="mt-2 d-flex flex-column gap-1"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="btnSubmitTambahIncident" class="btn btn-danger btn-sm px-4">
                    <i class="bi bi-save me-1"></i> Simpan Laporan Insiden
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== 2. MODAL EDIT INSIDEN (ALPINE.JS INTERACTIVE) ==================== -->
<div class="modal fade" id="modalEditIncident" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="formEditIncident" method="POST" enctype="multipart/form-data"
              class="modal-content"
              x-data="incidentEditHandler()">
            @csrf
            @method('PUT')
            <div class="modal-header bg-light">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Insiden
                    </h6>
                    <small class="text-muted">Perbarui klasifikasi, multiple tags kategori, dan dokumen investigasi</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body scrollable-modal-body">
                <div class="row g-3">
                    <!-- Nomor Insiden & Judul Insiden -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nomor Insiden</label>
                        <input type="text" name="nomor_insiden" id="editNomorInsiden" class="form-control form-control-sm font-monospace bg-light" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Judul Insiden <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="editJudulInsiden" class="form-control form-control-sm" placeholder="Contoh: Terpeleset di Area Turbin" required>
                    </div>

                    <!-- KLASIFIKASI (URUTAN: KATEGORI UMUM -> KATEGORI EBTKE -> KLASIFIKASI AREA) -->
                    <div class="col-12">
                        <label class="form-label small fw-bold text-danger mb-0">
                            <i class="bi bi-diagram-3-fill me-1"></i>Klasifikasi Insiden
                        </label>
                    </div>

                    <!-- 3a. Kategori Umum -->
                    <div class="col-12">
                        <label class="form-label small fw-bold d-flex justify-content-between align-items-center mb-1">
                            <span><i class="bi bi-tags text-primary me-1"></i> Kategori Umum</span>
                            <span class="badge bg-light text-primary border" x-text="selectedUmum.length + ' tag terpilih'"></span>
                        </label>

                        <div class="linkedin-tag-container">
                            <template x-for="(tag, idx) in selectedUmum" :key="tag">
                                <div class="linkedin-selected-badge">
                                    <span x-text="tag"></span>
                                    <button type="button" class="linkedin-remove-btn" @click="removeUmum(tag)">
                                        <i class="bi bi-x-circle-fill"></i>
                                    </button>
                                    <input type="hidden" name="kategori_umum[]" :value="tag">
                                </div>
                            </template>
                            <span x-show="selectedUmum.length === 0" class="text-muted small ps-1" style="font-size: 11px;">
                                Belum ada tag dipilih. Klik tombol opsi di bawah untuk menambahkan:
                            </span>
                        </div>

                        <div class="tag-suggestions-box">
                            @foreach($kategoriUmumList as $itemUmum)
                                <span class="tag-suggestion-pill"
                                      :class="{ 'selected': selectedUmum.includes('{{ $itemUmum }}') }"
                                      @click="addUmum('{{ $itemUmum }}')">
                                    <i class="bi bi-plus text-muted" x-show="!selectedUmum.includes('{{ $itemUmum }}')"></i>
                                    <i class="bi bi-check text-success" x-show="selectedUmum.includes('{{ $itemUmum }}')"></i>
                                    {{ $itemUmum }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- 3b. Kategori EBTKE -->
                    <div class="col-12">
                        <label class="form-label small fw-bold d-flex justify-content-between align-items-center mb-1">
                            <span><i class="bi bi-award text-success me-1"></i> Kategori EBTKE</span>
                            <span class="badge bg-light text-success border" x-text="selectedEbtke.length + ' tag terpilih'"></span>
                        </label>

                        <div class="linkedin-tag-container" style="background: #fbfdfc;">
                            <template x-for="(tag, idx) in selectedEbtke" :key="tag">
                                <div class="linkedin-selected-badge badge-ebtke-style">
                                    <span x-text="tag"></span>
                                    <button type="button" class="linkedin-remove-btn" @click="removeEbtke(tag)">
                                        <i class="bi bi-x-circle-fill"></i>
                                    </button>
                                    <input type="hidden" name="kategori_ebtke[]" :value="tag">
                                </div>
                            </template>
                            <span x-show="selectedEbtke.length === 0" class="text-muted small ps-1" style="font-size: 11px;">
                                Opsional: Klik tag kategori EBTKE di bawah:
                            </span>
                        </div>

                        <div class="tag-suggestions-box">
                            @foreach($kategoriEbtkeList as $itemEbtke)
                                <span class="tag-suggestion-pill"
                                      :class="{ 'selected': selectedEbtke.includes('{{ $itemEbtke }}') }"
                                      @click="addEbtke('{{ $itemEbtke }}')">
                                    <i class="bi bi-plus text-muted" x-show="!selectedEbtke.includes('{{ $itemEbtke }}')"></i>
                                    <i class="bi bi-check text-success" x-show="selectedEbtke.includes('{{ $itemEbtke }}')"></i>
                                    {{ $itemEbtke }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- 3c. Klasifikasi Area Kejadian -->
                    <div class="col-12">
                        <label class="form-label small fw-bold d-block mb-1">Klasifikasi Area Kejadian <span class="text-danger">*</span></label>
                        <input type="hidden" name="klasifikasi_area" :value="area">

                        <div class="binary-toggle-container">
                            <div class="binary-toggle-card"
                                 :class="{ 'active-panas': area === 'Kecelakaan Panas Bumi' }"
                                 @click="area = 'Kecelakaan Panas Bumi'">
                                <div class="binary-radio-indicator"></div>
                                <div>
                                    <div class="fw-bold" style="font-size: 13px;">
                                        <i class="bi bi-fire text-danger me-1"></i> Kecelakaan Panas Bumi
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Fasilitas operasional & pembangkit geotermal</small>
                                </div>
                            </div>

                            <div class="binary-toggle-card"
                                 :class="{ 'active-non-panas': area === 'Kecelakaan Non-Panas Bumi' }"
                                 @click="area = 'Kecelakaan Non-Panas Bumi'">
                                <div class="binary-radio-indicator"></div>
                                <div>
                                    <div class="fw-bold" style="font-size: 13px;">
                                        <i class="bi bi-building text-primary me-1"></i> Kecelakaan Non-Panas Bumi
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Fasilitas umum, kantor, jalan akses non-geotermal</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hari Kejadian & Tanggal Kejadian -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Hari Kejadian <span class="text-danger">*</span></label>
                        <input type="text" name="hari" id="editHari" class="form-control form-control-sm bg-light" readonly tabindex="-1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tanggal & Waktu Kejadian <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="tanggal_waktu" id="editTanggalWaktu" class="form-control form-control-sm" onchange="syncHariFromDate(this, 'editHari')" oninput="syncHariFromDate(this, 'editHari')" required>
                    </div>

                    <!-- Lokasi & Komponen -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Lokasi Kejadian <span class="text-danger">*</span></label>
                        <input type="text" name="lokasi_kejadian" id="editLokasiKejadian" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Komponen / Unit Terkait <span class="text-danger">*</span></label>
                        <input type="text" name="komponen_terkait" id="editKomponenTerkait" class="form-control form-control-sm" required>
                    </div>

                    <!-- Deskripsi -->
                    <div class="col-12">
                        <label class="form-label small fw-bold">Deskripsi Kejadian <span class="text-danger">*</span></label>
                        <textarea name="deskripsi_kejadian" id="editDeskripsiKejadian" class="form-control form-control-sm" rows="3" required></textarea>
                    </div>

                    <!-- Tindakan Perbaikan -->
                    <div class="col-12">
                        <label class="form-label small fw-bold">Tindakan Perbaikan / Korektif (CAPA)</label>
                        <textarea name="tindakan_perbaikan" id="editTindakanPerbaikan" class="form-control form-control-sm" rows="2"></textarea>
                    </div>

                    <!-- Status Laporan & Jumlah Hari Hilang (Conditional LTI) -->
                    <div :class="selectedUmum.includes('LTI (Lost Time Injury)') ? 'col-md-6' : 'col-12'">
                        <label class="form-label small fw-bold">Status Laporan <span class="text-danger">*</span></label>
                        <select name="status_laporan" id="editStatusLaporan" class="form-select form-select-sm" required>
                            <option value="Preliminary">Preliminary (Laporan Awal / Investigasi)</option>
                            <option value="Final">Final (Selesai / Closed)</option>
                        </select>
                    </div>
                    <div class="col-md-6" x-show="selectedUmum.includes('LTI (Lost Time Injury)')" x-transition x-cloak>
                        <label class="form-label small fw-bold">Jumlah Hari Hilang (LWD)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="jumlah_hari_hilang" id="editJumlahHariHilang" class="form-control form-control-sm" min="0" x-model="lwd" :disabled="!selectedUmum.includes('LTI (Lost Time Injury)')" placeholder="0">
                            <span class="input-group-text bg-light text-muted" style="font-size: 11px;">Hari</span>
                        </div>
                        <div class="form-text text-muted" style="font-size: 10px;">Hari kerja hilang karena LTI (Lost Time Injury)</div>
                    </div>

                    <!-- Dokumentasi (Multiple & Incremental Upload) -->
                    <div class="col-12">
                        <label class="form-label small fw-bold">Dokumentasi (Tambah File Investigasi / Bukti - Opsional)</label>
                        <input type="file" id="editDokumenInput" name="dokumen_investigasi[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" multiple onchange="handleIncrementalFiles(this, 'edit')">
                        <div class="form-text text-muted" style="font-size: 11px;">Dapat memilih beberapa file sekaligus atau menambahkan file secara bertahap (PDF, JPG, PNG, WEBP, DOC/DOCX, XLS/XLSX — Maks. 15MB/file)</div>
                        <div id="editFileListContainer" class="mt-2 d-flex flex-column gap-1"></div>
                        <div id="currentDocumentInfo" class="mt-2 small text-muted"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="btnSubmitEditIncident" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== 3. MODAL DETAIL INVESTIGASI ==================== -->
<div class="modal fade" id="modalDetailIncident" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-info-circle-fill text-info me-2"></i>Detail Investigasi Insiden
                    </h6>
                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                        <span id="detailNomor" class="badge bg-danger-subtle text-danger font-monospace"></span>
                        <span id="detailJudul" class="fw-semibold text-dark"></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body scrollable-modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Tanggal & Waktu:</small>
                        <strong id="detailTanggal"></strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Lokasi & Komponen:</small>
                        <strong id="detailLokasi"></strong> (<span id="detailKomponen"></span>)
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Klasifikasi Area:</small>
                        <span id="detailArea" class="badge"></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Status Laporan:</small>
                        <span id="detailStatus" class="badge"></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Penanggung Jawab (PIC):</small>
                        <strong id="detailPIC"></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Jumlah Hari Hilang (SR):</small>
                        <strong id="detailHariHilang" class="text-danger">0 Hari Kerja</strong>
                    </div>

                    <!-- Tagging Display in Detail Modal -->
                    <div class="col-md-6 border-top pt-2">
                        <small class="text-muted d-block fw-bold mb-1">Kategori Umum (Tags):</small>
                        <div id="detailTagsUmum" class="d-flex flex-wrap gap-1"></div>
                    </div>
                    <div class="col-md-6 border-top pt-2">
                        <small class="text-muted d-block fw-bold mb-1">Kategori EBTKE (Tags):</small>
                        <div id="detailTagsEbtke" class="d-flex flex-wrap gap-1"></div>
                    </div>

                    <div class="col-12 border-top pt-3">
                        <small class="text-muted d-block fw-bold mb-1">Deskripsi & Kronologi Kejadian:</small>
                        <div id="detailDeskripsi" class="p-3 bg-light rounded text-dark" style="white-space: pre-wrap;"></div>
                    </div>

                    <div class="col-12">
                        <small class="text-muted d-block fw-bold mb-1">Tindakan Perbaikan / Mitigasi (CAPA):</small>
                        <div id="detailTindakan" class="p-3 bg-light rounded text-dark" style="white-space: pre-wrap;"></div>
                    </div>

                    <div class="col-12 border-top pt-3" id="detailDokumenContainer">
                        <small class="text-muted d-block fw-bold mb-1">Dokumen Lampiran / Foto Bukti:</small>
                        <div id="detailDokumenLink"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== 4. MODAL POP-UP RINCIAN INSIDEN DARI GRAFIK (DRILLDOWN) ==================== -->
<div class="modal fade" id="modalChartDetailIncidents" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                <!-- SISI KIRI: Icon & Judul & Badge Kategori -->
                <div class="d-flex align-items-center gap-2 overflow-hidden me-3">
                    <div id="chartModalIconBox" class="brand-mark text-white flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 16px; border-radius: 8px;">
                        <i id="chartModalIcon" class="bi bi-pie-chart-fill"></i>
                    </div>
                    <div class="d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <span class="fw-bold text-dark fs-6 mb-0">Rincian Insiden:</span>
                            <span id="chartModalCategoryTitle" class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fs-6"></span>
                        </div>
                        <small id="chartModalCategorySubtitle" class="text-muted" style="font-size: 11px; margin-top: 1px;"></small>
                    </div>
                </div>
                <!-- SISI KANAN: Counter Badge & Tombol Close (X) -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span id="chartModalTotalBadge" class="modal-badge-counter">0 Insiden</span>
                    <button type="button" class="btn-close ms-1" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- SEARCH / FILTER DALAM MODAL -->
            <div class="px-3 pt-3 pb-2 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="input-group input-group-sm" style="max-width: 320px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="chartModalSearchInput" class="form-control border-start-0" placeholder="Filter di dalam rincian..." oninput="filterChartModalTable()">
                </div>
                <small class="text-muted" style="font-size: 11px;">
                    <i class="bi bi-info-circle me-1"></i>Menampilkan seluruh insiden yang terasosiasi dengan kategori ini
                </small>
            </div>

            <div class="modal-body scrollable-modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 45px;">No</th>
                                <th>No. Insiden | Judul</th>
                                <th>Tanggal & Waktu</th>
                                <th>Klasifikasi Area</th>
                                <th>Kategori Terkait</th>
                                <th>Lokasi & Unit</th>
                                <th class="text-center">Hari Hilang</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 90px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="chartModalTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <span id="chartModalShowingText" class="text-muted small">Menampilkan 0 insiden</span>
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== 5. MODAL KONFIRMASI HAPUS ==================== -->
<div class="modal fade" id="modalKonfirmasiHapus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-3">
            <div class="modal-body pb-2">
                <div class="text-danger mb-3">
                    <i class="bi bi-exclamation-triangle fs-1"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Hapus Data Insiden?</h6>
                <p class="text-muted small mb-2">
                    Laporan insiden dengan nomor berikut akan dihapus permanen:
                </p>
                <div class="p-2 bg-light rounded border mb-2">
                    <div id="deleteIncidentNumber" class="fw-bold text-danger font-monospace"></div>
                    <div id="deleteIncidentLocation" class="text-muted small"></div>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-2">
                <form id="formHapusIncident" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-light btn-sm px-3 me-2" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitHapusIncident" class="btn btn-danger btn-sm px-3">
                        <i class="bi bi-trash me-1"></i> Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    // Sinkronisasi otomatis kolom Hari Kejadian (Read-Only) dari input Tanggal & Waktu Kejadian
    function syncHariFromDate(input, targetSelectId) {
        if (!input || !input.value) return;
        const datePart = String(input.value).split('T')[0].split(' ')[0];
        const parts = datePart.split('-').map(Number);
        let d;
        if (parts.length === 3 && !parts.some(isNaN)) {
            d = new Date(parts[0], parts[1] - 1, parts[2]);
        } else {
            d = new Date(input.value);
        }
        if (isNaN(d.getTime())) return;
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const dayName = days[d.getDay()];
        const sel = document.getElementById(targetSelectId);
        if (sel) {
            sel.value = dayName;
        }
    }

    // State & Utilitas Multiple + Incremental File Upload (Append Files)
    const stagedFilesStore = {
        tambah: [],
        edit: []
    };

    function formatFileSize(bytes) {
        const b = Number(bytes) || 0;
        if (b <= 0) return '';
        if (b < 1024) return b + ' B';
        if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
        return (b / 1048576).toFixed(2) + ' MB';
    }

    function getFileIconClass(fileNameOrExt) {
        const ext = String(fileNameOrExt || '').split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'].includes(ext)) return 'bi-file-earmark-image text-primary';
        if (ext === 'pdf') return 'bi-file-earmark-pdf text-danger';
        if (['doc', 'docx'].includes(ext)) return 'bi-file-earmark-word text-info';
        if (['xls', 'xlsx', 'csv'].includes(ext)) return 'bi-file-earmark-excel text-success';
        return 'bi-file-earmark-text text-secondary';
    }

    function syncStagedFilesToInput(mode) {
        const inputId = mode === 'edit' ? 'editDokumenInput' : 'tambahDokumenInput';
        const inputEl = document.getElementById(inputId);
        if (!inputEl || typeof DataTransfer === 'undefined') return;
        const dt = new DataTransfer();
        (stagedFilesStore[mode] || []).forEach(file => dt.items.add(file));
        inputEl.files = dt.files;
    }

    function handleIncrementalFiles(inputEl, mode) {
        if (!inputEl || !inputEl.files) return;
        const incoming = Array.from(inputEl.files);
        if (!stagedFilesStore[mode]) stagedFilesStore[mode] = [];

        incoming.forEach(file => {
            const isDuplicate = stagedFilesStore[mode].some(existing =>
                existing.name === file.name &&
                existing.size === file.size &&
                existing.lastModified === file.lastModified
            );
            if (!isDuplicate) {
                stagedFilesStore[mode].push(file);
            }
        });

        syncStagedFilesToInput(mode);
        renderStagedFileList(mode);
    }

    function removeStagedFile(mode, index) {
        if (!stagedFilesStore[mode]) return;
        stagedFilesStore[mode].splice(index, 1);
        syncStagedFilesToInput(mode);
        renderStagedFileList(mode);
    }

    function clearStagedFiles(mode) {
        stagedFilesStore[mode] = [];
        syncStagedFilesToInput(mode);
        renderStagedFileList(mode);
    }

    function escapeIncidentHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderStagedFileList(mode) {
        const containerId = mode === 'edit' ? 'editFileListContainer' : 'tambahFileListContainer';
        const container = document.getElementById(containerId);
        if (!container) return;

        const files = stagedFilesStore[mode] || [];
        if (files.length === 0) {
            container.innerHTML = '';
            return;
        }

        let html = `<div class="small fw-semibold text-secondary mb-1"><i class="bi bi-paperclip me-1"></i>Daftar File Akan Diunggah (${files.length} file):</div>`;
        files.forEach((file, idx) => {
            const iconCls = getFileIconClass(file.name);
            const safeName = escapeIncidentHtml(file.name);
            const sizeBadge = file.size ? `<span class="badge bg-white text-muted border flex-shrink-0" style="font-size: 10px;">${formatFileSize(file.size)}</span>` : '';
            html += `
                <div class="d-flex align-items-center justify-content-between px-2 py-1 bg-light border rounded" style="font-size: 12px;">
                    <div class="d-flex align-items-center gap-2 text-truncate pe-2">
                        <i class="bi ${iconCls} fs-6 flex-shrink-0"></i>
                        <span class="text-dark fw-medium text-truncate" title="${safeName}">${safeName}</span>
                        ${sizeBadge}
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2 flex-shrink-0" onclick="removeStagedFile('${mode}', ${idx})" title="Hapus file dari daftar unggahan">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function buildStoredDocumentsHtml(inc, showImagePreviews = false) {
        let docs = Array.isArray(inc.documents_list) ? inc.documents_list : [];
        if (docs.length === 0 && inc.dokumen_investigasi) {
            const name = String(inc.dokumen_investigasi).split('/').pop();
            const ext = name.split('.').pop().toLowerCase();
            docs = [{
                original_name: name,
                file_path: inc.dokumen_investigasi,
                file_url: inc.id ? `/incident/${inc.id}/legacy-document` : ('/storage/' + String(inc.dokumen_investigasi).replace(/^\/+/, '')),
                extension: ext,
                size: 0
            }];
        }
        if (docs.length === 0) {
            return '';
        }

        let html = `<div class="d-flex flex-column gap-1">`;
        let previewsHtml = '';
        docs.forEach(doc => {
            const fileName = doc.original_name || doc.file_name || String(doc.file_path || '').split('/').pop();
            const safeName = escapeIncidentHtml(fileName);
            const rawUrl = doc.file_url || (doc.id ? `/incident/documents/${doc.id}/view` : '/incident/' + inc.id + '/legacy-document');
            const safeUrl = escapeIncidentHtml(rawUrl);
            const ext = String(doc.extension || fileName.split('.').pop() || '').toLowerCase();
            const iconCls = getFileIconClass(ext);
            const sizeStr = formatFileSize(doc.size);
            const sizeBadge = sizeStr ? `<span class="badge bg-white text-muted border flex-shrink-0" style="font-size: 10px;">${sizeStr}</span>` : '';

            html += `
                <div class="d-flex align-items-center justify-content-between px-2 py-1 bg-light border rounded" style="font-size: 12px;">
                    <div class="d-flex align-items-center gap-2 text-truncate pe-2">
                        <i class="bi ${iconCls} fs-6 flex-shrink-0"></i>
                        <a href="${safeUrl}" target="_blank" class="text-primary fw-medium text-decoration-none text-truncate" title="${safeName}">${safeName}</a>
                        ${sizeBadge}
                    </div>
                    <a href="${safeUrl}" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2 flex-shrink-0" style="font-size: 11px;">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Buka
                    </a>
                </div>
            `;

            if (showImagePreviews && ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'].includes(ext)) {
                previewsHtml += `
                    <a href="${safeUrl}" target="_blank" class="d-inline-block">
                        <img src="${safeUrl}" alt="${safeName}" class="img-fluid rounded border" style="max-height: 160px; object-fit: cover;">
                    </a>
                `;
            }
        });
        html += `</div>`;
        if (previewsHtml) {
            html += `<div class="mt-2 d-flex flex-wrap gap-2">${previewsHtml}</div>`;
        }
        return html;
    }

    // Alpine Handler Form Tambah Insiden
    function incidentFormHandler(initial = {}) {
        return {
            area: initial.area || 'Kecelakaan Panas Bumi',
            selectedUmum: initial.umum || [],
            selectedEbtke: initial.ebtke || [],
            lwd: initial.lwd ?? '',

            addUmum(tag) {
                if (!this.selectedUmum.includes(tag)) {
                    this.selectedUmum.push(tag);
                }
            },
            removeUmum(tag) {
                this.selectedUmum = this.selectedUmum.filter(t => t !== tag);
                if (tag === 'LTI (Lost Time Injury)') {
                    this.lwd = '';
                }
            },
            addEbtke(tag) {
                if (!this.selectedEbtke.includes(tag)) {
                    this.selectedEbtke.push(tag);
                }
            },
            removeEbtke(tag) {
                this.selectedEbtke = this.selectedEbtke.filter(t => t !== tag);
            }
        };
    }

    // Global reference untuk sinkronisasi Alpine edit form
    let activeEditAlpine = null;

    function incidentEditHandler() {
        return {
            area: 'Kecelakaan Panas Bumi',
            selectedUmum: [],
            selectedEbtke: [],
            lwd: '',

            init() {
                activeEditAlpine = this;
            },
            addUmum(tag) {
                if (!this.selectedUmum.includes(tag)) {
                    this.selectedUmum.push(tag);
                }
            },
            removeUmum(tag) {
                this.selectedUmum = this.selectedUmum.filter(t => t !== tag);
                if (tag === 'LTI (Lost Time Injury)') {
                    this.lwd = '';
                }
            },
            addEbtke(tag) {
                if (!this.selectedEbtke.includes(tag)) {
                    this.selectedEbtke.push(tag);
                }
            },
            removeEbtke(tag) {
                this.selectedEbtke = this.selectedEbtke.filter(t => t !== tag);
            }
        };
    }

    // Modal Edit Insiden
    function openEditModal(inc) {
        const form = document.getElementById('formEditIncident');
        form.action = `/incident/${inc.id}`;

        clearStagedFiles('edit');

        document.getElementById('editNomorInsiden').value = inc.nomor_insiden || '';
        if (document.getElementById('editJudulInsiden')) {
            document.getElementById('editJudulInsiden').value = inc.title || '';
        }

        let rawDate = inc.tanggal_waktu_raw || inc.tanggal_waktu || '';
        if (rawDate && rawDate.length >= 16 && (rawDate.includes('T') || rawDate.includes('-'))) {
            document.getElementById('editTanggalWaktu').value = rawDate.replace(' ', 'T').substring(0, 16);
        } else {
            document.getElementById('editTanggalWaktu').value = '';
        }

        if (document.getElementById('editHari')) {
            if (rawDate && (rawDate.includes('T') || rawDate.includes('-'))) {
                syncHariFromDate({ value: rawDate.replace(' ', 'T') }, 'editHari');
            } else if (inc.hari) {
                document.getElementById('editHari').value = inc.hari;
            }
        }

        document.getElementById('editLokasiKejadian').value = inc.lokasi_kejadian || '';
        document.getElementById('editKomponenTerkait').value = inc.komponen_terkait || '';
        document.getElementById('editDeskripsiKejadian').value = inc.deskripsi_kejadian || '';
        document.getElementById('editTindakanPerbaikan').value = inc.tindakan_perbaikan || '';
        if (document.getElementById('editStatusLaporan')) {
            let st = inc.status_laporan || 'Preliminary';
            if (st === 'Selesai' || st === 'Final') {
                st = 'Final';
            } else {
                st = 'Preliminary';
            }
            document.getElementById('editStatusLaporan').value = st;
        }

        // Parsing tags umum & EBTKE (Filter Hazard Report jika ada pada data lama)
        let rawUmum = inc.kategori_umum;
        if (typeof rawUmum === 'string') {
            try { rawUmum = JSON.parse(rawUmum); } catch(e) { rawUmum = [rawUmum]; }
        }
        if (!Array.isArray(rawUmum)) rawUmum = [];
        rawUmum = rawUmum.filter(t => t !== 'Hazard Report');

        let rawEbtke = inc.kategori_ebtke;
        if (typeof rawEbtke === 'string') {
            try { rawEbtke = JSON.parse(rawEbtke); } catch(e) { rawEbtke = [rawEbtke]; }
        }
        if (!Array.isArray(rawEbtke)) rawEbtke = [];

        const hasLti = rawUmum.includes('LTI (Lost Time Injury)');
        const lwdVal = (hasLti && inc.jumlah_hari_hilang !== null && inc.jumlah_hari_hilang !== undefined)
            ? inc.jumlah_hari_hilang
            : '';

        if (document.getElementById('editJumlahHariHilang')) {
            document.getElementById('editJumlahHariHilang').value = lwdVal;
        }

        // Sinkronkan ke Alpine component
        if (activeEditAlpine) {
            const isNonPanas = (inc.klasifikasi_area || '').includes('Non');
            activeEditAlpine.area = isNonPanas ? 'Kecelakaan Non-Panas Bumi' : 'Kecelakaan Panas Bumi';
            activeEditAlpine.selectedUmum = [...rawUmum];
            activeEditAlpine.selectedEbtke = [...rawEbtke];
            activeEditAlpine.lwd = lwdVal;
        }

        const docInfo = document.getElementById('currentDocumentInfo');
        const storedHtml = buildStoredDocumentsHtml(inc, false);
        if (storedHtml) {
            docInfo.innerHTML = `<div class="fw-semibold text-secondary mb-1"><i class="bi bi-folder-check me-1"></i>File Tersimpan Saat Ini:</div>` + storedHtml;
        } else {
            docInfo.innerHTML = 'Belum ada dokumen yang diunggah.';
        }

        const modal = new bootstrap.Modal(document.getElementById('modalEditIncident'));
        modal.show();
    }

    // Modal Detail Investigasi
    function openDetailModal(inc) {
        document.getElementById('detailNomor').textContent = inc.nomor_insiden || '-';
        if (document.getElementById('detailJudul')) {
            document.getElementById('detailJudul').textContent = inc.title ? ('— ' + inc.title) : '';
        }
        let tgl = inc.tanggal_waktu || '-';
        if (tgl.includes('T')) {
            tgl = tgl.replace('T', ' ').substring(0, 16) + ' WIB';
        } else if (!tgl.includes('WIB') && tgl !== '-') {
            tgl = tgl + ' WIB';
        }
        if (inc.hari && !tgl.startsWith(inc.hari)) {
            tgl = inc.hari + ', ' + tgl;
        }
        document.getElementById('detailTanggal').textContent = tgl;
        document.getElementById('detailLokasi').textContent = inc.lokasi_kejadian || '-';
        document.getElementById('detailKomponen').textContent = inc.komponen_terkait || '-';

        const areaSpan = document.getElementById('detailArea');
        const areaText = inc.klasifikasi_area || 'Kecelakaan Panas Bumi';
        areaSpan.textContent = areaText;
        areaSpan.className = areaText.includes('Non') ? 'badge badge-non-panas' : 'badge badge-panas-bumi';

        document.getElementById('detailDeskripsi').textContent = inc.deskripsi_kejadian || '-';
        document.getElementById('detailTindakan').textContent = inc.tindakan_perbaikan || 'Belum ada catatan tindakan perbaikan.';
        if (document.getElementById('detailPIC')) {
            document.getElementById('detailPIC').textContent = inc.penanggung_jawab || '-';
        }
        if (document.getElementById('detailTarget')) {
            document.getElementById('detailTarget').textContent = inc.target_penyelesaian ? inc.target_penyelesaian.substring(0, 10) : '-';
        }
        document.getElementById('detailHariHilang').textContent = (inc.jumlah_hari_hilang ?? 0) + ' Hari Kerja';

        // Render Tags di Detail Modal
        const tagsUmumBox = document.getElementById('detailTagsUmum');
        tagsUmumBox.innerHTML = '';
        let rawUmum = inc.kategori_umum;
        if (typeof rawUmum === 'string') {
            try { rawUmum = JSON.parse(rawUmum); } catch(e) { rawUmum = [rawUmum]; }
        }
        if (Array.isArray(rawUmum) && rawUmum.length > 0) {
            rawUmum.forEach(t => {
                const cls = t === 'Fatality' ? 'tag-pill-fatality' : 'tag-pill-umum';
                tagsUmumBox.innerHTML += `<span class="tag-pill-sm ${cls}">${escapeIncidentHtml(t)}</span>`;
            });
        } else {
            tagsUmumBox.innerHTML = '<span class="text-muted small">-</span>';
        }

        const tagsEbtkeBox = document.getElementById('detailTagsEbtke');
        tagsEbtkeBox.innerHTML = '';
        let rawEbtke = inc.kategori_ebtke;
        if (typeof rawEbtke === 'string') {
            try { rawEbtke = JSON.parse(rawEbtke); } catch(e) { rawEbtke = [rawEbtke]; }
        }
        if (Array.isArray(rawEbtke) && rawEbtke.length > 0) {
            rawEbtke.forEach(t => {
                const cls = t === 'Kecelakaan Mati' ? 'tag-pill-mati' : 'tag-pill-ebtke';
                tagsEbtkeBox.innerHTML += `<span class="tag-pill-sm ${cls}">${escapeIncidentHtml(t)}</span>`;
            });
        } else {
            tagsEbtkeBox.innerHTML = '<span class="text-muted small">-</span>';
        }

        const statusBadge = document.getElementById('detailStatus');
        const isFinal = inc.status_laporan === 'Final' || inc.status_laporan === 'Selesai';
        statusBadge.textContent = isFinal ? 'Final' : 'Preliminary';
        statusBadge.className = isFinal ? 'badge bg-success' : 'badge bg-warning text-dark';

        const docContainer = document.getElementById('detailDokumenContainer');
        const docLink = document.getElementById('detailDokumenLink');
        const storedDocsHtml = buildStoredDocumentsHtml(inc, true);
        docContainer.style.display = 'block';
        if (storedDocsHtml) {
            docLink.innerHTML = storedDocsHtml;
        } else {
            docLink.innerHTML = '<span class="text-muted small">Tidak ada dokumen lampiran.</span>';
        }

        const modal = new bootstrap.Modal(document.getElementById('modalDetailIncident'));
        modal.show();
    }

    // Modal Konfirmasi Hapus
    function openDeleteConfirmation(id, nomor, lokasi) {
        const form = document.getElementById('formHapusIncident');
        form.action = `/incident/${id}`;

        document.getElementById('deleteIncidentNumber').textContent = nomor;
        document.getElementById('deleteIncidentLocation').textContent = lokasi;

        const modal = new bootstrap.Modal(document.getElementById('modalKonfirmasiHapus'));
        modal.show();
    }

    // Data Lengkap Insiden untuk Interaktivitas Modal Grafik
    const allIncidentsData = @json($chartDetailIncidents);
    let currentFilteredModalData = [];

    // Fungsi Buka Modal Detail dari Klik Grafik
    function openChartDetailModal(chartType, selectedLabel) {
        if (!selectedLabel || selectedLabel === 'Belum ada data' || selectedLabel === 'Belum ada tag') return;
        selectedLabel = String(selectedLabel).replace(/\s*\(\d+\)$/, '');

        let iconClass = 'bi-pie-chart-fill';
        let iconBg = 'background: #ea580c;';
        let subtitle = '';

        if (chartType === 'area') {
            iconClass = 'bi-fire';
            const isPanas = selectedLabel.includes('Panas') && !selectedLabel.includes('Non');
            iconBg = isPanas ? 'background: #ea580c;' : 'background: #0284c7;';
            subtitle = 'Insiden berdasarkan Klasifikasi Area Kejadian';
            currentFilteredModalData = allIncidentsData.filter(inc => {
                const area = (inc.klasifikasi_area || '');
                return isPanas ? (!area.includes('Non') && area.includes('Panas')) : area.includes('Non');
            });
        } else if (chartType === 'umum') {
            iconClass = 'bi-tags-fill';
            iconBg = 'background: #0d6efd;';
            subtitle = 'Insiden yang terasosiasi dengan Tag Kategori Umum terpilih';
            currentFilteredModalData = allIncidentsData.filter(inc => inc.kategori_umum && inc.kategori_umum.includes(selectedLabel));
        } else if (chartType === 'ebtke') {
            iconClass = 'bi-award-fill';
            iconBg = 'background: #198754;';
            subtitle = 'Insiden yang terasosiasi dengan Tag Kategori EBTKE (ESDM) terpilih';
            currentFilteredModalData = allIncidentsData.filter(inc => inc.kategori_ebtke && inc.kategori_ebtke.includes(selectedLabel));
        }

        document.getElementById('chartModalIcon').className = 'bi ' + iconClass;
        document.getElementById('chartModalIconBox').style = iconBg + ' width: 36px; height: 36px; font-size: 16px; border-radius: 8px;';
        document.getElementById('chartModalCategoryTitle').textContent = selectedLabel;
        document.getElementById('chartModalCategorySubtitle').textContent = subtitle;
        document.getElementById('chartModalTotalBadge').textContent = currentFilteredModalData.length + ' Insiden';
        document.getElementById('chartModalSearchInput').value = '';

        renderChartModalTable(currentFilteredModalData);

        const modal = new bootstrap.Modal(document.getElementById('modalChartDetailIncidents'));
        modal.show();
    }

    function renderChartModalTable(data) {
        const tbody = document.getElementById('chartModalTableBody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                        Tidak ada insiden yang terasosiasi dengan kategori ini.
                    </td>
                </tr>
            `;
            document.getElementById('chartModalShowingText').textContent = 'Menampilkan 0 insiden';
            return;
        }

        data.forEach((item, index) => {
            const isFinal = item.status_laporan === 'Final' || item.status_laporan === 'Selesai';
            const statusBadge = isFinal
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Final</span>'
                : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Preliminary</span>';

            const areaBadge = (item.klasifikasi_area || '').includes('Non')
                ? '<span class="badge-non-panas"><i class="bi bi-building me-1"></i>Non-Panas</span>'
                : '<span class="badge-panas-bumi"><i class="bi bi-fire me-1"></i>Panas Bumi</span>';

            let tagsHtml = '';
            if (item.kategori_umum && item.kategori_umum.length > 0) {
                item.kategori_umum.forEach(t => {
                    tagsHtml += `<span class="tag-pill-sm ${t === 'Fatality' ? 'tag-pill-fatality' : 'tag-pill-umum'}">${t}</span>`;
                });
            }
            if (item.kategori_ebtke && item.kategori_ebtke.length > 0) {
                item.kategori_ebtke.forEach(t => {
                    tagsHtml += `<span class="tag-pill-sm ${t === 'Kecelakaan Mati' ? 'tag-pill-mati' : 'tag-pill-ebtke'}">${t}</span>`;
                });
            }
            if (!tagsHtml) tagsHtml = '<span class="text-muted small">-</span>';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center text-muted fw-semibold">${index + 1}</td>
                <td>
                    <span class="badge bg-danger-subtle text-danger font-monospace border border-danger-subtle">${item.nomor_insiden}</span>
                    <div class="fw-semibold text-dark mt-1">${item.title || '-'}</div>
                </td>
                <td class="text-nowrap">
                    <div class="fw-semibold text-secondary">${item.hari || ''}</div>
                    <i class="bi bi-calendar-event text-muted me-1"></i>${item.tanggal_waktu}
                </td>
                <td>${areaBadge}</td>
                <td style="max-width: 200px;"><div class="d-flex flex-wrap gap-1">${tagsHtml}</div></td>
                <td>
                    <div class="fw-bold text-dark"><i class="bi bi-geo-alt text-danger me-1"></i>${item.lokasi_kejadian}</div>
                    <small class="text-muted">Unit: ${item.komponen_terkait}</small>
                </td>
                <td class="text-center">
                    <span class="badge ${item.jumlah_hari_hilang > 0 ? 'bg-danger text-white' : 'bg-light text-muted border'}">
                        ${item.jumlah_hari_hilang} Hari
                    </span>
                </td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-2" onclick="openDetailModalFromChart(${item.id})" title="Buka Detail Investigasi">
                        <i class="bi bi-eye"></i> Detail
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        document.getElementById('chartModalShowingText').textContent = `Menampilkan ${data.length} insiden`;
    }

    function filterChartModalTable() {
        const q = document.getElementById('chartModalSearchInput').value.toLowerCase().trim();
        if (!q) {
            renderChartModalTable(currentFilteredModalData);
            return;
        }
        const filtered = currentFilteredModalData.filter(item => {
            const no = (item.nomor_insiden || '').toLowerCase();
            const title = (item.title || '').toLowerCase();
            const loc = (item.lokasi_kejadian || '').toLowerCase();
            const pic = (item.penanggung_jawab || '').toLowerCase();
            const desc = (item.deskripsi_kejadian || '').toLowerCase();
            const umum = (item.kategori_umum || []).join(' ').toLowerCase();
            const ebtke = (item.kategori_ebtke || []).join(' ').toLowerCase();
            return no.includes(q) || title.includes(q) || loc.includes(q) || pic.includes(q) || desc.includes(q) || umum.includes(q) || ebtke.includes(q);
        });
        renderChartModalTable(filtered);
    }

    function openDetailModalFromChart(id) {
        const item = allIncidentsData.find(x => x.id === id);
        if (item) {
            const chartModalEl = document.getElementById('modalChartDetailIncidents');
            const chartModalInstance = bootstrap.Modal.getInstance(chartModalEl);
            if (chartModalInstance) {
                chartModalInstance.hide();
            }
            setTimeout(() => {
                openDetailModal(item);
            }, 300);
        }
    }

    // AJAX Form Submit Handler dengan SweetAlert2 (Tambah, Edit, Hapus)
    function bindAjaxIncidentForm(formId, submitBtnId, successTitle) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            if (typeof Swal === 'undefined' || typeof fetch === 'undefined') {
                return; // Fallback ke submit normal jika SweetAlert2 tidak tersedia
            }
            e.preventDefault();

            const btn = document.getElementById(submitBtnId);
            const originalHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
            }

            try {
                const fileMode = formId === 'formEditIncident' ? 'edit' : (formId === 'formTambahIncident' ? 'tambah' : null);
                if (fileMode) {
                    syncStagedFilesToInput(fileMode);
                }
                const formData = new FormData(form);
                if (fileMode && stagedFilesStore[fileMode] && stagedFilesStore[fileMode].length > 0) {
                    formData.delete('dokumen_investigasi[]');
                    stagedFilesStore[fileMode].forEach(file => {
                        formData.append('dokumen_investigasi[]', file);
                    });
                }
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const modalEl = form.closest('.modal');
                    if (modalEl) {
                        const modalInst = bootstrap.Modal.getInstance(modalEl);
                        if (modalInst) modalInst.hide();
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: successTitle,
                        text: result.message || 'Data insiden berhasil disimpan.',
                        confirmButtonColor: '#dc2626',
                        timer: 1800,
                        showConfirmButton: true
                    });

                    window.location.reload();
                } else {
                    let errorMsg = result.message || 'Terjadi kesalahan saat memproses data.';
                    if (result.errors) {
                        errorMsg = Object.values(result.errors).flat().join('\n');
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan Data',
                        text: errorMsg,
                        confirmButtonColor: '#dc2626'
                    });
                }
            } catch (err) {
                form.submit();
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        });
    }

    // ==================== LINE CHART TREN KECELAKAAN KERJA & HIGHLIGHT CARD ====================
    let trendKecelakaanChartInstance = null;
    let activeTrendKlasifikasiState = @json($trendInit);

    function syncTrendControlVisibility(mode) {
        const monthSel = document.getElementById('trendMonthSelect');
        const yearSel = document.getElementById('trendYearSelect');
        if (monthSel) {
            monthSel.classList.toggle('d-none', mode !== 'harian');
        }
        if (yearSel) {
            yearSel.classList.toggle('d-none', mode === 'tahunan');
        }
    }

    function applyTrendKlasifikasiData(data) {
        if (!data) return;
        activeTrendKlasifikasiState = data;

        const highlightValEl = document.getElementById('highlightTotalPanasBumiValue');
        if (highlightValEl) {
            highlightValEl.textContent = Number(data.highlightTotal ?? 0);
        }

        const highlightPeriodEl = document.getElementById('highlightPeriodText');
        if (highlightPeriodEl && data.periodSubtitle) {
            highlightPeriodEl.textContent = data.periodSubtitle;
        }

        const badgePeriodEl = document.getElementById('trendActivePeriodBadge');
        if (badgePeriodEl && data.periodSubtitle) {
            badgePeriodEl.textContent = data.periodSubtitle;
        }

        const legPanasEl = document.getElementById('trendLegendPanasCount');
        if (legPanasEl) {
            legPanasEl.textContent = `(${Number(data.totalPanasBumi ?? data.highlightTotal ?? 0)})`;
        }

        const legNonPanasEl = document.getElementById('trendLegendNonPanasCount');
        if (legNonPanasEl) {
            legNonPanasEl.textContent = `(${Number(data.totalNonPanasBumi ?? 0)})`;
        }

        if (trendKecelakaanChartInstance) {
            trendKecelakaanChartInstance.data.labels = data.labels || [];
            trendKecelakaanChartInstance.data.datasets[0].data = data.panasBumi || [];
            trendKecelakaanChartInstance.data.datasets[1].data = data.nonPanasBumi || [];
            trendKecelakaanChartInstance.update();
        }
    }

    async function onTrendPeriodControlChange() {
        const modeEl = document.getElementById('trendModeSelect');
        const monthEl = document.getElementById('trendMonthSelect');
        const yearEl = document.getElementById('trendYearSelect');

        const mode = modeEl ? modeEl.value : 'bulanan';
        const month = monthEl ? monthEl.value : String(activeTrendKlasifikasiState?.month || (new Date().getMonth() + 1));
        const year = yearEl ? yearEl.value : String(activeTrendKlasifikasiState?.year || new Date().getFullYear());

        syncTrendControlVisibility(mode);

        try {
            const url = new URL('{{ route('incident.index') }}', window.location.origin);
            url.searchParams.set('trend_mode', mode);
            url.searchParams.set('trend_year', year);
            url.searchParams.set('trend_month', month);

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) return;
            const result = await response.json();
            if (result && result.trendKlasifikasi) {
                applyTrendKlasifikasiData(result.trendKlasifikasi);
            }
        } catch (err) {
            // Fallback tetap aman tanpa me-reload halaman
        }
    }

    // Inisialisasi Grafik & Chart.js Interaktif + AJAX Form Listeners
    document.addEventListener('DOMContentLoaded', function() {
        bindAjaxIncidentForm('formTambahIncident', 'btnSubmitTambahIncident', 'Laporan Insiden Tersimpan!');
        bindAjaxIncidentForm('formEditIncident', 'btnSubmitEditIncident', 'Perubahan Tersimpan!');
        bindAjaxIncidentForm('formHapusIncident', 'btnSubmitHapusIncident', 'Laporan Dihapus!');

        // 0. Inisialisasi Line Chart Tren Kecelakaan Kerja (Panas Bumi vs Non-Panas Bumi)
        const INCIDENT_MONTH_SHORT = {
            Januari: 'Jan', Februari: 'Feb', Maret: 'Mar', April: 'Apr',
            Mei: 'Mei', Juni: 'Jun', Juli: 'Jul', Agustus: 'Agu',
            September: 'Sep', Oktober: 'Okt', November: 'Nov', Desember: 'Des'
        };

        function getIncidentTrendAxisConfig() {
            const isMobile = window.innerWidth < 576;
            const isCompact = window.innerWidth < 768;
            return {
                isMobile,
                isCompact,
                maxTicksLimit: isMobile ? 6 : (isCompact ? 8 : undefined),
                autoSkipPadding: isMobile ? 10 : 6
            };
        }

        const ctxTrendKecelakaan = document.getElementById('chartTrenKecelakaanCanvas');
        if (ctxTrendKecelakaan && activeTrendKlasifikasiState) {
            syncTrendControlVisibility(activeTrendKlasifikasiState.mode || 'bulanan');
            const axCfg = getIncidentTrendAxisConfig();

            trendKecelakaanChartInstance = new Chart(ctxTrendKecelakaan, {
                type: 'line',
                data: {
                    labels: activeTrendKlasifikasiState.labels || [],
                    datasets: [
                        {
                            label: 'Panas Bumi',
                            data: activeTrendKlasifikasiState.panasBumi || [],
                            borderColor: '#ea580c',
                            backgroundColor: 'rgba(234, 88, 12, 0.12)',
                            pointBackgroundColor: '#ea580c',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: axCfg.isMobile ? 3 : 4.5,
                            pointHoverRadius: 6.5,
                            pointHitRadius: axCfg.isMobile ? 12 : 6,
                            pointHoverBackgroundColor: '#ea580c',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 2,
                            borderWidth: 2.5,
                            tension: 0.3,
                            fill: false
                        },
                        {
                            label: 'Non-Panas Bumi',
                            data: activeTrendKlasifikasiState.nonPanasBumi || [],
                            borderColor: '#0284c7',
                            backgroundColor: 'rgba(2, 132, 199, 0.10)',
                            pointBackgroundColor: '#0284c7',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: axCfg.isMobile ? 3 : 4.5,
                            pointHoverRadius: 6.5,
                            pointHitRadius: axCfg.isMobile ? 12 : 6,
                            pointHoverBackgroundColor: '#0284c7',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 2,
                            borderWidth: 2.5,
                            tension: 0.3,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    layout: {
                        padding: { top: 6, right: 10, bottom: 2, left: 4 }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    size: axCfg.isMobile ? 10 : 11,
                                    weight: '500'
                                },
                                maxRotation: 0,
                                minRotation: 0,
                                autoSkip: axCfg.isCompact,
                                autoSkipPadding: axCfg.autoSkipPadding,
                                maxTicksLimit: axCfg.maxTicksLimit,
                                callback: function(val) {
                                    const raw = this.getLabelForValue(val);
                                    if (window.innerWidth < 768 && INCIDENT_MONTH_SHORT[raw]) {
                                        return INCIDENT_MONTH_SHORT[raw];
                                    }
                                    return raw;
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grace: '10%',
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                color: '#64748b',
                                precision: 0,
                                stepSize: 1,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#ffffff',
                            bodyColor: '#f8fafc',
                            borderColor: 'rgba(255, 255, 255, 0.15)',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 8,
                            boxPadding: 5,
                            usePointStyle: true,
                            callbacks: {
                                title: function(items) {
                                    if (!items || items.length === 0) return '';
                                    const idx = items[0].dataIndex;
                                    const tLabels = activeTrendKlasifikasiState?.tooltipLabels || [];
                                    return tLabels[idx] || items[0].label || '';
                                },
                                label: function(context) {
                                    const val = context.parsed.y ?? 0;
                                    return ` ${context.dataset.label}: ${val} Kejadian`;
                                }
                            }
                        }
                    }
                }
            });

            let incResizeTimer = null;
            window.addEventListener('resize', () => {
                clearTimeout(incResizeTimer);
                incResizeTimer = setTimeout(() => {
                    if (!trendKecelakaanChartInstance) return;
                    const cfg = getIncidentTrendAxisConfig();
                    const xTicks = trendKecelakaanChartInstance.options.scales.x.ticks;
                    xTicks.autoSkip = cfg.isCompact;
                    xTicks.autoSkipPadding = cfg.autoSkipPadding;
                    xTicks.maxTicksLimit = cfg.maxTicksLimit;
                    xTicks.font.size = cfg.isMobile ? 10 : 11;
                    trendKecelakaanChartInstance.data.datasets.forEach(ds => {
                        ds.pointRadius = cfg.isMobile ? 3 : 4.5;
                        ds.pointHitRadius = cfg.isMobile ? 12 : 6;
                    });
                    trendKecelakaanChartInstance.update('none');
                }, 150);
            });
        }

        const chartAreaConfig = @json($chartArea);
        const chartUmumConfig = @json($chartUmum);
        const chartEbtkeConfig = @json($chartEbtke);

        // Plugin Center Text untuk Donut Chart
        const donutCenterTextPlugin = {
            id: 'donutCenterText',
            afterDraw(chart) {
                const { ctx, chartArea } = chart;
                if (!chartArea) return;
                const total = chart.config.options.plugins.centerTotal ?? 0;
                const centerLabel = chart.config.options.plugins.centerLabel ?? 'Total';
                const x = (chartArea.left + chartArea.right) / 2;
                const y = (chartArea.top + chartArea.bottom) / 2;

                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                // Angka Total
                ctx.font = 'bold 18px Inter, Arial, sans-serif';
                ctx.fillStyle = '#0f172a';
                ctx.fillText(total, x, y - 6);

                // Label Teks
                ctx.font = '9px Inter, Arial, sans-serif';
                ctx.fillStyle = '#64748b';
                ctx.fillText(centerLabel, x, y + 12);

                ctx.restore();
            }
        };

        // External HTML Tooltip Handler agar tooltip tidak terpotong batas canvas 148x148px maupun card container
        function getOrCreateExternalTooltip() {
            let tooltipEl = document.getElementById('incidentChartExternalTooltip');
            if (!tooltipEl) {
                tooltipEl = document.createElement('div');
                tooltipEl.id = 'incidentChartExternalTooltip';
                tooltipEl.className = 'incident-chart-external-tooltip';
                document.body.appendChild(tooltipEl);

                window.addEventListener('scroll', () => {
                    tooltipEl.style.opacity = '0';
                }, { passive: true });
            }
            return tooltipEl;
        }

        function externalDonutTooltipHandler(context) {
            const { chart, tooltip } = context;
            const tooltipEl = getOrCreateExternalTooltip();

            if (tooltip.opacity === 0) {
                tooltipEl.style.opacity = '0';
                return;
            }

            if (tooltip.body) {
                const bodyLines = tooltip.body.map(b => b.lines).flat();
                const text = bodyLines.join(' ').trim();
                const colors = tooltip.labelColors && tooltip.labelColors[0]
                    ? tooltip.labelColors[0].backgroundColor
                    : '#dc2626';
                const safeText = escapeIncidentHtml(text);

                tooltipEl.innerHTML = `
                    <span class="tooltip-color-swatch" style="background-color: ${escapeIncidentHtml(colors)};"></span>
                    <span>${safeText}</span>
                `;
            }

            const canvasRect = chart.canvas.getBoundingClientRect();
            tooltipEl.style.opacity = '1';

            const tooltipWidth = tooltipEl.offsetWidth || 220;
            const tooltipHeight = tooltipEl.offsetHeight || 34;
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

            let left = canvasRect.left + tooltip.caretX - (tooltipWidth / 2);
            let top = canvasRect.top + tooltip.caretY - tooltipHeight - 12;

            // Cegah keluar batas kiri atau kanan layar
            if (left < 12) {
                left = 12;
            } else if (left + tooltipWidth > viewportWidth - 12) {
                left = Math.max(12, viewportWidth - tooltipWidth - 12);
            }

            // Jika ruang di atas segmen terlalu sempit, tampilkan di bawah titik segmen
            if (top < 12) {
                top = Math.min(viewportHeight - tooltipHeight - 12, canvasRect.top + tooltip.caretY + 14);
            }

            tooltipEl.style.left = `${Math.round(left)}px`;
            tooltipEl.style.top = `${Math.round(top)}px`;
        }

        // 1. Chart Kategori Panas Bumi (Area) Clickable — Ukuran & Ketebalan Seragam
        const ctxArea = document.getElementById('chartAreaCanvas');
        if (ctxArea) {
            new Chart(ctxArea, {
                type: 'doughnut',
                data: {
                    labels: chartAreaConfig.labels,
                    datasets: [{
                        data: chartAreaConfig.total > 0 ? chartAreaConfig.data : [1, 1],
                        backgroundColor: chartAreaConfig.total > 0 ? chartAreaConfig.colors : ['#e2e8f0', '#cbd5e1'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    layout: { padding: 4 },
                    onClick: (event, elements, chart) => {
                        if (!elements || elements.length === 0) return;
                        const index = elements[0].index;
                        const label = chart.data.labels[index];
                        openChartDetailModal('area', label);
                    },
                    onHover: (event, chartElement) => {
                        event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false,
                            external: externalDonutTooltipHandler,
                            callbacks: {
                                label: function(context) {
                                    if (chartAreaConfig.total === 0) return 'Belum ada data';
                                    const val = context.raw || 0;
                                    const pct = ((val / chartAreaConfig.total) * 100).toFixed(1);
                                    return ` ${context.label}: ${val} (${pct}%) - Klik untuk detail`;
                                }
                            }
                        },
                        centerTotal: chartAreaConfig.total,
                        centerLabel: 'Insiden'
                    }
                },
                plugins: [donutCenterTextPlugin]
            });
        }

        // 2. Chart Kategori Umum Clickable — Ukuran & Ketebalan Seragam
        const ctxUmum = document.getElementById('chartUmumCanvas');
        if (ctxUmum) {
            let filteredUmumLabels = [];
            let filteredUmumRawLabels = [];
            let filteredUmumData = [];
            let filteredUmumColors = [];

            if (chartUmumConfig.total > 0) {
                chartUmumConfig.labels.forEach((lbl, idx) => {
                    const count = chartUmumConfig.data[idx] || 0;
                    if (count > 0) {
                        filteredUmumRawLabels.push(lbl);
                        filteredUmumLabels.push(`${lbl} (${count})`);
                        filteredUmumData.push(count);
                        const color = (chartUmumConfig.color_map && chartUmumConfig.color_map[lbl])
                            || chartUmumConfig.colors[idx]
                            || '#2563eb';
                        filteredUmumColors.push(color);
                    }
                });
            } else {
                filteredUmumRawLabels = ['Belum ada tag'];
                filteredUmumLabels = ['Belum ada tag'];
                filteredUmumData = [1];
                filteredUmumColors = ['#e2e8f0'];
            }

            new Chart(ctxUmum, {
                type: 'doughnut',
                data: {
                    labels: filteredUmumLabels,
                    datasets: [{
                        data: filteredUmumData,
                        backgroundColor: filteredUmumColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    layout: { padding: 4 },
                    onClick: (event, elements, chart) => {
                        if (!elements || elements.length === 0) return;
                        const index = elements[0].index;
                        const rawLabel = filteredUmumRawLabels[index] || chart.data.labels[index];
                        openChartDetailModal('umum', rawLabel);
                    },
                    onHover: (event, chartElement) => {
                        event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false,
                            external: externalDonutTooltipHandler,
                            callbacks: {
                                label: function(context) {
                                    if (chartUmumConfig.total === 0) return 'Belum ada tag terpilih';
                                    const val = context.raw || 0;
                                    const pct = ((val / chartUmumConfig.total) * 100).toFixed(1);
                                    const rawLbl = filteredUmumRawLabels[context.dataIndex] || context.label;
                                    return ` ${rawLbl}: ${val} (${pct}%) - Klik untuk detail`;
                                }
                            }
                        },
                        centerTotal: chartUmumConfig.total,
                        centerLabel: 'Tag'
                    }
                },
                plugins: [donutCenterTextPlugin]
            });
        }

        // 3. Chart Kategori EBTKE Clickable — Ukuran & Ketebalan Seragam
        const ctxEbtke = document.getElementById('chartEbtkeCanvas');
        if (ctxEbtke) {
            let filteredEbtkeLabels = [];
            let filteredEbtkeRawLabels = [];
            let filteredEbtkeData = [];
            let filteredEbtkeColors = [];

            if (chartEbtkeConfig.total > 0) {
                chartEbtkeConfig.labels.forEach((lbl, idx) => {
                    const count = chartEbtkeConfig.data[idx] || 0;
                    if (count > 0) {
                        filteredEbtkeRawLabels.push(lbl);
                        filteredEbtkeLabels.push(`${lbl} (${count})`);
                        filteredEbtkeData.push(count);
                        const color = (chartEbtkeConfig.color_map && chartEbtkeConfig.color_map[lbl])
                            || chartEbtkeConfig.colors[idx]
                            || '#dc2626';
                        filteredEbtkeColors.push(color);
                    }
                });
            } else {
                filteredEbtkeRawLabels = ['Belum ada tag'];
                filteredEbtkeLabels = ['Belum ada tag'];
                filteredEbtkeData = [1];
                filteredEbtkeColors = ['#e2e8f0'];
            }

            new Chart(ctxEbtke, {
                type: 'doughnut',
                data: {
                    labels: filteredEbtkeLabels,
                    datasets: [{
                        data: filteredEbtkeData,
                        backgroundColor: filteredEbtkeColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    layout: { padding: 4 },
                    onClick: (event, elements, chart) => {
                        if (!elements || elements.length === 0) return;
                        const index = elements[0].index;
                        const rawLabel = filteredEbtkeRawLabels[index] || chart.data.labels[index];
                        openChartDetailModal('ebtke', rawLabel);
                    },
                    onHover: (event, chartElement) => {
                        event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false,
                            external: externalDonutTooltipHandler,
                            callbacks: {
                                label: function(context) {
                                    if (chartEbtkeConfig.total === 0) return 'Belum ada tag EBTKE terpilih';
                                    const val = context.raw || 0;
                                    const pct = ((val / chartEbtkeConfig.total) * 100).toFixed(1);
                                    const rawLbl = filteredEbtkeRawLabels[context.dataIndex] || context.label;
                                    return ` ${rawLbl}: ${val} (${pct}%) - Klik untuk detail`;
                                }
                            }
                        },
                        centerTotal: chartEbtkeConfig.total,
                        centerLabel: 'Tag'
                    }
                },
                plugins: [donutCenterTextPlugin]
            });
        }
    });
</script>
@endpush
