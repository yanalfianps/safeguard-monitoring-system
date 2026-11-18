@extends('layouts.app', ['activeModule' => 'man-hours', 'title' => 'Detail Perhitungan Manhour'])

@section('content')
<div class="page-header">
    <div class="header-title-wrapper">
        <div class="header-icon-box blue">
            <i class="bi bi-clock-history"></i>
        </div>
        <div>
            <h1 class="page-heading">Detail Perhitungan Manhour</h1>
            <div class="breadcrumb-nav">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="mx-1 text-muted">&rsaquo;</span>
                <a href="{{ route('man-hours.index') }}">Man Hours &amp; Manpower</a>
                <span class="mx-1 text-muted">&rsaquo;</span>
                <span class="text-dark fw-semibold">Detail ({{ $manHour->category }} &bull; {{ $manHour->unit }})</span>
            </div>
        </div>
    </div>
    <div class="header-actions">
        <a href="{{ route('man-hours.index') }}" class="btn-action-outline">
            <i class="bi bi-arrow-left"></i> Kembali ke Manhour
        </a>
    </div>
</div>

<style>
    .mh-show-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
        gap: 18px;
        margin-bottom: 20px;
    }
    .mh-show-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        min-width: 0;
        max-width: 100%;
    }
    .mh-show-card--calc {
        background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
        border: 1px solid #a7f3d0;
    }
    .mh-info-pairs {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        font-size: 13px;
    }
    .mh-calc-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }
    @media (max-width: 575.98px) {
        .mh-show-card {
            padding: 15px;
        }
        .mh-info-pairs,
        .mh-calc-kpis {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="mh-show-grid">
    <div class="mh-show-card">
        <h3 style="margin:0 0 14px 0;font-size:15px;font-weight:700;color:#0f172a;">
            <i class="bi bi-database-fill" style="color:#2563eb;margin-right:6px;"></i> Sumber Data Workforce
        </h3>
        <div class="mh-info-pairs">
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Kode Workforce</div>
                <div style="font-weight:700;color:#1d4ed8;margin-top:2px;">{{ $manHour->workforce?->employee_code ?? '-' }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Tanggal Kerja</div>
                <div style="font-weight:700;color:#0f172a;margin-top:2px;">{{ $manHour->work_date ? $manHour->work_date->format('d/m/Y') : '-' }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Kategori Pekerja</div>
                <div style="font-weight:700;color:#059669;margin-top:2px;">{{ $manHour->category }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Unit / Area</div>
                <div style="font-weight:700;color:#0f172a;margin-top:2px;">{{ $manHour->unit }}</div>
            </div>
        </div>
        <div style="margin-top:14px;padding-top:12px;border-top:1px solid #e2e8f0;font-size:13px;">
            <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Catatan</div>
            <div style="color:#334155;margin-top:4px;">{{ $manHour->notes ?: 'Tidak ada catatan tambahan.' }}</div>
        </div>
    </div>

    <div class="mh-show-card mh-show-card--calc">
        <h3 style="margin:0 0 14px 0;font-size:15px;font-weight:700;color:#065f46;">
            <i class="bi bi-calculator-fill" style="color:#059669;margin-right:6px;"></i> Hasil Perhitungan Man Hours
        </h3>
        <div class="mh-calc-kpis">
            <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid #d1fae5;">
                <div style="font-size:11px;color:#64748b;font-weight:600;">Pekerja Sumber</div>
                <div style="font-size:20px;font-weight:800;color:#0f172a;margin-top:4px;">{{ number_format((int) $manHour->number_of_workers) }} <span style="font-size:12px;font-weight:600;color:#64748b;">Orang</span></div>
            </div>
            <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid #d1fae5;">
                <div style="font-size:11px;color:#64748b;font-weight:600;">Jam Kerja / Orang</div>
                <div style="font-size:20px;font-weight:800;color:#2563eb;margin-top:4px;">{{ number_format((float) $manHour->hours_per_worker, 2) }} <span style="font-size:12px;font-weight:600;color:#64748b;">Jam</span></div>
            </div>
            <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid #d1fae5;">
                <div style="font-size:11px;color:#64748b;font-weight:600;">Total Man Hours</div>
                <div style="font-size:20px;font-weight:800;color:#059669;margin-top:4px;">{{ number_format((float) $manHour->total_hours, 2) }} <span style="font-size:12px;font-weight:600;color:#64748b;">Jam</span></div>
            </div>
        </div>
        <div style="background:#fff;padding:12px 14px;border-radius:10px;border:1px dashed #34d399;font-size:13px;color:#065f46;">
            <strong>Rumus:</strong>
            {{ number_format((int) $manHour->number_of_workers) }} Pekerja × {{ number_format((float) $manHour->hours_per_worker, 2) }} Jam/Orang = <strong>{{ number_format((float) $manHour->total_hours, 2) }} Jam Man Hours</strong>
        </div>
    </div>
</div>
@endsection

