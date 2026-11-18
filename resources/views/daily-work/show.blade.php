@extends('layouts.app', [
    'title' => 'Detail Pekerjaan - ' . ($work->name ?? 'Daily Work'),
    'activeModule' => 'daily-work'
])

@section('content')
<div class="page-header">
    <div class="header-title-wrapper">
        <div class="header-icon-box teal">
            <i class="bi bi-calendar2-week"></i>
        </div>
        <div>
            <h1 class="page-heading">Detail Pekerjaan Harian</h1>
            <div class="breadcrumb-nav">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="mx-1 text-muted">›</span>
                <a href="{{ route('daily-work.index') }}">Daily Work</a>
                <span class="mx-1 text-muted">›</span>
                <span class="text-secondary fw-semibold">{{ $work->name }}</span>
            </div>
        </div>
    </div>

    <div class="header-actions">
        <a href="{{ route('daily-work.index') }}" class="btn btn-action-outline btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daily Work
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="chart-box-card h-100">
            <div class="chart-header d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="chart-title mb-1">{{ $work->name }}</h2>
                    <p class="chart-subtitle mb-0">Rincian informasi pekerjaan harian operasional</p>
                </div>
                <div>
                    <span class="badge bg-teal-subtle text-teal border px-3 py-1" style="font-size: 12px; background: #ccfbf1; color: #0f766e; border-color: #99f6e4;">
                        #DW-{{ str_pad($work->id, 4, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle" style="font-size: 12.5px;">
                    <tbody>
                        <tr>
                            <th class="bg-light text-muted" style="width: 30%;">ID Pekerjaan</th>
                            <td><strong class="text-dark">#DW-{{ str_pad($work->id, 4, '0', STR_PAD_LEFT) }}</strong></td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Nama Pekerjaan</th>
                            <td class="fw-semibold text-dark">{{ $work->name }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Perusahaan</th>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                    {{ $work->unit }}
                                </span>
                                @if($work->parent_company_name)
                                    <span class="badge bg-light text-muted border px-2 py-1 ms-1">
                                        Induk: {{ $work->parent_company_name }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Status</th>
                            <td>
                                @if($work->status === \App\Models\DailyWork::STATUS_COMPLETED)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle me-1"></i>Selesai
                                    </span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        <i class="bi bi-play-circle me-1"></i>{{ $work->status }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">SIKA</th>
                            <td>
                                @php
                                    $sikaItems = array_filter(array_map('trim', explode(',', $work->sikka ?? '')));
                                @endphp
                                @forelse($sikaItems as $sItem)
                                    <span class="badge bg-info-subtle text-info border px-2 py-1 me-1 mb-1" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">
                                        <i class="bi bi-file-earmark-check me-1"></i>{{ $sItem }}
                                    </span>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Lokasi</th>
                            <td><i class="bi bi-geo-alt text-danger me-1"></i>{{ $work->location }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Periode Tanggal</th>
                            <td>
                                {{ $work->start_date ? $work->start_date->format('d M Y') : '-' }} s/d 
                                {{ $work->end_date ? $work->end_date->format('d M Y') : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Keterangan / Deskripsi</th>
                            <td>{{ $work->description ?? 'Tidak ada keterangan tambahan.' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Waktu Pencatatan</th>
                            <td class="text-muted" style="font-size: 11.5px;">
                                Dibuat: {{ $work->created_at ? $work->created_at->format('d M Y, H:i') : '-' }} | 
                                Diperbarui: {{ $work->updated_at ? $work->updated_at->format('d M Y, H:i') : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="chart-box-card h-100">
            <div class="chart-header mb-3">
                <h2 class="chart-title mb-1">Informasi Perusahaan</h2>
                <p class="chart-subtitle mb-0">Ringkasan mitra pelaksana pekerjaan</p>
            </div>
            <div class="p-3 bg-light rounded border mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-building fs-4 text-success"></i>
                    <div>
                        <div class="fw-bold" style="font-size:12px">{{ $work->unit }}</div>
                        <div class="text-muted" style="font-size:10.5px">Area PT Geo Dipa Energi Dieng</div>
                    </div>
                </div>
                <div class="text-secondary" style="font-size:11px; line-height:1.4;">
                    Pekerjaan dipantau secara berkala sesuai ketentuan SIKA dan standar keselamatan kerja HSSE.
                </div>
            </div>

            <div class="d-grid gap-2">
                <a href="{{ route('daily-work.index') }}" class="btn btn-action-primary btn-sm">
                    <i class="bi bi-list-check me-1"></i> Lihat Semua Pekerjaan
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
