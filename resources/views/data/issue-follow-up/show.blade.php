@extends('layouts.app', [
    'title' => 'Detail Temuan ' . $issue->issue_code . ' - Issue & Follow Up',
    'activeModule' => 'issue-follow-up'
])

@push('styles')
<style>
    .ifu-detail-page {
        color: #1e293b;
        padding-bottom: 36px;
    }

    .ifu-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        margin-bottom: 22px;
    }

    .ifu-title-group {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .ifu-icon-box {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: linear-gradient(135deg, #047857 0%, #059669 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 6px 16px rgba(4, 120, 87, 0.22);
    }

    .ifu-page-title {
        font-size: 21px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 2px 0;
    }

    .ifu-breadcrumb {
        font-size: 12.5px;
        color: #64748b;
        margin: 0;
    }

    .ifu-breadcrumb a {
        color: #047857;
        text-decoration: none;
        font-weight: 600;
    }

    .ifu-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px 24px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        margin-bottom: 20px;
    }

    .ifu-card-title {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 16px 0;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .ifu-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .ifu-badge.status-open {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .ifu-badge.status-in-progress {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .ifu-badge.status-closed {
        background: #d1fae5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .ifu-badge.status-overdue {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .ifu-meta-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    @media (max-width: 768px) {
        .ifu-meta-grid {
            grid-template-columns: 1fr;
        }
    }

    .ifu-meta-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
    }

    .ifu-meta-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .ifu-meta-val {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
    }

    .ifu-timeline {
        position: relative;
        padding-left: 24px;
    }

    .ifu-timeline::before {
        content: "";
        position: absolute;
        left: 7px;
        top: 6px;
        bottom: 6px;
        width: 2px;
        background: #cbd5e1;
    }

    .ifu-timeline-item {
        position: relative;
        margin-bottom: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 13px 16px;
    }

    .ifu-timeline-item::before {
        content: "";
        position: absolute;
        left: -21px;
        top: 15px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #059669;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 2px #059669;
    }

    .btn-ifu {
        border-radius: 10px;
        padding: 8px 15px;
        font-size: 12.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        text-decoration: none;
        border: 1px solid transparent;
    }

    .btn-ifu-primary {
        background: linear-gradient(135deg, #047857 0%, #059669 100%);
        color: #ffffff;
    }

    .btn-ifu-primary:hover {
        color: #ffffff;
        background: linear-gradient(135deg, #065f46 0%, #047857 100%);
    }

    .btn-ifu-outline {
        background: #ffffff;
        color: #334155;
        border-color: #cbd5e1;
    }

    .btn-ifu-outline:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .doc-item-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 12px 16px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 10px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .doc-item-box:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    }

    .ifu-card,
    .ifu-meta-box {
        min-width: 0;
        max-width: 100%;
    }

    .ifu-header,
    .ifu-card-title {
        flex-wrap: wrap;
        gap: 10px;
    }

    @media (max-width: 767.98px) {
        .ifu-card {
            padding: 14px;
        }
        .doc-item-box {
            padding: 12px;
        }
    }
</style>
@endpush

@section('content')
<div class="ifu-detail-page">
    <div class="ifu-header">
        <div class="ifu-title-group" style="min-width: 0;">
            <div class="ifu-icon-box flex-shrink-0">
                <i class="bi bi-clipboard2-check-fill"></i>
            </div>
            <div>
                <h1 class="ifu-page-title">Detail Temuan: {{ $issue->issue_code }}</h1>
                <p class="ifu-breadcrumb">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <span class="mx-1">&rsaquo;</span>
                    <a href="{{ route('issue-follow-up.index') }}">Issue &amp; Follow Up</a>
                    <span class="mx-1">&rsaquo;</span>
                    <span class="fw-semibold text-dark">{{ $issue->issue_code }}</span>
                </p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('issue-follow-up.index') }}" class="btn-ifu btn-ifu-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <a href="{{ route('issue-follow-up.edit', $issue->id) }}" class="btn-ifu btn-ifu-outline">
                <i class="bi bi-pencil-square"></i> Edit Temuan
            </a>
            <form action="{{ route('issue-follow-up.destroy', $issue->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus temuan {{ $issue->issue_code }} beserta seluruh riwayat tindak lanjutnya?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-ifu btn-danger text-white">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- KOLOM KIRI: INFORMASI LENGKAP & RIWAYAT TINDAK LANJUT --}}
        <div class="col-12 col-xl-8">
            <div class="ifu-card">
                <div class="ifu-card-title">
                    <div style="min-width: 0;">
                        <span class="text-muted small d-block fw-normal">Judul Temuan</span>
                        <span class="fs-5">{{ $issue->title }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="ifu-badge {{ $issue->status_badge_class }}">
                            {{ $issue->status }}
                        </span>
                        @if($issue->isOverdue())
                            <span class="ifu-badge status-overdue">
                                <i class="bi bi-fire"></i> Overdue
                            </span>
                        @endif
                    </div>
                </div>

                <div class="ifu-meta-grid">
                    <div class="ifu-meta-box">
                        <div class="ifu-meta-label">Kode &amp; Fungsi Safeguard</div>
                        <div class="ifu-meta-val d-flex align-items-center gap-2">
                            <span>{{ $issue->issue_code }}</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $issue->division_label }}</span>
                        </div>
                    </div>
                    <div class="ifu-meta-box">
                        <div class="ifu-meta-label">Tanggal Temuan</div>
                        <div class="ifu-meta-val">{{ $issue->reported_date ? $issue->reported_date->format('d F Y') : '-' }}</div>
                    </div>
                    <div class="ifu-meta-box">
                        <div class="ifu-meta-label">Tanggal Closed</div>
                        <div class="ifu-meta-val {{ $issue->status === 'Closed' ? 'text-success' : 'text-muted' }}">
                            {{ ($issue->status === 'Closed' && $issue->closed_date) ? $issue->closed_date->format('d F Y') : '-' }}
                        </div>
                    </div>
                    <div class="ifu-meta-box">
                        <div class="ifu-meta-label">Lokasi</div>
                        <div class="ifu-meta-val">{{ $issue->location ?: '-' }}</div>
                    </div>
                    <div class="ifu-meta-box">
                        <div class="ifu-meta-label">Sumber Temuan</div>
                        <div class="ifu-meta-val">{{ $issue->category ?: '-' }}</div>
                    </div>
                    <div class="ifu-meta-box">
                        <div class="ifu-meta-label">Status Saat Ini</div>
                        <div class="ifu-meta-val">{{ $issue->status === 'Closed' ? 'Closed' : 'Open' }}</div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="ifu-meta-label mb-1">Deskripsi Temuan</div>
                    <div class="p-3 rounded-3 bg-light border text-dark" style="font-size: 13.5px; line-height: 1.6;">
                        {{ $issue->description }}
                    </div>
                </div>

                <div class="mb-3">
                    <div class="ifu-meta-label mb-1">Rencana Tindak Lanjut (Action Plan)</div>
                    <div class="p-3 rounded-3 bg-light border text-dark" style="font-size: 13.5px; line-height: 1.6;">
                        {{ $issue->action_plan ?: 'Belum ada rincian rencana tindak lanjut.' }}
                    </div>
                </div>

                @if($issue->attachment_path)
                    <div class="mb-3">
                        <div class="ifu-meta-label mb-1">Bukti / Dokumentasi Temuan</div>
                        <a href="{{ route('issue-follow-up.attachment.view', $issue->id) }}" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-paperclip me-1"></i> Lihat Lampiran Dokumentasi
                        </a>
                    </div>
                @endif

                <div class="d-flex flex-wrap justify-content-between text-muted small pt-3 border-top">
                    <span>Dibuat pada: <strong>{{ $issue->created_at ? $issue->created_at->format('d/m/Y H:i') : '-' }}</strong></span>
                    <span>Diperbarui terakhir: <strong>{{ $issue->updated_at ? $issue->updated_at->format('d/m/Y H:i') : '-' }}</strong></span>
                    @if($issue->status === 'Closed' && $issue->closed_date)
                        <span class="text-success fw-semibold">
                            Ditutup pada: {{ $issue->closed_date->format('d/m/Y') }}
                            {{ $issue->closed_by ? 'oleh ' . $issue->closed_by : '' }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- SECTION: DOKUMENTASI TEMUAN (MULTI-FILE LAMPIRAN) --}}
            <div class="ifu-card">
                <div class="ifu-card-title">
                    <div>
                        <i class="bi bi-folder2-open text-success me-2"></i>
                        <span>Dokumentasi Temuan</span>
                        <span class="badge bg-secondary rounded-pill ms-1">{{ $issue->documentations->count() }} File</span>
                    </div>
                    @adminManagement
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="openAddDocModal()">
                            <i class="bi bi-plus-lg me-1"></i> Add Documentation
                        </button>
                    @endadminManagement
                </div>

                <div id="documentationList">
                    @forelse($issue->documentations as $doc)
                        <div class="doc-item-box">
                            <div class="d-flex align-items-center gap-3" style="min-width: 0; flex: 1;">
                                <div class="rounded-3 p-2 bg-light text-primary border flex-shrink-0" style="font-size: 20px;">
                                    @if($doc->is_image)
                                        <i class="bi bi-file-earmark-image text-success"></i>
                                    @elseif(str_contains(strtolower($doc->mime_type ?? ''), 'pdf'))
                                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                                    @else
                                        <i class="bi bi-file-earmark-text text-primary"></i>
                                    @endif
                                </div>
                                <div style="min-width: 0;">
                                    <div class="fw-bold text-dark text-break" style="font-size: 13px;">{{ $doc->original_name }}</div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <span>{{ $doc->formatted_size }}</span> &bull;
                                        <span>Diupload: {{ $doc->created_at ? $doc->created_at->format('d/m/Y H:i') : '-' }}</span>
                                        @if($doc->uploaded_by)
                                            &bull; <span>Oleh: <strong>{{ $doc->uploaded_by }}</strong></span>
                                        @endif
                                    </div>
                                    @if($doc->caption && $doc->caption !== $doc->original_name)
                                        <div class="text-secondary small fst-italic mt-1">{{ $doc->caption }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <a href="{{ route('issue-follow-up.documentation.view', $doc->id) }}" target="_blank" class="btn btn-sm btn-light border" title="Lihat / Pratinjau File">
                                    <i class="bi bi-eye text-primary"></i>
                                </a>
                                <a href="{{ route('issue-follow-up.documentation.download', $doc->id) }}" class="btn btn-sm btn-light border" title="Unduh File">
                                    <i class="bi bi-download text-success"></i>
                                </a>
                                @adminManagement
                                    <form action="{{ route('issue-follow-up.documentation.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumentasi ini?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus Dokumentasi">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endadminManagement
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-folder2 d-block fs-2 mb-2 text-secondary"></i>
                            Belum ada file dokumentasi yang ditambahkan untuk temuan ini.
                            @adminManagement
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-success" onclick="openAddDocModal()">
                                        <i class="bi bi-plus-lg me-1"></i> Tambah Dokumentasi Pertama
                                    </button>
                                </div>
                            @endadminManagement
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- RIWAYAT TINDAK LANJUT SECARA KRONOLOGIS --}}
            <div class="ifu-card">
                <div class="ifu-card-title">
                    <span><i class="bi bi-clock-history text-success me-2"></i>Riwayat Tindak Lanjut &amp; Perubahan Status (Kronologis)</span>
                    <span class="badge bg-secondary rounded-pill">{{ $issue->chronologicalLogs->count() }} Catatan</span>
                </div>

                @if($issue->chronologicalLogs->isEmpty())
                    <div class="text-center py-4 text-muted small">
                        Belum ada catatan tindak lanjut untuk temuan ini.
                    </div>
                @else
                    <div class="ifu-timeline">
                        @foreach($issue->chronologicalLogs as $log)
                            <div class="ifu-timeline-item">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark" style="font-size: 13px;">
                                        <i class="bi bi-shield-check text-success me-1"></i>
                                        {{ $log->followed_up_by ?: 'Petugas HSSE' }}
                                    </span>
                                    <span class="text-muted" style="font-size: 11.5px;">
                                        {{ $log->followed_up_at ? $log->followed_up_at->format('d/m/Y H:i') : ($log->created_at ? $log->created_at->format('d/m/Y H:i') : '-') }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    @if($log->previous_status && $log->previous_status !== $log->new_status)
                                        <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                            Status: {{ $log->previous_status }} &rarr; <strong>{{ $log->new_status }}</strong>
                                        </span>
                                    @else
                                        <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                            Status: <strong>{{ $log->new_status ?: $issue->status }}</strong>
                                        </span>
                                    @endif
                                </div>
                                <div class="text-dark" style="font-size: 13px; line-height: 1.5;">
                                    {{ $log->note }}
                                </div>
                                @if($log->attachment_path)
                                    <div class="mt-2">
                                        <a href="{{ route('issue-follow-up.logs.attachment.view', $log->id) }}" target="_blank" class="small text-success fw-semibold text-decoration-none">
                                            <i class="bi bi-paperclip me-1"></i>Lihat Bukti Tindak Lanjut
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- KOLOM KANAN: FORM TAMBAH TINDAK LANJUT & UBAH STATUS --}}
        <div class="col-12 col-xl-4">
            <div class="ifu-card">
                <div class="ifu-card-title">
                    <span><i class="bi bi-list-check text-primary me-2"></i>Tambah Tindak Lanjut</span>
                </div>
                <form action="{{ route('issue-follow-up.follow-up', $issue->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Status Tindak Lanjut</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="In Progress" @selected($issue->status !== 'Closed')>In Progress (Sedang Ditindaklanjuti)</option>
                            <option value="Closed" @selected($issue->status === 'Closed')>Closed (Selesai &amp; Ditutup)</option>
                            <option value="Open">Open</option>
                        </select>
                        <div class="form-text small">Jika temuan berstatus Open dan mulai ditindaklanjuti, status otomatis beralih ke In Progress.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Catatan Tindak Lanjut <span class="text-danger">*</span></label>
                        <textarea name="note" rows="4" class="form-control form-control-sm" placeholder="Tuliskan progres perbaikan atau tindakan penyelesaian yang telah dilakukan..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Bukti Penyelesaian (Opsional)</label>
                        <input type="file" name="attachment" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
                    </div>

                    <button type="submit" class="btn-ifu btn-ifu-primary w-100 justify-content-center">
                        <i class="bi bi-send-fill"></i> Simpan Tindak Lanjut
                    </button>
                </form>
            </div>

            @if($issue->status !== 'Closed')
                <div class="ifu-card border-success">
                    <div class="ifu-card-title text-success">
                        <span><i class="bi bi-check-circle-fill me-2"></i>Tutup Temuan (Mark as Closed)</span>
                    </div>
                    <form action="{{ route('issue-follow-up.close', $issue->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Catatan Penutupan</label>
                            <textarea name="note" rows="2" class="form-control form-control-sm" placeholder="Catatan verifikasi penutupan temuan...">Temuan telah diverifikasi dan diselesaikan (Closed).</textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">
                            <i class="bi bi-check2-all me-1"></i> Tandai Selesai (Closed)
                        </button>
                    </form>
                </div>
            @else
                <div class="ifu-card border-warning">
                    <div class="ifu-card-title text-warning">
                        <span><i class="bi bi-arrow-counterclockwise me-2"></i>Buka Kembali Temuan (Reopen)</span>
                    </div>
                    <form action="{{ route('issue-follow-up.reopen', $issue->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Status Tujuan</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="In Progress">In Progress</option>
                                <option value="Open">Open</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Alasan Membuka Kembali <span class="text-danger">*</span></label>
                            <textarea name="note" rows="2" class="form-control form-control-sm" placeholder="Jelaskan alasan temuan dibuka kembali..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold text-dark">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Buka Kembali Temuan
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- MODAL ADD DOCUMENTATION --}}
<div class="modal fade" id="addDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('issue-follow-up.documentation.store', $issue->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold fs-6">
                        <i class="bi bi-cloud-arrow-up text-success me-1"></i> Add Documentation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih File Dokumentasi <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control form-control-sm" required accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                        <div class="form-text small text-muted">Format didukung: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX. Maksimal 15 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Keterangan / Deskripsi File (Opsional)</label>
                        <input type="text" name="caption" class="form-control form-control-sm" placeholder="Contoh: Foto kondisi setelah perbaikan">
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">
                        <i class="bi bi-upload me-1"></i> Unggah Dokumentasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openAddDocModal() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addDocModal')).show();
    }
</script>
@endpush

