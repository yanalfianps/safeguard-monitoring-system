@extends('layouts.app', ['activeModule' => 'environment-lb3', 'title' => 'Environment - LB3'])

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
        title="LB3 — Limbah B3"
        icon="bi bi-radioactive"
        icon-bg="linear-gradient(135deg, #0f766e 0%, #0d9488 100%)"
        icon-shadow="rgba(15, 118, 110, 0.22)"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Environment', 'url' => route('environment.index')],
            ['label' => 'LB3'],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('environment.lb3.export-pdf', request()->query()) }}" class="btn btn-danger shadow-none">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            @adminManagement
            <button type="button" class="btn btn-teal-primary hsse-action-primary shadow-none" data-bs-toggle="modal" data-bs-target="#modalTambahLb3">
                <i class="bi bi-plus-lg"></i> Tambah Data LB3
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
            <form method="GET" action="{{ route('environment.lb3.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Pencarian</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama limbah atau keterangan..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Satuan</label>
                    <select name="unit" class="form-select form-select-sm">
                        <option value="">Semua Satuan</option>
                        @foreach($allUnits as $u)
                            <option value="{{ $u->id }}" {{ request('unit') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary mb-1">Karakteristik</label>
                    <select name="characteristic" class="form-select form-select-sm">
                        <option value="">Semua Karakteristik</option>
                        @foreach($characteristics as $c)
                            <option value="{{ $c->name }}" {{ request('characteristic') == $c->name ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Masuk</label>
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
                    @if(request()->hasAny(['search', 'unit', 'characteristic', 'start_date', 'end_date']))
                        <a href="{{ route('environment.lb3.index') }}" class="btn btn-light border btn-sm" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL DATA LB3 -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Limbah</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                            <th>Tanggal Masuk</th>
                            <th>Karakteristik</th>
                            <th>Lampiran</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $idx => $r)
                            <tr>
                                <td class="text-center text-muted">{{ $records->firstItem() + $idx }}</td>
                                <td class="fw-bold text-dark">{{ $r->waste_name }}</td>
                                <td class="fw-semibold text-danger">{{ number_format($r->amount, 2, ',', '.') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $r->unit_label }}</span></td>
                                <td>{{ $r->entry_date ? $r->entry_date->format('d/m/Y') : '-' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($r->characteristics_list as $char)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 10.5px;">
                                                {{ $char }}
                                            </span>
                                        @empty
                                            <span class="text-muted small">-</span>
                                        @endforelse
                                    </div>
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
                                                        <a class="dropdown-item text-truncate d-flex align-items-center gap-2 py-1" href="{{ route('environment.files.show', ['module' => 'lb3', 'id' => $r->id, 'index' => $fIdx]) }}" target="_blank">
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
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Detail"
                                                onclick="openDetailLb3Modal({{ json_encode($r) }})">
                                            <i class="bi bi-eye text-primary"></i>
                                        </button>
                                        @adminManagement
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Edit"
                                                onclick="openEditLb3Modal({{ json_encode($r) }})">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2" title="Hapus"
                                                onclick="confirmDeleteLb3({{ $r->id }}, {{ \Illuminate\Support\Js::from($r->waste_name) }})">
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
                                    Belum ada catatan limbah B3.
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
<!-- MODAL TAMBAH DATA LB3 -->
<!-- ========================================================================= -->
@adminManagement
<div class="modal fade" id="modalTambahLb3" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('environment.lb3.store') }}" method="POST" enctype="multipart/form-data" id="formTambahLb3">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-radioactive" style="color: #0f766e;"></i> Catat Limbah B3 Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Limbah <span class="text-danger">*</span></label>
                            <input type="text" name="waste_name" class="form-control form-control-sm" placeholder="Contoh: Oli Bekas, Filter Bekas" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" step="any" min="0.001" name="amount" class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Satuan <span class="text-danger">*</span></label>
                            <select name="unit_id" class="form-select form-select-sm" required>
                                <option value="">Pilih Satuan...</option>
                                @foreach($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Masuk <span class="text-danger">*</span></label>
                            <input type="date" name="entry_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">Karakteristik Limbah B3</label>
                            <div class="d-flex flex-wrap gap-2 p-2 rounded bg-light border">
                                @foreach($characteristics as $c)
                                    <div class="form-check form-check-inline mb-0" style="font-size: 12.5px;">
                                        <input class="form-check-input" type="checkbox" name="characteristics[]" value="{{ $c->name }}" id="char_create_{{ $c->id }}">
                                        <label class="form-check-label" for="char_create_{{ $c->id }}">{{ $c->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan Tambahan</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Catatan lokasi penampungan, sumber, atau perlakuan khusus"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Dokumen / Bukti Pendukung</label>
                            @include('data.environment.partials.uploader', [
                                'containerId' => 'lb3CreateAttachmentInputs',
                                'listContainerId' => 'lb3CreateFileList',
                                'inputName' => 'attachments[]',
                                'maxSizeMb' => 5
                            ])
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-teal-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Data LB3</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDIT DATA LB3 -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditLb3" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST" enctype="multipart/form-data" id="formEditLb3">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-warning"></i> Edit Data LB3
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Limbah <span class="text-danger">*</span></label>
                            <input type="text" name="waste_name" id="editLb3WasteName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" step="any" min="0.001" name="amount" id="editLb3Amount" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Satuan <span class="text-danger">*</span></label>
                            <select name="unit_id" id="editLb3UnitId" class="form-select form-select-sm" required>
                                <option value="">Pilih Satuan...</option>
                                @foreach($allUnits as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} {{ !$u->is_active ? '(Nonaktif)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Masuk <span class="text-danger">*</span></label>
                            <input type="date" name="entry_date" id="editLb3EntryDate" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">Karakteristik Limbah B3</label>
                            <div class="d-flex flex-wrap gap-2 p-2 rounded bg-light border" id="editLb3CharacteristicsContainer">
                                @foreach($characteristics as $c)
                                    <div class="form-check form-check-inline mb-0" style="font-size: 12.5px;">
                                        <input class="form-check-input edit-char-checkbox" type="checkbox" name="characteristics[]" value="{{ $c->name }}" id="char_edit_{{ $c->id }}">
                                        <label class="form-check-label" for="char_edit_{{ $c->id }}">{{ $c->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan Tambahan</label>
                            <textarea name="description" id="editLb3Description" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Pengelolaan Dokumen / Lampiran</label>
                            <!-- Existing stored files container -->
                            <div id="editLb3ExistingFilesContainer"></div>

                            <!-- Uploader for appending new files -->
                            @include('data.environment.partials.uploader', [
                                'containerId' => 'lb3EditAttachmentInputs',
                                'listContainerId' => 'lb3EditFileList',
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
<form id="globalDeleteFormLb3" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endadminManagement

<!-- ========================================================================= -->
<!-- MODAL DETAIL DATA LB3 -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDetailLb3" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark d-inline-flex align-items-center gap-2">
                    <i class="bi bi-info-circle" style="color: #0f766e;"></i> Rincian Data LB3
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                    <tr>
                        <th class="text-secondary" style="width: 140px;">Nama Limbah</th>
                        <td class="fw-bold" id="detailLb3Name">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Jumlah</th>
                        <td class="fw-bold text-danger" id="detailLb3Amount">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Satuan</th>
                        <td id="detailLb3Unit">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Tanggal Masuk</th>
                        <td id="detailLb3Date">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Karakteristik</th>
                        <td id="detailLb3Characteristics">-</td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Keterangan</th>
                        <td id="detailLb3Desc">-</td>
                    </tr>
                </table>
                <div class="mt-3 pt-2 border-top">
                    <div class="small fw-bold text-secondary mb-2">Lampiran Dokumen:</div>
                    <div id="detailLb3FilesList"></div>
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
    function openDetailLb3Modal(data) {
        document.getElementById('detailLb3Name').innerText = data.waste_name || '-';
        document.getElementById('detailLb3Amount').innerText = Number(data.amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 2 });
        document.getElementById('detailLb3Unit').innerText = (data.unit ? data.unit.name : data.unit_name) || '-';
        document.getElementById('detailLb3Date').innerText = data.entry_date ? new Date(data.entry_date).toLocaleDateString('id-ID') : '-';
        document.getElementById('detailLb3Desc').innerText = data.description || '-';

        const charCont = document.getElementById('detailLb3Characteristics');
        if (data.characteristics && Array.isArray(data.characteristics) && data.characteristics.length > 0) {
            charCont.innerHTML = data.characteristics.map(c => `<span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1">${escapeEnvHtml(c)}</span>`).join('');
        } else {
            charCont.innerText = '-';
        }

        const filesCont = document.getElementById('detailLb3FilesList');
        const files = data.attachment_paths || [];
        if (files.length > 0) {
            filesCont.innerHTML = `
                <ul class="list-unstyled mb-0 d-flex flex-column gap-1">
                    ${files.map((f, idx) => {
                        const name = escapeEnvHtml(f.split('/').pop());
                        const icon = getFileIconClass(name);
                        const fileUrl = `/environment/files/lb3/${encodeURIComponent(data.id)}/${idx}`;
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

        new bootstrap.Modal(document.getElementById('modalDetailLb3')).show();
    }

    function openEditLb3Modal(data) {
        const form = document.getElementById('formEditLb3');
        form.action = `/environment/lb3/${data.id}`;

        document.getElementById('editLb3WasteName').value = data.waste_name || '';
        document.getElementById('editLb3Amount').value = data.amount || 0;
        document.getElementById('editLb3UnitId').value = data.unit_id || '';
        document.getElementById('editLb3EntryDate').value = data.entry_date ? data.entry_date.split('T')[0] : '';
        document.getElementById('editLb3Description').value = data.description || '';

        // Checkboxes characteristics
        const chars = data.characteristics || [];
        document.querySelectorAll('.edit-char-checkbox').forEach(cb => {
            cb.checked = chars.includes(cb.value);
        });

        // Reset new uploader
        resetEnvUploader('lb3EditAttachmentInputs', 'lb3EditFileList');

        // Render existing files
        renderExistingEnvFiles('editLb3ExistingFilesContainer', data.attachment_paths || [], 'deleted_attachments[]', 'formEditLb3', 'lb3', data.id);

        new bootstrap.Modal(document.getElementById('modalEditLb3')).show();
    }

    function confirmDeleteLb3(id, wasteName) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Data LB3?',
                text: `Data "${wasteName}" dan seluruh dokumen lampirannya akan dihapus permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('globalDeleteFormLb3');
                    form.action = `/environment/lb3/${id}`;
                    form.submit();
                }
            });
        } else {
            if (confirm(`Yakin ingin menghapus data LB3 "${wasteName}"?`)) {
                const form = document.getElementById('globalDeleteFormLb3');
                form.action = `/environment/lb3/${id}`;
                form.submit();
            }
        }
    }
</script>
@endpush

