@extends('layouts.app', ['activeModule' => 'workforce', 'title' => 'Detail Manpower - ' . $workforce->employee_code])

@section('content')
<div class="page-header">
    <div class="header-title-wrapper">
        <div class="header-icon-box green">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <h1 class="page-heading">Detail Manpower &mdash; {{ $workforce->employee_code }}</h1>
            <div class="breadcrumb-nav">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="mx-1 text-muted">&rsaquo;</span>
                <a href="{{ route('man-hours.index') }}">Man Hours &amp; Manpower</a>
                <span class="mx-1 text-muted">&rsaquo;</span>
                <span class="text-dark fw-semibold">Detail ({{ $workforce->category }} &bull; {{ $workforce->unit }})</span>
            </div>
        </div>
    </div>
    <div class="header-actions">
        <a href="{{ route('workforce.index') }}" class="btn-action-outline">
            <i class="bi bi-arrow-left"></i> Kembali ke Manpower
        </a>
        <a href="{{ route('man-hours.index', ['search' => $workforce->employee_code]) }}" class="btn-action-primary">
            <i class="bi bi-clock-history"></i> Lihat di Man Hours
        </a>
    </div>
</div>

<style>
    .wf-show-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
        gap: 18px;
        margin-bottom: 20px;
    }
    .wf-show-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        min-width: 0;
        max-width: 100%;
    }
    .wf-show-card--calc {
        background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
        border: 1px solid #a7f3d0;
    }
    .wf-info-pairs {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        font-size: 13px;
    }
    .wf-calc-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }
    @media (max-width: 575.98px) {
        .wf-show-card {
            padding: 15px;
        }
        .wf-info-pairs,
        .wf-calc-kpis {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="wf-show-grid">
    <div class="wf-show-card">
        <h3 style="margin:0 0 14px 0;font-size:15px;font-weight:700;color:#0f172a;">
            <i class="bi bi-person-vcard-fill" style="color:#059669;margin-right:6px;"></i> Informasi Data Manpower
        </h3>
        <div class="wf-info-pairs">
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Kode Rekaman</div>
                <div style="font-weight:700;color:#0f172a;margin-top:2px;">{{ $workforce->employee_code }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Tanggal Pencatatan</div>
                <div style="font-weight:700;color:#0f172a;margin-top:2px;">{{ $workforce->record_date ? $workforce->record_date->format('d/m/Y') : '-' }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Kategori / Komponen</div>
                <div style="font-weight:700;color:#059669;margin-top:2px;">{{ $workforce->category }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Unit / Area</div>
                <div style="font-weight:700;color:#0f172a;margin-top:2px;">{{ $workforce->unit }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Sub-Unit / Perusahaan</div>
                <div style="font-weight:600;color:#334155;margin-top:2px;">{{ $workforce->department ?: ($workforce->name ?: '-') }}</div>
            </div>
            <div>
                <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Status</div>
                <div style="font-weight:700;color:#059669;margin-top:2px;">{{ $workforce->status }}</div>
            </div>
        </div>
        <div style="margin-top:14px;padding-top:12px;border-top:1px solid #e2e8f0;font-size:13px;">
            <div style="color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;">Catatan</div>
            <div style="color:#334155;margin-top:4px;">{{ $workforce->notes ?: 'Tidak ada catatan tambahan.' }}</div>
        </div>
    </div>

    <div class="wf-show-card wf-show-card--calc">
        <h3 style="margin:0 0 14px 0;font-size:15px;font-weight:700;color:#065f46;">
            <i class="bi bi-calculator-fill" style="color:#059669;margin-right:6px;"></i> Rincian Perhitungan Man Hours Otomatis
        </h3>
        <div class="wf-calc-kpis">
            <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid #d1fae5;">
                <div style="font-size:11px;color:#64748b;font-weight:600;">Jumlah Pekerja</div>
                <div style="font-size:20px;font-weight:800;color:#0f172a;margin-top:4px;">{{ number_format((int) $workforce->worker_count) }} <span style="font-size:12px;font-weight:600;color:#64748b;">Orang</span></div>
            </div>
            <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid #d1fae5;">
                <div style="font-size:11px;color:#64748b;font-weight:600;">Jam Kerja / Orang</div>
                <div style="font-size:20px;font-weight:800;color:#2563eb;margin-top:4px;">{{ number_format((float) $workforce->hours_per_worker, 2) }} <span style="font-size:12px;font-weight:600;color:#64748b;">Jam</span></div>
            </div>
            <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid #d1fae5;">
                <div style="font-size:11px;color:#64748b;font-weight:600;">Total Man Hours</div>
                <div style="font-size:20px;font-weight:800;color:#059669;margin-top:4px;">{{ number_format((float) $workforce->calculated_man_hours, 2) }} <span style="font-size:12px;font-weight:600;color:#64748b;">Jam</span></div>
            </div>
        </div>
        <div style="background:#fff;padding:12px 14px;border-radius:10px;border:1px dashed #34d399;font-size:13px;color:#065f46;">
            <strong>Rumus Perhitungan:</strong>
            {{ number_format((int) $workforce->worker_count) }} Pekerja × {{ number_format((float) $workforce->hours_per_worker, 2) }} Jam/Orang = <strong>{{ number_format((float) $workforce->calculated_man_hours, 2) }} Jam Man Hours</strong>
        </div>
    </div>
</div>
@endsection

