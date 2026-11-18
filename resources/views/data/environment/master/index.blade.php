@extends('layouts.app', ['activeModule' => 'environment-master', 'title' => 'Environment - Master Data'])

@push('styles')
<style>
    .env-header-icon {
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
        color: #ffffff;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(15, 118, 110, 0.2);
    }
    .category-tabs-container {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: thin;
    }
    .category-tabs-container::-webkit-scrollbar {
        height: 4px;
    }
    .category-tabs-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .category-pill {
        border-radius: 8px;
        font-size: 12px;
        padding: 6px 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        transition: all 0.15s ease;
        border: 1px solid transparent;
    }
    .category-pill.active {
        background: #0f766e;
        color: #ffffff;
        border-color: #0f766e;
        box-shadow: 0 2px 4px rgba(15, 118, 110, 0.18);
    }
    .category-pill:not(.active) {
        background: #ffffff;
        color: #475569;
        border-color: #e2e8f0;
    }
    .category-pill:not(.active):hover {
        background: #f8fafc;
        color: #0f766e;
        border-color: #cbd5e1;
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
    /* Pagination fixes */
    .pagination {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 3px;
        list-style: none;
        padding: 0;
    }
    .pagination svg,
    .page-link svg {
        width: 14px !important;
        height: 14px !important;
        max-width: 14px !important;
        max-height: 14px !important;
        display: inline-block !important;
        vertical-align: middle !important;
    }
    .pagination .page-item .page-link {
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        color: #334155;
        padding: 4px 10px;
        font-size: 12px;
        line-height: 1.4;
        text-decoration: none;
        background-color: #ffffff;
        transition: all 0.15s ease;
    }
    .pagination .page-item.active .page-link {
        background-color: #0f766e;
        border-color: #0f766e;
        color: #ffffff;
        font-weight: 600;
    }
    .pagination .page-item.disabled .page-link {
        color: #94a3b8;
        background-color: #f8fafc;
        border-color: #e2e8f0;
        cursor: not-allowed;
    }
    .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: #f0fdfa;
        color: #0f766e;
        border-color: #99f6e4;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    <!-- HEADER -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="env-header-icon shadow-sm">
                <i class="bi bi-sliders"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark" style="font-size: 1.25rem;">Master Data Environment</h4>
                <div class="text-secondary small">Pengelolaan Master Satuan, Karakteristik LB3, Parameter &amp; Lokasi Pemantauan</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('environment.index') }}" class="btn btn-outline-secondary btn-sm shadow-none d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Kembali ke Ringkasan
            </a>
            @adminManagement
            <button type="button" class="btn btn-teal-primary btn-sm shadow-none d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalTambahMaster">
                <i class="bi bi-plus-lg"></i> Tambah Master Data
            </button>
            @endadminManagement
        </div>
    </div>

    <!-- FLASH ALERTS -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3 py-2 px-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3 py-2 px-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- CATEGORY TABS FILTER -->
    <div class="category-tabs-container mb-3">
        <a href="{{ route('environment.master.index', ['category' => 'all']) }}"
           class="category-pill {{ $selectedCategory === 'all' ? 'active' : '' }}">
            <i class="bi bi-grid-fill"></i> Semua Kategori Aktif
        </a>
        @foreach($categoryLabels as $catKey => $catLabel)
            <a href="{{ route('environment.master.index', ['category' => $catKey]) }}"
               class="category-pill {{ $selectedCategory === $catKey ? 'active' : '' }}">
                @if($catKey === 'unit')
                    <i class="bi bi-rulers"></i>
                @elseif($catKey === 'lb3_characteristic')
                    <i class="bi bi-radioactive"></i>
                @elseif($catKey === 'rkl_rpl_parameter')
                    <i class="bi bi-card-checklist"></i>
                @elseif($catKey === 'rkl_rpl_location')
                    <i class="bi bi-geo-alt"></i>
                @elseif($catKey === 'ews_location')
                    <i class="bi bi-bell"></i>
                @elseif($catKey === 'inspection_type')
                    <i class="bi bi-clipboard-check"></i>
                @elseif($catKey === 'inspection_location')
                    <i class="bi bi-geo-alt"></i>
                @elseif($catKey === 'weather_parameter')
                    <i class="bi bi-cloud-sun"></i>
                @else
                    <i class="bi bi-tag"></i>
                @endif
                {{ $catLabel }}
            </a>
        @endforeach
    </div>

    <!-- PENCARIAN -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-2 px-3">
            <form method="GET" action="{{ route('environment.master.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
                <div class="col-md-5 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari nama atau kode/simbol..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm d-inline-flex align-items-center gap-1">
                        Cari
                    </button>
                    @if(request()->filled('search'))
                        <a href="{{ route('environment.master.index', ['category' => $selectedCategory]) }}" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" title="Reset Pencarian">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL MASTER DATA -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 45px;">No</th>
                            <th style="width: 180px;">Kategori Master</th>
                            <th>Nama Master / Satuan</th>
                            <th style="width: 130px;">Kode / Simbol</th>
                            <th>Keterangan / Deskripsi</th>
                            <th class="text-center" style="width: 95px;">Status</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($masters as $idx => $m)
                            <tr>
                                <td class="text-center text-muted">{{ $masters->firstItem() + $idx }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border" style="font-size: 11px; font-weight: 500;">
                                        {{ $allCategoryLabels[$m->category] ?? ($categoryLabels[$m->category] ?? $m->category) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $m->name }}</div>
                                    @if($m->category === 'unit' && !empty($m->submodules))
                                        <div class="mt-1 d-flex flex-wrap gap-1">
                                            @foreach($m->submodule_labels as $subLabel)
                                                <span class="badge border" style="background-color: #f0fdfa; color: #0f766e; border-color: #99f6e4 !important; font-size: 9.5px; font-weight: 500;">
                                                    <i class="bi bi-tag-fill me-0.5" style="font-size: 8px;"></i> {{ $subLabel }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($m->code)
                                        <code class="text-dark bg-light px-2 py-0.5 rounded border" style="font-size: 11px;">{{ $m->code }}</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-secondary small">{{ $m->description ?? '-' }}</td>
                                <td class="text-center">
                                    @if($m->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size: 11px;">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                        @adminManagement
                                        <!-- Toggle Status Aktif/Nonaktif -->
                                        <form action="{{ route('environment.master.toggle', $m->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-xs {{ $m->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} py-1 px-1.5 d-inline-flex align-items-center justify-content-center"
                                                    title="{{ $m->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" style="height: 28px; width: 28px;">
                                                <i class="bi {{ $m->is_active ? 'bi-toggle-on text-success fs-5' : 'bi-toggle-off text-muted fs-5' }}"></i>
                                            </button>
                                        </form>

                                        <!-- Edit Modal Button -->
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-1.5 d-inline-flex align-items-center justify-content-center" title="Edit"
                                                style="height: 28px; width: 28px;"
                                                onclick="openEditMasterModal({{ json_encode($m) }})">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>

                                        <!-- Hapus Button -->
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-1.5 d-inline-flex align-items-center justify-content-center" title="Hapus"
                                                style="height: 28px; width: 28px;"
                                                onclick="confirmDeleteMaster({{ $m->id }}, {{ \Illuminate\Support\Js::from($m->name) }})">
                                            <i class="bi bi-trash3 text-danger"></i>
                                        </button>
                                        @endadminManagement
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                    Tidak ada data master ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($masters->hasPages())
            <div class="card-footer bg-white border-top py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    Menampilkan <strong>{{ $masters->firstItem() }}</strong> - <strong>{{ $masters->lastItem() }}</strong> dari <strong>{{ $masters->total() }}</strong> master
                </div>
                <div>
                    {{ $masters->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL TAMBAH MASTER DATA -->
<!-- ========================================================================= -->
@adminManagement
<div class="modal fade" id="modalTambahMaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('environment.master.store') }}" method="POST" id="formTambahMaster">
                @csrf
                <div class="modal-header bg-light border-bottom py-2 px-3">
                    <h5 class="modal-title fw-bold text-dark fs-6 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle text-teal" style="color: #0f766e;"></i> Tambah Master Data Environment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Kategori Master <span class="text-danger">*</span></label>
                            <select name="category" id="tambahMasterCategory" class="form-select form-select-sm" required>
                                <option value="">Pilih Kategori Aktif...</option>
                                @foreach($categoryLabels as $catKey => $catLabel)
                                    <option value="{{ $catKey }}" {{ $selectedCategory === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark" id="tambahMasterNameLabel">Nama Master <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="tambahMasterNameInput" class="form-control form-control-sm" placeholder="Contoh: Kilogram (kg), Area Power Plant" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Kode / Simbol <span class="text-muted fw-normal">(Opsional)</span></label>
                            <input type="text" name="code" class="form-control form-control-sm" placeholder="Contoh: kg, mg/L, LOC_01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Urutan Tampil</label>
                            <input type="number" name="order_index" class="form-control form-control-sm" value="0" min="0">
                        </div>

                        <!-- SUBMODULE MAPPING KHUSUS SATUAN -->
                        <div class="col-12" id="tambahSubmodulesContainer" style="display: none;">
                            <label class="form-label small fw-semibold text-dark mb-1">
                                <i class="bi bi-diagram-3 text-teal me-1" style="color: #0f766e;"></i> Digunakan pada Submenu:
                            </label>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row g-2">
                                    @foreach($unitSubmoduleOptions as $subKey => $subTitle)
                                        <div class="col-sm-6 col-md-4">
                                            <div class="form-check p-2 bg-white rounded border d-flex align-items-center gap-2">
                                                <input class="form-check-input ms-1 my-0 tambah-submodule-cb" type="checkbox" name="submodules[]" value="{{ $subKey }}" id="tambahSub_{{ $subKey }}">
                                                <label class="form-check-label small fw-medium text-dark cursor-pointer flex-fill mb-0" for="tambahSub_{{ $subKey }}" style="cursor: pointer;">
                                                    {{ $subTitle }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text text-muted small mt-2 mb-0" style="font-size: 11px;">
                                    <i class="bi bi-info-circle me-1"></i> Pilih satu atau beberapa submenu yang dapat memilih satuan ini saat pencatatan baru.
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">Deskripsi / Keterangan</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Keterangan opsional mengenai master data ini"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-teal-primary btn-sm d-inline-flex align-items-center gap-1">
                        <i class="bi bi-save"></i> Simpan Master Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDIT MASTER DATA -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditMaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="" method="POST" id="formEditMaster">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light border-bottom py-2 px-3">
                    <h5 class="modal-title fw-bold text-dark fs-6 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-warning"></i> Edit Master Data Environment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark" id="editMasterNameLabel">Nama Master <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editMasterName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Kode / Simbol <span class="text-muted fw-normal">(Opsional)</span></label>
                            <input type="text" name="code" id="editMasterCode" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Urutan Tampil</label>
                            <input type="number" name="order_index" id="editMasterOrder" class="form-control form-control-sm" min="0">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch pb-1">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editMasterActive">
                                <label class="form-check-label small fw-semibold text-dark" for="editMasterActive">Status Aktif (Tersedia untuk input baru)</label>
                            </div>
                        </div>

                        <!-- SUBMODULE MAPPING KHUSUS SATUAN -->
                        <div class="col-12" id="editSubmodulesContainer" style="display: none;">
                            <label class="form-label small fw-semibold text-dark mb-1">
                                <i class="bi bi-diagram-3 text-teal me-1" style="color: #0f766e;"></i> Digunakan pada Submenu:
                            </label>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row g-2">
                                    @foreach($unitSubmoduleOptions as $subKey => $subTitle)
                                        <div class="col-sm-6 col-md-4">
                                            <div class="form-check p-2 bg-white rounded border d-flex align-items-center gap-2">
                                                <input class="form-check-input ms-1 my-0 edit-submodule-cb" type="checkbox" name="submodules[]" value="{{ $subKey }}" id="editSub_{{ $subKey }}">
                                                <label class="form-check-label small fw-medium text-dark cursor-pointer flex-fill mb-0" for="editSub_{{ $subKey }}" style="cursor: pointer;">
                                                    {{ $subTitle }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text text-muted small mt-2 mb-0" style="font-size: 11px;">
                                    <i class="bi bi-info-circle me-1"></i> Centang satu atau beberapa submenu yang dapat memilih satuan ini saat pencatatan baru.
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">Deskripsi</label>
                            <textarea name="description" id="editMasterDescription" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-teal-primary btn-sm d-inline-flex align-items-center gap-1">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FORM HAPUS GLOBAL -->
<form id="globalDeleteFormMaster" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endadminManagement
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const catSelect = document.getElementById('tambahMasterCategory');
        const tambahSubCont = document.getElementById('tambahSubmodulesContainer');
        const nameLabel = document.getElementById('tambahMasterNameLabel');
        const nameInput = document.getElementById('tambahMasterNameInput');

        function updateTambahForm() {
            if (catSelect && tambahSubCont) {
                const isUnit = catSelect.value === 'unit';
                tambahSubCont.style.display = isUnit ? 'block' : 'none';
                if (nameLabel) {
                    nameLabel.innerHTML = isUnit ? 'Nama Satuan <span class="text-danger">*</span>' : 'Nama Master <span class="text-danger">*</span>';
                }
                if (nameInput) {
                    nameInput.placeholder = isUnit ? 'Contoh: Kilogram (kg), Ton, Liter' : 'Contoh: Area Power Plant, Kebisingan';
                }
            }
        }

        if (catSelect) {
            catSelect.addEventListener('change', updateTambahForm);
            updateTambahForm();
        }
    });

    function openEditMasterModal(data) {
        const form = document.getElementById('formEditMaster');
        form.action = `/environment/master/${data.id}`;

        const isUnit = data.category === 'unit';
        const nameLabel = document.getElementById('editMasterNameLabel');
        if (nameLabel) {
            nameLabel.innerHTML = isUnit ? 'Nama Satuan <span class="text-danger">*</span>' : 'Nama Master <span class="text-danger">*</span>';
        }

        document.getElementById('editMasterName').value = data.name || '';
        document.getElementById('editMasterCode').value = data.code || '';
        document.getElementById('editMasterOrder').value = data.order_index || 0;
        document.getElementById('editMasterDescription').value = data.description || '';
        document.getElementById('editMasterActive').checked = Boolean(data.is_active);

        const editSubCont = document.getElementById('editSubmodulesContainer');
        if (isUnit) {
            editSubCont.style.display = 'block';
            const assigned = Array.isArray(data.submodules) ? data.submodules : [];
            document.querySelectorAll('.edit-submodule-cb').forEach(cb => {
                cb.checked = assigned.includes(cb.value);
            });
        } else {
            editSubCont.style.display = 'none';
            document.querySelectorAll('.edit-submodule-cb').forEach(cb => {
                cb.checked = false;
            });
        }

        new bootstrap.Modal(document.getElementById('modalEditMaster')).show();
    }

    function confirmDeleteMaster(id, name) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Master Data?',
                text: `Hapus master data "${name}"? Jika master data ini pernah digunakan oleh data historis, penghapusan akan dicegah demi menjaga integritas laporan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Master',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('globalDeleteFormMaster');
                    form.action = `/environment/master/${id}`;
                    form.submit();
                }
            });
        } else {
            if (confirm(`Yakin ingin menghapus master data "${name}"?`)) {
                const form = document.getElementById('globalDeleteFormMaster');
                form.action = `/environment/master/${id}`;
                form.submit();
            }
        }
    }
</script>
@endpush
