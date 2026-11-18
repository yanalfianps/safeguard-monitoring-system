@extends('layouts.app', ['activeModule' => 'environment-cuaca', 'title' => 'Environment - EWS & Cuaca'])

@push('styles')
<style>
    .env-header-icon {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(2, 132, 199, 0.2);
    }
    .weather-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .weather-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .weather-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .btn-sky-primary {
        background-color: #0284c7;
        border-color: #0284c7;
        color: #ffffff;
    }
    .btn-sky-primary:hover {
        background-color: #0369a1;
        border-color: #0369a1;
        color: #ffffff;
    }
    .form-control-readonly {
        background-color: #f1f5f9 !important;
        color: #0369a1 !important;
        font-weight: 700 !important;
        border-color: #cbd5e1 !important;
        cursor: not-allowed !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    <!-- SUB-NAV / TABS (RUMPUN EWS & CUACA) -->
    <div class="mb-3">
        <ul class="nav nav-pills nav-fill bg-white p-1 rounded-3 shadow-sm border d-flex d-sm-inline-flex flex-wrap gap-1" style="max-width: 100%;">
            <li class="nav-item">
                <a class="nav-link text-secondary fw-semibold py-1 px-3 d-inline-flex align-items-center gap-1" href="{{ route('environment.ews.index') }}">
                    <i class="bi bi-bell"></i> Peringatan Dini (EWS)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active bg-primary text-white fw-semibold py-1 px-3 d-inline-flex align-items-center gap-1" href="{{ route('environment.cuaca.index') }}">
                    <i class="bi bi-cloud-sun"></i> Pencatatan Cuaca
                </a>
            </li>
        </ul>
    </div>

    <!-- HEADER -->
    <x-module-header
        title="EWS & Cuaca — Pencatatan Parameter Cuaca"
        icon="bi bi-cloud-sun"
        icon-bg="linear-gradient(135deg, #0284c7 0%, #0369a1 100%)"
        icon-shadow="rgba(2, 132, 199, 0.22)"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Environment', 'url' => route('environment.index')],
            ['label' => 'Cuaca'],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('environment.cuaca.export-pdf', request()->query()) }}" class="btn btn-danger shadow-none">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            @adminManagement
            <button type="button" class="btn btn-sky-primary hsse-action-primary shadow-none" data-bs-toggle="modal" data-bs-target="#modalTambahCuaca">
                <i class="bi bi-plus-lg"></i> Catat Kondisi Cuaca
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

    <!-- QUICK WEATHER PARAMETER CARDS (TERKINI) -->
    <div class="row g-3 mb-3">
        <!-- 1. Kecepatan Angin -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="weather-stat-card shadow-sm">
                <div class="weather-stat-icon bg-info-subtle text-info">
                    <i class="bi bi-wind"></i>
                </div>
                <div>
                    <div class="small fw-semibold text-secondary text-uppercase" style="font-size: 11px;">Kecepatan Angin</div>
                    <div class="fw-bold text-dark fs-5">
                        {{ $latestRecord ? number_format($latestRecord->wind_speed, 2, ',', '.') : '0,00' }} <span class="fs-6 fw-normal text-muted">m/s</span>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">Rata-rata: {{ number_format($avgWindSpeed, 2, ',', '.') }} m/s</div>
                </div>
            </div>
        </div>

        <!-- 2. Curah Hujan -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="weather-stat-card shadow-sm">
                <div class="weather-stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-cloud-rain"></i>
                </div>
                <div>
                    <div class="small fw-semibold text-secondary text-uppercase" style="font-size: 11px;">Curah Hujan</div>
                    <div class="fw-bold text-dark fs-5">
                        {{ $latestRecord ? number_format($latestRecord->rainfall, 2, ',', '.') : '0,00' }} <span class="fs-6 fw-normal text-muted">mm/hr</span>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">Rata-rata: {{ number_format($avgRainfall, 2, ',', '.') }} mm/hr</div>
                </div>
            </div>
        </div>

        <!-- 3. Temperatur -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="weather-stat-card shadow-sm">
                <div class="weather-stat-icon bg-danger-subtle text-danger">
                    <i class="bi bi-thermometer-half"></i>
                </div>
                <div>
                    <div class="small fw-semibold text-secondary text-uppercase" style="font-size: 11px;">Temperatur</div>
                    <div class="fw-bold text-dark fs-5">
                        {{ $latestRecord ? number_format($latestRecord->temperature, 1, ',', '.') : '0,0' }} <span class="fs-6 fw-normal text-muted">°C</span>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">Rata-rata: {{ number_format($avgTemp, 1, ',', '.') }} °C</div>
                </div>
            </div>
        </div>

        <!-- 4. Kelembaban / Humidity -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="weather-stat-card shadow-sm">
                <div class="weather-stat-icon bg-success-subtle text-success">
                    <i class="bi bi-moisture"></i>
                </div>
                <div>
                    <div class="small fw-semibold text-secondary text-uppercase" style="font-size: 11px;">Humidity (Kelembaban)</div>
                    <div class="fw-bold text-dark fs-5">
                        {{ $latestRecord ? number_format($latestRecord->humidity, 1, ',', '.') : '0,0' }} <span class="fs-6 fw-normal text-muted">%</span>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">Rata-rata: {{ number_format($avgHumidity, 1, ',', '.') }} %</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER CARD -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('environment.cuaca.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary mb-1">Pencarian Keterangan</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik keterangan cuaca..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Rentang Tanggal</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        <span class="input-group-text">-</span>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    @if(request()->hasAny(['search', 'start_date', 'end_date']))
                        <a href="{{ route('environment.cuaca.index') }}" class="btn btn-light border btn-sm" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL DATA CUACA -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th style="width: 100px;">Hari</th>
                            <th style="width: 140px;">Waktu &amp; Tanggal</th>
                            <th class="text-end" style="width: 130px;">Kecepatan Angin</th>
                            <th class="text-end" style="width: 130px;">Curah Hujan</th>
                            <th class="text-end" style="width: 110px;">Temperatur</th>
                            <th class="text-end" style="width: 110px;">Humidity</th>
                            <th>Keterangan</th>
                            <th class="text-center" style="width: 110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $r)
                            <tr>
                                <td class="text-center text-muted">{{ $records->firstItem() + $idx }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-dark border px-2 py-1 fw-bold">
                                        <i class="bi bi-calendar3 me-1 text-primary"></i> {{ $r->day_name }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $r->formatted_date }}</div>
                                    <div class="text-muted small"><i class="bi bi-clock me-1"></i>{{ $r->formatted_time }} WIB</div>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-wind text-info me-1"></i> {{ number_format($r->wind_speed, 2, ',', '.') }} m/s
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-cloud-rain text-primary me-1"></i> {{ number_format($r->rainfall, 2, ',', '.') }} mm/hr
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-thermometer-half text-danger me-1"></i> {{ number_format($r->temperature, 1, ',', '.') }} °C
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-moisture text-success me-1"></i> {{ number_format($r->humidity, 1, ',', '.') }} %
                                    </span>
                                </td>
                                <td>
                                    <span class="text-secondary">{{ $r->notes ?: '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Detail Rincian"
                                                onclick="openDetailCuacaModal({{ json_encode($r) }})">
                                            <i class="bi bi-eye text-primary"></i>
                                        </button>
                                        @adminManagement
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Edit Data"
                                                onclick="openEditCuacaModal({{ json_encode($r) }})">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Hapus Data"
                                                onclick="confirmDeleteCuaca({{ $r->id }}, {{ \Illuminate\Support\Js::from($r->day_name . ', ' . $r->formatted_date) }})">
                                            <i class="bi bi-trash3 text-danger"></i>
                                        </button>
                                        @endadminManagement
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-cloud-slash fs-2 d-block mb-1"></i>
                                    Belum ada data pencatatan parameter cuaca.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($records->hasPages())
            <div class="card-footer bg-white border-top py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    Menampilkan <strong>{{ $records->firstItem() }}</strong> - <strong>{{ $records->lastItem() }}</strong> dari <strong>{{ $records->total() }}</strong> data
                </div>
                <div>
                    {{ $records->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL TAMBAH DATA CUACA -->
<!-- ========================================================================= -->
@adminManagement
<div class="modal fade" id="modalTambahCuaca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('environment.cuaca.store') }}" method="POST" id="formTambahCuaca">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-cloud-sun text-primary"></i> Catat Kondisi Cuaca Lapangan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- 1. Tanggal Pencatatan -->
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Tanggal Pencatatan <span class="text-danger">*</span></label>
                            <input type="date" name="recording_date" id="tambahCuacaDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required onchange="calculateDayName(this.value, 'tambahCuacaDay')">
                        </div>

                        <!-- 2. Field Hari (Otomatis & Readonly) -->
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Hari (Otomatis)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-calendar-day text-primary"></i></span>
                                <input type="text" id="tambahCuacaDay" class="form-control form-control-sm form-control-readonly" readonly disabled value="Rabu">
                            </div>
                        </div>

                        <!-- 3. Waktu Pencatatan -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Waktu Pencatatan <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-clock text-secondary"></i></span>
                                <input type="time" name="recording_time" class="form-control form-control-sm" value="{{ date('H:i') }}" required>
                            </div>
                        </div>

                        <!-- 4. Parameter Cuaca Numerik dengan Satuan Jelas -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kecepatan Angin <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-wind text-info"></i></span>
                                <input type="number" step="any" min="0" name="wind_speed" class="form-control" placeholder="Contoh: 3.50" required>
                                <span class="input-group-text fw-bold text-secondary">m/s</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Curah Hujan <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-cloud-rain text-primary"></i></span>
                                <input type="number" step="any" min="0" name="rainfall" class="form-control" placeholder="Contoh: 12.40" required>
                                <span class="input-group-text fw-bold text-secondary">mm/hr</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Temperatur Udara <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-thermometer-half text-danger"></i></span>
                                <input type="number" step="any" name="temperature" class="form-control" placeholder="Contoh: 24.5" required>
                                <span class="input-group-text fw-bold text-secondary">°C</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kelembaban (Humidity) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-moisture text-success"></i></span>
                                <input type="number" step="any" min="0" max="100" name="humidity" class="form-control" placeholder="Contoh: 78.0" required>
                                <span class="input-group-text fw-bold text-secondary">%</span>
                            </div>
                        </div>

                        <!-- 5. Keterangan Bebas -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan / Catatan Cuaca</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Contoh: Cuaca cerah berawan, arah angin stabil, kondisi aman untuk operasional..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sky-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Data Cuaca</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDIT DATA CUACA -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditCuaca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST" id="formEditCuaca">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-warning"></i> Edit Data Cuaca
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Tanggal & Hari -->
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Tanggal Pencatatan <span class="text-danger">*</span></label>
                            <input type="date" name="recording_date" id="editCuacaDate" class="form-control form-control-sm" required onchange="calculateDayName(this.value, 'editCuacaDay')">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Hari (Otomatis)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-calendar-day text-primary"></i></span>
                                <input type="text" id="editCuacaDay" class="form-control form-control-sm form-control-readonly" readonly disabled value="-">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Waktu Pencatatan <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-clock text-secondary"></i></span>
                                <input type="time" name="recording_time" id="editCuacaTime" class="form-control form-control-sm" required>
                            </div>
                        </div>

                        <!-- Parameter Cuaca Numerik -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kecepatan Angin <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-wind text-info"></i></span>
                                <input type="number" step="any" min="0" name="wind_speed" id="editCuacaWind" class="form-control" required>
                                <span class="input-group-text fw-bold text-secondary">m/s</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Curah Hujan <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-cloud-rain text-primary"></i></span>
                                <input type="number" step="any" min="0" name="rainfall" id="editCuacaRainfall" class="form-control" required>
                                <span class="input-group-text fw-bold text-secondary">mm/hr</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Temperatur Udara <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-thermometer-half text-danger"></i></span>
                                <input type="number" step="any" name="temperature" id="editCuacaTemp" class="form-control" required>
                                <span class="input-group-text fw-bold text-secondary">°C</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kelembaban (Humidity) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-moisture text-success"></i></span>
                                <input type="number" step="any" min="0" max="100" name="humidity" id="editCuacaHumidity" class="form-control" required>
                                <span class="input-group-text fw-bold text-secondary">%</span>
                            </div>
                        </div>

                        <!-- Keterangan Bebas -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan / Catatan Cuaca</label>
                            <textarea name="notes" id="editCuacaNotes" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sky-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FORM HAPUS GLOBAL CUACA -->
<form id="globalDeleteFormCuaca" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endadminManagement

<!-- ========================================================================= -->
<!-- MODAL DETAIL DATA CUACA -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDetailCuaca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-primary"></i> Rincian Parameter Cuaca
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 d-flex align-items-center justify-content-between p-2 rounded bg-light border">
                    <div>
                        <div class="small text-secondary fw-semibold">Hari &amp; Tanggal:</div>
                        <div class="fw-bold text-dark fs-6" id="detailCuacaDayDate">-</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-secondary fw-semibold">Waktu Pencatatan:</div>
                        <div class="fw-semibold text-dark" id="detailCuacaTime">-</div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 border rounded text-center">
                            <div class="small text-secondary"><i class="bi bi-wind text-info me-1"></i> Kecepatan Angin</div>
                            <div class="fw-bold fs-5 text-dark" id="detailCuacaWind">-</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded text-center">
                            <div class="small text-secondary"><i class="bi bi-cloud-rain text-primary me-1"></i> Curah Hujan</div>
                            <div class="fw-bold fs-5 text-dark" id="detailCuacaRainfall">-</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded text-center">
                            <div class="small text-secondary"><i class="bi bi-thermometer-half text-danger me-1"></i> Temperatur Udara</div>
                            <div class="fw-bold fs-5 text-dark" id="detailCuacaTemp">-</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded text-center">
                            <div class="small text-secondary"><i class="bi bi-moisture text-success me-1"></i> Kelembaban (Humidity)</div>
                            <div class="fw-bold fs-5 text-dark" id="detailCuacaHumidity">-</div>
                        </div>
                    </div>
                </div>

                <div class="card bg-light border-0 rounded-2 p-3">
                    <div class="small fw-bold text-secondary mb-1">Keterangan / Catatan Cuaca:</div>
                    <div id="detailCuacaNotes" class="text-dark small">-</div>
                    <div class="mt-2 pt-2 border-top small text-muted d-flex justify-content-between">
                        <span>Pencatat:</span>
                        <strong id="detailCuacaRecorder">-</strong>
                    </div>
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
<script>
    // Fungsi menghitung hari dalam bahasa Indonesia dari tanggal input (YYYY-MM-DD)
    function calculateDayName(dateStr, targetInputId) {
        const target = document.getElementById(targetInputId);
        if (!target) return;

        if (!dateStr) {
            target.value = '-';
            return;
        }

        const parts = String(dateStr).substring(0, 10).split('-');
        if (parts.length === 3) {
            // new Date(year, monthIndex, day)
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            const d = new Date(year, month, day);

            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const dayIdx = d.getDay();
            target.value = days[dayIdx] || '-';
        } else {
            target.value = '-';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const createDateInput = document.getElementById('tambahCuacaDate');
        if (createDateInput) {
            calculateDayName(createDateInput.value, 'tambahCuacaDay');
        }
    });

    function openDetailCuacaModal(data) {
        const rawDate = data.recording_date ? String(data.recording_date).substring(0, 10) : '';
        let dayName = data.day_name;
        if (!dayName && rawDate) {
            const parts = rawDate.split('-');
            if (parts.length === 3) {
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                dayName = days[d.getDay()];
            }
        }
        dayName = dayName || '-';

        document.getElementById('detailCuacaDayDate').innerText = `${dayName}, ${data.formatted_date || rawDate}`;
        document.getElementById('detailCuacaTime').innerText = (data.formatted_time || (data.recording_time ? data.recording_time.substring(0, 5) : '-')) + ' WIB';

        document.getElementById('detailCuacaWind').innerText = (parseFloat(data.wind_speed).toFixed(2)) + ' m/s';
        document.getElementById('detailCuacaRainfall').innerText = (parseFloat(data.rainfall).toFixed(2)) + ' mm/hr';
        document.getElementById('detailCuacaTemp').innerText = (parseFloat(data.temperature).toFixed(1)) + ' °C';
        document.getElementById('detailCuacaHumidity').innerText = (parseFloat(data.humidity).toFixed(1)) + ' %';

        document.getElementById('detailCuacaNotes').innerText = data.notes || 'Tidak ada catatan tambahan.';
        document.getElementById('detailCuacaRecorder').innerText = data.recorder ? data.recorder.name : 'Administrator HSSE';

        new bootstrap.Modal(document.getElementById('modalDetailCuaca')).show();
    }

    function openEditCuacaModal(data) {
        const form = document.getElementById('formEditCuaca');
        form.action = `/environment/cuaca/${data.id}`;

        if (data.recording_date) {
            const dateStr = String(data.recording_date).substring(0, 10);
            document.getElementById('editCuacaDate').value = dateStr;
            calculateDayName(dateStr, 'editCuacaDay');
        }
        if (data.recording_time) {
            document.getElementById('editCuacaTime').value = String(data.recording_time).substring(0, 5);
        }

        document.getElementById('editCuacaWind').value = data.wind_speed;
        document.getElementById('editCuacaRainfall').value = data.rainfall;
        document.getElementById('editCuacaTemp').value = data.temperature;
        document.getElementById('editCuacaHumidity').value = data.humidity;
        document.getElementById('editCuacaNotes').value = data.notes || '';

        new bootstrap.Modal(document.getElementById('modalEditCuaca')).show();
    }

    function confirmDeleteCuaca(id, label) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Data Cuaca?',
                text: `Catatan cuaca "${label}" akan dihapus permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('globalDeleteFormCuaca');
                    form.action = `/environment/cuaca/${id}`;
                    form.submit();
                }
            });
        } else {
            if (confirm(`Yakin ingin menghapus data cuaca "${label}"?`)) {
                const form = document.getElementById('globalDeleteFormCuaca');
                form.action = `/environment/cuaca/${id}`;
                form.submit();
            }
        }
    }
</script>
@endpush
