@extends('layouts.app', ['activeModule' => 'environment-inspeksi', 'title' => 'Environment - Inspeksi'])

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
        title="Inspeksi Lingkungan"
        icon="bi bi-clipboard-check"
        icon-bg="linear-gradient(135deg, #0f766e 0%, #0d9488 100%)"
        icon-shadow="rgba(15, 118, 110, 0.22)"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Environment', 'url' => route('environment.index')],
            ['label' => 'Inspeksi'],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('environment.inspeksi.export-pdf', request()->query()) }}" class="btn btn-danger shadow-none">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            @adminManagement
            <button type="button" class="btn btn-teal-primary hsse-action-primary shadow-none" data-bs-toggle="modal" data-bs-target="#modalTambahInspeksi">
                <i class="bi bi-plus-lg"></i> Catat Hasil Inspeksi
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

    <!-- FILTER CARD -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('environment.inspeksi.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Pencarian</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik kata kunci kondisi, lokasi..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Jenis Inspeksi</label>
                    <select name="inspection_type" class="form-select form-select-sm">
                        <option value="">Semua Jenis Inspeksi</option>
                        @foreach($typeOptions as $optType)
                            <option value="{{ $optType }}" {{ request('inspection_type') == $optType ? 'selected' : '' }}>{{ $optType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Lokasi</label>
                    <select name="location" class="form-select form-select-sm">
                        <option value="">Semua Lokasi</option>
                        @foreach($locationOptions as $optLoc)
                            <option value="{{ $optLoc }}" {{ (request('location') == $optLoc || request('monitoring_lokasi') == $optLoc) ? 'selected' : '' }}>{{ $optLoc }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Rentang Tanggal</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        <span class="input-group-text">-</span>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill" title="Terapkan Filter">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request()->hasAny(['search', 'inspection_type', 'location', 'monitoring_lokasi', 'start_date', 'end_date']))
                        <a href="{{ route('environment.inspeksi.index') }}" class="btn btn-light border btn-sm" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL DATA INSPEKSI -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th style="width: 120px;">Tanggal</th>
                            <th style="width: 160px;">Jenis Inspeksi</th>
                            <th style="width: 170px;">Lokasi</th>
                            <th>Kondisi Hasil Inspeksi</th>
                            <th style="width: 150px;">Dokumen Lampiran</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $r)
                            @php
                                $typeBadgeClass = match($r->inspection_type_label) {
                                    'Spill Kit' => 'bg-danger-subtle text-danger border-danger-subtle',
                                    'Tempat Sampah' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                    'TPS LB3' => 'bg-dark-subtle text-dark border-dark-subtle',
                                    'PPKH/Lahan' => 'bg-success-subtle text-success border-success-subtle',
                                    default => 'bg-primary-subtle text-primary border-primary-subtle',
                                };
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $records->firstItem() + $idx }}</td>
                                <td class="text-secondary fw-semibold">
                                    {{ $r->formatted_date }}
                                </td>
                                <td>
                                    <span class="badge {{ $typeBadgeClass }} border px-2 py-1 fw-bold">
                                        <i class="bi bi-tag-fill me-1"></i> {{ $r->inspection_type_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <i class="bi bi-geo-alt-fill text-teal-primary me-1" style="color: #0f766e;"></i> {{ $r->location_label }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-normal text-dark text-break">{{ $r->condition_text }}</div>
                                </td>
                                <td>
                                    @if(count($r->attachment_list) > 0)
                                        <div class="dropdown">
                                            <button class="btn btn-xs btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size: 11.5px;">
                                                <i class="bi bi-paperclip me-1"></i> {{ count($r->attachment_list) }} Dokumen
                                            </button>
                                            <ul class="dropdown-menu shadow-sm" style="font-size: 12px; max-width: 280px;">
                                                @foreach($r->attachment_list as $fIdx => $filePath)
                                                    <li>
                                                        <a class="dropdown-item text-truncate d-flex align-items-center gap-2 py-1" href="{{ route('environment.files.show', ['module' => 'inspeksi', 'id' => $r->id, 'index' => $fIdx]) }}" target="_blank">
                                                            <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                            <span class="text-truncate">{{ basename($filePath) }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <span class="text-muted small">Tidak ada</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Detail Rincian"
                                                onclick="openDetailInspeksiModal({{ json_encode($r) }})">
                                            <i class="bi bi-eye text-primary"></i>
                                        </button>
                                        @adminManagement
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Edit Data"
                                                onclick="openEditInspeksiModal({{ json_encode($r) }})">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Hapus Data"
                                                onclick="confirmDeleteInspeksi({{ $r->id }}, {{ \Illuminate\Support\Js::from($r->inspection_type_label . ' di ' . $r->location_label) }})">
                                            <i class="bi bi-trash3 text-danger"></i>
                                        </button>
                                        @endadminManagement
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                                    Belum ada data catatan inspeksi.
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
<!-- MODAL TAMBAH DATA INSPEKSI -->
<!-- ========================================================================= -->
@adminManagement
<div class="modal fade" id="modalTambahInspeksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('environment.inspeksi.store') }}" method="POST" enctype="multipart/form-data" id="formTambahInspeksi">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-clipboard-check" style="color: #0f766e;"></i> Catat Hasil Inspeksi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Tanggal Inspeksi -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Inspeksi <span class="text-danger">*</span></label>
                            <input type="date" name="inspection_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <!-- 1. Inspeksi (Dropdown) -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Inspeksi (Jenis) <span class="text-danger">*</span></label>
                            <select name="inspection_type" class="form-select form-select-sm" required>
                                <option value="">Pilih Jenis Inspeksi...</option>
                                @foreach($typeOptions as $optType)
                                    <option value="{{ $optType }}">{{ $optType }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 2. Lokasi (Dropdown) -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Lokasi <span class="text-danger">*</span></label>
                            <select name="location_name" class="form-select form-select-sm" required>
                                <option value="">Pilih Lokasi...</option>
                                @foreach($locationOptions as $optLoc)
                                    <option value="{{ $optLoc }}">{{ $optLoc }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 3. Kondisi (Free Text / Textarea) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Kondisi Hasil Inspeksi <span class="text-danger">*</span></label>
                            <textarea name="condition" rows="3" class="form-control form-control-sm" placeholder="Tuliskan deskripsi kondisi hasil inspeksi di lapangan secara bebas dan mendalam..." required></textarea>
                            <div class="form-text small text-muted">Deskripsikan kondisi fisik, kelayakan, temuan, atau catatan kebersihan di area inspeksi.</div>
                        </div>

                        <!-- 4. Upload Dokumen -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Dokumen / Bukti Foto Lampiran</label>
                            @include('data.environment.partials.uploader', [
                                'containerId' => 'inspeksiCreateAttachmentInputs',
                                'listContainerId' => 'inspeksiCreateFileList',
                                'inputName' => 'attachments[]',
                                'maxSizeMb' => 5
                            ])
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-teal-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Data Inspeksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDIT DATA INSPEKSI -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditInspeksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST" enctype="multipart/form-data" id="formEditInspeksi">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-warning"></i> Edit Data Inspeksi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Tanggal Inspeksi -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Inspeksi <span class="text-danger">*</span></label>
                            <input type="date" name="inspection_date" id="editInspeksiDate" class="form-control form-control-sm" required>
                        </div>

                        <!-- 1. Inspeksi (Dropdown) -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Inspeksi (Jenis) <span class="text-danger">*</span></label>
                            <select name="inspection_type" id="editInspeksiType" class="form-select form-select-sm" required>
                                <option value="">Pilih Jenis Inspeksi...</option>
                                @foreach($typeOptions as $optType)
                                    <option value="{{ $optType }}">{{ $optType }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 2. Lokasi (Dropdown) -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Lokasi <span class="text-danger">*</span></label>
                            <select name="location_name" id="editInspeksiLocation" class="form-select form-select-sm" required>
                                <option value="">Pilih Lokasi...</option>
                                @foreach($locationOptions as $optLoc)
                                    <option value="{{ $optLoc }}">{{ $optLoc }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 3. Kondisi (Free Text) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Kondisi Hasil Inspeksi <span class="text-danger">*</span></label>
                            <textarea name="condition" id="editInspeksiCondition" rows="3" class="form-control form-control-sm" placeholder="Tuliskan deskripsi kondisi hasil inspeksi..." required></textarea>
                        </div>

                        <!-- 4. Dokumen Lampiran -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Pengelolaan Dokumen / Lampiran</label>
                            <div id="editInspeksiExistingFilesContainer"></div>

                            @include('data.environment.partials.uploader', [
                                'containerId' => 'inspeksiEditAttachmentInputs',
                                'listContainerId' => 'inspeksiEditFileList',
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
<form id="globalDeleteFormInspeksi" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endadminManagement

<!-- ========================================================================= -->
<!-- MODAL DETAIL DATA INSPEKSI -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDetailInspeksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-teal-primary" style="color: #0f766e;"></i> Detail Hasil Inspeksi Lingkungan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                            <tr>
                                <th class="text-secondary" style="width: 130px;">Jenis Inspeksi:</th>
                                <td><span class="badge bg-primary-subtle text-primary fw-bold" id="detailInspeksiType">-</span></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Lokasi:</th>
                                <td class="fw-bold text-dark" id="detailInspeksiLocation">-</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                            <tr>
                                <th class="text-secondary" style="width: 130px;">Tanggal Catat:</th>
                                <td id="detailInspeksiDate">-</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Pencatat:</th>
                                <td id="detailInspeksiCreator" class="text-secondary">-</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card bg-light border-0 rounded-2 p-3 mb-3">
                    <div class="small fw-bold text-secondary mb-1">Deskripsi Kondisi Hasil Inspeksi:</div>
                    <div id="detailInspeksiCondition" class="fw-normal text-dark text-break" style="white-space: pre-line; font-size: 13.5px;">-</div>
                </div>

                <div>
                    <div class="small fw-bold text-secondary mb-2">Dokumen / Bukti Lampiran:</div>
                    <div id="detailInspeksiFilesList"></div>
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
    function openDetailInspeksiModal(data) {
        document.getElementById('detailInspeksiType').innerText = data.inspection_type_label || (data.inspection_type || 'PPKH/Lahan');
        document.getElementById('detailInspeksiLocation').innerText = data.location_label || (data.location_name || data.reference_name) || '-';

        const rawDate = data.inspection_date || data.sampling_date || data.created_at;
        document.getElementById('detailInspeksiDate').innerText = rawDate ? new Date(rawDate).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) : '-';
        document.getElementById('detailInspeksiCreator').innerText = data.creator ? data.creator.name : (data.recorder ? data.recorder.name : 'Administrator HSSE');
        document.getElementById('detailInspeksiCondition').innerText = data.condition_text || (data.condition || data.activity_name || data.description) || '-';

        const filesCont = document.getElementById('detailInspeksiFilesList');
        const files = data.attachment_paths || [];
        if (files.length > 0) {
            filesCont.innerHTML = `
                <ul class="list-unstyled mb-0 d-flex flex-column gap-1">
                    ${files.map((f, idx) => {
                        const name = escapeEnvHtml(f.split('/').pop());
                        const icon = getFileIconClass(name);
                        const fileUrl = `/environment/files/inspeksi/${encodeURIComponent(data.id)}/${idx}`;
                        return `
                            <li class="py-1 px-2 rounded bg-white border d-flex align-items-center justify-content-between" style="font-size: 12px;">
                                <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                    <i class="bi ${icon}"></i>
                                    <span class="text-truncate">${name}</span>
                                </div>
                                <a href="${fileUrl}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Dokumen
                                </a>
                            </li>
                        `;
                    }).join('')}
                </ul>
            `;
        } else {
            filesCont.innerHTML = '<span class="text-muted small">Tidak ada dokumen dilampirkan.</span>';
        }

        new bootstrap.Modal(document.getElementById('modalDetailInspeksi')).show();
    }

    function openEditInspeksiModal(data) {
        const form = document.getElementById('formEditInspeksi');
        form.action = `/environment/inspeksi/${data.id}`;

        const rawDate = data.inspection_date || data.sampling_date || data.created_at;
        if (rawDate) {
            document.getElementById('editInspeksiDate').value = String(rawDate).substring(0, 10);
        }

        const type = data.inspection_type || data.inspection_type_label || 'PPKH/Lahan';
        document.getElementById('editInspeksiType').value = type;

        const lokasi = data.location_name || data.reference_name || data.monitoring_lokasi || 'Area Well Pad';
        document.getElementById('editInspeksiLocation').value = lokasi;

        document.getElementById('editInspeksiCondition').value = data.condition || data.activity_name || data.description || '';

        resetEnvUploader('inspeksiEditAttachmentInputs', 'inspeksiEditFileList');
        renderExistingEnvFiles('editInspeksiExistingFilesContainer', data.attachment_paths || [], 'deleted_attachments[]', 'formEditInspeksi', 'inspeksi', data.id);

        new bootstrap.Modal(document.getElementById('modalEditInspeksi')).show();
    }

    function confirmDeleteInspeksi(id, label) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Data Inspeksi?',
                text: `Data "${label}" beserta seluruh lampiran terkait akan dihapus permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('globalDeleteFormInspeksi');
                    form.action = `/environment/inspeksi/${id}`;
                    form.submit();
                }
            });
        } else {
            if (confirm(`Yakin ingin menghapus data inspeksi "${label}"?`)) {
                const form = document.getElementById('globalDeleteFormInspeksi');
                form.action = `/environment/inspeksi/${id}`;
                form.submit();
            }
        }
    }
</script>
@endpush

