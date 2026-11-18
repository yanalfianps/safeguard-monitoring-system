@extends('layouts.app', [
    'title' => 'Dashboard',
    'activeModule' => 'dashboard'
])

@push('styles')
<style>
    :root {
        --dash-navy: #0f273d;
        --dash-green: #059669;
        --dash-blue: #2563eb;
        --dash-purple: #7c3aed;
        --dash-orange: #f59e0b;
        --dash-red: #ef4444;
        --dash-border: #e2e8f0;
        --dash-card-bg: #ffffff;
    }

    .dashboard-container {
        padding-bottom: 30px;
    }

    /* Page Header */
    .dashboard-page-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
    }

    .dashboard-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #2563eb;
        color: white;
        display: grid;
        place-items: center;
        font-size: 22px;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    }

    .dashboard-title {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        line-height: 1.2;
    }

    .dashboard-subtitle {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }

    /* Project Timeline Full Width Card */
    .project-timeline-card {
        background: #ffffff;
        border: 1px solid var(--dash-border);
        border-radius: 14px;
        padding: 20px 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(15, 39, 61, 0.04);
        position: relative;
    }

    .timeline-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .timeline-title-group {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .timeline-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        color: white;
        display: grid;
        place-items: center;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }

    .timeline-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        line-height: 1.2;
    }

    .timeline-subtitle {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
    }

    .timeline-badge-percent {
        display: flex;
        align-items: baseline;
        gap: 8px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 8px 16px;
        border-radius: 12px;
    }

    .timeline-percent-number {
        font-size: 24px;
        font-weight: 900;
        color: #059669;
        line-height: 1;
        font-family: inherit;
    }

    .timeline-percent-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #047857;
        letter-spacing: 0.5px;
    }

    .timeline-progress-track {
        height: 16px;
        background: #f1f5f9;
        border-radius: 9999px;
        overflow: hidden;
        position: relative;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
    }

    .timeline-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #059669 0%, #0ea5e9 50%, #2563eb 100%);
        border-radius: 9999px;
        transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
    }

    .timeline-markers-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
        font-size: 11.5px;
        color: #64748b;
        flex-wrap: wrap;
        gap: 8px;
    }

    .timeline-marker-item strong {
        color: #0f172a;
    }

    /* 3 Cards Durasi Waktu */
    .timeline-metric-card {
        background: #ffffff;
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        height: 100%;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s;
    }

    .timeline-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -4px rgba(15, 39, 61, 0.08);
    }

    .metric-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 22px;
        flex-shrink: 0;
    }

    .metric-icon-box.days { background: #eff6ff; color: #2563eb; }
    .metric-icon-box.weeks { background: #ecfdf5; color: #059669; }
    .metric-icon-box.years { background: #fffbeb; color: #d97706; }

    .metric-content {
        flex: 1;
        min-width: 0;
    }

    .metric-label {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 2px;
    }

    .metric-value {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin-bottom: 3px;
    }

    .metric-unit {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }

    .metric-subtext {
        font-size: 11px;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Cards & Panels */
    .dash-card {
        background: var(--dash-card-bg);
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        padding: 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .dash-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .dash-card-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    /* 2 Card Utama: Total Man Hours & Total Manpower */
    .dashboard-hero-card {
        background: #ffffff;
        border: 1px solid var(--dash-border);
        border-radius: 14px;
        padding: 22px 24px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        text-decoration: none !important;
        color: inherit;
        cursor: pointer;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s, border-color 0.2s;
    }

    .dashboard-hero-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px -6px rgba(15, 39, 61, 0.12);
        color: inherit;
        text-decoration: none;
    }

    .dashboard-hero-card.man-hours:hover {
        border-color: #93c5fd;
    }

    .dashboard-hero-card.manpower:hover {
        border-color: #86efac;
    }

    .dashboard-hero-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .dashboard-hero-card.man-hours::before {
        background: linear-gradient(90deg, #2563eb, #38bdf8);
    }

    .dashboard-hero-card.manpower::before {
        background: linear-gradient(90deg, #059669, #34d399);
    }

    .hero-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .hero-card-badge {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 5px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .hero-card-badge.blue {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .hero-card-badge.green {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .hero-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 22px;
    }

    .hero-card-icon.blue {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #2563eb;
    }

    .hero-card-icon.green {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        color: #059669;
    }

    .hero-card-title {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .hero-card-value-wrap {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-bottom: 8px;
    }

    .hero-card-value {
        font-size: 34px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        letter-spacing: -0.5px;
    }

    .hero-card-unit {
        font-size: 15px;
        font-weight: 600;
        color: #64748b;
    }

    .hero-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 14px;
        border-top: 1px dashed #e2e8f0;
        margin-top: 12px;
        font-size: 12px;
        color: #64748b;
    }

    .hero-card-link {
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color 0.15s, transform 0.15s;
    }

    .hero-card-link:hover {
        color: #1d4ed8;
        transform: translateX(2px);
    }

    /* Middle Row: Insiden & Indikator HSSE */
    .subcard-insiden {
        border-radius: 10px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        height: 100%;
    }

    .subcard-insiden.red {
        background: #fef2f2;
        border: 1px solid #fee2e2;
    }

    .subcard-insiden.orange {
        background: #fff7ed;
        border: 1px solid #ffedd5;
    }

    .subcard-insiden.purple {
        background: #faf5ff;
        border: 1px solid #f3e8ff;
    }

    .icon-circle-insiden {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 22px;
        flex-shrink: 0;
    }

    .subcard-insiden.red .icon-circle-insiden {
        background: #fee2e2;
        color: #dc2626;
    }

    .subcard-insiden.orange .icon-circle-insiden {
        background: #ffedd5;
        color: #ea580c;
    }

    .subcard-insiden.purple .icon-circle-insiden {
        background: #f3e8ff;
        color: #7c3aed;
    }

    .insiden-info-label {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .insiden-info-value {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin: 2px 0;
    }

    .insiden-info-trend {
        font-size: 10.5px;
        font-weight: 600;
    }

    .insiden-info-trend.up { color: #dc2626; }
    .insiden-info-trend.down { color: #16a34a; }
    .insiden-info-trend.neutral { color: #64748b; }

    /* Subcard Indikator HSSE Wajib */
    .hsse-indicator-subcard {
        border-radius: 10px;
        border: 1px solid var(--dash-border);
        background: #ffffff;
        padding: 22px 16px;
        height: 100%;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .hsse-indicator-subcard:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    /* Helper Utilities for Equal Height & Flexbox Distribution */
    .h-full { height: 100% !important; }
    .w-full { width: 100% !important; }
    .flex-1 { flex: 1 1 0% !important; }
    .items-stretch { align-items: stretch !important; }
    .justify-between { justify-content: space-between !important; }
    .min-h-\[120px\] { min-height: 120px !important; }
    .py-6 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
    .py-8 { padding-top: 2rem !important; padding-bottom: 2rem !important; }

    .hsse-indicator-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }

    .hsse-ind-icon-name {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .hsse-ind-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        font-size: 16px;
    }

    .hsse-ind-icon-box.green { background: #d1fae5; color: #059669; }
    .hsse-ind-icon-box.orange { background: #ffedd5; color: #ea580c; }
    .hsse-ind-icon-box.purple { background: #ede9fe; color: #7c3aed; }

    .hsse-ind-name {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
    }

    .hsse-ind-info-btn {
        color: #94a3b8;
        font-size: 13px;
        cursor: pointer;
    }

    .hsse-ind-value {
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        margin: 12px 0 0;
        line-height: 1.1;
    }

    .hsse-ind-formula {
        font-size: 9.5px;
        color: #64748b;
        font-family: monospace;
        background: #f8fafc;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        margin-bottom: 6px;
        display: inline-block;
    }

    .hsse-ind-details {
        font-size: 10px;
        color: #64748b;
        border-top: 1px dashed #e2e8f0;
        padding-top: 6px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    /* Donut Charts Container */
    .donut-chart-box {
        position: relative;
        height: 160px;
        width: 160px;
        margin: 0 auto;
    }

    .donut-legend-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 5px;
    }

    .legend-color-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    /* Latest Tables & Events */
    .dash-table {
        font-size: 11.5px;
    }

    .dash-table th {
        font-size: 10.5px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 9px 12px;
    }

    .dash-table td {
        padding: 10px 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    .status-badge-pill {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
    }

    .status-badge-open { background: #dbeafe; color: #1d4ed8; }
    .status-badge-progress { background: #fef3c7; color: #b45309; }
    .status-badge-overdue { background: #fee2e2; color: #dc2626; }
    .status-badge-closed { background: #d1fae5; color: #047857; }

    /* Timeline Event Item */
    .event-list-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px dashed #f1f5f9;
    }

    .event-list-item:last-child {
        border-bottom: none;
    }

    .event-thumb-box {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        overflow: hidden;
        background: #f1f5f9;
        display: grid;
        place-items: center;
        color: #64748b;
        flex-shrink: 0;
    }

    .event-thumb-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .event-info {
        flex: 1;
        min-width: 0;
    }

    .event-title {
        font-size: 12px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event-date {
        font-size: 10.5px;
        color: #64748b;
    }

    .event-badge-tag {
        font-size: 10px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        white-space: nowrap;
    }

    .event-badge-tag.pelatihan { background: #fee2e2; color: #dc2626; }
    .event-badge-tag.inspeksi { background: #dbeafe; color: #2563eb; }
    .event-badge-tag.simulasi { background: #ede9fe; color: #7c3aed; }
    .event-badge-tag.sosialisasi { background: #d1fae5; color: #059669; }

    @media (max-width: 1200px) {
        .filter-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">

    <!-- PAGE HEADER -->
    <div class="dashboard-page-header">
        <div class="dashboard-icon-box">
            <i class="bi bi-speedometer2"></i>
        </div>
        <div>
            <h1 class="dashboard-title">Dashboard</h1>
            <div class="dashboard-subtitle">{{ config('branding.project_title', 'Project Manajemen Geodipa Unit Dieng 2') }}</div>
        </div>
    </div>

    <!-- 1. KARTU PROGRESS BAR PROYEK MEMANJANG (FULL WIDTH) -->
    <div class="project-timeline-card">
        <div class="timeline-card-header">
            <div class="timeline-title-group">
                <div class="timeline-icon-box">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <h2 class="timeline-title">Progress Waktu Proyek Dieng 2</h2>
                    <div class="timeline-subtitle">
                        Periode Kontrak: <strong>{{ $project_timeline['start_date_display'] }}</strong> &mdash; <strong>{{ $project_timeline['end_date_display'] }}</strong>
                        &bull; Total Durasi: <strong>{{ number_format($project_timeline['total_days'], 0, ',', '.') }} Hari Kalender</strong>
                    </div>
                </div>
            </div>
            <div class="timeline-badge-percent">
                <span class="timeline-percent-number">{{ $project_timeline['percentage_display'] }}</span>
                <span class="timeline-percent-label">Waktu Berjalan</span>
            </div>
        </div>

        <!-- Progress Bar Linier -->
        <div class="timeline-progress-track" title="Waktu Proyek Berjalan: {{ $project_timeline['percentage_display'] }}">
            <div class="timeline-progress-bar" style="width: {{ $project_timeline['percentage'] }}%;"></div>
        </div>

        <!-- Milestone Markers -->
        <div class="timeline-markers-row">
            <div class="timeline-marker-item">
                <i class="bi bi-play-circle-fill text-success me-1"></i> Mulai: <strong>{{ $project_timeline['start_date_display'] }}</strong>
            </div>
            <div class="timeline-marker-item text-center">
                <i class="bi bi-geo-alt-fill text-primary me-1"></i> Hari Ini: <strong>{{ $project_timeline['now_date_display'] }}</strong> ({{ $project_timeline['percentage_display'] }})
            </div>
            <div class="timeline-marker-item text-end">
                <i class="bi bi-flag-fill text-danger me-1"></i> Target Selesai: <strong>{{ $project_timeline['end_date_display'] }}</strong>
                <span class="badge bg-light text-secondary border ms-1">Sisa {{ number_format($project_timeline['remaining_days'], 0, ',', '.') }} Hari</span>
            </div>
        </div>
    </div>

    <!-- 2. 3 KARTU DURASI WAKTU PROYEK (HARI, MINGGU, TAHUN) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Hari Berjalan -->
        <div class="col-md-4">
            <div class="timeline-metric-card">
                <div class="metric-icon-box days">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-label">Hari Proyek Berjalan</div>
                    <div class="metric-value">
                        {{ number_format($project_timeline['elapsed_days'], 0, ',', '.') }}
                        <span class="metric-unit">Hari</span>
                    </div>
                    <div class="metric-subtext">Dari total {{ number_format($project_timeline['total_days'], 0, ',', '.') }} hari kalender proyek</div>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Minggu Berjalan -->
        <div class="col-md-4">
            <div class="timeline-metric-card">
                <div class="metric-icon-box weeks">
                    <i class="bi bi-calendar2-week-fill"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-label">Minggu Proyek Berjalan</div>
                    <div class="metric-value">
                        {{ number_format($project_timeline['elapsed_weeks'], 0, ',', '.') }}
                        <span class="metric-unit">Minggu</span>
                    </div>
                    <div class="metric-subtext">Akumulasi minggu kerja berjalan</div>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Tahun & Bulan Berjalan -->
        <div class="col-md-4">
            <div class="timeline-metric-card">
                <div class="metric-icon-box years">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-label">Durasi Waktu Berjalan</div>
                    <div class="metric-value">
                        {{ $project_timeline['elapsed_years'] }} <span class="metric-unit">Thn</span>
                        @if($project_timeline['elapsed_months_remainder'] > 0)
                            {{ $project_timeline['elapsed_months_remainder'] }} <span class="metric-unit">Bln</span>
                        @endif
                    </div>
                    <div class="metric-subtext">Terhitung sejak {{ $project_timeline['start_date_display'] }} ({{ $project_timeline['elapsed_total_months'] }} Bulan)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- BARIS KARTU UTAMA: 2 CARD BESAR BERDAMPINGAN (MAN HOURS & MANPOWER) -->
    <div class="row g-3 mb-4">
        <!-- Card Kiri: Total Akumulatif Jam Kerja Orang (Man Hours Kumulatif) -->
        <div class="col-lg-6">
            <a href="{{ route('man-hours.index') }}" class="dashboard-hero-card man-hours text-decoration-none">
                <div>
                    <div class="hero-card-header">
                        <div>
                            <div class="hero-card-title">Jam kerja aman</div>
                        </div>
                        <div class="hero-card-icon blue">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>

                    <div class="hero-card-value-wrap">
                        <div class="hero-card-value">{{ number_format($core_cards['man_hours']['total_hours'] ?? $core_cards['man_hours']['total'] ?? 0, 0, ',', '.') }}</div>
                        <div class="hero-card-unit">Jam Kerja</div>
                    </div>
                </div>

                <div class="hero-card-footer">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-check text-primary"></i>
                        <span>Update Terakhir: <strong>{{ $core_cards['man_hours']['latest_record_date_display'] ?? $core_cards['man_hours']['latest_date'] ?? 'Belum ada data' }}</strong></span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Card Kanan: Total Manpower / Jumlah Pekerja Aktif -->
        <div class="col-lg-6">
            <a href="{{ route('man-hours.index') }}" class="dashboard-hero-card manpower text-decoration-none">
                <div>
                    <div class="hero-card-header">
                        <div>
                            <div class="hero-card-title">Total Manpower (Jumlah Pekerja Aktif)</div>
                        </div>
                        <div class="hero-card-icon green">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>

                    <div class="hero-card-value-wrap">
                        <div class="hero-card-value">{{ number_format($core_cards['workforce']['total_workers'] ?? 0, 0, ',', '.') }}</div>
                        <div class="hero-card-unit">Orang Pekerja</div>
                    </div>
                </div>

                <div class="hero-card-footer">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event text-success"></i>
                        <span>Update Terakhir: <strong>{{ $core_cards['workforce']['latest_record_date_display'] ?? $core_cards['workforce']['latest_date'] ?? 'Belum ada data' }}</strong></span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- BARIS 2: TOTAL INSIDEN & LTI + INDIKATOR HSSE -->
    <div class="row g-4 mb-4 items-stretch">
        <!-- Kiri: Total Insiden & LTI -->
        <div class="col-12 col-lg-6 d-flex flex-column">
            <div class="dash-card h-full flex flex-col justify-between">
                <div>
                    <div class="dash-card-header mb-3">
                        <h2 class="dash-card-title">
                            <i class="bi bi-shield-exclamation text-danger"></i> Total Insiden &amp; LTI
                        </h2>
                    </div>
                    <div class="row g-3">
                        <!-- Subcard 1: Total Insiden (Panas Bumi) -->
                        <div class="col-6">
                            <div class="subcard-insiden red h-full">
                                <div class="icon-circle-insiden">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <div>
                                    <div class="insiden-info-label">Total Insiden</div>
                                    <div class="insiden-info-value">{{ number_format($total_insiden_panas_bumi, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Subcard 2: Total Lost Time Injury (LTI) -->
                        <div class="col-6">
                            <div class="subcard-insiden orange h-full">
                                <div class="icon-circle-insiden">
                                    <i class="bi bi-cone-striped"></i>
                                </div>
                                <div>
                                    <div class="insiden-info-label">Total Lost Time Injury (LTI)</div>
                                    <div class="insiden-info-value">{{ number_format($total_lti, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subcard 3: Total Hari Kerja Hilang (Full-Width Bawah) -->
                <div class="mt-3 w-full">
                    <div class="subcard-insiden purple">
                        <div class="icon-circle-insiden">
                            <i class="bi bi-calendar2-x-fill"></i>
                        </div>
                        <div>
                            <div class="insiden-info-label">Total Hari Kerja Hilang</div>
                            <div class="insiden-info-value">
                                {{ number_format($total_lwd, 0, ',', '.') }} <span style="font-size: 15px; font-weight: 600; color: #64748b;">Hari</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanan: Indikator HSSE (TRIR, LTI-FR, LTI-SR) -->
        <div class="col-12 col-lg-6 d-flex flex-column">
            <div class="dash-card h-full flex flex-col">
                <div class="dash-card-header mb-3">
                    <h2 class="dash-card-title">
                        <i class="bi bi-speedometer text-success"></i> Insiden
                    </h2>
                </div>

                <!-- Wrapper 3 Kotak Indikator (Vertically Centered & Fills White Space) -->
                <div class="flex-1 flex flex-col justify-center">
                    <div class="row g-3 h-full items-center">
                        <!-- Indikator 1: TRIR -->
                        <div class="col-4 h-full">
                            <div class="hsse-indicator-subcard h-full min-h-[120px] flex flex-col justify-center py-6">
                                <div class="hsse-indicator-header">
                                    <div class="hsse-ind-icon-name">
                                        <div class="hsse-ind-icon-box green">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <div class="hsse-ind-name">TRIR</div>
                                    </div>
                                    <i class="bi bi-info-circle hsse-ind-info-btn" data-bs-toggle="tooltip"
                                       title="Total Recordable Incident Rate. Rumus: (Total Insiden Panas Bumi / Total Man Hours) x 1.000.000"></i>
                                </div>
                                <div class="hsse-ind-value text-success">{{ number_format($trir, 2, ',', '.') }}</div>
                            </div>
                        </div>

                        <!-- Indikator 2: LTI-FR -->
                        <div class="col-4 h-full">
                            <div class="hsse-indicator-subcard h-full min-h-[120px] flex flex-col justify-center py-6">
                                <div class="hsse-indicator-header">
                                    <div class="hsse-ind-icon-name">
                                        <div class="hsse-ind-icon-box orange">
                                            <i class="bi bi-people-fill"></i>
                                        </div>
                                        <div class="hsse-ind-name">LTI-FR</div>
                                    </div>
                                    <i class="bi bi-info-circle hsse-ind-info-btn" data-bs-toggle="tooltip"
                                       title="Lost Time Injury Frequency Rate. Rumus: (Total LTI / Total Man Hours) x 1.000.000"></i>
                                </div>
                                <div class="hsse-ind-value text-warning">{{ number_format($lti_fr, 2, ',', '.') }}</div>
                            </div>
                        </div>

                        <!-- Indikator 3: LTI-SR -->
                        <div class="col-4 h-full">
                            <div class="hsse-indicator-subcard h-full min-h-[120px] flex flex-col justify-center py-6">
                                <div class="hsse-indicator-header">
                                    <div class="hsse-ind-icon-name">
                                        <div class="hsse-ind-icon-box purple">
                                            <i class="bi bi-calendar2-x"></i>
                                        </div>
                                        <div class="hsse-ind-name">LTI-SR</div>
                                    </div>
                                    <i class="bi bi-info-circle hsse-ind-info-btn" data-bs-toggle="tooltip"
                                       title="Lost Time Injury Severity Rate. Rumus: (Total Hari Hilang / Total Man Hours) x 1.000.000"></i>
                                </div>
                                <div class="hsse-ind-value text-purple" style="color: #7c3aed;">{{ number_format($lti_sr, 2, ',', '.') }}</div>
                            </div>
                        </div>

                        @if($total_man_hours == 0)
                            <div class="col-12 mt-2">
                                <div class="alert alert-light border text-muted py-1 px-2 mb-0 d-flex align-items-center gap-2" style="font-size: 11px;">
                                    <i class="bi bi-info-circle text-primary"></i>
                                    <span>Data Man Hours belum tersedia sehingga indikator bernilai <strong>0,00</strong>.</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BARIS 3: 4 DONUT CHARTS ISSUE & FOLLOW UP (HEALTH & SAFETY, SECURITY, ENVIRONMENT, SOCIAL) -->
    <div class="row g-3 mb-4">
        @foreach($issue_follow_up_charts as $chart)
            <div class="col-12 col-md-6 col-lg-3">
                <div class="dash-card bg-white rounded-lg shadow-sm p-4 border border-slate-200/60 h-100 d-flex flex-column justify-content-between" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                    <div>
                        <!-- Card Header: Title & Total Badge -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3 class="fw-bold mb-0 text-slate-800" style="font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                                {{ $chart['name'] }}
                            </h3>
                            <span class="badge border text-secondary bg-light" style="font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.5rem; border: 1px solid #cbd5e1 !important; color: #64748b; background-color: #f8fafc; border-radius: 9999px;">
                                Total: {{ $chart['total'] }}
                            </span>
                        </div>

                        <!-- Sub-header / Status Indicator Box -->
                        <div class="bg-slate-50 rounded p-2 text-xs mb-3 text-center" style="background-color: #f8fafc; border-radius: 6px; padding: 6px 10px; font-size: 0.78rem; margin-bottom: 12px;">
                            <span class="fw-semibold text-red-500" style="color: #ef4444; font-weight: 600;">
                                Open: {{ $chart['open_count'] }} ({{ $chart['open_percentage'] }}%)
                            </span>
                            <span class="mx-1 text-muted" style="color: #94a3b8;">•</span>
                            <span class="fw-semibold text-green-500" style="color: #10b981; font-weight: 600;">
                                Closed: {{ $chart['closed_count'] }} ({{ $chart['closed_percentage'] }}%)
                            </span>
                        </div>
                    </div>

                    <!-- Canvas Donut Chart -->
                    <div class="w-100 d-flex align-items-center justify-content-center flex-grow-1" style="height: 170px; position: relative;">
                        <canvas id="chart-{{ $chart['slug'] }}"></canvas>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- BARIS 4: TABEL ISU TINDAK LANJUT TERBARU & KEGIATAN TERBARU (EVENTS) -->
    <div class="row g-3">
        <!-- Kiri: Isu Tindak Lanjut Terbaru -->
        <div class="col-lg-7">
            <div class="dash-card p-0 overflow-hidden">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <h2 class="dash-card-title">
                        <i class="bi bi-clipboard-data text-primary"></i> Isu Tindak Lanjut Terbaru
                    </h2>
                    <a href="{{ route('issue-follow-up.index') }}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 11px;">
                        Lihat Semua
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table dash-table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="ps-3">No</th>
                                <th>Tanggal</th>
                                <th>Judul Issue</th>
                                <th>Kategori</th>
                                <th>Penanggung Jawab</th>
                                <th>Tenggat</th>
                                <th class="text-center pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latest_issues as $index => $issue)
                                <tr>
                                    <td class="ps-3 text-muted">{{ $index + 1 }}</td>
                                    <td>{{ $issue->reported_date ? $issue->reported_date->format('d/m/Y') : '-' }}</td>
                                    <td class="fw-semibold text-dark">{{ $issue->title }}</td>
                                    <td>
                                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 10px;">
                                            {{ $issue->hsse_division_label ?? $issue->category ?? '-' }}
                                        </span>
                                    </td>
                                    <td>{{ $issue->pic ?? $issue->reported_by ?? '-' }}</td>
                                    <td>
                                        <span class="{{ ($issue->is_overdue ?? false) ? 'text-danger fw-bold' : 'text-muted' }}">
                                            {{ $issue->due_date ? $issue->due_date->format('d/m/Y') : '-' }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-3">
                                        @php
                                            $st = strtolower($issue->status ?? 'open');
                                            $badgeClass = match($st) {
                                                'in progress' => 'status-badge-progress',
                                                'closed', 'close' => 'status-badge-closed',
                                                default => ($issue->is_overdue ?? false) ? 'status-badge-overdue' : 'status-badge-open'
                                            };
                                            $stLabel = ($issue->is_overdue ?? false) && $st !== 'closed' ? 'Overdue' : $issue->status;
                                        @endphp
                                        <span class="status-badge-pill {{ $badgeClass }}">{{ $stLabel }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox d-block fs-3 mb-1"></i>
                                        Belum ada rekaman isu tindak lanjut pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kanan: Kegiatan Terbaru (Events) -->
        <div class="col-lg-5">
            <div class="dash-card p-0 overflow-hidden">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <h2 class="dash-card-title">
                        <i class="bi bi-calendar-event text-success"></i> Kegiatan Terbaru
                    </h2>
                    <a href="{{ route('events.index') }}" class="btn btn-outline-success btn-sm py-0 px-2" style="font-size: 11px;">
                        Lihat Semua
                    </a>
                </div>
                <div class="p-3">
                    @forelse($latest_events as $event)
                        @php
                            $cat = strtolower($event->category ?? 'pelatihan');
                            $badgeType = match(true) {
                                str_contains($cat, 'inspeksi') => 'inspeksi',
                                str_contains($cat, 'simulasi') => 'simulasi',
                                str_contains($cat, 'sosialisasi') => 'sosialisasi',
                                default => 'pelatihan',
                            };
                        @endphp
                        <div class="event-list-item">
                            <div class="event-thumb-box">
                                @if(!empty($event->documentation) && file_exists(public_path('storage/' . $event->documentation)))
                                    <img src="{{ asset('storage/' . $event->documentation) }}" alt="Event">
                                @else
                                    <i class="bi bi-image"></i>
                                @endif
                            </div>
                            <div class="event-info">
                                <div class="event-title" title="{{ $event->title }}">{{ $event->title }}</div>
                                <div class="event-date">
                                    <i class="bi bi-clock me-1"></i>{{ $event->event_date ? $event->event_date->translatedFormat('d M Y') : '-' }}
                                </div>
                            </div>
                            <div>
                                <span class="event-badge-tag {{ $badgeType }}">
                                    {{ $event->category ?? 'Kegiatan' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x d-block fs-3 mb-1"></i>
                            Belum ada jadwal kegiatan terbaru pada periode ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inisialisasi Tooltip Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Inisialisasi 4 Donut Charts "Issue & Follow Up" (Health & Safety, Security, Environment, Social)
        const issueChartsData = @json($issue_follow_up_charts ?? []);
        issueChartsData.forEach(function (cat) {
            const canvas = document.getElementById('chart-' + cat.slug);
            if (!canvas) return;

            const isEmpty = (cat.total === 0 || cat.total === '0');
            const chartData = isEmpty ? [1] : [cat.open_count, cat.closed_count];
            const bgColors = isEmpty ? ['#e2e8f0'] : ['#ef4444', '#10b981'];
            const labels = isEmpty ? ['Tidak Ada Data'] : ['Open', 'Closed'];

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: chartData,
                        backgroundColor: bgColors,
                        borderWidth: isEmpty ? 0 : 2,
                        borderColor: '#ffffff',
                        hoverOffset: isEmpty ? 0 : 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: !isEmpty,
                            callbacks: {
                                label: function (context) {
                                    const value = context.parsed;
                                    const pct = cat.total > 0 ? Math.round((value / cat.total) * 100) : 0;
                                    return ` ${context.label}: ${value} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
    });
</script>
@endpush
