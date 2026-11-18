@extends('layouts.app', [
    'title' => 'Man Hours & Manpower',
    'activeModule' => 'man-hours'
])

@push('styles')
<style>
    /* =========================================================================
       COLOR PALETTE & BASE HSSE THEME
       ========================================================================= */
    :root {
        --color-primary-green: #059669;
        --color-primary-green-hover: #047857;
        --color-text-navy: #0f172a;
        --color-text-slate: #334155;
        --color-text-muted: #64748b;
        --color-card-border: #e2e8f0;
        --color-bg-light: #f8fafc;
    }

    body {
        color: var(--color-text-navy);
    }

    /* =========================================================================
       1. PAGE HEADER: Title, Breadcrumb & Primary Action Buttons
       ========================================================================= */
    .mh-top-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 20px;
    }

    .mh-header-brand {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .mh-header-icon {
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

    .mh-header-title {
        font-size: 22px;
        font-weight: 800;
        color: var(--color-text-navy);
        line-height: 1.15;
        margin: 0;
        letter-spacing: -0.3px;
    }

    .mh-breadcrumb-nav {
        font-size: 12px;
        color: var(--color-text-muted);
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .mh-breadcrumb-nav a {
        color: inherit;
        text-decoration: none;
    }

    .mh-breadcrumb-nav a:hover {
        color: var(--color-primary-green);
    }

    .mh-header-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .btn-green-action {
        background-color: #059669;
        color: #ffffff;
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
        cursor: pointer;
        text-decoration: none;
    }

    .btn-green-action:hover {
        background-color: #047857;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);
    }

    .btn-white-action {
        background-color: #ffffff;
        color: var(--color-text-slate);
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }

    .btn-white-action:hover {
        background-color: #f8fafc;
        border-color: #94a3b8;
        color: var(--color-text-navy);
        transform: translateY(-1px);
    }

    /* =========================================================================
       2. DATE HIGHLIGHT STRIP (Clean & Big Date like Reference)
       ========================================================================= */
    .mh-date-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 22px;
    }

    .mh-big-date-box {
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

    .mh-big-date-box::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 60px;
        height: 100%;
        background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.12), transparent 70%);
        pointer-events: none;
    }

    .mh-calendar-icon-box {
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

    .mh-big-date-info {
        display: flex;
        flex-direction: column;
    }

    .mh-big-date-day-badge {
        font-size: 11px;
        font-weight: 800;
        color: #059669;
        letter-spacing: 0.4px;
        line-height: 1.2;
    }

    .mh-big-date-text {
        font-size: 24px;
        font-weight: 800;
        color: var(--color-text-navy);
        letter-spacing: -0.5px;
        line-height: 1.15;
    }

    /* =========================================================================
       3. 4 HIGHLIGHT CARDS (Exact match with reference)
       ========================================================================= */
    .mh-highlight-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .mh-highlight-card {
        background: #ffffff;
        border: 1px solid var(--color-card-border);
        border-radius: 14px;
        padding: 18px 20px;
        position: relative;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 124px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .mh-highlight-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.04);
    }

    .mh-card-header-flex {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
    }

    .mh-card-icon-badge {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .mh-card-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--color-text-muted);
        line-height: 1.25;
    }

    .mh-card-value-box {
        display: flex;
        align-items: baseline;
        gap: 6px;
    }

    .mh-card-big-number {
        font-size: 28px;
        font-weight: 800;
        color: var(--color-text-navy);
        line-height: 1;
        letter-spacing: -0.5px;
    }

    .mh-card-unit-text {
        font-size: 13px;
        font-weight: 600;
        color: var(--color-text-muted);
    }

    .mh-card-sub-info {
        font-size: 11.5px;
        font-weight: 600;
        color: #b45309;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* =========================================================================
       4. GRAPH & TODAY RECAP ROW
       ========================================================================= */
    .mh-charts-row {
        display: grid;
        grid-template-columns: minmax(0, 1.85fr) minmax(0, 1.15fr);
        gap: 16px;
        margin-bottom: 24px;
        align-items: stretch;
        min-width: 0;
        max-width: 100%;
    }

    .mh-panel-box {
        background: #ffffff;
        border: 1px solid var(--color-card-border);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .mh-panel-title-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 14px;
    }

    .mh-panel-title {
        font-size: 14.5px;
        font-weight: 800;
        color: var(--color-text-navy);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mh-period-badge {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 3px 10px;
        font-size: 11px;
        font-weight: 600;
    }

    .btn-chart-zoom-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12.5px;
        cursor: pointer;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }

    .btn-chart-zoom-icon:hover,
    .btn-chart-zoom-icon.active {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
    }

    .chart-container-box {
        position: relative;
        height: 280px;
        width: 100%;
        min-width: 0;
    }

    /* Stock-chart Range Navigator Bar */
    .chart-range-slider-bar {
        margin-top: 10px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        height: 36px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 18px;
        gap: 8px;
        font-size: 10.5px;
        font-weight: 600;
        color: #64748b;
        overflow: hidden;
    }

    .chart-range-slider-bar > span {
        position: relative;
        z-index: 1;
        white-space: nowrap;
    }

    .chart-range-slider-curve {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        opacity: 0.25;
        pointer-events: none;
        background: linear-gradient(180deg, #93c5fd 0%, #dbeafe 100%);
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1000 100' preserveAspectRatio='none'%3E%3Cpath d='M0,50 Q100,20 200,60 T400,30 T600,70 T800,40 T1000,50 L1000,100 L0,100 Z' fill='black'/%3E%3C/svg%3E");
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1000 100' preserveAspectRatio='none'%3E%3Cpath d='M0,50 Q100,20 200,60 T400,30 T600,70 T800,40 T1000,50 L1000,100 L0,100 Z' fill='black'/%3E%3C/svg%3E");
        mask-size: 100% 100%;
        -webkit-mask-size: 100% 100%;
    }

    .slider-handle-box {
        position: absolute;
        top: 2px;
        bottom: 2px;
        left: 8px;
        right: 8px;
        border: 1.5px solid #60a5fa;
        background: rgba(239, 246, 255, 0.45);
        border-radius: 4px;
        pointer-events: none;
    }

    .slider-handle-left, .slider-handle-right {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 10px;
        height: 20px;
        background: #ffffff;
        border: 1.5px solid #3b82f6;
        border-radius: 3px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        pointer-events: auto;
        cursor: ew-resize;
        z-index: 3;
    }

    .slider-handle-left { left: -4px; }
    .slider-handle-right { right: -4px; }

    /* Pie Chart & Legend Grid */
    .pie-panel-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding-top: 2px;
        flex: 1;
    }

    .pie-chart-wrap {
        position: relative;
        width: 165px;
        height: 165px;
        flex-shrink: 0;
    }

    .pie-center-label {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        pointer-events: none;
    }

    .pie-center-val {
        font-size: 22px;
        font-weight: 800;
        color: var(--color-text-navy);
        line-height: 1;
    }

    .pie-center-sub {
        font-size: 11px;
        color: var(--color-text-muted);
        font-weight: 600;
        margin-top: 2px;
    }

    .pie-legend-list {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 5px;
        font-size: 11.5px;
    }

    .pie-legend-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 5px;
        margin-bottom: 2px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .pie-legend-header-right {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .pie-legend-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 2.5px 0;
    }

    .pie-legend-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pie-color-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .pie-legend-name {
        font-weight: 700;
        color: var(--color-text-navy);
    }

    .pie-legend-right {
        display: flex;
        align-items: center;
        gap: 14px;
        font-weight: 600;
    }

    .pie-count-val {
        color: var(--color-text-slate);
        min-width: 24px;
        text-align: right;
    }

    .pie-mh-val {
        color: #059669;
        font-weight: 700;
        min-width: 68px;
        text-align: right;
    }

    /* =========================================================================
       5. FILTER BAR
       ========================================================================= */
    .mh-filter-container {
        background: #ffffff;
        border: 1px solid var(--color-card-border);
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .mh-filter-header {
        font-size: 13.5px;
        font-weight: 800;
        color: var(--color-text-navy);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .mh-filter-controls-grid {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .mh-filter-controls-grid > div:first-child {
        flex: 0 0 180px;
    }

    #dynamicPeriodInputsContainer {
        flex: 1 1 280px;
        min-width: 240px;
    }

    .mh-filter-controls-grid > div:nth-child(3) {
        flex: 0 1 240px;
        min-width: 200px;
    }

    .mh-filter-controls-grid > div:nth-child(4),
    .mh-filter-controls-grid > div:nth-child(5) {
        flex: 0 0 auto;
    }

    .filter-dynamic-controls {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        width: 100%;
    }

    .filter-dynamic-subcol {
        flex: 1;
        min-width: 0;
    }

    .filter-range-sep {
        align-self: center;
        color: #94a3b8;
        font-weight: 700;
        font-size: 14px;
        padding-top: 18px;
    }

    .filter-field-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--color-text-muted);
        margin-bottom: 5px;
        display: block;
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

    .mh-filter-container .form-control,
    .mh-filter-container .form-select {
        font-size: 12.5px;
        border-color: #cbd5e1;
        border-radius: 8px;
        padding: 7px 11px;
    }

    .mh-filter-container .form-control:focus,
    .mh-filter-container .form-select:focus {
        border-color: #059669;
        box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.15);
    }

    /* Custom Multi-Select Dropdown with Checkboxes */
    .company-dropdown-wrap {
        position: relative;
    }

    .company-dropdown-btn {
        width: 100%;
        text-align: left;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 7px 11px;
        font-size: 12.5px;
        color: var(--color-text-slate);
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
    }

    .company-dropdown-btn:hover {
        border-color: #94a3b8;
    }

    .company-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 8px 12px;
        margin-top: 4px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        z-index: 1050;
        display: none;
        max-height: 240px;
        overflow-y: auto;
    }

    .company-dropdown-menu.show {
        display: block;
    }

    .company-check-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        font-size: 12px;
        color: var(--color-text-navy);
        cursor: pointer;
        user-select: none;
    }

    .company-check-item.is-parent-company {
        font-weight: 700;
        color: var(--color-text-navy);
        margin-top: 2px;
    }

    .company-check-item.is-subcon-company {
        padding-left: 22px;
        position: relative;
        color: var(--color-text-slate);
    }

    .company-check-item.is-subcon-company::before {
        content: "";
        position: absolute;
        left: 8px;
        top: 50%;
        width: 8px;
        height: 8px;
        border-left: 1.5px solid #cbd5e1;
        border-bottom: 1.5px solid #cbd5e1;
        transform: translateY(-75%);
    }

    .company-check-item input[type="checkbox"] {
        accent-color: #059669;
        cursor: pointer;
    }

    .btn-filter-apply {
        background-color: #059669;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background-color 0.2s;
        height: 38px;
        white-space: nowrap;
    }

    .btn-filter-apply:hover {
        background-color: #047857;
    }

    .btn-filter-reset {
        background-color: #ffffff;
        color: var(--color-text-slate);
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 8px 14px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        height: 38px;
        white-space: nowrap;
    }

    .btn-filter-reset:hover {
        background-color: #f1f5f9;
        color: var(--color-text-navy);
    }

    /* =========================================================================
       6. TABLE RIWAYAT MAN HOURS & MANPOWER
       ========================================================================= */
    .mh-table-container {
        background: #ffffff;
        border: 1px solid var(--color-card-border);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        margin-bottom: 30px;
    }

    .mh-table-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
    }

    .mh-table-heading {
        font-size: 15px;
        font-weight: 800;
        color: var(--color-text-navy);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .table-tools-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .search-box-wrap {
        position: relative;
        min-width: 250px;
    }

    .search-box-wrap i {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 13px;
    }

    .search-box-wrap input {
        padding-left: 32px;
        font-size: 12.5px;
        border-color: #cbd5e1;
        border-radius: 8px;
    }

    .btn-pdf-export {
        background-color: #ef4444;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 12.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        white-space: nowrap;
        transition: background-color 0.2s;
    }

    .btn-pdf-export:hover {
        background-color: #dc2626;
        color: #ffffff;
    }

    .table-manpower {
        width: 100%;
        min-width: 1300px;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12.5px;
    }

    .table-responsive-manpower {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 8px;
    }

    .table-responsive-manpower::-webkit-scrollbar {
        height: 8px;
    }

    .table-responsive-manpower::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .table-responsive-manpower::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .table-responsive-manpower::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .table-manpower thead th {
        background-color: #ffffff;
        color: #64748b;
        font-weight: 700;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 10px 8px;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .table-manpower tbody td {
        padding: 11px 8px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #1e293b;
    }

    .table-manpower tbody tr:hover td {
        background-color: #f8fafc;
    }

    .badge-status-green {
        background: #ecfdf5;
        color: #059669;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .table-action-icon {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #475569;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .table-action-icon:hover {
        background: #ffffff;
        border-color: #059669;
        color: #059669;
        box-shadow: 0 2px 5px rgba(5, 150, 105, 0.15);
        transform: translateY(-1px);
    }

    .table-action-icon.trash-icon:hover {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #dc2626;
        box-shadow: 0 2px 5px rgba(220, 38, 38, 0.15);
    }

    .table-actions-cell {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        white-space: nowrap;
    }

    /* Input Modal Subtotal Box */
    .modal-subtotal-box {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        overflow: hidden;
        height: 31px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .modal-subtotal-box:focus-within {
        border-color: #059669;
        box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.15);
    }

    .modal-subtotal-val {
        flex: 1;
        text-align: right;
        padding: 0 10px;
        font-weight: 700;
        color: #059669;
        font-size: 12.5px;
    }

    .modal-subtotal-input {
        flex: 1;
        width: 100%;
        min-width: 0;
        border: none;
        background: transparent;
        text-align: right;
        padding: 0 8px;
        font-weight: 700;
        color: #059669;
        font-size: 12.5px;
        height: 100%;
        outline: none;
    }

    .modal-subtotal-input:focus {
        outline: none;
        box-shadow: none;
    }

    .modal-subtotal-unit {
        background: #f8fafc;
        border-left: 1px solid #e2e8f0;
        color: #059669;
        font-size: 11px;
        font-weight: 600;
        padding: 0 8px;
        height: 100%;
        display: flex;
        align-items: center;
    }

    /* =========================================================================
       7. HIERARCHICAL COMPANY MANAGEMENT MODAL
       ========================================================================= */
    .company-tree-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12.5px;
    }

    .company-tree-table thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 11px;
        padding: 10px 12px;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        letter-spacing: 0.3px;
    }

    .company-tree-table tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .company-tree-table tr.parent-row {
        background-color: #ffffff;
        transition: background-color 0.15s ease;
    }

    .company-tree-table tr.parent-row:hover {
        background-color: #f8fafc;
    }

    .company-tree-table tr.parent-row.is-jotre {
        background-color: #fffdf5;
    }

    .company-tree-table tr.child-row {
        background-color: #fafbfc;
    }

    .company-tree-table tr.child-row:hover {
        background-color: #f1f5f9;
    }

    .company-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .tree-branch-line {
        display: inline-block;
        width: 16px;
        height: 20px;
        border-left: 2px solid #cbd5e1;
        border-bottom: 2px solid #cbd5e1;
        margin-right: 8px;
        margin-left: 12px;
        vertical-align: middle;
        position: relative;
        top: -6px;
    }

    .toggle-switch-ios {
        position: relative;
        display: inline-block;
        width: 36px;
        height: 20px;
    }

    .toggle-switch-ios input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .25s;
        border-radius: 20px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .25s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    input:checked + .toggle-slider {
        background-color: #059669;
    }

    input:checked + .toggle-slider:before {
        transform: translateX(16px);
    }

    .btn-add-subcont-pill {
        background-color: #ffffff;
        color: #059669;
        border: 1px solid #10b981;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
        white-space: nowrap;
    }

    .btn-add-subcont-pill:hover {
        background-color: #ecfdf5;
        color: #047857;
    }

    .btn-action-icon-square {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 1px solid transparent;
        background: transparent;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        transition: all 0.15s ease;
    }

    .btn-action-icon-square:hover {
        background: #f1f5f9;
        color: var(--color-text-navy);
        border-color: #cbd5e1;
    }

    .btn-action-icon-square.text-danger:hover {
        background: #fef2f2;
        color: #dc2626 !important;
        border-color: #fecaca;
    }

    /* Floating Card for Add/Edit Company */
    .company-floating-form-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12), 0 2px 6px rgba(15, 23, 42, 0.04);
        padding: 18px;
        position: absolute;
        right: 20px;
        top: 65px;
        width: min(320px, calc(100vw - 32px));
        max-width: calc(100vw - 32px);
        z-index: 1060;
        display: none;
    }

    .company-floating-form-card.show {
        display: block;
        animation: fadeInDown 0.2s ease-out;
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Modal styling */
    .custom-modal-content {
        border-radius: 14px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    }

    .mh-highlight-grid,
    .mh-filter-panel,
    .mh-table-container,
    .mh-page-header,
    .mh-date-strip {
        min-width: 0;
        max-width: 100%;
    }

    .mh-highlight-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .mh-page-header,
    .mh-header-actions,
    .mh-table-topbar {
        flex-wrap: wrap;
        gap: 10px;
    }

    @media (max-width: 1380px) {
        .pie-chart-wrap {
            width: 140px;
            height: 140px;
        }
        .pie-panel-content {
            gap: 12px;
        }
        .pie-legend-header-right {
            gap: 8px;
        }
        .pie-legend-list {
            font-size: 11px;
        }
    }

    @media (max-width: 1320px) {
        .mh-charts-row { grid-template-columns: minmax(0, 1fr); }
    }

    @media (max-width: 1200px) {
        .mh-highlight-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mh-filter-controls-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 991.98px) {
        .mh-panel-box,
        .mh-filter-panel,
        .mh-table-container {
            padding: 15px;
        }
        .company-floating-form-card {
            right: 10px;
            left: 10px;
            width: auto;
            max-width: none;
        }
    }

    @media (max-width: 768px) {
        .mh-highlight-grid { grid-template-columns: minmax(0, 1fr); }
        .mh-filter-controls-grid { grid-template-columns: minmax(0, 1fr); }
        .pie-panel-content {
            flex-direction: column;
            align-items: stretch;
        }
        .pie-chart-wrap {
            margin: 0 auto;
        }
        .mh-page-header,
        .mh-table-topbar {
            flex-direction: column;
            align-items: stretch;
        }
        .search-box-wrap {
            width: 100%;
            max-width: 100%;
        }
    }

    .mh-top-header {
        min-width: 0;
        max-width: 100%;
        flex-wrap: wrap;
        gap: 10px;
    }

    @media (max-width: 575.98px) {
        .mh-panel-box,
        .mh-filter-panel,
        .mh-table-container {
            padding: 12px;
        }
        .mh-big-date-text {
            font-size: 16px;
        }
        .mh-highlight-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .mh-highlight-card {
            padding: 12px;
            min-width: 0;
        }
        .mh-card-big-number {
            font-size: 22px;
        }
        .mh-card-label {
            font-size: 11.5px;
        }
        .mh-header-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            width: 100%;
        }
        .mh-header-actions > :first-child:nth-last-child(3) {
            grid-column: 1 / -1;
        }
        .mh-header-actions > a,
        .mh-header-actions > button {
            width: 100%;
            height: 100%;
            min-height: 38px;
            justify-content: center;
            text-align: center;
            font-size: 11.5px;
            padding: 7px 8px;
            white-space: normal;
            line-height: 1.25;
        }
        .mh-trend-controls {
            width: 100%;
            justify-content: space-between;
            gap: 8px;
        }
        .btn-chart-zoom-icon {
            width: 32px;
            height: 32px;
            font-size: 13.5px;
        }
        .chart-container-box {
            height: 235px;
        }
        .chart-range-slider-bar {
            padding: 0 20px;
            height: 34px;
            font-size: 10px;
        }
        #trendSliderTick1,
        #trendSliderTick2 {
            display: none;
        }
    }
