@extends('layouts.app', [
    'title' => 'Detail Event - ' . $event->title,
    'activeModule' => 'events'
])

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4><i class="bi bi-info-circle text-primary me-2"></i>Detail Kegiatan</h4>
        <a href="{{ route('events.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Events
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <p class="mb-1 text-muted">Judul Kegiatan</p>
                    <h5 class="fw-bold">{{ $event->title }}</h5>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted">Fungsi Safeguard</p>
                    <div class="d-flex flex-wrap gap-1">
                        @forelse($event->safeguard_badges_data as $b)
                            <span class="badge" style="background: {{ $b['bg'] }}; color: {{ $b['color'] }}; border: 1px solid {{ $b['border'] }}; font-size: 11px; font-weight: 500;">
                                {{ $b['name'] }}
                            </span>
                        @empty
                            <span class="badge bg-secondary-subtle text-secondary">-</span>
                        @endforelse
                    </div>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted">Status</p>
                    @php
                        $statusClass = match($event->status) {
                            'Selesai' => 'status-selesai',
                            'Berlangsung' => 'status-berlangsung',
                            'Dibatalkan' => 'status-dibatalkan',
                            default => 'status-direncanakan'
                        };
                    @endphp
                    <span class="badge-status {{ $statusClass }}">{{ $event->status }}</span>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Perusahaan/Instansi</p>
                    <p class="fw-semibold">{{ $event->company ?: $event->unit ?: '-' }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Tanggal Kegiatan</p>
                    <p class="fw-semibold">{{ $event->formatted_date_range }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Penanggung Jawab (PIC)</p>
                    <p class="fw-semibold">{{ $event->pic ?: '-' }}</p>
                </div>
                <div class="col-12">
                    <p class="mb-1 text-muted">Deskripsi</p>
                    <p>{{ $event->description ?: '-' }}</p>
                </div>
                <div class="col-md-6 border-top pt-3">
                    <p class="mb-1 text-muted small">Dibuat oleh</p>
                    <p class="small fw-semibold mb-0">
                        {{ $resolvedCreatedBy ?? $event->created_by ?? '-' }}
                        @if($event->created_at)
                            <span class="text-muted fw-normal">({{ $event->created_at->format('d/m/Y H:i') }})</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6 border-top pt-3">
                    <p class="mb-1 text-muted small">Diubah oleh</p>
                    <p class="small fw-semibold mb-0">
                        {{ $resolvedUpdatedBy ?? $event->updated_by ?? '-' }}
                        @if($event->updated_at)
                            <span class="text-muted fw-normal">({{ $event->updated_at->format('d/m/Y H:i') }})</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-images text-primary me-2"></i>Dokumentasi ({{ $event->documents->count() }})</h6>
                </div>
                <div class="card-body">
                    @if($event->documents->isNotEmpty())
                        <div class="row g-2">
                            @foreach($event->documents as $doc)
                                @php
                                    $fileName = $doc->file_name ?: $doc->title ?: 'Dokumen';
                                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                    $isImage = ($doc->mime_type && str_starts_with($doc->mime_type, 'image/')) 
                                        || in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                @endphp
                                <div class="col-6 col-sm-4">
                                    <div class="border rounded overflow-hidden bg-white text-center shadow-sm h-100 d-flex flex-column justify-content-between">
                                        <a href="{{ route('events.documentation.download', $doc->id) }}" target="_blank" class="text-decoration-none d-block bg-light">
                                            @if($isImage && $doc->file_url)
                                                <img src="{{ $doc->file_url }}" alt="{{ $fileName }}" style="width:100%; height:95px; object-fit:cover;" onerror="this.src='/images/backgrounds/login-dieng.webp'">
                                            @elseif($ext === 'pdf')
                                                <div class="d-flex flex-column align-items-center justify-content-center text-danger" style="height:95px;">
                                                    <i class="bi bi-file-earmark-pdf-fill fs-1"></i>
                                                    <span class="badge bg-danger-subtle text-danger" style="font-size: 9px;">PDF</span>
                                                </div>
                                            @elseif(in_array($ext, ['doc', 'docx']))
                                                <div class="d-flex flex-column align-items-center justify-content-center text-primary" style="height:95px;">
                                                    <i class="bi bi-file-earmark-word-fill fs-1"></i>
                                                    <span class="badge bg-primary-subtle text-primary" style="font-size: 9px;">WORD</span>
                                                </div>
                                            @elseif(in_array($ext, ['xls', 'xlsx']))
                                                <div class="d-flex flex-column align-items-center justify-content-center text-success" style="height:95px;">
                                                    <i class="bi bi-file-earmark-excel-fill fs-1"></i>
                                                    <span class="badge bg-success-subtle text-success" style="font-size: 9px;">EXCEL</span>
                                                </div>
                                            @else
                                                <div class="d-flex flex-column align-items-center justify-content-center text-secondary" style="height:95px;">
                                                    <i class="bi bi-file-earmark-text-fill fs-1"></i>
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 9px;">FILE</span>
                                                </div>
                                            @endif
                                        </a>
                                        <div class="p-2 d-flex justify-content-between align-items-center border-top">
                                            <div class="small text-dark text-truncate text-start pe-1" title="{{ $fileName }}" style="font-size: 11px;">
                                                {{ $fileName }}
                                            </div>
                                            <a href="{{ route('events.documentation.download', $doc->id) }}" class="btn btn-sm btn-light border py-0 px-1 text-primary" title="Unduh File">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mb-0">Belum ada dokumentasi untuk kegiatan ini.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Kegiatan</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Waktu</th>
                                    <th>Pengguna</th>
                                    <th>Aksi</th>
                                    <th class="pe-3">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($event->activityLogs as $log)
                                    <tr>
                                        <td class="ps-3">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-' }}</td>
                                        <td class="fw-semibold">{{ $log->user_name ?: '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                                        <td class="pe-3">{{ $log->description }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Belum ada riwayat aktivitas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

