@props([
    'containerId',
    'listContainerId',
    'inputName' => 'attachments[]',
    'accept' => '.pdf,.jpg,.jpeg,.png',
    'maxSizeMb' => 5,
    'hint' => 'Maksimal 5MB per file (PDF, JPG, PNG)'
])

<div class="custom-file-dropzone"
     onclick="triggerEnvUpload('{{ $containerId }}')"
     ondragover="handleEnvDragOver(event)"
     ondragleave="handleEnvDragLeave(event)"
     ondrop="handleEnvDrop(event, '{{ $containerId }}', '{{ $listContainerId }}', {{ $maxSizeMb }})">
    <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-1">
        <i class="bi bi-cloud-arrow-up text-secondary fs-5"></i>
        <span class="small fw-semibold text-dark">Pilih dokumen atau tarik file ke sini</span>
        <button type="button" class="btn btn-xs btn-light border py-1 px-2 fw-medium text-secondary shadow-none"
                onclick="event.stopPropagation(); triggerEnvUpload('{{ $containerId }}')">
            <i class="bi bi-folder2-open me-1"></i> Pilih Dokumen
        </button>
    </div>
    <div class="text-muted" style="font-size: 11px;">{{ $hint }}</div>
</div>

<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 mt-1 mb-1">
    <button type="button" class="btn btn-xs btn-light border text-secondary d-inline-flex align-items-center gap-1 shadow-none py-1 px-2"
            style="font-size: 11.5px;"
            onclick="addEnvAttachmentInput('{{ $containerId }}', '{{ $inputName }}', '{{ $accept }}', {{ $maxSizeMb }}, '{{ $listContainerId }}')">
        <i class="bi bi-plus text-primary fs-6 lh-1"></i> + Tambah Dokumen
    </button>
    <span class="text-muted fst-italic" style="font-size: 11px;">Bisa memilih dokumen dari folder berbeda</span>
</div>

<div id="{{ $containerId }}" class="attachment-input-wrapper d-none">
    <div class="attachment-input-row">
        <input type="file" name="{{ $inputName }}" class="form-control form-control-sm"
               accept="{{ $accept }}" multiple
               onchange="refreshEnvFileList('{{ $containerId }}', '{{ $listContainerId }}', {{ $maxSizeMb }})">
    </div>
</div>

<div id="{{ $listContainerId }}"></div>

