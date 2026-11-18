@extends('layouts.app', [
    'title' => 'Edit Temuan - Issue & Follow Up',
    'activeModule' => 'issue-follow-up'
])

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-0">Edit Temuan ({{ $issue->issue_code }})</h4>
            <div class="text-muted small"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a> &rsaquo; <a href="{{ route('issue-follow-up.index') }}" class="text-decoration-none text-muted">Issue &amp; Follow Up</a> &rsaquo; <span class="text-dark fw-semibold">Edit</span></div>
        </div>
        <a href="{{ route('issue-follow-up.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <form action="{{ route('issue-follow-up.update', $issue->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @php
                    $isClosed = in_array(strtolower((string) old('status', $issue->status)), ['closed', 'close'], true);
                @endphp
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Judul Temuan <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title', $issue->title) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Fungsi Safeguard <span class="text-danger">*</span></label>
                        <select name="hsse_division" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Fungsi Safeguard --</option>
                            @foreach(\App\Models\IssueFollowUp::HSSE_DIVISIONS as $divCode => $divLabel)
                                <option value="{{ $divCode }}" @selected(\App\Models\IssueFollowUp::normalizeDivisionCode(old('hsse_division', $issue->hsse_division)) === $divCode)>{{ $divLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tanggal Temuan <span class="text-danger">*</span></label>
                        <input type="date" name="reported_date" class="form-control form-control-sm" value="{{ old('reported_date', $issue->reported_date ? $issue->reported_date->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Lokasi</label>
                        <input type="text" name="location" class="form-control form-control-sm" value="{{ old('location', $issue->location) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Sumber Temuan <span class="text-danger">*</span></label>
                        <select name="category" class="form-select form-select-sm" required>
                            @foreach($categories as $c)
                                <option value="{{ $c }}" @selected(old('category', $issue->category) === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Status Temuan <span class="text-danger">*</span></label>
                        <select name="status" id="editStatusSelect" class="form-select form-select-sm" onchange="toggleEditClosedDate()" required>
                            <option value="Open" @selected(!$isClosed)>Open</option>
                            <option value="Closed" @selected($isClosed)>Closed</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tanggal Closed</label>
                        <input type="date" name="closed_date" id="editClosedDateInput" class="form-control form-control-sm" value="{{ old('closed_date', $issue->closed_date ? $issue->closed_date->format('Y-m-d') : '') }}" {{ $isClosed ? 'required' : 'disabled' }}>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Deskripsi Temuan <span class="text-danger">*</span></label>
                        <textarea name="description" rows="3" class="form-control form-control-sm" required>{{ old('description', $issue->description) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Dokumentasi / Lampiran (Multi-File)</label>

                        <!-- Kontainer Dokumen yang Ditandai untuk Dihapus -->
                        <div id="editDeletedDocsContainer"></div>

                        <!-- Daftar Dokumen Tersimpan (Vertikal) -->
                        @if($issue->documentations && $issue->documentations->count() > 0)
                        <div class="mb-3" id="existingDocsSection">
                            <label class="form-label fw-semibold small text-muted d-block mb-2">
                                <i class="bi bi-folder2-open me-1 text-primary"></i> Dokumen Tersimpan Saat Ini (<span id="existingDocsCount">{{ $issue->documentations->count() }}</span>)
                            </label>
                            <div class="d-flex flex-column gap-2" id="existingDocsList">
                                @foreach($issue->documentations as $doc)
                                @php
                                    $ext = strtolower(pathinfo($doc->original_name ?? $doc->file_name, PATHINFO_EXTENSION));
                                    $iconClass = 'bi-file-earmark-text text-secondary';
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) {
                                        $iconClass = 'bi-file-earmark-image text-success';
                                    } elseif ($ext === 'pdf') {
                                        $iconClass = 'bi-file-earmark-pdf text-danger';
                                    } elseif (in_array($ext, ['doc', 'docx'])) {
                                        $iconClass = 'bi-file-earmark-word text-primary';
                                    } elseif (in_array($ext, ['xls', 'xlsx'])) {
                                        $iconClass = 'bi-file-earmark-excel text-success';
                                    }
                                @endphp
                                <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-2 border bg-white shadow-sm w-100 existing-doc-row" id="existing-doc-{{ $doc->id }}">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-3" style="min-width: 0;">
                                        <i class="bi {{ $iconClass }} fs-5 flex-shrink-0"></i>
                                        <div class="text-truncate">
                                            <span class="fw-semibold text-dark" style="font-size: 12.5px;">{{ $doc->original_name ?: $doc->file_name }}</span>
                                            <span class="text-muted ms-1" style="font-size: 11.5px;">({{ $doc->formatted_size }})</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                        <a href="{{ route('issue-follow-up.documentation.view', $doc->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 11.5px;" title="Lihat berkas">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size: 11.5px;" onclick="markDeleteExistingDoc({{ $doc->id }})" title="Hapus dokumen ini">
                                            <i class="bi bi-trash3"></i>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Area Unggah Dokumen Baru -->
                        <div class="upload-drag-area" id="editDropzone"
                             style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 16px; text-align: center; background: #f8fafc; cursor: pointer;"
                             onclick="document.getElementById('editDocsInput').click()"
                             ondragover="event.preventDefault(); this.style.borderColor='#059669'; this.style.background='#ecfdf5';"
                             ondragleave="event.preventDefault(); this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';"
                             ondrop="handleEditDrop(event)">
                            <i class="bi bi-cloud-arrow-up fs-3 text-secondary d-block mb-1"></i>
                            <span class="fw-semibold text-dark d-block" style="font-size:12px;">Pilih Berkas Dokumentasi Baru</span>
                            <span class="text-muted" style="font-size:11px;">Dapat memilih banyak berkas sekaligus (JPG, PNG, WEBP, PDF, DOCX, XLSX - maks 15 MB/file)</span>
                            <input type="file" name="documentations[]" id="editDocsInput" class="d-none" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" onchange="handleEditFiles(this.files)">
                        </div>
                        <div id="editPreviewFilesContainer" class="d-flex flex-column gap-2 mt-2"></div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('issue-follow-up.index') }}" class="btn btn-secondary btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-success btn-sm px-4">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    let editDt = new DataTransfer();

    function markDeleteExistingDoc(docId) {
        const row = document.getElementById('existing-doc-' + docId);
        if (row) {
            row.remove();
        }
        const container = document.getElementById('editDeletedDocsContainer');
        if (container) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'deleted_documentations[]';
            input.value = docId;
            container.appendChild(input);
        }
        const list = document.getElementById('existingDocsList');
        const remaining = list ? list.querySelectorAll('.existing-doc-row').length : 0;
        const countEl = document.getElementById('existingDocsCount');
        if (countEl) countEl.textContent = remaining;
        if (list && remaining === 0) {
            list.innerHTML = '<div class="text-muted small py-2 px-1 fst-italic">Seluruh dokumen lama telah ditandai untuk dihapus.</div>';
        }
    }

    function handleEditDrop(e) {
        e.preventDefault();
        const dz = document.getElementById('editDropzone');
        if (dz) { dz.style.borderColor = '#cbd5e1'; dz.style.background = '#f8fafc'; }
        if (e.dataTransfer && e.dataTransfer.files) handleEditFiles(e.dataTransfer.files);
    }

    function handleEditFiles(fileList) {
        if (!fileList) return;
        for (let i = 0; i < fileList.length; i++) {
            editDt.items.add(fileList[i]);
        }
        const inp = document.getElementById('editDocsInput');
        if (inp) inp.files = editDt.files;
        renderEditPreviews();
    }

    function removeEditFile(idx) {
        const next = new DataTransfer();
        for (let i = 0; i < editDt.files.length; i++) {
            if (i !== idx) next.items.add(editDt.files[i]);
        }
        editDt = next;
        const inp = document.getElementById('editDocsInput');
        if (inp) inp.files = editDt.files;
        renderEditPreviews();
    }

    function escapeEditHtml(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatEditFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
        return bytes + ' B';
    }

    function getEditFileIcon(filename) {
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

    function renderEditPreviews() {
        const c = document.getElementById('editPreviewFilesContainer');
        if (!c) return;
        if (editDt.files.length === 0) {
            c.innerHTML = '';
            return;
        }
        c.innerHTML = Array.from(editDt.files).map((f, i) => `
            <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-2 border bg-white shadow-sm w-100">
                <div class="d-flex align-items-center gap-2 overflow-hidden me-3" style="min-width: 0;">
                    <i class="bi ${getEditFileIcon(f.name)} fs-5 flex-shrink-0"></i>
                    <div class="text-truncate">
                        <span class="fw-semibold text-dark" style="font-size: 12.5px;">${escapeEditHtml(f.name)}</span>
                        <span class="text-muted ms-1" style="font-size: 11.5px;">(${formatEditFileSize(f.size)})</span>
                        <span class="badge bg-success-subtle text-success ms-1" style="font-size: 10px;">Baru</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2 flex-shrink-0 d-inline-flex align-items-center gap-1" style="font-size: 11.5px;" onclick="removeEditFile(${i})" title="Hapus berkas ini">
                    <i class="bi bi-trash3"></i>
                    <span>Hapus</span>
                </button>
            </div>
        `).join('');
    }

    function toggleEditClosedDate() {
        const st = document.getElementById('editStatusSelect');
        const dt = document.getElementById('editClosedDateInput');
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