</style>
@endpush

@section('content')
@php
    $isAdmin = auth()->user()?->isAdminManagement() ?? false;
@endphp

<!-- ===========================================================================
     1. PAGE HEADER: Title, Breadcrumb & Primary Action Buttons
     =========================================================================== -->
<div class="mh-top-header">
    <div class="mh-header-brand">
        <div class="mh-header-icon">
            <i class="bi bi-clock"></i>
        </div>
        <div>
            <h1 class="mh-header-title">Man Hours &amp; Manpower</h1>
            <div class="mh-breadcrumb-nav">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span>&rsaquo;</span>
                <span class="text-dark fw-semibold">Man Hours &amp; Manpower</span>
            </div>
        </div>
    </div>

    <!-- Header Actions (No submenus, direct modals) -->
    <div class="mh-header-actions">
        @adminManagement
            <button type="button" class="btn-green-action" onclick="openBatchManhourModal()" title="Input Man Hours & Manpower">
                <i class="bi bi-plus-lg"></i> Input Man Hours &amp; Manpower<span class="visually-hidden">Input Manhour</span>
            </button>
            <button type="button" class="btn-white-action" onclick="openManageCompaniesModal()">
                <i class="bi bi-gear"></i> Manajemen Perusahaan<span class="visually-hidden">Kelola Perusahaan</span>
            </button>
        @endadminManagement
        @canExportPdf
            <a href="{{ route('man-hours.export', request()->query()) }}" id="btnPdfExportLink" class="btn-pdf-export" target="_blank">
                <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
            </a>
        @endcanExportPdf
    </div>
</div>

<!-- ===========================================================================
     2. BIG DATE STRIP (Clean & Big Date like Reference)
     =========================================================================== -->
<div class="mh-date-strip">
    <div class="mh-big-date-box">
        <div class="mh-calendar-icon-box">
            <i class="bi bi-calendar-event"></i>
        </div>
        <div class="mh-big-date-info">
            <span class="mh-big-date-day-badge" id="headerDayDisplay">
                {{ $stats['latest_day_name'] ?? '-' }}
            </span>
            <span class="mh-big-date-text" id="headerDateDisplay">
                {{ $stats['latest_date_only'] ?? ($stats['latest_header_date'] ?? 'Belum ada data') }}
            </span>
        </div>
    </div>
</div>

<!-- ===========================================================================
     3. 4 HIGHLIGHT CARDS (Exact match with reference)
     =========================================================================== -->
