@extends('layouts.app', ['activeModule' => 'environment', 'title' => 'Environment - Ringkasan'])

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
    .env-metric-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .env-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
    }
    .env-metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }
    .env-metric-card.card-lb3::before { background: #dc2626; }
    .env-metric-card.card-non-lb3::before { background: #059669; }
    .env-metric-card.card-rkl::before { background: #0284c7; }
    .env-metric-card.card-ppkh::before { background: #0f766e; }

    .env-metric-title {
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 6px;
    }
    .env-metric-value {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .env-metric-desc {
        font-size: 11.5px;
        color: #94a3b8;
        margin-top: 6px;
    }

    .quick-menu-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        text-decoration: none;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s ease;
    }
    .quick-menu-card:hover {
        border-color: #0f766e;
        background: #f0fdfa;
        color: #0f766e;
        box-shadow: 0 3px 8px rgba(15, 118, 110, 0.08);
    }
    .quick-menu-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
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
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="env-header-icon shadow-sm">
                <i class="bi bi-tree-fill"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Environment</h4>
                <div class="text-secondary small">Pusat Pemantauan, Pengelolaan Dokumen Lingkungan &amp; Pelaporan Terpadu</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="GET" action="{{ route('environment.index') }}" class="d-flex align-items-center gap-2">
                <label class="small text-secondary fw-semibold text-nowrap">Tahun:</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('environment.export-pdf', ['year' => $year]) }}" class="btn btn-danger btn-sm shadow-none d-inline-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            @adminManagement
            <a href="{{ route('environment.master.index') }}" class="btn btn-outline-secondary btn-sm shadow-none d-inline-flex align-items-center gap-1">
                <i class="bi bi-sliders"></i> Master Data
            </a>
            @endadminManagement
        </div>
    </div>

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

    <!-- METRICS CARDS -->
    <div class="row g-3 mb-4">
        <!-- 1. LB3 -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="{{ route('environment.lb3.index') }}" class="text-decoration-none d-block h-100">
                <div class="env-metric-card card-lb3">
                    <div>
                        <div class="env-metric-title"><i class="bi bi-radioactive text-danger me-1"></i> LB3 (B3)</div>
                        <div class="env-metric-value text-danger">{{ number_format($totalLb3Count) }}</div>
                    </div>
                    <div class="env-metric-desc">Total {{ number_format($totalLb3Weight, 2, ',', '.') }} akumulasi limbah</div>
                </div>
            </a>
        </div>

        <!-- 2. Non-LB3 -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="{{ route('environment.non-lb3.index') }}" class="text-decoration-none d-block h-100">
                <div class="env-metric-card card-non-lb3">
                    <div>
                        <div class="env-metric-title"><i class="bi bi-trash3 text-success me-1"></i> Non-LB3</div>
                        <div class="env-metric-value text-success">{{ number_format($totalNonLb3Count) }}</div>
                    </div>
                    <div class="env-metric-desc">Total {{ number_format($totalNonLb3Weight, 2, ',', '.') }} akumulasi limbah</div>
                </div>
            </a>
        </div>

        <!-- 3. RKL-RPL -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="{{ route('environment.rkl-rpl.index') }}" class="text-decoration-none d-block h-100">
                <div class="env-metric-card card-rkl">
                    <div>
                        <div class="env-metric-title"><i class="bi bi-clipboard2-pulse text-info me-1"></i> RKL–RPL</div>
                        <div class="env-metric-value text-primary">{{ number_format($totalRklRplCount) }}</div>
                    </div>
                    <div class="env-metric-desc">{{ $parametersCount }} Parameter Lingkungan Dipantau</div>
                </div>
            </a>
        </div>

        <!-- 4. Inspeksi -->
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="{{ route('environment.inspeksi.index') }}" class="text-decoration-none d-block h-100">
                <div class="env-metric-card card-ppkh">
                    <div>
                        <div class="env-metric-title" style="color: #0f766e;"><i class="bi bi-clipboard-check me-1"></i> Inspeksi</div>
                        <div class="env-metric-value" style="color: #0f766e;">{{ number_format($totalInspeksiCount) }}</div>
                    </div>
                    <div class="env-metric-desc">Spill Kit, Sampah, TPS LB3, PPKH/Lahan</div>
                </div>
            </a>
        </div>
    </div>

    <!-- TREN ENERGI (BBM & LISTRIK) -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-fuel-pump text-warning fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Tren Pemakaian BBM ({{ $year }})</h6>
                            <span class="text-muted small">Total Pemakaian: <strong>{{ number_format($totalBbmYear, 2, ',', '.') }} Liter</strong></span>
                        </div>
                    </div>
                    <a href="{{ route('environment.energi.index', ['tab' => 'bbm', 'year' => $year]) }}" class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1">
                        Detail BBM <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <canvas id="chartBbm" height="220"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-lightning-charge text-primary fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Tren Pemakaian Listrik ({{ $year }})</h6>
                            <span class="text-muted small">Total Pemakaian: <strong>{{ number_format($totalListrikYear, 2, ',', '.') }} kWh</strong></span>
                        </div>
                    </div>
                    <a href="{{ route('environment.energi.index', ['tab' => 'listrik', 'year' => $year]) }}" class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1">
                        Detail Listrik <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <canvas id="chartListrik" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- RINGKASAN INSPEKSI & CUACA -->
    <div class="row g-3 mb-4">
        <!-- 1. Ringkasan Inspeksi -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clipboard-check text-success fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Ringkasan Inspeksi Lingkungan</h6>
                            <span class="text-muted small">Total {{ $totalInspeksiCount }} catatan hasil inspeksi</span>
                        </div>
                    </div>
                    <a href="{{ route('environment.inspeksi.index') }}" class="btn btn-xs btn-outline-success d-inline-flex align-items-center gap-1">
                        Buka Inspeksi <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <!-- Rekap Jenis Inspeksi -->
                    <div class="mb-3">
                        <div class="small fw-semibold text-secondary text-uppercase mb-2" style="font-size: 11px;">Sebaran Jenis Inspeksi:</div>
                        <div class="d-flex flex-wrap gap-2">
                            @php
                                $standardTypes = ['Spill Kit', 'Tempat Sampah', 'TPS LB3', 'PPKH/Lahan'];
                                $typeMap = $inspeksiByType->pluck('total', 'inspection_type')->toArray();
                            @endphp
                            @foreach($standardTypes as $sType)
                                <div class="px-3 py-2 rounded border bg-light d-flex align-items-center justify-content-between flex-fill">
                                    <span class="small fw-semibold text-dark">{{ $sType }}</span>
                                    <span class="badge bg-primary rounded-pill ms-2">{{ $typeMap[$sType] ?? 0 }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Highlight Inspeksi Terakhir -->
                    <div class="pt-2 border-top">
                        <div class="small fw-semibold text-secondary text-uppercase mb-2" style="font-size: 11px;">Inspeksi Terbaru:</div>
                        @php
                            $latestItem = $latestInspeksi instanceof \Illuminate\Support\Collection ? $latestInspeksi->first() : $latestInspeksi;
                        @endphp
                        @if($latestItem)
                            <div class="p-2 rounded bg-light border">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-teal-primary text-white" style="background-color: #0f766e;">{{ $latestItem->inspection_type_label }}</span>
                                    <span class="small text-muted">{{ $latestItem->formatted_date }}</span>
                                </div>
                                <div class="fw-bold text-dark small mb-1">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> {{ $latestItem->location_label }}
                                </div>
                                <div class="small text-secondary text-truncate mb-2">{{ Str::limit($latestItem->condition_text, 120) }}</div>
                                <div class="d-flex align-items-center justify-content-between small text-muted border-top pt-1">
                                    <span><i class="bi bi-paperclip me-1"></i> {{ count($latestItem->attachment_list) }} Dokumen Lampiran</span>
                                    <span>Pencatat: {{ $latestItem->creator?->name ?? 'Sistem' }}</span>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-3 small">Belum ada rekaman inspeksi lapangan.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Ringkasan Cuaca Lapangan -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cloud-sun text-primary fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Monitoring Parameter Cuaca</h6>
                            <span class="text-muted small">Total {{ $totalWeatherCount }} pencatatan meteorologi</span>
                        </div>
                    </div>
                    <a href="{{ route('environment.cuaca.index') }}" class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1">
                        Buka Cuaca <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <!-- 4 Parameter Grid -->
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <div class="p-2 rounded border bg-light text-center">
                                <div class="small text-secondary mb-1"><i class="bi bi-wind text-info me-1"></i> Kecepatan Angin</div>
                                <div class="fw-bold text-dark fs-5">{{ $latestWeather ? number_format($latestWeather->wind_speed, 2, ',', '.') : '0,00' }} <span class="fs-6 fw-normal text-muted">m/s</span></div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="p-2 rounded border bg-light text-center">
                                <div class="small text-secondary mb-1"><i class="bi bi-cloud-rain text-primary me-1"></i> Curah Hujan</div>
                                <div class="fw-bold text-dark fs-5">{{ $latestWeather ? number_format($latestWeather->rainfall, 2, ',', '.') : '0,00' }} <span class="fs-6 fw-normal text-muted">mm/hr</span></div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="p-2 rounded border bg-light text-center">
                                <div class="small text-secondary mb-1"><i class="bi bi-thermometer-half text-danger me-1"></i> Temperatur</div>
                                <div class="fw-bold text-dark fs-5">{{ $latestWeather ? number_format($latestWeather->temperature, 1, ',', '.') : '0,0' }} <span class="fs-6 fw-normal text-muted">°C</span></div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="p-2 rounded border bg-light text-center">
                                <div class="small text-secondary mb-1"><i class="bi bi-moisture text-success me-1"></i> Humidity</div>
                                <div class="fw-bold text-dark fs-5">{{ $latestWeather ? number_format($latestWeather->humidity, 1, ',', '.') : '0,0' }} <span class="fs-6 fw-normal text-muted">%</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Highlight Kondisi Cuaca Terkini -->
                    <div class="pt-2 border-top">
                        <div class="small fw-semibold text-secondary text-uppercase mb-2" style="font-size: 11px;">Pencatatan Cuaca Terkini:</div>
                        @if($latestWeather)
                            <div class="p-2 rounded bg-light border">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-secondary-subtle text-dark border px-2 py-1 fw-bold">
                                        <i class="bi bi-calendar3 me-1 text-primary"></i> {{ $latestWeather->day_name }}
                                    </span>
                                    <span class="small text-muted">{{ $latestWeather->formatted_date }} ({{ $latestWeather->formatted_time }} WIB)</span>
                                </div>
                                <div class="small text-secondary">{{ $latestWeather->notes ?: 'Kondisi cuaca terpantau normal.' }}</div>
                            </div>
                        @else
                            <div class="text-center text-muted py-3 small">Belum ada pencatatan data cuaca.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- REKAP EWS & MENU PINTAS -->
    <div class="row g-3">
        <!-- EWS Rekap per Lokasi -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bell text-warning fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Early Warning System (EWS)</h6>
                            <span class="text-muted small">Total {{ $totalEwsCount }} pencatatan kondisi aktual</span>
                        </div>
                    </div>
                    <a href="{{ route('environment.ews.index') }}" class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1">
                        Lihat EWS <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    @if($ewsByLocation->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($ewsByLocation as $loc)
                                <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-geo-alt-fill text-danger"></i>
                                        <span class="fw-semibold text-dark">{{ $loc->location_name }}</span>
                                    </div>
                                    <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">
                                        {{ $loc->total }} catatan
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-shield-check fs-2 d-block mb-1 text-success"></i>
                            Belum ada pencatatan EWS.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pintasan Submenu Environment -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-grid-fill text-success me-1"></i> Submenu Environment</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('environment.lb3.index') }}" class="quick-menu-card">
                                <div class="quick-menu-icon bg-danger-subtle text-danger">
                                    <i class="bi bi-radioactive"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">LB3</div>
                                    <div class="text-muted" style="font-size: 11px;">Limbah B3</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('environment.non-lb3.index') }}" class="quick-menu-card">
                                <div class="quick-menu-icon bg-success-subtle text-success">
                                    <i class="bi bi-trash3"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">Non-LB3</div>
                                    <div class="text-muted" style="font-size: 11px;">Limbah Non-B3</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('environment.rkl-rpl.index') }}" class="quick-menu-card">
                                <div class="quick-menu-icon bg-info-subtle text-info">
                                    <i class="bi bi-clipboard2-pulse"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">RKL–RPL</div>
                                    <div class="text-muted" style="font-size: 11px;">Pemantauan Lingkungan</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('environment.inspeksi.index') }}" class="quick-menu-card">
                                <div class="quick-menu-icon text-white" style="background-color: #0f766e;">
                                    <i class="bi bi-clipboard-check"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">Inspeksi</div>
                                    <div class="text-muted" style="font-size: 11px;">Spill Kit, TPS, PPKH</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('environment.energi.index') }}" class="quick-menu-card">
                                <div class="quick-menu-icon bg-warning-subtle text-warning">
                                    <i class="bi bi-lightning-charge"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">Energi</div>
                                    <div class="text-muted" style="font-size: 11px;">BBM &amp; Listrik</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('environment.ews.index') }}" class="quick-menu-card">
                                <div class="quick-menu-icon bg-secondary-subtle text-dark">
                                    <i class="bi bi-bell"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">EWS &amp; Cuaca</div>
                                    <div class="text-muted" style="font-size: 11px;">Peringatan Dini &amp; Cuaca</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <a href="{{ route('environment.master.index') }}" class="quick-menu-card">
                                <div class="quick-menu-icon bg-dark-subtle text-dark">
                                    <i class="bi bi-sliders"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">Master Data</div>
                                    <div class="text-muted" style="font-size: 11px;">Konfigurasi Parameter</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const isEnvMobile = window.innerWidth < 576;

        // Chart BBM
        const ctxBbm = document.getElementById('chartBbm');
        if (ctxBbm) {
            new Chart(ctxBbm, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Pemakaian BBM (Liter)',
                        data: {!! json_encode(array_values($bbmMonthly)) !!},
                        borderColor: '#d97706',
                        backgroundColor: 'rgba(217, 119, 6, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: isEnvMobile ? 3 : 4,
                        pointHitRadius: 12,
                        pointBackgroundColor: '#d97706'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            ticks: {
                                autoSkip: isEnvMobile,
                                maxTicksLimit: isEnvMobile ? 6 : 12,
                                maxRotation: 0,
                                font: { size: isEnvMobile ? 10 : 11 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: { size: isEnvMobile ? 9.5 : 11 },
                                callback: v => v.toLocaleString('id-ID') + ' L'
                            }
                        }
                    }
                }
            });
        }

        // Chart Listrik
        const ctxListrik = document.getElementById('chartListrik');
        if (ctxListrik) {
            new Chart(ctxListrik, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Pemakaian Listrik (kWh)',
                        data: {!! json_encode(array_values($listrikMonthly)) !!},
                        backgroundColor: '#0f766e',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            ticks: {
                                autoSkip: isEnvMobile,
                                maxTicksLimit: isEnvMobile ? 6 : 12,
                                maxRotation: 0,
                                font: { size: isEnvMobile ? 10 : 11 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: { size: isEnvMobile ? 9.5 : 11 },
                                callback: v => v.toLocaleString('id-ID') + ' kWh'
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
