<?php

namespace App\Http\Requests;

use App\Models\IssueFollowUp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $routeParam = $this->route('issue_follow_up');
        $existing = $routeParam instanceof IssueFollowUp
            ? $routeParam
            : ($routeParam ? IssueFollowUp::find($routeParam) : null);

        if ($existing) {
            $this->merge([
                'hsse_division' => $this->input('hsse_division', $existing->hsse_division),
                'reported_date' => $this->input('reported_date', $existing->reported_date ? $existing->reported_date->format('Y-m-d') : null),
                'title' => $this->input('title', $existing->title),
                'category' => $this->input('category', $existing->category),
                'unit' => $this->input('unit', $existing->unit ?: 'PMU'),
                'description' => $this->input('description', $existing->description),
                'pic' => $this->input('pic', $existing->pic ?: 'Tim HSSE'),
                'priority' => $this->input('priority', $existing->priority ?: 'Sedang'),
            ]);
        }

        if ($this->has('hsse_division') && $this->input('hsse_division') !== null) {
            $normalized = IssueFollowUp::normalizeDivisionCode((string) $this->input('hsse_division'));
            $this->merge(['hsse_division' => $normalized]);
        }

        if ($this->has('status') && $this->input('status') !== null) {
            $rawStatus = trim((string) $this->input('status'));
            if (strcasecmp($rawStatus, 'Close') === 0 || strcasecmp($rawStatus, 'Closed') === 0) {
                $this->merge(['status' => 'Closed']);
            } elseif (strcasecmp($rawStatus, 'In Progress') === 0) {
                $this->merge(['status' => 'In Progress']);
            } else {
                $this->merge(['status' => 'Open', 'closed_date' => null]);
            }
        }

        if ($this->has('deleted_documentations') && is_string($this->input('deleted_documentations'))) {
            $decoded = json_decode($this->input('deleted_documentations'), true);
            $this->merge(['deleted_documentations' => is_array($decoded) ? $decoded : array_filter(explode(',', $this->input('deleted_documentations')))]);
        }
        if ($this->has('delete_documentations') && !$this->has('deleted_documentations')) {
            $del = $this->input('delete_documentations');
            if (is_string($del)) {
                $decoded = json_decode($del, true);
                $del = is_array($decoded) ? $decoded : array_filter(explode(',', $del));
            }
            $this->merge(['deleted_documentations' => (array) $del]);
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
            'deleted_documentations' => ['nullable', 'array'],
            'deleted_documentations.*' => ['integer'],
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

