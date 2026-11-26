@extends('layouts.app', [
    'title' => 'Inventory',
    'activeModule' => 'inventory'
])

@push('styles')
<style>
    :root {
        --inv-navy: #17324D;
        --inv-navy-hover: #0F2337;
        --inv-slate: #64748B;
        --inv-surface: #FFFFFF;
        --inv-bg-muted: #F1F5F9;
        --inv-teal: #0F766E;
        --inv-teal-hover: #0D5F59;
        --inv-danger: #DC2626;
        --inv-border: #CBD5E1;
        --inv-border-light: #E2E8F0;
        /* Alias variabel kompatibilitas */
        --navy: #17324D;
        --navy-dark: #0F2337;
        --green: #0F766E;
        --page-bg: #F1F5F9;
        --border: #E2E8F0;
        --muted: #64748B;
    }

    /* HEADER UTAMA INVENTORY — FORMAL & PROFESIONAL */
    .inv-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--inv-border-light);
    }
    .inv-header-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: var(--inv-navy);
        color: var(--inv-surface);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .inv-header-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .btn-inv-primary,
    .btn-inv-secondary {
        height: 38px;
        padding: 0 15px;
        border-radius: 7px;
        font-size: 12.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        text-decoration: none;
        transition: all 0.16s ease;
        cursor: pointer;
        white-space: nowrap;
        line-height: 1;
    }
    .btn-inv-primary i,
    .btn-inv-secondary i {
        font-size: 14px;
        line-height: 1;
    }
    .btn-inv-primary {
        background: var(--inv-teal);
        color: var(--inv-surface);
        border: 1px solid var(--inv-teal);
    }
    .btn-inv-primary:hover,
    .btn-inv-primary:focus {
        background: var(--inv-teal-hover);
        border-color: var(--inv-teal-hover);
        color: var(--inv-surface);
    }
    .btn-inv-secondary {
        background: var(--inv-surface);
        color: var(--inv-navy);
        border: 1px solid var(--inv-border);
    }
    .btn-inv-secondary i {
        color: var(--inv-slate);
    }
    .btn-inv-secondary:hover,
    .btn-inv-secondary:focus {
        background: var(--inv-bg-muted);
        border-color: #94A3B8;
        color: var(--inv-navy);
    }
    .btn-inv-secondary:hover i {
        color: var(--inv-navy);
    }
    .btn-inv-navy {
        background: var(--inv-navy);
        color: var(--inv-surface);
        border: 1px solid var(--inv-navy);
    }
    .btn-inv-navy:hover,
    .btn-inv-navy:focus {
        background: var(--inv-navy-hover);
        border-color: var(--inv-navy-hover);
        color: var(--inv-surface);
    }

    /* NAVIGASI 4 TAB DALAM 2 KELOMPOK WARNA FORMAL (HIJAU: STOK/TRANSAKSI, KUNING: PEMINJAMAN) */
    /* NAVIGASI TAB INVENTORY — BLOK WARNA SOLID LEMBUT PER KELOMPOK (SEJAJAR 1 BARIS HORIZONTAL) */
    .inventory-nav-tabs {
        display: flex;
        flex-wrap: nowrap !important;
        align-items: stretch;
        gap: 12px;
        background: transparent;
        border: none;
        padding: 0 0 4px 0;
        margin-bottom: 20px;
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }
    .tab-group {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 6px;
        border-radius: 10px;
        flex-shrink: 0;
        box-sizing: border-box;
    }
    .tab-group-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0 8px 0 6px;
        white-space: nowrap;
        flex-shrink: 0;
        user-select: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .inventory-nav-tabs .tab-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 13px;
        min-height: 36px;
        white-space: nowrap;
        flex-shrink: 0;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        border-radius: 7px;
        border: none !important;
        outline: none !important;
        transition: all 0.15s ease-in-out;
    }
    .tab-badge {
        font-size: 11px;
        padding: 2px 7px;
        border-radius: 999px;
        font-weight: 700;
        line-height: 1.25;
        border: none !important;
    }
    .tab-badge.badge-overdue-alert {
        background: var(--inv-danger) !important;
        color: #FFFFFF !important;
        border-color: var(--inv-danger) !important;
    }

    /* 1. KELOMPOK STOK & TRANSAKSI (BLOK HIJAU SAGE / MINT LEMBUT) */
    .tab-group-stock {
        background: #e8f5ec;
        border: 1px solid #d4ebd9;
    }
    .tab-group-stock .tab-group-label {
        color: #1b4d2e;
    }
    .tab-group-stock .tab-group-label i {
        color: #1b4d2e;
    }
    .tab-link-green {
        color: #1b4d2e;
        background: transparent;
    }
    .tab-link-green i {
        font-size: 14px;
        color: #2d6a4f;
        transition: color 0.15s ease;
    }
    .tab-link-green .tab-badge {
        background: rgba(27, 77, 46, 0.12);
        color: #1b4d2e;
    }
    .tab-link-green:hover {
        color: #0f381e;
        background: rgba(255, 255, 255, 0.65);
    }
    .tab-link-green:hover i {
        color: #1b4d2e;
    }
    .tab-link-green.active {
        color: #ffffff !important;
        background: #1e7e48 !important;
        box-shadow: 0 1px 3px rgba(30, 126, 72, 0.25) !important;
    }
    .tab-link-green.active i {
        color: #ffffff !important;
    }
    .tab-link-green.active .tab-badge {
        background: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
    }

    /* 2. KELOMPOK PEMINJAMAN (BLOK KUNING PASTEL / KREM KEEMASAN LEMBUT) */
    .tab-group-borrow {
        background: #fdf4dc;
        border: 1px solid #fae6be;
    }
    .tab-group-borrow .tab-group-label {
        color: #783f04;
    }
    .tab-group-borrow .tab-group-label i {
        color: #783f04;
    }
    .tab-link-yellow {
        color: #783f04;
        background: transparent;
    }
    .tab-link-yellow i {
        font-size: 14px;
        color: #92400e;
        transition: color 0.15s ease;
    }
    .tab-link-yellow .tab-badge {
        background: rgba(120, 63, 4, 0.12);
        color: #783f04;
    }
    .tab-link-yellow:hover {
        color: #5a2e02;
        background: rgba(255, 255, 255, 0.65);
    }
    .tab-link-yellow:hover i {
        color: #783f04;
    }
    .tab-link-yellow.active {
        color: #ffffff !important;
        background: #b46b08 !important;
        box-shadow: 0 1px 3px rgba(180, 107, 8, 0.25) !important;
    }
    .tab-link-yellow.active i {
        color: #ffffff !important;
    }
    .tab-link-yellow.active .tab-badge {
        background: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
    }

    /* AUTOCOMPLETE TERINTEGRASI CATATAN TRANSAKSI — UKURAN LEBIH BESAR & JELAS */
    .trx-table-responsive {
        overflow-x: auto;
        overflow-y: visible;
    }
    @media (min-width: 768px) {
        .trx-table-responsive {
            overflow: visible !important;
        }
    }
    .trx-autocomplete-wrapper {
        position: relative;
        width: 100%;
    }
    .trx-item-search-input {
        font-size: 13.5px !important;
        font-weight: 500;
        min-height: 36px;
    }
    .trx-autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        width: min(440px, calc(100vw - 44px));
        min-width: 0;
        max-width: min(600px, calc(100vw - 32px));
        max-height: 350px !important;
        overflow-y: auto !important;
        z-index: 1080 !important;
        background: #ffffff;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2), 0 8px 10px -6px rgba(15, 23, 42, 0.1) !important;
        margin-top: 4px;
        padding: 5px;
    }
    .trx-ac-item {
        border: none !important;
        border-bottom: 1px solid #f1f5f9 !important;
        border-radius: 6px !important;
        padding: 10px 14px !important;
        margin-bottom: 3px !important;
        text-align: left !important;
        background: #ffffff;
        transition: all 0.15s ease-in-out;
    }
    .trx-ac-item:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
    }
    .trx-ac-item:hover,
    .trx-ac-item.active {
        background-color: #f0fdf4 !important;
        border-left: 3.5px solid #16a34a !important;
    }
    .trx-ac-title {
        font-size: 13.5px;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.4;
    }
    .trx-ac-code {
        font-family: var(--bs-font-monospace);
        font-weight: 700;
        color: #0284c7;
        margin-right: 6px;
    }
    .trx-ac-category {
        font-size: 11px;
        font-weight: 500;
        padding: 2px 8px;
        border-radius: 4px;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .trx-ac-unit {
        font-size: 12px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 6px;
        background: #f8fafc;
        color: #334155;
        border: 1px solid #cbd5e1;
    }
    .trx-ac-stock {
        font-size: 12px;
        color: #64748b;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .trx-ac-stock-val {
        color: #0f172a;
        font-weight: 600;
    }
    .trx-row-stock-info {
        font-size: 12px !important;
        color: #475569;
        line-height: 1.35;
    }

    @media (max-width: 991.98px) {
        .inventory-nav-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            padding-bottom: 4px;
        }
    }

    /* DASHBOARD SUMMARY CARDS — FORMAL INDUSTRIAL */
    .inv-summary-card {
        background: var(--inv-surface);
        border: 1px solid var(--inv-border-light);
        border-radius: 10px;
        padding: 16px 18px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 1px 2px rgba(23, 50, 77, 0.04);
    }
    .inv-summary-card.card-navy { border-left: 3px solid var(--inv-navy); }
    .inv-summary-card.card-teal { border-left: 3px solid var(--inv-teal); }
    .inv-summary-card.card-blue { border-left: 3px solid var(--inv-navy); }
    .inv-summary-card.card-danger { border-left: 3px solid var(--inv-danger); }

    .inv-summary-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: var(--inv-slate);
    }
    .inv-summary-value {
        font-size: 26px;
        font-weight: 800;
        color: var(--inv-navy);
        line-height: 1.15;
        margin: 6px 0 4px;
    }
    .inv-summary-sub {
        font-size: 12px;
        color: var(--inv-slate);
    }
    .inv-stat-pill {
        font-size: 11px;
        font-weight: 600;
        color: var(--inv-slate);
        background: var(--inv-bg-muted);
        border: 1px solid var(--inv-border-light);
        border-radius: 999px;
        padding: 2px 9px;
    }
    .inv-stat-pill.pill-danger {
        color: var(--inv-danger);
        background: #FEF2F2;
        border-color: #FECACA;
    }

    /* PANEL & FILTER BAR */
    .panel {
        background: var(--inv-surface);
        border: 1px solid var(--inv-border-light);
        border-radius: 10px;
        padding: 16px 18px;
        box-shadow: 0 1px 2px rgba(23, 50, 77, 0.03);
    }

    .badge-kategori {
        font-size: 11px;
        background: var(--inv-bg-muted);
        color: var(--inv-navy);
        border: 1px solid var(--inv-border);
        font-weight: 600;
        border-radius: 6px;
        padding: 3px 8px;
        display: inline-block;
    }

    .badge-loc-warehouse {
        background: var(--inv-bg-muted);
        color: var(--inv-navy);
        border: 1px solid var(--inv-border);
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
    }

    .badge-loc-hsse {
        background: #F0FDFA;
        color: var(--inv-teal);
        border: 1px solid #99F6E4;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
    }

    /* TRANSACTION TYPE RADIO BUTTONS */
    .trx-type-btn {
        cursor: pointer;
        border: 1px solid var(--inv-border);
        padding: 9px 14px;
        border-radius: 7px;
        text-align: center;
        transition: all 0.16s ease;
        background: var(--inv-bg-muted);
        color: var(--inv-navy);
        font-weight: 600;
    }
    .trx-radio:checked + .trx-type-masuk {
        border-color: var(--inv-teal);
        background: #F0FDFA;
        color: var(--inv-teal);
        font-weight: 700;
    }
    .trx-radio:checked + .trx-type-keluar {
        border-color: #FCA5A5;
        background: #FEF2F2;
        color: #B91C1C;
        font-weight: 700;
    }
    .trx-radio:checked + .trx-type-keluar i {
        color: #DC2626;
    }

    /* OVERDUE ROW HIGHLIGHT */
    .row-overdue {
        background-color: #FFF5F5 !important;
    }
    .row-overdue td {
        border-bottom-color: #FECACA !important;
    }

    /* MODAL STYLING */
    .modal-content {
        border: 1px solid var(--inv-border-light);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.14);
    }
    .modal-header {
        padding: 14px 20px;
        border-bottom: 1px solid var(--inv-border-light);
        background: var(--inv-bg-muted);
    }
    .modal-body.scrollable-modal-body {
        max-height: calc(86vh - 130px);
        overflow-y: auto;
        padding: 20px;
    }
    .modal-footer {
        padding: 12px 20px;
        border-top: 1px solid var(--inv-border-light);
        background: var(--inv-bg-muted);
    }
</style>
@endpush

@section('content')
@php
    $isAdminInventoryUser = auth()->user()?->isAdminInventory() ?? false;
    $invBreadcrumbs = $isAdminInventoryUser
        ? [['label' => 'Peminjaman Barang']]
        : [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Inventory'],
        ];
@endphp
<!-- 1. HEADER & TOMBOL AKSI UTAMA (TANPA EXPORT PDF DI HEADER UTAMA) -->
<x-module-header
    :title="$isAdminInventoryUser ? 'Peminjaman Barang' : 'Inventory'"
    icon="bi bi-boxes"
    icon-bg="#17324D"
    icon-shadow="rgba(23, 50, 77, 0.25)"
    title-color="#17324D"
    :breadcrumbs="$invBreadcrumbs"
    :bordered="true"
>
    @canManageInventory
    <x-slot:actions>
        <button type="button"
                class="btn-inv-secondary"
                data-bs-toggle="modal"
                data-bs-target="#modalPinjamBarang"
                onclick="openBorrowModal()">
            <i class="bi bi-box-arrow-right"></i>
            <span>Pinjam Barang</span>
        </button>

        @adminManagement
        <button type="button"
                class="btn-inv-secondary"
                onclick="openTrxModal()">
            <i class="bi bi-arrow-left-right"></i>
            <span>Catat Transaksi</span>
        </button>

        <button type="button"
                class="btn-inv-secondary"
                onclick="openMasterModal('kategori')">
            <i class="bi bi-tags"></i>
            <span>Kelola Master</span>
        </button>

        <button type="button"
                class="btn-inv-primary hsse-action-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalTambahBarang">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Master Barang</span>
        </button>
        @endadminManagement
    </x-slot:actions>
    @endcanManageInventory
</x-module-header>

<!-- NOTIFIKASI FLASH & VALIDASI -->
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

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Terdapat kesalahan input:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- 2. NAVIGASI 4 TAB UTAMA DALAM 2 KELOMPOK WARNA SEJAJAR 1 BARIS (HIJAU: STOK & TRANSAKSI | KUNING: PEMINJAMAN) -->
<div class="inventory-nav-tabs" role="tablist">
    @if(!auth()->user()?->isAdminInventory())
    <!-- Kelompok 1 — Stok & Transaksi (Warna Hijau) -->
    <div class="tab-group tab-group-stock">
        <span class="tab-group-label"><i class="bi bi-layers me-1"></i>Stok &amp; Transaksi</span>

        <a href="{{ route('inventory.index', ['tab' => 'master']) }}"
           class="tab-link tab-link-green {{ $activeTab === 'master' ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i>
            <span>Master Barang</span>
            <span class="tab-badge">{{ $items->total() }}</span>
        </a>

        <a href="{{ route('inventory.index', ['tab' => 'history']) }}"
           class="tab-link tab-link-green {{ $activeTab === 'history' ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i>
            <span>Riwayat Transaksi</span>
            <span class="tab-badge">{{ $transactions->total() }}</span>
        </a>
    </div>
    @endif

    <!-- Kelompok 2 — Peminjaman (Warna Kuning) -->
    <div class="tab-group tab-group-borrow">
        <span class="tab-group-label"><i class="bi bi-arrow-left-right me-1"></i>Peminjaman</span>

        <a href="{{ route('inventory.index', ['tab' => 'borrowed']) }}"
           class="tab-link tab-link-yellow {{ $activeTab === 'borrowed' ? 'active' : '' }}">
            <i class="bi bi-box-arrow-right"></i>
            <span>Barang Terpinjam</span>
            <span class="tab-badge {{ ($totalPeminjamanOverdue ?? 0) > 0 ? 'badge-overdue-alert' : '' }}">
                {{ $totalPeminjamanAktif ?? 0 }}
            </span>
        </a>

        <a href="{{ route('inventory.index', ['tab' => 'borrow_history']) }}"
           class="tab-link tab-link-yellow {{ $activeTab === 'borrow_history' ? 'active' : '' }}">
            <i class="bi bi-journal-check"></i>
            <span>History Peminjaman</span>
            <span class="tab-badge">{{ $totalHistoryPeminjaman ?? 0 }}</span>
        </a>
    </div>
</div>

<!-- ==================== ISI KONTEN MENYESUAIKAN TAB AKTIF ==================== -->

@if($activeTab === 'master')
    <!-- TAB 1: MASTER BARANG (A.1 - A.3, B.4 - B.5) -->
    <!-- Ringkasan Statistik Stok Master Barang (3 Kartu Berbeda Fungsi) -->
    <div class="row g-3 mb-3">
        <!-- Kartu 1: Jenis Barang Tersedia (Jumlah jenis barang berbeda yang mempunyai stok tersedia > 0) -->
        <div class="col-12 col-md-4">
            <div class="inv-summary-card card-navy">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="inv-summary-label">Jenis Barang Tersedia</span>
                    <span class="inv-stat-pill">
                        <i class="bi bi-grid-3x3-gap me-1"></i>Stok &gt; 0
                    </span>
                </div>
                <div class="inv-summary-value">{{ number_format($jenisBarang) }}</div>
                <div class="inv-summary-sub">
                    Jumlah jenis barang berbeda yang mempunyai stok tersedia lebih dari nol
                </div>
            </div>
        </div>

        <!-- Kartu 2: Total Jumlah Barang (Akumulasi stok tersedia Warehouse dan Gudang HSSE) -->
        <div class="col-12 col-md-4">
            <div class="inv-summary-card card-teal">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="inv-summary-label">Total Jumlah Barang</span>
                    <span class="inv-stat-pill">
                        <i class="bi bi-stack me-1"></i>Akumulasi Tersedia
                    </span>
                </div>
                <div class="inv-summary-value" style="color: var(--inv-teal);">{{ number_format($totalJumlahBarang) }}</div>
                <div class="inv-summary-sub">
                    Akumulasi stok fisik tersedia di Warehouse dan Gudang HSSE (di luar barang dipinjam)
                </div>
            </div>
        </div>

        <!-- Kartu 3: Stok Warehouse / Gudang HSSE (Jumlah stok masing-masing lokasi dalam satu kartu) -->
        <div class="col-12 col-md-4">
            <div class="inv-summary-card card-navy">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="inv-summary-label">Stok Warehouse / Gudang HSSE</span>
                    <span class="inv-stat-pill">
                        <i class="bi bi-geo-alt me-1"></i>Per Lokasi
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between gap-2 my-1">
                    <div class="flex-fill p-2 rounded border" style="background: var(--inv-bg-muted); border-color: var(--inv-border-light) !important;">
                        <div class="text-muted" style="font-size: 10.5px; font-weight: 700; text-transform: uppercase;">Warehouse</div>
                        <div class="fw-bold" style="font-size: 19px; color: var(--inv-navy); line-height: 1.2;">{{ number_format($totalStokWarehouse) }}</div>
                    </div>
                    <div class="flex-fill p-2 rounded border" style="background: #F0FDFA; border-color: #99F6E4 !important;">
                        <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: var(--inv-teal);">Gudang HSSE</div>
                        <div class="fw-bold" style="font-size: 19px; color: var(--inv-teal); line-height: 1.2;">{{ number_format($totalStokHsse) }}</div>
                    </div>
                </div>
                <div class="inv-summary-sub">
                    Rincian jumlah stok tersedia pada masing-masing lokasi penyimpanan
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Master Barang -->
    <div class="panel mb-3">
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="master">

            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold mb-1 text-secondary">Nama atau Kode Barang</label>
                <input type="text"
                       name="filter_item"
                       value="{{ $filterItem }}"
                       class="form-control form-control-sm"
                       placeholder="Cari nama atau kode barang...">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Kategori</label>
                <select name="filter_category" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ $filterCategory === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Lokasi</label>
                <select name="filter_location" class="form-select form-select-sm">
                    <option value="">Semua Lokasi</option>
                    @foreach($locationList as $loc)
                        <option value="{{ $loc }}" {{ $filterLocation === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold mb-1 text-secondary">Tanggal Transaksi</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="filter_date_start" value="{{ $filterDateStart }}" class="form-control" title="Dari Tanggal">
                    <span class="input-group-text bg-light text-muted px-1">s/d</span>
                    <input type="date" name="filter_date_end" value="{{ $filterDateEnd }}" class="form-control" title="Sampai Tanggal">
                </div>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">PIC / Petugas Pencatat</label>
                <div class="d-flex gap-1">
                    <input type="text"
                           name="filter_pic"
                           value="{{ $filterPic }}"
                           list="picDatalist"
                           class="form-control form-control-sm"
                           placeholder="Nama PIC...">
                    <datalist id="picDatalist">
                        @foreach($picList as $p)
                            <option value="{{ $p }}">
                        @endforeach
                    </datalist>
                    <button type="submit" class="btn btn-primary btn-sm px-2" title="Terapkan Filter">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <a href="{{ route('inventory.index', ['tab' => 'master']) }}"
                       class="btn btn-outline-secondary btn-sm px-2"
                       title="Reset Filter">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
    <div class="panel p-0 overflow-hidden">
        <div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="fw-bold" style="color: var(--inv-navy);"><i class="bi bi-box-seam me-1 text-secondary"></i> Daftar Master Barang &amp; Stok Per Lokasi</span>
                <small class="text-muted ms-2">Stok barang yang sedang dipinjam tidak dihitung sebagai stok tersedia di lokasi</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                @canExportPdf
                <a href="{{ route('inventory.export', array_merge(request()->query(), ['tab' => 'master', 'format' => 'pdf'])) }}"
                   class="btn-inv-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Export PDF</span>
                </a>
                @endcanExportPdf
                <span class="badge bg-white text-secondary border font-monospace">Total: {{ $items->total() }} Barang</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th class="text-center">Satuan</th>
                        <th class="text-center">Stok Warehouse</th>
                        <th class="text-center">Stok Gudang HSSE</th>
                        <th class="text-center">Total Stok</th>
                        <th>Data Expired</th>
                        <th>Keterangan</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $expiryGroups = $item->getStockExpiryGroups(true);
                            $recordedGroups = collect($expiryGroups)->filter(fn($g) => !empty($g['expired_date']))->values();
                            $unrecordedGroups = collect($expiryGroups)->filter(fn($g) => empty($g['expired_date']))->values();
                            $earliestGroup = $recordedGroups->first();
                            $hasHistory = $item->transactions()->exists() || $item->borrowings()->exists();
                            $stokExpiredPayload = [
                                'id' => $item->id,
                                'code' => $item->kode_barang,
                                'name' => $item->nama_barang,
                                'category' => $item->kategori,
                                'satuan' => $item->satuan,
                                'stock_warehouse' => (int) $item->stock_warehouse,
                                'stock_hsse' => (int) $item->stock_hsse,
                                'total_stock' => (int) $item->current_stock,
                                'borrowed_stock' => (int) $item->borrowed_stock,
                                'groups' => $expiryGroups,
                            ];
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <span class="badge bg-secondary font-monospace">{{ $item->kode_barang }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->nama_barang }}</div>
                                @if($item->borrowed_stock > 0)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mt-1" style="font-size: 10px;">
                                        <i class="bi bi-arrow-left-right me-1"></i>Sedang Dipinjam: {{ $item->borrowed_stock }} {{ $item->satuan }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge-kategori">{{ $item->kategori }}</span>
                            </td>
                            <td class="text-center fw-semibold text-secondary">
                                {{ $item->satuan }}
                            </td>
                            <td class="text-center">
                                <span class="badge-loc-warehouse">{{ number_format($item->stock_warehouse) }} {{ $item->satuan }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge-loc-hsse">{{ number_format($item->stock_hsse) }} {{ $item->satuan }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $item->current_stock > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} px-2 py-1 fw-bold">
                                    {{ number_format($item->current_stock) }} {{ $item->satuan }}
                                </span>
                            </td>
                            <td>
                                @if($earliestGroup)
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="small fw-semibold {{ $earliestGroup['status'] === 'expired' ? 'text-danger' : 'text-dark' }}">
                                            <i class="bi bi-calendar-event me-1"></i>{{ $earliestGroup['formatted_date'] }}
                                        </span>
                                        <span class="badge {{ $earliestGroup['badge_class'] }}" style="font-size: 9.5px;">
                                            {{ $earliestGroup['status_label'] }}
                                        </span>
                                    </div>
                                    @if(count($expiryGroups) > 1)
                                        <small class="text-muted d-block mt-1" style="font-size: 10.5px;">
                                            <i class="bi bi-layers me-1"></i>{{ count($expiryGroups) }} kelompok stok/expired
                                        </small>
                                    @endif
                                @elseif($unrecordedGroups->isNotEmpty())
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 10px;">
                                        Belum Dicatat
                                    </span>
                                    @if(count($unrecordedGroups) > 1)
                                        <small class="text-muted d-block mt-1" style="font-size: 10.5px;">
                                            {{ count($unrecordedGroups) }} lokasi stok
                                        </small>
                                    @endif
                                @else
                                    <span class="badge bg-light text-muted border" style="font-size: 10px;">Stok Kosong (0)</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $item->keterangan ?: '-' }}</small>
                            </td>
                            <td class="text-center pe-3 text-nowrap">
                                <button type="button"
                                        class="btn btn-sm btn-outline-dark me-1"
                                        onclick="openStokExpiredModal({{ json_encode($stokExpiredPayload) }})"
                                        title="Lihat Rincian Stok & Tanggal Expired per Lokasi">
                                    <i class="bi bi-calendar2-check me-1"></i>Lihat Stok &amp; Expired
                                </button>

                                @canManageInventory
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success me-1"
                                            onclick="openQuickTransaction({{ $item->id }})"
                                            title="Catat Mutasi Barang Ini">
                                        <i class="bi bi-arrow-down-up"></i>
                                    </button>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary me-1"
                                            onclick="openEditModal({{ json_encode([
                                                'id' => $item->id,
                                                'barang_id' => $item->kode_barang,
                                                'nama_barang' => $item->nama_barang,
                                                'kategori' => $item->kategori,
                                                'satuan' => $item->satuan,
                                                'stock_warehouse' => (int) $item->stock_warehouse,
                                                'stock_hsse' => (int) $item->stock_hsse,
                                                'keterangan' => $item->keterangan ?? '',
                                                'expired_date' => $item->expired_date ? $item->expired_date->format('Y-m-d') : '',
                                            ]) }})"
                                            title="Edit Master Barang">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="openDeleteConfirmation({{ $item->id }}, {{ Js::from($item->nama_barang) }}, {{ Js::from($item->kode_barang) }}, {{ $hasHistory ? 'true' : 'false' }})"
                                            title="Hapus Master Barang">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endcanManageInventory
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
                                Tidak ada data master barang yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $items->links('pagination::bootstrap-5') }}
        </div>
    </div>

