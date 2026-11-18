<?php

namespace App\Http\Requests;

use App\Models\IssueFollowUp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Dukung alias input bahasa Indonesia (misal dari modal tindak lanjut insiden)
        if (!$this->filled('title') && $this->filled('judul_temuan')) {
            $this->merge(['title' => $this->input('judul_temuan')]);
        }
        if (!$this->filled('hsse_division') && $this->filled('fungsi_safeguard')) {
            $this->merge(['hsse_division' => $this->input('fungsi_safeguard')]);
        }
        if (!$this->filled('reported_date') && $this->filled('tanggal_temuan')) {
            $this->merge(['reported_date' => $this->input('tanggal_temuan')]);
        }
        if (!$this->filled('location') && $this->filled('lokasi')) {
            $this->merge(['location' => $this->input('lokasi')]);
        }
        if (!$this->filled('category') && $this->filled('sumber_temuan')) {
            $this->merge(['category' => $this->input('sumber_temuan')]);
        }
        if (!$this->filled('closed_date') && $this->filled('tanggal_closed')) {
            $this->merge(['closed_date' => $this->input('tanggal_closed')]);
        }
        if (!$this->filled('description') && $this->filled('deskripsi_temuan')) {
            $this->merge(['description' => $this->input('deskripsi_temuan')]);
        }

        if ($this->has('hsse_division') && $this->input('hsse_division') !== null) {
            $normalized = IssueFollowUp::normalizeDivisionCode((string) $this->input('hsse_division'));
            $this->merge(['hsse_division' => $normalized]);
        }

        // Default untuk field lama yang dihapus dari form sederhana
        if (!$this->filled('unit')) {
            $this->merge(['unit' => 'PMU']);
        }

        if (!$this->filled('pic')) {
            $this->merge(['pic' => $this->user()?->name ?? 'Tim HSSE']);
        }

        if (!$this->filled('priority')) {
            $this->merge(['priority' => 'Sedang']);
        }

        $rawStatus = trim((string) $this->input('status', 'Open'));
        if (strcasecmp($rawStatus, 'Close') === 0 || strcasecmp($rawStatus, 'Closed') === 0) {
            $this->merge(['status' => 'Closed']);
        } elseif (strcasecmp($rawStatus, 'In Progress') === 0) {
            $this->merge(['status' => 'In Progress']);
        } else {
            $this->merge(['status' => 'Open', 'closed_date' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'hsse_division' => ['required', 'string', Rule::in(array_keys(IssueFollowUp::HSSE_DIVISIONS))],
            'reported_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'action_plan' => ['nullable', 'string'],
            'pic' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date', 'after_or_equal:reported_date'],
            'closed_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', Rule::in(IssueFollowUp::PRIORITIES)],
            'status' => ['nullable', 'string', Rule::in(['Open', 'In Progress', 'Closed', 'Close'])],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:5120'],
            'documentations' => ['nullable', 'array'],
            'documentations.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'hsse_division.required' => 'Fungsi Safeguard (Health & Safety, Security, Environment, atau Social) wajib dipilih.',
            'hsse_division.in' => 'Fungsi Safeguard tidak valid. Pilih salah satu dari Health & Safety, Security, Environment, atau Social.',
            'reported_date.required' => 'Tanggal temuan wajib diisi.',
            'reported_date.date' => 'Format tanggal temuan tidak valid.',
            'title.required' => 'Judul temuan wajib diisi.',
            'title.max' => 'Judul temuan maksimal 255 karakter.',
            'category.required' => 'Sumber temuan wajib dipilih.',
            'description.required' => 'Deskripsi temuan wajib diisi.',
            'due_date.date' => 'Format tanggal tenggat tidak valid.',
            'due_date.after_or_equal' => 'Tanggal tenggat tidak boleh lebih awal dari tanggal temuan.',
            'closed_date.date' => 'Format Tanggal Closed tidak valid.',
            'priority.in' => 'Tingkat risiko / prioritas tidak valid.',
            'status.in' => 'Status temuan tidak valid.',
            'attachment.mimes' => 'Lampiran harus berupa gambar (JPG, PNG, WEBP) atau dokumen (PDF, DOC, DOCX).',
            'attachment.max' => 'Ukuran file lampiran maksimal 5 MB.',
            'documentations.*.mimes' => 'Dokumentasi harus berupa gambar (JPG, PNG, WEBP) atau dokumen (PDF, DOC, DOCX, XLS, XLSX).',
            'documentations.*.max' => 'Ukuran setiap file dokumentasi maksimal 15 MB.',
        ];
    }
}

