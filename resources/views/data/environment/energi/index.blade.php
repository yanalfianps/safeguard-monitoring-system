@extends('layouts.app', ['activeModule' => 'environment-energi', 'title' => 'Environment - Energi'])

@push('styles')
<style>
    .env-header-icon {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
        color: #ffffff;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(15, 118, 110, 0.2);
    }
    .custom-file-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .custom-file-dropzone:hover, .custom-file-dropzone.dragover {
        border-color: #0f766e;
        background: #f0fdfa;
    }
    .attachment-scroll-list {
        max-height: 180px;
        overflow-y: auto;
    }
    .hover-danger:hover {
        color: #dc2626 !important;
    }
    .nav-tabs-energy .nav-link {
        font-weight: 600;
        color: #64748b;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 10px 18px;
    }
    .nav-tabs-energy .nav-link.active {
        color: #0f766e;
        background: transparent;
        border-bottom-color: #0f766e;
    }
    .energy-card-stat {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
    }
    .btn-teal-primary {
        background-color: #0f766e;
        border-color: #0f766e;
        color: #ffffff;
    }
    .btn-teal-primary:hover {
        background-color: #115e59;
        border-color: #115e59;
        color: #ffffff;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    <!-- HEADER -->
    <x-module-header
        title="Energi — BBM & Listrik"
        icon="bi bi-lightning-charge"
        icon-bg="linear-gradient(135deg, #0f766e 0%, #0d9488 100%)"
        icon-shadow="rgba(15, 118, 110, 0.22)"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Environment', 'url' => route('environment.index')],
            ['label' => 'Energi'],
        ]"
    >
        <x-slot:actions>
            <form method="GET" action="{{ route('environment.energi.index') }}" class="hsse-header-inline-form">
                <input type="hidden" name="tab" value="{{ $type }}">
                <label>Tahun:</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('environment.energi.export-pdf', ['tab' => $type, 'year' => $year]) }}" class="btn btn-danger shadow-none">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            @adminManagement
            <button type="button" class="btn btn-teal-primary hsse-action-primary shadow-none" data-bs-toggle="modal" data-bs-target="#modalTambahEnergi">
                <i class="bi bi-plus-lg"></i> Catat Pemakaian {{ strtoupper($type) }}
            </button>
            @endadminManagement
        </x-slot:actions>
    </x-module-header>

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
    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- TABS BBM & LISTRIK -->
    <ul class="nav nav-tabs nav-tabs-energy flex-wrap mb-3 border-bottom">
        <li class="nav-item">
            <a class="nav-link {{ $type === 'bbm' ? 'active' : '' }}" href="{{ route('environment.energi.index', ['tab' => 'bbm', 'year' => $year]) }}">
                <i class="bi bi-fuel-pump me-1 text-warning"></i> Bahan Bakar Minyak (BBM) — Liter
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $type === 'listrik' ? 'active' : '' }}" href="{{ route('environment.energi.index', ['tab' => 'listrik', 'year' => $year]) }}">
                <i class="bi bi-lightning-charge me-1 text-primary"></i> Konsumsi Listrik — kWh
            </a>
        </li>
    </ul>

    <!-- RINGKASAN TAHUNAN & GRAFIK TREN -->
    <div class="row g-3 mb-4">
        <!-- Statistik Kartu -->
        <div class="col-lg-4">
            <div class="d-flex flex-column gap-2 h-100">
                <div class="energy-card-stat flex-fill">
                    <div class="small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Total Pemakaian Tahun {{ $year }}</div>
                    <div class="fs-4 fw-bold {{ $type === 'bbm' ? 'text-warning' : 'text-primary' }} mt-1">
                        {{ number_format($totalUsage, 2, ',', '.') }} <span class="fs-6 fw-normal text-muted">{{ $type === 'bbm' ? 'Liter' : 'kWh' }}</span>
                    </div>
                    <div class="text-muted small mt-1">Akumulasi seluruh bulan yang telah tercatat</div>
                </div>
                <div class="energy-card-stat flex-fill">
                    <div class="small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Rata-rata Bulanan</div>
                    <div class="fs-5 fw-bold text-dark mt-1">
                        {{ number_format($averageMonthly, 2, ',', '.') }} <span class="fs-6 fw-normal text-muted">{{ $type === 'bbm' ? 'Liter' : 'kWh' }}</span>
                    </div>
                    <div class="text-muted small mt-1">Rerata per bulan aktif</div>
                </div>
                <div class="energy-card-stat flex-fill">
                    <div class="small fw-bold text-secondary text-uppercase" style="font-size: 11px;">Pemakaian Tertinggi</div>
                    <div class="fs-5 fw-bold text-danger mt-1">
                        {{ number_format($maxUsage, 2, ',', '.') }} <span class="fs-6 fw-normal text-muted">{{ $type === 'bbm' ? 'Liter' : 'kWh' }}</span>
                    </div>
                    <div class="text-muted small mt-1">Puncak pemakaian dalam satu bulan</div>
                </div>
            </div>
        </div>

        <!-- Grafik Tren Pemakaian -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-2 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="fw-bold small text-dark"><i class="bi bi-graph-up me-1"></i> Tren Pemakaian {{ strtoupper($type) }} (Januari–Desember {{ $year }})</span>
                    <span class="badge {{ $type === 'bbm' ? 'bg-warning-subtle text-warning' : 'bg-primary-subtle text-primary' }}">
                        Satuan: {{ $type === 'bbm' ? 'Liter' : 'kWh' }}
                    </span>
                </div>
                <div class="card-body p-3">
                    <div style="position: relative; width: 100%; height: 220px;">
                        <canvas id="energyTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL REKAP BULANAN -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-table me-1"></i> Rincian Pencatatan Pemakaian {{ strtoupper($type) }} — Tahun {{ $year }}
            </h6>
            <span class="small text-muted">{{ $records->count() }} dari 12 Bulan Tercatat</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 60px;">Bulan</th>
                            <th>Periode</th>
                            <th>Jumlah Pemakaian</th>
                            <th>Satuan</th>
                            <th>Keterangan</th>
                            <th>Lampiran Dokumen</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $r = $records->firstWhere('month', $m);
                            @endphp
                            <tr class="{{ $r ? '' : 'table-light text-muted' }}">
                                <td class="text-center fw-bold">{{ $m }}</td>
                                <td class="fw-semibold">
                                    {{ $monthNames[$m] }} {{ $year }}
                                </td>
                                <td>
                                    @if($r)
                                        <span class="fw-bold {{ $type === 'bbm' ? 'text-warning' : 'text-primary' }} fs-6">
                                            {{ number_format($r->usage_amount, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-muted fst-italic">Belum dicatat</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $type === 'bbm' ? 'Liter' : 'kWh' }}</span>
                                </td>
                                <td>
                                    {{ $r?->description ?? '-' }}
                                </td>
                                <td>
                                    @if($r && count($r->attachment_list) > 0)
                                        <div class="dropdown">
                                            <button class="btn btn-xs btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size: 11.5px;">
                                                <i class="bi bi-paperclip me-1"></i> {{ count($r->attachment_list) }} Dokumen
                                            </button>
                                            <ul class="dropdown-menu shadow-sm" style="font-size: 12px; max-width: 280px;">
                                                @foreach($r->attachment_list as $fIdx => $filePath)
                                                    <li>
                                                        <a class="dropdown-item text-truncate d-flex align-items-center gap-2 py-1" href="{{ route('environment.files.show', ['module' => 'energi', 'id' => $r->id, 'index' => $fIdx]) }}" target="_blank">
                                                            <i class="bi bi-file-earmark-text text-primary"></i>
                                                            <span class="text-truncate">{{ basename($filePath) }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($r)
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Detail"
                                                    onclick="openDetailEnergyModal({{ json_encode($r) }})">
                                                <i class="bi bi-eye text-primary"></i>
                                            </button>
                                            @adminManagement
                                            <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Edit"
                                                    onclick="openEditEnergyModal({{ json_encode($r) }})">
                                                <i class="bi bi-pencil-square text-warning"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Hapus"
                                                    onclick="confirmDeleteEnergy({{ $r->id }}, {{ \Illuminate\Support\Js::from($monthNames[$m] . ' ' . $year) }})">
                                                <i class="bi bi-trash3 text-danger"></i>
                                            </button>
                                            @endadminManagement
                                        </div>
                                    @else
                                        @adminManagement
                                        <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2"
                                                onclick="openCreateEnergyForMonth({{ $m }}, '{{ $monthNames[$m] }}')">
                                            <i class="bi bi-plus-circle me-1"></i> Isi Data
                                        </button>
                                        @endadminManagement
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">Total Pemakaian {{ $year }}:</td>
                            <td class="{{ $type === 'bbm' ? 'text-warning' : 'text-primary' }} fs-6">
                                {{ number_format($totalUsage, 2, ',', '.') }}
                            </td>
                            <td>{{ $type === 'bbm' ? 'Liter' : 'kWh' }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL TAMBAH DATA ENERGI -->
<!-- ========================================================================= -->
@adminManagement
<div class="modal fade" id="modalTambahEnergi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('environment.energi.store') }}" method="POST" enctype="multipart/form-data" id="formTambahEnergi">
                @csrf
                <input type="hidden" name="energy_type" value="{{ $type }}">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi {{ $type === 'bbm' ? 'bi-fuel-pump text-warning' : 'bi-lightning-charge text-primary' }} me-1"></i>
                        Catat Pemakaian {{ strtoupper($type) }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bulan <span class="text-danger">*</span></label>
                            <select name="month" id="tambahEnergyMonth" class="form-select form-select-sm" required>
                                @foreach($monthNames as $mNum => $mLabel)
                                    <option value="{{ $mNum }}" {{ $mNum == date('n') ? 'selected' : '' }}>{{ $mLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="year" id="tambahEnergyYear" class="form-control form-control-sm" value="{{ $year }}" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Jumlah Pemakaian <span class="text-danger">*</span></label>
                            <input type="number" step="any" min="0.01" name="usage_amount" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Satuan</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $type === 'bbm' ? 'Liter' : 'kWh' }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan / Catatan</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Catatan pemakaian genset, kendaraan operasional, atau gardu"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Dokumen Pendukung (Struk/Invoice/Logsheet)</label>
                            @include('data.environment.partials.uploader', [
                                'containerId' => 'energyCreateAttachmentInputs',
                                'listContainerId' => 'energyCreateFileList',
                                'inputName' => 'attachments[]',
                                'maxSizeMb' => 5
                            ])
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-teal-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Pemakaian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDIT DATA ENERGI -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditEnergi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST" enctype="multipart/form-data" id="formEditEnergi">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-warning me-1"></i> Edit Data Pemakaian {{ strtoupper($type) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bulan <span class="text-danger">*</span></label>
                            <select name="month" id="editEnergyMonth" class="form-select form-select-sm" required>
                                @foreach($monthNames as $mNum => $mLabel)
                                    <option value="{{ $mNum }}">{{ $mLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="year" id="editEnergyYear" class="form-control form-control-sm" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Jumlah Pemakaian <span class="text-danger">*</span></label>
                            <input type="number" step="any" min="0.01" name="usage_amount" id="editEnergyAmount" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Satuan</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $type === 'bbm' ? 'Liter' : 'kWh' }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan / Catatan</label>
                            <textarea name="description" id="editEnergyDescription" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Pengelolaan Dokumen / Lampiran</label>
                            <div id="editEnergyExistingFilesContainer"></div>

                            @include('data.environment.partials.uploader', [
                                'containerId' => 'energyEditAttachmentInputs',
                                'listContainerId' => 'energyEditFileList',
                                'inputName' => 'attachments[]',
                                'maxSizeMb' => 5
                            ])
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-teal-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FORM HAPUS GLOBAL -->
<form id="globalDeleteFormEnergy" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endadminManagement

<!-- ========================================================================= -->
<!-- MODAL DETAIL DATA ENERGI -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDetailEnergi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                    <i class="bi bi-info-circle" style="color: #0f766e;"></i> Rincian Pemakaian {{ strtoupper($type) }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                    <tr>
                        <th class="text-secondary" style="width: 150px;">Kategori Energi</th>
                        <td class="fw-bold" id="detailEnergyType">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Periode Bulan</th>
                        <td id="detailEnergyPeriod">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Jumlah Pemakaian</th>
                        <td class="fw-bold {{ $type === 'bbm' ? 'text-warning' : 'text-primary' }} fs-6" id="detailEnergyAmount">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Satuan</th>
                        <td id="detailEnergyUnit">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Keterangan</th>
                        <td id="detailEnergyDesc">-</td>
                    </tr>
                </table>
                <div class="mt-3 pt-2 border-top">
                    <div class="small fw-bold text-secondary mb-2">Lampiran Dokumen:</div>
                    <div id="detailEnergyFilesList"></div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('data.environment.partials.upload-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const isBbm = '{{ $type }}' === 'bbm';
        const color = isBbm ? '#d97706' : '#2563eb';
        const unit = isBbm ? ' Liter' : ' kWh';

        const ctx = document.getElementById('energyTrendChart');
        if (ctx) {
            const isMobile = window.innerWidth < 576;
            new Chart(ctx, {
                type: isBbm ? 'line' : 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Pemakaian ' + (isBbm ? 'BBM (Liter)' : 'Listrik (kWh)'),
                        data: {!! json_encode(array_values($trendData)) !!},
                        borderColor: color,
                        backgroundColor: isBbm ? 'rgba(217, 119, 6, 0.12)' : color,
                        fill: isBbm,
                        tension: 0.3,
                        borderRadius: isBbm ? 0 : 4,
                        pointRadius: isMobile ? 3 : 4,
                        pointHitRadius: 12,
                        pointBackgroundColor: color
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            ticks: {
                                autoSkip: isMobile,
                                maxTicksLimit: isMobile ? 6 : 12,
                                maxRotation: 0,
                                font: { size: isMobile ? 10 : 11 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: { size: isMobile ? 9.5 : 11 },
                                callback: v => v.toLocaleString('id-ID') + unit
                            }
                        }
                    }
                }
            });
        }
    });

    function openCreateEnergyForMonth(mNum, mLabel) {
        document.getElementById('tambahEnergyMonth').value = mNum;
        new bootstrap.Modal(document.getElementById('modalTambahEnergi')).show();
    }

    function openDetailEnergyModal(data) {
        const isBbm = data.energy_type === 'bbm';
        document.getElementById('detailEnergyType').innerText = isBbm ? 'Bahan Bakar Minyak (BBM)' : 'Konsumsi Listrik';
        document.getElementById('detailEnergyPeriod').innerText = (data.month_name || '') + ' ' + (data.year || '');
        document.getElementById('detailEnergyAmount').innerText = Number(data.usage_amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 2 });
        document.getElementById('detailEnergyUnit').innerText = data.unit || (isBbm ? 'Liter' : 'kWh');
        document.getElementById('detailEnergyDesc').innerText = data.description || '-';

        const filesCont = document.getElementById('detailEnergyFilesList');
        const files = data.attachment_paths || [];
        if (files.length > 0) {
            filesCont.innerHTML = `
                <ul class="list-unstyled mb-0 d-flex flex-column gap-1">
                    ${files.map((f, idx) => {
                        const name = escapeEnvHtml(f.split('/').pop());
                        const icon = getFileIconClass(name);
                        const fileUrl = `/environment/files/energi/${encodeURIComponent(data.id)}/${idx}`;
                        return `
                            <li class="py-1 px-2 rounded bg-light border d-flex align-items-center justify-content-between" style="font-size: 12px;">
                                <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                    <i class="bi ${icon}"></i>
                                    <span class="text-truncate">${name}</span>
                                </div>
                                <a href="${fileUrl}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2">Buka</a>
                            </li>
                        `;
                    }).join('')}
                </ul>
            `;
        } else {
            filesCont.innerHTML = '<span class="text-muted small">Tidak ada dokumen dilampirkan.</span>';
        }

        new bootstrap.Modal(document.getElementById('modalDetailEnergi')).show();
    }

    function openEditEnergyModal(data) {
        const form = document.getElementById('formEditEnergi');
        form.action = `/environment/energi/${data.id}`;

        document.getElementById('editEnergyMonth').value = data.month || 1;
        document.getElementById('editEnergyYear').value = data.year || 2026;
        document.getElementById('editEnergyAmount').value = data.usage_amount || 0;
        document.getElementById('editEnergyDescription').value = data.description || '';

        resetEnvUploader('energyEditAttachmentInputs', 'energyEditFileList');
        renderExistingEnvFiles('editEnergyExistingFilesContainer', data.attachment_paths || [], 'deleted_attachments[]', 'formEditEnergi', 'energi', data.id);

        new bootstrap.Modal(document.getElementById('modalEditEnergi')).show();
    }

    function confirmDeleteEnergy(id, periodText) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Data Pemakaian?',
                text: `Data pemakaian periode "${periodText}" dan dokumen lampirannya akan dihapus permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('globalDeleteFormEnergy');
                    form.action = `/environment/energi/${id}`;
                    form.submit();
                }
            });
        } else {
            if (confirm(`Yakin ingin menghapus data pemakaian periode "${periodText}"?`)) {
                const form = document.getElementById('globalDeleteFormEnergy');
                form.action = `/environment/energi/${id}`;
                form.submit();
            }
        }
    }
</script>
@endpush

