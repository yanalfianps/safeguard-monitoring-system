@extends('layouts.app', [
    'title' => 'Dashboard',
    'activeModule' => 'dashboard'
])

@push('styles')
<style>
    :root {
        --dash-navy: #1E3A5F;
        --dash-green: #15803D;
        --dash-teal: #0F766E;
        --dash-blue: #2563EB;
        --dash-blue-deep: #1D4ED8;
        --dash-purple: #1E3A5F;
        --dash-orange: #D97706;
        --dash-red: #DC2626;
        --dash-border: #CBD5E1;
        --dash-card-bg: #FFFFFF;
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
        background: #2563EB;
        color: #FFFFFF;
        display: grid;
        place-items: center;
        font-size: 22px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.28);
    }

    .dashboard-title {
        font-size: 22px;
        font-weight: 800;
        color: #1E3A5F;
        margin: 0;
        line-height: 1.2;
    }

    .dashboard-subtitle {
        font-size: 12px;
        color: #475569;
        margin-top: 2px;
    }

    /* Project Timeline Full Width Card */
    .project-timeline-card {
        background: #FFFFFF;
        border: 1px solid var(--dash-border);
        border-top: 4px solid #1E3A5F;
        border-radius: 14px;
        padding: 20px 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(30, 58, 95, 0.06);
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
        background: #1E3A5F;
        color: #FFFFFF;
        display: grid;
        place-items: center;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(30, 58, 95, 0.24);
    }

    .timeline-title {
        font-size: 16px;
        font-weight: 800;
        color: #1E3A5F;
        margin: 0;
        line-height: 1.2;
    }

    .timeline-subtitle {
        font-size: 12px;
        color: #475569;
        margin-top: 3px;
    }

    .timeline-badge-percent {
        display: flex;
        align-items: baseline;
        gap: 8px;
        background: #ECFDF5;
        border: 1.5px solid #86EFAC;
        padding: 8px 16px;
        border-radius: 12px;
    }

    .timeline-percent-number {
        font-size: 24px;
        font-weight: 900;
        color: #15803D;
        line-height: 1;
        font-family: inherit;
    }

    .timeline-percent-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #15803D;
        letter-spacing: 0.5px;
    }

    .timeline-progress-track {
        height: 16px;
        background: #E2E8F0;
        border-radius: 9999px;
        overflow: hidden;
        position: relative;
        box-shadow: inset 0 1px 3px rgba(15, 23, 42, 0.1);
        border: 1px solid #CBD5E1;
    }

    .timeline-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #0F766E 0%, #2563EB 100%);
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
        color: #475569;
        flex-wrap: wrap;
        gap: 8px;
    }

    .timeline-marker-item strong {
        color: #1E3A5F;
    }

    .timeline-marker-item .text-success { color: #15803D !important; }
    .timeline-marker-item .text-primary { color: #2563EB !important; }
    .timeline-marker-item .text-danger { color: #DC2626 !important; }

    /* 3 Cards Durasi Waktu */
    .timeline-metric-card {
        background: #FFFFFF;
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 2px 6px rgba(30, 58, 95, 0.05);
        height: 100%;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s, border-color 0.2s;
    }

    .timeline-metric-card:hover {
        transform: translateY(-2px);
        border-color: #94A3B8;
        box-shadow: 0 10px 22px -6px rgba(30, 58, 95, 0.14);
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

    .metric-icon-box.days {
        background: #2563EB;
        color: #FFFFFF;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.24);
    }

    .metric-icon-box.weeks {
        background: #0F766E;
        color: #FFFFFF;
        box-shadow: 0 4px 10px rgba(15, 118, 110, 0.24);
    }

    .metric-icon-box.years {
        background: #D97706;
        color: #FFFFFF;
        box-shadow: 0 4px 10px rgba(217, 119, 6, 0.24);
    }

    .metric-content {
        flex: 1;
        min-width: 0;
    }

    .metric-label {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #475569;
        margin-bottom: 2px;
    }

    .metric-value {
        font-size: 24px;
        font-weight: 800;
        color: #1E3A5F;
        line-height: 1.1;
        margin-bottom: 3px;
    }

    .metric-unit,
    .metric-value .text-sm {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }

    .metric-subtext {
        font-size: 11px;
        color: #64748B;
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
        box-shadow: 0 2px 8px rgba(30, 58, 95, 0.05);
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
        color: #1E3A5F;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    /* 2 Card Utama: Total Man Hours & Total Manpower */
    .dashboard-hero-card {
        background: #FFFFFF;
        border: 1.5px solid var(--dash-border);
        border-radius: 14px;
        padding: 22px 24px;
        box-shadow: 0 2px 10px rgba(30, 58, 95, 0.06);
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
        color: inherit;
        text-decoration: none;
    }

    .dashboard-hero-card.man-hours {
        background: #FFFFFF;
        border-color: #BFDBFE;
    }

    .dashboard-hero-card.manpower {
        background: #FFFFFF;
        border-color: #A7F3D0;
    }

    .dashboard-hero-card.man-hours:hover {
        border-color: #2563EB;
        box-shadow: 0 12px 24px -6px rgba(37, 99, 235, 0.16);
    }

    .dashboard-hero-card.manpower:hover {
        border-color: #15803D;
        box-shadow: 0 12px 24px -6px rgba(21, 128, 61, 0.16);
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
        background: #2563EB;
    }

    .dashboard-hero-card.manpower::before {
        background: #15803D;
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
        background: #DBEAFE;
        color: #1D4ED8;
        border: 1px solid #93C5FD;
    }

    .hero-card-badge.green {
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #86EFAC;
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
        background: #2563EB;
        color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.26);
    }

    .hero-card-icon.green {
        background: #15803D;
        color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(21, 128, 61, 0.26);
    }

    .hero-card-title {
        font-size: 13px;
        font-weight: 700;
        color: #1E3A5F;
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
        color: #0F172A;
        line-height: 1.1;
        letter-spacing: -0.5px;
    }

    .hero-card-unit {
        font-size: 15px;
        font-weight: 600;
        color: #475569;
    }

    .hero-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 14px;
        border-top: 1px dashed #CBD5E1;
        margin-top: 12px;
        font-size: 12px;
        color: #475569;
    }

    .hero-card-footer strong {
        color: #1E3A5F;
    }

    .hero-card-footer .text-primary { color: #2563EB !important; }
    .hero-card-footer .text-success { color: #15803D !important; }

    .hero-card-link {
        font-weight: 600;
        color: #2563EB;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color 0.15s, transform 0.15s;
    }

    .hero-card-link:hover {
        color: #1D4ED8;
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
        background: #FFFFFF;
        border: 1px solid #FCA5A5;
    }

    .subcard-insiden.orange {
        background: #FFFFFF;
        border: 1px solid #FCD34D;
    }

    .subcard-insiden.purple {
        background: #FFFFFF;
        border: 1px solid #CBD5E1;
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

    .subcard-insiden.red .icon-circle-insiden,
    .icon-circle-insiden.red {
        background: #DC2626;
        color: #FFFFFF;
    }

    .subcard-insiden.orange .icon-circle-insiden,
    .icon-circle-insiden.orange {
        background: #D97706;
        color: #FFFFFF;
    }

    .subcard-insiden.purple .icon-circle-insiden,
    .icon-circle-insiden.purple {
        background: #1E3A5F;
        color: #FFFFFF;
    }

    .insiden-info-label {
        font-size: 11px;
        font-weight: 700;
        color: #1E3A5F;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .insiden-info-value {
        font-size: 28px;
        font-weight: 800;
        color: #0F172A;
        line-height: 1.1;
        margin: 2px 0;
    }

    .insiden-info-trend {
        font-size: 10.5px;
        font-weight: 600;
    }

    .insiden-info-trend.up { color: #DC2626; }
    .insiden-info-trend.down { color: #15803D; }
    .insiden-info-trend.neutral { color: #475569; }

    /* Subcard Indikator HSSE Wajib */
    .hsse-indicator-subcard {
        border-radius: 10px;
        border: 1px solid var(--dash-border);
        background: #FFFFFF;
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
        box-shadow: 0 4px 10px rgba(30, 58, 95, 0.08);
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

    .hsse-ind-icon-box.green { background: #15803D; color: #FFFFFF; }
    .hsse-ind-icon-box.orange { background: #D97706; color: #FFFFFF; }
    .hsse-ind-icon-box.purple { background: #1E3A5F; color: #FFFFFF; }

    .hsse-ind-name {
        font-size: 13px;
        font-weight: 700;
        color: #1E3A5F;
    }

    .hsse-ind-info-btn {
        color: #64748B;
        font-size: 13px;
        cursor: pointer;
    }

    .hsse-ind-value {
        font-size: 28px;
        font-weight: 800;
        color: #0F172A;
        margin: 12px 0 0;
        line-height: 1.1;
    }

    .hsse-ind-formula {
        font-size: 9.5px;
        color: #475569;
        font-family: monospace;
        background: #F1F5F9;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #CBD5E1;
        margin-bottom: 6px;
        display: inline-block;
    }

    .hsse-ind-details {
        font-size: 10px;
        color: #475569;
        border-top: 1px dashed #CBD5E1;
        padding-top: 6px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    /* Merged Card Indikator HSSE (Grid 3 Kolom) */
    .hsse-section-card {
        background: #FFFFFF;
        border: 1.5px solid var(--dash-border);
        border-top: 4px solid #DC2626;
        box-shadow: 0 2px 10px rgba(30, 58, 95, 0.06);
    }

    .hsse-section-card:hover {
        border-color: #FCA5A5;
        border-top-color: #DC2626;
    }

    .hsse-indicator-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        width: 100%;
    }

    @media (min-width: 768px) {
        .hsse-indicator-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .hsse-column-card {
        background: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-radius: 10px;
        padding: 12px;
        height: 100%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        text-align: center;
        transition: transform 0.15s, box-shadow 0.15s, background-color 0.15s, border-color 0.15s;
    }

    .hsse-column-card:hover {
        background: #FFFFFF;
        border-color: #94A3B8;
        box-shadow: 0 6px 14px rgba(30, 58, 95, 0.08);
        transform: translateY(-2px);
    }

    .hsse-merged-row {
        background: #F8FAFC;
        border: 1px solid var(--dash-border);
        border-radius: 10px;
        padding: 13px 16px;
        transition: transform 0.15s, box-shadow 0.15s, background-color 0.15s;
    }

    .hsse-merged-row:hover {
        background: #FFFFFF;
        box-shadow: 0 4px 12px rgba(30, 58, 95, 0.06);
        transform: translateY(-1px);
    }

    .hsse-row-header-label {
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: #1E3A5F;
        line-height: 1.25;
    }

    .hsse-row-value-primary {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }

    .hsse-row-divider {
        height: 1px;
        background: #CBD5E1;
        margin: 10px 0;
        width: 100%;
    }

    .hsse-row-sublabel {
        font-size: 11.5px;
        font-weight: 700;
        color: #475569;
    }

    .hsse-row-value-secondary {
        font-size: 18px;
        font-weight: 800;
        line-height: 1;
    }

    /* Monitoring Anggaran Card */
    .budget-monitoring-card {
        background: #FFFFFF;
        border: 1.5px solid var(--dash-border);
        border-top: 4px solid #1E3A5F;
        box-shadow: 0 2px 10px rgba(30, 58, 95, 0.06);
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s, border-color 0.2s;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .budget-monitoring-card:hover {
        transform: translateY(-2px);
        border-color: #93C5FD;
        border-top-color: #1D4ED8;
        box-shadow: 0 10px 24px -6px rgba(30, 58, 95, 0.14);
    }

    .budget-stat-box {
        background: #F8FAFC;
        border-radius: 10px;
        padding: 16px;
        height: 100%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-left: 4px solid #0F766E;
        border-top: 1px solid var(--dash-border);
        border-right: 1px solid var(--dash-border);
        border-bottom: 1px solid var(--dash-border);
        transition: transform 0.15s, background-color 0.15s, box-shadow 0.15s;
    }

    .budget-stat-box:hover {
        background: #FFFFFF;
        box-shadow: 0 6px 14px rgba(30, 58, 95, 0.08);
    }

    .budget-accent-budget {
        border-left-color: #0F766E;
    }

    .budget-accent-assigned {
        border-left-color: #2563EB;
    }

    .budget-accent-available {
        border-left-color: #15803D;
    }

    .budget-stat-label {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        color: #475569;
        margin-bottom: 8px;
    }

    .budget-stat-value {
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 0;
    }

    .budget-stat-subtext {
        font-size: 11px;
        color: #475569;
        line-height: 1.35;
    }

    /* Gas Monitoring Section */
    .gas-section-card {
        background: #FFFFFF;
        border: 1.5px solid var(--dash-border);
        border-top: 4px solid #0F766E;
        box-shadow: 0 2px 10px rgba(30, 58, 95, 0.06);
        padding: 20px;
        border-radius: 14px;
    }

    .gas-section-card:hover {
        border-color: #5EEAD4;
        border-top-color: #0F766E;
    }

    .gas-update-pill-card {
        background: #F0FDFA;
        border: 1.5px solid #5EEAD4;
        border-radius: 12px;
        padding: 12px 18px;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        box-shadow: 0 2px 6px rgba(15, 118, 110, 0.1);
    }

    .gas-update-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #0F766E;
        color: #FFFFFF;
        display: grid;
        place-items: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .gas-update-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #0F766E;
        letter-spacing: 0.5px;
    }

    .gas-update-value {
        font-size: 15px;
        font-weight: 800;
        color: #1E3A5F;
        line-height: 1.2;
    }

    .gas-card-v2 {
        background: #F8FAFC;
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        padding: 16px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s, border-color 0.2s;
        box-shadow: 0 1px 4px rgba(30, 58, 95, 0.04);
    }

    .gas-card-v2:hover {
        background: #FFFFFF;
        border-color: #0F766E;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -4px rgba(15, 118, 110, 0.14);
    }

    .gas-card-v2-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .gas-v2-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .gas-v2-title {
        font-size: 13px;
        font-weight: 700;
        color: #1E3A5F;
    }

    .gas-v2-value-wrap {
        margin: 6px 0 10px;
    }

    .gas-v2-value {
        font-size: 22px;
        font-weight: 800;
        color: #0F172A;
        line-height: 1.1;
    }

    .gas-v2-threshold {
        font-size: 11px;
        color: #475569;
        background: #FFFFFF;
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid #CBD5E1;
        display: inline-block;
        font-weight: 600;
    }

    .badge-sesuai {
        background-color: #DCFCE7;
        color: #15803D;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 9999px;
        border: 1px solid #86EFAC;
    }

    .badge-tidaksesuai {
        background-color: #FEE2E2;
        color: #DC2626;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 9999px;
        border: 1px solid #FCA5A5;
    }

    /* Issue & Follow Up Section */
    .issue-section-card {
        background: #FFFFFF;
        border: 1.5px solid var(--dash-border);
        border-top: 4px solid #D97706;
        box-shadow: 0 2px 10px rgba(30, 58, 95, 0.06);
        padding: 20px;
        border-radius: 14px;
    }

    .issue-section-card:hover {
        border-color: #FCD34D;
        border-top-color: #D97706;
    }

    /* Issue & Follow Up Recap Cards */
    .issue-recap-card {
        background: #F8FAFC;
        border: 1px solid var(--dash-border);
        border-left-width: 4px;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 1px 4px rgba(30, 58, 95, 0.04);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
    }

    .issue-recap-card:hover {
        background: #FFFFFF;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px -4px rgba(30, 58, 95, 0.12);
    }

    .issue-recap-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #475569;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .issue-recap-value {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }

    .issue-chart-card {
        background: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-radius: 12px;
        padding: 18px;
        box-shadow: 0 1px 4px rgba(30, 58, 95, 0.04);
        transition: background-color 0.2s, box-shadow 0.2s, border-color 0.2s;
    }

    .issue-chart-card:hover {
        background: #FFFFFF;
        border-color: #94A3B8;
        box-shadow: 0 6px 14px rgba(30, 58, 95, 0.08);
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

    /* Latest Tables & Events Cards */
    .dash-events-card {
        background: #FFFFFF;
        border: 1.5px solid var(--dash-border);
        border-top: 4px solid #1D4ED8;
        box-shadow: 0 2px 10px rgba(30, 58, 95, 0.06);
    }

    .dash-dailywork-card {
        background: #FFFFFF;
        border: 1.5px solid var(--dash-border);
        border-top: 4px solid #15803D;
        box-shadow: 0 2px 10px rgba(30, 58, 95, 0.06);
    }

    .dash-section-header {
        background: #F8FAFC;
        border-bottom: 1px solid #CBD5E1 !important;
    }

    .btn-dash-events {
        font-size: 11px;
        font-weight: 600;
        border: 1px solid #2563EB;
        color: #1D4ED8;
        background: #EFF6FF;
        transition: all 0.15s ease;
    }

    .btn-dash-events:hover {
        background: #2563EB;
        border-color: #2563EB;
        color: #FFFFFF;
    }

    .btn-dash-dailywork {
        font-size: 11px;
        font-weight: 600;
        border: 1px solid #15803D;
        color: #15803D;
        background: #DCFCE7;
        transition: all 0.15s ease;
    }

    .btn-dash-dailywork:hover {
        background: #15803D;
        border-color: #15803D;
        color: #FFFFFF;
    }

    .dash-company-badge {
        background: #F1F5F9;
        color: #1E3A5F;
        border: 1px solid #CBD5E1;
        font-weight: 600;
    }

    /* Latest Tables & Events */
    .dash-table {
        font-size: 11.5px;
    }

    .dash-table th {
        font-size: 10.5px;
        text-transform: uppercase;
        color: #1E3A5F;
        font-weight: 700;
        background: #F1F5F9;
        border-bottom: 1.5px solid #CBD5E1;
        padding: 9px 12px;
    }

    .dash-table td {
        padding: 10px 12px;
        vertical-align: middle;
        border-bottom: 1px solid #E2E8F0;
        color: #334155;
    }

    .dash-table tbody tr:hover td {
        background-color: #F3F6FB;
    }

    .status-badge-pill {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        border: 1px solid transparent;
    }

    .status-badge-open { background: #DBEAFE; color: #1D4ED8; border-color: #93C5FD; }
    .status-badge-progress { background: #FEF3C7; color: #B45309; border-color: #FCD34D; }
    .status-badge-overdue { background: #FEE2E2; color: #DC2626; border-color: #FCA5A5; }
    .status-badge-closed { background: #DCFCE7; color: #15803D; border-color: #86EFAC; }

    /* Timeline Event Item */
    .event-list-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px dashed #CBD5E1;
    }

    .event-list-item:last-child {
        border-bottom: none;
    }

    .event-thumb-box {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        overflow: hidden;
        background: #E2E8F0;
        border: 1px solid #CBD5E1;
        display: grid;
        place-items: center;
        color: #1E3A5F;
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
        color: #0F172A;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event-date {
        font-size: 10.5px;
        color: #475569;
    }

    .event-badge-tag {
        font-size: 10px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .event-badge-tag.pelatihan { background: #FEE2E2; color: #DC2626; border-color: #FCA5A5; }
    .event-badge-tag.inspeksi { background: #DBEAFE; color: #1D4ED8; border-color: #93C5FD; }
    .event-badge-tag.simulasi { background: #E0E7FF; color: #1E3A5F; border-color: #A5B4FC; }
    .event-badge-tag.sosialisasi { background: #DCFCE7; color: #15803D; border-color: #86EFAC; }

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

    /* Soft Pastel Background & Clickable Wrapper Utilities */
    .bg-blue-50 { background-color: #eff6ff !important; }
    .bg-sky-50 { background-color: #f0f9ff !important; }
    .bg-emerald-50 { background-color: #ecfdf5 !important; }
    .bg-teal-50 { background-color: #f0fdfa !important; }
    .bg-red-50 { background-color: #fef2f2 !important; }
    .bg-rose-50 { background-color: #fff1f2 !important; }
    .bg-slate-50 { background-color: #f8fafc !important; }
    .bg-gray-50 { background-color: #f9fafb !important; }
    .bg-cyan-50 { background-color: #ecfeff !important; }
    .bg-indigo-50 { background-color: #eef2ff !important; }
    .bg-orange-50 { background-color: #fff7ed !important; }
    .bg-amber-50 { background-color: #fffbeb !important; }
    .bg-purple-50 { background-color: #faf5ff !important; }
    .bg-fuchsia-50 { background-color: #fdf4ff !important; }
    .bg-yellow-50 { background-color: #fefce8 !important; }
    .bg-lime-50 { background-color: #f7fee7 !important; }

    .border-blue-100 { border-color: #dbeafe !important; }
    .border-emerald-100 { border-color: #d1fae5 !important; }
    .border-red-100 { border-color: #fee2e2 !important; }
    .border-rose-100 { border-color: #ffe4e6 !important; }
    .border-cyan-100 { border-color: #cffafe !important; }
    .border-amber-100 { border-color: #fef3c7 !important; }
    .border-orange-100 { border-color: #ffedd5 !important; }
    .border-purple-100 { border-color: #f3e8ff !important; }
    .border-yellow-100 { border-color: #fef9c3 !important; }
    .border-lime-100 { border-color: #ecfccb !important; }
    .border-slate-200 { border-color: #e2e8f0 !important; }

    .rounded-xl { border-radius: 14px !important; }
    .rounded-2xl { border-radius: 18px !important; }

    .clickable-section-card {
        display: block;
        width: 100%;
        text-decoration: none !important;
        color: inherit;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s, border-color 0.2s;
        cursor: pointer;
    }

    .clickable-section-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(15, 39, 61, 0.08), 0 8px 10px -6px rgba(15, 39, 61, 0.04);
        color: inherit;
        text-decoration: none;
    }

    /* Grid & Responsive Utilities for Tailwind-like Layouts */
    .grid {
        display: grid !important;
    }
    .grid-cols-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
    }
    .items-stretch {
        align-items: stretch !important;
    }
    .min-w-0 {
        min-width: 0 !important;
    }
    .gap-2 {
        gap: 0.5rem !important;
    }
    .gap-4 {
        gap: 1rem !important;
    }
    .whitespace-nowrap {
        white-space: nowrap !important;
    }
    .truncate {
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }
    .tracking-tight {
        letter-spacing: -0.025em !important;
    }
    .leading-tight {
        line-height: 1.25 !important;
    }
    .font-bold {
        font-weight: 700 !important;
    }
    .text-\[10px\] {
        font-size: 10px !important;
    }
    .text-base {
        font-size: 1rem !important;
    }
    .text-lg {
        font-size: 1.125rem !important;
    }

    @media (min-width: 576px) {
        .sm\:p-3 {
            padding: 0.75rem !important;
        }
        .sm\:text-xs {
            font-size: 0.75rem !important;
        }
    }

    @media (min-width: 768px) {
        .md\:grid-cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    @media (min-width: 1024px) {
        .lg\:text-lg {
            font-size: 1.125rem !important;
        }
        .lg\:text-xl {
            font-size: 1.25rem !important;
        }
    }

    /* Breakpoint xl (>1366px) agar layar laptop kecil 1366px ke bawah tersusun atas-bawah */
    @media (min-width: 1400px) {
        .xl\:grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
        .xl\:gap-4 {
            gap: 1rem !important;
        }
        .xl\:p-4 {
            padding: 1rem !important;
        }
        .xl\:text-sm {
            font-size: clamp(0.75rem, 0.78vw, 0.875rem) !important;
        }
        .xl\:text-xl {
            font-size: clamp(1.05rem, 1.08vw, 1.25rem) !important;
        }
        .xl\:text-2xl {
            font-size: clamp(1rem, 1.08vw, 1.5rem) !important;
        }
    }

    @media (min-width: 1850px) {
        .xl\:text-2xl {
            font-size: 1.5rem !important;
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
                        Periode Kontrak: {{ $start_date->translatedFormat('d F Y') }} &mdash; {{ $end_date->translatedFormat('d F Y') }}
                    </div>
                </div>
            </div>
            <div class="timeline-badge-percent">
                <span class="timeline-percent-number">{{ number_format($progress_percentage, 2, ',', '.') }}%</span>
                <span class="timeline-percent-label">Waktu Berjalan</span>
            </div>
        </div>

        <!-- Progress Bar Linier -->
        <div class="timeline-progress-track" title="Waktu Proyek Berjalan: {{ number_format($progress_percentage, 2, ',', '.') }}%">
            <div class="timeline-progress-bar" style="width: {{ $progress_percentage }}%;"></div>
        </div>

        <!-- Milestone Markers -->
        <div class="timeline-markers-row">
            <div class="timeline-marker-item">
                <i class="bi bi-play-circle-fill text-success me-1"></i> Mulai: <strong>{{ $start_date->translatedFormat('d F Y') }}</strong>
            </div>
            <div class="timeline-marker-item text-center">
                <i class="bi bi-geo-alt-fill text-primary me-1"></i> Hari Ini: <strong>{{ $today->translatedFormat('d F Y') }}</strong> ({{ number_format($progress_percentage, 2, ',', '.') }}%)
            </div>
            <div class="timeline-marker-item text-end">
                <i class="bi bi-flag-fill text-danger me-1"></i> Target Selesai: <strong>{{ $end_date->translatedFormat('d F Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- 2. 3 KARTU DURASI WAKTU PROYEK (HARI, MINGGU, BULAN) -->
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
                        {{ number_format($passed_days, 0, ',', '.') }}/{{ number_format($total_days, 0, ',', '.') }} <span class="text-sm">Hari</span>
                    </div>
                    <div class="metric-subtext">Sisa {{ number_format($rem_days, 0, ',', '.') }} Hari</div>
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
                        {{ number_format($passed_weeks, 0, ',', '.') }}/{{ number_format($total_weeks, 0, ',', '.') }} <span class="text-sm">Minggu</span>
                    </div>
                    <div class="metric-subtext">Sisa {{ number_format($rem_weeks, 0, ',', '.') }} Minggu</div>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Bulan Berjalan -->
        <div class="col-md-4">
            <div class="timeline-metric-card">
                <div class="metric-icon-box years">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-label">Bulan Proyek Berjalan</div>
                    <div class="metric-value">
                        {{ number_format($passed_months, 0, ',', '.') }}/{{ number_format($total_months, 0, ',', '.') }} <span class="text-sm">Bulan</span>
                    </div>
                    <div class="metric-subtext">Sisa {{ number_format($rem_months, 0, ',', '.') }} Bulan</div>
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

    <!-- BARIS 2: INDIKATOR HSSE (MERGED INSIDEN) + MONITORING ANGGARAN -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4 items-stretch">
        <!-- Kiri: Indikator HSSE (Wrapper Hyperlink ke Incident) -->
        <div class="d-flex flex-column min-w-0">
            <a href="{{ route('incident.index') }}" class="dash-card hsse-section-card clickable-section-card block w-full transition duration-200 hover:shadow-md cursor-pointer text-decoration-none text-dark rounded-xl p-2 sm:p-3 xl:p-4 h-full d-flex flex-column justify-content-between min-w-0">
                <div>
                    <div class="dash-card-header mb-3">
                        <h2 class="dash-card-title">
                            <i class="bi bi-speedometer" style="color: #DC2626;"></i> Insiden
                        </h2>
                    </div>

                    <!-- Grid 3 Kolom Menyamping: Total Insiden & Indikator Rasio -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 xl:gap-4 w-full hsse-indicator-grid">
                        <!-- Kolom 1: Total Insiden & TRIR -->
                        <div class="hsse-column-card rounded-lg p-2 sm:p-3 xl:p-4 d-flex flex-column justify-between items-center text-center shadow-sm min-w-0">
                            <div class="w-100">
                                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                                    <div class="icon-circle-insiden red" style="width: 28px; height: 28px; font-size: 13px; border-radius: 6px;">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                    <span class="hsse-row-header-label text-center text-[10px] sm:text-xs xl:text-sm leading-tight">TOTAL INSIDEN</span>
                                </div>
                                <div class="hsse-row-value-primary my-1" style="color: #DC2626;">
                                    {{ number_format($total_insiden_panas_bumi, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="w-100">
                                <div class="hsse-row-divider"></div>
                                <div class="d-flex align-items-center justify-content-between px-1 gap-2">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="hsse-row-sublabel">TRIR</span>
                                        <i class="bi bi-info-circle hsse-ind-info-btn" data-bs-toggle="tooltip" 
                                           title="Total Recordable Incident Rate. Rumus: (Total Insiden Panas Bumi / Total Man Hours) x 1.000.000"></i>
                                    </div>
                                    <div class="hsse-row-value-secondary text-base lg:text-lg xl:text-xl font-bold whitespace-nowrap" style="color: #15803D;">
                                        {{ number_format($trir, 2, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom 2: Total Lost Time Injury (LTI) & LTI-FR -->
                        <div class="hsse-column-card rounded-lg p-2 sm:p-3 xl:p-4 d-flex flex-column justify-between items-center text-center shadow-sm min-w-0">
                            <div class="w-100">
                                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                                    <div class="icon-circle-insiden orange" style="width: 28px; height: 28px; font-size: 13px; border-radius: 6px;">
                                        <i class="bi bi-cone-striped"></i>
                                    </div>
                                    <span class="hsse-row-header-label text-center text-[10px] sm:text-xs xl:text-sm leading-tight">TOTAL LOST TIME INJURY (LTI)</span>
                                </div>
                                <div class="hsse-row-value-primary my-1" style="color: #D97706;">
                                    {{ number_format($total_lti, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="w-100">
                                <div class="hsse-row-divider"></div>
                                <div class="d-flex align-items-center justify-content-between px-1 gap-2">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="hsse-row-sublabel">LTI-FR</span>
                                        <i class="bi bi-info-circle hsse-ind-info-btn" data-bs-toggle="tooltip" 
                                           title="Lost Time Injury Frequency Rate. Rumus: (Total LTI / Total Man Hours) x 1.000.000"></i>
                                    </div>
                                    <div class="hsse-row-value-secondary text-base lg:text-lg xl:text-xl font-bold whitespace-nowrap" style="color: #D97706;">
                                        {{ number_format($lti_fr, 2, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom 3: Total Hari Kerja Hilang & LTI-SR -->
                        <div class="hsse-column-card rounded-lg p-2 sm:p-3 xl:p-4 d-flex flex-column justify-between items-center text-center shadow-sm min-w-0">
                            <div class="w-100">
                                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                                    <div class="icon-circle-insiden purple" style="width: 28px; height: 28px; font-size: 13px; border-radius: 6px;">
                                        <i class="bi bi-calendar2-x-fill"></i>
                                    </div>
                                    <span class="hsse-row-header-label text-center text-[10px] sm:text-xs xl:text-sm leading-tight">TOTAL HARI KERJA HILANG</span>
                                </div>
                                <div class="hsse-row-value-primary my-1" style="color: #1E3A5F;">
                                    {{ number_format($total_lwd, 0, ',', '.') }} <span style="font-size: 12px; font-weight: 600; color: #475569;">Hari</span>
                                </div>
                            </div>
                            <div class="w-100">
                                <div class="hsse-row-divider"></div>
                                <div class="d-flex align-items-center justify-content-between px-1 gap-2">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="hsse-row-sublabel">LTI-SR</span>
                                        <i class="bi bi-info-circle hsse-ind-info-btn" data-bs-toggle="tooltip" 
                                           title="Lost Time Injury Severity Rate. Rumus: (Total Hari Hilang / Total Man Hours) x 1.000.000"></i>
                                    </div>
                                    <div class="hsse-row-value-secondary text-base lg:text-lg xl:text-xl font-bold whitespace-nowrap" style="color: #1E3A5F;">
                                        {{ number_format($lti_sr, 2, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($total_man_hours == 0)
                    <div class="mt-3 pt-2">
                        <div class="alert alert-light border py-1 px-2 mb-0 d-flex align-items-center gap-2" style="font-size: 11px; color: #475569; border-color: #CBD5E1 !important;">
                            <i class="bi bi-info-circle" style="color: #2563EB;"></i>
                            <span>Data Man Hours belum tersedia sehingga indikator bernilai <strong style="color: #1E3A5F;">0,00</strong>.</span>
                        </div>
                    </div>
                @endif
            </a>
        </div>

        <!-- Kanan: Monitoring Anggaran (Wrapper Hyperlink ke Anggaran) -->
        <div class="d-flex flex-column min-w-0">
            <a href="{{ route('budget-monitoring.index') }}" class="dash-card budget-monitoring-card clickable-section-card block w-full transition duration-200 hover:shadow-md cursor-pointer text-decoration-none text-dark rounded-xl p-2 sm:p-3 xl:p-4 h-full d-flex flex-column justify-content-between min-w-0">
                <div>
                    <div class="dash-card-header mb-3">
                        <h2 class="dash-card-title">
                            <i class="bi bi-wallet2" style="color: #2563EB;"></i> Monitoring Anggaran
                        </h2>
                    </div>

                    <!-- 3 Kotak Indikator Anggaran (Budget, Assigned, Available) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 xl:gap-4 w-full">
                        <!-- Kotak 1: Budget -->
                        <div class="budget-stat-box budget-accent-budget shadow-sm p-2 sm:p-3 xl:p-4 min-w-0">
                            <div class="w-100 min-w-0">
                                <div class="budget-stat-label" style="color: #0F766E;">BUDGET</div>
                                <div class="budget-stat-value text-lg lg:text-xl xl:text-2xl tracking-tight whitespace-nowrap truncate" style="color: #1E3A5F;">
                                    Rp {{ number_format($total_budget, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <!-- Kotak 2: Assigned -->
                        <div class="budget-stat-box budget-accent-assigned shadow-sm p-2 sm:p-3 xl:p-4 min-w-0">
                            <div class="w-100 min-w-0">
                                <div class="budget-stat-label" style="color: #1D4ED8;">ASSIGNED</div>
                                <div class="budget-stat-value text-lg lg:text-xl xl:text-2xl tracking-tight whitespace-nowrap truncate" style="color: #1D4ED8;">
                                    Rp {{ number_format($total_assigned, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <!-- Kotak 3: Available -->
                        <div class="budget-stat-box budget-accent-available shadow-sm p-2 sm:p-3 xl:p-4 min-w-0">
                            <div class="w-100 min-w-0">
                                <div class="budget-stat-label" style="color: #15803D;">AVAILABLE</div>
                                <div class="budget-stat-value text-lg lg:text-xl xl:text-2xl tracking-tight whitespace-nowrap truncate" style="color: #15803D;">
                                    Rp {{ number_format($total_available, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- BARIS 3: DATA GAS MONITORING (WRAPPER HYPERLINK) -->
    <div class="mb-4">
        <a href="{{ route('gas-monitoring.index') }}" class="clickable-section-card gas-section-card block w-full transition duration-200 hover:shadow-md cursor-pointer text-decoration-none text-dark rounded-xl">
            <!-- Header Section: Judul di Kiri & Update Terakhir di Kanan (Tombol Lihat Detail Dihapus) -->
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h2 class="dash-card-title fs-5 mb-0">
                    <i class="bi bi-clouds" style="color: #0F766E;"></i> Data Gas Monitoring
                </h2>

                <!-- Kotak Update Terakhir Dipindah ke Sisi Kanan Header -->
                <div class="gas-update-pill-card mb-0 py-1.5 px-3 d-inline-flex align-items-center gap-2 shadow-sm" style="border-radius: 10px;">
                    <div class="gas-update-icon-box" style="width: 28px; height: 28px; font-size: 14px; border-radius: 6px;">
                        <i class="bi bi-calendar2-check-fill"></i>
                    </div>
                    <div>
                        <div class="gas-update-label" style="font-size: 9.5px; line-height: 1;">Update Terakhir</div>
                        <div class="gas-update-value" style="font-size: 12.5px; line-height: 1.2;">{{ $gas_update_date }}</div>
                    </div>
                </div>
            </div>

            <!-- 5 Card Komponen Gas (CO, O2, H2S, LEL, Pressure) -->
            <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-md-5">
                <!-- Card 1: CO -->
                <div class="col">
                    <div class="gas-card-v2 shadow-sm">
                        <div class="gas-card-v2-header">
                            <div class="d-flex align-items-center gap-2">
                                <div class="gas-v2-icon" style="background: #15803D; color: #FFFFFF;">
                                    <i class="bi bi-cloud"></i>
                                </div>
                                <span class="gas-v2-title">CO (ppm)</span>
                            </div>
                            <span class="{{ $gas_co_status === 'Sesuai' ? 'badge-sesuai' : ($gas_co_status === 'Tidak Sesuai' ? 'badge-tidaksesuai' : 'badge bg-light text-muted border') }}">
                                {{ $gas_co_status }}
                            </span>
                        </div>
                        <div class="gas-v2-value-wrap">
                            <span class="gas-v2-value">@if(($gas_co ?? '-') !== '-'){{ $gas_co }} <span style="font-size: 13px; font-weight: 600; color: #475569;">ppm</span>@else - @endif</span>
                        </div>
                        <div class="gas-v2-threshold">NAB: &le; 25 ppm</div>
                    </div>
                </div>

                <!-- Card 2: O2 -->
                <div class="col">
                    <div class="gas-card-v2 shadow-sm">
                        <div class="gas-card-v2-header">
                            <div class="d-flex align-items-center gap-2">
                                <div class="gas-v2-icon" style="background: #2563EB; color: #FFFFFF;">
                                    <span class="fw-bold" style="font-size: 13px;">O₂</span>
                                </div>
                                <span class="gas-v2-title">O₂ (%)</span>
                            </div>
                            <span class="{{ $gas_o2_status === 'Sesuai' ? 'badge-sesuai' : ($gas_o2_status === 'Tidak Sesuai' ? 'badge-tidaksesuai' : 'badge bg-light text-muted border') }}">
                                {{ $gas_o2_status }}
                            </span>
                        </div>
                        <div class="gas-v2-value-wrap">
                            <span class="gas-v2-value">@if(($gas_o2 ?? '-') !== '-'){{ $gas_o2 }} <span style="font-size: 13px; font-weight: 600; color: #475569;">%</span>@else - @endif</span>
                        </div>
                        <div class="gas-v2-threshold">NAB: 19.5 - 23.5 %</div>
                    </div>
                </div>

                <!-- Card 3: H2S -->
                <div class="col">
                    <div class="gas-card-v2 shadow-sm">
                        <div class="gas-card-v2-header">
                            <div class="d-flex align-items-center gap-2">
                                <div class="gas-v2-icon" style="background: #DC2626; color: #FFFFFF;">
                                    <i class="bi bi-radioactive"></i>
                                </div>
                                <span class="gas-v2-title">H₂S (ppm)</span>
                            </div>
                            <span class="{{ $gas_h2s_status === 'Sesuai' ? 'badge-sesuai' : ($gas_h2s_status === 'Tidak Sesuai' ? 'badge-tidaksesuai' : 'badge bg-light text-muted border') }}">
                                {{ $gas_h2s_status }}
                            </span>
                        </div>
                        <div class="gas-v2-value-wrap">
                            <span class="gas-v2-value">@if(($gas_h2s ?? '-') !== '-'){{ $gas_h2s }} <span style="font-size: 13px; font-weight: 600; color: #475569;">ppm</span>@else - @endif</span>
                        </div>
                        <div class="gas-v2-threshold">NAB: &le; 10 ppm</div>
                    </div>
                </div>

                <!-- Card 4: LEL -->
                <div class="col">
                    <div class="gas-card-v2 shadow-sm">
                        <div class="gas-card-v2-header">
                            <div class="d-flex align-items-center gap-2">
                                <div class="gas-v2-icon" style="background: #D97706; color: #FFFFFF;">
                                    <i class="bi bi-fire"></i>
                                </div>
                                <span class="gas-v2-title">LEL (%)</span>
                            </div>
                            <span class="{{ $gas_lel_status === 'Sesuai' ? 'badge-sesuai' : ($gas_lel_status === 'Tidak Sesuai' ? 'badge-tidaksesuai' : 'badge bg-light text-muted border') }}">
                                {{ $gas_lel_status }}
                            </span>
                        </div>
                        <div class="gas-v2-value-wrap">
                            <span class="gas-v2-value">@if(($gas_lel ?? '-') !== '-'){{ $gas_lel }} <span style="font-size: 13px; font-weight: 600; color: #475569;">%</span>@else - @endif</span>
                        </div>
                        <div class="gas-v2-threshold">NAB: &le; 5 %</div>
                    </div>
                </div>

                <!-- Card 5: Pressure -->
                <div class="col">
                    <div class="gas-card-v2 shadow-sm">
                        <div class="gas-card-v2-header">
                            <div class="d-flex align-items-center gap-2">
                                <div class="gas-v2-icon" style="background: #1E3A5F; color: #FFFFFF;">
                                    <i class="bi bi-speedometer2"></i>
                                </div>
                                <span class="gas-v2-title">Pressure (Psi)</span>
                            </div>
                        </div>
                        <div class="gas-v2-value-wrap">
                            <span class="gas-v2-value">@if(($gas_pressure ?? '-') !== '-'){{ $gas_pressure }} <span style="font-size: 13px; font-weight: 600; color: #475569;">Psi</span>@else - @endif</span>
                        </div>
                        <div class="gas-v2-threshold text-muted">&nbsp;</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- BARIS 4: ISSUE & FOLLOW UP (WRAPPER HYPERLINK) -->
    <div class="mb-4">
        <a href="{{ route('issue-follow-up.index') }}" class="clickable-section-card issue-section-card block w-full transition duration-200 hover:shadow-md cursor-pointer text-decoration-none text-dark rounded-xl">
            <!-- Header Section: Judul (Tombol Lihat Semua Issue Dihapus) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="dash-card-title fs-5 mb-0">
                    <i class="bi bi-pie-chart-fill" style="color: #D97706;"></i> Issue &amp; Follow Up
                </h2>
            </div>

            <!-- 3 Card Rekap: All, Open, Closed (Grid 3 Kolom Sejajar Horizontal) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Kotak 1: All -->
                <div class="issue-recap-card shadow-sm" style="border-left-color: #1E3A5F;">
                    <div>
                        <div class="issue-recap-title" style="color: #1E3A5F;">All</div>
                        <div class="issue-recap-value" style="color: #1E3A5F;">{{ $total_issues }}</div>
                    </div>
                    <div class="icon-circle-insiden" style="background: #1E3A5F; color: #FFFFFF; width: 44px; height: 44px; border-radius: 10px; font-size: 20px;">
                        <i class="bi bi-layers-fill"></i>
                    </div>
                </div>

                <!-- Kotak 2: Open -->
                <div class="issue-recap-card shadow-sm" style="border-left-color: #DC2626;">
                    <div>
                        <div class="issue-recap-title" style="color: #DC2626;">Open</div>
                        <div class="issue-recap-value" style="color: #DC2626;">
                            {{ $total_open_issues }} <span style="font-size: 15px; font-weight: 700;">({{ $pct_open_issues }}%)</span>
                        </div>
                    </div>
                    <div class="icon-circle-insiden" style="background: #DC2626; color: #FFFFFF; width: 44px; height: 44px; border-radius: 10px; font-size: 20px;">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>
                </div>

                <!-- Kotak 3: Closed -->
                <div class="issue-recap-card shadow-sm" style="border-left-color: #15803D;">
                    <div>
                        <div class="issue-recap-title" style="color: #15803D;">Closed</div>
                        <div class="issue-recap-value" style="color: #15803D;">
                            {{ $total_closed_issues }} <span style="font-size: 15px; font-weight: 700;">({{ $pct_closed_issues }}%)</span>
                        </div>
                    </div>
                    <div class="icon-circle-insiden" style="background: #15803D; color: #FFFFFF; width: 44px; height: 44px; border-radius: 10px; font-size: 20px;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>

            <!-- 4 Card Pie Chart (Health & Safety, Security, Environment, Social) -->
            <div class="row g-3">
                @foreach($issue_follow_up_charts as $chart)
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="dash-card issue-chart-card rounded-lg shadow-sm h-100 d-flex flex-column justify-content-between" style="border-radius: 12px; padding: 18px;">
                            <div>
                                <!-- Card Header: Title & Total Badge -->
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h3 class="fw-bold mb-0" style="font-size: 0.95rem; font-weight: 700; color: #1E3A5F;">
                                        {{ $chart['name'] }}
                                    </h3>
                                    <span class="badge border" style="font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.5rem; border: 1px solid #CBD5E1 !important; color: #1E3A5F; background-color: #E2E8F0; border-radius: 9999px;">
                                        Total: {{ $chart['total'] }}
                                    </span>
                                </div>

                                <!-- Sub-header / Status Indicator Box -->
                                <div class="rounded p-2 text-xs mb-3 text-center" style="background-color: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 6px; padding: 6px 10px; font-size: 0.78rem; margin-bottom: 12px;">
                                    <span class="fw-semibold" style="color: #DC2626; font-weight: 600;">
                                        Open: {{ $chart['open_count'] }} ({{ $chart['open_percentage'] }}%)
                                    </span>
                                    <span class="mx-1" style="color: #64748B;">•</span>
                                    <span class="fw-semibold" style="color: #15803D; font-weight: 600;">
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
        </a>
    </div>

    <!-- BARIS 5: KEGIATAN TERBARU (EVENTS) & PEKERJAAN HARI INI (DAILY WORK) -->
    <div class="row g-4 mt-1">
        <!-- Kolom Kiri: Kegiatan Terbaru (Events) -->
        <div class="col-12 col-lg-6">
            <div class="dash-card dash-events-card p-0 overflow-hidden h-100 d-flex flex-column rounded-xl">
                <div class="dash-section-header p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h2 class="dash-card-title mb-1">
                            <i class="bi bi-calendar-event" style="color: #1D4ED8;"></i> Kegiatan Terbaru
                        </h2>
                        <div style="font-size: 11px; color: #475569;">
                            Daftar agenda dan kegiatan HSSE terkini
                        </div>
                    </div>
                    <a href="{{ route('events.index') }}" class="btn btn-sm py-1 px-2.5 btn-dash-events">
                        Lihat Semua
                    </a>
                </div>
                <div class="p-3 flex-grow-1">
                    @forelse($recent_events as $event)
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
                            <div class="event-thumb-box shadow-sm">
                                @if(!empty($event->documentation) && file_exists(public_path('storage/' . $event->documentation)))
                                    <img src="{{ asset('storage/' . $event->documentation) }}" alt="Event">
                                @else
                                    <i class="bi bi-image"></i>
                                @endif
                            </div>
                            <div class="event-info flex-grow-1 min-w-0">
                                <div class="event-title text-truncate" title="{{ $event->title }}">{{ $event->title }}</div>
                                <div class="event-date">
                                    <i class="bi bi-clock me-1"></i>{{ $event->event_date ? $event->event_date->translatedFormat('d M Y') : '-' }}
                                </div>
                            </div>
                            <div class="ms-2 flex-shrink-0">
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

        <!-- Kolom Kanan: Pekerjaan Hari Ini (Daily Work) -->
        <div class="col-12 col-lg-6">
            <div class="dash-card dash-dailywork-card p-0 overflow-hidden h-100 d-flex flex-column rounded-xl">
                <div class="dash-section-header p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h2 class="dash-card-title mb-1">
                            <i class="bi bi-briefcase" style="color: #15803D;"></i> Pekerjaan Hari Ini
                        </h2>
                        <div style="font-size: 11px; color: #475569;">
                            Menampilkan daftar pekerjaan yang aktif pada tanggal {{ $today_works_date_display ?? \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('d F Y') }}
                        </div>
                    </div>
                    <a href="{{ route('daily-work.index', ['date_mode' => 'today']) }}" class="btn btn-sm py-1 px-2.5 btn-dash-dailywork">
                        Lihat Semua
                    </a>
                </div>
                <div class="table-responsive flex-grow-1">
                    <table class="table dash-table table-hover mb-0" style="background: transparent;">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="ps-3 text-center">No</th>
                                <th>Nama Pekerjaan</th>
                                <th>Perusahaan</th>
                                <th>Lokasi</th>
                                <th class="text-center pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($today_works as $index => $work)
                                <tr>
                                    <td class="ps-3 text-center" style="color: #475569;">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 170px; color: #0F172A;" title="{{ $work->name }}">
                                            {{ $work->name }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge dash-company-badge shadow-sm" style="font-size: 10px;">
                                            {{ $work->company ?? $work->unit ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 120px; color: #475569;" title="{{ $work->location ?? '-' }}">
                                            {{ $work->location ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-3">
                                        @php
                                            $st = strtolower($work->status ?? 'berlangsung');
                                            $badgeClass = match(true) {
                                                str_contains($st, 'selesai') => 'status-badge-closed',
                                                str_contains($st, 'berlangsung') => 'status-badge-progress',
                                                str_contains($st, 'tunda') || str_contains($st, 'pause') => 'status-badge-overdue',
                                                default => 'status-badge-open',
                                            };
                                        @endphp
                                        <span class="status-badge-pill {{ $badgeClass }}">{{ $work->status ?? 'Berlangsung' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox d-block fs-3 mb-1"></i>
                                        Tidak ada pekerjaan yang sesuai dengan periode hari ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
            const bgColors = isEmpty ? ['#CBD5E1'] : ['#DC2626', '#15803D'];
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
