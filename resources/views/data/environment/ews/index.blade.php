@extends('layouts.app', ['activeModule' => 'environment-ews', 'title' => 'Environment - EWS & Cuaca'])

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
    <!-- SUB-NAV / TABS (RUMPUN EWS & CUACA) -->
    <div class="mb-3">
        <ul class="nav nav-pills nav-fill bg-white p-1 rounded-3 shadow-sm border d-flex d-sm-inline-flex flex-wrap gap-1" style="max-width: 100%;">
            <li class="nav-item">
                <a class="nav-link active bg-primary text-white fw-semibold py-1 px-3 d-inline-flex align-items-center gap-1" href="{{ route('environment.ews.index') }}">
                    <i class="bi bi-bell"></i> Peringatan Dini (EWS)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-secondary fw-semibold py-1 px-3 d-inline-flex align-items-center gap-1" href="{{ route('environment.cuaca.index') }}">
                    <i class="bi bi-cloud-sun"></i> Pencatatan Cuaca
                </a>
            </li>
        </ul>
    </div>

    <!-- HEADER -->
    <x-module-header
        title="EWS & Cuaca — Early Warning System"
        icon="bi bi-bell"
        icon-bg="linear-gradient(135deg, #0f766e 0%, #0d9488 100%)"
        icon-shadow="rgba(15, 118, 110, 0.22)"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Environment', 'url' => route('environment.index')],
            ['label' => 'EWS'],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('environment.ews.export-pdf', request()->query()) }}" class="btn btn-danger shadow-none">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            @adminManagement
            <button type="button" class="btn btn-teal-primary hsse-action-primary shadow-none" data-bs-toggle="modal" data-bs-target="#modalTambahEws">
                <i class="bi bi-plus-lg"></i> Catat Kondisi EWS
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
            <form method="GET" action="{{ route('environment.ews.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Pencarian Kondisi</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Ketik kata kunci kondisi, lokasi, atau keterangan..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Lokasi EWS</label>
                    <select name="location" class="form-select form-select-sm">
                        <option value="">Semua Lokasi EWS</option>
                        @foreach($allLocations as $l)
                            <option value="{{ $l->id }}" {{ request('location') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Pencatatan</label>
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
                    @if(request()->hasAny(['search', 'location', 'start_date', 'end_date']))
                        <a href="{{ route('environment.ews.index') }}" class="btn btn-light border btn-sm" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL DATA EWS -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Lokasi</th>
                            <th>Tanggal Pencatatan</th>
                            <th>Kondisi Aktual</th>
                            <th>Keterangan</th>
                            <th>Dokumen Lampiran</th>
                            <th>Pencatat</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $r)
                            <tr>
                                <td class="text-center text-muted">{{ $records->firstItem() + $idx }}</td>
                                <td class="fw-bold text-dark">
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-broadcast me-1 text-danger"></i> {{ $r->location_label }}
                                    </span>
                                </td>
                                <td>{{ $r->recording_date ? $r->recording_date->format('d/m/Y') : '-' }}</td>
                                <td class="text-truncate" style="max-width: 250px;" title="{{ $r->condition }}">
                                    {{ $r->condition }}
                                </td>
                                <td class="text-truncate" style="max-width: 160px;" title="{{ $r->description }}">
                                    {{ $r->description ?? '-' }}
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
                                                        <a class="dropdown-item text-truncate d-flex align-items-center gap-2 py-1" href="{{ route('environment.files.show', ['module' => 'ews', 'id' => $r->id, 'index' => $fIdx]) }}" target="_blank">
                                                            <i class="bi bi-file-earmark-image text-success"></i>
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
                                <td><span class="small text-muted">{{ $r->recorder?->name ?? 'Sistem' }}</span></td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Detail"
                                                onclick="openDetailEwsModal({{ json_encode($r) }})">
                                            <i class="bi bi-eye text-primary"></i>
                                        </button>
                                        @adminManagement
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Edit"
                                                onclick="openEditEwsModal({{ json_encode($r) }})">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Hapus"
                                                onclick="confirmDeleteEws({{ $r->id }}, {{ \Illuminate\Support\Js::from($r->location_label) }})">
                                            <i class="bi bi-trash3 text-danger"></i>
                                        </button>
                                        @endadminManagement
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                                    Belum ada data pencatatan Early Warning System (EWS).
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
<!-- MODAL TAMBAH DATA EWS -->
<!-- ========================================================================= -->
@adminManagement
<div class="modal fade" id="modalTambahEws" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('environment.ews.store') }}" method="POST" enctype="multipart/form-data" id="formTambahEws">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-bell" style="color: #0f766e;"></i> Catat Kondisi EWS Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Lokasi EWS <span class="text-danger">*</span></label>
                            <select name="location_id" class="form-select form-select-sm" required>
                                <option value="">Pilih Lokasi EWS...</option>
                                @foreach($locations as $l)
                                    <option value="{{ $l->id }}">{{ $l->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Pencatatan <span class="text-danger">*</span></label>
                            <input type="date" name="recording_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Kondisi Aktual <span class="text-danger">*</span></label>
                            <textarea name="condition" rows="3" class="form-control form-control-sm" placeholder="Jelaskan kondisi aktual sensor, peringatan dini, alarm, atau status di lapangan..." required></textarea>
                            <div class="form-text small">Gunakan teks bebas untuk menjabarkan kondisi nyata di lokasi.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan Tambahan / Tindak Lanjut</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Catatan inspeksi atau rekomendasi perbaikan jika diperlukan"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Dokumen / Foto Lampiran EWS</label>
                            @include('data.environment.partials.uploader', [
                                'containerId' => 'ewsCreateAttachmentInputs',
                                'listContainerId' => 'ewsCreateFileList',
                                'inputName' => 'attachments[]',
                                'maxSizeMb' => 5
                            ])
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-teal-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Pencatatan EWS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDIT DATA EWS -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditEws" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST" enctype="multipart/form-data" id="formEditEws">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-warning"></i> Edit Pencatatan EWS
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Lokasi EWS <span class="text-danger">*</span></label>
                            <select name="location_id" id="editEwsLocationId" class="form-select form-select-sm" required>
                                <option value="">Pilih Lokasi...</option>
                                @foreach($allLocations as $l)
                                    <option value="{{ $l->id }}">{{ $l->name }} {{ !$l->is_active ? '(Nonaktif)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Pencatatan <span class="text-danger">*</span></label>
                            <input type="date" name="recording_date" id="editEwsRecordingDate" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Kondisi Aktual <span class="text-danger">*</span></label>
                            <textarea name="condition" id="editEwsCondition" rows="3" class="form-control form-control-sm" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan Tambahan / Tindak Lanjut</label>
                            <textarea name="description" id="editEwsDescription" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Pengelolaan Dokumen / Lampiran</label>
                            <div id="editEwsExistingFilesContainer"></div>

                            @include('data.environment.partials.uploader', [
                                'containerId' => 'ewsEditAttachmentInputs',
                                'listContainerId' => 'ewsEditFileList',
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
<form id="globalDeleteFormEws" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endadminManagement

<!-- ========================================================================= -->
<!-- MODAL DETAIL DATA EWS -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDetailEws" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                    <i class="bi bi-info-circle" style="color: #0f766e;"></i> Rincian Pencatatan EWS
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                    <tr>
                        <th class="text-secondary" style="width: 140px;">Lokasi</th>
                        <td class="fw-bold" id="detailEwsLocation">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Tanggal</th>
                        <td id="detailEwsDate">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Pencatat</th>
                        <td id="detailEwsRecorder">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Kondisi Aktual</th>
                        <td class="fw-semibold" id="detailEwsCondition">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Keterangan</th>
                        <td id="detailEwsDesc">-</td>
                    </tr>
                </table>
                <div class="mt-3 pt-2 border-top">
                    <div class="small fw-bold text-secondary mb-2">Lampiran Dokumen:</div>
                    <div id="detailEwsFilesList"></div>
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
    function openDetailEwsModal(data) {
        document.getElementById('detailEwsLocation').innerText = (data.location ? data.location.name : data.location_name) || '-';
        document.getElementById('detailEwsDate').innerText = data.recording_date ? new Date(data.recording_date).toLocaleDateString('id-ID') : '-';
        document.getElementById('detailEwsRecorder').innerText = data.recorder ? data.recorder.name : 'Sistem';
        document.getElementById('detailEwsCondition').innerText = data.condition || '-';
        document.getElementById('detailEwsDesc').innerText = data.description || '-';

        const filesCont = document.getElementById('detailEwsFilesList');
        const files = data.attachment_paths || [];
        if (files.length > 0) {
            filesCont.innerHTML = `
                <ul class="list-unstyled mb-0 d-flex flex-column gap-1">
                    ${files.map((f, idx) => {
                        const name = escapeEnvHtml(f.split('/').pop());
                        const icon = getFileIconClass(name);
                        const fileUrl = `/environment/files/ews/${encodeURIComponent(data.id)}/${idx}`;
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

        new bootstrap.Modal(document.getElementById('modalDetailEws')).show();
    }

    function openEditEwsModal(data) {
        const form = document.getElementById('formEditEws');
        form.action = `/environment/ews/${data.id}`;

        document.getElementById('editEwsLocationId').value = data.location_id || '';
        document.getElementById('editEwsRecordingDate').value = data.recording_date ? data.recording_date.split('T')[0] : '';
        document.getElementById('editEwsCondition').value = data.condition || '';
        document.getElementById('editEwsDescription').value = data.description || '';

        resetEnvUploader('ewsEditAttachmentInputs', 'ewsEditFileList');
        renderExistingEnvFiles('editEwsExistingFilesContainer', data.attachment_paths || [], 'deleted_attachments[]', 'formEditEws', 'ews', data.id);

        new bootstrap.Modal(document.getElementById('modalEditEws')).show();
    }

    function confirmDeleteEws(id, locName) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Pencatatan EWS?',
                text: `Data EWS lokasi "${locName}" dan dokumen terkait akan dihapus permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('globalDeleteFormEws');
                    form.action = `/environment/ews/${id}`;
                    form.submit();
                }
            });
        } else {
            if (confirm(`Yakin ingin menghapus data EWS "${locName}"?`)) {
                const form = document.getElementById('globalDeleteFormEws');
                form.action = `/environment/ews/${id}`;
                form.submit();
            }
        }
    }
</script>
@endpush

