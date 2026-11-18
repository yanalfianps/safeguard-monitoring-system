<script>
    function escapeEnvHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        const val = parseFloat((bytes / Math.pow(k, i)).toFixed(1));
        return val.toString().replace('.', ',') + ' ' + sizes[i];
    }

    function getFileIconClass(fileName) {
        const ext = (fileName || '').split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'bi-file-earmark-image text-success';
        if (ext === 'pdf') return 'bi-file-earmark-pdf text-danger';
        if (['doc', 'docx'].includes(ext)) return 'bi-file-earmark-word text-primary';
        return 'bi-file-earmark-text text-secondary';
    }

    function triggerEnvUpload(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const inputs = container.querySelectorAll('input[type="file"]');
        for (let i = 0; i < inputs.length; i++) {
            if (!inputs[i].files || inputs[i].files.length === 0) {
                inputs[i].click();
                return;
            }
        }
        if (inputs.length > 0) {
            const firstInput = inputs[0];
            const inputName = firstInput ? firstInput.name : 'attachments[]';
            const accept = firstInput ? firstInput.accept : '.pdf,.jpg,.jpeg,.png';
            const onchangeAttr = firstInput ? firstInput.getAttribute('onchange') : '';
            const row = document.createElement('div');
            row.className = 'attachment-input-row';
            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.name = inputName;
            newInput.className = 'form-control form-control-sm';
            if (accept) newInput.accept = accept;
            newInput.multiple = true;
            if (onchangeAttr) {
                newInput.setAttribute('onchange', onchangeAttr);
            }
            row.appendChild(newInput);
            container.appendChild(row);
            newInput.click();
        }
    }

    function handleEnvDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.add('dragover');
    }

    function handleEnvDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('dragover');
    }

    function handleEnvDrop(e, containerId, listContainerId, maxSizeMb) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('dragover');

        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            const container = document.getElementById(containerId);
            if (!container) return;

            let targetInput = null;
            const inputs = container.querySelectorAll('input[type="file"]');
            for (let i = 0; i < inputs.length; i++) {
                if (!inputs[i].files || inputs[i].files.length === 0) {
                    targetInput = inputs[i];
                    break;
                }
            }
            if (!targetInput) {
                const firstInput = inputs[0];
                const inputName = firstInput ? firstInput.name : 'attachments[]';
                const accept = firstInput ? firstInput.accept : '.pdf,.jpg,.jpeg,.png';
                const row = document.createElement('div');
                row.className = 'attachment-input-row';
                row.innerHTML = `<input type="file" name="${inputName}" class="form-control form-control-sm" accept="${accept}" multiple onchange="refreshEnvFileList('${containerId}', '${listContainerId}', ${maxSizeMb})">`;
                container.appendChild(row);
                targetInput = row.querySelector('input[type="file"]');
            }

            try {
                targetInput.files = e.dataTransfer.files;
                refreshEnvFileList(containerId, listContainerId, maxSizeMb);
            } catch (err) {
                console.warn('Drop error:', err);
            }
        }
    }

    function addEnvAttachmentInput(containerId, inputName, accept, maxSizeMb, listContainerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const allInputs = container.querySelectorAll('input[type="file"]');
        const lastInput = allInputs[allInputs.length - 1];
        if (lastInput && (!lastInput.files || lastInput.files.length === 0)) {
            lastInput.click();
            return;
        }

        const row = document.createElement('div');
        row.className = 'attachment-input-row';
        row.innerHTML = `<input type="file" name="${inputName}" class="form-control form-control-sm" accept="${accept}" multiple onchange="refreshEnvFileList('${containerId}', '${listContainerId}', ${maxSizeMb})">`;
        container.appendChild(row);

        const input = row.querySelector('input[type="file"]');
        if (input) {
            input.click();
        }
    }

    function refreshEnvFileList(containerId, listContainerId, maxSizeMb) {
        const container = document.getElementById(containerId);
        const listContainer = document.getElementById(listContainerId);
        if (!container || !listContainer) return;

        listContainer.innerHTML = '';
        const inputs = container.querySelectorAll('input[type="file"]');
        let allFiles = [];
        let hasOverSize = false;
        let overSizeFileName = '';
        let hasInvalidExt = false;
        let invalidExtFileName = '';

        inputs.forEach((input, inputIdx) => {
            if (input.files && input.files.length > 0) {
                const acceptAttr = (input.accept || '').toLowerCase();
                const allowedExts = acceptAttr ? acceptAttr.split(',').map(s => s.trim().replace(/^\./, '')) : [];

                for (let fIdx = 0; fIdx < input.files.length; fIdx++) {
                    const file = input.files[fIdx];
                    const ext = (file.name || '').split('.').pop().toLowerCase();

                    if (allowedExts.length > 0 && !allowedExts.includes(ext)) {
                        hasInvalidExt = true;
                        invalidExtFileName = file.name;
                    }

                    if (maxSizeMb && file.size > maxSizeMb * 1024 * 1024) {
                        hasOverSize = true;
                        overSizeFileName = file.name;
                    }

                    allFiles.push({
                        file: file,
                        inputIdx: inputIdx,
                        fIdx: fIdx
                    });
                }
            }
        });

        if (hasInvalidExt) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Format Dokumen Tidak Didukung',
                    text: `File "${invalidExtFileName}" tidak sesuai format yang diizinkan (.pdf, .jpg, .jpeg, .png).`
                });
            } else {
                alert(`File "${invalidExtFileName}" tidak sesuai format yang diizinkan.`);
            }
        } else if (hasOverSize) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ukuran File Terlalu Besar',
                    text: `File "${overSizeFileName}" melebihi batas maksimal ${maxSizeMb} MB per file.`
                });
            } else {
                alert(`File "${overSizeFileName}" melebihi batas maksimal ${maxSizeMb} MB per file.`);
            }
        }

        if (allFiles.length === 0) return;

        let totalBytes = allFiles.reduce((acc, curr) => acc + curr.file.size, 0);

        let listHtml = `
            <div class="mt-1 pt-1">
                <div class="small fw-semibold text-secondary mb-1 d-flex align-items-center justify-content-between" style="font-size: 11.5px;">
                    <span>Lampiran dipilih (${allFiles.length} file)</span>
                    <span class="text-muted">${formatFileSize(totalBytes)}</span>
                </div>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-1 attachment-scroll-list">
        `;

        allFiles.forEach((item) => {
            const icon = getFileIconClass(item.file.name);
            const safeName = escapeEnvHtml(item.file.name);
            listHtml += `
                <li class="d-flex align-items-center justify-content-between py-1 px-2 rounded bg-white border" style="font-size: 12px; border-color: #e2e8f0 !important;">
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <i class="bi ${icon}"></i>
                        <span class="text-truncate fw-medium text-dark" title="${safeName}" style="max-width: 250px;">${safeName}</span>
                        <span class="text-muted text-nowrap" style="font-size: 11px;">(${formatFileSize(item.file.size)})</span>
                    </div>
                    <button type="button" class="btn btn-link text-muted p-0 ms-2 lh-1 hover-danger"
                            title="Hapus file ini"
                            onclick="removeSingleEnvFile('${containerId}', '${listContainerId}', ${item.inputIdx}, ${item.fIdx}, ${maxSizeMb})">
                        <i class="bi bi-trash3 fs-6"></i>
                    </button>
                </li>
            `;
        });

        listHtml += `
                </ul>
            </div>
        `;
        listContainer.innerHTML = listHtml;
    }

    function removeSingleEnvFile(containerId, listContainerId, inputIdx, fileIdx, maxSizeMb) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const inputs = container.querySelectorAll('input[type="file"]');
        const targetInput = inputs[inputIdx];
        if (!targetInput) return;

        try {
            const dt = new DataTransfer();
            for (let i = 0; i < targetInput.files.length; i++) {
                if (i !== fileIdx) {
                    dt.items.add(targetInput.files[i]);
                }
            }
            targetInput.files = dt.files;
        } catch (e) {
            console.warn('DataTransfer error:', e);
            targetInput.value = '';
        }

        if (targetInput.files.length === 0 && inputs.length > 1) {
            const row = targetInput.closest('.attachment-input-row');
            if (row) row.remove();
        }

        refreshEnvFileList(containerId, listContainerId, maxSizeMb);
    }

    function resetEnvUploader(containerId, listContainerId) {
        const container = document.getElementById(containerId);
        const listContainer = document.getElementById(listContainerId);
        if (!container) return;

        const rows = container.querySelectorAll('.attachment-input-row');
        rows.forEach((row, idx) => {
            if (idx === 0) {
                const input = row.querySelector('input[type="file"]');
                if (input) input.value = '';
            } else {
                row.remove();
            }
        });
        if (listContainer) listContainer.innerHTML = '';
    }

    function renderExistingEnvFiles(containerId, filesList, deleteInputName, formId, envModule = null, recordId = null) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';

        const form = formId ? document.getElementById(formId) : container.closest('form');
        if (form) {
            form.querySelectorAll(`input[name="${deleteInputName}"]`).forEach(el => el.remove());
        }

        if (!filesList || !Array.isArray(filesList) || filesList.length === 0) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'mb-2';
        let html = `
            <div class="small fw-semibold text-secondary mb-1 d-flex align-items-center justify-content-between" style="font-size: 11.5px;">
                <span>Dokumen Tersimpan (${filesList.length} file)</span>
            </div>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-1 attachment-scroll-list">
        `;

        filesList.forEach((filePath, idx) => {
            const fileName = filePath.split('/').pop();
            const safeName = escapeEnvHtml(fileName);
            const safePath = escapeEnvHtml(filePath);
            const icon = getFileIconClass(fileName);
            const rowId = `existing-file-${containerId}-${idx}`;
            const fileHref = (envModule && recordId)
                ? `/environment/files/${encodeURIComponent(envModule)}/${encodeURIComponent(recordId)}/${idx}`
                : `/storage/${encodeURI(filePath)}`;
            html += `
                <li class="d-flex align-items-center justify-content-between py-1 px-2 rounded bg-white border" id="${rowId}" style="font-size: 12px; border-color: #e2e8f0 !important;">
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <i class="bi ${icon}"></i>
                        <a href="${fileHref}" target="_blank" class="text-decoration-none text-primary fw-medium text-truncate file-link" title="Klik untuk melihat/mengunduh dokumen" style="max-width: 250px;">
                            ${safeName}
                        </a>
                        <span class="badge bg-danger d-none badge-deleted ms-1" style="font-size: 10px;">Akan Dihapus</span>
                    </div>
                    <button type="button" class="btn btn-link text-muted p-0 ms-2 lh-1 hover-danger btn-toggle-delete"
                            data-row-id="${rowId}" data-path="${safePath}" data-input-name="${escapeEnvHtml(deleteInputName)}" data-form-id="${escapeEnvHtml(form ? form.id : '')}"
                            title="Hapus file ini"
                            onclick="toggleDeleteExistingEnvFile(this)">
                        <i class="bi bi-trash3 fs-6"></i>
                    </button>
                </li>
            `;
        });

        html += `
            </ul>
            <div class="text-muted mt-1 fst-italic" style="font-size: 11px;">Klik ikon tempat sampah pada dokumen yang ingin dihapus saat disimpan.</div>
        `;
        wrapper.innerHTML = html;
        container.appendChild(wrapper);
    }

    function toggleDeleteExistingEnvFile(btn, rowId, filePath, inputName, formId) {
        rowId = rowId || btn.dataset.rowId;
        filePath = filePath || btn.dataset.path;
        inputName = inputName || btn.dataset.inputName;
        formId = formId || btn.dataset.formId;
        const row = document.getElementById(rowId);
        if (!row) return;

        const fileLink = row.querySelector('.file-link');
        const badge = row.querySelector('.badge-deleted');
        const form = formId ? document.getElementById(formId) : row.closest('form');

        const isMarked = row.classList.contains('marked-for-deletion');
        if (!isMarked) {
            row.classList.add('marked-for-deletion');
            if (fileLink) fileLink.classList.add('text-decoration-line-through', 'text-muted');
            if (badge) badge.classList.remove('d-none');
            btn.classList.remove('text-muted');
            btn.classList.add('text-primary');
            btn.setAttribute('title', 'Batalkan penghapusan file');
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise fs-6"></i>';

            if (form) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = inputName;
                hidden.value = filePath;
                hidden.className = `delete-input-${rowId}`;
                form.appendChild(hidden);
            }
        } else {
            row.classList.remove('marked-for-deletion');
            if (fileLink) fileLink.classList.remove('text-decoration-line-through', 'text-muted');
            if (badge) badge.classList.add('d-none');
            btn.classList.remove('text-primary');
            btn.classList.add('text-muted');
            btn.setAttribute('title', 'Hapus file ini');
            btn.innerHTML = '<i class="bi bi-trash3 fs-6"></i>';

            if (form) {
                form.querySelectorAll(`.delete-input-${rowId}`).forEach(el => el.remove());
            }
        }
    }

    // Safeguard on form submit: disable empty file inputs so browser doesn't send empty files
    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (form && form.querySelectorAll) {
                const emptyInputs = [];
                form.querySelectorAll('.attachment-input-wrapper input[type="file"]').forEach(input => {
                    if (!input.files || input.files.length === 0) {
                        input.disabled = true;
                        emptyInputs.push(input);
                    }
                });
                setTimeout(() => {
                    emptyInputs.forEach(input => { input.disabled = false; });
                }, 1000);
            }
        });
    });
</script>

