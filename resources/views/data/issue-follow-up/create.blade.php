@extends('layouts.app', [
    'title' => 'Tambah Temuan - Issue & Follow Up',
    'activeModule' => 'issue-follow-up'
])

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-0">Tambah Temuan Baru</h4>
            <div class="text-muted small"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a> &rsaquo; <a href="{{ route('issue-follow-up.index') }}" class="text-decoration-none text-muted">Issue &amp; Follow Up</a> &rsaquo; <span class="text-dark fw-semibold">Tambah Temuan</span></div>
        </div>
        <a href="{{ route('issue-follow-up.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <form action="{{ route('issue-follow-up.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Judul Temuan <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Fungsi Safeguard <span class="text-danger">*</span></label>
                        <select name="hsse_division" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Fungsi Safeguard --</option>
                            @foreach(\App\Models\IssueFollowUp::HSSE_DIVISIONS as $divCode => $divLabel)
                                <option value="{{ $divCode }}" @selected(old('hsse_division') === $divCode)>{{ $divLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tanggal Temuan <span class="text-danger">*</span></label>
                        <input type="date" name="reported_date" class="form-control form-control-sm" value="{{ old('reported_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Lokasi</label>
                        <input type="text" name="location" class="form-control form-control-sm" value="{{ old('location') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Sumber Temuan <span class="text-danger">*</span></label>
                        <select name="category" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Sumber Temuan --</option>
                            @foreach($sources as $src)
                                <option value="{{ $src }}" @selected(old('category') === $src)>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Status Temuan <span class="text-danger">*</span></label>
                        <select name="status" id="createStatusSelect" class="form-select form-select-sm" onchange="toggleCreateClosedDate()" required>
                            <option value="Open" @selected(old('status', 'Open') === 'Open')>Open</option>
                            <option value="Closed" @selected(old('status') === 'Closed')>Closed</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tanggal Closed</label>
                        <input type="date" name="closed_date" id="createClosedDateInput" class="form-control form-control-sm" value="{{ old('closed_date') }}" {{ old('status') === 'Closed' ? 'required' : 'disabled' }}>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Deskripsi Temuan <span class="text-danger">*</span></label>
                        <textarea name="description" rows="3" class="form-control form-control-sm" required>{{ old('description') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Unggah Dokumentasi / Lampiran (Multi-File)</label>
                        <div class="upload-drag-area" id="createDropzone"
                             style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 16px; text-align: center; background: #f8fafc; cursor: pointer;"
                             onclick="document.getElementById('createDocsInput').click()"
                             ondragover="event.preventDefault(); this.style.borderColor='#059669'; this.style.background='#ecfdf5';"
                             ondragleave="event.preventDefault(); this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';"
                             ondrop="handleCreateDrop(event)">
                            <i class="bi bi-cloud-arrow-up fs-3 text-secondary d-block mb-1"></i>
                            <span class="fw-semibold text-dark d-block" style="font-size:12px;">Pilih Berkas Dokumentasi</span>
                            <span class="text-muted" style="font-size:11px;">Dapat memilih banyak berkas sekaligus (JPG, PNG, WEBP, PDF, DOCX, XLSX - maks 15 MB/file)</span>
                            <input type="file" name="documentations[]" id="createDocsInput" class="d-none" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" onchange="handleCreateFiles(this.files)">
                        </div>
                        <div id="createPreviewFilesContainer" class="d-flex flex-column gap-2 mt-3"></div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('issue-follow-up.index') }}" class="btn btn-secondary btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-success btn-sm px-4">Simpan Temuan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    let createDt = new DataTransfer();
    function handleCreateDrop(e) {
        e.preventDefault();
        const dz = document.getElementById('createDropzone');
        if (dz) { dz.style.borderColor = '#cbd5e1'; dz.style.background = '#f8fafc'; }
        if (e.dataTransfer && e.dataTransfer.files) handleCreateFiles(e.dataTransfer.files);
    }
    function handleCreateFiles(fileList) {
        if (!fileList) return;
        for (let i = 0; i < fileList.length; i++) {
            createDt.items.add(fileList[i]);
        }
        const inp = document.getElementById('createDocsInput');
        if (inp) inp.files = createDt.files;
        renderCreatePreviews();
    }
    function removeCreateFile(idx) {
        const next = new DataTransfer();
        for (let i = 0; i < createDt.files.length; i++) {
            if (i !== idx) next.items.add(createDt.files[i]);
        }
        createDt = next;
        const inp = document.getElementById('createDocsInput');
        if (inp) inp.files = createDt.files;
        renderCreatePreviews();
    }
    function escapeCreateHtml(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function formatCreateFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
        return bytes + ' B';
    }
    function getCreateFileIcon(filename) {
        const ext = String(filename || '').split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(ext)) {
            return 'bi-file-earmark-image text-success';
        }
        if (ext === 'pdf') {
            return 'bi-file-earmark-pdf text-danger';
        }
        if (['doc', 'docx'].includes(ext)) {
            return 'bi-file-earmark-word text-primary';
        }
        if (['xls', 'xlsx'].includes(ext)) {
            return 'bi-file-earmark-excel text-success';
        }
        return 'bi-file-earmark-text text-secondary';
    }
    function renderCreatePreviews() {
        const c = document.getElementById('createPreviewFilesContainer');
        if (!c) return;
        if (createDt.files.length === 0) {
            c.innerHTML = '';
            return;
        }
        c.innerHTML = Array.from(createDt.files).map((f, i) => `
            <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-2 border bg-white shadow-sm w-100">
                <div class="d-flex align-items-center gap-2 overflow-hidden me-3" style="min-width: 0;">
                    <i class="bi ${getCreateFileIcon(f.name)} fs-5 flex-shrink-0"></i>
                    <div class="text-truncate">
                        <span class="fw-semibold text-dark" style="font-size: 12.5px;">${escapeCreateHtml(f.name)}</span>
                        <span class="text-muted ms-1" style="font-size: 11.5px;">(${formatCreateFileSize(f.size)})</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2 flex-shrink-0 d-inline-flex align-items-center gap-1" style="font-size: 11.5px;" onclick="removeCreateFile(${i})" title="Hapus berkas ini">
                    <i class="bi bi-trash3"></i>
                    <span>Hapus</span>
                </button>
            </div>
        `).join('');
    }
    function toggleCreateClosedDate() {
        const st = document.getElementById('createStatusSelect');
        const dt = document.getElementById('createClosedDateInput');
        if (!st || !dt) return;
        if (st.value === 'Closed') {
            dt.disabled = false;
            dt.required = true;
            if (!dt.value) dt.value = "{{ date('Y-m-d') }}";
        } else {
            dt.value = '';
            dt.disabled = true;
            dt.required = false;
        }
    }
</script>
@endsection
