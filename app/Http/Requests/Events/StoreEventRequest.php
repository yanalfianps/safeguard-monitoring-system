<?php

namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdminManagement();
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        if (!$this->filled('safeguard_function')) {
            $merges['safeguard_function'] = 'Health & Safety';
        }

        if (!$this->filled('status')) {
            $merges['status'] = Event::determineAutoStatus($this->input('event_date'));
        }

        if (!$this->filled('category')) {
            $merges['category'] = $this->input('safeguard_function') ?: 'Kegiatan';
        }

        if (!$this->filled('company')) {
            $merges['company'] = $this->input('unit', 'PT Geo Dipa Energi (Persero)');
        }

        if (!$this->filled('unit')) {
            $merges['unit'] = $this->input('company', 'PMU');
        }

        if (!$this->filled('location')) {
            $merges['location'] = 'Area Operasi Dieng';
        }

        if (!empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'safeguard_function' => ['required', 'string', Rule::in(Event::SAFEGUARD_FUNCTIONS)],
            'company' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:100'],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'location' => ['nullable', 'string', 'max:255'],
            'participants' => ['nullable', 'integer', 'min:0'],
            'pic' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Direncanakan,Berlangsung,Selesai,Dibatalkan'],
            'description' => ['nullable', 'string'],
            'documentation_files.*' => ['nullable', 'file', 'max:15360', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul kegiatan wajib diisi.',
            'safeguard_function.required' => 'Fungsi Safeguard wajib dipilih.',
            'safeguard_function.in' => 'Pilihan Fungsi Safeguard tidak valid.',
            'company.required' => 'Nama perusahaan / instansi wajib dipilih atau diisi.',
            'event_date.required' => 'Tanggal kegiatan wajib diisi.',
            'event_date.date' => 'Format tanggal kegiatan tidak valid.',
            'status.required' => 'Status kegiatan wajib dipilih.',
            'documentation_files.*.max' => 'Ukuran file dokumentasi maksimal 15 MB per file.',
            'documentation_files.*.mimes' => 'Format file yang didukung: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX.',
        ];
    }
}
