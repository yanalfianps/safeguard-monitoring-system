@extends('layouts.app', ['activeModule' => 'environment-rkl-rpl', 'title' => 'Environment - RKL–RPL'])

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
        title="RKL–RPL — Pemantauan Lingkungan"
        icon="bi bi-clipboard2-pulse"
        icon-bg="linear-gradient(135deg, #0f766e 0%, #0d9488 100%)"
        icon-shadow="rgba(15, 118, 110, 0.22)"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Environment', 'url' => route('environment.index')],
            ['label' => 'RKL–RPL'],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('environment.rkl-rpl.export-pdf', request()->query()) }}" class="btn btn-danger shadow-none">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            @adminManagement
            <button type="button" class="btn btn-teal-primary hsse-action-primary shadow-none" data-bs-toggle="modal" data-bs-target="#modalTambahRklRpl">
                <i class="bi bi-plus-lg"></i> Catat Hasil Pemantauan
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
            <form method="GET" action="{{ route('environment.rkl-rpl.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Pencarian</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Parameter, lokasi, hasil, catatan..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Parameter</label>
                    <select name="parameter" class="form-select form-select-sm">
                        <option value="">Semua Parameter</option>
                        @foreach($allParameters as $p)
                            <option value="{{ $p->id }}" {{ request('parameter') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Lokasi</label>
                    <select name="location" class="form-select form-select-sm">
                        <option value="">Semua Lokasi</option>
                        @foreach($allLocations as $l)
                            <option value="{{ $l->id }}" {{ request('location') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Sampling</label>
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
                    @if(request()->hasAny(['search', 'parameter', 'location', 'start_date', 'end_date']))
                        <a href="{{ route('environment.rkl-rpl.index') }}" class="btn btn-light border btn-sm" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL DATA RKL-RPL -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Parameter Pemantauan</th>
                            <th>Lokasi Pengamatan</th>
                            <th>Tanggal Sampling</th>
                            <th>Hasil / Nilai</th>
                            <th>Satuan</th>
                            <th>Catatan</th>
                            <th>Dokumen Pengujian</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $r)
                            <tr>
                                <td class="text-center text-muted">{{ $records->firstItem() + $idx }}</td>
                                <td class="fw-bold text-dark">{{ $r->parameter_label }}</td>
                                <td><span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-geo-alt me-1"></i> {{ $r->location_label }}</span></td>
                                <td>{{ $r->sampling_date ? $r->sampling_date->format('d/m/Y') : '-' }}</td>
                                <td class="fw-bold text-primary">{{ $r->measurement_value }}</td>
                                <td><span class="badge bg-light text-secondary border">{{ $r->unit_label ?: '-' }}</span></td>
                                <td class="text-truncate" style="max-width: 180px;" title="{{ $r->description }}">{{ $r->description ?? '-' }}</td>
                                <td>
                                    @if(count($r->attachment_list) > 0)
                                        <div class="dropdown">
                                            <button class="btn btn-xs btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size: 11.5px;">
                                                <i class="bi bi-paperclip me-1"></i> {{ count($r->attachment_list) }} Dokumen
                                            </button>
                                            <ul class="dropdown-menu shadow-sm" style="font-size: 12px; max-width: 280px;">
                                                @foreach($r->attachment_list as $fIdx => $filePath)
                                                    <li>
                                                        <a class="dropdown-item text-truncate d-flex align-items-center gap-2 py-1" href="{{ route('environment.files.show', ['module' => 'rkl-rpl', 'id' => $r->id, 'index' => $fIdx]) }}" target="_blank">
                                                            <i class="bi bi-file-earmark-text text-info"></i>
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
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Detail"
                                                onclick="openDetailRklRplModal({{ json_encode($r) }})">
                                            <i class="bi bi-eye text-primary"></i>
                                        </button>
                                        @adminManagement
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Edit"
                                                onclick="openEditRklRplModal({{ json_encode($r) }})">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Hapus"
                                                onclick="confirmDeleteRklRpl({{ $r->id }}, {{ \Illuminate\Support\Js::from($r->parameter_label) }})">
                                            <i class="bi bi-trash3 text-danger"></i>
                                        </button>
                                        @endadminManagement
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                                    Belum ada data pemantauan RKL–RPL.
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
<!-- MODAL TAMBAH DATA RKL-RPL -->
<!-- ========================================================================= -->
@adminManagement
<div class="modal fade" id="modalTambahRklRpl" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('environment.rkl-rpl.store') }}" method="POST" enctype="multipart/form-data" id="formTambahRklRpl">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-clipboard2-pulse" style="color: #0f766e;"></i> Catat Hasil Pemantauan RKL–RPL
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Parameter Pemantauan <span class="text-danger">*</span></label>
                            <select name="parameter_id" class="form-select form-select-sm" required>
                                <option value="">Pilih Parameter...</option>
                                @foreach($parameters as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Lokasi Pemantauan <span class="text-danger">*</span></label>
                            <select name="location_id" class="form-select form-select-sm" required>
                                <option value="">Pilih Lokasi...</option>
                                @foreach($locations as $l)
                                    <option value="{{ $l->id }}">{{ $l->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Sampling / Pengukuran <span class="text-danger">*</span></label>
                            <input type="date" name="sampling_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Hasil / Nilai Pengamatan <span class="text-danger">*</span></label>
                            <input type="text" name="measurement_value" class="form-control form-control-sm" placeholder="Contoh: 54.2, Baik, Nihil" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Satuan Hasil <span class="text-muted fw-normal">(Jika relevan)</span></label>
                            <select name="unit_id" id="tambahRklUnitSelect" class="form-select form-select-sm">
                                <option value="">-- Tanpa Satuan / Tidak Relevan --</option>
                                @foreach($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan / Keterangan</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Catatan kondisi lapangan, cuaca, atau laboratorium penguji"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Dokumen Pendukung (Laporan Pengujian / Foto)</label>
                            @include('data.environment.partials.uploader', [
                                'containerId' => 'rklRplCreateAttachmentInputs',
                                'listContainerId' => 'rklRplCreateFileList',
                                'inputName' => 'attachments[]',
                                'maxSizeMb' => 5
                            ])
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-teal-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Hasil Pemantauan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDIT DATA RKL-RPL -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditRklRpl" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST" enctype="multipart/form-data" id="formEditRklRpl">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-warning"></i> Edit Data Pemantauan RKL–RPL
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Parameter Pemantauan <span class="text-danger">*</span></label>
                            <select name="parameter_id" id="editRklParameterId" class="form-select form-select-sm" required>
                                <option value="">Pilih Parameter...</option>
                                @foreach($allParameters as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} {{ !$p->is_active ? '(Nonaktif)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Lokasi Pemantauan <span class="text-danger">*</span></label>
                            <select name="location_id" id="editRklLocationId" class="form-select form-select-sm" required>
                                <option value="">Pilih Lokasi...</option>
                                @foreach($allLocations as $l)
                                    <option value="{{ $l->id }}">{{ $l->name }} {{ !$l->is_active ? '(Nonaktif)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Sampling <span class="text-danger">*</span></label>
                            <input type="date" name="sampling_date" id="editRklSamplingDate" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Hasil / Nilai Pengamatan <span class="text-danger">*</span></label>
                            <input type="text" name="measurement_value" id="editRklMeasurementValue" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Satuan Hasil <span class="text-muted fw-normal">(Jika relevan)</span></label>
                            <select name="unit_id" id="editRklUnitSelect" class="form-select form-select-sm">
                                <option value="">-- Tanpa Satuan / Tidak Relevan --</option>
                                @foreach($allUnits ?? $units as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} {{ !$u->is_active ? '(Nonaktif)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan / Keterangan</label>
                            <textarea name="description" id="editRklDescription" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Pengelolaan Dokumen / Lampiran</label>
                            <div id="editRklExistingFilesContainer"></div>

                            @include('data.environment.partials.uploader', [
                                'containerId' => 'rklRplEditAttachmentInputs',
                                'listContainerId' => 'rklRplEditFileList',
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
<form id="globalDeleteFormRklRpl" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endadminManagement

<!-- ========================================================================= -->
<!-- MODAL DETAIL DATA RKL-RPL -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDetailRklRpl" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                    <i class="bi bi-info-circle" style="color: #0f766e;"></i> Rincian Pemantauan RKL–RPL
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                    <tr>
                        <th class="text-secondary" style="width: 150px;">Parameter</th>
                        <td class="fw-bold" id="detailRklParameter">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Lokasi Pemantauan</th>
                        <td id="detailRklLocation">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Tanggal Sampling</th>
                        <td id="detailRklSamplingDate">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Hasil / Nilai</th>
                        <td class="fw-bold text-primary" id="detailRklValue">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Satuan</th>
                        <td id="detailRklUnit">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Keterangan / Catatan</th>
                        <td id="detailRklDesc">-</td>
                    </tr>
                </table>
                <div class="mt-3 pt-2 border-top">
                    <div class="small fw-bold text-secondary mb-2">Lampiran Dokumen:</div>
                    <div id="detailRklFilesList"></div>
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
    function openDetailRklRplModal(data) {
        document.getElementById('detailRklParameter').innerText = (data.parameter ? data.parameter.name : data.parameter_name) || '-';
        document.getElementById('detailRklLocation').innerText = (data.location ? data.location.name : data.location_name) || '-';
        document.getElementById('detailRklSamplingDate').innerText = data.sampling_date ? new Date(data.sampling_date).toLocaleDateString('id-ID') : '-';
        document.getElementById('detailRklValue').innerText = data.measurement_value || '-';
        document.getElementById('detailRklUnit').innerText = (data.unit ? data.unit.name : data.unit_name) || '-';
        document.getElementById('detailRklDesc').innerText = data.description || '-';

        const filesCont = document.getElementById('detailRklFilesList');
        const files = data.attachment_paths || [];
        if (files.length > 0) {
            filesCont.innerHTML = `
                <ul class="list-unstyled mb-0 d-flex flex-column gap-1">
                    ${files.map((f, idx) => {
                        const name = escapeEnvHtml(f.split('/').pop());
                        const icon = getFileIconClass(name);
                        const fileUrl = `/environment/files/rkl-rpl/${encodeURIComponent(data.id)}/${idx}`;
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

        new bootstrap.Modal(document.getElementById('modalDetailRklRpl')).show();
    }

    function openEditRklRplModal(data) {
        const form = document.getElementById('formEditRklRpl');
        form.action = `/environment/rkl-rpl/${data.id}`;

        document.getElementById('editRklParameterId').value = data.parameter_id || '';
        document.getElementById('editRklLocationId').value = data.location_id || '';
        document.getElementById('editRklSamplingDate').value = data.sampling_date ? data.sampling_date.split('T')[0] : '';
        document.getElementById('editRklMeasurementValue').value = data.measurement_value || '';
        document.getElementById('editRklUnitSelect').value = data.unit_id || '';
        document.getElementById('editRklDescription').value = data.description || '';

        resetEnvUploader('rklRplEditAttachmentInputs', 'rklRplEditFileList');
        renderExistingEnvFiles('editRklExistingFilesContainer', data.attachment_paths || [], 'deleted_attachments[]', 'formEditRklRpl', 'rkl-rpl', data.id);

        new bootstrap.Modal(document.getElementById('modalEditRklRpl')).show();
    }

    function confirmDeleteRklRpl(id, paramName) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Data RKL–RPL?',
                text: `Data pemantauan "${paramName}" dan dokumen terkait akan dihapus permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('globalDeleteFormRklRpl');
                    form.action = `/environment/rkl-rpl/${id}`;
                    form.submit();
                }
            });
        } else {
            if (confirm(`Yakin ingin menghapus data pemantauan "${paramName}"?`)) {
                const form = document.getElementById('globalDeleteFormRklRpl');
                form.action = `/environment/rkl-rpl/${id}`;
                form.submit();
            }
        }
    }
</script>
@endpush