<div class="mh-highlight-grid">
    <!-- Card 1: Manpower (Today) -->
    <div class="mh-highlight-card">
        <div class="mh-card-header-flex">
            <div class="mh-card-icon-badge" style="background: #ecfdf5; color: #059669;">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="mh-card-label" id="cardLabelManpowerToday">Manpower<br>(Today)</div>
        </div>
        <div class="mh-card-value-box">
            <span class="mh-card-big-number" id="cardManpowerToday">{{ number_format($stats['manpower_today'] ?? 0, 0, ',', '.') }}</span>
            <span class="mh-card-unit-text">Orang</span>
        </div>
    </div>

    <!-- Card 2: Manpower (Terbanyak) -->
    <div class="mh-highlight-card">
        <div class="mh-card-header-flex">
            <div class="mh-card-icon-badge" style="background: #eff6ff; color: #2563eb;">
                <i class="bi bi-bar-chart-fill"></i>
            </div>
            <div class="mh-card-label">Manpower<br>(Terbanyak)</div>
        </div>
        <div>
            <div class="mh-card-value-box">
                <span class="mh-card-big-number" id="cardManpowerMax">{{ number_format($stats['manpower_max'] ?? 0, 0, ',', '.') }}</span>
                <span class="mh-card-unit-text">Orang</span>
            </div>
            <div class="mh-card-sub-info">
                <i class="bi bi-award-fill"></i> <span id="cardManpowerMaxDate">{{ $stats['manpower_max_date'] ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Man Hours (Today) -->
    <div class="mh-highlight-card">
        <div class="mh-card-header-flex">
            <div class="mh-card-icon-badge" style="background: #fffbeb; color: #d97706;">
                <i class="bi bi-clock"></i>
            </div>
            <div class="mh-card-label" id="cardLabelManhoursToday">Man Hours<br>(Today)</div>
        </div>
        <div class="mh-card-value-box">
            <span class="mh-card-big-number" id="cardManhoursToday">{{ number_format($stats['manhours_today'] ?? 0, 0, ',', '.') }}</span>
            <span class="mh-card-unit-text">Jam</span>
        </div>
    </div>

    <!-- Card 4: Man Hours (Kumulatif) -->
    <div class="mh-highlight-card">
        <div class="mh-card-header-flex">
            <div class="mh-card-icon-badge" style="background: #fdf2f8; color: #9333ea;">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
            <div class="mh-card-label">Man Hours<br>(Kumulatif)</div>
        </div>
        <div class="mh-card-value-box">
            <span class="mh-card-big-number" id="cardManhoursCumulative">{{ number_format($stats['manhours_cumulative'] ?? ($stats['cumulative_man_hours'] ?? 0), 0, ',', '.') }}</span>
            <span class="mh-card-unit-text">Jam</span>
        </div>
    </div>
</div>

<!-- ===========================================================================
     4. GRAPH & REKAP TENAGA KERJA
     =========================================================================== -->
<div class="mh-charts-row">
    <!-- Left: Tren Man Hours & Manpower -->
    <div class="mh-panel-box">
        <div class="mh-panel-title-bar">
            <h2 class="mh-panel-title">
                <i class="bi bi-graph-up text-success"></i> Tren Man Hours &amp; Manpower
            </h2>
            <div class="d-flex align-items-center flex-wrap gap-2 mh-trend-controls">
                <span class="mh-period-badge" id="trendPeriodBadge">{{ $stats['chart_period_badge'] ?? '01 Okt – 31 Okt (31 Hari)' }}</span>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button type="button" id="btnZoomInTrend" class="btn-chart-zoom-icon" onclick="zoomTrendChart('in')" title="Perbesar (15 Hari Terakhir)">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <button type="button" id="btnZoomOutTrend" class="btn-chart-zoom-icon active" onclick="zoomTrendChart('out')" title="Tampilkan Semua">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="chart-container-box">
            <canvas id="trendStockChartCanvas"></canvas>
        </div>
        <!-- Stock-like Range Navigator Bar -->
        @php
            $initialSliderTicks = $stats['slider_ticks'] ?? ['01 Okt 2026', '11 Okt 2026', '21 Okt 2026', '31 Okt 2026'];
        @endphp
        <div class="chart-range-slider-bar" id="trendRangeSliderBar">
            <div class="chart-range-slider-curve"></div>
            <div class="slider-handle-box" id="trendSliderHandleBox">
                <span class="slider-handle-left" id="trendSliderHandleLeft" title="Geser Tanggal Awal"></span>
                <span class="slider-handle-right" id="trendSliderHandleRight" title="Geser Tanggal Akhir"></span>
            </div>
            <span id="trendSliderTick0">{{ $initialSliderTicks[0] ?? '01 Okt 2026' }}</span>
            <span id="trendSliderTick1">{{ $initialSliderTicks[1] ?? '11 Okt 2026' }}</span>
            <span id="trendSliderTick2">{{ $initialSliderTicks[2] ?? '21 Okt 2026' }}</span>
            <span id="trendSliderTick3">{{ $initialSliderTicks[3] ?? '31 Okt 2026' }}</span>
        </div>
    </div>

    <!-- Right: Rekap Tenaga Kerja (Donut Chart + Table List) -->
    <div class="mh-panel-box">
        <div class="mh-panel-title-bar">
            <h2 class="mh-panel-title">
                <i class="bi bi-pie-chart-fill text-primary"></i> Rekap Tenaga Kerja
            </h2>
            <span class="mh-period-badge" id="recapPeriodBadge">{{ $stats['chart_period_badge'] ?? '01 Okt – 31 Okt (31 Hari)' }}</span>
        </div>
        <div class="pie-panel-content">
            <!-- Donut / Pie Chart with Center Text -->
            <div class="pie-chart-wrap">
                <canvas id="todayRecapPieCanvas"></canvas>
                <div class="pie-center-label">
                    <div class="pie-center-val" id="pieCenterTotal">{{ number_format($todayTotalWorkers, 0, ',', '.') }}</div>
                    <div class="pie-center-sub">Orang</div>
                </div>
            </div>

            <!-- Legend with counts and Man Hours (Today) -->
            <div class="pie-legend-list">
                <div class="pie-legend-header">
                    <span>PERUSAHAAN</span>
                    <div class="pie-legend-header-right">
                        <span style="min-width: 40px; text-align: right;">ORANG</span>
                        <span id="recapHeaderManhours" style="min-width: 105px; text-align: right;">{{ !empty($stats['is_filtered']) ? 'MAN HOURS' : 'MAN HOURS (TODAY)' }}</span>
                    </div>
                </div>
                <div id="pieLegendList">
                    @php
                        $companyColors = [
                            'GDE (Only)' => '#10b981',
                            'GDE' => '#10b981',
                            'JOTRE (Only)' => '#6366f1',
                            'JOTRE' => '#6366f1',
                            'BISMO' => '#ec4899',
                            'KODIPA' => '#8b5cf6',
                            'SOKKO' => '#06b6d4',
                            'ADP' => '#f59e0b',
                            'WCP' => '#84cc16',
                            'TDP' => '#3b82f6',
                            'PJSE' => '#f97316',
                            'PMC' => '#0284c7',
                            'Bhumi Phala' => '#a855f7',
                            'Rentokil' => '#ef4444',
                            'UTILITY' => '#f97316',
                            'SECURITY' => '#3b82f6',
                            'KP' => '#818cf8',
                        ];
                    @endphp
                    @foreach($todayBreakdown as $bRow)
                        @php
                            $cColor = $companyColors[$bRow['unit']] ?? '#94a3b8';
                        @endphp
                        <div class="pie-legend-row" data-company="{{ $bRow['unit'] }}">
                            <div class="pie-legend-left">
                                <span class="pie-color-dot" style="background-color: {{ $cColor }};"></span>
                                <span class="pie-legend-name">{{ $bRow['unit'] }}</span>
                            </div>
                            <div class="pie-legend-right">
                                <span class="pie-count-val">{{ $bRow['count'] }}</span>
                                <span class="pie-mh-val">{{ number_format($bRow['manhours'] ?? 0, 0, ',', '.') }} Jam</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===========================================================================
     5. FILTER SECTION (Above Table, Below Graphs)
     =========================================================================== -->
<div class="mh-filter-container">
    <div class="mh-filter-header">
        <i class="bi bi-funnel text-success"></i> Filter
    </div>
    <form id="filterFormManpower" onsubmit="event.preventDefault(); applyFilters();">
        <div class="mh-filter-controls-grid">
            <!-- 1. Periode -->
            <div>
                <label class="filter-field-label">Periode</label>
                <select id="filterPeriod" class="form-select" onchange="onPeriodDropdownChange(this.value)">
                    <option value="all" selected>Semua Periode</option>
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="monthly">Bulanan</option>
                    <option value="yearly">Tahunan</option>
                    <option value="custom">Rentang Tanggal</option>
                </select>
            </div>

            <!-- 2. Dynamic Period Inputs -->
            <div id="dynamicPeriodInputsContainer" style="display: none;">
                <div class="filter-dynamic-controls" id="filterControlsAll" style="display: none;">
                </div>

                <!-- Opsi Harian -->
                <div class="filter-dynamic-controls" id="filterControlsDaily" style="display: none;">
                    <div class="filter-dynamic-subcol">
                        <label class="filter-field-label">Pilih Tanggal</label>
                        <div class="filter-date-input-wrap">
                            <i class="bi bi-calendar3"></i>
                            <input type="date" id="filterDailyDate" class="form-control" placeholder="dd/mm/yyyy" onchange="syncDateInputsFromDynamic()">
                        </div>
                    </div>
                </div>

                <!-- Opsi Mingguan -->
                <div class="filter-dynamic-controls" id="filterControlsWeekly" style="display: none;">
                    <div class="filter-dynamic-subcol">
                        <label class="filter-field-label">Pilih Tanggal dalam Minggu</label>
                        <div class="filter-date-input-wrap">
                            <i class="bi bi-calendar-week"></i>
                            <input type="date" id="filterWeeklyPick" class="form-control" onchange="onWeeklyDatePick(this.value)">
                        </div>
                    </div>
                    <div class="filter-dynamic-subcol">
                        <label class="filter-field-label">Rentang Seminggu</label>
                        <input type="text" id="filterWeeklyDisplay" class="form-control fw-semibold" readonly style="background-color: #f8fafc; color: #059669;" placeholder="Pilih tanggal di sebelah">
                    </div>
                </div>

                <!-- Opsi Bulanan -->
                <div class="filter-dynamic-controls" id="filterControlsMonthly" style="display: none;">
                    <div class="filter-dynamic-subcol">
                        <label class="filter-field-label">Bulan</label>
                        <select id="filterMonthSelect" class="form-select" onchange="onMonthlySelectChange()">
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10" selected>Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    <div class="filter-dynamic-subcol" style="max-width: 130px;">
                        <label class="filter-field-label">Tahun</label>
                        <select id="filterMonthYearSelect" class="form-select" onchange="onMonthlySelectChange()">
                            @for($y = 2024; $y <= 2028; $y++)
                                <option value="{{ $y }}" {{ $y == 2026 ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Opsi Tahunan -->
                <div class="filter-dynamic-controls" id="filterControlsYearly" style="display: none;">
                    <div class="filter-dynamic-subcol">
                        <label class="filter-field-label">Pilih Tahun</label>
                        <select id="filterYearSelect" class="form-select" onchange="onYearlySelectChange()">
                            @for($y = 2024; $y <= 2028; $y++)
                                <option value="{{ $y }}" {{ $y == 2026 ? 'selected' : '' }}>Tahun {{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <!-- Opsi Rentang Tanggal (Custom) -->
                <div class="filter-dynamic-controls" id="filterControlsCustom">
                    <div class="filter-dynamic-subcol">
                        <label class="filter-field-label">Tanggal Mulai</label>
                        <div class="filter-date-input-wrap">
                            <i class="bi bi-calendar3"></i>
                            <input type="date" id="filterStartDate" class="form-control" placeholder="dd/mm/yyyy" onchange="syncHiddenPeriodDates()">
                        </div>
                    </div>
                    <span class="filter-range-sep">&ndash;</span>
                    <div class="filter-dynamic-subcol">
                        <label class="filter-field-label">Tanggal Akhir</label>
                        <div class="filter-date-input-wrap">
                            <i class="bi bi-calendar3"></i>
                            <input type="date" id="filterEndDate" class="form-control" placeholder="dd/mm/yyyy" onchange="syncHiddenPeriodDates()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Unit / Perusahaan (Dropdown with Checkboxes) -->
            <div>
                <label class="filter-field-label">Unit / Perusahaan</label>
                <div class="company-dropdown-wrap">
                    <button type="button" class="company-dropdown-btn" id="companyDropdownBtn" onclick="toggleCompanyDropdown(event)">
                        <span id="companyDropdownText">Semua Perusahaan</span>
                        <i class="bi bi-chevron-down" style="font-size: 11px;"></i>
                    </button>
                    <div class="company-dropdown-menu" id="companyDropdownMenu">
                        <label class="company-check-item">
                            <input type="checkbox" id="checkAllCompanies" checked onchange="toggleSelectAllCompanies(this.checked)">
                            <strong>Semua Perusahaan</strong>
                        </label>
                        <hr class="my-1 text-muted">
                        @php
                            $filterSource = !empty($activeCompanyList)
                                ? array_values(array_unique(array_merge(['GDE (All)'], array_slice($activeCompanyList, 0, 2), ['JOTRE (All)'], array_slice($activeCompanyList, 2))))
                                : ['GDE (All)', 'GDE (Only)', 'KODIPA', 'JOTRE (All)', 'JOTRE (Only)', 'ADP', 'SOKKO', 'BISMO', 'WCP', 'TDP', 'PJSE', 'Rentokil', 'PMC', 'Bhumi Phala'];
                            $subconItems = ['GDE (Only)', 'KODIPA', 'JOTRE (Only)', 'ADP', 'SOKKO', 'BISMO', 'WCP'];
                            if (!empty($activeFilterCompanies)) {
                                foreach ($activeFilterCompanies as $afc) {
                                    if (!empty($afc->parent_company_id) && !in_array($afc->name, $subconItems, true)) {
                                        $subconItems[] = $afc->name;
                                    }
                                }
                            }
                            $parentItems = ['GDE (All)', 'JOTRE (All)'];
                        @endphp
                        @foreach($filterSource as $cItem)
                            @php
                                $itemClass = '';
                                if (in_array($cItem, $subconItems, true)) {
                                    $itemClass = 'is-subcon-company';
                                } elseif (in_array($cItem, $parentItems, true)) {
                                    $itemClass = 'is-parent-company';
                                }
                            @endphp
                            <label class="company-check-item {{ $itemClass }}">
                                <input type="checkbox" class="company-checkbox" value="{{ $cItem }}" checked onchange="onIndividualCompanyChange(this)">
                                <span>{{ $cItem }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 4. Tombol Terapkan Filter -->
            <div>
                <button type="submit" class="btn-filter-apply">
                    <i class="bi bi-funnel-fill"></i> Terapkan Filter
                </button>
            </div>

            <!-- 5. Tombol Reset -->
            <div>
                <button type="button" class="btn-filter-reset" onclick="resetAllFilters()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ===========================================================================
     6. RIWAYAT MAN HOURS & MANPOWER (TABLE)
     =========================================================================== -->
<div class="mh-table-container">
    <div class="mh-table-topbar">
        <h2 class="mh-table-heading">
            <i class="bi bi-table text-success"></i> Riwayat Man Hours &amp; Manpower
        </h2>
        <div class="table-tools-right">
            <!-- Search Box -->
            <div class="search-box-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="form-control form-control-sm" id="tableSearchInput" placeholder="Cari tanggal atau unit...">
            </div>
        </div>
    </div>

    <div class="table-responsive-manpower">
        <table class="table-manpower" id="mainManpowerTable">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">NO.</th>
                    <th style="width: 100px;">TANGGAL</th>
                    @php
                        $tableCompanyHeaders = !empty($activeCompanyList)
                            ? $activeCompanyList
                            : ['GDE (Only)', 'KODIPA', 'JOTRE (Only)', 'ADP', 'SOKKO', 'BISMO', 'WCP', 'TDP', 'PJSE', 'Rentokil', 'PMC', 'Bhumi Phala'];
                    @endphp
                    @foreach($tableCompanyHeaders as $compHeader)
                        <th style="text-align: center; min-width: 72px;">{{ strtoupper($compHeader) }}</th>
                    @endforeach
                    <th style="width: 110px; text-align: center;">TOTAL PEKERJA</th>
                    <th style="width: 100px; text-align: center;">JAM KERJA</th>
                    <th style="width: 130px; text-align: center;">TOTAL MAN HOURS</th>
                    <th style="width: 130px; text-align: center;">STATUS</th>
                    <th style="width: 130px; text-align: center;">LAST UPDATE</th>
                    <th style="width: 90px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody id="manpowerTableBody">
                @forelse($historyTableRows as $idx => $r)
                    <tr data-date="{{ $r['date'] }}">
                        <td style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
                        <td class="fw-bold">{{ $r['date_formatted'] }}</td>
                        @foreach($tableCompanyHeaders as $compHeader)
                            <td style="text-align: center;">{{ $r['units'][$compHeader] ?? 0 }}</td>
                        @endforeach
                        <td style="text-align: center;" class="fw-bold text-dark">{{ $r['total_workers'] }}</td>
                        <td style="text-align: center;" class="text-secondary">{{ number_format($r['hours_per_worker'], 1, ',', '.') }}</td>
                        <td style="text-align: center;" class="fw-bold text-success">{{ number_format($r['total_man_hours'], 0, ',', '.') }}</td>
                        <td style="text-align: center;">
                            <span class="badge-status-green">
                                <i class="bi bi-check-circle-fill"></i> Sudah Diinput
                            </span>
                        </td>
                        <td style="text-align: center; font-size: 11.5px; color: #64748b;">
                            {{ $r['last_update'] ?? '-' }}
                        </td>
                        <td style="text-align: center;">
                            <div class="table-actions-cell">
                                <!-- View Detail -->
                                <button type="button" class="table-action-icon" onclick="viewDetailDate('{{ $r['date'] }}')" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                                @adminManagement
                                <!-- Edit -->
                                <button type="button" class="table-action-icon" onclick="openBatchManhourModal('{{ $r['date'] }}')" title="Edit Data">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- Delete -->
                                <button type="button" class="table-action-icon trash-icon" onclick="deleteDateRecord('{{ $r['date'] }}')" title="Hapus Data">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endadminManagement
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($tableCompanyHeaders) + 8 }}" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Tidak ada data Man Hours &amp; Manpower pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ===========================================================================
     MODAL 1: INPUT / EDIT MAN HOURS & MANPOWER
     =========================================================================== -->
<div class="modal fade" id="modalBatchManhour" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-modal-content">
            <form id="formBatchManhour" onsubmit="submitBatchManhour(event)">
                @csrf
                <div class="modal-header border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-2.5">
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: #ecfdf5; color: #059669; display: grid; place-items: center; font-size: 18px;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="batchModalTitle">Input Man Hours &amp; Manpower</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Tanggal Pencatatan -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark" style="font-size: 12.5px;">Tanggal Pencatatan <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="modalRecordDate" name="record_date" value="{{ date('Y-m-d') }}" required onchange="loadDateForBatchInput(this.value)">
                    </div>

                    <!-- Tabel Input Perusahaan -->
                    <div class="table-responsive border rounded mb-3" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm align-middle mb-0" style="font-size: 12.5px;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 28%;">Perusahaan</th>
                                    <th style="width: 24%;">Jumlah Pekerja (Orang)</th>
                                    <th style="width: 24%;">Jam Kerja (Jam)</th>
                                    <th style="width: 24%; text-align: right;">Subtotal Man Hours</th>
                                </tr>
                            </thead>
                            <tbody id="modalUnitsTbody">
                                @php
                                    $modalCompanyItems = !empty($activeCompanyList)
                                        ? $activeCompanyList
                                        : ['GDE (Only)', 'KODIPA', 'JOTRE (Only)', 'ADP', 'SOKKO', 'BISMO', 'WCP', 'TDP', 'PJSE', 'Rentokil', 'PMC', 'Bhumi Phala'];
                                @endphp
                                @foreach($modalCompanyItems as $idx => $comp)
                                    @php
                                        $fieldKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $comp);
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $comp }}</span>
                                            <input type="hidden" name="units[{{ $idx }}][unit]" value="{{ $comp }}">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm modal-worker-input"
                                                   name="units[{{ $idx }}][worker_count]"
                                                   id="inputWorker_{{ $fieldKey }}"
                                                   data-company="{{ $comp }}"
                                                   min="0" step="1" value="0" required oninput="onModalWorkerOrHoursInput('{{ $fieldKey }}')">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm modal-hours-input"
                                                   name="units[{{ $idx }}][hours_per_worker]"
                                                   id="inputHours_{{ $fieldKey }}"
                                                   data-company="{{ $comp }}"
                                                   min="0" max="24" step="any" value="8" required oninput="onModalWorkerOrHoursInput('{{ $fieldKey }}')">
                                        </td>
                                        <td>
                                            <div class="modal-subtotal-box">
                                                <input type="number" class="modal-subtotal-input"
                                                       name="units[{{ $idx }}][subtotal_man_hours]"
                                                       id="subtotalHours_{{ $fieldKey }}"
                                                       data-company="{{ $comp }}"
                                                       min="0" step="any" value="0" required oninput="onModalSubtotalManualInput('{{ $fieldKey }}')">
                                                <span class="modal-subtotal-unit">Jam</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Live Calculated Summary Card -->
                    <div class="p-3 rounded d-flex justify-content-end align-items-center flex-wrap gap-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <div class="text-end">
                            <div class="text-muted small">Total Pekerja</div>
                            <div class="fs-5 fw-bold text-primary"><span id="modalLiveWorkers">0</span> <small class="fs-6 text-dark">Orang</small></div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Jam Kerja Rata-rata</div>
                            <div class="fs-5 fw-bold text-warning"><span id="modalLiveAvgHours">0,0</span> <small class="fs-6 text-dark">Jam</small></div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Total Man Hours</div>
                            <div class="fs-5 fw-bold text-success"><span id="modalLiveManhours">0</span> <small class="fs-6 text-dark">Jam</small></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top bg-light py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-green-action btn-sm px-4" id="btnSaveBatchManhour">
                        <i class="bi bi-check-lg"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===========================================================================
     MODAL 2: DETAIL TANGGAL PENCATATAN (LIHAT DETAIL)
     =========================================================================== -->
<div class="modal fade" id="modalDetailDate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content custom-modal-content">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark mb-0">
                    <i class="bi bi-calendar2-check text-success me-2"></i> Rincian Tanggal <span id="detailModalDateText"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-sm table-bordered align-middle mb-3" style="font-size: 12.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>Perusahaan</th>
                            <th class="text-center">Jumlah Pekerja</th>
                            <th class="text-center">Jam / Orang</th>
                            <th class="text-end">Total Man Hours</th>
                        </tr>
                    </thead>
                    <tbody id="detailModalTbody">
                        <!-- Populated by JS -->
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>Total</td>
                            <td class="text-center" id="detailModalTotalWorkers">0</td>
                            <td class="text-center" id="detailModalAvgHours">0.0</td>
                            <td class="text-end text-success" id="detailModalTotalManhours">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="modal-footer border-top bg-light py-2 px-4">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ===========================================================================
     MODAL 3: MANAJEMEN PERUSAHAAN (Hierarki Parent-Child, Keterangan & Switch Toggle)
     =========================================================================== -->
<div class="modal fade" id="modalManageCompanies" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content custom-modal-content position-relative overflow-hidden" style="border-radius: 16px;">
            <!-- Header -->
            <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; color: #059669; display: grid; place-items: center; font-size: 24px; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.15);">
                        <i class="bi bi-buildings"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" style="letter-spacing: -0.3px;">Manajemen Perusahaan</h4>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="search-box-wrap" style="min-width: 220px;">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control form-control-sm" id="manageCompanySearchInput" placeholder="Cari nama perusahaan..." oninput="filterCompanyTreeTable(this.value)">
                    </div>
                    @adminManagement
                    <button type="button" class="btn btn-green-action py-1 px-3" style="font-size: 12.5px; height: 35px;" onclick="openCompanyFormPopup('main')">
                        <i class="bi bi-plus-lg"></i> Tambah Perusahaan
                    </button>
                    @endadminManagement
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Body Table -->
            <div class="modal-body p-0 position-relative" style="max-height: 520px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="company-tree-table" id="tableCompanyTree">
                        <thead>
                            <tr>
                                <th>Nama Perusahaan</th>
                                <th style="width: 280px;">Keterangan</th>
                                <th style="width: 130px; text-align: center;">Status</th>
                                <th style="width: 250px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="companyTreeTableBody">
                            <!-- Populated dynamically via JS from master data -->
                        </tbody>
                    </table>
                </div>

                <!-- Floating Card: Tambah / Edit Perusahaan atau Subkontraktor -->
                <div class="company-floating-form-card" id="companyFloatingFormCard">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div id="floatingCardIconBox" style="width: 30px; height: 30px; border-radius: 8px; background: #ecfdf5; color: #059669; display: grid; place-items: center; font-size: 16px;">
                                <i class="bi bi-buildings"></i>
                            </div>
                            <h6 class="fw-bold mb-0 text-dark" id="floatingCardTitle">Tambah Perusahaan</h6>
                        </div>
                        <button type="button" class="btn-close btn-sm" onclick="closeCompanyFormPopup()" aria-label="Close"></button>
                    </div>

                    <form id="formFloatingCompany" onsubmit="submitFloatingCompany(event)">
                        <input type="hidden" id="floatCompId">
                        <input type="hidden" id="floatCompCategory" value="main">
                        <input type="hidden" id="floatCompParentId" value="">

                        <!-- Field Nama Perusahaan -->
                        <div class="mb-2.5">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Nama Perusahaan <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="floatCompName" placeholder="Contoh: MITRA BARU" required>
                        </div>

                        <!-- Dropdown Pilihan Perusahaan Induk -->
                        <div class="mb-2.5 d-none" id="floatParentSelectWrapper">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Perusahaan Induk <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="floatParentSelect" onchange="onFloatParentChange(this.value)">
                                <!-- Dynamic options -->
                            </select>
                        </div>

                        <!-- Field Keterangan (Opsional) -->
                        <div class="mb-2.5">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Keterangan (Opsional)
                            </label>
                            <textarea class="form-control form-control-sm" id="floatCompDesc" rows="2" placeholder="Ketik keterangan perusahaan..."></textarea>
                        </div>

                        <!-- Field Status Toggle -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark mb-1 d-block">Status</label>
                            <div class="d-flex align-items-center gap-2">
                                <label class="toggle-switch-ios">
                                    <input type="checkbox" id="floatCompIsActive" checked onchange="updateFloatStatusLabel(this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="small fw-semibold text-success" id="floatCompStatusText">Aktif</span>
                            </div>
                        </div>

                        <!-- Tombol Batal & Simpan -->
                        <div class="d-flex justify-content-end gap-2 pt-1 border-top">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="closeCompanyFormPopup()">Batal</button>
                            <button type="submit" class="btn btn-green-action btn-sm px-3" id="btnSubmitFloatCompany">
                                <i class="bi bi-check-lg"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-top bg-light py-2 px-4 d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    <i class="bi bi-info-circle me-1"></i> Perusahaan nonaktif tidak akan menghapus atau mereset data historis sebelumnya.
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Metadata Kompatibilitas Hidden untuk Unit & Akumulasi -->
<div class="d-none" aria-hidden="true">
    @if(!empty($unitExpansion['has_new_units']))
        <div id="unitExpansionBanner">
            {{ implode(', ', $unitExpansion['new_unit_names'] ?? []) }}
        </div>
    @endif
    @foreach(($unitRecap['rows'] ?? []) as $uRow)
        @if(!empty($uRow['unit_id']))
            <div id="unitAccumDisplay-{{ $uRow['unit_id'] }}">
                {{ $uRow['period_man_hours'] }}
                @if(!empty($uRow['has_manual_adjustment']))
                    <span>Koreksi Manual</span>
                @endif
            </div>
            <form id="unitAccumForm-{{ $uRow['unit_id'] }}"></form>
        @endif
    @endforeach
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const MH_BASE_URL = "{{ url('/man-hours') }}";
    const IS_ADMIN_MANAGEMENT = @json($isAdmin);
    @php
        $jsTargetCompanies = !empty($activeCompanyList)
            ? $activeCompanyList
            : ['GDE (Only)', 'KODIPA', 'JOTRE (Only)', 'ADP', 'SOKKO', 'BISMO', 'WCP', 'TDP', 'PJSE', 'Rentokil', 'PMC', 'Bhumi Phala'];
    @endphp
    let TARGET_COMPANIES = @json($jsTargetCompanies);
    if (!Array.isArray(TARGET_COMPANIES) || TARGET_COMPANIES.length === 0) {
        TARGET_COMPANIES = ['GDE (Only)', 'KODIPA', 'JOTRE (Only)', 'ADP', 'SOKKO', 'BISMO', 'WCP', 'TDP', 'PJSE', 'Rentokil', 'PMC', 'Bhumi Phala'];
    }

    function getCompanyInputKey(comp) {
        return String(comp).replace(/[^a-zA-Z0-9_]/g, '_');
    }

    const COMPANY_COLOR_MAP = {
        'GDE (Only)': '#10b981',
        'GDE': '#10b981',
        'JOTRE (Only)': '#6366f1',
        'JOTRE': '#6366f1',
        'BISMO': '#ec4899',
        'KODIPA': '#8b5cf6',
        'SOKKO': '#06b6d4',
        'ADP': '#f59e0b',
        'WCP': '#84cc16',
        'TDP': '#3b82f6',
        'PJSE': '#f97316',
        'PMC': '#0284c7',
        'Bhumi Phala': '#a855f7',
        'Rentokil': '#ef4444',
        'UTILITY': '#f97316',
        'SECURITY': '#3b82f6',
        'KP': '#818cf8',
    };

    let trendChartInstance = null;
    let pieChartInstance = null;

    let fullChartData = @json($trendChart ?? ['labels' => [], 'workers' => [], 'manhours' => []]);
    let currentTodayBreakdown = @json($todayBreakdown ?? []);
    let currentTodayTotal = {{ (int) $todayTotalWorkers }};
    let currentZoom = 'All';

    // Vertical Crosshair line plugin for Stock-like Hover
    const verticalLinePlugin = {
        id: 'verticalLinePlugin',
        afterDraw: (chart) => {
            if (chart.tooltip?._active && chart.tooltip._active.length) {
                const activePoint = chart.tooltip._active[0];
                const ctx = chart.ctx;
                const x = activePoint.element.x;
                const topY = chart.scales.yWorkers.top;
                const bottomY = chart.scales.yWorkers.bottom;

                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([3, 3]);
                ctx.moveTo(x, topY);
                ctx.lineTo(x, bottomY);
                ctx.lineWidth = 1;
                ctx.strokeStyle = '#94a3b8';
                ctx.stroke();
                ctx.restore();
            }
        }
    };

    // =========================================================================
    // 1. TREN MAN HOURS & MANPOWER (DUAL-AXIS LINE CHART WITH POINTS)
    // =========================================================================
    let currentRenderedTrendData = null;
    let lastTrendBpBucket = '';

    function getTrendBpBucket() {
        const w = window.innerWidth;
        if (w < 576) return 'mobile';
        if (w < 992) return 'tablet';
        return 'desktop';
    }

    function initTrendStockChart(chartData) {
        const canvas = document.getElementById('trendStockChartCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        currentRenderedTrendData = chartData;
        lastTrendBpBucket = getTrendBpBucket();
        const isMobile = window.innerWidth < 576;
        const isTablet = window.innerWidth < 992;

        if (trendChartInstance) {
            trendChartInstance.destroy();
        }

        const workerVals = (chartData.workers || []).map(Number).filter(n => !isNaN(n));
        const mhVals = (chartData.manhours || chartData.man_hours || []).map(Number).filter(n => !isNaN(n));
        const maxW = Math.max(250, ...(workerVals.length ? workerVals : [250]));
        const maxMh = Math.max(2400, ...(mhVals.length ? mhVals : [2400]));
        const yWorkersMax = maxW > 250 ? Math.ceil(maxW / 50) * 50 : 250;
        const yHoursMax = maxMh > 2400 ? Math.ceil(maxMh / 480) * 480 : 2400;

        trendChartInstance = new Chart(ctx, {
            type: 'line',
            plugins: [verticalLinePlugin],
            data: {
                labels: chartData.labels || [],
                datasets: [
                    {
                        label: 'Manpower (Orang)',
                        data: chartData.workers || [],
                        borderColor: '#059669',
                        backgroundColor: '#059669',
                        pointBackgroundColor: '#059669',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: isMobile ? 1 : 1.5,
                        pointRadius: isMobile ? 2.2 : 3.2,
                        pointHoverRadius: 6,
                        pointHitRadius: isMobile ? 14 : 6,
                        tension: 0.15,
                        borderWidth: 2,
                        yAxisID: 'yWorkers'
                    },
                    {
                        label: 'Man Hours (Jam)',
                        data: chartData.manhours || chartData.man_hours || [],
                        borderColor: '#2563eb',
                        backgroundColor: '#2563eb',
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: isMobile ? 1 : 1.5,
                        pointRadius: isMobile ? 2.2 : 3.2,
                        pointHoverRadius: 6,
                        pointHitRadius: isMobile ? 14 : 6,
                        tension: 0.15,
                        borderWidth: 2,
                        yAxisID: 'yHours'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 11, weight: '600' },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 14
                        }
                    },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#0f172a',
                        bodyColor: '#334155',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        boxPadding: 4,
                        usePointStyle: true,
                        titleFont: { weight: '800', size: 12 },
                        bodyFont: { weight: '600', size: 11.5 },
                        callbacks: {
                            title: function(items) {
                                if (!items.length) return '';
                                const idx = items[0].dataIndex;
                                const dtStr = chartData.dates?.[idx];
                                if (dtStr) {
                                    const dtObj = new Date(dtStr + 'T00:00:00');
                                    if (!isNaN(dtObj.getTime())) {
                                        return dtObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                                    }
                                }
                                const label = items[0].label;
                                return `${label} 2026`;
                            },
                            label: function (ctx) {
                                if (ctx.dataset.yAxisID === 'yHours') {
                                    return ` Man Hours:  ${Number(ctx.raw).toLocaleString('id-ID')} Jam`;
                                }
                                return ` Manpower:  ${Number(ctx.raw).toLocaleString('id-ID')} Orang`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#f8fafc' },
                        ticks: {
                            font: { size: isMobile ? 9.5 : 10 },
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkip: isMobile || isTablet,
                            autoSkipPadding: isMobile ? 14 : 10,
                            maxTicksLimit: isMobile ? 5 : (isTablet ? 8 : undefined),
                            callback: function(val, index) {
                                const label = this.getLabelForValue(val);
                                if (isMobile || isTablet) return label;
                                if ((chartData.labels || []).length <= 15) return label;
                                return (index % 2 === 0 || index === (chartData.labels || []).length - 1) ? label : '';
                            }
                        }
                    },
                    yWorkers: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        max: yWorkersMax,
                        title: {
                            display: !isMobile,
                            text: 'Manpower (Orang)',
                            font: { size: 10.5, weight: '700' },
                            color: '#64748b'
                        },
                        ticks: {
                            stepSize: Math.max(50, Math.round(yWorkersMax / 5)),
                            font: { size: isMobile ? 9.5 : 10 },
                            padding: isMobile ? 4 : 6
                        }
                    },
                    yHours: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        max: yHoursMax,
                        grid: { drawOnChartArea: false },
                        title: {
                            display: !isMobile,
                            text: 'Man Hours (Jam)',
                            font: { size: 10.5, weight: '700' },
                            color: '#64748b'
                        },
                        ticks: {
                            stepSize: Math.max(480, Math.round(yHoursMax / 5)),
                            font: { size: isMobile ? 9.5 : 10 },
                            padding: isMobile ? 4 : 6,
                            callback: function(value) {
                                return Number(value).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    let sliderDomainStart = @json($stats['domain_start_date'] ?? now()->format('Y-m-d'));
    let sliderDomainEnd = @json($stats['domain_end_date'] ?? now()->format('Y-m-d'));

    function zoomTrendChart(dir) {
        const btnIn = document.getElementById('btnZoomInTrend');
        const btnOut = document.getElementById('btnZoomOutTrend');
        const periodSel = document.getElementById('filterPeriod');
        const startInput = document.getElementById('filterStartDate');
        const endInput = document.getElementById('filterEndDate');

        if (dir === 'in') {
            if (btnIn) btnIn.classList.add('active');
            if (btnOut) btnOut.classList.remove('active');

            const dates = Array.isArray(fullChartData?.dates) ? fullChartData.dates : [];
            let zoomStart = '';
            let zoomEnd = '';

            if (dates.length > 1) {
                const sliceCount = dates.length > 15 ? 15 : Math.max(1, Math.ceil(dates.length / 2));
                const sliced = dates.slice(-sliceCount);
                zoomStart = sliced[0];
                zoomEnd = sliced[sliced.length - 1];
            } else if (dates.length === 1) {
                zoomStart = dates[0];
                zoomEnd = dates[0];
            } else {
                const endDt = new Date((endInput?.value || sliderDomainEnd || new Date().toISOString().slice(0, 10)) + 'T00:00:00');
                const startDt = new Date(endDt);
                startDt.setDate(endDt.getDate() - 14);
                const fmt = (dt) => `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
                zoomStart = fmt(startDt);
                zoomEnd = fmt(endDt);
            }

            if (periodSel) {
                periodSel.value = 'custom';
                onPeriodDropdownChange('custom', false);
            }
            if (startInput) startInput.value = zoomStart;
            if (endInput) endInput.value = zoomEnd;
            applyFilters();
        } else {
            if (btnOut) btnOut.classList.add('active');
            if (btnIn) btnIn.classList.remove('active');

            if (periodSel) {
                periodSel.value = 'all';
                onPeriodDropdownChange('all', false);
            }
            if (startInput) startInput.value = '';
            if (endInput) endInput.value = '';
            resetSliderHandlesVisual();
            applyFilters();
        }
    }

    function resetSliderHandlesVisual() {
        const box = document.getElementById('trendSliderHandleBox');
        if (box) {
            box.style.left = '8px';
            box.style.right = '8px';
        }
    }

    function initTrendRangeSliderDrag() {
        const bar = document.getElementById('trendRangeSliderBar');
        const box = document.getElementById('trendSliderHandleBox');
        const leftHandle = document.getElementById('trendSliderHandleLeft');
        const rightHandle = document.getElementById('trendSliderHandleRight');
        if (!bar || !box || !leftHandle || !rightHandle) return;

        let draggingSide = null;
        let leftPct = 0;
        let rightPct = 100;

        const fmtISO = (dt) => {
            const yr = dt.getFullYear();
            const mo = String(dt.getMonth() + 1).padStart(2, '0');
            const da = String(dt.getDate()).padStart(2, '0');
            return `${yr}-${mo}-${da}`;
        };

        const pctToDateStr = (pct) => {
            const dStart = new Date((sliderDomainStart || new Date().toISOString().slice(0, 10)) + 'T00:00:00');
            const dEnd = new Date((sliderDomainEnd || new Date().toISOString().slice(0, 10)) + 'T00:00:00');
            const diffMs = Math.max(0, dEnd.getTime() - dStart.getTime());
            const targetMs = dStart.getTime() + Math.round((pct / 100) * diffMs);
            return fmtISO(new Date(targetMs));
        };

        const updateVisual = () => {
            const rect = bar.getBoundingClientRect();
            const usableW = Math.max(1, rect.width - 16);
            const leftPx = 8 + Math.round((leftPct / 100) * usableW);
            const rightPx = 8 + Math.round(((100 - rightPct) / 100) * usableW);
            box.style.left = `${leftPx}px`;
            box.style.right = `${rightPx}px`;
        };

        const onPointerMove = (clientX) => {
            if (!draggingSide) return;
            const rect = bar.getBoundingClientRect();
            const usableW = Math.max(1, rect.width - 16);
            const relX = Math.min(Math.max(0, clientX - rect.left - 8), usableW);
            const pct = Math.round((relX / usableW) * 100);

            if (draggingSide === 'left') {
                leftPct = Math.min(pct, rightPct);
            } else if (draggingSide === 'right') {
                rightPct = Math.max(pct, leftPct);
            }
            updateVisual();
        };

        const onPointerUp = () => {
            if (!draggingSide) return;
            draggingSide = null;
            document.body.style.userSelect = '';

            const newStart = pctToDateStr(leftPct);
            const newEnd = pctToDateStr(rightPct);

            const periodSel = document.getElementById('filterPeriod');
            const startInput = document.getElementById('filterStartDate');
            const endInput = document.getElementById('filterEndDate');

            if (periodSel) {
                periodSel.value = 'custom';
                onPeriodDropdownChange('custom', false);
            }
            if (startInput) startInput.value = newStart;
            if (endInput) endInput.value = newEnd;
            applyFilters();
        };

        leftHandle.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            draggingSide = 'left';
            document.body.style.userSelect = 'none';
        });

        rightHandle.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            draggingSide = 'right';
            document.body.style.userSelect = 'none';
        });

        window.addEventListener('mousemove', (e) => {
            if (draggingSide) onPointerMove(e.clientX);
        });

        window.addEventListener('mouseup', () => {
            if (draggingSide) onPointerUp();
        });
    }

    let trendResizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(trendResizeTimer);
        trendResizeTimer = setTimeout(function () {
            if (currentRenderedTrendData && getTrendBpBucket() !== lastTrendBpBucket) {
                initTrendStockChart(currentRenderedTrendData);
            }
        }, 160);
    });

    function setZoomPeriod(zoom) {
        currentZoom = zoom;
        if (!fullChartData || !Array.isArray(fullChartData.labels)) return;
        initTrendStockChart(fullChartData);
    }

    // =========================================================================
    // 2. REKAP TENAGA KERJA (DONUT CHART & TABEL SAMPING)
    // =========================================================================
    function initPieChart(breakdownData) {
        const canvas = document.getElementById('todayRecapPieCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        if (pieChartInstance) {
            pieChartInstance.destroy();
        }

        const isEmpty = !Array.isArray(breakdownData) || breakdownData.length === 0 || breakdownData.every(d => Number(d.count || 0) === 0);
        const labels = isEmpty ? ['Tidak Ada Data'] : breakdownData.map(d => d.unit);
        const data = isEmpty ? [1] : breakdownData.map(d => d.count);
        const bgColors = isEmpty ? ['#e2e8f0'] : breakdownData.map(d => COMPANY_COLOR_MAP[d.unit] || '#94a3b8');

        pieChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: bgColors,
                    borderWidth: isEmpty ? 0 : 2,
                    borderColor: '#ffffff',
                    hoverOffset: isEmpty ? 0 : 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: !isEmpty,
                        callbacks: {
                            label: function (ctx) {
                                if (isEmpty) return '';
                                const row = breakdownData[ctx.dataIndex];
                                return ` ${ctx.label}: ${ctx.raw} Orang (${Number(row?.manhours ?? 0).toLocaleString('id-ID')} Jam)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function renderPieLegend(breakdownData, totalWorkers) {
        const container = document.getElementById('pieLegendList');
        const centerVal = document.getElementById('pieCenterTotal');
        if (centerVal) centerVal.textContent = Number(totalWorkers).toLocaleString('id-ID');

        if (!container) return;

        let rowsHtml = '';
        breakdownData.forEach(b => {
            const color = COMPANY_COLOR_MAP[b.unit] || '#94a3b8';
            const mh = Number(b.manhours ?? 0).toLocaleString('id-ID');
            rowsHtml += `
                <div class="pie-legend-row" data-company="${b.unit}">
                    <div class="pie-legend-left">
                        <span class="pie-color-dot" style="background-color: ${color};"></span>
                        <span class="pie-legend-name">${b.unit}</span>
                    </div>
                    <div class="pie-legend-right">
                        <span class="pie-count-val">${b.count}</span>
                        <span class="pie-mh-val">${mh} Jam</span>
                    </div>
                </div>
            `;
        });

        container.innerHTML = rowsHtml;
    }

    // =========================================================================
    // 3. DROPDOWN UNIT / PERUSAHAAN CHECKBOX
    // =========================================================================
    function toggleCompanyDropdown(e) {
        e.stopPropagation();
        const menu = document.getElementById('companyDropdownMenu');
        if (menu) menu.classList.toggle('show');
    }

    document.addEventListener('click', function (e) {
        const wrap = document.querySelector('.company-dropdown-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('companyDropdownMenu')?.classList.remove('show');
        }
    });

    function toggleSelectAllCompanies(isChecked) {
        document.querySelectorAll('.company-checkbox').forEach(cb => {
            cb.checked = isChecked;
        });
        updateCompanyDropdownLabel();
    }

    function onIndividualCompanyChange(triggerEl) {
        if (triggerEl && triggerEl.value) {
            const val = triggerEl.value.trim();
            const isChecked = triggerEl.checked;
            const jotreSubcons = ['PJSE', 'ADP', 'SOKKO', 'BISMO', 'WCP'];

            if (val === 'GDE (All)') {
                document.querySelectorAll('.company-checkbox').forEach(cb => {
                    if (cb.value === 'GDE (Only)' || cb.value === 'KODIPA') {
                        cb.checked = isChecked;
                    }
                });
            } else if (val === 'GDE (Only)') {
                const gdeAllCb = Array.from(document.querySelectorAll('.company-checkbox')).find(c => c.value === 'GDE (All)');
                const kodipaCb = Array.from(document.querySelectorAll('.company-checkbox')).find(c => c.value === 'KODIPA');
                if (gdeAllCb && (!isChecked || !(kodipaCb && kodipaCb.checked))) {
                    gdeAllCb.checked = false;
                }
            } else if (val === 'JOTRE (All)') {
                document.querySelectorAll('.company-checkbox').forEach(cb => {
                    if (cb.value === 'JOTRE (Only)' || jotreSubcons.includes(cb.value)) {
                        cb.checked = isChecked;
                    }
                });
            } else if (val === 'JOTRE (Only)') {
                const jotreAllCb = Array.from(document.querySelectorAll('.company-checkbox')).find(c => c.value === 'JOTRE (All)');
                if (jotreAllCb && !isChecked) {
                    jotreAllCb.checked = false;
                }
            } else if (jotreSubcons.includes(val)) {
                if (!isChecked) {
                    const jotreAllCb = Array.from(document.querySelectorAll('.company-checkbox')).find(c => c.value === 'JOTRE (All)');
                    if (jotreAllCb) jotreAllCb.checked = false;
                }
            }
        }

        const allBoxes = document.querySelectorAll('.company-checkbox');
        const checkedBoxes = document.querySelectorAll('.company-checkbox:checked');
        const allMaster = document.getElementById('checkAllCompanies');

        if (checkedBoxes.length === allBoxes.length) {
            allMaster.checked = true;
            allMaster.indeterminate = false;
        } else if (checkedBoxes.length === 0) {
            allMaster.checked = false;
            allMaster.indeterminate = false;
        } else {
            allMaster.checked = false;
            allMaster.indeterminate = true;
        }

        updateCompanyDropdownLabel();
    }

    function updateCompanyDropdownLabel() {
        const allMaster = document.getElementById('checkAllCompanies');
        const checkedBoxes = Array.from(document.querySelectorAll('.company-checkbox:checked')).map(cb => cb.value);
        const labelEl = document.getElementById('companyDropdownText');
        if (!labelEl) return;

        if (allMaster && allMaster.checked) {
            labelEl.textContent = 'Semua Perusahaan';
        } else if (checkedBoxes.length === 0) {
            labelEl.textContent = 'Pilih Perusahaan';
        } else if (checkedBoxes.length === 1) {
            labelEl.textContent = checkedBoxes[0];
        } else {
            labelEl.textContent = 'Perusahaan Terpilih';
        }
    }

    function onPeriodDropdownChange(periodVal, shouldApply = true) {
        const controls = [
            'filterControlsAll',
            'filterControlsDaily',
            'filterControlsWeekly',
            'filterControlsMonthly',
            'filterControlsYearly',
            'filterControlsCustom'
        ];
        controls.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });

        const startInput = document.getElementById('filterStartDate');
        const endInput = document.getElementById('filterEndDate');

        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        const todayStr = `${y}-${m}-${d}`;

        const container = document.getElementById('dynamicPeriodInputsContainer');
        if (container) {
            container.style.display = (periodVal === 'all') ? 'none' : '';
        }

        if (periodVal === 'all') {
            if (startInput) startInput.value = '';
            if (endInput) endInput.value = '';
            resetSliderHandlesVisual();
            if (shouldApply) applyFilters();
        } else if (periodVal === 'daily') {
            const el = document.getElementById('filterControlsDaily');
            if (el) el.style.display = 'flex';
            const dailyInput = document.getElementById('filterDailyDate');
            if (dailyInput) {
                if (!dailyInput.value) dailyInput.value = todayStr;
                if (startInput) startInput.value = dailyInput.value;
                if (endInput) endInput.value = dailyInput.value;
            }
            if (shouldApply) applyFilters();
        } else if (periodVal === 'weekly') {
            const el = document.getElementById('filterControlsWeekly');
            if (el) el.style.display = 'flex';
            const weekPicker = document.getElementById('filterWeeklyPick');
            if (weekPicker) {
                if (!weekPicker.value) weekPicker.value = todayStr;
                onWeeklyDatePick(weekPicker.value, shouldApply);
            }
        } else if (periodVal === 'monthly') {
            const el = document.getElementById('filterControlsMonthly');
            if (el) el.style.display = 'flex';
            onMonthlySelectChange(shouldApply);
        } else if (periodVal === 'yearly') {
            const el = document.getElementById('filterControlsYearly');
            if (el) el.style.display = 'flex';
            onYearlySelectChange(shouldApply);
        } else if (periodVal === 'custom') {
            const el = document.getElementById('filterControlsCustom');
            if (el) el.style.display = 'flex';
            if (shouldApply && (startInput?.value || endInput?.value)) {
                applyFilters();
            }
        }
    }

    function syncDateInputsFromDynamic() {
        const dailyVal = document.getElementById('filterDailyDate')?.value;
        const startInput = document.getElementById('filterStartDate');
        const endInput = document.getElementById('filterEndDate');
        if (dailyVal) {
            if (startInput) startInput.value = dailyVal;
            if (endInput) endInput.value = dailyVal;
            applyFilters();
        }
    }

    function onWeeklyDatePick(dateStr, shouldApply = true) {
        if (!dateStr) return;
        const picked = new Date(dateStr + 'T00:00:00');
        if (isNaN(picked.getTime())) return;

        const day = picked.getDay();
        const diffToMonday = day === 0 ? -6 : 1 - day;

        const monday = new Date(picked);
        monday.setDate(picked.getDate() + diffToMonday);

        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);

        const fmtISO = (dt) => {
            const yr = dt.getFullYear();
            const mo = String(dt.getMonth() + 1).padStart(2, '0');
            const da = String(dt.getDate()).padStart(2, '0');
            return `${yr}-${mo}-${da}`;
        };

        const fmtDisplay = (dt) => {
            return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        };

        const startInput = document.getElementById('filterStartDate');
        const endInput = document.getElementById('filterEndDate');
        const displayEl = document.getElementById('filterWeeklyDisplay');

        if (startInput) startInput.value = fmtISO(monday);
        if (endInput) endInput.value = fmtISO(sunday);
        if (displayEl) displayEl.value = `${fmtDisplay(monday)} - ${fmtDisplay(sunday)}`;
        if (shouldApply) applyFilters();
    }

    function onMonthlySelectChange(shouldApply = true) {
        const monthVal = document.getElementById('filterMonthSelect')?.value || '10';
        const yearVal = parseInt(document.getElementById('filterMonthYearSelect')?.value || '2026', 10);

        const lastDay = new Date(yearVal, parseInt(monthVal, 10), 0).getDate();
        const startStr = `${yearVal}-${monthVal}-01`;
        const endStr = `${yearVal}-${monthVal}-${String(lastDay).padStart(2, '0')}`;

        const startInput = document.getElementById('filterStartDate');
        const endInput = document.getElementById('filterEndDate');
        if (startInput) startInput.value = startStr;
        if (endInput) endInput.value = endStr;
        if (shouldApply) applyFilters();
    }

    function onYearlySelectChange(shouldApply = true) {
        const yearVal = document.getElementById('filterYearSelect')?.value || '2026';
        const startInput = document.getElementById('filterStartDate');
        const endInput = document.getElementById('filterEndDate');
        if (startInput) startInput.value = `${yearVal}-01-01`;
        if (endInput) endInput.value = `${yearVal}-12-31`;
        if (shouldApply) applyFilters();
    }

    function syncHiddenPeriodDates() {
        const periodSel = document.getElementById('filterPeriod');
        if (periodSel && periodSel.value !== 'custom') {
            periodSel.value = 'custom';
        }
        const startInput = document.getElementById('filterStartDate');
        const endInput = document.getElementById('filterEndDate');
        if (startInput && endInput && startInput.value && endInput.value && startInput.value > endInput.value) {
            endInput.value = startInput.value;
        }
        if (startInput?.value || endInput?.value) {
            applyFilters();
        }
    }

    // =========================================================================
    // 4. APPLY & RESET FILTERS (AJAX)
    // =========================================================================
    function applyFilters() {
        const periodType = document.getElementById('filterPeriod')?.value || 'all';
        const startDate = document.getElementById('filterStartDate')?.value || '';
        const endDate = document.getElementById('filterEndDate')?.value || '';
        const allChecked = document.getElementById('checkAllCompanies')?.checked;
        const selectedCompanies = Array.from(document.querySelectorAll('.company-checkbox:checked')).map(cb => cb.value);

        const params = new URLSearchParams();
        if (periodType && periodType !== 'all') {
            params.set('period_type', periodType);
        }
        if (startDate) params.set('start_date', startDate);
        if (endDate) params.set('end_date', endDate);

        if (!allChecked && selectedCompanies.length > 0) {
            selectedCompanies.forEach(c => params.append('unit[]', c));
        }

        const pdfBtn = document.getElementById('btnPdfExportLink');
        if (pdfBtn) {
            pdfBtn.href = `{{ route('man-hours.export') }}?${params.toString()}`;
        }

        fetch(`${MH_BASE_URL}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            // 1. Update Header Big Date & Day
            if (data.stats && (data.stats.latest_day_name || data.stats.latest_date_only)) {
                const dayEl = document.getElementById('headerDayDisplay');
                const dateEl = document.getElementById('headerDateDisplay');
                if (dayEl && data.stats.latest_day_name) dayEl.textContent = data.stats.latest_day_name;
                if (dateEl && data.stats.latest_date_only) dateEl.textContent = data.stats.latest_date_only;
            } else if (data.latestActiveDateHeader) {
                const hDate = document.getElementById('headerDateDisplay');
                if (hDate) hDate.textContent = data.latestActiveDateHeader;
            }

            // 2. Update 4 Highlight Cards (Statis terhadap filter, hanya berubah bila ada data baru di DB) & Label Rekap
            const s = data.stats || {};
            const isFiltered = Boolean(s.is_filtered);

            if (s.domain_start_date) sliderDomainStart = s.domain_start_date;
            if (s.domain_end_date) sliderDomainEnd = s.domain_end_date;

            const recapHdrMh = document.getElementById('recapHeaderManhours');
            if (recapHdrMh) {
                recapHdrMh.textContent = isFiltered ? 'MAN HOURS' : 'MAN HOURS (TODAY)';
            }

            if (s.chart_period_badge) {
                const trendBadge = document.getElementById('trendPeriodBadge');
                const recapBadge = document.getElementById('recapPeriodBadge');
                if (trendBadge) trendBadge.textContent = s.chart_period_badge;
                if (recapBadge) recapBadge.textContent = s.chart_period_badge;
            }

            const sliderTicks = s.slider_ticks || data.trendChart?.slider_ticks;
            if (Array.isArray(sliderTicks) && sliderTicks.length === 4) {
                sliderTicks.forEach((tickVal, idx) => {
                    const tickEl = document.getElementById(`trendSliderTick${idx}`);
                    if (tickEl) tickEl.textContent = tickVal;
                });
            }

            document.getElementById('cardManpowerToday').textContent = Number(s.manpower_today ?? 0).toLocaleString('id-ID');
            document.getElementById('cardManpowerMax').textContent = Number(s.manpower_max ?? 0).toLocaleString('id-ID');
            document.getElementById('cardManpowerMaxDate').textContent = s.manpower_max_date ?? '-';
            document.getElementById('cardManhoursToday').textContent = Number(s.manhours_today ?? 0).toLocaleString('id-ID');
            const cumEl = document.getElementById('cardManhoursCumulative');
            if (cumEl) {
                cumEl.textContent = Number(s.manhours_cumulative ?? s.cumulative_man_hours ?? 0).toLocaleString('id-ID');
            }

            // 3. Update Chart
            if (data.trendChart) {
                fullChartData = data.trendChart;
                initTrendStockChart(fullChartData);
            }

            // 4. Update Pie Chart & Legend
            currentTodayBreakdown = data.todayBreakdown || [];
            currentTodayTotal = Number(data.todayTotalWorkers || 0);
            initPieChart(currentTodayBreakdown);
            renderPieLegend(currentTodayBreakdown, currentTodayTotal);

            // 5. Update Table
            renderMainTable(data.historyTableRows || []);
        })
        .catch(err => console.error('Filter apply error:', err));
    }

    function renderMainTable(rows) {
        const tbody = document.getElementById('manpowerTableBody');
        if (!tbody) return;

        const tableCompanies = TARGET_COMPANIES;

        if (rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${tableCompanies.length + 8}" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        Tidak ada data Man Hours &amp; Manpower pada periode ini.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = rows.map((r, idx) => {
            const compCells = tableCompanies.map(cName => {
                const val = r.units?.[cName] ?? 0;
                return `<td style="text-align: center;">${val}</td>`;
            }).join('');

            return `
                <tr data-date="${r.date}">
                    <td style="text-align: center; color: #64748b;">${idx + 1}</td>
                    <td class="fw-bold">${r.date_formatted}</td>
                    ${compCells}
                    <td style="text-align: center;" class="fw-bold text-dark">${r.total_workers}</td>
                    <td style="text-align: center;" class="text-secondary">${Number(r.hours_per_worker).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}</td>
                    <td style="text-align: center;" class="fw-bold text-success">${Number(r.total_man_hours).toLocaleString('id-ID')}</td>
                    <td style="text-align: center;">
                        <span class="badge-status-green">
                            <i class="bi bi-check-circle-fill"></i> Sudah Diinput
                        </span>
                    </td>
                    <td style="text-align: center; font-size: 11.5px; color: #64748b;">
                        ${r.last_update ?? '-'}
                    </td>
                    <td style="text-align: center;">
                        <div class="table-actions-cell">
                            <button type="button" class="table-action-icon" onclick="viewDetailDate('${r.date}')" title="Lihat Detail">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${IS_ADMIN_MANAGEMENT ? `
                            <button type="button" class="table-action-icon" onclick="openBatchManhourModal('${r.date}')" title="Edit Data">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="table-action-icon trash-icon" onclick="deleteDateRecord('${r.date}')" title="Hapus Data">
                                <i class="bi bi-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Table Search
    document.getElementById('tableSearchInput')?.addEventListener('input', function (e) {
        const q = (e.target.value || '').toLowerCase().trim();
        document.querySelectorAll('#manpowerTableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // =========================================================================
    // 5. MODAL INPUT & EDIT BATCH
    // =========================================================================
    function openBatchManhourModal(targetDate = null) {
        const dateInput = document.getElementById('modalRecordDate');
        const chosenDate = targetDate || new Date().toISOString().substring(0, 10);
        if (dateInput) {
            dateInput.value = chosenDate;
        }

        const title = document.getElementById('batchModalTitle');
        if (title) {
            title.textContent = targetDate ? `Edit Man Hours & Manpower (${targetDate})` : 'Input Man Hours & Manpower';
        }

        // Reset inputs to 0 before loading
        TARGET_COMPANIES.forEach(comp => {
            const fieldKey = getCompanyInputKey(comp);
            const wInput = document.getElementById(`inputWorker_${fieldKey}`);
            const hInput = document.getElementById(`inputHours_${fieldKey}`);
            const subInput = document.getElementById(`subtotalHours_${fieldKey}`);
            if (wInput) wInput.value = 0;
            if (hInput) hInput.value = 8;
            if (subInput) subInput.value = 0;
        });
        calcModalLiveTotal();

        if (targetDate) {
            loadDateForBatchInput(chosenDate);
        } else {
            loadDateForBatchInput(chosenDate);
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBatchManhour')).show();
    }

    function loadDateForBatchInput(dateVal) {
        if (!dateVal) return;

        fetch(`${MH_BASE_URL}/by-date?record_date=${encodeURIComponent(dateVal)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.units)) return;

            data.units.forEach(u => {
                const rawKey = u.unit_name || u.unit;
                const fieldKey = getCompanyInputKey(rawKey);
                const wInput = document.getElementById(`inputWorker_${fieldKey}`) || document.getElementById(`inputWorker_${rawKey}`);
                const hInput = document.getElementById(`inputHours_${fieldKey}`) || document.getElementById(`inputHours_${rawKey}`);
                const subInput = document.getElementById(`subtotalHours_${fieldKey}`) || document.getElementById(`subtotalHours_${rawKey}`);
                const wVal = u.worker_count ?? 0;
                const hVal = u.hours_per_worker ?? 8;
                const subVal = u.calculated_man_hours !== undefined ? Math.round(Number(u.calculated_man_hours) * 100) / 100 : Math.round(wVal * hVal * 100) / 100;
                if (wInput) wInput.value = wVal;
                if (hInput) hInput.value = hVal;
                if (subInput) subInput.value = subVal;
            });

            calcModalLiveTotal();
        })
        .catch(() => {});
    }

    function onModalWorkerOrHoursInput(fieldKey) {
        const wInput = document.getElementById(`inputWorker_${fieldKey}`);
        const hInput = document.getElementById(`inputHours_${fieldKey}`);
        const subInput = document.getElementById(`subtotalHours_${fieldKey}`);

        const wVal = parseInt(wInput?.value || '0', 10) || 0;
        const hVal = parseFloat(hInput?.value || '0') || 0;
        const sub = Math.round(wVal * hVal * 100) / 100;

        if (subInput) {
            subInput.value = sub;
        }
        calcModalLiveTotal();
    }

    function onModalSubtotalManualInput(fieldKey) {
        // Jika subtotal diubah manual, jam kerja tetap tidak boleh otomatis berubah
        calcModalLiveTotal();
    }

    function calcModalLiveTotal() {
        let totW = 0;
        let totMh = 0;

        TARGET_COMPANIES.forEach(comp => {
            const fieldKey = getCompanyInputKey(comp);
            const wInput = document.getElementById(`inputWorker_${fieldKey}`) || document.getElementById(`inputWorker_${comp}`);
            const hInput = document.getElementById(`inputHours_${fieldKey}`) || document.getElementById(`inputHours_${comp}`);
            const subEl = document.getElementById(`subtotalHours_${fieldKey}`) || document.getElementById(`subtotalHours_${comp}`);

            const wVal = parseInt(wInput?.value || '0', 10) || 0;
            const hVal = parseFloat(hInput?.value || '8') || 0;

            let sub = wVal * hVal;
            if (subEl && subEl.tagName === 'INPUT') {
                sub = parseFloat(subEl.value || '0') || 0;
            } else if (subEl) {
                subEl.textContent = Math.round(sub).toLocaleString('id-ID');
            }

            totW += wVal;
            totMh += sub;
        });

        const avgH = totW > 0 ? (totMh / totW) : 0.0;

        document.getElementById('modalLiveWorkers').textContent = totW.toLocaleString('id-ID');
        document.getElementById('modalLiveManhours').textContent = Math.round(totMh).toLocaleString('id-ID');
        if (document.getElementById('modalLiveAvgHours')) {
            document.getElementById('modalLiveAvgHours').textContent = avgH.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
        }
    }

    function submitBatchManhour(e) {
        e.preventDefault();

        const dateVal = document.getElementById('modalRecordDate')?.value;
        if (!dateVal) {
            Swal.fire('Peringatan', 'Tanggal pencatatan wajib diisi.', 'warning');
            return;
        }

        const unitsPayload = [];
        TARGET_COMPANIES.forEach(comp => {
            const fieldKey = getCompanyInputKey(comp);
            const wInput = document.getElementById(`inputWorker_${fieldKey}`) || document.getElementById(`inputWorker_${comp}`);
            const hInput = document.getElementById(`inputHours_${fieldKey}`) || document.getElementById(`inputHours_${comp}`);
            const subInput = document.getElementById(`subtotalHours_${fieldKey}`) || document.getElementById(`subtotalHours_${comp}`);

            const wVal = parseInt(wInput?.value || '0', 10) || 0;
            const hVal = parseFloat(hInput?.value || '8') || 0;
            const subVal = subInput && subInput.tagName === 'INPUT'
                ? (parseFloat(subInput.value || '0') || 0)
                : (wVal * hVal);

            unitsPayload.push({
                unit: comp,
                category: comp,
                worker_count: wVal,
                hours_per_worker: hVal,
                subtotal_man_hours: subVal
            });
        });

        const btn = document.getElementById('btnSaveBatchManhour');
        btn.disabled = true;

        fetch(`${MH_BASE_URL}/batch`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                record_date: dateVal,
                units: unitsPayload
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (!data.success) {
                Swal.fire('Gagal Menyimpan', data.message || 'Terjadi kesalahan.', 'error');
                return;
            }

            bootstrap.Modal.getInstance(document.getElementById('modalBatchManhour'))?.hide();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Disimpan',
                text: 'Data Man Hours & Manpower berhasil disimpan.',
                timer: 1600,
                showConfirmButton: false
            });

            applyFilters();
        })
        .catch(() => {
            btn.disabled = false;
            Swal.fire('Error', 'Terjadi kesalahan saat memproses data.', 'error');
        });
    }

    // =========================================================================
    // 6. VIEW DETAIL MODAL
    // =========================================================================
    function viewDetailDate(dateStr) {
        document.getElementById('detailModalDateText').textContent = dateStr;
        fetch(`${MH_BASE_URL}/by-date?record_date=${encodeURIComponent(dateStr)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.units)) return;

            const tbody = document.getElementById('detailModalTbody');
            let totW = 0;
            let totMh = 0;

            const displayUnits = data.units.filter(u => TARGET_COMPANIES.includes(u.unit_name));

            tbody.innerHTML = displayUnits.map(u => {
                const sub = u.calculated_man_hours !== undefined ? Number(u.calculated_man_hours) : ((u.worker_count || 0) * (u.hours_per_worker || 8));
                totW += (u.worker_count || 0);
                totMh += sub;
                return `
                    <tr>
                        <td class="fw-bold">${u.unit_name}</td>
                        <td class="text-center">${u.worker_count || 0}</td>
                        <td class="text-center">${Number(u.hours_per_worker || 8).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}</td>
                        <td class="text-end fw-bold text-success">${Math.round(sub).toLocaleString('id-ID')} Jam</td>
                    </tr>
                `;
            }).join('');

            const avgH = totW > 0 ? (totMh / totW) : 0.0;
            document.getElementById('detailModalTotalWorkers').textContent = `${totW} Orang`;
            document.getElementById('detailModalAvgHours').textContent = `${avgH.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} Jam`;
            document.getElementById('detailModalTotalManhours').textContent = `${Math.round(totMh).toLocaleString('id-ID')} Jam`;

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailDate')).show();
        })
        .catch(() => {});
    }

    // =========================================================================
    // 7. DELETE DATE RECORD
    // =========================================================================
    function deleteDateRecord(dateStr) {
        Swal.fire({
            title: 'Hapus Data?',
            html: `Apakah Anda yakin ingin menghapus seluruh data Man Hours & Manpower pada tanggal <strong>${dateStr}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(res => {
            if (!res.isConfirmed) return;

            fetch(`${MH_BASE_URL}/date/${dateStr}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    applyFilters();
                } else {
                    Swal.fire('Error', data.message || 'Gagal menghapus data.', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error'));
        });
    }

    // =========================================================================
    // 8. MANAJEMEN PERUSAHAAN (HIERARCHY TREE, EDITABLE KETERANGAN, SWITCH STATUS)
    // =========================================================================
    let masterHierarchyCompanies = @json($hierarchicalCompanies ?? []);
    let openExpandedParents = { 'GDE (All)': true, 'JOTRE (All)': true };

    const COMPANY_ICON_MAP = {
        'gde': { icon: 'bi-buildings', bg: '#ecfdf5', color: '#059669' },
        'kodipa': { icon: 'bi-building', bg: '#eff6ff', color: '#2563eb' },
        'jotre': { icon: 'bi-cone-striped', bg: '#fef3c7', color: '#d97706' },
        'pjse': { icon: 'bi-cpu', bg: '#ede9fe', color: '#7c3aed' },
        'adp': { icon: 'bi-broadcast-pin', bg: '#ede9fe', color: '#7c3aed' },
        'sokko': { icon: 'bi-wrench-adjustable-circle', bg: '#ede9fe', color: '#7c3aed' },
        'bismo': { icon: 'bi-hospital', bg: '#ede9fe', color: '#7c3aed' },
        'wcp': { icon: 'bi-shield-check', bg: '#fffbeb', color: '#d97706' },
        'tdp': { icon: 'bi-shield-shaded', bg: '#eff6ff', color: '#2563eb' },
        'rentokil': { icon: 'bi-bug', bg: '#ffe4e6', color: '#e11d48' },
        'pmc': { icon: 'bi-diagram-3', bg: '#ecfeff', color: '#0891b2' },
        'bhumi': { icon: 'bi-leaf', bg: '#f0fdf4', color: '#16a34a' },
        'environesia': { icon: 'bi-tree', bg: '#f0fdf4', color: '#16a34a' },
        'praktik': { icon: 'bi-mortarboard', bg: '#eef2ff', color: '#4f46e5' },
    };

    const DEFAULT_DESCRIPTION_MAP = {
        'gde (all)': 'Perusahaan Utama',
        'gde (only)': 'Perusahaan Utama',
        'kodipa': 'Koperasi Perusahaan',
        'jotre (all)': 'Maincon',
        'jotre (only)': 'Maincon',
        'adp': 'Subcon',
        'sokko': 'Subcon',
        'bismo': 'Subcon',
        'wcp': 'Subcon',
        'pjse': 'Utility',
        'pjse (utility)': 'Utility',
        'tdp': 'BUJP / Security',
        'tdp (bujp)': 'BUJP / Security',
        'rentokil': 'Pest Control',
        'rentokil (pest control)': 'Pest Control',
        'pmc': 'Project Management Consultant',
        'bhumi phala': 'Maintenance EWS',
        'bhumi phala (maintenance ews)': 'Maintenance EWS',
    };

    function getCompanyIconMeta(name) {
        const lower = (name || '').toLowerCase();
        for (const [key, meta] of Object.entries(COMPANY_ICON_MAP)) {
            if (lower.includes(key)) return meta;
        }
        return { icon: 'bi-building', bg: '#f1f5f9', color: '#64748b' };
    }

    function getCompanyDescription(comp) {
        if (comp.description && String(comp.description).trim() !== '') {
            return escapeHtml(comp.description);
        }
        const key = String(comp.name || '').toLowerCase().trim();
        if (DEFAULT_DESCRIPTION_MAP[key]) {
            return escapeHtml(DEFAULT_DESCRIPTION_MAP[key]);
        }
        return '<span class="text-muted">—</span>';
    }

    function openManageCompaniesModal() {
        renderCompanyTreeTable();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalManageCompanies')).show();
    }

    function toggleParentExpand(parentName) {
        openExpandedParents[parentName] = !openExpandedParents[parentName];
        renderCompanyTreeTable(document.getElementById('manageCompanySearchInput')?.value || '');
    }

    function getSortedHierarchyCompanies(list) {
        if (!Array.isArray(list)) return [];
        const getBucket = (c) => {
            if (c.is_required) return 0;
            if (Number(c.sort_order) === 1000) return 1;
            return 2;
        };
        return [...list].sort((a, b) => {
            const bucketA = getBucket(a);
            const bucketB = getBucket(b);
            if (bucketA !== bucketB) return bucketA - bucketB;

            if (bucketA === 0 || bucketA === 1) {
                const sortA = Number(a.sort_order ?? 100);
                const sortB = Number(b.sort_order ?? 100);
                if (sortA !== sortB) return sortA - sortB;
                const nameCmp = String(a.name || '').localeCompare(String(b.name || ''));
                if (nameCmp !== 0) return nameCmp;
            }

            return Number(a.id || 0) - Number(b.id || 0);
        });
    }

    function renderCompanyTreeTable(searchKeyword) {
        const tbody = document.getElementById('companyTreeTableBody');
        if (!tbody) return;

        const activeSearch = searchKeyword !== undefined
            ? searchKeyword
            : (document.getElementById('manageCompanySearchInput')?.value || '');
        const q = (activeSearch || '').toLowerCase().trim();

        const sortedCompanies = getSortedHierarchyCompanies(masterHierarchyCompanies);
        const rootCompanies = sortedCompanies.filter(c => !c.parent_company_id);

        let html = '';

        // Render seluruh perusahaan induk/mandiri sesuai urutan konsisten (perusahaan lama tetap di atas, perusahaan baru di paling bawah)
        rootCompanies.forEach(main => {
            const subcons = sortedCompanies.filter(c => c.parent_company_id === main.id && c.name !== 'PJSE');
            const mainDisplayName = main.display_name || main.name || '';
            const isMatchSelf = main.name.toLowerCase().includes(q)
                || mainDisplayName.toLowerCase().includes(q)
                || (main.description || '').toLowerCase().includes(q);
            const isMatchChild = subcons.some(s => {
                const sDisp = s.display_name || s.name || '';
                return s.name.toLowerCase().includes(q)
                    || sDisp.toLowerCase().includes(q)
                    || (s.description || '').toLowerCase().includes(q);
            });

            if (q && !isMatchSelf && !isMatchChild) return;

            const isExpanded = q ? true : (openExpandedParents[main.name] !== false);
            const meta = getCompanyIconMeta(main.name);
            const isJotre = main.name.includes('JOTRE');
            const desc = getCompanyDescription(main);
            const displayName = main.display_name || main.name;

            html += `
                <tr class="parent-row ${isJotre ? 'is-jotre' : ''}" data-id="${main.id}">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            ${subcons.length > 0 ? `
                                <button type="button" class="btn btn-sm p-0 border-0 text-muted" onclick="toggleParentExpand('${escapeHtml(main.name)}')" style="width: 18px;">
                                    <i class="bi bi-chevron-${isExpanded ? 'down' : 'right'}"></i>
                                </button>
                            ` : '<span style="display:inline-block; width:18px;"></span>'}
                            <div class="company-icon-box" style="background: ${meta.bg}; color: ${meta.color};">
                                <i class="bi ${meta.icon}"></i>
                            </div>
                            <span class="fw-bold text-dark" style="font-size: 13px;">${escapeHtml(displayName)}</span>
                        </div>
                    </td>
                    <td>
                        <span class="text-secondary small">${desc}</span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center gap-2">
                            <label class="toggle-switch-ios">
                                <input type="checkbox" ${main.is_active ? 'checked' : ''} onchange="toggleHierarchyCompanyActive(${main.id}, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="small fw-semibold ${main.is_active ? 'text-success' : 'text-muted'}">${main.is_active ? 'Aktif' : 'Nonaktif'}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center justify-content-center gap-2">
                            @adminManagement
                            <button type="button" class="btn-add-subcont-pill" onclick="openCompanyFormPopup('subcon', ${main.id}, '${escapeHtml(main.name)}')">
                                <i class="bi bi-plus-lg"></i> Tambah Sub Contractor
                            </button>
                            <button type="button" class="btn-action-icon-square" onclick="openCompanyFormPopup('edit', ${main.id})" title="Edit Perusahaan">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn-action-icon-square text-danger" onclick="deleteHierarchyCompany(${main.id})" title="Hapus Perusahaan">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endadminManagement
                        </div>
                    </td>
                </tr>
            `;

            if (isExpanded) {
                subcons.forEach(sub => {
                    const subDisplayName = sub.display_name || sub.name;
                    if (q && !sub.name.toLowerCase().includes(q) && !subDisplayName.toLowerCase().includes(q) && !(sub.description || '').toLowerCase().includes(q)) return;

                    const subMeta = getCompanyIconMeta(sub.name);
                    const subDesc = getCompanyDescription(sub);

                    html += `
                        <tr class="child-row" data-id="${sub.id}">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="tree-branch-line"></span>
                                    <div class="company-icon-box me-2" style="background: ${subMeta.bg}; color: ${subMeta.color};">
                                        <i class="bi ${subMeta.icon}"></i>
                                    </div>
                                    <span class="fw-semibold text-dark" style="font-size: 12.5px;">${escapeHtml(subDisplayName)}</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-secondary small">${subDesc}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <label class="toggle-switch-ios">
                                        <input type="checkbox" ${sub.is_active ? 'checked' : ''} onchange="toggleHierarchyCompanyActive(${sub.id}, this.checked)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="small fw-semibold ${sub.is_active ? 'text-success' : 'text-muted'}">${sub.is_active ? 'Aktif' : 'Nonaktif'}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                    @adminManagement
                                    <button type="button" class="btn-add-subcont-pill" onclick="openCompanyFormPopup('subcon', ${main.id}, '${escapeHtml(main.name)}')">
                                        <i class="bi bi-plus-lg"></i> Tambah Sub Contractor
                                    </button>
                                    <button type="button" class="btn-action-icon-square" onclick="openCompanyFormPopup('edit', ${sub.id})" title="Edit Sub Contractor">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn-action-icon-square text-danger" onclick="deleteHierarchyCompany(${sub.id})" title="Hapus Sub Contractor">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endadminManagement
                                </div>
                            </td>
                        </tr>
                    `;
                });
            }
        });

        tbody.innerHTML = html || `
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                    <i class="bi bi-search fs-3 d-block mb-1"></i>
                    Tidak ada perusahaan yang cocok dengan pencarian.
                </td>
            </tr>
        `;
    }

    function filterCompanyTreeTable(val) {
        renderCompanyTreeTable(val);
    }

    function onFloatParentChange(val) {
        const parentInput = document.getElementById('floatCompParentId');
        if (parentInput) parentInput.value = val;
    }

    function openCompanyFormPopup(mode = 'main', parentId = null, parentName = '') {
        const card = document.getElementById('companyFloatingFormCard');
        if (!card) return;

        const titleEl = document.getElementById('floatingCardTitle');
        const iconBox = document.getElementById('floatingCardIconBox');
        const idInput = document.getElementById('floatCompId');
        const catInput = document.getElementById('floatCompCategory');
        const parentInput = document.getElementById('floatCompParentId');
        const nameInput = document.getElementById('floatCompName');
        const descInput = document.getElementById('floatCompDesc');
        const activeInput = document.getElementById('floatCompIsActive');
        const statusText = document.getElementById('floatCompStatusText');
        const parentSelectWrapper = document.getElementById('floatParentSelectWrapper');

        nameInput.readOnly = false;
        nameInput.style.backgroundColor = '';
        nameInput.style.cursor = '';
        if (parentSelectWrapper) parentSelectWrapper.classList.add('d-none');

        if (mode === 'subcon') {
            const targetParent = masterHierarchyCompanies.find(c => c.id === Number(parentId));
            const pName = targetParent ? targetParent.name : parentName;
            titleEl.textContent = `Tambah Sub Contractor: ${pName}`;
            iconBox.style.background = '#ede9fe';
            iconBox.style.color = '#7c3aed';
            iconBox.innerHTML = '<i class="bi bi-building"></i>';
            idInput.value = '';
            catInput.value = 'subcon';
            parentInput.value = parentId || '';

            nameInput.value = '';
            nameInput.placeholder = 'Contoh: PT SUB CONTRACTOR BARU';
            descInput.value = 'Subcon';

            activeInput.checked = true;
            statusText.textContent = 'Aktif';
            statusText.className = 'small fw-semibold text-success';
        } else if (mode === 'edit') {
            const comp = masterHierarchyCompanies.find(c => c.id === Number(parentId));
            if (!comp) return;

            const isSub = comp.category === 'subcon';
            titleEl.textContent = isSub ? `Edit Sub Contractor: ${comp.name}` : `Edit Perusahaan: ${comp.name}`;
            iconBox.style.background = isSub ? '#ede9fe' : '#ecfdf5';
            iconBox.style.color = isSub ? '#7c3aed' : '#059669';
            iconBox.innerHTML = '<i class="bi bi-pencil-square"></i>';

            idInput.value = comp.id;
            catInput.value = comp.category;
            parentInput.value = comp.parent_company_id || '';
            nameInput.value = comp.name;

            if (comp.is_required) {
                nameInput.readOnly = true;
                nameInput.style.backgroundColor = '#f8fafc';
            }

            const key = String(comp.name || '').toLowerCase().trim();
            descInput.value = comp.description || DEFAULT_DESCRIPTION_MAP[key] || '';
            activeInput.checked = !!comp.is_active;
            statusText.textContent = comp.is_active ? 'Aktif' : 'Nonaktif';
            statusText.className = comp.is_active ? 'small fw-semibold text-success' : 'small fw-semibold text-muted';
        } else {
            titleEl.textContent = 'Tambah Perusahaan';
            iconBox.style.background = '#ecfdf5';
            iconBox.style.color = '#059669';
            iconBox.innerHTML = '<i class="bi bi-buildings"></i>';
            idInput.value = '';
            catInput.value = 'main';
            parentInput.value = '';
            nameInput.value = '';
            nameInput.placeholder = 'Contoh: MITRA BARU';
            descInput.value = '';
            activeInput.checked = true;
            statusText.textContent = 'Aktif';
            statusText.className = 'small fw-semibold text-success';
        }

        card.classList.add('show');
        if (!nameInput.readOnly) {
            nameInput.focus();
        } else {
            descInput.focus();
        }
    }

    function closeCompanyFormPopup() {
        document.getElementById('companyFloatingFormCard')?.classList.remove('show');
    }

    function updateFloatStatusLabel(isChecked) {
        const statusText = document.getElementById('floatCompStatusText');
        if (statusText) {
            statusText.textContent = isChecked ? 'Aktif' : 'Nonaktif';
            statusText.className = isChecked ? 'small fw-semibold text-success' : 'small fw-semibold text-muted';
        }
    }

    function submitFloatingCompany(e) {
        e.preventDefault();
        const id = document.getElementById('floatCompId')?.value;
        const name = document.getElementById('floatCompName')?.value.trim();
        const desc = document.getElementById('floatCompDesc')?.value.trim();
        const category = document.getElementById('floatCompCategory')?.value || 'main';
        const parentId = document.getElementById('floatCompParentId')?.value;
        const isActive = document.getElementById('floatCompIsActive')?.checked;

        if (!name) {
            Swal.fire('Peringatan', 'Nama perusahaan wajib diisi.', 'warning');
            return;
        }

        const btn = document.getElementById('btnSubmitFloatCompany');
        btn.disabled = true;

        const url = id ? `/daily-work/masters/companies/${id}` : '/daily-work/masters/companies';
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                name: name,
                category: category,
                parent_company_id: category === 'subcon' && parentId ? Number(parentId) : null,
                description: desc,
                is_active: isActive
            })
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                let errorMsg = data.message || 'Terjadi kesalahan validasi.';
                if (data.errors && typeof data.errors === 'object') {
                    const firstErrKey = Object.keys(data.errors)[0];
                    if (firstErrKey && data.errors[firstErrKey].length > 0) {
                        errorMsg = data.errors[firstErrKey][0];
                    }
                }
                throw new Error(errorMsg);
            }
            return data;
        })
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                if (data.companies) {
                    masterHierarchyCompanies = data.companies;
                } else if (id) {
                    const idx = masterHierarchyCompanies.findIndex(c => c.id === Number(id));
                    if (idx !== -1) {
                        masterHierarchyCompanies[idx].name = name;
                        masterHierarchyCompanies[idx].description = desc;
                        masterHierarchyCompanies[idx].is_active = isActive;
                    }
                }
                closeCompanyFormPopup();
                renderCompanyTreeTable();
                Swal.fire({
                    icon: 'success',
                    title: id ? 'Tersimpan' : 'Berhasil',
                    text: data.message || 'Data perusahaan berhasil disimpan.',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            Swal.fire('Gagal', err.message || 'Terjadi kesalahan sistem.', 'error');
        });
    }

    function toggleHierarchyCompanyActive(id, newStatus) {
        fetch(`/daily-work/masters/companies/${id}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ is_active: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const comp = masterHierarchyCompanies.find(c => c.id === Number(id));
                if (comp) comp.is_active = newStatus;
                renderCompanyTreeTable(document.getElementById('manageCompanySearchInput')?.value || '');
            } else {
                Swal.fire('Error', data.message || 'Gagal mengubah status.', 'error');
                renderCompanyTreeTable();
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            renderCompanyTreeTable();
        });
    }

    function deleteHierarchyCompany(id) {
        const comp = masterHierarchyCompanies.find(c => c.id === Number(id));
        if (!comp) return;

        Swal.fire({
            title: 'Hapus Perusahaan?',
            html: `Apakah Anda yakin ingin menghapus <strong>"${escapeHtml(comp.name)}"</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(res => {
            if (!res.isConfirmed) return;

            fetch(`/daily-work/masters/companies/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async r => {
                const data = await r.json().catch(() => ({}));
                if (data.success) {
                    masterHierarchyCompanies = Array.isArray(data.companies)
                        ? data.companies
                        : masterHierarchyCompanies.filter(c => c.id !== Number(id));
                    renderCompanyTreeTable();
                    Swal.fire({ icon: 'success', title: 'Terhapus', text: data.message, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Tidak Dapat Dihapus', data.message || 'Gagal menghapus perusahaan.', 'warning');
                }
            })
            .catch(() => Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error'));
        });
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

    function resetAllFilters() {
        const periodSelect = document.getElementById('filterPeriod');
        if (periodSelect) {
            periodSelect.value = 'all';
            onPeriodDropdownChange('all', false);
        }

        const startInput = document.getElementById('filterStartDate');
        const endInput = document.getElementById('filterEndDate');
        if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';
        resetSliderHandlesVisual();

        const btnOut = document.getElementById('btnZoomOutTrend');
        const btnIn = document.getElementById('btnZoomInTrend');
        if (btnOut) btnOut.classList.add('active');
        if (btnIn) btnIn.classList.remove('active');

        const allMaster = document.getElementById('checkAllCompanies');
        if (allMaster) {
            allMaster.checked = true;
            toggleSelectAllCompanies(true);
        }

        const searchInput = document.getElementById('tableSearchInput');
        if (searchInput) {
            searchInput.value = '';
            document.querySelectorAll('#manpowerTableBody tr').forEach(row => row.style.display = '');
        }

        applyFilters();
    }

    // =========================================================================
    // INITIALIZATION
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function () {
        initTrendStockChart(fullChartData);
        initPieChart(currentTodayBreakdown);
        renderPieLegend(currentTodayBreakdown, currentTodayTotal);
        calcModalLiveTotal();
        initTrendRangeSliderDrag();

        onPeriodDropdownChange('all', false);
    });
</script>
@endpush
