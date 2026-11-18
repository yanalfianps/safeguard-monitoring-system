<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EnvironmentMaster;
use App\Models\EnvironmentLb3Record;
use App\Models\EnvironmentNonLb3Record;
use App\Models\EnvironmentRklRplRecord;
use App\Models\EnvironmentPpichRecord;
use App\Models\EnvironmentInspectionRecord;
use App\Models\EnvironmentEnergyRecord;
use App\Models\EnvironmentEwsRecord;
use App\Models\EnvironmentWeatherRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnvironmentController extends Controller
{
    private function deleteStoredEnvFiles(array $paths): void
    {
        foreach ($paths as $fPath) {
            $clean = ltrim((string) $fPath, '/');
            if ($clean === '' || str_contains($clean, '..')) {
                continue;
            }
            foreach (['local', 'public'] as $diskName) {
                if (Storage::disk($diskName)->exists($clean)) {
                    Storage::disk($diskName)->delete($clean);
                }
            }
        }
    }

    /**
     * Sajikan dokumen lampiran Environment secara terautentikasi dari private storage (fallback legacy public).
     */
    public function viewDocument(string $module, int $id, int $index): BinaryFileResponse
    {
        $record = match ($module) {
            'lb3' => EnvironmentLb3Record::findOrFail($id),
            'non-lb3' => EnvironmentNonLb3Record::findOrFail($id),
            'rkl-rpl' => EnvironmentRklRplRecord::findOrFail($id),
            'inspeksi' => EnvironmentInspectionRecord::findOrFail($id),
            'energi' => EnvironmentEnergyRecord::findOrFail($id),
            'ews' => EnvironmentEwsRecord::findOrFail($id),
            default => abort(404, 'Modul environment tidak valid.'),
        };

        $paths = $record->attachment_list;
        if (!isset($paths[$index])) {
            abort(404, 'Dokumen lampiran tidak ditemukan.');
        }

        $filePath = ltrim((string) $paths[$index], '/');
        if ($filePath === '' || str_contains($filePath, '..')) {
            abort(404, 'Path dokumen tidak valid.');
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                $fullPath = $disk->path($filePath);
                $mime = $disk->mimeType($filePath) ?: 'application/octet-stream';
                $safeName = str_replace(['"', "\r", "\n", '\\', '/'], '_', basename($filePath));

                return response()->file($fullPath, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $safeName . '"',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404, 'File dokumen tidak ditemukan di penyimpanan.');
    }

    /**
     * Dashboard / Overview Ringkasan Lingkungan
     */
    public function index(Request $request): View
    {
        $year = (int) $request->input('year', date('Y'));

        // Rekap LB3 & Non-LB3
        $totalLb3Count = EnvironmentLb3Record::count();
        $totalLb3Weight = EnvironmentLb3Record::sum('amount');
        $totalNonLb3Count = EnvironmentNonLb3Record::count();
        $totalNonLb3Weight = EnvironmentNonLb3Record::sum('amount');

        // Rekap RKL-RPL
        $totalRklRplCount = EnvironmentRklRplRecord::count();
        $parametersCount = EnvironmentRklRplRecord::distinct('parameter_name')->count('parameter_name');

        // Rekap Inspeksi (Sebelumnya PPKH)
        $totalInspeksiCount = EnvironmentInspectionRecord::count();
        $totalPpkhCount = $totalInspeksiCount;
        $totalPpichCount = $totalInspeksiCount;
        $inspeksiByType = EnvironmentInspectionRecord::select('inspection_type', DB::raw('count(*) as total'))
            ->groupBy('inspection_type')
            ->orderByDesc('total')
            ->get();
        $latestInspeksi = EnvironmentInspectionRecord::orderByDesc('sampling_date')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        // Rekap EWS
        $totalEwsCount = EnvironmentEwsRecord::count();
        $ewsByLocation = EnvironmentEwsRecord::select('location_name', DB::raw('count(*) as total'))
            ->groupBy('location_name')
            ->orderByDesc('total')
            ->get();

        // Rekap Cuaca (Pencatatan Parameter Cuaca Terpisah)
        $totalWeatherCount = EnvironmentWeatherRecord::count();
        $latestWeather = EnvironmentWeatherRecord::with('location')
            ->orderByDesc('recording_date')
            ->orderByDesc('recording_time')
            ->orderByDesc('id')
            ->first();
        $avgWeather = [
            'wind_speed' => (float) (EnvironmentWeatherRecord::avg('wind_speed') ?? 0),
            'rainfall' => (float) (EnvironmentWeatherRecord::avg('rainfall') ?? 0),
            'temperature' => (float) (EnvironmentWeatherRecord::avg('temperature') ?? 0),
            'humidity' => (float) (EnvironmentWeatherRecord::avg('humidity') ?? 0),
        ];

        // Data Tren Pemakaian BBM & Listrik per Bulan pada Tahun Terpilih
        $bbmMonthly = [];
        $listrikMonthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $bbmMonthly[$m] = (float) EnvironmentEnergyRecord::where('energy_type', 'bbm')
                ->where('year', $year)
                ->where('month', $m)
                ->value('usage_amount') ?? 0;

            $listrikMonthly[$m] = (float) EnvironmentEnergyRecord::where('energy_type', 'listrik')
                ->where('year', $year)
                ->where('month', $m)
                ->value('usage_amount') ?? 0;
        }

        $totalBbmYear = array_sum($bbmMonthly);
        $totalListrikYear = array_sum($listrikMonthly);

        $availableYears = EnvironmentEnergyRecord::distinct('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
        if (!in_array((int) date('Y'), $availableYears)) {
            array_unshift($availableYears, (int) date('Y'));
        }

        return view('data.environment.index', compact(
            'year',
            'availableYears',
            'totalLb3Count',
            'totalLb3Weight',
            'totalNonLb3Count',
            'totalNonLb3Weight',
            'totalRklRplCount',
            'parametersCount',
            'totalInspeksiCount',
            'inspeksiByType',
            'latestInspeksi',
            'totalPpkhCount',
            'totalPpichCount',
            'totalEwsCount',
            'ewsByLocation',
            'totalWeatherCount',
            'latestWeather',
            'avgWeather',
            'bbmMonthly',
            'listrikMonthly',
            'totalBbmYear',
            'totalListrikYear'
        ));
    }

    /**
     * Export PDF Ringkasan Lingkungan (Overview)
     */
    public function overviewExportPdf(Request $request): SymfonyResponse
    {
        $year = (int) $request->input('year', date('Y'));

        // Rekap LB3 & Non-LB3
        $totalLb3Count = EnvironmentLb3Record::count();
        $totalLb3Weight = EnvironmentLb3Record::sum('amount');
        $totalNonLb3Count = EnvironmentNonLb3Record::count();
        $totalNonLb3Weight = EnvironmentNonLb3Record::sum('amount');

        // Rekap RKL-RPL
        $totalRklRplCount = EnvironmentRklRplRecord::count();
        $parametersCount = EnvironmentRklRplRecord::distinct('parameter_name')->count('parameter_name');

        // Rekap Inspeksi & PPKH
        $totalInspeksiCount = EnvironmentInspectionRecord::count();
        $totalPpkhCount = $totalInspeksiCount;
        $inspeksiByType = EnvironmentInspectionRecord::select('inspection_type', DB::raw('count(*) as total'))
            ->groupBy('inspection_type')
            ->orderByDesc('total')
            ->get();

        // Rekap EWS
        $totalEwsCount = EnvironmentEwsRecord::count();
        $ewsByLocation = EnvironmentEwsRecord::select('location_name', DB::raw('count(*) as total'))
            ->groupBy('location_name')
            ->orderByDesc('total')
            ->get();

        // Rekap Cuaca
        $totalWeatherCount = EnvironmentWeatherRecord::count();
        $latestWeather = EnvironmentWeatherRecord::with('location')
            ->orderByDesc('recording_date')
            ->orderByDesc('recording_time')
            ->orderByDesc('id')
            ->first();

        // Data Tren Pemakaian BBM & Listrik per Bulan pada Tahun Terpilih
        $bbmMonthly = [];
        $listrikMonthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $bbmMonthly[$m] = (float) EnvironmentEnergyRecord::where('energy_type', 'bbm')
                ->where('year', $year)
                ->where('month', $m)
                ->value('usage_amount') ?? 0;

            $listrikMonthly[$m] = (float) EnvironmentEnergyRecord::where('energy_type', 'listrik')
                ->where('year', $year)
                ->where('month', $m)
                ->value('usage_amount') ?? 0;
        }

        $totalBbmYear = array_sum($bbmMonthly);
        $totalListrikYear = array_sum($listrikMonthly);

        $pdf = Pdf::loadView('exports.environment-overview-pdf', [
            'year' => $year,
            'totalLb3Count' => $totalLb3Count,
            'totalLb3Weight' => $totalLb3Weight,
            'totalNonLb3Count' => $totalNonLb3Count,
            'totalNonLb3Weight' => $totalNonLb3Weight,
            'totalRklRplCount' => $totalRklRplCount,
            'parametersCount' => $parametersCount,
            'totalInspeksiCount' => $totalInspeksiCount,
            'inspeksiByType' => $inspeksiByType,
            'totalPpkhCount' => $totalPpkhCount,
            'totalEwsCount' => $totalEwsCount,
            'ewsByLocation' => $ewsByLocation,
            'totalWeatherCount' => $totalWeatherCount,
            'latestWeather' => $latestWeather,
            'bbmMonthly' => $bbmMonthly,
            'listrikMonthly' => $listrikMonthly,
            'totalBbmYear' => $totalBbmYear,
            'totalListrikYear' => $totalListrikYear,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
            'generatedBy' => Auth::user()?->name ?? 'Administrator HSSE',
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Ringkasan_Environment_{$year}_" . date('Ymd_His') . '.pdf');
    }

    // =========================================================================
    // 1. LB3 (Limbah Bahan Berbahaya dan Beracun)
    // =========================================================================

    public function lb3Index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $unitFilter = $request->input('unit');
        $charFilter = $request->input('characteristic');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentLb3Record::with(['unit', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('waste_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($unitFilter, function ($q) use ($unitFilter) {
                $q->where(function ($sub) use ($unitFilter) {
                    $sub->where('unit_id', $unitFilter)
                        ->orWhere('unit_name', $unitFilter);
                });
            })
            ->when($charFilter, function ($q) use ($charFilter) {
                $q->whereJsonContains('characteristics', $charFilter);
            })
            ->when($startDate, fn($q) => $q->whereDate('entry_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('entry_date', '<=', $endDate))
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        $records = $query->paginate(15)->withQueryString();

        // Master Data untuk Dropdown Input Baru (Hanya Satuan Aktif yang dipetakan ke LB3)
        $units = EnvironmentMaster::active()->byCategory('unit')->forSubmodule('lb3')->get();
        $characteristics = EnvironmentMaster::active()->byCategory('lb3_characteristic')->get();

        // Semua master satuan untuk filter (termasuk yang nonaktif)
        $allUnits = EnvironmentMaster::byCategory('unit')->get();

        return view('data.environment.lb3.index', compact('records', 'units', 'characteristics', 'allUnits'));
    }

    public function lb3Store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'waste_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.001',
            'unit_id' => 'required|exists:environment_masters,id',
            'entry_date' => 'required|date',
            'characteristics' => 'nullable|array',
            'characteristics.*' => 'string|max:100',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $unitMaster = EnvironmentMaster::findOrFail($validated['unit_id']);

        $storedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $storedAttachments[] = $file->store('environment/lb3', 'local');
                }
            }
        }

        EnvironmentLb3Record::create([
            'waste_name' => trim($validated['waste_name']),
            'amount' => $validated['amount'],
            'unit_id' => $unitMaster->id,
            'unit_name' => $unitMaster->name,
            'entry_date' => $validated['entry_date'],
            'characteristics' => $validated['characteristics'] ?? [],
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $storedAttachments,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('environment.lb3.index')->with('success', 'Data LB3 berhasil dicatat.');
    }

    public function lb3Update(Request $request, EnvironmentLb3Record $record): RedirectResponse
    {
        $validated = $request->validate([
            'waste_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.001',
            'unit_id' => 'required|exists:environment_masters,id',
            'entry_date' => 'required|date',
            'characteristics' => 'nullable|array',
            'characteristics.*' => 'string|max:100',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'deleted_attachments' => 'nullable|array',
            'deleted_attachments.*' => 'string',
        ]);

        $unitMaster = EnvironmentMaster::findOrFail($validated['unit_id']);
        $currentAttachments = $record->attachment_list;
        $toPhysicallyDelete = [];

        if ($request->filled('deleted_attachments')) {
            $deletedList = (array) $request->input('deleted_attachments');
            $toPhysicallyDelete = array_intersect($currentAttachments, $deletedList);
            $currentAttachments = array_values(array_diff($currentAttachments, $deletedList));
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $currentAttachments[] = $file->store('environment/lb3', 'local');
                }
            }
        }

        $record->update([
            'waste_name' => trim($validated['waste_name']),
            'amount' => $validated['amount'],
            'unit_id' => $unitMaster->id,
            'unit_name' => $unitMaster->name,
            'entry_date' => $validated['entry_date'],
            'characteristics' => $validated['characteristics'] ?? [],
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $currentAttachments,
        ]);

        $this->deleteStoredEnvFiles($toPhysicallyDelete);

        return redirect()->route('environment.lb3.index')->with('success', 'Data LB3 berhasil diperbarui.');
    }

    public function lb3Destroy(EnvironmentLb3Record $record): RedirectResponse
    {
        $files = $record->attachment_list;
        $record->delete();

        $this->deleteStoredEnvFiles($files);

        return redirect()->route('environment.lb3.index')->with('success', 'Data LB3 berhasil dihapus.');
    }

    public function lb3ExportPdf(Request $request): SymfonyResponse
    {
        $search = trim((string) $request->input('search'));
        $unitFilter = $request->input('unit');
        $charFilter = $request->input('characteristic');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentLb3Record::with(['unit', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('waste_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($unitFilter, function ($q) use ($unitFilter) {
                $q->where(function ($sub) use ($unitFilter) {
                    $sub->where('unit_id', $unitFilter)
                        ->orWhere('unit_name', $unitFilter);
                });
            })
            ->when($charFilter, function ($q) use ($charFilter) {
                $q->whereJsonContains('characteristics', $charFilter);
            })
            ->when($startDate, fn($q) => $q->whereDate('entry_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('entry_date', '<=', $endDate))
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        $records = $query->get();

        $pdf = Pdf::loadView('exports.environment-lb3-pdf', [
            'records' => $records,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
            'generatedBy' => Auth::user()?->name ?? 'Administrator HSSE',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_LB3_' . date('Ymd_His') . '.pdf');
    }

    public function lb3ExportExcel(Request $request): SymfonyResponse
    {
        $records = EnvironmentLb3Record::with('unit')->orderByDesc('entry_date')->get();
        $filename = 'Laporan_LB3_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['No', 'Nama Limbah', 'Jumlah', 'Satuan', 'Tanggal Masuk', 'Karakteristik', 'Keterangan', 'Jumlah Lampiran']);

            foreach ($records as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r->waste_name,
                    $r->amount,
                    $r->unit_label,
                    $r->entry_date ? $r->entry_date->format('Y-m-d') : '-',
                    implode(', ', $r->characteristics_list),
                    $r->description ?? '-',
                    count($r->attachment_list),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // 2. Non-LB3 (Limbah Non-B3)
    // =========================================================================

    public function nonLb3Index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $sourceFilter = $request->input('source');
        $unitFilter = $request->input('unit');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentNonLb3Record::with(['sourceMaster', 'unit', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('waste_name', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($sourceFilter, function ($q) use ($sourceFilter) {
                $q->where(function ($sub) use ($sourceFilter) {
                    $sub->where('source_id', $sourceFilter)
                        ->orWhere('source', $sourceFilter);
                });
            })
            ->when($unitFilter, function ($q) use ($unitFilter) {
                $q->where(function ($sub) use ($unitFilter) {
                    $sub->where('unit_id', $unitFilter)
                        ->orWhere('unit_name', $unitFilter);
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('entry_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('entry_date', '<=', $endDate))
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        $records = $query->paginate(15)->withQueryString();

        // Master Data untuk Dropdown Input Baru (Hanya Satuan Aktif yang dipetakan ke Non-LB3)
        $units = EnvironmentMaster::active()->byCategory('unit')->forSubmodule('non_lb3')->get();
        $sources = EnvironmentMaster::active()->byCategory('non_lb3_source')->get();

        $allUnits = EnvironmentMaster::byCategory('unit')->get();
        $allSources = EnvironmentMaster::byCategory('non_lb3_source')->get();

        return view('data.environment.non-lb3.index', compact('records', 'units', 'sources', 'allUnits', 'allSources'));
    }

    public function nonLb3Store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'waste_name' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.001',
            'unit_id' => 'required|exists:environment_masters,id',
            'entry_date' => 'required|date',
            'transport_date' => 'nullable|date|after_or_equal:entry_date',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $unitMaster = EnvironmentMaster::findOrFail($validated['unit_id']);
        $sourceName = trim($validated['source']);

        $sourceMasterId = EnvironmentMaster::where('name', $sourceName)->value('id');

        $storedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $storedAttachments[] = $file->store('environment/non_lb3', 'local');
                }
            }
        }

        EnvironmentNonLb3Record::create([
            'waste_name' => trim($validated['waste_name']),
            'source' => $sourceName,
            'source_id' => $sourceMasterId,
            'amount' => $validated['amount'],
            'unit_id' => $unitMaster->id,
            'unit_name' => $unitMaster->name,
            'entry_date' => $validated['entry_date'],
            'transport_date' => $validated['transport_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $storedAttachments,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('environment.non-lb3.index')->with('success', 'Data Non-LB3 berhasil dicatat.');
    }

    public function nonLb3Update(Request $request, EnvironmentNonLb3Record $record): RedirectResponse
    {
        $validated = $request->validate([
            'waste_name' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.001',
            'unit_id' => 'required|exists:environment_masters,id',
            'entry_date' => 'required|date',
            'transport_date' => 'nullable|date|after_or_equal:entry_date',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'deleted_attachments' => 'nullable|array',
            'deleted_attachments.*' => 'string',
        ]);

        $unitMaster = EnvironmentMaster::findOrFail($validated['unit_id']);
        $sourceName = trim($validated['source']);

        $currentAttachments = $record->attachment_list;
        $toPhysicallyDelete = [];

        if ($request->filled('deleted_attachments')) {
            $deletedList = (array) $request->input('deleted_attachments');
            $toPhysicallyDelete = array_intersect($currentAttachments, $deletedList);
            $currentAttachments = array_values(array_diff($currentAttachments, $deletedList));
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $currentAttachments[] = $file->store('environment/non_lb3', 'local');
                }
            }
        }

        $record->update([
            'waste_name' => trim($validated['waste_name']),
            'source' => $sourceName,
            'amount' => $validated['amount'],
            'unit_id' => $unitMaster->id,
            'unit_name' => $unitMaster->name,
            'entry_date' => $validated['entry_date'],
            'transport_date' => $validated['transport_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $currentAttachments,
        ]);

        $this->deleteStoredEnvFiles($toPhysicallyDelete);

        return redirect()->route('environment.non-lb3.index')->with('success', 'Data Non-LB3 berhasil diperbarui.');
    }

    public function nonLb3Destroy(EnvironmentNonLb3Record $record): RedirectResponse
    {
        $files = $record->attachment_list;
        $record->delete();

        $this->deleteStoredEnvFiles($files);

        return redirect()->route('environment.non-lb3.index')->with('success', 'Data Non-LB3 berhasil dihapus.');
    }

    public function nonLb3ExportPdf(Request $request): SymfonyResponse
    {
        $search = trim((string) $request->input('search'));
        $sourceFilter = $request->input('source');
        $unitFilter = $request->input('unit');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentNonLb3Record::with(['sourceMaster', 'unit', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('waste_name', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($sourceFilter, function ($q) use ($sourceFilter) {
                $q->where(function ($sub) use ($sourceFilter) {
                    $sub->where('source_id', $sourceFilter)
                        ->orWhere('source', $sourceFilter);
                });
            })
            ->when($unitFilter, function ($q) use ($unitFilter) {
                $q->where(function ($sub) use ($unitFilter) {
                    $sub->where('unit_id', $unitFilter)
                        ->orWhere('unit_name', $unitFilter);
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('entry_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('entry_date', '<=', $endDate))
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        $records = $query->get();

        $pdf = Pdf::loadView('exports.environment-non-lb3-pdf', [
            'records' => $records,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
            'generatedBy' => Auth::user()?->name ?? 'Administrator HSSE',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_Non_LB3_' . date('Ymd_His') . '.pdf');
    }

    public function nonLb3ExportExcel(Request $request): SymfonyResponse
    {
        $records = EnvironmentNonLb3Record::with(['sourceMaster', 'unit'])->orderByDesc('entry_date')->get();
        $filename = 'Laporan_Non_LB3_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['No', 'Nama Limbah', 'Sumber Limbah', 'Jumlah', 'Satuan', 'Tanggal Masuk', 'Tanggal Angkut', 'Status Pengangkutan', 'Keterangan']);

            foreach ($records as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r->waste_name,
                    $r->source_label,
                    $r->amount,
                    $r->unit_label,
                    $r->entry_date ? $r->entry_date->format('Y-m-d') : '-',
                    $r->transport_date ? $r->transport_date->format('Y-m-d') : '-',
                    $r->transport_date ? 'Sudah Diangkut' : 'Belum Diangkut',
                    $r->description ?? '-',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // 3. RKL–RPL (Pemantauan Lingkungan)
    // =========================================================================

    public function rklRplIndex(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $paramFilter = $request->input('parameter');
        $locFilter = $request->input('location');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentRklRplRecord::with(['parameter', 'location', 'unit', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('parameter_name', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%")
                        ->orWhere('measurement_value', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($paramFilter, function ($q) use ($paramFilter) {
                $q->where(function ($sub) use ($paramFilter) {
                    $sub->where('parameter_id', $paramFilter)
                        ->orWhere('parameter_name', $paramFilter);
                });
            })
            ->when($locFilter, function ($q) use ($locFilter) {
                $q->where(function ($sub) use ($locFilter) {
                    $sub->where('location_id', $locFilter)
                        ->orWhere('location_name', $locFilter);
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('sampling_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('sampling_date', '<=', $endDate))
            ->orderByDesc('sampling_date')
            ->orderByDesc('id');

        $records = $query->paginate(15)->withQueryString();

        $parameters = EnvironmentMaster::active()->byCategory('rkl_rpl_parameter')->get();
        $locations = EnvironmentMaster::active()->byCategory('rkl_rpl_location')->get();
        $units = EnvironmentMaster::active()->byCategory('unit')->forSubmodule('rkl_rpl')->get();

        $allParameters = EnvironmentMaster::byCategory('rkl_rpl_parameter')->get();
        $allLocations = EnvironmentMaster::byCategory('rkl_rpl_location')->get();
        $allUnits = EnvironmentMaster::byCategory('unit')->get();

        return view('data.environment.rkl-rpl.index', compact('records', 'parameters', 'locations', 'units', 'allParameters', 'allLocations', 'allUnits'));
    }

    public function rklRplStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parameter_id' => 'required|exists:environment_masters,id',
            'location_id' => 'required|exists:environment_masters,id',
            'sampling_date' => 'required|date',
            'measurement_value' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:environment_masters,id',
            'unit_name' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $paramMaster = EnvironmentMaster::findOrFail($validated['parameter_id']);
        $locMaster = EnvironmentMaster::findOrFail($validated['location_id']);

        $unitName = null;
        if (!empty($validated['unit_id'])) {
            $u = EnvironmentMaster::find($validated['unit_id']);
            if ($u) $unitName = $u->name;
        } elseif (!empty($validated['unit_name'])) {
            $unitName = trim($validated['unit_name']);
        }

        $storedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $storedAttachments[] = $file->store('environment/rkl_rpl', 'local');
                }
            }
        }

        EnvironmentRklRplRecord::create([
            'parameter_id' => $paramMaster->id,
            'parameter_name' => $paramMaster->name,
            'location_id' => $locMaster->id,
            'location_name' => $locMaster->name,
            'sampling_date' => $validated['sampling_date'],
            'measurement_value' => trim($validated['measurement_value']),
            'unit_id' => $validated['unit_id'] ?? null,
            'unit_name' => $unitName,
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $storedAttachments,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('environment.rkl-rpl.index')->with('success', 'Data pemantauan RKL–RPL berhasil dicatat.');
    }

    public function rklRplUpdate(Request $request, EnvironmentRklRplRecord $record): RedirectResponse
    {
        $validated = $request->validate([
            'parameter_id' => 'required|exists:environment_masters,id',
            'location_id' => 'required|exists:environment_masters,id',
            'sampling_date' => 'required|date',
            'measurement_value' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:environment_masters,id',
            'unit_name' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'deleted_attachments' => 'nullable|array',
            'deleted_attachments.*' => 'string',
        ]);

        $paramMaster = EnvironmentMaster::findOrFail($validated['parameter_id']);
        $locMaster = EnvironmentMaster::findOrFail($validated['location_id']);

        $unitName = null;
        if (!empty($validated['unit_id'])) {
            $u = EnvironmentMaster::find($validated['unit_id']);
            if ($u) $unitName = $u->name;
        } elseif (!empty($validated['unit_name'])) {
            $unitName = trim($validated['unit_name']);
        }

        $currentAttachments = $record->attachment_list;
        $toPhysicallyDelete = [];

        if ($request->filled('deleted_attachments')) {
            $deletedList = (array) $request->input('deleted_attachments');
            $toPhysicallyDelete = array_intersect($currentAttachments, $deletedList);
            $currentAttachments = array_values(array_diff($currentAttachments, $deletedList));
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $currentAttachments[] = $file->store('environment/rkl_rpl', 'local');
                }
            }
        }

        $record->update([
            'parameter_id' => $paramMaster->id,
            'parameter_name' => $paramMaster->name,
            'location_id' => $locMaster->id,
            'location_name' => $locMaster->name,
            'sampling_date' => $validated['sampling_date'],
            'measurement_value' => trim($validated['measurement_value']),
            'unit_id' => $validated['unit_id'] ?? null,
            'unit_name' => $unitName,
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $currentAttachments,
        ]);

        $this->deleteStoredEnvFiles($toPhysicallyDelete);

        return redirect()->route('environment.rkl-rpl.index')->with('success', 'Data pemantauan RKL–RPL berhasil diperbarui.');
    }

    public function rklRplDestroy(EnvironmentRklRplRecord $record): RedirectResponse
    {
        $files = $record->attachment_list;
        $record->delete();

        $this->deleteStoredEnvFiles($files);

        return redirect()->route('environment.rkl-rpl.index')->with('success', 'Data pemantauan RKL–RPL berhasil dihapus.');
    }

    public function rklRplExportPdf(Request $request): SymfonyResponse
    {
        $search = trim((string) $request->input('search'));
        $paramFilter = $request->input('parameter');
        $locFilter = $request->input('location');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentRklRplRecord::with(['parameter', 'location', 'unit', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('parameter_name', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%")
                        ->orWhere('measurement_value', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($paramFilter, function ($q) use ($paramFilter) {
                $q->where(function ($sub) use ($paramFilter) {
                    $sub->where('parameter_id', $paramFilter)
                        ->orWhere('parameter_name', $paramFilter);
                });
            })
            ->when($locFilter, function ($q) use ($locFilter) {
                $q->where(function ($sub) use ($locFilter) {
                    $sub->where('location_id', $locFilter)
                        ->orWhere('location_name', $locFilter);
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('sampling_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('sampling_date', '<=', $endDate))
            ->orderByDesc('sampling_date')
            ->orderByDesc('id');

        $records = $query->get();

        $pdf = Pdf::loadView('exports.environment-rkl-rpl-pdf', [
            'records' => $records,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
            'generatedBy' => Auth::user()?->name ?? 'Administrator HSSE',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan_RKL_RPL_' . date('Ymd_His') . '.pdf');
    }

    public function rklRplExportExcel(Request $request): SymfonyResponse
    {
        $records = EnvironmentRklRplRecord::with(['parameter', 'location', 'unit'])->orderByDesc('sampling_date')->get();
        $filename = 'Laporan_RKL_RPL_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['No', 'Parameter', 'Lokasi Pemantauan', 'Tanggal Pengukuran/Sampling', 'Hasil/Nilai', 'Satuan', 'Catatan/Keterangan']);

            foreach ($records as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r->parameter_label,
                    $r->location_label,
                    $r->sampling_date ? $r->sampling_date->format('Y-m-d') : '-',
                    $r->measurement_value,
                    $r->unit_label ?: '-',
                    $r->description ?? '-',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // 4. PPICH (Pemantauan Lokasi)
    // =========================================================================

    // =========================================================================
    // 4. Inspeksi (Sebelumnya PPKH - Persetujuan Penggunaan Kawasan Hutan)
    // =========================================================================

    public function inspeksiIndex(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $typeFilter = $request->input('inspection_type');
        $lokasiFilter = $request->input('location', $request->input('monitoring_lokasi'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentInspectionRecord::with(['creator', 'inspectionTypeMaster', 'location'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('inspection_type', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%")
                        ->orWhere('reference_name', 'like', "%{$search}%")
                        ->orWhere('condition', 'like', "%{$search}%")
                        ->orWhere('activity_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($typeFilter, function ($q) use ($typeFilter) {
                $q->where(function ($sub) use ($typeFilter) {
                    $sub->where('inspection_type', $typeFilter)
                        ->orWhere('inspection_type_id', $typeFilter);
                });
            })
            ->when($lokasiFilter, function ($q) use ($lokasiFilter) {
                $q->where(function ($sub) use ($lokasiFilter) {
                    $sub->where('location_name', $lokasiFilter)
                        ->orWhere('reference_name', $lokasiFilter)
                        ->orWhere('location_id', $lokasiFilter);
                });
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->where(function ($sub) use ($startDate) {
                    $sub->whereDate('inspection_date', '>=', $startDate)
                        ->orWhere(function ($s2) use ($startDate) {
                            $s2->whereNull('inspection_date')
                               ->whereDate('sampling_date', '>=', $startDate);
                        });
                });
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->where(function ($sub) use ($endDate) {
                    $sub->whereDate('inspection_date', '<=', $endDate)
                        ->orWhere(function ($s2) use ($endDate) {
                            $s2->whereNull('inspection_date')
                               ->whereDate('sampling_date', '<=', $endDate);
                        });
                });
            })
            ->orderByRaw('COALESCE(inspection_date, sampling_date, created_at) DESC')
            ->orderByDesc('id');

        $records = $query->paginate(15)->withQueryString();

        // Master Data dropdown options
        $inspectionTypes = EnvironmentMaster::active()->byCategory('inspection_type')->orderBy('order_index')->get();
        if ($inspectionTypes->isEmpty()) {
            $typeOptions = EnvironmentMaster::defaultInspectionTypes();
        } else {
            $typeOptions = $inspectionTypes->pluck('name')->toArray();
        }

        $locationMasters = EnvironmentMaster::active()->byCategory('inspection_location')->orderBy('order_index')->get();
        if ($locationMasters->isEmpty()) {
            $locationOptions = EnvironmentMaster::defaultInspectionLocations();
        } else {
            $locationOptions = $locationMasters->pluck('name')->toArray();
        }

        $allInspectionTypes = EnvironmentMaster::byCategory('inspection_type')->orderBy('order_index')->get();
        $allLocations = EnvironmentMaster::byCategory('inspection_location')->orderBy('order_index')->get();

        return view('data.environment.inspeksi.index', compact(
            'records',
            'inspectionTypes',
            'locationMasters',
            'typeOptions',
            'locationOptions',
            'allInspectionTypes',
            'allLocations'
        ));
    }

    public function inspeksiStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inspection_type' => 'nullable|string|max:150',
            'inspection_type_id' => 'nullable|integer',
            'location_name' => 'nullable|string|max:150',
            'location_id' => 'nullable|integer',
            'condition' => 'nullable|string',
            'inspection_date' => 'nullable|date',
            'monitoring_lokasi' => 'nullable|string|max:150',
            'activity' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Resolusi jenis inspeksi
        $inspectionTypeName = $validated['inspection_type'] ?? null;
        $inspectionTypeId = $validated['inspection_type_id'] ?? null;
        if (!$inspectionTypeName && $inspectionTypeId) {
            $master = EnvironmentMaster::find($inspectionTypeId);
            $inspectionTypeName = $master?->name;
        }
        if (!$inspectionTypeName) {
            $inspectionTypeName = 'PPKH/Lahan';
        }

        // Resolusi lokasi inspeksi
        $locationName = $validated['location_name'] ?? ($validated['monitoring_lokasi'] ?? null);
        $locationId = $validated['location_id'] ?? null;
        if (!$locationName && $locationId) {
            $masterLoc = EnvironmentMaster::find($locationId);
            $locationName = $masterLoc?->name;
        }
        if (!$locationName) {
            $locationName = 'Area Well Pad';
        }

        // Resolusi kondisi / aktivitas (free text)
        $conditionText = trim((string) ($validated['condition'] ?? ($validated['activity'] ?? '')));
        if ($conditionText === '') {
            $conditionText = 'Kondisi terpantau normal dan sesuai standar operasional.';
        }

        // Resolusi tanggal
        $inspectionDate = $validated['inspection_date'] ?? now()->toDateString();

        $storedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $storedAttachments[] = $file->store('environment/inspeksi', 'local');
                }
            }
        }

        EnvironmentInspectionRecord::create([
            'inspection_type_id' => $inspectionTypeId,
            'inspection_type' => $inspectionTypeName,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'reference_name' => $locationName,
            'condition' => $conditionText,
            'activity_name' => $conditionText,
            'description' => $conditionText,
            'inspection_date' => $inspectionDate,
            'sampling_date' => $inspectionDate,
            'attachment_paths' => $storedAttachments,
            'created_by' => Auth::id(),
        ]);

        $redirectRoute = $request->routeIs('environment.ppkh.*') ? 'environment.ppkh.index' : 'environment.inspeksi.index';
        return redirect()->route($redirectRoute)->with('success', 'Data hasil inspeksi berhasil dicatat.');
    }

    public function inspeksiUpdate(Request $request, $recordId): RedirectResponse
    {
        $record = EnvironmentInspectionRecord::findOrFail($recordId);

        $validated = $request->validate([
            'inspection_type' => 'nullable|string|max:150',
            'inspection_type_id' => 'nullable|integer',
            'location_name' => 'nullable|string|max:150',
            'location_id' => 'nullable|integer',
            'condition' => 'nullable|string',
            'inspection_date' => 'nullable|date',
            'monitoring_lokasi' => 'nullable|string|max:150',
            'activity' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'deleted_attachments' => 'nullable|array',
            'deleted_attachments.*' => 'string',
        ]);

        $inspectionTypeName = $validated['inspection_type'] ?? $record->inspection_type;
        $inspectionTypeId = $validated['inspection_type_id'] ?? $record->inspection_type_id;
        if (!$inspectionTypeName && $inspectionTypeId) {
            $inspectionTypeName = EnvironmentMaster::find($inspectionTypeId)?->name ?? $record->inspection_type;
        }

        $locationName = $validated['location_name'] ?? ($validated['monitoring_lokasi'] ?? $record->location_name);
        $locationId = $validated['location_id'] ?? $record->location_id;
        if (!$locationName && $locationId) {
            $locationName = EnvironmentMaster::find($locationId)?->name ?? $record->location_name;
        }

        $conditionText = trim((string) ($validated['condition'] ?? ($validated['activity'] ?? $record->condition)));
        if ($conditionText === '') {
            $conditionText = $record->condition ?: ($record->activity_name ?: '-');
        }

        $inspectionDate = $validated['inspection_date'] ?? ($record->inspection_date ? $record->inspection_date->format('Y-m-d') : ($record->sampling_date ? $record->sampling_date->format('Y-m-d') : now()->toDateString()));

        $currentAttachments = $record->attachment_list;
        $toPhysicallyDelete = [];

        if ($request->filled('deleted_attachments')) {
            $deletedList = (array) $request->input('deleted_attachments');
            $toPhysicallyDelete = array_intersect($currentAttachments, $deletedList);
            $currentAttachments = array_values(array_diff($currentAttachments, $deletedList));
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $currentAttachments[] = $file->store('environment/inspeksi', 'local');
                }
            }
        }

        $record->update([
            'inspection_type_id' => $inspectionTypeId,
            'inspection_type' => $inspectionTypeName,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'reference_name' => $locationName,
            'condition' => $conditionText,
            'activity_name' => $conditionText,
            'description' => $conditionText,
            'inspection_date' => $inspectionDate,
            'sampling_date' => $inspectionDate,
            'attachment_paths' => $currentAttachments,
        ]);

        $this->deleteStoredEnvFiles($toPhysicallyDelete);

        $redirectRoute = $request->routeIs('environment.ppkh.*') ? 'environment.ppkh.index' : 'environment.inspeksi.index';
        return redirect()->route($redirectRoute)->with('success', 'Data inspeksi berhasil diperbarui.');
    }

    public function inspeksiDestroy(Request $request, $recordId): RedirectResponse
    {
        $record = EnvironmentInspectionRecord::findOrFail($recordId);
        $files = $record->attachment_list;
        $record->delete();

        $this->deleteStoredEnvFiles($files);

        $redirectRoute = $request->routeIs('environment.ppkh.*') ? 'environment.ppkh.index' : 'environment.inspeksi.index';
        return redirect()->route($redirectRoute)->with('success', 'Data inspeksi berhasil dihapus.');
    }

    public function inspeksiExportPdf(Request $request): SymfonyResponse
    {
        $search = trim((string) $request->input('search'));
        $typeFilter = $request->input('inspection_type');
        $lokasiFilter = $request->input('location', $request->input('monitoring_lokasi'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentInspectionRecord::with(['creator', 'inspectionTypeMaster', 'location'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('inspection_type', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%")
                        ->orWhere('reference_name', 'like', "%{$search}%")
                        ->orWhere('condition', 'like', "%{$search}%")
                        ->orWhere('activity_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($typeFilter, function ($q) use ($typeFilter) {
                $q->where(function ($sub) use ($typeFilter) {
                    $sub->where('inspection_type', $typeFilter)
                        ->orWhere('inspection_type_id', $typeFilter);
                });
            })
            ->when($lokasiFilter, function ($q) use ($lokasiFilter) {
                $q->where(function ($sub) use ($lokasiFilter) {
                    $sub->where('location_name', $lokasiFilter)
                        ->orWhere('reference_name', $lokasiFilter)
                        ->orWhere('location_id', $lokasiFilter);
                });
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->where(function ($sub) use ($startDate) {
                    $sub->whereDate('inspection_date', '>=', $startDate)
                        ->orWhere(function ($s2) use ($startDate) {
                            $s2->whereNull('inspection_date')
                               ->whereDate('sampling_date', '>=', $startDate);
                        });
                });
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->where(function ($sub) use ($endDate) {
                    $sub->whereDate('inspection_date', '<=', $endDate)
                        ->orWhere(function ($s2) use ($endDate) {
                            $s2->whereNull('inspection_date')
                               ->whereDate('sampling_date', '<=', $endDate);
                        });
                });
            })
            ->orderByRaw('COALESCE(inspection_date, sampling_date, created_at) DESC')
            ->orderByDesc('id');

        $records = $query->get();

        $pdf = Pdf::loadView('exports.environment-inspeksi-pdf', [
            'records' => $records,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
            'generatedBy' => Auth::user()?->name ?? 'Administrator HSSE',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_Inspeksi_Lingkungan_' . date('Ymd_His') . '.pdf');
    }

    public function inspeksiExportExcel(Request $request): SymfonyResponse
    {
        $records = EnvironmentInspectionRecord::orderByRaw('COALESCE(inspection_date, sampling_date, created_at) DESC')->orderByDesc('id')->get();
        $filename = 'Laporan_Inspeksi_Lingkungan_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['No', 'Tanggal Inspeksi', 'Jenis Inspeksi', 'Lokasi', 'Kondisi Hasil Inspeksi', 'Jumlah Lampiran', 'Pencatat']);

            foreach ($records as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r->formatted_date,
                    $r->inspection_type_label,
                    $r->location_label,
                    $r->condition_text,
                    count($r->attachment_list),
                    $r->creator?->name ?? 'Sistem',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // Legacy Aliases for PPKH & PPICH (Backward Compatibility)
    // =========================================================================

    public function ppkhIndex(Request $request): View
    {
        return $this->inspeksiIndex($request);
    }

    public function ppkhStore(Request $request): RedirectResponse
    {
        // Jika request khusus PPKH format lama (monitoring_lokasi harus SK 2013 / SK 2024 jika tidak menyertakan inspection_type)
        if ($request->has('monitoring_lokasi') && !$request->has('inspection_type') && !$request->has('location_name')) {
            $request->validate([
                'monitoring_lokasi' => ['required', 'string', Rule::in(['SK 2013', 'SK 2024'])],
                'activity' => 'required|string',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);
        }

        return $this->inspeksiStore($request);
    }

    public function ppkhUpdate(Request $request, $record): RedirectResponse
    {
        $recordId = $record instanceof EnvironmentPpichRecord ? $record->id : $record;
        if ($request->has('monitoring_lokasi') && !$request->has('inspection_type') && !$request->has('location_name')) {
            $request->validate([
                'monitoring_lokasi' => ['required', 'string', Rule::in(['SK 2013', 'SK 2024'])],
                'activity' => 'required|string',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'deleted_attachments' => 'nullable|array',
                'deleted_attachments.*' => 'string',
            ]);
        }

        return $this->inspeksiUpdate($request, $recordId);
    }

    public function ppkhDestroy(Request $request, $record): RedirectResponse
    {
        $recordId = $record instanceof EnvironmentPpichRecord ? $record->id : $record;
        return $this->inspeksiDestroy($request, $recordId);
    }

    public function ppkhExportPdf(Request $request): SymfonyResponse
    {
        return $this->inspeksiExportPdf($request);
    }

    public function ppkhExportExcel(Request $request): SymfonyResponse
    {
        return $this->inspeksiExportExcel($request);
    }

    public function ppichIndex(Request $request)
    {
        return $this->inspeksiIndex($request);
    }

    public function ppichStore(Request $request)
    {
        return $this->inspeksiStore($request);
    }

    public function ppichUpdate(Request $request, $record)
    {
        return $this->inspeksiUpdate($request, $record instanceof EnvironmentPpichRecord ? $record->id : $record);
    }

    public function ppichDestroy($record)
    {
        return $this->inspeksiDestroy(request(), $record instanceof EnvironmentPpichRecord ? $record->id : $record);
    }

    public function ppichExportPdf(Request $request)
    {
        return $this->inspeksiExportPdf($request);
    }

    public function ppichExportExcel(Request $request)
    {
        return $this->inspeksiExportExcel($request);
    }

    // =========================================================================
    // 5. Energi (BBM dan Listrik)
    // =========================================================================

    public function energyIndex(Request $request): View
    {
        $type = in_array($request->input('tab'), ['listrik', 'bbm']) ? $request->input('tab') : 'bbm';
        $year = (int) $request->input('year', date('Y'));

        $query = EnvironmentEnergyRecord::where('energy_type', $type)
            ->when($year, fn($q) => $q->where('year', $year))
            ->orderBy('month');

        $records = $query->get();

        // Rekap Tahunan & Rata-rata Bulanan
        $totalUsage = $records->sum('usage_amount');
        $averageMonthly = $records->count() > 0 ? ($totalUsage / $records->count()) : 0;
        $maxUsage = $records->max('usage_amount') ?? 0;

        // Data Grafik Tren 12 Bulan
        $trendData = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $records->firstWhere('month', $m);
            $trendData[$m] = $row ? (float) $row->usage_amount : 0;
        }

        $availableYears = EnvironmentEnergyRecord::where('energy_type', $type)
            ->distinct('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
        if (!in_array((int) date('Y'), $availableYears)) {
            array_unshift($availableYears, (int) date('Y'));
        }

        $monthNames = EnvironmentEnergyRecord::monthNames();

        return view('data.environment.energi.index', compact(
            'type',
            'year',
            'records',
            'totalUsage',
            'averageMonthly',
            'maxUsage',
            'trendData',
            'availableYears',
            'monthNames'
        ));
    }

    public function energyStore(Request $request): RedirectResponse
    {
        $type = in_array($request->input('energy_type'), ['bbm', 'listrik']) ? $request->input('energy_type') : 'bbm';
        $unit = $type === 'bbm' ? 'Liter' : 'kWh';

        $validated = $request->validate([
            'energy_type' => ['required', Rule::in(['bbm', 'listrik'])],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'usage_amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        // Cek duplikasi periode untuk jenis energi yang sama
        $exists = EnvironmentEnergyRecord::where('energy_type', $validated['energy_type'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            $monthName = EnvironmentEnergyRecord::monthNames()[$validated['month']];
            return redirect()->back()
                ->withInput()
                ->withErrors(['month' => "Data pemakaian {$type} untuk periode {$monthName} {$validated['year']} sudah tercatat. Silakan lakukan edit jika ingin memperbarui."]);
        }

        $storedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $storedAttachments[] = $file->store("environment/energy/{$type}", 'local');
                }
            }
        }

        EnvironmentEnergyRecord::create([
            'energy_type' => $validated['energy_type'],
            'month' => $validated['month'],
            'year' => $validated['year'],
            'usage_amount' => $validated['usage_amount'],
            'unit' => $unit,
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $storedAttachments,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('environment.energi.index', ['tab' => $type, 'year' => $validated['year']])
            ->with('success', "Data pemakaian {$type} periode " . EnvironmentEnergyRecord::monthNames()[$validated['month']] . " {$validated['year']} berhasil disimpan.");
    }

    public function energyUpdate(Request $request, EnvironmentEnergyRecord $record): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'usage_amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'deleted_attachments' => ['nullable', 'array'],
            'deleted_attachments.*' => ['string'],
        ]);

        // Cek duplikasi periode selain record ini
        $exists = EnvironmentEnergyRecord::where('energy_type', $record->energy_type)
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->where('id', '!=', $record->id)
            ->exists();

        if ($exists) {
            $monthName = EnvironmentEnergyRecord::monthNames()[$validated['month']];
            return redirect()->back()
                ->withInput()
                ->withErrors(['month' => "Data pemakaian {$record->energy_type} untuk periode {$monthName} {$validated['year']} sudah tercatat di data lain."]);
        }

        $currentAttachments = $record->attachment_list;
        $toPhysicallyDelete = [];

        if ($request->filled('deleted_attachments')) {
            $deletedList = (array) $request->input('deleted_attachments');
            $toPhysicallyDelete = array_intersect($currentAttachments, $deletedList);
            $currentAttachments = array_values(array_diff($currentAttachments, $deletedList));
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $currentAttachments[] = $file->store("environment/energy/{$record->energy_type}", 'local');
                }
            }
        }

        $record->update([
            'month' => $validated['month'],
            'year' => $validated['year'],
            'usage_amount' => $validated['usage_amount'],
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $currentAttachments,
        ]);

        $this->deleteStoredEnvFiles($toPhysicallyDelete);

        return redirect()->route('environment.energi.index', ['tab' => $record->energy_type, 'year' => $validated['year']])
            ->with('success', "Data pemakaian {$record->energy_type} berhasil diperbarui.");
    }

    public function energyDestroy(EnvironmentEnergyRecord $record): RedirectResponse
    {
        $type = $record->energy_type;
        $year = $record->year;
        $files = $record->attachment_list;
        $record->delete();

        $this->deleteStoredEnvFiles($files);

        return redirect()->route('environment.energi.index', ['tab' => $type, 'year' => $year])
            ->with('success', "Data pemakaian {$type} berhasil dihapus.");
    }

    public function energyExportPdf(Request $request): SymfonyResponse
    {
        $type = in_array($request->input('tab'), ['listrik', 'bbm']) ? $request->input('tab') : 'bbm';
        $year = (int) $request->input('year', date('Y'));

        $records = EnvironmentEnergyRecord::where('energy_type', $type)
            ->where('year', $year)
            ->orderBy('month')
            ->get();

        $totalUsage = $records->sum('usage_amount');

        $pdf = Pdf::loadView('exports.environment-energy-pdf', [
            'type' => $type,
            'year' => $year,
            'records' => $records,
            'totalUsage' => $totalUsage,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
            'generatedBy' => Auth::user()?->name ?? 'Administrator HSSE',
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Laporan_Pemakaian_Energi_{$type}_{$year}_" . date('Ymd_His') . '.pdf');
    }

    public function energyExportExcel(Request $request): SymfonyResponse
    {
        $type = in_array($request->input('tab'), ['listrik', 'bbm']) ? $request->input('tab') : 'bbm';
        $year = (int) $request->input('year', date('Y'));

        $records = EnvironmentEnergyRecord::where('energy_type', $type)
            ->where('year', $year)
            ->orderBy('month')
            ->get();

        $filename = "Laporan_Pemakaian_Energi_{$type}_{$year}_" . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records, $type, $year) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Bulan', 'Tahun', 'Kategori', 'Jumlah Pemakaian', 'Satuan', 'Keterangan']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->month_name,
                    $r->year,
                    strtoupper($type),
                    $r->usage_amount,
                    $r->unit,
                    $r->description ?? '-',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // 6. EWS (Early Warning System)
    // =========================================================================

    public function ewsIndex(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $locFilter = $request->input('location');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentEwsRecord::with(['location', 'recorder'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('condition', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($locFilter, function ($q) use ($locFilter) {
                $q->where(function ($sub) use ($locFilter) {
                    $sub->where('location_id', $locFilter)
                        ->orWhere('location_name', $locFilter);
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('recording_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('recording_date', '<=', $endDate))
            ->orderByDesc('recording_date')
            ->orderByDesc('id');

        $records = $query->paginate(15)->withQueryString();

        $locations = EnvironmentMaster::active()->byCategory('ews_location')->get();
        $allLocations = EnvironmentMaster::byCategory('ews_location')->get();

        return view('data.environment.ews.index', compact('records', 'locations', 'allLocations'));
    }

    public function ewsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:environment_masters,id',
            'recording_date' => 'required|date',
            'condition' => 'required|string',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $locMaster = EnvironmentMaster::findOrFail($validated['location_id']);

        $storedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $storedAttachments[] = $file->store('environment/ews', 'local');
                }
            }
        }

        EnvironmentEwsRecord::create([
            'location_id' => $locMaster->id,
            'location_name' => $locMaster->name,
            'recording_date' => $validated['recording_date'],
            'condition' => trim($validated['condition']),
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $storedAttachments,
            'recorded_by' => Auth::id(),
        ]);

        return redirect()->route('environment.ews.index')->with('success', 'Pencatatan EWS berhasil disimpan.');
    }

    public function ewsUpdate(Request $request, EnvironmentEwsRecord $record): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:environment_masters,id',
            'recording_date' => 'required|date',
            'condition' => 'required|string',
            'description' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'deleted_attachments' => 'nullable|array',
            'deleted_attachments.*' => 'string',
        ]);

        $locMaster = EnvironmentMaster::findOrFail($validated['location_id']);

        $currentAttachments = $record->attachment_list;
        $toPhysicallyDelete = [];

        if ($request->filled('deleted_attachments')) {
            $deletedList = (array) $request->input('deleted_attachments');
            $toPhysicallyDelete = array_intersect($currentAttachments, $deletedList);
            $currentAttachments = array_values(array_diff($currentAttachments, $deletedList));
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $currentAttachments[] = $file->store('environment/ews', 'local');
                }
            }
        }

        $record->update([
            'location_id' => $locMaster->id,
            'location_name' => $locMaster->name,
            'recording_date' => $validated['recording_date'],
            'condition' => trim($validated['condition']),
            'description' => $validated['description'] ?? null,
            'attachment_paths' => $currentAttachments,
        ]);

        $this->deleteStoredEnvFiles($toPhysicallyDelete);

        return redirect()->route('environment.ews.index')->with('success', 'Pencatatan EWS berhasil diperbarui.');
    }

    public function ewsDestroy(EnvironmentEwsRecord $record): RedirectResponse
    {
        $files = $record->attachment_list;
        $record->delete();

        $this->deleteStoredEnvFiles($files);

        return redirect()->route('environment.ews.index')->with('success', 'Pencatatan EWS berhasil dihapus.');
    }

    public function ewsExportPdf(Request $request): SymfonyResponse
    {
        $search = trim((string) $request->input('search'));
        $locFilter = $request->input('location');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentEwsRecord::with(['location', 'recorder'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('condition', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($locFilter, function ($q) use ($locFilter) {
                $q->where(function ($sub) use ($locFilter) {
                    $sub->where('location_id', $locFilter)
                        ->orWhere('location_name', $locFilter);
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('recording_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('recording_date', '<=', $endDate))
            ->orderByDesc('recording_date')
            ->orderByDesc('id');

        $records = $query->get();

        $pdf = Pdf::loadView('exports.environment-ews-pdf', [
            'records' => $records,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
            'generatedBy' => Auth::user()?->name ?? 'Administrator HSSE',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_EWS_' . date('Ymd_His') . '.pdf');
    }

    public function ewsExportExcel(Request $request): SymfonyResponse
    {
        $records = EnvironmentEwsRecord::with(['location', 'recorder'])->orderByDesc('recording_date')->get();
        $filename = 'Laporan_EWS_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['No', 'Lokasi', 'Tanggal Pencatatan', 'Kondisi Aktual', 'Keterangan', 'Pencatat']);

            foreach ($records as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r->location_label,
                    $r->recording_date ? $r->recording_date->format('Y-m-d') : '-',
                    $r->condition,
                    $r->description ?? '-',
                    $r->recorder?->name ?? 'Sistem',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // 7. Cuaca (Pencatatan Parameter Cuaca Terpisah dari EWS)
    // =========================================================================

    public function cuacaIndex(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $locFilter = $request->input('location');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentWeatherRecord::with(['location', 'recorder'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('notes', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%");
                });
            })
            ->when($locFilter, function ($q) use ($locFilter) {
                $q->where(function ($sub) use ($locFilter) {
                    $sub->where('location_id', $locFilter)
                        ->orWhere('location_name', $locFilter);
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('recording_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('recording_date', '<=', $endDate))
            ->orderByDesc('recording_date')
            ->orderByDesc('recording_time')
            ->orderByDesc('id');

        $records = $query->paginate(15)->withQueryString();

        $locationMasters = EnvironmentMaster::active()->byCategory('inspection_location')->orderBy('order_index')->get();
        if ($locationMasters->isEmpty()) {
            $locationOptions = EnvironmentMaster::defaultInspectionLocations();
        } else {
            $locationOptions = $locationMasters->pluck('name')->toArray();
        }

        // Statistik ringkas cuaca terkini
        $latestRecord = EnvironmentWeatherRecord::with('location')
            ->orderByDesc('recording_date')
            ->orderByDesc('recording_time')
            ->orderByDesc('id')
            ->first();

        $avgWindSpeed = EnvironmentWeatherRecord::avg('wind_speed') ?? 0;
        $avgRainfall = EnvironmentWeatherRecord::avg('rainfall') ?? 0;
        $avgTemp = EnvironmentWeatherRecord::avg('temperature') ?? 0;
        $avgHumidity = EnvironmentWeatherRecord::avg('humidity') ?? 0;

        return view('data.environment.cuaca.index', compact(
            'records',
            'locationMasters',
            'locationOptions',
            'latestRecord',
            'avgWindSpeed',
            'avgRainfall',
            'avgTemp',
            'avgHumidity'
        ));
    }

    public function cuacaStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recording_date' => 'required|date',
            'recording_time' => 'nullable|string',
            'location_name' => 'nullable|string|max:150',
            'location_id' => 'nullable|integer',
            'wind_speed' => 'required|numeric|min:0',
            'rainfall' => 'required|numeric|min:0',
            'temperature' => 'required|numeric',
            'humidity' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $carbonDate = Carbon::parse($validated['recording_date']);
        $dayName = $days[$carbonDate->dayOfWeek] ?? 'Rabu';

        $recTime = $validated['recording_time'] ?: now()->format('H:i');

        EnvironmentWeatherRecord::create([
            'recording_date' => $validated['recording_date'],
            'recording_time' => $recTime,
            'day_name' => $dayName,
            'location_id' => $validated['location_id'] ?? null,
            'location_name' => $validated['location_name'] ?? null,
            'wind_speed' => (float) $validated['wind_speed'],
            'rainfall' => (float) $validated['rainfall'],
            'temperature' => (float) $validated['temperature'],
            'humidity' => (float) $validated['humidity'],
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => Auth::id(),
        ]);

        return redirect()->route('environment.cuaca.index')->with('success', 'Data pencatatan cuaca berhasil disimpan.');
    }

    public function cuacaUpdate(Request $request, EnvironmentWeatherRecord $record): RedirectResponse
    {
        $validated = $request->validate([
            'recording_date' => 'required|date',
            'recording_time' => 'nullable|string',
            'location_name' => 'nullable|string|max:150',
            'location_id' => 'nullable|integer',
            'wind_speed' => 'required|numeric|min:0',
            'rainfall' => 'required|numeric|min:0',
            'temperature' => 'required|numeric',
            'humidity' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $carbonDate = Carbon::parse($validated['recording_date']);
        $dayName = $days[$carbonDate->dayOfWeek] ?? 'Rabu';

        $recTime = $validated['recording_time'] ?: ($record->recording_time ?: now()->format('H:i'));

        $record->update([
            'recording_date' => $validated['recording_date'],
            'recording_time' => $recTime,
            'day_name' => $dayName,
            'location_id' => $validated['location_id'] ?? $record->location_id,
            'location_name' => $validated['location_name'] ?? $record->location_name,
            'wind_speed' => (float) $validated['wind_speed'],
            'rainfall' => (float) $validated['rainfall'],
            'temperature' => (float) $validated['temperature'],
            'humidity' => (float) $validated['humidity'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('environment.cuaca.index')->with('success', 'Data cuaca berhasil diperbarui.');
    }

    public function cuacaDestroy(EnvironmentWeatherRecord $record): RedirectResponse
    {
        $record->delete();
        return redirect()->route('environment.cuaca.index')->with('success', 'Data cuaca berhasil dihapus.');
    }

    public function cuacaExportPdf(Request $request): SymfonyResponse
    {
        $search = trim((string) $request->input('search'));
        $locFilter = $request->input('location');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EnvironmentWeatherRecord::with(['location', 'recorder'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('notes', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%");
                });
            })
            ->when($locFilter, function ($q) use ($locFilter) {
                $q->where(function ($sub) use ($locFilter) {
                    $sub->where('location_id', $locFilter)
                        ->orWhere('location_name', $locFilter);
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('recording_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('recording_date', '<=', $endDate))
            ->orderByDesc('recording_date')
            ->orderByDesc('recording_time')
            ->orderByDesc('id');

        $records = $query->get();

        $pdf = Pdf::loadView('exports.environment-cuaca-pdf', [
            'records' => $records,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
            'generatedBy' => Auth::user()?->name ?? 'Administrator HSSE',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_Cuaca_Lingkungan_' . date('Ymd_His') . '.pdf');
    }

    public function cuacaExportExcel(Request $request): SymfonyResponse
    {
        $records = EnvironmentWeatherRecord::with(['location', 'recorder'])
            ->orderByDesc('recording_date')
            ->orderByDesc('recording_time')
            ->get();
        $filename = 'Laporan_Cuaca_Lingkungan_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['No', 'Hari', 'Tanggal', 'Waktu', 'Kecepatan Angin (m/s)', 'Curah Hujan (mm/hr)', 'Temperatur (°C)', 'Humidity (%)', 'Keterangan', 'Pencatat']);

            foreach ($records as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r->day_name,
                    $r->formatted_date,
                    $r->formatted_time,
                    $r->wind_speed,
                    $r->rainfall,
                    $r->temperature,
                    $r->humidity,
                    $r->notes ?? '-',
                    $r->recorder?->name ?? 'Sistem',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // 8. Pengelolaan Master Data Environment
    // =========================================================================

    public function masterIndex(Request $request): View
    {
        $selectedCategory = $request->input('category', 'all');
        $search = trim((string) $request->input('search'));
        $categoryLabels = EnvironmentMaster::activeCategoryLabels();
        $allCategoryLabels = EnvironmentMaster::allCategoryLabels();
        $activeCategoryKeys = array_keys($categoryLabels);
        $unitSubmoduleOptions = EnvironmentMaster::unitSubmoduleOptions();

        $query = EnvironmentMaster::query()
            ->when($selectedCategory !== 'all', function ($q) use ($selectedCategory) {
                $q->where('category', $selectedCategory);
            }, function ($q) use ($activeCategoryKeys) {
                // By default 'all' focuses on active categories
                $q->whereIn('category', $activeCategoryKeys);
            })
            ->when($search, fn($q) => $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('category')
            ->orderBy('order_index')
            ->orderBy('name');

        $masters = $query->paginate(20)->withQueryString();

        return view('data.environment.master.index', compact('masters', 'selectedCategory', 'categoryLabels', 'allCategoryLabels', 'unitSubmoduleOptions'));
    }

    public function masterStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(array_keys(EnvironmentMaster::activeCategoryLabels()))],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'submodules' => ['nullable', 'array'],
            'submodules.*' => ['string', Rule::in(array_keys(EnvironmentMaster::unitSubmoduleOptions()))],
        ]);

        EnvironmentMaster::create([
            'category' => $validated['category'],
            'name' => trim($validated['name']),
            'code' => !empty($validated['code']) ? trim($validated['code']) : null,
            'description' => $validated['description'] ?? null,
            'order_index' => $validated['order_index'] ?? 0,
            'submodules' => $validated['category'] === 'unit' ? ($validated['submodules'] ?? []) : null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('environment.master.index', ['category' => $validated['category']])
            ->with('success', 'Data master berhasil ditambahkan.');
    }

    public function masterUpdate(Request $request, EnvironmentMaster $master): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'submodules' => ['nullable', 'array'],
            'submodules.*' => ['string', Rule::in(array_keys(EnvironmentMaster::unitSubmoduleOptions()))],
        ]);

        $updateData = [
            'name' => trim($validated['name']),
            'code' => !empty($validated['code']) ? trim($validated['code']) : null,
            'description' => $validated['description'] ?? null,
            'order_index' => $validated['order_index'] ?? 0,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : $master->is_active,
        ];

        if ($master->category === 'unit') {
            $updateData['submodules'] = $validated['submodules'] ?? [];
        }

        $master->update($updateData);

        return redirect()->route('environment.master.index', ['category' => $master->category])
            ->with('success', 'Data master berhasil diperbarui.');
    }

    public function masterToggle(EnvironmentMaster $master): RedirectResponse
    {
        $master->update(['is_active' => !$master->is_active]);
        $statusText = $master->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('environment.master.index', ['category' => $master->category])
            ->with('success', "Master data '{$master->name}' berhasil {$statusText}.");
    }

    public function masterDestroy(EnvironmentMaster $master): RedirectResponse
    {
        // Periksa apakah master ini digunakan oleh data historis di tabel manapun
        $isUsed = false;
        $reason = '';

        if ($master->category === 'unit') {
            if (EnvironmentLb3Record::where('unit_id', $master->id)->exists() ||
                EnvironmentNonLb3Record::where('unit_id', $master->id)->exists() ||
                EnvironmentRklRplRecord::where('unit_id', $master->id)->exists()) {
                $isUsed = true;
                $reason = 'Satuan ini masih digunakan pada catatan data limbah atau pemantauan.';
            }
        } elseif ($master->category === 'non_lb3_source') {
            if (EnvironmentNonLb3Record::where('source_id', $master->id)->exists()) {
                $isUsed = true;
                $reason = 'Sumber limbah ini masih digunakan pada catatan Non-LB3.';
            }
        } elseif ($master->category === 'rkl_rpl_parameter') {
            if (EnvironmentRklRplRecord::where('parameter_id', $master->id)->exists()) {
                $isUsed = true;
                $reason = 'Parameter ini masih digunakan pada catatan RKL–RPL.';
            }
        } elseif ($master->category === 'rkl_rpl_location') {
            if (EnvironmentRklRplRecord::where('location_id', $master->id)->exists()) {
                $isUsed = true;
                $reason = 'Lokasi ini masih digunakan pada catatan RKL–RPL.';
            }
        } elseif ($master->category === 'ppich_location') {
            if (EnvironmentPpichRecord::where('location_id', $master->id)->exists() ||
                EnvironmentInspectionRecord::where('location_id', $master->id)->exists()) {
                $isUsed = true;
                $reason = 'Lokasi ini masih digunakan pada catatan pemantauan lokasi.';
            }
        } elseif ($master->category === 'inspection_type') {
            if (EnvironmentInspectionRecord::where('inspection_type_id', $master->id)
                ->orWhere('inspection_type', $master->name)
                ->exists()) {
                $isUsed = true;
                $reason = 'Jenis inspeksi ini masih digunakan pada catatan data inspeksi.';
            }
        } elseif ($master->category === 'inspection_location') {
            if (EnvironmentInspectionRecord::where('location_id', $master->id)
                ->orWhere('location_name', $master->name)
                ->orWhere('reference_name', $master->name)
                ->exists() ||
                EnvironmentWeatherRecord::where('location_id', $master->id)
                ->orWhere('location_name', $master->name)
                ->exists()) {
                $isUsed = true;
                $reason = 'Lokasi ini masih digunakan pada catatan inspeksi atau cuaca.';
            }
        } elseif ($master->category === 'ews_location') {
            if (EnvironmentEwsRecord::where('location_id', $master->id)->exists() ||
                EnvironmentWeatherRecord::where('location_id', $master->id)->exists()) {
                $isUsed = true;
                $reason = 'Lokasi ini masih digunakan pada catatan EWS atau Cuaca.';
            }
        }

        if ($isUsed) {
            return redirect()->back()->with('error', "Master data '{$master->name}' tidak dapat dihapus karena memiliki riwayat data terkait ({$reason}). Silakan gunakan opsi nonaktifkan agar tidak muncul pada input baru namun histori tetap terjaga.");
        }

        $master->delete();
        return redirect()->route('environment.master.index', ['category' => $master->category])
            ->with('success', "Master data '{$master->name}' berhasil dihapus.");
    }
}