@elseif($activeTab === 'history')
    <!-- TAB 2: RIWAYAT TRANSAKSI (D.12 - D.14) -->
    <!-- Filter Spesifik Riwayat Transaksi -->
    <div class="panel mb-3">
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="history">

            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold mb-1 text-secondary">Nama / Kode Barang</label>
                <input type="text" name="trx_search" value="{{ request('trx_search', $filterItem) }}" class="form-control form-control-sm" placeholder="Cari barang atau kode transaksi...">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Kategori</label>
                <select name="trx_kategori" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ request('trx_kategori', $filterCategory) == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Lokasi & Jenis</label>
                <div class="input-group input-group-sm">
                    <select name="trx_lokasi" class="form-select">
                        <option value="">Semua Lokasi</option>
                        @foreach($locationList as $loc)
                            <option value="{{ $loc }}" {{ request('trx_lokasi', $filterLocation) == $loc ? 'selected' : '' }}>{{ $loc }}</option>
                        @endforeach
                    </select>
                    <select name="trx_jenis" class="form-select">
                        <option value="">Semua</option>
                        <option value="masuk" {{ request('trx_jenis') == 'masuk' ? 'selected' : '' }}>Masuk</option>
                        <option value="keluar" {{ request('trx_jenis') == 'keluar' ? 'selected' : '' }}>Keluar</option>
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold mb-1 text-secondary">Tanggal Transaksi</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="trx_start" value="{{ request('trx_start', $filterDateStart) }}" class="form-control">
                    <span class="input-group-text bg-light text-muted px-1">s/d</span>
                    <input type="date" name="trx_end" value="{{ request('trx_end', $filterDateEnd) }}" class="form-control">
                </div>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">PIC / Petugas</label>
                <div class="d-flex gap-1">
                    <input type="text" name="trx_pic" value="{{ request('trx_pic', $filterPic) }}" class="form-control form-control-sm" placeholder="Nama PIC...">
                    <button type="submit" class="btn btn-primary btn-sm px-2" title="Filter">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('inventory.index', ['tab' => 'history']) }}" class="btn btn-outline-secondary btn-sm px-2" title="Reset">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="panel p-0 overflow-hidden">
        <div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="fw-bold" style="color: var(--inv-navy);"><i class="bi bi-clock-history me-1 text-secondary"></i> Riwayat Transaksi Mutasi Barang</span>
                <small class="text-muted ms-2">Seluruh histori mutasi barang masuk dan keluar per lokasi penyimpanan</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                @canExportPdf
                <a href="{{ route('inventory.export', array_merge(request()->query(), ['tab' => 'history', 'format' => 'pdf'])) }}"
                   class="btn-inv-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Export PDF</span>
                </a>
                @endcanExportPdf
                <span class="badge bg-white text-secondary border font-monospace">Total: {{ $transactions->total() }} Transaksi</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Kode Transaksi</th>
                        <th>Tanggal Transaksi</th>
                        <th>Nama Barang</th>
                        <th class="text-center">Jenis Transaksi</th>
                        <th>Lokasi</th>
                        <th class="text-center">Satuan</th>
                        <th class="text-center">Kuantitas</th>
                        <th>PIC / Petugas Pencatat</th>
                        <th>Keterangan</th>
                        <th class="text-center pe-3">Aksi Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        @php
                            $invItem = $trx->inventory ?? $trx->item;
                            $jenisLower = strtolower(trim((string) ($trx->jenis_transaksi ?? $trx->transaction_type ?? 'masuk')));
                            $isPenyesuaian = $jenisLower === 'penyesuaian';
                            $isMasuk = $jenisLower === 'masuk' || ($isPenyesuaian && strtolower((string) $trx->transaction_type) === 'masuk');
                            $tglTrx = $trx->tanggal ?? $trx->transaction_date ?? $trx->created_at;
                            $picName = $trx->petugas ?? $trx->person_in_charge ?? 'Petugas HSSE';
                            $namaBarang = $invItem?->nama_barang ?? $invItem?->item_name ?? 'Barang Dihapus';
                            $kodeBarang = $invItem?->kode_barang ?? $invItem?->item_code ?? '-';
                            $kategoriBarang = $invItem?->kategori ?? $invItem?->category ?? '-';
                            $satuanBarang = $trx->satuan ?: ($invItem?->satuan ?? 'Box');
                            $lokasiTrx = $trx->location ?: 'Warehouse';
                            $qtyVal = (int) ($trx->jumlah ?? $trx->quantity ?? 0);
                            $trxCode = $trx->transaction_code ?: ('TRX-' . str_pad((string) $trx->id, 4, '0', STR_PAD_LEFT));
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <span class="badge bg-light text-dark border font-monospace">{{ $trxCode }}</span>
                            </td>
                            <td class="text-nowrap">
                                <div class="fw-semibold text-dark">{{ $tglTrx ? \Carbon\Carbon::parse($tglTrx)->format('d/m/Y') : '-' }}</div>
                                <small class="text-muted" style="font-size: 10.5px;">{{ $trx->created_at ? $trx->created_at->format('H:i') . ' WIB' : '' }}</small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $namaBarang }}</div>
                                <small class="text-muted font-monospace">{{ $kodeBarang }} · {{ $kategoriBarang }}</small>
                            </td>
                            <td class="text-center">
                                @if($isPenyesuaian)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-sliders me-1"></i>Penyesuaian
                                    </span>
                                @elseif($isMasuk)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-box-arrow-in-down me-1"></i>Masuk
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="bi bi-box-arrow-up me-1"></i>Keluar
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $lokasiTrx === 'Gudang HSSE' ? 'badge-loc-hsse' : 'badge-loc-warehouse' }}">
                                    {{ $lokasiTrx }}
                                </span>
                            </td>
                            <td class="text-center text-secondary fw-semibold">
                                {{ $satuanBarang }}
                            </td>
                            <td class="text-center fw-bold">
                                @if($isPenyesuaian)
                                    <span class="text-warning-emphasis">{{ number_format($qtyVal) }}</span>
                                @elseif($isMasuk)
                                    <span class="text-success">+{{ number_format($qtyVal) }}</span>
                                @else
                                    <span class="text-danger">-{{ number_format($qtyVal) }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><i class="bi bi-person-badge text-primary me-1"></i>{{ $picName }}</span>
                            </td>
                            <td>
                                <small class="text-muted d-block">{{ $trx->keterangan ?? $trx->notes ?? '-' }}</small>
                                @if($trx->expired_date)
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mt-1" style="font-size: 10px;">
                                        Exp: {{ $trx->expired_date->format('d/m/Y') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        onclick="openTransactionDetailModal({{ json_encode([
                                            'code' => $trxCode,
                                            'date' => $tglTrx ? \Carbon\Carbon::parse($tglTrx)->format('d/m/Y') : '-',
                                            'time' => $trx->created_at ? $trx->created_at->format('H:i') . ' WIB' : '-',
                                            'item_name' => $namaBarang,
                                            'item_code' => $kodeBarang,
                                            'category' => $kategoriBarang,
                                            'type' => $isMasuk ? 'Barang Masuk (In)' : 'Barang Keluar (Out)',
                                            'location' => $lokasiTrx,
                                            'satuan' => $satuanBarang,
                                            'qty' => $qtyVal,
                                            'pic' => $picName,
                                            'expired_date' => $trx->expired_date ? $trx->expired_date->format('d/m/Y') : '-',
                                            'notes' => $trx->keterangan ?? $trx->notes ?? '-',
                                        ]) }})"
                                        title="Lihat Detail Transaksi">
                                    <i class="bi bi-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history fs-2 d-block mb-2"></i>
                                Belum ada riwayat transaksi yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    </div>

@elseif($activeTab === 'borrowed')
    <!-- TAB 3: BARANG TERPINJAM (F.14 - F.20, G.21 - G.23) -->
    <!-- Kartu Statistik Peminjaman (F.17: Jumlah Peminjaman, Jumlah Barang Dipinjam, Melewati Tenggat) -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="inv-summary-card card-navy">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="inv-summary-label">Jumlah Peminjaman</span>
                    <span class="inv-stat-pill">
                        <i class="bi bi-clock me-1"></i>Aktif
                    </span>
                </div>
                <div class="inv-summary-value">{{ number_format($totalPeminjamanAktif ?? 0) }}</div>
                <div class="inv-summary-sub">Jumlah catatan peminjaman yang masih memiliki barang belum dikembalikan</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="inv-summary-card card-teal">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="inv-summary-label">Jumlah Barang Dipinjam</span>
                    <span class="inv-stat-pill">
                        <i class="bi bi-box me-1"></i>Masih Dipinjam
                    </span>
                </div>
                <div class="inv-summary-value" style="color: var(--inv-navy);">{{ number_format($totalUnitTerpinjam ?? 0) }}</div>
                <div class="inv-summary-sub">Total kuantitas barang yang masih dipinjam di lapangan</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="inv-summary-card {{ ($totalPeminjamanOverdue ?? 0) > 0 ? 'card-danger' : 'card-navy' }}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="inv-summary-label">Melewati Tenggat</span>
                    <span class="inv-stat-pill {{ ($totalPeminjamanOverdue ?? 0) > 0 ? 'pill-danger' : '' }}">
                        <i class="bi bi-exclamation-circle me-1"></i>Overdue
                    </span>
                </div>
                <div class="inv-summary-value {{ ($totalPeminjamanOverdue ?? 0) > 0 ? 'text-danger' : '' }}">{{ number_format($totalPeminjamanOverdue ?? 0) }}</div>
                <div class="inv-summary-sub {{ ($totalPeminjamanOverdue ?? 0) > 0 ? 'text-danger fw-semibold' : '' }}">Peminjaman melewati rencana pengembalian dan belum selesai</div>
            </div>
        </div>
    </div>

    <!-- Filter Barang Terpinjam (F.18 & F.19) -->
    <div class="panel mb-3">
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="borrowed">

            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Cari Peminjaman</label>
                <input type="text"
                       name="borrow_search"
                       value="{{ request('borrow_search') }}"
                       class="form-control form-control-sm"
                       placeholder="Kode, Peminjam, Unit, Barang...">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Lokasi</label>
                <select name="borrow_location" class="form-select form-select-sm">
                    <option value="">Semua Lokasi</option>
                    @foreach($locationList as $loc)
                        <option value="{{ $loc }}" {{ request('borrow_location') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Status Peminjaman</label>
                <select name="borrow_status" class="form-select form-select-sm">
                    <option value="Semua Status" {{ in_array(request('borrow_status', 'Semua Status'), ['Semua Status', 'active', 'all'], true) ? 'selected' : '' }}>Semua Status</option>
                    <option value="Sedang Dipinjam" {{ request('borrow_status') === 'Sedang Dipinjam' ? 'selected' : '' }}>Sedang Dipinjam</option>
                    <option value="Sudah Dikembalikan" {{ in_array(request('borrow_status'), ['Sudah Dikembalikan', 'returned'], true) ? 'selected' : '' }}>Sudah Dikembalikan</option>
                    <option value="Melewati Tenggat" {{ in_array(request('borrow_status'), ['Melewati Tenggat', 'overdue'], true) ? 'selected' : '' }}>Melewati Tenggat</option>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Rentang Tanggal Peminjaman</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="borrow_start" value="{{ request('borrow_start') }}" class="form-control" title="Dari Tanggal Peminjaman">
                    <input type="date" name="borrow_end" value="{{ request('borrow_end') }}" class="form-control" title="Sampai Tanggal Peminjaman">
                </div>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Rentang Rencana Kembali</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="expected_start" value="{{ request('expected_start') }}" class="form-control" title="Dari Rencana Kembali">
                    <input type="date" name="expected_end" value="{{ request('expected_end') }}" class="form-control" title="Sampai Rencana Kembali">
                </div>
            </div>

            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <a href="{{ route('inventory.index', ['tab' => 'borrowed']) }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel Barang Terpinjam (F.24 - F.27) -->
    <div class="panel p-0 overflow-hidden">
        <div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold" style="color: var(--inv-navy);"><i class="bi bi-arrow-left-right me-1 text-secondary"></i> Daftar Barang Terpinjam</span>
            <div class="d-flex align-items-center gap-2">
                @canExportPdf
                <a href="{{ route('inventory.export', array_merge(request()->query(), ['tab' => 'borrowed', 'format' => 'pdf'])) }}"
                   class="btn-inv-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Export PDF</span>
                </a>
                @endcanExportPdf
                <span class="badge bg-white text-secondary border font-monospace">{{ $borrowings->total() }} Data</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Kode Peminjaman</th>
                        <th>Nama Peminjam</th>
                        <th>Unit/Divisi/Departemen</th>
                        <th>Nama Barang</th>
                        <th>Lokasi</th>
                        <th class="text-center">Jumlah Dipinjam</th>
                        <th class="text-center">Jumlah Dikembalikan</th>
                        <th class="text-center">Masih Dipinjam</th>
                        <th>Tanggal Expired</th>
                        <th>Tanggal Peminjaman</th>
                        <th>Rencana Pengembalian</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($borrowings as $b)
                        @php
                            $satuanItem = $b->item->satuan ?? 'Box';
                            $dispStatus = $b->display_status;
                            $isOverdueRow = $b->is_overdue;
                            $bExpiryDetails = $b->expiry_details;
                        @endphp
                        <tr class="{{ $isOverdueRow ? 'row-overdue' : '' }}">
                            <td class="ps-3">
                                <span class="badge bg-light text-dark border font-monospace">{{ $b->borrow_code }}</span>
                            </td>
                            <td class="fw-bold text-dark">
                                {{ $b->borrower_name }}
                            </td>
                            <td>
                                <span class="text-secondary small fw-semibold"><i class="bi bi-building me-1"></i>{{ $b->department ?: '-' }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $b->item->nama_barang ?? $b->item->item_name ?? 'Barang Dihapus' }}</div>
                                <small class="text-muted font-monospace">{{ $b->item->kode_barang ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="{{ ($b->location ?? 'Warehouse') === 'Gudang HSSE' ? 'badge-loc-hsse' : 'badge-loc-warehouse' }}">
                                    {{ $b->location ?? 'Warehouse' }}
                                </span>
                            </td>
                            <td class="text-center fw-bold text-dark">
                                {{ $b->quantity_borrowed }} {{ $satuanItem }}
                            </td>
                            <td class="text-center fw-semibold text-success">
                                {{ $b->quantity_returned }} {{ $satuanItem }}
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $b->remaining_quantity > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-secondary-subtle text-secondary' }} fw-bold px-2 py-1">
                                    {{ $b->remaining_quantity }} {{ $satuanItem }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                @foreach($bExpiryDetails as $expDet)
                                    <div class="d-flex align-items-center gap-1 {{ !$loop->first ? 'mt-1 pt-1 border-top' : '' }}">
                                        <span class="small fw-semibold {{ $expDet['status'] === 'expired' ? 'text-danger' : 'text-dark' }}">
                                            {{ $expDet['formatted_date'] }}
                                        </span>
                                        @if(count($bExpiryDetails) > 1)
                                            <span class="badge bg-light text-dark border" style="font-size: 9.5px;">
                                                {{ $expDet['quantity_borrowed'] }} {{ $satuanItem }}
                                            </span>
                                        @endif
                                        @if($expDet['status'] !== 'unrecorded')
                                            <span class="badge {{ $expDet['badge_class'] }}" style="font-size: 9.5px;">
                                                {{ $expDet['status_label'] }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-nowrap small">
                                {{ $b->borrow_date ? $b->borrow_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="text-nowrap small">
                                @if($b->expected_return_date)
                                    <span class="{{ $isOverdueRow ? 'text-danger fw-bold' : 'text-dark' }}">
                                        {{ $b->expected_return_date->format('d/m/Y') }}
                                    </span>
                                    @if($isOverdueRow)
                                        <div class="text-danger fw-bold" style="font-size: 10px;">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Lewat Tenggat!
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($dispStatus === \App\Models\InventoryBorrowing::STATUS_RETURNED)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i>Sudah Dikembalikan
                                    </span>
                                @elseif($dispStatus === \App\Models\InventoryBorrowing::STATUS_OVERDUE)
                                    <span class="badge bg-danger text-white rounded-pill px-2 py-1">
                                        <i class="bi bi-exclamation-octagon-fill me-1"></i>Melewati Tenggat
                                    </span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-clock-fill me-1"></i>Sedang Dipinjam
                                    </span>
                                    @if($b->quantity_returned > 0 && $b->remaining_quantity > 0)
                                        <div class="text-warning-emphasis fw-semibold mt-1" style="font-size: 10px;">
                                            (Kembali Sebagian)
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-end pe-3 text-nowrap">
                                <div class="d-inline-flex gap-1">
                                    @canManageInventory
                                        @if($b->remaining_quantity > 0)
                                            <button type="button"
                                                    class="btn btn-success btn-sm py-1 px-2"
                                                    onclick="openReturnModal({{ json_encode([
                                                        'id' => $b->id,
                                                        'code' => $b->borrow_code,
                                                        'name' => $b->item->nama_barang ?? '',
                                                        'borrower' => $b->borrower_name,
                                                        'department' => $b->department ?? '-',
                                                        'location' => $b->location ?? 'Warehouse',
                                                        'satuan' => $satuanItem,
                                                        'borrowed' => $b->quantity_borrowed,
                                                        'returned' => $b->quantity_returned,
                                                        'remaining' => $b->remaining_quantity,
                                                        'expiry_details' => $bExpiryDetails,
                                                    ]) }})"
                                                    title="Catat Pengembalian Barang">
                                                <i class="bi bi-box-arrow-in-left me-1"></i> Kembalikan
                                            </button>

                                            <button type="button"
                                                    class="btn btn-outline-warning text-dark btn-sm py-1 px-2"
                                                    onclick="openEditDueDateModal({{ json_encode([
                                                        'id' => $b->id,
                                                        'code' => $b->borrow_code,
                                                        'name' => $b->item->nama_barang ?? '',
                                                        'borrower' => $b->borrower_name,
                                                        'expected_return_date' => $b->expected_return_date ? $b->expected_return_date->format('Y-m-d') : date('Y-m-d'),
                                                    ]) }})"
                                                    title="Edit / Perpanjang Rencana Pengembalian">
                                                <i class="bi bi-calendar-plus"></i>
                                            </button>
                                        @endif
                                    @endcanManageInventory

                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm py-1 px-2"
                                            onclick="openDetailBorrowingModal({{ json_encode([
                                                'code' => $b->borrow_code,
                                                'name' => $b->item->nama_barang ?? '',
                                                'item_code' => $b->item->kode_barang ?? '',
                                                'borrower' => $b->borrower_name,
                                                'department' => $b->department ?? '-',
                                                'location' => $b->location ?? 'Warehouse',
                                                'satuan' => $satuanItem,
                                                'borrowed' => $b->quantity_borrowed,
                                                'returned' => $b->quantity_returned,
                                                'remaining' => $b->remaining_quantity,
                                                'borrow_date' => $b->borrow_date ? $b->borrow_date->format('d/m/Y H:i') : '-',
                                                'expected_return_date' => $b->expected_return_date ? $b->expected_return_date->format('d/m/Y') : '-',
                                                'last_return_date' => $b->last_return_date ? $b->last_return_date->format('d/m/Y H:i') : '-',
                                                'status' => $dispStatus,
                                                'purpose' => $b->purpose ?? '-',
                                                'notes' => $b->notes ?? '-',
                                                'recorded_by' => $b->recorded_by ?? '-',
                                                'timeline' => $b->chronological_timeline,
                                                'due_history' => $b->due_date_history ?? [],
                                                'expiry_details' => $bExpiryDetails,
                                            ]) }})"
                                            title="Lihat Detail & Riwayat Kronologis">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-5 text-muted">
                                <i class="bi bi-arrow-left-right fs-2 d-block mb-2 text-warning"></i>
                                Tidak ada data peminjaman aktif yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $borrowings->links('pagination::bootstrap-5') }}
        </div>
    </div>

