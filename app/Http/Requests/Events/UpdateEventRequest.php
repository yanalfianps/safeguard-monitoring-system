<?php

namespace App\Http\Requests\Events;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdminManagement();
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        // 1. Parsing Tanggal Kegiatan (Date Range Picker interaktif "d/m/Y - d/m/Y" atau "Y-m-d - Y-m-d")
        $rawDate = trim((string) $this->input('event_date'));
        $startDate = null;
        $endDate = null;

        if (!empty($rawDate)) {
            $parts = preg_split('/\s+(?:-|to|s\/d)\s+/i', $rawDate);
            $parseDate = function ($str) {
                $str = trim((string) $str);
                if ($str === '') return null;
                foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'] as $fmt) {
                    try {
                        $d = Carbon::createFromFormat($fmt, $str);
                        if ($d && $d->year > 1900) return $d->format('Y-m-d');
                    } catch (\Throwable) {}
                }
                try {
                    $d = Carbon::parse($str);
                    return $d ? $d->format('Y-m-d') : null;
                } catch (\Throwable) {
                    return null;
                }
            };

            if (is_array($parts) && count($parts) === 2) {
                $startDate = $parseDate($parts[0]);
                $endDate = $parseDate($parts[1]);
            } elseif (is_array($parts) && count($parts) === 1) {
                $single = $parseDate($parts[0]);
                if ($single) {
                    $startDate = $single;
                    $endDate = $single;
                }
            }
        }

        if ($startDate && $endDate) {
            $merges['start_date'] = $startDate;
            $merges['end_date'] = $endDate;
            $merges['event_date'] = $startDate;
        }

        // 2. Multiple Select Fungsi Safeguard
        if ($this->has('safeguard_functions') && is_array($this->input('safeguard_functions'))) {
            $filteredSafeguards = array_values(array_filter($this->input('safeguard_functions'), fn($f) => !empty(trim((string)$f))));
            $merges['safeguard_functions'] = $filteredSafeguards;
            $merges['safeguard_function'] = !empty($filteredSafeguards) ? implode(', ', $filteredSafeguards) : 'Health & Safety';
        } elseif ($this->filled('safeguard_function')) {
            $funcs = array_values(array_filter(array_map('trim', explode(',', (string) $this->input('safeguard_function')))));
            $merges['safeguard_functions'] = !empty($funcs) ? $funcs : ['Health & Safety'];
            $merges['safeguard_function'] = implode(', ', $merges['safeguard_functions']);
        } else {
            $currentEvent = $this->route('event') instanceof Event
                ? $this->route('event')
                : Event::find($this->route('event') ?: $this->route('id'));

            $existing = $currentEvent ? ($currentEvent->safeguard_functions_list ?: [$currentEvent->safeguard_function ?: 'Health & Safety']) : ['Health & Safety'];
            $merges['safeguard_functions'] = $existing;
            $merges['safeguard_function'] = implode(', ', $existing);
        }

        // 3. Single Select Perusahaan / Instansi
        if (!$this->filled('company')) {
            $merges['company'] = $this->input('unit', 'PT Geo Dipa Energi (Persero)');
        }

        if (!$this->filled('status')) {
            $merges['status'] = Event::determineAutoStatus($startDate ?: $this->input('event_date'), $endDate);
        }

        if (!$this->filled('unit')) {
            $merges['unit'] = $merges['company'] ?? 'PMU';
        }

        if (!empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'safeguard_functions' => ['required', 'array', 'min:1'],
            'safeguard_functions.*' => ['required', 'string', Rule::in(Event::SAFEGUARD_FUNCTIONS)],
            'safeguard_function' => ['nullable', 'string', 'max:150'],
            'company' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:100'],
            'event_date' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
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
            'safeguard_functions.required' => 'Minimal satu Fungsi Safeguard wajib dipilih.',
            'safeguard_functions.min' => 'Minimal satu Fungsi Safeguard wajib dipilih.',
            'safeguard_functions.*.in' => 'Pilihan Fungsi Safeguard tidak valid.',
            'company.required' => 'Perusahaan / instansi wajib dipilih.',
            'event_date.required' => 'Tanggal kegiatan wajib diisi.',
            'start_date.required' => 'Tanggal awal kegiatan wajib diisi.',
            'start_date.date' => 'Format tanggal awal kegiatan tidak valid.',
            'end_date.required' => 'Tanggal akhir kegiatan wajib diisi.',
            'end_date.date' => 'Format tanggal akhir kegiatan tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal daripada tanggal awal kegiatan.',
            'status.required' => 'Status kegiatan wajib dipilih.',
            'documentation_files.*.max' => 'Ukuran file dokumentasi maksimal 15 MB per file.',
            'documentation_files.*.mimes' => 'Format file yang didukung: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX.',
        ];
    }
}