@elseif($activeTab === 'borrow_history')
    <!-- TAB 4: HISTORY PEMINJAMAN (G.28 - G.31) -->
    <!-- Filter History Peminjaman (G.30) -->
    <div class="panel mb-3">
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="borrow_history">

            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Kode / Nama Peminjam</label>
                <input type="text"
                       name="history_search"
                       value="{{ request('history_search') }}"
                       class="form-control form-control-sm"
                       placeholder="Kode atau peminjam...">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Unit/Divisi/Departemen</label>
                <input type="text"
                       name="history_department"
                       value="{{ request('history_department') }}"
                       list="deptDatalist"
                       class="form-control form-control-sm"
                       placeholder="Semua Unit/Divisi...">
                <datalist id="deptDatalist">
                    @foreach($departmentList as $dept)
                        <option value="{{ $dept }}">
                    @endforeach
                </datalist>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Nama Barang & Lokasi</label>
                <div class="input-group input-group-sm">
                    <select name="history_item_id" class="form-select">
                        <option value="">Semua Barang</option>
                        @foreach($inventories as $inv)
                            <option value="{{ $inv->id }}" {{ (string) request('history_item_id') === (string) $inv->id ? 'selected' : '' }}>
                                [{{ $inv->kode_barang }}] {{ $inv->nama_barang }}
                            </option>
                        @endforeach
                    </select>
                    <select name="history_location" class="form-select">
                        <option value="">Semua Lokasi</option>
                        @foreach($locationList as $loc)
                            <option value="{{ $loc }}" {{ request('history_location') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Rentang Tgl Peminjaman</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="history_borrow_start" value="{{ request('history_borrow_start') }}" class="form-control">
                    <input type="date" name="history_borrow_end" value="{{ request('history_borrow_end') }}" class="form-control">
                </div>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold mb-1 text-secondary">Rentang Tgl Pengembalian</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="history_return_start" value="{{ request('history_return_start') }}" class="form-control">
                    <input type="date" name="history_return_end" value="{{ request('history_return_end') }}" class="form-control">
                </div>
            </div>

            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <a href="{{ route('inventory.index', ['tab' => 'borrow_history']) }}" class="btn btn-outline-secondary btn-sm" title="Reset">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel History Peminjaman Selesai (G.29) -->
    <div class="panel p-0 overflow-hidden">
        <div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="fw-bold" style="color: var(--inv-navy);"><i class="bi bi-journal-check me-1 text-secondary"></i> History Peminjaman Selesai</span>
                <small class="text-muted ms-2">Daftar transaksi peminjaman yang seluruh barangnya telah dikembalikan (Masih Dipinjam = 0)</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                @canExportPdf
                <a href="{{ route('inventory.export', array_merge(request()->query(), ['tab' => 'borrow_history', 'format' => 'pdf'])) }}"
                   class="btn-inv-secondary" style="height: 32px; padding: 0 12px; font-size: 12px;">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Export PDF</span>
                </a>
                @endcanExportPdf
                <span class="badge bg-white text-secondary border font-monospace">{{ $borrowHistories->total() }} Selesai</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th>Kode Peminjaman</th>
                        <th>Nama Peminjam</th>
                        <th>Unit/Divisi/Departemen</th>
                        <th>Nama Barang</th>
                        <th>Lokasi Barang</th>
                        <th class="text-center">Jumlah Dipinjam</th>
                        <th class="text-center">Jumlah Dikembalikan</th>
                        <th>Tanggal Expired</th>
                        <th>Tanggal Peminjaman</th>
                        <th>Rencana Pengembalian</th>
                        <th>Tanggal Pengembalian Terakhir</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($borrowHistories as $idx => $bh)
                        @php
                            $satuanBh = $bh->item->satuan ?? 'Box';
                            $lastReturn = $bh->last_return_date;
                            $bhExpiryDetails = $bh->expiry_details;
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted">{{ $borrowHistories->firstItem() + $idx }}</td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $bh->borrow_code }}</span>
                            </td>
                            <td class="fw-bold text-dark">{{ $bh->borrower_name }}</td>
                            <td class="small text-secondary fw-semibold">{{ $bh->department ?: '-' }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $bh->item->nama_barang ?? $bh->item->item_name ?? 'Barang Dihapus' }}</div>
                                <small class="text-muted font-monospace">{{ $bh->item->kode_barang ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="{{ ($bh->location ?? 'Warehouse') === 'Gudang HSSE' ? 'badge-loc-hsse' : 'badge-loc-warehouse' }}">
                                    {{ $bh->location ?? 'Warehouse' }}
                                </span>
                            </td>
                            <td class="text-center fw-bold">{{ $bh->quantity_borrowed }} {{ $satuanBh }}</td>
                            <td class="text-center fw-bold text-success">{{ $bh->quantity_returned }} {{ $satuanBh }}</td>
                            <td class="text-nowrap">
                                @foreach($bhExpiryDetails as $expDet)
                                    <div class="d-flex align-items-center gap-1 {{ !$loop->first ? 'mt-1 pt-1 border-top' : '' }}">
                                        <span class="small fw-semibold {{ $expDet['status'] === 'expired' ? 'text-danger' : 'text-dark' }}">
                                            {{ $expDet['formatted_date'] }}
                                        </span>
                                        @if(count($bhExpiryDetails) > 1)
                                            <span class="badge bg-light text-dark border" style="font-size: 9.5px;">
                                                {{ $expDet['quantity_borrowed'] }} {{ $satuanBh }}
                                            </span>
                                        @endif
                                        @if($expDet['status'] !== 'unrecorded')
                                            <span class="badge {{ $expDet['badge_class'] }}" style="font-size: 9.5px;">
                                                {{ $expDet['status_label'] }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-nowrap small">{{ $bh->borrow_date ? $bh->borrow_date->format('d/m/Y') : '-' }}</td>
                            <td class="text-nowrap small">{{ $bh->expected_return_date ? $bh->expected_return_date->format('d/m/Y') : '-' }}</td>
                            <td class="text-nowrap small fw-semibold text-success">
                                {{ $lastReturn ? $lastReturn->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                    <i class="bi bi-check-circle-fill me-1"></i>Sudah Dikembalikan
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="openDetailBorrowingModal({{ json_encode([
                                            'code' => $bh->borrow_code,
                                            'name' => $bh->item->nama_barang ?? '',
                                            'item_code' => $bh->item->kode_barang ?? '',
                                            'borrower' => $bh->borrower_name,
                                            'department' => $bh->department ?? '-',
                                            'location' => $bh->location ?? 'Warehouse',
                                            'satuan' => $satuanBh,
                                            'borrowed' => $bh->quantity_borrowed,
                                            'returned' => $bh->quantity_returned,
                                            'remaining' => 0,
                                            'borrow_date' => $bh->borrow_date ? $bh->borrow_date->format('d/m/Y H:i') : '-',
                                            'expected_return_date' => $bh->expected_return_date ? $bh->expected_return_date->format('d/m/Y') : '-',
                                            'last_return_date' => $lastReturn ? $lastReturn->format('d/m/Y H:i') : '-',
                                            'status' => 'Sudah Dikembalikan',
                                            'purpose' => $bh->purpose ?? '-',
                                            'notes' => $bh->notes ?? '-',
                                            'recorded_by' => $bh->recorded_by ?? '-',
                                            'timeline' => $bh->chronological_timeline,
                                            'due_history' => $bh->due_date_history ?? [],
                                            'expiry_details' => $bhExpiryDetails,
                                        ]) }})"
                                        title="Lihat Detail Riwayat Kronologis">
                                    <i class="bi bi-eye me-1"></i> Lihat Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-check fs-2 d-block mb-2 text-success"></i>
                                Belum ada riwayat peminjaman yang selesai dikembalikan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $borrowHistories->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif

<!-- ==================== DAFTAR MODAL INVENTORY ==================== -->

@adminManagement
<!-- 1. MODAL TAMBAH MASTER BARANG (A.1 - A.4) -->
<div class="modal fade" id="modalTambahBarang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('inventory.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-box-seam-fill text-success me-2"></i>Tambah Master Barang Baru
                    </h6>
                    <small class="text-muted">Kode barang dibuat otomatis dan stok dipisahkan antara Warehouse & Gudang HSSE</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body scrollable-modal-body">
                <div class="row g-3">
                    <!-- 1. Kode Barang (Otomatis & Unik) -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">1. Kode Barang <span class="badge bg-secondary-subtle text-secondary ms-1">Otomatis & Unik</span></label>
                        <input type="text"
                               name="barang_id"
                               value="{{ $nextItemCode }}"
                               class="form-control form-control-sm font-monospace bg-light"
                               readonly>
                        <small class="text-muted" style="font-size: 11px;">Dibuat otomatis oleh sistem.</small>
                    </div>

                    <!-- 2. Kategori -->
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">2. Kategori <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="openMasterModal('kategori', 'modalTambahBarang')" style="font-size: 11px;">
                                + Kelola Kategori
                            </button>
                        </div>
                        <select name="kategori" id="addMasterKategoriSelect" class="form-select form-select-sm" required>
                            @foreach($kategoriList as $kat)
                                <option value="{{ $kat }}">{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Nama Barang -->
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">3. Nama Barang <span class="text-danger">*</span></label>
                        <input type="text"
                               name="nama_barang"
                               id="addNamaBarangInput"
                               class="form-control form-control-sm"
                               placeholder="Contoh: Safety Helmet MSA V-Gard Putih"
                               oninput="checkAddExistingMasterItem()"
                               required>
                        <div id="addDuplicateItemWarning" class="alert alert-info py-1 px-2 mt-1 mb-0 small d-none" style="font-size: 11px;"></div>
                    </div>

                    <!-- 4. Satuan (Pilihan Dinamis Master Satuan) -->
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">4. Satuan <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="openMasterModal('satuan', 'modalTambahBarang')" style="font-size: 11px;">
                                + Kelola Satuan
                            </button>
                        </div>
                        <select name="satuan" id="addMasterSatuanSelect" class="form-select form-select-sm" required>
                            @foreach($satuanList as $sat)
                                <option value="{{ $sat }}" {{ $sat === 'Box' ? 'selected' : '' }}>{{ $sat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 5. Jumlah Stok Warehouse -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-primary">5. Jumlah Stok Warehouse <span class="text-danger">*</span></label>
                        <input type="number"
                               name="stock_warehouse"
                               id="addStockWarehouse"
                               class="form-control form-control-sm border-primary"
                               value="0"
                               min="0"
                               required
                               oninput="updateAddTotalStockPreview()">
                    </div>

                    <!-- 6. Jumlah Stok Gudang HSSE -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-success">6. Jumlah Stok Gudang HSSE <span class="text-danger">*</span></label>
                        <input type="number"
                               name="stock_hsse"
                               id="addStockHsse"
                               class="form-control form-control-sm border-success"
                               value="0"
                               min="0"
                               required
                               oninput="updateAddTotalStockPreview()">
                    </div>

                    <!-- Preview Akumulasi Total Stok -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-dark">Total Stok Tersedia</label>
                        <input type="text"
                               id="addTotalStockPreview"
                               class="form-control form-control-sm bg-light fw-bold text-dark"
                               value="0"
                               readonly>
                        <small class="text-muted" style="font-size: 10.5px;">Warehouse + Gudang HSSE</small>
                    </div>

                    <!-- 7. Keterangan (Wajib Diisi) -->
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">7. Keterangan <span class="text-danger">* (Wajib Diisi)</span></label>
                        <textarea name="keterangan"
                                  class="form-control form-control-sm"
                                  rows="2"
                                  placeholder="Spesifikasi barang, standar keselamatan, atau peruntukan..."
                                  required></textarea>
                    </div>

                    <!-- 8. Data Expired / Tanggal Kedaluwarsa -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">8. Tanggal Kedaluwarsa (Expired)</label>
                        <input type="date" name="expired_date" class="form-control form-control-sm">
                        <small class="text-muted" style="font-size: 11px;">Isi jika barang memiliki masa kedaluwarsa.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success btn-sm px-4">
                    <i class="bi bi-check-lg me-1"></i> Simpan Master Barang
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 2. MODAL EDIT MASTER BARANG (A.1 - A.4) -->
<div class="modal fade" id="modalEditMasterGlobal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="formEditMaster" method="POST" action="" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Master Barang
                    </h6>
                    <small class="text-muted">Perbarui informasi barang dan stok masing-masing lokasi penyimpanan</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body scrollable-modal-body">
                <div class="row g-3">
                    <!-- 1. Kode Barang -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">1. Kode Barang</label>
                        <input type="text"
                               name="barang_id"
                               id="editBarangId"
                               class="form-control form-control-sm font-monospace bg-light"
                               readonly>
                    </div>

                    <!-- 2. Kategori -->
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">2. Kategori <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="openMasterModal('kategori', 'modalEditMasterGlobal')" style="font-size: 11px;">
                                + Kelola Kategori
                            </button>
                        </div>
                        <select name="kategori" id="editKategori" class="form-select form-select-sm" required>
                            @foreach($kategoriList as $kat)
                                <option value="{{ $kat }}">{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Nama Barang -->
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">3. Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_barang" id="editNamaBarang" class="form-control form-control-sm" required>
                    </div>

                    <!-- 4. Satuan -->
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">4. Satuan <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="openMasterModal('satuan', 'modalEditMasterGlobal')" style="font-size: 11px;">
                                + Kelola Satuan
                            </button>
                        </div>
                        <select name="satuan" id="editSatuan" class="form-select form-select-sm" required>
                            @foreach($satuanList as $sat)
                                <option value="{{ $sat }}">{{ $sat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 5. Jumlah Stok Warehouse -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-primary">5. Jumlah Stok Warehouse <span class="text-danger">*</span></label>
                        <input type="number"
                               name="stock_warehouse"
                               id="editStockWarehouse"
                               class="form-control form-control-sm border-primary"
                               min="0"
                               required
                               oninput="updateEditTotalStockPreview()">
                    </div>

                    <!-- 6. Jumlah Stok Gudang HSSE -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-success">6. Jumlah Stok Gudang HSSE <span class="text-danger">*</span></label>
                        <input type="number"
                               name="stock_hsse"
                               id="editStockHsse"
                               class="form-control form-control-sm border-success"
                               min="0"
                               required
                               oninput="updateEditTotalStockPreview()">
                    </div>

                    <!-- Total Stok Akumulasi -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-dark">Total Stok Tersedia</label>
                        <input type="text"
                               id="editTotalStockPreview"
                               class="form-control form-control-sm bg-light fw-bold text-dark"
                               readonly>
                    </div>

                    <!-- 7. Keterangan (Wajib) -->
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">7. Keterangan <span class="text-danger">* (Wajib Diisi)</span></label>
                        <textarea name="keterangan"
                                  id="editKeterangan"
                                  class="form-control form-control-sm"
                                  rows="2"
                                  required></textarea>
                    </div>

                    <!-- 8. Data Expired -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">8. Tanggal Kedaluwarsa (Expired)</label>
                        <input type="date" name="expired_date" id="editExpiredDate" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL KELOLA MASTER (KATEGORI & SATUAN BARANG) -->
<div class="modal fade" id="modalKelolaMaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 680px;">
        <div class="modal-content shadow border-0">
            <div class="modal-header py-2.5 px-3 bg-light border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px; height:34px; background:#e0f2fe; color:#0369a1; font-size:16px;">
                        <i class="bi bi-tags-fill"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0">Kelola Master Inventory</h6>
                        <small class="text-muted" style="font-size: 11.5px;">Pengelolaan data master Kategori Barang dan Satuan Barang</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Notifikasi Operasi Master -->
                <div id="masterModalFeedback" class="alert py-2 px-3 small d-none mb-3" role="alert"></div>

                <!-- Tab Navigasi Kelola Master -->
                <ul class="nav nav-tabs nav-fill mb-3" id="kelolaMasterTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold small py-2"
                                id="tabMasterKategori"
                                data-bs-toggle="tab"
                                data-bs-target="#paneMasterKategori"
                                type="button"
                                role="tab"
                                aria-controls="paneMasterKategori"
                                aria-selected="true">
                            <i class="bi bi-folder2-open me-1"></i>Kategori
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small py-2"
                                id="tabMasterSatuan"
                                data-bs-toggle="tab"
                                data-bs-target="#paneMasterSatuan"
                                type="button"
                                role="tab"
                                aria-controls="paneMasterSatuan"
                                aria-selected="false">
                            <i class="bi bi-rulers me-1"></i>Satuan
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="kelolaMasterTabContent">
                    <!-- TAB 1: KATEGORI -->
                    <div class="tab-pane fade show active" id="paneMasterKategori" role="tabpanel" aria-labelledby="tabMasterKategori">
                        <!-- Form Tambah Kategori Cepat -->
                        <div class="p-2.5 bg-light rounded border mb-3">
                            <form id="formAddKategori" onsubmit="submitAddKategori(event)">
                                <label class="form-label small fw-bold mb-1"><i class="bi bi-plus-circle me-1 text-primary"></i>Tambah Kategori Baru</label>
                                <div class="input-group input-group-sm">
                                    <input type="text"
                                           id="newKategoriNameInput"
                                           class="form-control"
                                           placeholder="Contoh: Alat Pelindung Pernapasan, Peralatan Listrik..."
                                           maxlength="100"
                                           required>
                                    <button type="submit" class="btn btn-primary" id="btnSubmitAddKategori">
                                        <i class="bi bi-plus-lg me-1"></i>Tambah
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                    * Kategori duplikat (huruf besar/kecil atau spasi ganda) otomatis dicegah oleh sistem.
                                </small>
                            </form>
                        </div>

                        <!-- Filter Pencarian Kategori -->
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                                <input type="text"
                                       id="searchKategoriInput"
                                       class="form-control"
                                       placeholder="Cari nama kategori..."
                                       oninput="renderKategoriTable()">
                            </div>
                            <span id="kategoriCountBadge" class="badge bg-secondary-subtle text-secondary font-monospace flex-shrink-0" style="font-size: 11px;">
                                Total: 0 Kategori
                            </span>
                        </div>

                        <!-- Tabel Master Kategori -->
                        <div class="table-responsive border rounded" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 8%;" class="text-center">No</th>
                                        <th style="width: 42%;">Nama Kategori</th>
                                        <th style="width: 16%;" class="text-center">Status</th>
                                        <th style="width: 16%;" class="text-center">Pemakaian</th>
                                        <th style="width: 18%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="kategoriTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">
                                            <span class="spinner-border spinner-border-sm me-1"></span> Memuat data kategori...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: SATUAN (Menggunakan fitur Kelola Satuan yang sudah tersedia) -->
                    <div class="tab-pane fade" id="paneMasterSatuan" role="tabpanel" aria-labelledby="tabMasterSatuan">
                        <!-- Form Tambah Satuan Cepat -->
                        <div class="p-2.5 bg-light rounded border mb-3">
                            <form id="formAddSatuan" onsubmit="submitAddSatuan(event)">
                                <label class="form-label small fw-bold mb-1"><i class="bi bi-plus-circle me-1 text-primary"></i>Tambah Satuan Baru</label>
                                <div class="input-group input-group-sm">
                                    <input type="text"
                                           id="newSatuanNameInput"
                                           class="form-control"
                                           placeholder="Contoh: Roll, Set, Pack, Pasang, Zak..."
                                           maxlength="50"
                                           required>
                                    <button type="submit" class="btn btn-primary" id="btnSubmitAddSatuan">
                                        <i class="bi bi-plus-lg me-1"></i>Tambah
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                    * Satuan duplikat (huruf besar/kecil atau spasi ganda) otomatis dicegah oleh sistem.
                                </small>
                            </form>
                        </div>

                        <!-- Filter Pencarian Satuan -->
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                                <input type="text"
                                       id="searchSatuanInput"
                                       class="form-control"
                                       placeholder="Cari nama satuan..."
                                       oninput="renderSatuanTable()">
                            </div>
                            <span id="satuanCountBadge" class="badge bg-secondary-subtle text-secondary font-monospace flex-shrink-0" style="font-size: 11px;">
                                Total: 0 Satuan
                            </span>
                        </div>

                        <!-- Tabel Master Satuan -->
                        <div class="table-responsive border rounded" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 8%;" class="text-center">No</th>
                                        <th style="width: 38%;">Nama Satuan</th>
                                        <th style="width: 18%;" class="text-center">Status</th>
                                        <th style="width: 18%;" class="text-center">Pemakaian</th>
                                        <th style="width: 18%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="satuanTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">
                                            <span class="spinner-border spinner-border-sm me-1"></span> Memuat data satuan...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-end">
                <button type="button" id="btnCloseMasterModal" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CUSTOM EDIT NAMA KATEGORI / SATUAN (KELOLA MASTER) -->
<div class="modal fade" id="modalMasterEditDialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 400px;">
        <form id="formMasterEditDialog" class="modal-content" onsubmit="submitMasterEditDialog(event)" novalidate>
            <div class="modal-header py-2.5 px-3 bg-light border-bottom">
                <h6 class="modal-title fw-bold text-dark mb-0" id="masterEditDialogTitle">
                    <i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Master
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label for="masterEditInput" id="masterEditInputLabel" class="form-label small fw-bold mb-1">Nama Baru <span class="text-danger">*</span></label>
                    <input type="text"
                           id="masterEditInput"
                           class="form-control form-control-sm"
                           maxlength="100"
                           required>
                    <div id="masterEditError" class="text-danger small mt-1 d-none" style="font-size: 11.5px;"></div>
                </div>
                <small class="text-muted d-block" style="font-size: 11px;">
                    Perubahan nama akan otomatis diperbarui pada data terkait.
                </small>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="btnMasterEditSave" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-check-lg me-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CUSTOM KONFIRMASI, PERINGATAN & NOTIFIKASI (KELOLA MASTER) -->
<div class="modal fade" id="modalMasterActionDialog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 400px;">
        <div class="modal-content text-center p-3">
            <div class="modal-body pb-2">
                <div id="masterActionIcon" class="text-primary mb-2">
                    <i class="bi bi-info-circle fs-1"></i>
                </div>
                <h6 id="masterActionTitle" class="fw-bold text-dark mb-1">Konfirmasi</h6>
                <div id="masterActionBadgeWrap" class="p-2 bg-light rounded border my-2 d-none">
                    <div id="masterActionBadge" class="fw-bold text-dark small"></div>
                </div>
                <p id="masterActionMessage" class="text-muted small mb-2"></p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" id="masterActionCancelBtn" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="masterActionConfirmBtn" class="btn btn-primary btn-sm px-3">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- 3. MODAL CATAT TRANSAKSI MUTASI BARANG — MULTI-BARANG SEKALIGUS (C.7 - C.11) -->
<div class="modal fade" id="modalTransaksiTerpadu" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-md-down">
        <form action="{{ route('inventory.transactions.store') }}" method="POST" id="formMultiTransaksi" class="modal-content" novalidate onsubmit="return handleTrxFormSubmit(event)">
            @csrf
            <input type="hidden" name="_strict_trx_form" value="1">
            <div class="modal-header">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-arrow-left-right text-primary me-2"></i>Catat Transaksi Mutasi Barang
                    </h6>
                    <small class="text-muted">Fokus pencatatan mutasi stok masuk/keluar per lokasi (Warehouse / Gudang HSSE)</small>
                </div>
                <button type="button" class="btn-close" onclick="attemptCloseTrxModal()" aria-label="Tutup"></button>
            </div>
            <div class="modal-body scrollable-modal-body">
                <!-- Kotak Pesan Validasi Form Transaksi -->
                <div id="trxValidationAlert" class="alert alert-danger py-2 px-3 small d-none mb-3" role="alert">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i>Mohon lengkapi data wajib berikut sebelum menyimpan:</div>
                    <ul id="trxValidationList" class="mb-0 ps-3"></ul>
                </div>

                <!-- Header Form Transaksi -->
                <div class="row g-3 mb-3 pb-3 border-bottom">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">Jenis Transaksi <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" name="jenis_transaksi" id="trxMasuk" value="masuk" class="d-none trx-radio" checked onchange="markTrxDirty(); revalidateAllTrxRows()">
                                <label for="trxMasuk" class="trx-type-btn trx-type-masuk w-100 d-block py-2">
                                    <i class="bi bi-box-arrow-in-down me-1"></i> Barang Masuk
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" name="jenis_transaksi" id="trxKeluar" value="keluar" class="d-none trx-radio" onchange="markTrxDirty(); revalidateAllTrxRows()">
                                <label for="trxKeluar" class="trx-type-btn trx-type-keluar w-100 d-block py-2">
                                    <i class="bi bi-box-arrow-up me-1"></i> Barang Keluar
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Tanggal Transaksi <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_transaksi" id="trxTanggalInput" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required onchange="markTrxDirty()">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small fw-bold mb-1">Petugas Pencatat <span class="text-danger">*</span></label>
                        <input type="text"
                               name="petugas"
                               id="trxPetugasInput"
                               value="{{ Auth::user()->name ?? 'Administrator HSSE' }}"
                               class="form-control form-control-sm"
                               placeholder="Wajib diisi: Nama petugas pencatat / PIC"
                               required
                               oninput="markTrxDirty()">
                    </div>
                </div>

                <!-- Header Daftar Item Barang & Tombol Tambah Baris (Pencarian Terintegrasi di Kolom Pilih Barang) -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <span class="fw-bold small text-dark"><i class="bi bi-list-check me-1 text-primary"></i>Daftar Item Barang:</span>
                        <small class="text-muted d-block" style="font-size: 11px;">Ketik nama atau kode barang langsung pada kolom Pilih Barang untuk memunculkan pilihan.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTrxItemRow()">
                        <i class="bi bi-plus-circle me-1"></i> + Tambah Baris Barang
                    </button>
                </div>

                <!-- Tabel Dinamis Multi-Item -->
                <div class="table-responsive trx-table-responsive border rounded mb-3">
                    <table class="table table-sm align-middle mb-0" id="trxItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 38%;">Pilih Barang <span class="text-danger">*</span></th>
                                <th style="width: 16%;">Lokasi <span class="text-danger">*</span></th>
                                <th style="width: 13%;">Satuan <span class="text-danger">*</span></th>
                                <th style="width: 13%;">Kuantitas <span class="text-danger">*</span></th>
                                <th style="width: 15%;">Tanggal Expired <small class="text-muted fw-normal">(Opsional)</small></th>
                                <th style="width: 5%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="trxItemsContainer">
                            <!-- Baris Pertama Default -->
                            <tr class="trx-item-row" data-row-index="0">
                                <td>
                                    <div class="position-relative trx-autocomplete-wrapper">
                                        <input type="hidden" name="items[0][inventory_id]" class="trx-item-id-input" value="" required>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-muted px-2.5"><i class="bi bi-search"></i></span>
                                            <input type="text"
                                                   class="form-control form-control-sm trx-item-search-input"
                                                   placeholder="Ketik nama atau kode barang (cth: mask)..."
                                                   autocomplete="off"
                                                   onfocus="onTrxItemSearchFocus(this)"
                                                   oninput="onTrxItemSearchInput(this)"
                                                   onkeydown="onTrxItemSearchKeydown(event, this)">
                                            <button class="btn btn-outline-secondary btn-sm trx-item-clear-btn d-none"
                                                    type="button"
                                                    title="Kosongkan pilihan"
                                                    onclick="clearTrxItemSelection(this)">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div class="trx-autocomplete-dropdown list-group shadow border rounded d-none">
                                        </div>
                                        <div class="trx-row-stock-info small mt-1.5" style="font-size: 11.5px;"></div>
                                    </div>
                                </td>
                                <td>
                                    <select name="items[0][location]" class="form-select form-select-sm trx-location-select" required onchange="markTrxDirty(); validateTrxRow(this.closest('tr'))">
                                        @foreach($locationList as $loc)
                                            <option value="{{ $loc }}">{{ $loc }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="items[0][satuan]" class="form-select form-select-sm trx-satuan-select" required onchange="markTrxDirty()">
                                        @foreach($satuanList as $sat)
                                            <option value="{{ $sat }}">{{ $sat }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number"
                                           name="items[0][jumlah]"
                                           class="form-control form-control-sm trx-qty-input"
                                           min="1"
                                           value="1"
                                           required
                                           oninput="markTrxDirty(); validateTrxRow(this.closest('tr'))">
                                    <div class="trx-row-warning text-danger small mt-1 d-none" style="font-size: 10.5px;"></div>
                                </td>
                                <td>
                                    <input type="date" name="items[0][expired_date]" class="form-control form-control-sm trx-expired-input" onchange="markTrxDirty()">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeTrxItemRow(this)" title="Hapus Baris">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Keterangan Opsional -->
                <div>
                    <label class="form-label small fw-bold mb-1">Keterangan <span class="text-muted fw-normal">(Opsional)</span></label>
                    <textarea name="keterangan" id="trxKeteranganInput" class="form-control form-control-sm" rows="2" placeholder="Catatan tambahan mutasi barang (opsional)..." oninput="markTrxDirty()"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm px-3" onclick="attemptCloseTrxModal()">Batal</button>
                <button type="submit" id="btnSubmitTrx" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-save me-1"></i> Simpan Transaksi
                </button>
            </div>
        </form>
    </div>
</div>
@endadminManagement

<!-- 4. MODAL CATAT PEMINJAMAN BARANG (E.16, E.17, E.21) -->
<div class="modal fade" id="modalPinjamBarang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="formPinjamBarang" action="{{ route('inventory.borrowings.store') }}" method="POST" onsubmit="return handleBorrowSubmit(event)" class="modal-content">
            @csrf
            <input type="hidden" name="_strict_borrow_form" value="1">
            <div class="modal-header">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-box-arrow-right text-warning me-2"></i>Catat Peminjaman Barang
                    </h6>
                    <small class="text-muted">Peminjaman akan mengurangi stok tersedia pada lokasi yang dipilih hingga barang dikembalikan</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body scrollable-modal-body">
                <div class="row g-3">
                    <!-- Kode Peminjaman Otomatis & Tanggal Peminjaman -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Kode Peminjaman <span class="badge bg-secondary-subtle text-secondary ms-1">Otomatis</span></label>
                        <input type="text" class="form-control form-control-sm font-monospace bg-light" value="{{ $nextBorrowCode }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tanggal Peminjaman <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="borrow_date" class="form-control form-control-sm" value="{{ date('Y-m-d\TH:i') }}" required>
                    </div>

                    <!-- Bagian Data Peminjam & Unit/Divisi/Departemen (Digabung dalam Satu Kelompok Form, Dua Kolom Berdampingan) -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded border">
                            <div class="fw-bold small text-secondary text-uppercase mb-2">
                                <i class="bi bi-person-vcard me-1"></i>Data Peminjam & Unit/Divisi/Departemen
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-1">Data Peminjam (Nama Peminjam) <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="borrower_name"
                                           class="form-control form-control-sm bg-white"
                                           placeholder="Masukkan nama lengkap peminjam"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-1">Unit/Divisi/Departemen <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="department"
                                           list="deptDatalist"
                                           class="form-control form-control-sm bg-white"
                                           placeholder="Contoh: Operasi / Pemeliharaan / HSSE"
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Pencarian Langsung Barang & Lokasi Penyimpanan -->
                    <div class="col-md-8 position-relative">
                        <label class="form-label small fw-bold mb-1">Pencarian & Pilih Barang <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm mb-1">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                            <input type="text"
                                   id="borrowItemSearchInput"
                                   class="form-control form-control-sm"
                                   autocomplete="off"
                                   placeholder="Ketik nama atau sebagian kata barang untuk menampilkan hasil..."
                                   onfocus="filterBorrowItemOptions(this.value, true)"
                                   oninput="filterBorrowItemOptions(this.value, true)">
                        </div>
                        <!-- Hasil Pencarian Langsung (Live Search Results) -->
                        <div id="borrowLiveSearchResults"
                             class="list-group shadow-sm border rounded d-none position-absolute bg-white"
                             style="z-index: 1060; max-height: 210px; overflow-y: auto; left: 0.5rem; right: 0.5rem;">
                        </div>
                        <select name="inventory_item_id"
                                id="borrowItemSelect"
                                class="form-select form-select-sm"
                                required
                                onchange="handleBorrowItemSelect()">
                            <option value="">-- Pilih Barang --</option>
                            @foreach($inventories as $inv)
                                @if(!$inv->is_consumable)
                                    <option value="{{ $inv->id }}"
                                            data-code="{{ $inv->kode_barang }}"
                                            data-name="{{ $inv->nama_barang }}"
                                            data-warehouse="{{ (int) $inv->stock_warehouse }}"
                                            data-hsse="{{ (int) $inv->stock_hsse }}"
                                            data-available="{{ (int) $inv->available_stock }}"
                                            data-satuan="{{ $inv->satuan }}">
                                        [{{ $inv->kode_barang }}] {{ $inv->nama_barang }} (Warehouse: {{ (int) $inv->stock_warehouse }} {{ $inv->satuan }} | Gudang HSSE: {{ (int) $inv->stock_hsse }} {{ $inv->satuan }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <div id="borrowStockAlert" class="small mt-1"></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">Lokasi Penyimpanan <span class="text-danger">*</span></label>
                        <select name="location"
                                id="borrowLocationSelect"
                                class="form-select form-select-sm"
                                required
                                onchange="onBorrowLocationChange()">
                            @foreach($locationList as $loc)
                                <option value="{{ $loc }}">{{ $loc }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="location" id="borrowLocationHidden" disabled>
                        <small id="borrowLocationNote" class="text-muted d-block mt-1" style="font-size: 11px;">Pilih lokasi pengambilan barang.</small>
                    </div>

                    <!-- Pemilihan Kelompok Tanggal Expired & Jumlah per Kelompok (Mendukung Tambah Baris Multi-Batch) -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded border">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div>
                                    <span class="fw-bold small text-dark text-uppercase">
                                        <i class="bi bi-calendar2-check me-1 text-primary"></i>Pilih Kelompok Tanggal Expired &amp; Jumlah Pinjam <span class="text-danger">*</span>
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 11px;">
                                        Menampilkan kelompok tanggal expired yang tersedia pada lokasi yang dipilih. Gunakan <strong>+ Tambah Baris Kelompok Expired</strong> jika meminjam dari dua kelompok expired berbeda.
                                    </small>
                                </div>
                                <button type="button"
                                        id="btnAddBorrowBatchRow"
                                        class="btn btn-sm btn-outline-primary py-1 px-2"
                                        onclick="addBorrowBatchRow()">
                                    <i class="bi bi-plus-circle me-1"></i>+ Tambah Baris Kelompok Expired
                                </button>
                            </div>

                            <div class="table-responsive bg-white border rounded">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light small">
                                        <tr>
                                            <th style="width: 46%;">Tanggal Expired (Kelompok Stok) <span class="text-danger">*</span></th>
                                            <th style="width: 22%;" class="text-center">Stok Kelompok</th>
                                            <th style="width: 25%;">Jumlah &amp; Satuan <span class="text-danger">*</span></th>
                                            <th style="width: 7%;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="borrowBatchesContainer">
                                        <tr class="borrow-batch-row" data-batch-index="0">
                                            <td>
                                                <select name="borrow_batches[0][expiry_key]"
                                                        class="form-select form-select-sm borrow-batch-select"
                                                        required
                                                        onchange="onBorrowBatchSelectChange(this)">
                                                    <option value="">-- Pilih Barang &amp; Lokasi Terlebih Dahulu --</option>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-secondary border borrow-batch-avail-badge">-</span>
                                            </td>
                                            <td>
                                                <div class="row g-1 align-items-center">
                                                    <div class="col-7">
                                                        <input type="number"
                                                               name="borrow_batches[0][quantity]"
                                                               class="form-control form-control-sm borrow-batch-qty"
                                                               min="1"
                                                               value="1"
                                                               required
                                                               oninput="onBorrowBatchQtyInput(this)">
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="text"
                                                               class="form-control form-control-sm bg-light text-secondary fw-semibold borrow-batch-unit text-center px-1"
                                                               value="Satuan"
                                                               readonly>
                                                    </div>
                                                </div>
                                                <div class="borrow-batch-row-warn text-danger small mt-1 d-none" style="font-size: 10.5px;"></div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger py-0 px-2"
                                                        onclick="removeBorrowBatchRow(this)"
                                                        title="Hapus Baris Kelompok Expired">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Total Jumlah Dipinjam dan Satuan Berdampingan dalam Satu Baris -->
                    <div class="col-md-6">
                        <div class="row g-2 align-items-end">
                            <div class="col-7">
                                <label class="form-label small fw-bold mb-1">Total Jumlah Dipinjam <span class="text-danger">*</span></label>
                                <input type="number"
                                       name="quantity_borrowed"
                                       id="borrowQtyInput"
                                       class="form-control form-control-sm bg-light fw-bold"
                                       min="1"
                                       value="1"
                                       required
                                       readonly
                                       placeholder="Total Jumlah">
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-bold mb-1">Satuan</label>
                                <input type="text"
                                       id="borrowSatuanInput"
                                       class="form-control form-control-sm bg-light fw-semibold text-secondary"
                                       value="Satuan"
                                       readonly>
                                <span class="d-none" id="borrowSatuanLabel">Satuan</span>
                            </div>
                        </div>
                        <div id="borrowQtyWarning" class="text-danger small mt-1 d-none"></div>
                    </div>

                    <!-- Rencana Pengembalian (Wajib) -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">Rencana Pengembalian <span class="text-danger">*</span></label>
                        <input type="date"
                               name="expected_return_date"
                               class="form-control form-control-sm"
                               value="{{ date('Y-m-d', strtotime('+7 days')) }}"
                               required>
                    </div>

                    <!-- Keperluan Pemakaian (Wajib) -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">Keperluan Pemakaian <span class="text-danger">*</span></label>
                        <textarea name="purpose"
                                  rows="2"
                                  class="form-control form-control-sm"
                                  placeholder="Jelaskan keperluan dan lokasi pekerjaan..."
                                  required></textarea>
                    </div>

                    <!-- Petugas Pencatat (Wajib) & Keterangan (Opsional) -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">Petugas Pencatat <span class="text-danger">*</span></label>
                        <input type="text"
                               name="recorded_by"
                               class="form-control form-control-sm mb-2"
                               value="{{ Auth::user()->name ?? 'Administrator HSSE' }}"
                               required>

                        <label class="form-label small fw-bold mb-1">Keterangan <span class="text-muted fw-normal">(Opsional)</span></label>
                        <input type="text"
                               name="notes"
                               class="form-control form-control-sm"
                               placeholder="Catatan kondisi atau kelengkapan alat (opsional)...">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="btnSubmitBorrow" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Peminjaman
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 5. MODAL CATAT PENGEMBALIAN BARANG (F.22 - F.24) -->
<div class="modal fade" id="modalKembalikanBarang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formReturnBorrowing" method="POST" action="" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <div>
                    <h6 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-box-arrow-in-left me-2"></i>Catat Pengembalian Barang
                    </h6>
                    <small class="text-white-50">Mendukung pengembalian sebagian (bertahap) maupun pengembalian penuh</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border mb-3 small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Kode Peminjaman:</span>
                        <strong class="font-monospace text-dark" id="returnModalCode">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Nama Barang:</span>
                        <strong class="text-dark" id="returnModalName">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Peminjam / Unit:</span>
                        <strong class="text-dark" id="returnModalBorrower">-</strong>
                    </div>
                    <div class="d-flex justify-content-between pt-1 border-top">
                        <span class="text-muted">Masih Dipinjam:</span>
                        <strong class="text-danger fs-6" id="returnModalRemaining">-</strong>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Jumlah Dikembalikan <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="number"
                                   name="quantity_returned"
                                   id="returnQtyInput"
                                   class="form-control"
                                   min="1"
                                   required>
                            <span class="input-group-text bg-light fw-semibold" id="returnSatuanLabel">Unit</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Lokasi Pengembalian <span class="text-danger">*</span></label>
                        <select name="return_location" id="returnLocationSelect" class="form-select form-select-sm" required>
                            @foreach($locationList as $loc)
                                <option value="{{ $loc }}">{{ $loc }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tanggal Pengembalian Aktual <span class="text-danger">*</span></label>
                        <input type="datetime-local"
                               name="actual_return_date"
                               class="form-control form-control-sm"
                               value="{{ date('Y-m-d\TH:i') }}"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Petugas Penerima <span class="text-danger">*</span></label>
                        <input type="text"
                               name="recorded_by"
                               class="form-control form-control-sm"
                               value="{{ Auth::user()->name ?? 'Administrator HSSE' }}"
                               required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Keterangan Pengembalian <span class="text-muted fw-normal">(Opsional)</span></label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Kondisi barang saat dikembalikan atau catatan tambahan..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success btn-sm px-4">
                    <i class="bi bi-check-lg me-1"></i> Simpan Pengembalian
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 6. MODAL EDIT / PERPANJANG RENCANA PENGEMBALIAN (F.25) -->
<div class="modal fade" id="modalEditDueDate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form id="formEditDueDate" method="POST" action="" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    <i class="bi bi-calendar-plus text-warning me-2"></i>Edit Rencana Kembali
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-2 bg-light rounded border small mb-3">
                    <div class="fw-bold text-dark" id="dueDateModalItem">-</div>
                    <div class="text-muted font-monospace" style="font-size: 11px;" id="dueDateModalCode">-</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Rencana Pengembalian Baru <span class="text-danger">*</span></label>
                    <input type="date" name="expected_return_date" id="dueDateInput" class="form-control form-control-sm" required>
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-bold">Alasan Perpanjangan</label>
                    <textarea name="reason" rows="2" class="form-control form-control-sm" placeholder="Contoh: Pekerjaan lapangan diperpanjang..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm px-3">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- 7. MODAL DETAIL RIWAYAT PEMINJAMAN & PENGEMBALIAN KRONOLOGIS (G.31) -->
<div class="modal fade" id="modalDetailBorrowing" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history text-primary me-2"></i>Detail Riwayat Peminjaman & Pengembalian
                    </h6>
                    <small class="text-muted">Urutan kronologis peminjaman hingga setiap tahap pengembalian barang</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body scrollable-modal-body" id="detailBorrowingContent">
                <!-- Diisi secara dinamis melalui JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@adminManagement
<!-- 8. MODAL DETAIL TRANSAKSI MUTASI (D.12) -->
<div class="modal fade" id="modalDetailTransaction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    <i class="bi bi-receipt text-primary me-2"></i>Detail Transaksi Mutasi Barang
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailTransactionContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary btn-sm px-4 btn" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- 9. MODAL KONFIRMASI HAPUS MASTER BARANG (A.3) -->
<div class="modal fade" id="modalKonfirmasiHapus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-3">
            <div class="modal-body pb-2">
                <div class="text-danger mb-2">
                    <i class="bi bi-exclamation-triangle fs-1"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Hapus Master Barang</h6>
                <div class="p-2 bg-light rounded border my-2">
                    <div id="deleteItemName" class="fw-bold text-dark small"></div>
                    <div id="deleteItemCode" class="font-monospace text-muted" style="font-size: 11px;"></div>
                </div>
                <p id="deleteWarningProtected" class="text-danger small fw-semibold d-none mb-2">
                    Barang ini sudah memiliki riwayat transaksi/peminjaman dan dilindungi agar tidak merusak histori data.
                </p>
                <p id="deleteWarningNormal" class="text-muted small mb-2">
                    Apakah Anda yakin ingin menghapus master barang ini?
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <form id="formHapusItem" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="btnConfirmDeleteItem" class="btn btn-danger btn-sm px-3">
                        <i class="bi bi-trash me-1"></i> Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endadminManagement

<!-- 10. MODAL LIHAT STOK & EXPIRED PER LOKASI (Requirement 1) -->
<div class="modal fade" id="modalLihatStokExpired" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0">
                        <i class="bi bi-calendar2-check text-primary me-2"></i>Rincian Stok &amp; Tanggal Expired Barang
                    </h6>
                    <small class="text-muted">Rincian ketersediaan stok per lokasi penyimpanan (Warehouse &amp; Gudang HSSE) beserta kelompok tanggal kedaluwarsanya</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body scrollable-modal-body" id="stokExpiredModalContent">
                <!-- Diisi secara dinamis melalui JavaScript openStokExpiredModal() -->
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    @adminManagement
                        <button type="button" id="btnQuickAddBatchFromExpiryModal" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Catat Barang Masuk (Tambah Kelompok Expired)
                        </button>
                    @endadminManagement
                </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@php
    $masterItemsForJs = $inventories->map(function ($inv) {
        return [
            'id' => $inv->id,
            'code' => $inv->kode_barang,
            'name' => $inv->nama_barang,
            'category' => $inv->kategori,
            'warehouse' => (int) $inv->stock_warehouse,
            'hsse' => (int) $inv->stock_hsse,
            'unit' => $inv->satuan,
            'expiry_groups' => $inv->getStockExpiryGroups(true),
        ];
    })->values();
@endphp
@push('scripts')
<script>
    // Data Master Barang & Satuan untuk pembuatan baris transaksi multi-item
    const MASTER_ITEMS_DATA = @json($masterItemsForJs);

    const SATUAN_OPTIONS = @json($satuanList);
    const LOCATION_OPTIONS = @json($locationList);

    function escapeInvHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    let trxRowCounter = 1;

    // 1. Kalkulasi otomatis Total Stok & Deteksi Barang Existing pada Modal Tambah & Edit Master Barang
    function checkAddExistingMasterItem() {
        const nameInput = document.getElementById('addNamaBarangInput');
        const warnBox = document.getElementById('addDuplicateItemWarning');
        if (!nameInput || !warnBox) return;

        const typed = nameInput.value.trim().toLowerCase();
        if (!typed) {
            warnBox.classList.add('d-none');
            warnBox.innerHTML = '';
            return;
        }

        const matched = MASTER_ITEMS_DATA.find(it => (it.name || '').trim().toLowerCase() === typed || (it.code || '').trim().toLowerCase() === typed);
        if (matched) {
            warnBox.innerHTML = `<i class="bi bi-info-circle-fill me-1"></i>Barang <strong>[${escapeInvHtml(matched.code)}] ${escapeInvHtml(matched.name)}</strong> sudah terdaftar (Warehouse: ${matched.warehouse} ${escapeInvHtml(matched.unit)} | Gudang HSSE: ${matched.hsse} ${escapeInvHtml(matched.unit)}). Jika disimpan dengan stok > 0, stok akan otomatis ditambahkan ke master barang tersebut tanpa membuat duplikat baru.`;
            warnBox.classList.remove('d-none');
        } else {
            warnBox.classList.add('d-none');
            warnBox.innerHTML = '';
        }
    }

    function updateAddTotalStockPreview() {
        const wh = parseInt(document.getElementById('addStockWarehouse')?.value) || 0;
        const hsse = parseInt(document.getElementById('addStockHsse')?.value) || 0;
        const preview = document.getElementById('addTotalStockPreview');
        if (preview) {
            preview.value = (wh + hsse).toLocaleString('id-ID');
        }
    }

    function updateEditTotalStockPreview() {
        const wh = parseInt(document.getElementById('editStockWarehouse')?.value) || 0;
        const hsse = parseInt(document.getElementById('editStockHsse')?.value) || 0;
        const preview = document.getElementById('editTotalStockPreview');
        if (preview) {
            preview.value = (wh + hsse).toLocaleString('id-ID');
        }
    }

    // 2. Modal Edit Master Barang
    function openEditModal(item) {
        const form = document.getElementById('formEditMaster');
        form.action = `/inventory/${item.id}`;

        document.getElementById('editBarangId').value = item.barang_id || '';
        document.getElementById('editNamaBarang').value = item.nama_barang || '';

        const resolvedKategori = (typeof resolveRenamedCategory === 'function')
            ? resolveRenamedCategory(item.kategori || '')
            : (item.kategori || '');
        const kategoriSelect = document.getElementById('editKategori');
        if (kategoriSelect) {
            let katExists = Array.from(kategoriSelect.options).some(opt => opt.value === resolvedKategori);
            if (!katExists && resolvedKategori) {
                const opt = new Option(resolvedKategori, resolvedKategori, true, true);
                kategoriSelect.add(opt);
            }
            kategoriSelect.value = resolvedKategori;
        }

        const satuanSelect = document.getElementById('editSatuan');
        if (satuanSelect) {
            // Jika satuan lama tidak ada di daftar standar, tambahkan sementara agar tidak hilang
            let exists = Array.from(satuanSelect.options).some(opt => opt.value === item.satuan);
            if (!exists && item.satuan) {
                const opt = new Option(item.satuan, item.satuan, true, true);
                satuanSelect.add(opt);
            }
            satuanSelect.value = item.satuan || 'Box';
        }

        document.getElementById('editStockWarehouse').value = item.stock_warehouse ?? 0;
        document.getElementById('editStockHsse').value = item.stock_hsse ?? 0;
        document.getElementById('editKeterangan').value = item.keterangan || '';
        document.getElementById('editExpiredDate').value = item.expired_date || '';
        updateEditTotalStockPreview();

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditMasterGlobal'));
        modal.show();
    }

    // 3. Modal Konfirmasi Hapus Master Barang
    function openDeleteConfirmation(itemId, itemName, itemCode, hasHistory = false) {
        const form = document.getElementById('formHapusItem');
        form.action = `/inventory/${itemId}`;

        document.getElementById('deleteItemName').textContent = itemName;
        document.getElementById('deleteItemCode').textContent = itemCode;

        const warnProtected = document.getElementById('deleteWarningProtected');
        const warnNormal = document.getElementById('deleteWarningNormal');
        const btnDelete = document.getElementById('btnConfirmDeleteItem');

        if (hasHistory) {
            warnProtected.classList.remove('d-none');
            warnNormal.classList.add('d-none');
            btnDelete.disabled = true;
        } else {
            warnProtected.classList.add('d-none');
            warnNormal.classList.remove('d-none');
            btnDelete.disabled = false;
        }

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKonfirmasiHapus'));
        modal.show();
    }

    // 4. Multi-Item Form Transaksi Mutasi Barang (Modal Khusus, Validasi Ketat, Konfirmasi Batal/Tutup)
    let trxModalIsDirty = false;

    function markTrxDirty() {
        trxModalIsDirty = true;
    }

    function isTrxModalDirty() {
        if (trxModalIsDirty) return true;
        const rows = document.querySelectorAll('#trxItemsContainer .trx-item-row');
        if (rows.length > 1) return true;
        const firstId = rows[0]?.querySelector('.trx-item-id-input')?.value || rows[0]?.querySelector('.trx-item-select')?.value;
        if (firstId) return true;
        const ket = document.getElementById('trxKeteranganInput');
        if (ket && ket.value.trim() !== '') return true;
        return false;
    }

    function openTrxModal() {
        trxModalIsDirty = false;
        const alertBox = document.getElementById('trxValidationAlert');
        if (alertBox) alertBox.classList.add('d-none');
        const btn = document.getElementById('btnSubmitTrx');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Transaksi';
        }
        const modalEl = document.getElementById('modalTransaksiTerpadu');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    function attemptCloseTrxModal() {
        if (isTrxModalDirty()) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Batalkan Pengisian?',
                    text: 'Perubahan pada formulir transaksi belum disimpan. Apakah Anda yakin ingin menutup form ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Tutup',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        forceCloseTrxModal();
                    }
                });
                return;
            } else {
                const confirmed = window.confirm('Perubahan pada formulir transaksi belum disimpan. Apakah Anda yakin ingin menutup dan membatalkan pengisian transaksi ini?');
                if (!confirmed) {
                    return;
                }
            }
        }
        forceCloseTrxModal();
    }

    function forceCloseTrxModal() {
        trxModalIsDirty = false;
        const alertBox = document.getElementById('trxValidationAlert');
        if (alertBox) alertBox.classList.add('d-none');
        const modalEl = document.getElementById('modalTransaksiTerpadu');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();
        }
    }

    function handleTrxFormSubmit(event) {
        const errors = [];
        const petugasInput = document.getElementById('trxPetugasInput');
        const tanggalInput = document.getElementById('trxTanggalInput');

        if (!tanggalInput || !tanggalInput.value.trim()) {
            errors.push('Tanggal Transaksi wajib diisi.');
        }
        if (!petugasInput || !petugasInput.value.trim()) {
            errors.push('Petugas Pencatat wajib diisi.');
        }

        const rows = document.querySelectorAll('#trxItemsContainer .trx-item-row');
        if (rows.length === 0) {
            errors.push('Minimal satu baris barang wajib diisi.');
        }

        rows.forEach((row, index) => {
            const rowNum = index + 1;
            const itemIdInput = row.querySelector('.trx-item-id-input') || row.querySelector('.trx-item-select');
            const locSel = row.querySelector('.trx-location-select');
            const satSel = row.querySelector('.trx-satuan-select');
            const qtyInp = row.querySelector('.trx-qty-input');

            if (!itemIdInput || !itemIdInput.value) {
                errors.push(`Baris #${rowNum}: Barang wajib dipilih.`);
            }
            if (!locSel || !locSel.value) {
                errors.push(`Baris #${rowNum}: Lokasi wajib dipilih.`);
            }
            if (!satSel || !satSel.value) {
                errors.push(`Baris #${rowNum}: Satuan wajib dipilih.`);
            }
            const qtyVal = parseInt(qtyInp?.value, 10);
            if (!qtyInp || isNaN(qtyVal) || qtyVal < 1) {
                errors.push(`Baris #${rowNum}: Kuantitas wajib diisi (minimal 1).`);
            } else if (!validateTrxRow(row)) {
                errors.push(`Baris #${rowNum}: Kuantitas keluar melebihi stok yang tersedia di lokasi ${locSel?.value || ''}.`);
            }
        });

        const alertBox = document.getElementById('trxValidationAlert');
        const alertList = document.getElementById('trxValidationList');

        if (errors.length > 0) {
            if (event) event.preventDefault();
            if (alertBox && alertList) {
                alertList.innerHTML = errors.map(err => `<li>${escapeInvHtml(err)}</li>`).join('');
                alertBox.classList.remove('d-none');
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            return false;
        }

        if (alertBox) alertBox.classList.add('d-none');
        trxModalIsDirty = false;
        const btn = document.getElementById('btnSubmitTrx');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
        }
        return true;
    }

    // Autocomplete Catatan Transaksi (Pencarian langsung di dropdown per baris)
    function onTrxItemSearchFocus(inputEl) {
        const wrapper = inputEl.closest('.trx-autocomplete-wrapper');
        const dropdown = wrapper.querySelector('.trx-autocomplete-dropdown');
        filterAndRenderTrxAutocomplete(wrapper, inputEl.value);
        dropdown.classList.remove('d-none');
    }

    function onTrxItemSearchInput(inputEl) {
        const wrapper = inputEl.closest('.trx-autocomplete-wrapper');
        const row = inputEl.closest('tr');
        const idInput = wrapper.querySelector('.trx-item-id-input');
        const clearBtn = wrapper.querySelector('.trx-item-clear-btn');
        const dropdown = wrapper.querySelector('.trx-autocomplete-dropdown');

        const currentItemId = idInput.value;
        if (currentItemId) {
            const currentItem = MASTER_ITEMS_DATA.find(it => String(it.id) === String(currentItemId));
            const expectedText = currentItem ? `[${currentItem.code}] ${currentItem.name}` : '';
            if (inputEl.value.trim() !== expectedText) {
                idInput.value = '';
                row.removeAttribute('data-warehouse');
                row.removeAttribute('data-hsse');
                row.removeAttribute('data-unit');
                wrapper.querySelector('.trx-row-stock-info').textContent = '';
                validateTrxRow(row);
            }
        }

        if (inputEl.value.trim().length > 0) {
            clearBtn.classList.remove('d-none');
        } else {
            clearBtn.classList.add('d-none');
            idInput.value = '';
            row.removeAttribute('data-warehouse');
            row.removeAttribute('data-hsse');
            row.removeAttribute('data-unit');
            wrapper.querySelector('.trx-row-stock-info').textContent = '';
            validateTrxRow(row);
        }

        filterAndRenderTrxAutocomplete(wrapper, inputEl.value);
        dropdown.classList.remove('d-none');
        markTrxDirty();
    }

    function onTrxItemSearchKeydown(event, inputEl) {
        const wrapper = inputEl.closest('.trx-autocomplete-wrapper');
        const dropdown = wrapper.querySelector('.trx-autocomplete-dropdown');
        if (dropdown.classList.contains('d-none')) {
            if (event.key === 'ArrowDown' || event.key === 'Enter') {
                onTrxItemSearchFocus(inputEl);
            }
            return;
        }

        const items = dropdown.querySelectorAll('.trx-ac-item');
        if (!items || items.length === 0) return;

        let activeIdx = -1;
        items.forEach((item, idx) => {
            if (item.classList.contains('active')) activeIdx = idx;
        });

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            const nextIdx = (activeIdx + 1) < items.length ? activeIdx + 1 : 0;
            items.forEach((it, idx) => it.classList.toggle('active', idx === nextIdx));
            items[nextIdx].scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            const prevIdx = (activeIdx - 1) >= 0 ? activeIdx - 1 : items.length - 1;
            items.forEach((it, idx) => it.classList.toggle('active', idx === prevIdx));
            items[prevIdx].scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (activeIdx >= 0 && items[activeIdx]) {
                const itemId = items[activeIdx].getAttribute('data-id');
                selectTrxItemById(wrapper.closest('tr'), itemId);
            } else if (items.length > 0) {
                const itemId = items[0].getAttribute('data-id');
                selectTrxItemById(wrapper.closest('tr'), itemId);
            }
        } else if (event.key === 'Escape') {
            dropdown.classList.add('d-none');
        }
    }

    function filterAndRenderTrxAutocomplete(wrapper, keyword) {
        const dropdown = wrapper.querySelector('.trx-autocomplete-dropdown');
        const kw = (keyword || '').trim().toLowerCase();

        let matches = [];
        if (!kw) {
            matches = MASTER_ITEMS_DATA.slice(0, 30);
        } else {
            // Pencarian mencocokkan sebagian kata dari nama barang, kode barang, maupun kategori
            matches = MASTER_ITEMS_DATA.filter(it => {
                const name = (it.name || '').toLowerCase();
                const code = (it.code || '').toLowerCase();
                const cat = (it.category || '').toLowerCase();
                return name.includes(kw) || code.includes(kw) || cat.includes(kw);
            });
        }

        if (matches.length === 0) {
            dropdown.innerHTML = `
                <div class="p-3 text-center text-muted small">
                    <i class="bi bi-search text-secondary d-block mb-1" style="font-size: 1.25rem;"></i>
                    <span>Barang tidak ditemukan untuk kata kunci tersebut.</span>
                </div>
            `;
            return;
        }

        dropdown.innerHTML = matches.map((it, idx) => `
            <button type="button"
                    class="list-group-item list-group-item-action trx-ac-item ${idx === 0 ? 'active' : ''}"
                    data-id="${escapeInvHtml(it.id)}"
                    onclick="selectTrxItemById(this, '${escapeInvHtml(it.id)}')"
                    onmousedown="selectTrxItemById(this, '${escapeInvHtml(it.id)}')">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="trx-ac-title">
                        <span class="trx-ac-code">[${escapeInvHtml(it.code)}]</span>
                        <span>${escapeInvHtml(it.name)}</span>
                        ${it.category ? `<span class="trx-ac-category ms-1">${escapeInvHtml(it.category)}</span>` : ''}
                    </div>
                    <span class="trx-ac-unit flex-shrink-0">${escapeInvHtml(it.unit)}</span>
                </div>
                <div class="trx-ac-stock">
                    <span><i class="bi bi-building me-1 text-primary"></i>Warehouse: <strong class="trx-ac-stock-val">${it.warehouse} ${escapeInvHtml(it.unit)}</strong></span>
                    <span><i class="bi bi-shield-check me-1 text-success"></i>Gudang HSSE: <strong class="trx-ac-stock-val">${it.hsse} ${escapeInvHtml(it.unit)}</strong></span>
                </div>
            </button>
        `).join('');
    }

    function selectTrxItemById(rowOrEl, itemId) {
        if (!rowOrEl || !itemId) return;
        const row = rowOrEl.tagName === 'TR' ? rowOrEl : rowOrEl.closest('tr');
        if (!row) return;
        const item = MASTER_ITEMS_DATA.find(it => String(it.id) === String(itemId));
        if (!item) return;

        const idInput = row.querySelector('.trx-item-id-input');
        const searchInput = row.querySelector('.trx-item-search-input');
        const clearBtn = row.querySelector('.trx-item-clear-btn');
        const dropdown = row.querySelector('.trx-autocomplete-dropdown');
        const infoBox = row.querySelector('.trx-row-stock-info');
        const satuanSelect = row.querySelector('.trx-satuan-select');

        if (idInput) idInput.value = item.id;
        if (searchInput) searchInput.value = `[${item.code}] ${item.name}`;
        if (clearBtn) clearBtn.classList.remove('d-none');
        if (dropdown) dropdown.classList.add('d-none');

        row.setAttribute('data-warehouse', item.warehouse);
        row.setAttribute('data-hsse', item.hsse);
        row.setAttribute('data-unit', item.unit);

        if (satuanSelect) {
            let exists = Array.from(satuanSelect.options).some(o => o.value === item.unit);
            if (!exists && item.unit) {
                satuanSelect.add(new Option(item.unit, item.unit, true, true));
            }
            satuanSelect.value = item.unit;
        }

        if (infoBox) {
            infoBox.innerHTML = `<span class="text-secondary"><i class="bi bi-info-circle me-1"></i>Stok Tersedia:</span> Warehouse <strong class="text-dark">${item.warehouse} ${escapeInvHtml(item.unit)}</strong> &bull; Gudang HSSE <strong class="text-dark">${item.hsse} ${escapeInvHtml(item.unit)}</strong>`;
        }

        markTrxDirty();
        validateTrxRow(row);
    }

    function clearTrxItemSelection(btnOrRow) {
        if (!btnOrRow) return;
        const row = btnOrRow.tagName === 'TR' ? btnOrRow : btnOrRow.closest('tr');
        if (!row) return;

        const idInput = row.querySelector('.trx-item-id-input');
        const searchInput = row.querySelector('.trx-item-search-input');
        const clearBtn = row.querySelector('.trx-item-clear-btn');
        const dropdown = row.querySelector('.trx-autocomplete-dropdown');
        const infoBox = row.querySelector('.trx-row-stock-info');

        if (idInput) idInput.value = '';
        if (searchInput) searchInput.value = '';
        if (clearBtn) clearBtn.classList.add('d-none');
        if (dropdown) dropdown.classList.add('d-none');
        if (infoBox) infoBox.textContent = '';

        row.removeAttribute('data-warehouse');
        row.removeAttribute('data-hsse');
        row.removeAttribute('data-unit');

        markTrxDirty();
        validateTrxRow(row);
    }

    function addTrxItemRow(preselectedItemId = '') {
        markTrxDirty();
        const tbody = document.getElementById('trxItemsContainer');
        const idx = trxRowCounter++;

        const locOpts = LOCATION_OPTIONS.map(l => `<option value="${l}">${l}</option>`).join('');
        const satOpts = SATUAN_OPTIONS.map(s => `<option value="${s}">${s}</option>`).join('');

        const tr = document.createElement('tr');
        tr.className = 'trx-item-row';
        tr.setAttribute('data-row-index', idx);
        tr.innerHTML = `
            <td>
                <div class="position-relative trx-autocomplete-wrapper">
                    <input type="hidden" name="items[${idx}][inventory_id]" class="trx-item-id-input" value="" required>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted px-2.5"><i class="bi bi-search"></i></span>
                        <input type="text"
                               class="form-control form-control-sm trx-item-search-input"
                               placeholder="Ketik nama atau kode barang (cth: mask)..."
                               autocomplete="off"
                               onfocus="onTrxItemSearchFocus(this)"
                               oninput="onTrxItemSearchInput(this)"
                               onkeydown="onTrxItemSearchKeydown(event, this)">
                        <button class="btn btn-outline-secondary btn-sm trx-item-clear-btn d-none"
                                type="button"
                                title="Kosongkan pilihan"
                                onclick="clearTrxItemSelection(this)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="trx-autocomplete-dropdown list-group shadow border rounded d-none">
                    </div>
                    <div class="trx-row-stock-info small mt-1.5" style="font-size: 11.5px;"></div>
                </div>
            </td>
            <td>
                <select name="items[${idx}][location]" class="form-select form-select-sm trx-location-select" required onchange="markTrxDirty(); validateTrxRow(this.closest('tr'))">
                    ${locOpts}
                </select>
            </td>
            <td>
                <select name="items[${idx}][satuan]" class="form-select form-select-sm trx-satuan-select" required onchange="markTrxDirty()">
                    ${satOpts}
                </select>
            </td>
            <td>
                <input type="number" name="items[${idx}][jumlah]" class="form-control form-control-sm trx-qty-input" min="1" value="1" required oninput="markTrxDirty(); validateTrxRow(this.closest('tr'))">
                <div class="trx-row-warning text-danger small mt-1 d-none" style="font-size: 10.5px;"></div>
            </td>
            <td>
                <input type="date" name="items[${idx}][expired_date]" class="form-control form-control-sm trx-expired-input" onchange="markTrxDirty()">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeTrxItemRow(this)" title="Hapus Baris">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);

        if (preselectedItemId) {
            selectTrxItemById(tr, preselectedItemId);
        }
    }

    function removeTrxItemRow(btn) {
        markTrxDirty();
        const tbody = document.getElementById('trxItemsContainer');
        const rows = tbody.querySelectorAll('.trx-item-row');
        if (rows.length <= 1) {
            // Reset baris pertama jika hanya tinggal 1 baris
            const firstRow = rows[0];
            clearTrxItemSelection(firstRow);
            firstRow.querySelector('.trx-qty-input').value = 1;
            firstRow.querySelector('.trx-row-warning').classList.add('d-none');
            revalidateAllTrxRows();
            return;
        }
        btn.closest('tr').remove();
        revalidateAllTrxRows();
    }

    function validateTrxRow(row) {
        const isKeluar = document.getElementById('trxKeluar')?.checked;
        const idInput = row.querySelector('.trx-item-id-input');
        const locSelect = row.querySelector('.trx-location-select');
        const qtyInput = row.querySelector('.trx-qty-input');
        const warnBox = row.querySelector('.trx-row-warning');

        const itemId = idInput?.value;
        if (isKeluar && itemId) {
            const item = MASTER_ITEMS_DATA.find(it => String(it.id) === String(itemId));
            if (item) {
                const loc = getSelectedBorrowLocation();
                const avail = loc === 'Gudang HSSE' ? item.hsse : item.warehouse;
                const reqQty = parseInt(qtyInput?.value) || 0;

                if (reqQty > avail) {
                    warnBox.textContent = `Melebihi stok ${loc} (Tersedia: ${avail})!`;
                    warnBox.classList.remove('d-none');
                    updateTrxSubmitButtonState();
                    return false;
                }
            }
        }

        warnBox.classList.add('d-none');
        updateTrxSubmitButtonState();
        return true;
    }

    function revalidateAllTrxRows() {
        document.querySelectorAll('#trxItemsContainer .trx-item-row').forEach(r => validateTrxRow(r));
    }

    function updateTrxSubmitButtonState() {
        const hasVisibleWarning = document.querySelectorAll('#trxItemsContainer .trx-row-warning:not(.d-none)').length > 0;
        const btn = document.getElementById('btnSubmitTrx');
        if (btn) {
            btn.disabled = hasVisibleWarning;
        }
    }

    function openQuickTransaction(itemId) {
        trxModalIsDirty = false;
        const alertBox = document.getElementById('trxValidationAlert');
        if (alertBox) alertBox.classList.add('d-none');
        const btn = document.getElementById('btnSubmitTrx');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Transaksi';
        }
        const tbody = document.getElementById('trxItemsContainer');
        const rows = tbody.querySelectorAll('.trx-item-row');
        for (let i = 1; i < rows.length; i++) {
            rows[i].remove();
        }
        if (rows[0]) {
            selectTrxItemById(rows[0], itemId);
            trxModalIsDirty = true;
        }
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTransaksiTerpadu'));
        modal.show();
    }

    // Listener klik di luar untuk menutup dropdown autocomplete transaksi
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.trx-autocomplete-wrapper')) {
            document.querySelectorAll('.trx-autocomplete-dropdown').forEach(d => d.classList.add('d-none'));
        }
    });

    // 5. Form Peminjaman Barang (Pencarian Langsung, Pemilihan Kelompok Expired & Multi-Baris)
    let borrowBatchRowCounter = 1;

    function openBorrowModal() {
        const modalEl = document.getElementById('modalPinjamBarang');
        if (modalEl) {
            const btn = document.getElementById('btnSubmitBorrow');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Simpan Peminjaman';
            }
            const resultsBox = document.getElementById('borrowLiveSearchResults');
            if (resultsBox) resultsBox.classList.add('d-none');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    function filterBorrowItemOptions(keyword, showDropdown = true) {
        const select = document.getElementById('borrowItemSelect');
        const resultsBox = document.getElementById('borrowLiveSearchResults');
        if (!select) return;

        const kw = (keyword || '').trim().toLowerCase();
        const matchedOptions = [];

        Array.from(select.options).forEach(opt => {
            if (!opt.value) return;
            const match = !kw || opt.text.toLowerCase().includes(kw);
            opt.hidden = !match;
            if (match) {
                matchedOptions.push(opt);
            }
        });

        if (!resultsBox) return;

        if (!showDropdown || kw.length === 0) {
            if (kw.length === 0 && select.value) {
                select.value = '';
                handleBorrowItemSelect();
            }
            resultsBox.classList.add('d-none');
            resultsBox.innerHTML = '';
            return;
        }

        if (matchedOptions.length === 0) {
            resultsBox.innerHTML = `<div class="list-group-item small text-muted py-2"><i class="bi bi-info-circle me-1"></i>Tidak ditemukan barang dengan kata kunci "<strong>${escapeInvHtml(keyword)}</strong>"</div>`;
            resultsBox.classList.remove('d-none');
            return;
        }

        resultsBox.innerHTML = matchedOptions.map(opt => {
            const code = opt.getAttribute('data-code') || '';
            const name = opt.getAttribute('data-name') || '';
            const wh = opt.getAttribute('data-warehouse') || '0';
            const hsse = opt.getAttribute('data-hsse') || '0';
            const sat = opt.getAttribute('data-satuan') || 'Unit';
            return `
                <button type="button"
                        class="list-group-item list-group-item-action py-2 px-3 small text-start"
                        onclick="selectBorrowLiveItem('${escapeInvHtml(opt.value)}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="font-monospace fw-bold text-primary">[${escapeInvHtml(code)}]</span>
                            <span class="fw-semibold text-dark ms-1">${escapeInvHtml(name)}</span>
                        </div>
                        <span class="badge bg-light text-secondary border">${escapeInvHtml(sat)}</span>
                    </div>
                    <div class="text-muted mt-1" style="font-size: 11px;">
                        Warehouse: <strong>${escapeInvHtml(wh)} ${escapeInvHtml(sat)}</strong> &bull; Gudang HSSE: <strong>${escapeInvHtml(hsse)} ${escapeInvHtml(sat)}</strong>
                    </div>
                </button>
            `;
        }).join('');
        resultsBox.classList.remove('d-none');
    }

    function selectBorrowLiveItem(itemId) {
        const select = document.getElementById('borrowItemSelect');
        const searchInput = document.getElementById('borrowItemSearchInput');
        const resultsBox = document.getElementById('borrowLiveSearchResults');

        if (select) {
            select.value = String(itemId);
            const selectedOpt = select.options[select.selectedIndex];
            if (searchInput && selectedOpt && selectedOpt.value) {
                const code = selectedOpt.getAttribute('data-code') || '';
                const name = selectedOpt.getAttribute('data-name') || '';
                searchInput.value = `[${code}] ${name}`;
            }
            handleBorrowItemSelect();
        }
        if (resultsBox) {
            resultsBox.classList.add('d-none');
        }
    }

    document.addEventListener('click', function (e) {
        const resultsBox = document.getElementById('borrowLiveSearchResults');
        const searchInput = document.getElementById('borrowItemSearchInput');
        if (resultsBox && !resultsBox.contains(e.target) && e.target !== searchInput) {
            resultsBox.classList.add('d-none');
        }
    });

    function getAvailableExpiryGroupsForBorrow(itemId, location) {
        if (!itemId) return [];
        const master = MASTER_ITEMS_DATA.find(it => String(it.id) === String(itemId));
        if (!master || !Array.isArray(master.expiry_groups)) return [];
        return master.expiry_groups.filter(g => g.location === location && (parseInt(g.quantity, 10) || 0) > 0);
    }

    function buildBorrowBatchSelectOptionsHtml(itemId, location, selectedKey = '') {
        if (!itemId) {
            return '<option value="">-- Pilih Barang & Lokasi Terlebih Dahulu --</option>';
        }
        const groups = getAvailableExpiryGroupsForBorrow(itemId, location);
        if (groups.length === 0) {
            return `<option value="">-- Tidak ada stok tersedia di ${location} --</option>`;
        }

        let html = '';
        groups.forEach((g, idx) => {
            const isSel = selectedKey ? (g.expiry_key === selectedKey) : (idx === 0);
            const label = g.expiry_key === 'UNRECORDED'
                ? `Belum Dicatat (Tersedia: ${g.quantity} ${g.satuan})`
                : `${g.formatted_date} — Tersedia: ${g.quantity} ${g.satuan} [${g.status_label}]`;
            html += `<option value="${g.expiry_key}" data-avail="${g.quantity}" data-formatted="${g.formatted_date}" data-status="${g.status_label}" data-unit="${g.satuan}" ${isSel ? 'selected' : ''}>${label}</option>`;
        });
        return html;
    }

    function getSelectedBorrowLocation() {
        const locHidden = document.getElementById('borrowLocationHidden');
        if (locHidden && !locHidden.disabled && locHidden.value) {
            return locHidden.value;
        }
        const locSelect = document.getElementById('borrowLocationSelect');
        return locSelect ? locSelect.value : 'Warehouse';
    }

    function handleBorrowItemSelect() {
        const select = document.getElementById('borrowItemSelect');
        const locSelect = document.getElementById('borrowLocationSelect');
        const locHidden = document.getElementById('borrowLocationHidden');
        const locNote = document.getElementById('borrowLocationNote');
        const selected = select?.options[select.selectedIndex];
        const alertBox = document.getElementById('borrowStockAlert');
        const satuanLabel = document.getElementById('borrowSatuanLabel');
        const satuanInput = document.getElementById('borrowSatuanInput');
        const qtyInput = document.getElementById('borrowQtyInput');
        const submitBtn = document.getElementById('btnSubmitBorrow');

        if (!selected || !selected.value) {
            if (satuanLabel) satuanLabel.textContent = 'Satuan';
            if (satuanInput) satuanInput.value = 'Satuan';
            if (alertBox) alertBox.innerHTML = '';
            if (locNote) locNote.innerHTML = 'Pilih lokasi pengambilan barang.';
            if (locSelect) {
                locSelect.disabled = false;
                locSelect.innerHTML = LOCATION_OPTIONS.map(l => `<option value="${l}">${l}</option>`).join('');
            }
            if (locHidden) {
                locHidden.disabled = true;
                locHidden.value = '';
            }
            if (qtyInput) qtyInput.removeAttribute('max');
            rebuildBorrowBatchesForSelection();
            validateBorrowQty();
            return;
        }

        const wh = parseInt(selected.getAttribute('data-warehouse')) || 0;
        const hsse = parseInt(selected.getAttribute('data-hsse')) || 0;
        const unit = selected.getAttribute('data-satuan') || 'Unit';

        if (satuanLabel) satuanLabel.textContent = unit;
        if (satuanInput) satuanInput.value = unit;

        // Tentukan lokasi yang memiliki stok > 0
        const availableLocations = [];
        if (wh > 0) availableLocations.push({ name: 'Warehouse', stock: wh });
        if (hsse > 0) availableLocations.push({ name: 'Gudang HSSE', stock: hsse });

        if (availableLocations.length === 0) {
            // Kasus A: Stok kosong di semua lokasi -> Tampilkan info jelas & cegah peminjaman
            if (locSelect) {
                locSelect.innerHTML = '<option value="">-- Stok Habis di Semua Lokasi --</option>';
                locSelect.disabled = true;
            }
            if (locHidden) {
                locHidden.disabled = true;
                locHidden.value = '';
            }
            if (locNote) {
                locNote.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Stok barang ini tidak tersedia di lokasi manapun. Peminjaman tidak dapat dilakukan.</span>';
            }
            if (alertBox) {
                alertBox.innerHTML = '<div class="alert alert-danger py-1 px-2 small mb-0"><i class="bi bi-exclamation-triangle-fill me-1"></i>Stok barang habis di semua lokasi (Warehouse: 0, Gudang HSSE: 0). Peminjaman dicegah.</div>';
            }
            if (submitBtn) submitBtn.disabled = true;
            const tbody = document.getElementById('borrowBatchesContainer');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-2 text-danger small"><i class="bi bi-exclamation-circle me-1"></i>Stok kosong di semua lokasi.</td></tr>';
            }
            return;
        } else if (availableLocations.length === 1) {
            // Kasus B: Stok hanya tersedia di 1 lokasi -> Otomatis pilih dan kunci (disable)
            const singleLoc = availableLocations[0];
            if (locSelect) {
                locSelect.innerHTML = `<option value="${escapeInvHtml(singleLoc.name)}" selected>${escapeInvHtml(singleLoc.name)} (Tersedia: ${singleLoc.stock} ${escapeInvHtml(unit)})</option>`;
                locSelect.value = singleLoc.name;
                locSelect.disabled = true;
            }
            if (locHidden) {
                locHidden.value = singleLoc.name;
                locHidden.disabled = false;
            }
            if (locNote) {
                locNote.innerHTML = `<span class="text-primary small"><i class="bi bi-lock-fill me-1"></i>Lokasi otomatis dikunci ke <strong>${escapeInvHtml(singleLoc.name)}</strong> karena stok hanya tersedia di lokasi ini (${singleLoc.stock} ${escapeInvHtml(unit)}).</span>`;
            }
            if (alertBox) {
                alertBox.innerHTML = `Stok di <strong>${escapeInvHtml(singleLoc.name)}</strong>: <strong class="text-success">${singleLoc.stock} ${escapeInvHtml(unit)}</strong> <span class="text-muted">(Lokasi lain: 0)</span>`;
            }
            if (qtyInput) qtyInput.max = singleLoc.stock;
        } else {
            // Kasus C: Stok tersedia di lebih dari 1 lokasi -> Pengguna bebas memilih dari lokasi yang stok > 0
            const currentSelectedLoc = locSelect?.value;
            const stillValid = availableLocations.some(l => l.name === currentSelectedLoc);
            const activeLocName = stillValid ? currentSelectedLoc : availableLocations[0].name;

            if (locSelect) {
                locSelect.innerHTML = availableLocations.map(l =>
                    `<option value="${escapeInvHtml(l.name)}" ${l.name === activeLocName ? 'selected' : ''}>${escapeInvHtml(l.name)} (Tersedia: ${l.stock} ${escapeInvHtml(unit)})</option>`
                ).join('');
                locSelect.value = activeLocName;
                locSelect.disabled = false;
            }
            if (locHidden) {
                locHidden.disabled = true;
                locHidden.value = '';
            }
            if (locNote) {
                locNote.innerHTML = '<span class="text-muted small"><i class="bi bi-arrow-down-up me-1"></i>Pilih lokasi penyimpanan. Batas maksimal peminjaman mengikuti stok lokasi terpilih.</span>';
            }

            const chosenLocData = availableLocations.find(l => l.name === activeLocName);
            const locStock = chosenLocData ? chosenLocData.stock : 0;
            if (alertBox) {
                alertBox.innerHTML = `Stok di <strong>${escapeInvHtml(activeLocName)}</strong>: <strong class="text-success">${locStock} ${escapeInvHtml(unit)}</strong> <span class="text-muted">(Warehouse: ${wh} | Gudang HSSE: ${hsse})</span>`;
            }
            if (qtyInput) qtyInput.max = locStock;
        }

        rebuildBorrowBatchesForSelection();
    }

    function onBorrowLocationChange() {
        const select = document.getElementById('borrowItemSelect');
        const locSelect = document.getElementById('borrowLocationSelect');
        const selected = select?.options[select.selectedIndex];
        const alertBox = document.getElementById('borrowStockAlert');
        const qtyInput = document.getElementById('borrowQtyInput');

        if (selected && selected.value && locSelect) {
            const wh = parseInt(selected.getAttribute('data-warehouse')) || 0;
            const hsse = parseInt(selected.getAttribute('data-hsse')) || 0;
            const unit = selected.getAttribute('data-satuan') || 'Unit';
            const loc = locSelect.value;
            const locStock = loc === 'Gudang HSSE' ? hsse : wh;

            if (alertBox) {
                alertBox.innerHTML = `Stok di <strong>${escapeInvHtml(loc)}</strong>: <strong class="${locStock > 0 ? 'text-success' : 'text-danger'}">${locStock} ${escapeInvHtml(unit)}</strong> <span class="text-muted">(Warehouse: ${wh} | Gudang HSSE: ${hsse})</span>`;
            }
            if (qtyInput) qtyInput.max = locStock;
        }

        rebuildBorrowBatchesForSelection();
    }

    function rebuildBorrowBatchesForSelection() {
        const itemId = document.getElementById('borrowItemSelect')?.value || '';
        const location = getSelectedBorrowLocation();
        const selectedOpt = document.getElementById('borrowItemSelect')?.selectedOptions[0];
        const unit = selectedOpt?.getAttribute('data-satuan') || 'Satuan';
        const tbody = document.getElementById('borrowBatchesContainer');
        if (!tbody) return;

        // Reset ke 1 baris pertama saat barang atau lokasi diganti
        borrowBatchRowCounter = 1;
        tbody.innerHTML = `
            <tr class="borrow-batch-row" data-batch-index="0">
                <td>
                    <select name="borrow_batches[0][expiry_key]"
                            class="form-select form-select-sm borrow-batch-select"
                            required
                            onchange="onBorrowBatchSelectChange(this)">
                        ${buildBorrowBatchSelectOptionsHtml(itemId, location)}
                    </select>
                </td>
                <td class="text-center">
                    <span class="badge bg-light text-secondary border borrow-batch-avail-badge">-</span>
                </td>
                <td>
                    <div class="row g-1 align-items-center">
                        <div class="col-7">
                            <input type="number"
                                   name="borrow_batches[0][quantity]"
                                   class="form-control form-control-sm borrow-batch-qty"
                                   min="1"
                                   value="1"
                                   required
                                   oninput="onBorrowBatchQtyInput(this)">
                        </div>
                        <div class="col-5">
                            <input type="text"
                                   class="form-control form-control-sm bg-light text-secondary fw-semibold borrow-batch-unit text-center px-1"
                                   value="${unit}"
                                   readonly>
                        </div>
                    </div>
                    <div class="borrow-batch-row-warn text-danger small mt-1 d-none" style="font-size: 10.5px;"></div>
                </td>
                <td class="text-center">
                    <button type="button"
                            class="btn btn-sm btn-outline-danger py-0 px-2"
                            onclick="removeBorrowBatchRow(this)"
                            title="Hapus Baris Kelompok Expired">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
        `;

        const firstSelect = tbody.querySelector('.borrow-batch-select');
        if (firstSelect) {
            onBorrowBatchSelectChange(firstSelect);
        } else {
            validateBorrowQty();
        }
    }

    function addBorrowBatchRow() {
        const itemId = document.getElementById('borrowItemSelect')?.value || '';
        const location = getSelectedBorrowLocation();
        const selectedOpt = document.getElementById('borrowItemSelect')?.selectedOptions[0];
        const unit = selectedOpt?.getAttribute('data-satuan') || 'Satuan';
        const tbody = document.getElementById('borrowBatchesContainer');
        if (!tbody) return;

        if (!itemId) {
            alert('Silakan pilih barang terlebih dahulu sebelum menambah baris kelompok expired.');
            return;
        }

        const groups = getAvailableExpiryGroupsForBorrow(itemId, location);
        if (groups.length === 0) {
            alert(`Tidak ada stok tersedia untuk barang ini di ${location}.`);
            return;
        }

        // Pilih kelompok expired yang belum dipilih di baris sebelumnya jika ada
        const usedKeys = Array.from(tbody.querySelectorAll('.borrow-batch-select')).map(s => s.value);
        const unusedGroup = groups.find(g => !usedKeys.includes(g.expiry_key));
        const defaultKey = unusedGroup ? unusedGroup.expiry_key : groups[0].expiry_key;

        const idx = borrowBatchRowCounter++;
        const tr = document.createElement('tr');
        tr.className = 'borrow-batch-row';
        tr.setAttribute('data-batch-index', idx);
        tr.innerHTML = `
            <td>
                <select name="borrow_batches[${idx}][expiry_key]"
                        class="form-select form-select-sm borrow-batch-select"
                        required
                        onchange="onBorrowBatchSelectChange(this)">
                    ${buildBorrowBatchSelectOptionsHtml(itemId, location, defaultKey)}
                </select>
            </td>
            <td class="text-center">
                <span class="badge bg-light text-secondary border borrow-batch-avail-badge">-</span>
            </td>
            <td>
                <div class="row g-1 align-items-center">
                    <div class="col-7">
                        <input type="number"
                               name="borrow_batches[${idx}][quantity]"
                               class="form-control form-control-sm borrow-batch-qty"
                               min="1"
                               value="1"
                               required
                               oninput="onBorrowBatchQtyInput(this)">
                    </div>
                    <div class="col-5">
                        <input type="text"
                               class="form-control form-control-sm bg-light text-secondary fw-semibold borrow-batch-unit text-center px-1"
                               value="${unit}"
                               readonly>
                    </div>
                </div>
                <div class="borrow-batch-row-warn text-danger small mt-1 d-none" style="font-size: 10.5px;"></div>
            </td>
            <td class="text-center">
                <button type="button"
                        class="btn btn-sm btn-outline-danger py-0 px-2"
                        onclick="removeBorrowBatchRow(this)"
                        title="Hapus Baris Kelompok Expired">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);

        const sel = tr.querySelector('.borrow-batch-select');
        if (sel) {
            onBorrowBatchSelectChange(sel);
        }
    }

    function removeBorrowBatchRow(btn) {
        const tbody = document.getElementById('borrowBatchesContainer');
        if (!tbody) return;
        const rows = tbody.querySelectorAll('.borrow-batch-row');
        if (rows.length <= 1) {
            rows[0].querySelector('.borrow-batch-qty').value = 1;
            validateBorrowQty();
            return;
        }
        btn.closest('tr').remove();
        validateBorrowQty();
    }

    function onBorrowBatchSelectChange(sel) {
        const row = sel.closest('tr');
        const badge = row?.querySelector('.borrow-batch-avail-badge');
        const qtyInput = row?.querySelector('.borrow-batch-qty');
        const opt = sel.options[sel.selectedIndex];

        if (opt && opt.value) {
            const avail = parseInt(opt.getAttribute('data-avail'), 10) || 0;
            const unit = opt.getAttribute('data-unit') || 'Unit';
            if (badge) {
                badge.textContent = `Tersedia: ${avail} ${unit}`;
                badge.className = 'badge bg-success-subtle text-success border border-success-subtle borrow-batch-avail-badge';
            }
            if (qtyInput) {
                qtyInput.max = avail;
                if ((parseInt(qtyInput.value, 10) || 0) > avail && avail > 0) {
                    qtyInput.value = avail;
                }
            }
        } else {
            if (badge) {
                badge.textContent = '-';
                badge.className = 'badge bg-light text-secondary border borrow-batch-avail-badge';
            }
        }
        validateBorrowQty();
    }

    function onBorrowBatchQtyInput() {
        validateBorrowQty();
    }

    function validateBorrowQty() {
        const select = document.getElementById('borrowItemSelect');
        const locSelect = document.getElementById('borrowLocationSelect');
        const selected = select?.options[select.selectedIndex];
        const qtyInput = document.getElementById('borrowQtyInput');
        const warning = document.getElementById('borrowQtyWarning');
        const submitBtn = document.getElementById('btnSubmitBorrow');
        const tbody = document.getElementById('borrowBatchesContainer');

        let totalBatchQty = 0;
        let hasRowError = false;
        const requestedByExpiryKey = {};
        const availByExpiryKey = {};

        if (tbody) {
            const rows = tbody.querySelectorAll('.borrow-batch-row');
            rows.forEach(row => {
                const batchSel = row.querySelector('.borrow-batch-select');
                const rowQtyInp = row.querySelector('.borrow-batch-qty');
                const rowWarn = row.querySelector('.borrow-batch-row-warn');
                const opt = batchSel?.options[batchSel.selectedIndex];

                const rQty = Math.max(0, parseInt(rowQtyInp?.value, 10) || 0);
                totalBatchQty += rQty;

                if (opt && opt.value) {
                    const key = opt.value;
                    const avail = parseInt(opt.getAttribute('data-avail'), 10) || 0;
                    const formatted = opt.getAttribute('data-formatted') || key;
                    availByExpiryKey[key] = avail;
                    requestedByExpiryKey[key] = (requestedByExpiryKey[key] || 0) + rQty;

                    if (rQty < 1) {
                        rowWarn.textContent = 'Jumlah minimal 1.';
                        rowWarn.classList.remove('d-none');
                        hasRowError = true;
                    } else if (requestedByExpiryKey[key] > avail) {
                        rowWarn.textContent = `Melebihi stok kelompok [${formatted}] (Tersedia: ${avail})!`;
                        rowWarn.classList.remove('d-none');
                        hasRowError = true;
                    } else {
                        rowWarn.classList.add('d-none');
                    }
                } else {
                    rowWarn.classList.add('d-none');
                }
            });
        }

        if (qtyInput && totalBatchQty > 0) {
            qtyInput.value = totalBatchQty;
        }

        if (selected && selected.value) {
            const wh = parseInt(selected.getAttribute('data-warehouse')) || 0;
            const hsse = parseInt(selected.getAttribute('data-hsse')) || 0;
            const loc = getSelectedBorrowLocation();
            const avail = loc === 'Gudang HSSE' ? hsse : wh;
            const requested = parseInt(qtyInput?.value) || 0;

            if (avail <= 0) {
                warning.textContent = `Stok barang ini di ${loc} sedang kosong (0)! Pilih lokasi lain atau barang lain.`;
                warning.classList.remove('d-none');
                if (submitBtn) submitBtn.disabled = true;
                return;
            }

            if (requested > avail) {
                warning.textContent = `Total jumlah peminjaman (${requested}) melebihi stok yang tersedia di ${loc} (${avail})!`;
                warning.classList.remove('d-none');
                if (submitBtn) submitBtn.disabled = true;
                return;
            }
        }

        warning.classList.add('d-none');
        if (submitBtn) {
            submitBtn.disabled = hasRowError;
        }
    }

    function handleBorrowSubmit(event) {
        validateBorrowQty();
        const locHidden = document.getElementById('borrowLocationHidden');
        const locSelect = document.getElementById('borrowLocationSelect');
        if (locSelect && locSelect.disabled && locHidden) {
            locHidden.disabled = false;
            locHidden.value = locSelect.value;
        }
        const submitBtn = document.getElementById('btnSubmitBorrow');
        if (submitBtn && submitBtn.disabled) {
            if (event) event.preventDefault();
            return false;
        }
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
        }
        return true;
    }

    // 5B. Modal Lihat Stok & Expired per Lokasi (Requirement 1)
    function openStokExpiredModal(data) {
        const container = document.getElementById('stokExpiredModalContent');
        if (!container) return;

        const groups = Array.isArray(data.groups) ? data.groups : [];
        const whGroups = groups.filter(g => g.location === 'Warehouse');
        const hsseGroups = groups.filter(g => g.location === 'Gudang HSSE');

        const buildLocationTableRows = (locGroups, locLabel) => {
            if (locGroups.length === 0) {
                return `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3 small">
                            Tidak ada stok tersedia di <strong>${locLabel}</strong> (0 ${data.satuan}).
                        </td>
                    </tr>
                `;
            }
            return locGroups.map((g, i) => `
                <tr>
                    <td class="ps-3 text-muted">${i + 1}</td>
                    <td>
                        <span class="${g.location === 'Gudang HSSE' ? 'badge-loc-hsse' : 'badge-loc-warehouse'}">
                            <i class="bi bi-geo-alt me-1"></i>${g.location}
                        </span>
                    </td>
                    <td class="fw-bold ${g.status === 'expired' ? 'text-danger' : 'text-dark'}">
                        <i class="bi ${g.expiry_key === 'UNRECORDED' ? 'bi-question-circle text-muted' : 'bi-calendar-event text-primary'} me-1"></i>${g.formatted_date}
                    </td>
                    <td class="text-center fw-bold text-dark">
                        ${g.quantity} ${g.satuan}
                    </td>
                    <td class="text-center pe-3">
                        <span class="badge ${g.badge_class} px-2 py-1">${g.status_label}</span>
                    </td>
                </tr>
            `).join('');
        };

        const allRowsHtml = groups.length > 0
            ? buildLocationTableRows(groups, 'Semua Lokasi')
            : `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4 small">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Saat ini tidak ada stok tersedia di Warehouse maupun Gudang HSSE.
                    </td>
                </tr>
            `;

        container.innerHTML = `
            <div class="p-3 bg-light rounded border mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2 pb-2 border-bottom">
                    <div>
                        <span class="badge bg-secondary font-monospace me-1">${data.code}</span>
                        <strong class="text-dark fs-6">${data.name}</strong>
                        <div class="text-muted small mt-1">${data.category} &bull; Satuan: <strong>${data.satuan}</strong></div>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Total Stok Tersedia</span>
                        <span class="fs-5 fw-bold text-success">${data.total_stock} ${data.satuan}</span>
                    </div>
                </div>
                <div class="row g-2 small">
                    <div class="col-md-4">
                        <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-building me-1 text-primary"></i>Stok Warehouse:</span>
                            <strong class="text-dark">${data.stock_warehouse} ${data.satuan}</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-shield-check me-1 text-success"></i>Stok Gudang HSSE:</span>
                            <strong class="text-dark">${data.stock_hsse} ${data.satuan}</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-arrow-left-right me-1 text-warning"></i>Sedang Dipinjam:</span>
                            <strong class="text-warning-emphasis">${data.borrowed_stock || 0} ${data.satuan}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark small text-uppercase mb-0">
                    <i class="bi bi-layers me-1 text-primary"></i>Daftar Kelompok Stok Berdasarkan Lokasi &amp; Tanggal Expired
                </h6>
                <span class="badge bg-light text-secondary border">${groups.length} Kelompok Stok Aktif</span>
            </div>

            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 45px;">No</th>
                            <th>Lokasi Penyimpanan</th>
                            <th>Kelompok Tanggal Expired</th>
                            <th class="text-center">Jumlah Stok</th>
                            <th class="text-center pe-3">Status Kedaluwarsa</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${allRowsHtml}
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-2">
                <i class="bi bi-info-circle me-1"></i>Jika barang yang sama masuk dengan tanggal expired berbeda, sistem otomatis mengelompokkannya pada tabel ini tanpa membuat baris master barang baru.
            </div>
        `;

        const quickAddBtn = document.getElementById('btnQuickAddBatchFromExpiryModal');
        if (quickAddBtn) {
            quickAddBtn.onclick = function () {
                const expModal = bootstrap.Modal.getInstance(document.getElementById('modalLihatStokExpired'));
                if (expModal) expModal.hide();
                openQuickTransaction(data.id);
            };
        }

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLihatStokExpired'));
        modal.show();
    }

    // 6. Modal Pengembalian Barang (Bertahap & Penuh)
    function openReturnModal(data) {
        const form = document.getElementById('formReturnBorrowing');
        form.action = `/inventory/borrowings/${data.id}/return`;

        document.getElementById('returnModalCode').textContent = data.code;
        document.getElementById('returnModalName').textContent = data.name;
        document.getElementById('returnModalBorrower').textContent = `${data.borrower} (${data.department || '-'})`;
        document.getElementById('returnModalRemaining').textContent = `${data.remaining} ${data.satuan}`;
        document.getElementById('returnSatuanLabel').textContent = data.satuan;

        const locSelect = document.getElementById('returnLocationSelect');
        if (locSelect && data.location) {
            locSelect.value = data.location;
        }

        const qtyInput = document.getElementById('returnQtyInput');
        qtyInput.max = data.remaining;
        qtyInput.value = data.remaining;

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKembalikanBarang'));
        modal.show();
    }

    // 7. Modal Edit Rencana Pengembalian (Due Date)
    function openEditDueDateModal(data) {
        const form = document.getElementById('formEditDueDate');
        form.action = `/inventory/borrowings/${data.id}/due-date`;

        document.getElementById('dueDateModalItem').textContent = `${data.name} — ${data.borrower}`;
        document.getElementById('dueDateModalCode').textContent = data.code;
        document.getElementById('dueDateInput').value = data.expected_return_date || '';

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditDueDate'));
        modal.show();
    }

    // 8. Modal Detail Riwayat Peminjaman & Pengembalian Kronologis (H.26 + Pemantauan Kelompok Expired)
    function openDetailBorrowingModal(data) {
        const container = document.getElementById('detailBorrowingContent');
        const timeline = Array.isArray(data.timeline) ? data.timeline : [];
        const dueHistory = Array.isArray(data.due_history) ? data.due_history : [];
        const expiryDetails = Array.isArray(data.expiry_details) ? data.expiry_details : [];

        let expiryRowsHtml = '';
        if (expiryDetails.length > 0) {
            expiryRowsHtml = expiryDetails.map((ed, idx) => `
                <tr>
                    <td class="ps-3 text-muted">${idx + 1}</td>
                    <td><span class="badge bg-light text-dark border">${ed.location || data.location}</span></td>
                    <td class="fw-bold ${ed.status === 'expired' ? 'text-danger' : 'text-dark'}">
                        <i class="bi bi-calendar-event me-1"></i>${ed.formatted_date}
                    </td>
                    <td class="text-center fw-bold">${ed.quantity_borrowed} ${data.satuan}</td>
                    <td class="text-center text-success fw-semibold">${ed.quantity_returned} ${data.satuan}</td>
                    <td class="text-center text-danger fw-bold">${ed.remaining_quantity} ${data.satuan}</td>
                    <td class="text-center pe-3">
                        <span class="badge ${ed.badge_class} px-2 py-1">${ed.status_label}</span>
                    </td>
                </tr>
            `).join('');
        } else {
            expiryRowsHtml = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-2 small">Belum Dicatat</td>
                </tr>
            `;
        }

        let timelineRowsHtml = '';
        timeline.forEach((row, index) => {
            const isBorrow = index === 0;
            timelineRowsHtml += `
                <tr>
                    <td class="fw-bold ${isBorrow ? 'text-primary' : 'text-success'}">
                        <i class="bi ${isBorrow ? 'bi-box-arrow-right' : 'bi-box-arrow-in-left'} me-1"></i>${row.keterangan}
                    </td>
                    <td class="text-end fw-bold">${row.jumlah} ${data.satuan}</td>
                    <td>${row.tanggal}</td>
                    <td><span class="badge bg-light text-dark border">${row.lokasi || data.location}</span></td>
                    <td>${row.petugas || '-'}</td>
                    <td class="small text-muted">${row.catatan || '-'}</td>
                </tr>
            `;
        });

        let dueHistoryHtml = '';
        if (dueHistory.length > 0) {
            let dhRows = '';
            dueHistory.forEach((dh, i) => {
                dhRows += `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${dh.previous_date || '-'}</td>
                        <td class="fw-bold text-primary">${dh.new_date || '-'}</td>
                        <td>${dh.reason || '-'}</td>
                        <td>${dh.updated_by || '-'} <small class="text-muted">(${dh.updated_at || ''})</small></td>
                    </tr>
                `;
            });
            dueHistoryHtml = `
                <h6 class="fw-bold text-dark small text-uppercase mt-3 mb-2">
                    <i class="bi bi-calendar-range me-1 text-warning"></i>Histori Perpanjangan Rencana Pengembalian
                </h6>
                <div class="table-responsive border rounded mb-3">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tenggat Sebelumnya</th>
                                <th>Tenggat Baru</th>
                                <th>Alasan</th>
                                <th>Diubah Oleh</th>
                            </tr>
                        </thead>
                        <tbody>${dhRows}</tbody>
                    </table>
                </div>
            `;
        }

        container.innerHTML = `
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Kode Peminjaman:</span>
                            <strong class="font-monospace text-dark">${data.code}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Nama Peminjam:</span>
                            <strong class="text-dark">${data.borrower}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Unit/Divisi/Departemen:</span>
                            <strong class="text-dark">${data.department || '-'}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Status Saat Ini:</span>
                            <strong class="text-dark">${data.status}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Nama Barang:</span>
                            <strong class="text-dark">${data.name} [${data.item_code}]</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Lokasi Asal:</span>
                            <strong class="text-dark">${data.location}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Rencana Pengembalian:</span>
                            <strong class="text-dark">${data.expected_return_date}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Ringkasan Kuantitas:</span>
                            <span>Dipinjam: <strong>${data.borrowed}</strong> · Dikembalikan: <strong class="text-success">${data.returned}</strong> · Masih Dipinjam: <strong class="text-danger">${data.remaining} ${data.satuan}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-dark small text-uppercase mb-2">
                <i class="bi bi-calendar2-check me-1 text-primary"></i>Rincian Kelompok Tanggal Expired Barang yang Dipinjam
            </h6>
            <div class="table-responsive border rounded mb-3">
                <table class="table table-sm align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 40px;">No</th>
                            <th>Lokasi Asal</th>
                            <th>Tanggal Expired</th>
                            <th class="text-center">Dipinjam</th>
                            <th class="text-center">Dikembalikan</th>
                            <th class="text-center">Masih Dipinjam</th>
                            <th class="text-center pe-3">Status Expired</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${expiryRowsHtml}
                    </tbody>
                </table>
            </div>

            <h6 class="fw-bold text-dark small text-uppercase mb-2">
                <i class="bi bi-list-ol me-1 text-primary"></i>Tabel Kronologis Peminjaman & Pengembalian
            </h6>
            <div class="table-responsive border rounded mb-3">
                <table class="table table-sm table-striped align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Keterangan</th>
                            <th class="text-end">Jumlah</th>
                            <th>Tanggal</th>
                            <th>Lokasi</th>
                            <th>Petugas</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${timelineRowsHtml}
                    </tbody>
                </table>
            </div>

            ${dueHistoryHtml}

            <div class="row g-2 small">
                <div class="col-md-6">
                    <div class="p-2 bg-light rounded border">
                        <span class="text-muted d-block">Keperluan Pemakaian:</span>
                        <strong class="text-dark">${data.purpose || '-'}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-2 bg-light rounded border">
                        <span class="text-muted d-block">Keterangan Tambahan:</span>
                        <strong class="text-dark">${data.notes || '-'}</strong>
                    </div>
                </div>
            </div>
        `;

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailBorrowing'));
        modal.show();
    }

    // 9. Modal Detail Transaksi Mutasi
    function openTransactionDetailModal(data) {
        const container = document.getElementById('detailTransactionContent');
        container.innerHTML = `
            <div class="list-group list-group-flush small">
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Kode Transaksi:</span>
                    <strong class="font-monospace text-dark">${data.code}</strong>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Tanggal & Waktu:</span>
                    <strong class="text-dark">${data.date} (${data.time})</strong>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Nama Barang:</span>
                    <strong class="text-dark">${data.item_name} [${data.item_code}]</strong>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Kategori:</span>
                    <span>${data.category}</span>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Jenis Transaksi:</span>
                    <strong class="text-dark">${data.type}</strong>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Lokasi Penyimpanan:</span>
                    <strong class="text-primary">${data.location}</strong>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Kuantitas & Satuan:</span>
                    <strong class="fs-6 text-dark">${data.qty} ${data.satuan}</strong>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">PIC / Petugas Pencatat:</span>
                    <strong class="text-dark">${data.pic}</strong>
                </div>
                <div class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Tanggal Expired Batch:</span>
                    <span>${data.expired_date}</span>
                </div>
                <div class="list-group-item px-0">
                    <div class="text-muted mb-1">Keterangan:</div>
                    <div class="p-2 bg-light rounded border text-dark">${data.notes}</div>
                </div>
            </div>
        `;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailTransaction'));
        modal.show();
    }

    // 10. KELOLA MASTER INVENTORY (KATEGORI & SATUAN BARANG + SINKRONISASI DROPDOWN)
    let MASTER_UNITS_DATA = @json($units ?? []);
    let MASTER_CATEGORIES_DATA = @json($categories ?? []);
    let CATEGORY_RENAME_MAP = {};
    let MASTER_RETURN_MODAL_ID = null;
    let MASTER_SUSPEND_RETURN = false;
    let MASTER_EDIT_CALLBACK = null;
    let MASTER_ACTION_CALLBACK = null;

    function resolveRenamedCategory(name) {
        let current = name;
        const visited = new Set();
        while (current && CATEGORY_RENAME_MAP[current] && !visited.has(current)) {
            visited.add(current);
            current = CATEGORY_RENAME_MAP[current];
        }
        return current;
    }

    function showMasterFeedback(message, type = 'success') {
        const box = document.getElementById('masterModalFeedback');
        if (!box) return;
        const icon = type === 'success'
            ? '<i class="bi bi-check-circle-fill me-1"></i>'
            : '<i class="bi bi-exclamation-triangle-fill me-1"></i>';
        box.className = `alert alert-${type} py-2 px-3 small mb-3`;
        box.innerHTML = `${icon}${escapeHtml(message)}`;
        box.classList.remove('d-none');
    }

    function hideMasterFeedback() {
        const box = document.getElementById('masterModalFeedback');
        if (box) box.classList.add('d-none');
    }

    function openMasterSubModal(subModalId, onReady = null) {
        const masterEl = document.getElementById('modalKelolaMaster');
        const subEl = document.getElementById(subModalId);
        if (!subEl) return;

        const showSub = () => {
            const subModal = bootstrap.Modal.getOrCreateInstance(subEl);
            subModal.show();
            if (typeof onReady === 'function') {
                onReady();
            }
        };

        const masterModal = masterEl ? bootstrap.Modal.getInstance(masterEl) : null;
        if (masterModal && masterEl.classList.contains('show')) {
            MASTER_SUSPEND_RETURN = true;
            masterEl.addEventListener('hidden.bs.modal', function onMasterTempHidden() {
                showSub();
            }, { once: true });
            masterModal.hide();
        } else {
            showSub();
        }
    }

    function showMasterEditModal({ title, label, initialValue = '', maxLength = 100, onSubmit }) {
        const titleEl = document.getElementById('masterEditDialogTitle');
        const labelEl = document.getElementById('masterEditInputLabel');
        const inputEl = document.getElementById('masterEditInput');
        const errEl = document.getElementById('masterEditError');

        if (titleEl) {
            titleEl.innerHTML = `<i class="bi bi-pencil-square text-primary me-2"></i>${escapeHtml(title || 'Edit Data Master')}`;
        }
        if (labelEl) {
            labelEl.innerHTML = `${escapeHtml(label || 'Nama Baru')} <span class="text-danger">*</span>`;
        }
        if (inputEl) {
            inputEl.value = initialValue || '';
            inputEl.maxLength = maxLength;
        }
        if (errEl) {
            errEl.textContent = '';
            errEl.classList.add('d-none');
        }

        MASTER_EDIT_CALLBACK = onSubmit;
        openMasterSubModal('modalMasterEditDialog', () => {
            setTimeout(() => {
                if (inputEl) {
                    inputEl.focus();
                    inputEl.select();
                }
            }, 150);
        });
    }

    function submitMasterEditDialog(event) {
        event.preventDefault();
        const inputEl = document.getElementById('masterEditInput');
        const errEl = document.getElementById('masterEditError');
        const trimmed = (inputEl?.value || '').trim();

        if (!trimmed) {
            if (errEl) {
                errEl.textContent = 'Nama tidak boleh kosong.';
                errEl.classList.remove('d-none');
            }
            inputEl?.focus();
            return;
        }

        const cb = MASTER_EDIT_CALLBACK;
        MASTER_EDIT_CALLBACK = null;
        const editEl = document.getElementById('modalMasterEditDialog');
        const editModal = editEl ? bootstrap.Modal.getInstance(editEl) : null;

        const runCb = () => {
            if (typeof cb === 'function') cb(trimmed);
        };

        if (editModal && editEl.classList.contains('show')) {
            editEl.addEventListener('hidden.bs.modal', runCb, { once: true });
            editModal.hide();
        } else {
            runCb();
        }
    }

    function showMasterDialog({
        variant = 'confirm',
        title = 'Konfirmasi',
        badgeText = '',
        message = '',
        confirmText = 'OK',
        cancelText = 'Batal',
        showCancel = true,
        onConfirm = null,
    }) {
        const iconEl = document.getElementById('masterActionIcon');
        const titleEl = document.getElementById('masterActionTitle');
        const badgeWrap = document.getElementById('masterActionBadgeWrap');
        const badgeEl = document.getElementById('masterActionBadge');
        const msgEl = document.getElementById('masterActionMessage');
        const cancelBtn = document.getElementById('masterActionCancelBtn');
        const confirmBtn = document.getElementById('masterActionConfirmBtn');

        const variantMap = {
            success: { iconClass: 'text-success', icon: 'bi-check-circle-fill', btnClass: 'btn-success' },
            warning: { iconClass: 'text-warning', icon: 'bi-exclamation-triangle-fill', btnClass: 'btn-warning text-dark' },
            danger:  { iconClass: 'text-danger',  icon: 'bi-exclamation-triangle',      btnClass: 'btn-danger' },
            confirm: { iconClass: 'text-primary', icon: 'bi-question-circle-fill',     btnClass: 'btn-primary' },
        };
        const cfg = variantMap[variant] || variantMap.confirm;

        if (iconEl) {
            iconEl.className = `${cfg.iconClass} mb-2`;
            iconEl.innerHTML = `<i class="bi ${cfg.icon} fs-1"></i>`;
        }
        if (titleEl) titleEl.textContent = title;
        if (badgeWrap && badgeEl) {
            if (badgeText) {
                badgeEl.textContent = badgeText;
                badgeWrap.classList.remove('d-none');
            } else {
                badgeWrap.classList.add('d-none');
            }
        }
        if (msgEl) msgEl.textContent = message;
        if (cancelBtn) {
            cancelBtn.textContent = cancelText;
            cancelBtn.classList.toggle('d-none', !showCancel);
        }
        if (confirmBtn) {
            confirmBtn.textContent = confirmText;
            confirmBtn.className = `btn ${cfg.btnClass} btn-sm px-3`;
        }

        MASTER_ACTION_CALLBACK = onConfirm;
        openMasterSubModal('modalMasterActionDialog');
    }

    function switchMasterTab(tabName) {
        const tabBtnId = tabName === 'satuan' ? 'tabMasterSatuan' : 'tabMasterKategori';
        const tabBtn = document.getElementById(tabBtnId);
        if (tabBtn && window.bootstrap) {
            bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        }
    }

    function openMasterModal(defaultTab = 'kategori', returnModalId = null) {
        MASTER_RETURN_MODAL_ID = returnModalId || null;
        MASTER_SUSPEND_RETURN = false;
        hideMasterFeedback();

        const closeBtn = document.getElementById('btnCloseMasterModal');
        if (closeBtn) {
            closeBtn.textContent = MASTER_RETURN_MODAL_ID ? 'Kembali ke Form' : 'Tutup';
        }

        const showTarget = () => {
            const masterEl = document.getElementById('modalKelolaMaster');
            if (!masterEl) return;
            const masterModal = bootstrap.Modal.getOrCreateInstance(masterEl);
            masterModal.show();
            switchMasterTab(defaultTab);
            loadCategoriesData();
            loadUnitsData();
        };

        if (returnModalId) {
            const sourceEl = document.getElementById(returnModalId);
            const sourceModal = sourceEl ? bootstrap.Modal.getInstance(sourceEl) : null;
            if (sourceModal && sourceEl.classList.contains('show')) {
                sourceEl.addEventListener('hidden.bs.modal', function onSourceHidden() {
                    showTarget();
                }, { once: true });
                sourceModal.hide();
                return;
            }
        }

        showTarget();
    }

    function openMasterUnitModal() {
        openMasterModal('satuan', null);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const masterEl = document.getElementById('modalKelolaMaster');
        if (masterEl) {
            masterEl.addEventListener('hidden.bs.modal', function() {
                if (MASTER_SUSPEND_RETURN) {
                    return;
                }
                if (MASTER_RETURN_MODAL_ID) {
                    const targetId = MASTER_RETURN_MODAL_ID;
                    MASTER_RETURN_MODAL_ID = null;
                    const targetEl = document.getElementById(targetId);
                    if (targetEl) {
                        bootstrap.Modal.getOrCreateInstance(targetEl).show();
                    }
                }
            });
        }

        const restoreMasterModal = () => {
            if (MASTER_SUSPEND_RETURN && masterEl) {
                MASTER_SUSPEND_RETURN = false;
                bootstrap.Modal.getOrCreateInstance(masterEl).show();
            }
        };

        const editDialogEl = document.getElementById('modalMasterEditDialog');
        if (editDialogEl) {
            editDialogEl.addEventListener('hidden.bs.modal', function() {
                // Jika ditutup via Batal / X tanpa submit, kembalikan modal Kelola Master
                if (!MASTER_EDIT_CALLBACK && MASTER_SUSPEND_RETURN) {
                    restoreMasterModal();
                } else if (MASTER_EDIT_CALLBACK) {
                    MASTER_EDIT_CALLBACK = null;
                    restoreMasterModal();
                }
            });
        }

        const actionDialogEl = document.getElementById('modalMasterActionDialog');
        if (actionDialogEl) {
            const confirmBtn = document.getElementById('masterActionConfirmBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    const cb = MASTER_ACTION_CALLBACK;
                    MASTER_ACTION_CALLBACK = null;
                    const inst = bootstrap.Modal.getInstance(actionDialogEl);
                    if (inst && actionDialogEl.classList.contains('show')) {
                        actionDialogEl.addEventListener('hidden.bs.modal', function() {
                            if (typeof cb === 'function') {
                                cb();
                            } else {
                                restoreMasterModal();
                            }
                        }, { once: true });
                        inst.hide();
                    }
                });
            }

            actionDialogEl.addEventListener('hidden.bs.modal', function() {
                // Jika ditutup via Batal / X, kembalikan modal Kelola Master
                if (MASTER_ACTION_CALLBACK !== null) {
                    MASTER_ACTION_CALLBACK = null;
                    restoreMasterModal();
                } else if (MASTER_SUSPEND_RETURN) {
                    restoreMasterModal();
                }
            });
        }
    });

    // --- TAB 1: KELOLA KATEGORI ---
    function loadCategoriesData(renamedOld = null, renamedNew = null) {
        fetch('{{ route('inventory.categories.index') }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && Array.isArray(res.data)) {
                MASTER_CATEGORIES_DATA = res.data;
                renderKategoriTable();
                syncKategoriDropdowns(renamedOld, renamedNew);
            }
        })
        .catch(err => {
            console.error('Gagal memuat data kategori:', err);
            renderKategoriTable();
        });
    }

    function renderKategoriTable() {
        const tbody = document.getElementById('kategoriTableBody');
        const countBadge = document.getElementById('kategoriCountBadge');
        const searchInput = document.getElementById('searchKategoriInput');
        if (!tbody) return;

        const kw = (searchInput?.value || '').trim().toLowerCase();
        const filtered = MASTER_CATEGORIES_DATA.filter(c => !kw || (c.name || '').toLowerCase().includes(kw));

        if (countBadge) {
            countBadge.textContent = `Total: ${filtered.length} Kategori`;
        }

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted small">
                        <i class="bi bi-info-circle me-1"></i>Tidak ada kategori yang cocok.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map((c, idx) => {
            const isAktif = !!c.is_active;
            const statusBadge = isAktif
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>'
                : '<span class="badge bg-secondary-subtle text-secondary border">Nonaktif</span>';
            const usageCount = parseInt(c.usage_count, 10) || 0;
            const usageBadge = usageCount > 0
                ? `<span class="badge bg-light text-dark border font-monospace" title="${usageCount} barang menggunakan kategori ini">${usageCount} barang</span>`
                : `<span class="badge bg-light text-muted border font-monospace">0 barang</span>`;

            return `
                <tr data-category-id="${c.id}">
                    <td class="text-center text-muted small">${idx + 1}</td>
                    <td>
                        <strong class="text-dark small">${escapeHtml(c.name)}</strong>
                    </td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">${usageBadge}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button"
                                    class="btn btn-outline-secondary py-0 px-2"
                                    title="Edit Nama Kategori"
                                    onclick="promptEditKategori(${c.id}, '${escapeJs(c.name)}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button"
                                    class="btn ${isAktif ? 'btn-outline-warning' : 'btn-outline-success'} py-0 px-2"
                                    title="${isAktif ? 'Nonaktifkan Kategori' : 'Aktifkan Kategori'}"
                                    onclick="toggleKategoriStatus(${c.id}, '${escapeJs(c.name)}', ${isAktif ? 'true' : 'false'})">
                                <i class="bi ${isAktif ? 'bi-toggle-on' : 'bi-toggle-off'}"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-outline-danger py-0 px-2"
                                    title="Hapus Kategori"
                                    onclick="confirmDeleteKategori(${c.id}, '${escapeJs(c.name)}', ${usageCount})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function submitAddKategori(event) {
        event.preventDefault();
        const input = document.getElementById('newKategoriNameInput');
        const btn = document.getElementById('btnSubmitAddKategori');
        const name = (input?.value || '').trim();

        if (!name) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        fetch('{{ route('inventory.categories.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ name: name })
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Gagal menambahkan kategori.');
            }
            return data;
        })
        .then(data => {
            input.value = '';
            const msg = data.message || 'Kategori berhasil ditambahkan.';
            showMasterFeedback(msg, 'success');
            loadCategoriesData();
            showMasterDialog({
                variant: 'success',
                title: 'Kategori Ditambahkan',
                message: msg,
                confirmText: 'OK',
                showCancel: false
            });
        })
        .catch(err => {
            showMasterFeedback(err.message, 'danger');
            showMasterDialog({
                variant: 'danger',
                title: 'Gagal Menambahkan Kategori',
                message: err.message,
                confirmText: 'Tutup',
                showCancel: false
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Tambah';
        });
    }

    function promptEditKategori(categoryId, currentName) {
        showMasterEditModal({
            title: 'Edit Nama Kategori',
            label: 'Nama Kategori',
            initialValue: currentName,
            maxLength: 100,
            onSubmit: (trimmed) => {
                if (trimmed === currentName) {
                    const masterEl = document.getElementById('modalKelolaMaster');
                    if (MASTER_SUSPEND_RETURN && masterEl) {
                        MASTER_SUSPEND_RETURN = false;
                        bootstrap.Modal.getOrCreateInstance(masterEl).show();
                    }
                    return;
                }

                fetch(`{{ url('inventory/categories') }}/${categoryId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name: trimmed })
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Gagal memperbarui kategori.');
                    }
                    return data;
                })
                .then(data => {
                    const oldName = data.old_name || currentName;
                    const updatedName = data.data?.name || trimmed;
                    if (oldName !== updatedName) {
                        CATEGORY_RENAME_MAP[oldName] = updatedName;
                    }
                    const msg = data.message || 'Kategori berhasil diperbarui.';
                    showMasterFeedback(msg, 'success');
                    loadCategoriesData(oldName, updatedName);
                    showMasterDialog({
                        variant: 'success',
                        title: 'Kategori Diperbarui',
                        badgeText: updatedName,
                        message: msg,
                        confirmText: 'OK',
                        showCancel: false
                    });
                })
                .catch(err => {
                    showMasterFeedback(err.message, 'danger');
                    showMasterDialog({
                        variant: 'danger',
                        title: 'Gagal Memperbarui Kategori',
                        message: err.message,
                        confirmText: 'Tutup',
                        showCancel: false
                    });
                });
            }
        });
    }

    function toggleKategoriStatus(categoryId, categoryName = '', isCurrentlyActive = true, skipConfirm = false) {
        const executeToggle = () => {
            fetch(`{{ url('inventory/categories') }}/${categoryId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Gagal mengubah status kategori.');
                }
                return data;
            })
            .then(data => {
                const msg = data.message || 'Status kategori berhasil diperbarui.';
                showMasterFeedback(msg, 'success');
                loadCategoriesData();
                showMasterDialog({
                    variant: 'success',
                    title: 'Status Kategori Diperbarui',
                    message: msg,
                    confirmText: 'OK',
                    showCancel: false
                });
            })
            .catch(err => {
                showMasterFeedback(err.message, 'danger');
                showMasterDialog({
                    variant: 'danger',
                    title: 'Gagal Mengubah Status',
                    message: err.message,
                    confirmText: 'Tutup',
                    showCancel: false
                });
            });
        };

        if (skipConfirm) {
            executeToggle();
            return;
        }

        const actionLabel = isCurrentlyActive ? 'Nonaktifkan' : 'Aktifkan';
        showMasterDialog({
            variant: isCurrentlyActive ? 'warning' : 'confirm',
            title: `${actionLabel} Kategori`,
            badgeText: categoryName,
            message: isCurrentlyActive
                ? 'Kategori yang dinonaktifkan tidak akan muncul pada pilihan input baru, namun data barang lama tetap aman.'
                : 'Kategori akan diaktifkan kembali dan tersedia pada pilihan dropdown input barang.',
            confirmText: `Ya, ${actionLabel}`,
            cancelText: 'Batal',
            showCancel: true,
            onConfirm: executeToggle
        });
    }

    function confirmDeleteKategori(categoryId, categoryName, usageCount) {
        if (usageCount > 0) {
            const warnMsg = `Kategori "${categoryName}" tidak dapat dihapus karena masih digunakan oleh ${usageCount} data barang. Anda dapat menonaktifkannya agar tidak muncul pada pilihan input baru.`;
            showMasterFeedback(warnMsg, 'warning');
            showMasterDialog({
                variant: 'warning',
                title: 'Kategori Masih Digunakan',
                badgeText: `${categoryName} (${usageCount} barang)`,
                message: 'Kategori yang masih digunakan oleh barang tidak boleh dihapus. Apakah Anda ingin menonaktifkannya saja?',
                confirmText: 'Nonaktifkan Saja',
                cancelText: 'Tutup',
                showCancel: true,
                onConfirm: () => toggleKategoriStatus(categoryId, categoryName, true, true)
            });
            return;
        }

        showMasterDialog({
            variant: 'danger',
            title: 'Hapus Kategori',
            badgeText: categoryName,
            message: 'Apakah Anda yakin ingin menghapus kategori ini secara permanen?',
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            showCancel: true,
            onConfirm: () => {
                fetch(`{{ url('inventory/categories') }}/${categoryId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        if (data.can_deactivate) {
                            showMasterFeedback(data.message, 'warning');
                            showMasterDialog({
                                variant: 'warning',
                                title: 'Kategori Masih Digunakan',
                                badgeText: categoryName,
                                message: data.message + ' Apakah Anda ingin menonaktifkannya saja?',
                                confirmText: 'Nonaktifkan Saja',
                                cancelText: 'Tutup',
                                showCancel: true,
                                onConfirm: () => toggleKategoriStatus(categoryId, categoryName, true, true)
                            });
                            return null;
                        }
                        throw new Error(data.message || 'Gagal menghapus kategori.');
                    }
                    return data;
                })
                .then(data => {
                    if (data) {
                        const msg = data.message || 'Kategori berhasil dihapus.';
                        showMasterFeedback(msg, 'success');
                        loadCategoriesData();
                        showMasterDialog({
                            variant: 'success',
                            title: 'Kategori Dihapus',
                            message: msg,
                            confirmText: 'OK',
                            showCancel: false
                        });
                    }
                })
                .catch(err => {
                    showMasterFeedback(err.message, 'danger');
                    showMasterDialog({
                        variant: 'danger',
                        title: 'Gagal Menghapus Kategori',
                        message: err.message,
                        confirmText: 'Tutup',
                        showCancel: false
                    });
                });
            }
        });
    }

    function syncKategoriDropdowns(renamedOld = null, renamedNew = null) {
        const activeNames = MASTER_CATEGORIES_DATA.filter(c => !!c.is_active).map(c => c.name);
        if (activeNames.length === 0) return;

        // Perbarui select pada Modal Tambah Master Barang
        const addSelect = document.getElementById('addMasterKategoriSelect');
        if (addSelect) {
            let curVal = addSelect.value;
            if (renamedOld && renamedNew && curVal === renamedOld) {
                curVal = renamedNew;
            }
            addSelect.innerHTML = activeNames.map(n => `<option value="${escapeHtml(n)}" ${n === curVal ? 'selected' : ''}>${escapeHtml(n)}</option>`).join('');
            if (curVal && activeNames.includes(curVal)) {
                addSelect.value = curVal;
            }
        }

        // Perbarui select pada Modal Edit Master Barang
        const editSelect = document.getElementById('editKategori');
        if (editSelect) {
            let curVal = editSelect.value;
            if (renamedOld && renamedNew && curVal === renamedOld) {
                curVal = renamedNew;
            }
            editSelect.innerHTML = activeNames.map(n => `<option value="${escapeHtml(n)}" ${n === curVal ? 'selected' : ''}>${escapeHtml(n)}</option>`).join('');
            if (curVal && !activeNames.includes(curVal)) {
                editSelect.add(new Option(curVal, curVal, true, true));
            } else if (curVal) {
                editSelect.value = curVal;
            }
        }

        // Perbarui dropdown filter kategori pada tab Master & Riwayat Transaksi
        document.querySelectorAll('select[name="filter_category"], select[name="trx_kategori"]').forEach(sel => {
            let curVal = sel.value;
            if (renamedOld && renamedNew && curVal === renamedOld) {
                curVal = renamedNew;
            }
            const opts = ['<option value="">Semua Kategori</option>']
                .concat(activeNames.map(n => `<option value="${escapeHtml(n)}" ${n === curVal ? 'selected' : ''}>${escapeHtml(n)}</option>`));
            sel.innerHTML = opts.join('');
            if (curVal) sel.value = curVal;
        });

        // Perbarui tampilan badge kategori di tabel jika ada perubahan nama
        if (renamedOld && renamedNew && renamedOld !== renamedNew) {
            document.querySelectorAll('.badge-kategori').forEach(el => {
                if (el.textContent.trim() === renamedOld) {
                    el.textContent = renamedNew;
                }
            });
        }
    }

    // --- TAB 2: KELOLA SATUAN (Tetap menggunakan mekanisme yang sudah berjalan) ---
    function loadUnitsData() {
        fetch('{{ route('inventory.units.index') }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && Array.isArray(res.data)) {
                MASTER_UNITS_DATA = res.data;
                renderSatuanTable();
                syncSatuanDropdowns();
            }
        })
        .catch(err => {
            console.error('Gagal memuat data satuan:', err);
            renderSatuanTable();
        });
    }

    function renderSatuanTable() {
        const tbody = document.getElementById('satuanTableBody');
        const countBadge = document.getElementById('satuanCountBadge');
        const searchInput = document.getElementById('searchSatuanInput');
        if (!tbody) return;

        const kw = (searchInput?.value || '').trim().toLowerCase();
        const filtered = MASTER_UNITS_DATA.filter(u => !kw || (u.name || '').toLowerCase().includes(kw));

        if (countBadge) {
            countBadge.textContent = `Total: ${filtered.length} Satuan`;
        }

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted small">
                        <i class="bi bi-info-circle me-1"></i>Tidak ada satuan yang cocok.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map((u, idx) => {
            const isAktif = !!u.is_active;
            const statusBadge = isAktif
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>'
                : '<span class="badge bg-secondary-subtle text-secondary border">Nonaktif</span>';
            const usageCount = parseInt(u.usage_count, 10) || 0;
            const usageBadge = usageCount > 0
                ? `<span class="badge bg-light text-dark border font-monospace" title="${usageCount} kali digunakan pada data master / transaksi">${usageCount}x dipakai</span>`
                : `<span class="badge bg-light text-muted border font-monospace">0x dipakai</span>`;

            return `
                <tr data-unit-id="${u.id}">
                    <td class="text-center text-muted small">${idx + 1}</td>
                    <td>
                        <strong class="text-dark small">${escapeHtml(u.name)}</strong>
                    </td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">${usageBadge}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button"
                                    class="btn btn-outline-secondary py-0 px-2"
                                    title="Edit Nama Satuan"
                                    onclick="promptEditSatuan(${u.id}, '${escapeJs(u.name)}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button"
                                    class="btn ${isAktif ? 'btn-outline-warning' : 'btn-outline-success'} py-0 px-2"
                                    title="${isAktif ? 'Nonaktifkan Satuan' : 'Aktifkan Satuan'}"
                                    onclick="toggleSatuanStatus(${u.id}, '${escapeJs(u.name)}', ${isAktif ? 'true' : 'false'})">
                                <i class="bi ${isAktif ? 'bi-toggle-on' : 'bi-toggle-off'}"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-outline-danger py-0 px-2"
                                    title="Hapus Satuan"
                                    onclick="confirmDeleteSatuan(${u.id}, '${escapeJs(u.name)}', ${usageCount})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function submitAddSatuan(event) {
        event.preventDefault();
        const input = document.getElementById('newSatuanNameInput');
        const btn = document.getElementById('btnSubmitAddSatuan');
        const name = (input?.value || '').trim();

        if (!name) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        fetch('{{ route('inventory.units.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ name: name })
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Gagal menambahkan satuan.');
            }
            return data;
        })
        .then(data => {
            input.value = '';
            const msg = data.message || 'Satuan berhasil ditambahkan.';
            showMasterFeedback(msg, 'success');
            loadUnitsData();
            showMasterDialog({
                variant: 'success',
                title: 'Satuan Ditambahkan',
                message: msg,
                confirmText: 'OK',
                showCancel: false
            });
        })
        .catch(err => {
            showMasterFeedback(err.message, 'danger');
            showMasterDialog({
                variant: 'danger',
                title: 'Gagal Menambahkan Satuan',
                message: err.message,
                confirmText: 'Tutup',
                showCancel: false
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Tambah';
        });
    }

    function promptEditSatuan(unitId, currentName) {
        showMasterEditModal({
            title: 'Edit Nama Satuan',
            label: 'Nama Satuan',
            initialValue: currentName,
            maxLength: 50,
            onSubmit: (trimmed) => {
                if (trimmed === currentName) {
                    const masterEl = document.getElementById('modalKelolaMaster');
                    if (MASTER_SUSPEND_RETURN && masterEl) {
                        MASTER_SUSPEND_RETURN = false;
                        bootstrap.Modal.getOrCreateInstance(masterEl).show();
                    }
                    return;
                }

                fetch(`{{ url('inventory/units') }}/${unitId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name: trimmed })
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Gagal memperbarui satuan.');
                    }
                    return data;
                })
                .then(data => {
                    const msg = data.message || 'Satuan berhasil diperbarui.';
                    showMasterFeedback(msg, 'success');
                    loadUnitsData();
                    showMasterDialog({
                        variant: 'success',
                        title: 'Satuan Diperbarui',
                        badgeText: data.data?.name || trimmed,
                        message: msg,
                        confirmText: 'OK',
                        showCancel: false
                    });
                })
                .catch(err => {
                    showMasterFeedback(err.message, 'danger');
                    showMasterDialog({
                        variant: 'danger',
                        title: 'Gagal Memperbarui Satuan',
                        message: err.message,
                        confirmText: 'Tutup',
                        showCancel: false
                    });
                });
            }
        });
    }

    function toggleSatuanStatus(unitId, unitName = '', isCurrentlyActive = true, skipConfirm = false) {
        const executeToggle = () => {
            fetch(`{{ url('inventory/units') }}/${unitId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Gagal mengubah status satuan.');
                }
                return data;
            })
            .then(data => {
                const msg = data.message || 'Status satuan berhasil diperbarui.';
                showMasterFeedback(msg, 'success');
                loadUnitsData();
                showMasterDialog({
                    variant: 'success',
                    title: 'Status Satuan Diperbarui',
                    message: msg,
                    confirmText: 'OK',
                    showCancel: false
                });
            })
            .catch(err => {
                showMasterFeedback(err.message, 'danger');
                showMasterDialog({
                    variant: 'danger',
                    title: 'Gagal Mengubah Status',
                    message: err.message,
                    confirmText: 'Tutup',
                    showCancel: false
                });
            });
        };

        if (skipConfirm) {
            executeToggle();
            return;
        }

        const actionLabel = isCurrentlyActive ? 'Nonaktifkan' : 'Aktifkan';
        showMasterDialog({
            variant: isCurrentlyActive ? 'warning' : 'confirm',
            title: `${actionLabel} Satuan`,
            badgeText: unitName,
            message: isCurrentlyActive
                ? 'Satuan yang dinonaktifkan tidak akan muncul pada pilihan input baru, namun data barang dan transaksi lama tetap aman.'
                : 'Satuan akan diaktifkan kembali dan tersedia pada pilihan dropdown input barang.',
            confirmText: `Ya, ${actionLabel}`,
            cancelText: 'Batal',
            showCancel: true,
            onConfirm: executeToggle
        });
    }

    function confirmDeleteSatuan(unitId, unitName, usageCount) {
        if (usageCount > 0) {
            const warnMsg = `Satuan "${unitName}" tidak dapat dihapus permanen karena sedang digunakan (${usageCount}x pemakaian pada barang/transaksi). Anda dapat menonaktifkannya agar tidak muncul pada pilihan input baru.`;
            showMasterFeedback(warnMsg, 'warning');
            showMasterDialog({
                variant: 'warning',
                title: 'Satuan Masih Digunakan',
                badgeText: `${unitName} (${usageCount}x dipakai)`,
                message: 'Satuan yang masih digunakan oleh barang/transaksi tidak boleh dihapus permanen. Apakah Anda ingin menonaktifkannya saja?',
                confirmText: 'Nonaktifkan Saja',
                cancelText: 'Tutup',
                showCancel: true,
                onConfirm: () => toggleSatuanStatus(unitId, unitName, true, true)
            });
            return;
        }

        showMasterDialog({
            variant: 'danger',
            title: 'Hapus Satuan',
            badgeText: unitName,
            message: 'Apakah Anda yakin ingin menghapus satuan ini secara permanen?',
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            showCancel: true,
            onConfirm: () => {
                fetch(`{{ url('inventory/units') }}/${unitId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        if (data.can_deactivate) {
                            showMasterFeedback(data.message, 'warning');
                            showMasterDialog({
                                variant: 'warning',
                                title: 'Satuan Masih Digunakan',
                                badgeText: unitName,
                                message: data.message + ' Apakah Anda ingin menonaktifkannya saja?',
                                confirmText: 'Nonaktifkan Saja',
                                cancelText: 'Tutup',
                                showCancel: true,
                                onConfirm: () => toggleSatuanStatus(unitId, unitName, true, true)
                            });
                            return null;
                        }
                        throw new Error(data.message || 'Gagal menghapus satuan.');
                    }
                    return data;
                })
                .then(data => {
                    if (data) {
                        const msg = data.message || 'Satuan berhasil dihapus.';
                        showMasterFeedback(msg, 'success');
                        loadUnitsData();
                        showMasterDialog({
                            variant: 'success',
                            title: 'Satuan Dihapus',
                            message: msg,
                            confirmText: 'OK',
                            showCancel: false
                        });
                    }
                })
                .catch(err => {
                    showMasterFeedback(err.message, 'danger');
                    showMasterDialog({
                        variant: 'danger',
                        title: 'Gagal Menghapus Satuan',
                        message: err.message,
                        confirmText: 'Tutup',
                        showCancel: false
                    });
                });
            }
        });
    }

    function syncSatuanDropdowns() {
        const activeNames = MASTER_UNITS_DATA.filter(u => !!u.is_active).map(u => u.name);
        if (activeNames.length === 0) return;

        // Perbarui SATUAN_OPTIONS
        SATUAN_OPTIONS.length = 0;
        activeNames.forEach(n => SATUAN_OPTIONS.push(n));

        // Perbarui select pada Modal Tambah Master Barang
        const addSelect = document.getElementById('addMasterSatuanSelect');
        if (addSelect) {
            const curVal = addSelect.value;
            addSelect.innerHTML = activeNames.map(n => `<option value="${escapeHtml(n)}" ${n === curVal ? 'selected' : ''}>${escapeHtml(n)}</option>`).join('');
        }

        // Perbarui select pada Modal Edit Master Barang
        const editSelect = document.getElementById('editSatuan') || document.getElementById('editMasterSatuanSelect');
        if (editSelect) {
            const curVal = editSelect.value;
            editSelect.innerHTML = activeNames.map(n => `<option value="${escapeHtml(n)}" ${n === curVal ? 'selected' : ''}>${escapeHtml(n)}</option>`).join('');
        }

        // Perbarui select satuan di tabel transaksi
        document.querySelectorAll('#trxItemsContainer .trx-satuan-select').forEach(sel => {
            const cur = sel.value;
            sel.innerHTML = activeNames.map(n => `<option value="${escapeHtml(n)}" ${n === cur ? 'selected' : ''}>${escapeHtml(n)}</option>`).join('');
            if (cur) sel.value = cur;
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    function escapeJs(str) {
        if (!str) return '';
        return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
    }
</script>
@endpush
